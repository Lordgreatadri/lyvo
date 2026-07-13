<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PayoutChannel;
use App\Enums\PayoutStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payout;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Src\Domain\Payout\PayoutService;

/**
 * Admin\PayoutController
 * ----------------------
 * The disbursements console. Admins validate a recipient's registered name and
 * release escrow funds to operators' mobile-money wallets via Moolre transfers.
 * Every query is written to be cheap: the status breakdown is one grouped
 * aggregate, the log is paginated with a narrow column selection, and the
 * "awaiting payout" queue targets released orders that have no successful payout.
 */
class PayoutController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize(Permissions::PAYOUTS_VIEW);

        // Single grouped aggregate instead of one count per status.
        $counts = Payout::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusFilter = $request->query('status');

        $payouts = Payout::query()
            ->with(['recipientUser:id,name', 'payable'])
            ->when(
                in_array($statusFilter, array_column(PayoutStatus::cases(), 'value'), true),
                fn ($q) => $q->where('status', $statusFilter),
            )
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.payouts.index', [
            'payouts' => $payouts,
            'counts' => $counts,
            'statuses' => PayoutStatus::cases(),
            'statusFilter' => $statusFilter,
            'channels' => PayoutChannel::mobileMoneyCases(),
            'awaiting' => $this->awaitingPayout(),
            'settledTotal' => (float) Payout::successful()->sum('amount'),
        ]);
    }

    /** Validate a recipient's registered name before a transfer (AJAX). */
    public function validateName(Request $request, PayoutService $payouts): JsonResponse
    {
        $this->authorize(Permissions::PAYOUTS_MANAGE);

        $data = $request->validate([
            'channel' => ['required', Rule::enum(PayoutChannel::class)],
            'receiver' => ['required', 'string', 'max:32'],
        ]);

        $result = $payouts->validateName($data['receiver'], PayoutChannel::from($data['channel']));

        return response()->json([
            'ok' => $result->success,
            'name' => $result->recipientName,
            'message' => $result->success ? 'Name validated.' : $result->message,
        ], $result->success ? 200 : 422);
    }

    /** Initiate a disbursement to an operator. */
    public function store(Request $request, PayoutService $payouts): RedirectResponse
    {
        $this->authorize(Permissions::PAYOUTS_MANAGE);

        $data = $request->validate([
            'channel' => ['required', Rule::enum(PayoutChannel::class)],
            'receiver' => ['required', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:0.1', 'max:1000000'],
            'reference' => ['nullable', 'string', 'max:120'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')],
        ]);

        $order = null;
        $recipientUserId = null;

        if (! empty($data['order_id'])) {
            $order = Order::with('operator.user')->find($data['order_id']);

            if ($order === null || $order->status !== OrderStatus::Released) {
                return back()->with('error', 'Funds can only be paid out once the order has been released from escrow.');
            }

            if ($order->isPaidOut()) {
                return back()->with('error', 'This order has already been paid out to the operator.');
            }

            $recipientUserId = $order->operator?->user?->id;
        }

        $payout = $payouts->pay(
            amount: (float) $data['amount'],
            receiver: $data['receiver'],
            channel: PayoutChannel::from($data['channel']),
            context: $order ? 'escrow-release' : 'manual',
            recipientUserId: $recipientUserId,
            initiatedBy: $request->user()->id,
            reference: $data['reference'] ?? ($order?->order_number),
            payable: $order,
            recipientName: $data['recipient_name'] ?? null,
        );

        if ($payout->status === PayoutStatus::Failed) {
            return back()->with('error', $payout->failure_reason ?: 'The payout could not be completed.');
        }

        $message = $payout->status === PayoutStatus::Successful
            ? 'Payout successful — funds have been sent to the operator.'
            : 'Payout initiated — awaiting settlement confirmation from Moolre.';

        return back()->with('success', $message);
    }

    /** Re-check a payout's status with the gateway. */
    public function refreshStatus(Payout $payout, PayoutService $payouts): RedirectResponse
    {
        $this->authorize(Permissions::PAYOUTS_MANAGE);

        $payout = $payouts->syncStatus($payout);

        return back()->with('success', "Payout status refreshed — {$payout->status->label()}.");
    }

    /**
     * Released orders that still owe the operator a payout, newest first.
     *
     * @return \Illuminate\Support\Collection<int, Order>
     */
    private function awaitingPayout()
    {
        return Order::query()
            ->where('status', OrderStatus::Released->value)
            ->whereDoesntHave('payouts', fn ($q) => $q->where('status', PayoutStatus::Successful->value))
            ->with('operator.user:id,name,phone')
            ->latest('released_at')
            ->limit(25)
            ->get();
    }
}
