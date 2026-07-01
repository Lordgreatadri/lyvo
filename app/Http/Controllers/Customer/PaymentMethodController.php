<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StorePaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * PaymentMethodController
 * -----------------------
 * Customer saved payment instruments. Same default-handling rules as delivery
 * addresses; only non-sensitive metadata is ever stored.
 */
class PaymentMethodController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.payment-methods.index', [
            'methods' => $request->user()->paymentMethods()->orderByDesc('is_default')->latest()->get(),
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $user = $request->user();
        $isFirst = $user->paymentMethods()->count() === 0;
        $makeDefault = $request->boolean('is_default') || $isFirst;

        DB::transaction(function () use ($user, $request, $makeDefault) {
            $method = $user->paymentMethods()->create(
                array_merge($request->safe()->except('is_default'), ['is_default' => $makeDefault])
            );

            if ($makeDefault) {
                $this->promoteDefault($user->id, $method->id);
            }
        });

        return back()->with('status', 'Payment method saved.');
    }

    public function setDefault(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->authorizeMethod($request, $paymentMethod);
        $this->promoteDefault($paymentMethod->user_id, $paymentMethod->id);

        return back()->with('status', 'Default payment method updated.');
    }

    public function destroy(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->authorizeMethod($request, $paymentMethod);

        $wasDefault = $paymentMethod->is_default;
        $paymentMethod->delete();

        if ($wasDefault) {
            $next = $request->user()->paymentMethods()->latest()->first();
            if ($next) {
                $this->promoteDefault($next->user_id, $next->id);
            }
        }

        return back()->with('status', 'Payment method removed.');
    }

    private function promoteDefault(int $userId, int $methodId): void
    {
        PaymentMethod::where('user_id', $userId)->update(['is_default' => false]);
        PaymentMethod::whereKey($methodId)->update(['is_default' => true]);
    }

    private function authorizeMethod(Request $request, PaymentMethod $paymentMethod): void
    {
        abort_unless($paymentMethod->user_id === $request->user()->id, 403);
    }
}
