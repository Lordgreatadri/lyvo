<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PaymentChannel;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Src\Domain\Commerce\OrderService;

/**
 * CheckoutController
 * ------------------
 * Turns a public product into an escrow-protected order. The buyer reviews the
 * item, picks a delivery address (from their book) and a mobile-money channel,
 * then OrderService opens the payment and holds the funds in escrow.
 */
class CheckoutController extends Controller
{
    public function create(Request $request, Product $product): View|RedirectResponse
    {
        $addresses = $request->user()
            ->deliveryAddresses()
            ->orderByDesc('is_default')
            ->get();

        if ($addresses->isEmpty()) {
            return redirect()
                ->route('customer.addresses.index')
                ->with('error', 'Please add a delivery address before checking out.');
        }

        $product->load('operator', 'category');

        if (! $product->isPublished()) {
            return redirect()->route('store.index')->with('error', 'This item is no longer available.');
        }

        return view('checkout.create', [
            'product' => $product,
            'addresses' => $addresses,
            'channels' => PaymentChannel::cases(),
        ]);
    }

    public function store(Request $request, Product $product, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'channel' => ['required', Rule::enum(PaymentChannel::class)],
            'payer_phone' => ['required', 'string', 'max:20'],
            'delivery_address_id' => ['required', 'integer', Rule::exists('delivery_addresses', 'id')->where('user_id', $request->user()->id)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $address = isset($data['delivery_address_id'])
            ? $request->user()->deliveryAddresses()->find($data['delivery_address_id'])
            : null;

        try {
            $order = $orders->place(
                customer: $request->user(),
                product: $product,
                quantity: (int) $data['quantity'],
                channel: PaymentChannel::from($data['channel']),
                payerPhone: $data['payer_phone'],
                address: $address,
                note: $data['note'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('customer.orders.show', $order)
            ->with('success', 'Payment started — your funds are protected by LYVO escrow.');
    }
}
