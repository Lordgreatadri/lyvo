<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Src\Domain\Commerce\EscrowService;
use Src\Domain\Payment\PaymentService;

/**
 * Customer OrderController
 * ------------------------
 * The buyer's window on their escrow orders: track the lifecycle, confirm
 * delivery (releasing funds to the seller) or raise a dispute for admin review.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::forCustomer($request->user()->id)
            ->with(['operator:id,uuid,business_name', 'items'])
            ->latest()
            ->paginate(12);

        return view('orders.index', [
            'role' => 'customer',
            'orders' => $orders,
            'summary' => $this->summary($request),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);

        $order->load(['operator:id,uuid,business_name', 'items.product', 'events.actor', 'payment']);

        return view('orders.show', [
            'role' => 'customer',
            'order' => $order,
        ]);
    }

    public function confirm(Request $request, Order $order, EscrowService $escrow): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        try {
            $escrow->confirmDelivery($order, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Delivery confirmed — funds released to the seller.');
    }

    public function dispute(Request $request, Order $order, EscrowService $escrow): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $escrow->raiseDispute($order, $request->user(), $data['reason']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Dispute submitted — our team will review it shortly.');
    }

    /**
     * Submit the OTP Moolre sent to the payer to authorise the collection. The
     * PaymentService verifies the number and initiates the payment; settlement
     * then advances the order to escrow via the payment webhook.
     */
    public function submitOtp(Request $request, Order $order, PaymentService $payments): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        $data = $request->validate([
            'otp' => ['required', 'string', 'min:4', 'max:8'],
        ]);

        $transaction = $order->payment;

        if ($transaction === null || $transaction->status !== \App\Enums\PaymentStatus::AwaitingOtp) {
            return back()->with('error', 'This payment is not awaiting an OTP.');
        }

        $transaction = $payments->submitOtp($transaction, $data['otp']);

        if ($transaction->status === \App\Enums\PaymentStatus::Failed) {
            return back()->with('error', $transaction->failure_reason ?: 'That OTP could not be verified. Please try again.');
        }

        return back()->with('success', 'OTP verified — approve the payment prompt on your phone to protect your funds in escrow.');
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->customer_id !== $request->user()->id) {
            throw new NotFoundHttpException('Order not found.');
        }
    }

    /** @return array<string, mixed> */
    private function summary(Request $request): array
    {
        $orders = Order::forCustomer($request->user()->id)->get(['status', 'total']);

        return [
            'active' => $orders->filter(fn ($o) => $o->status->isEscrowHeld())->count(),
            'protected' => $orders->filter(fn ($o) => $o->status->isEscrowHeld())->sum('total'),
            'completed' => $orders->where('status', \App\Enums\OrderStatus::Released)->count(),
            'total' => $orders->count(),
        ];
    }
}
