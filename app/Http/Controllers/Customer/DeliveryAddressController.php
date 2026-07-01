<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreDeliveryAddressRequest;
use App\Models\DeliveryAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * DeliveryAddressController
 * -------------------------
 * Customer delivery address book. Business rules enforced here:
 *  - a customer may keep at most config('lyvo.customer.max_delivery_addresses') (3)
 *  - exactly one address is the default; setting a new default clears the rest
 *  - the first address added becomes the default automatically
 */
class DeliveryAddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.addresses.index', [
            'addresses' => $request->user()->deliveryAddresses()->orderByDesc('is_default')->latest()->get(),
            'maxReached' => $this->maxReached($request),
        ]);
    }

    public function store(StoreDeliveryAddressRequest $request): RedirectResponse
    {
        if ($this->maxReached($request)) {
            return back()->withErrors([
                'address_line' => 'You can save up to '.config('lyvo.customer.max_delivery_addresses').' delivery addresses.',
            ]);
        }

        $user = $request->user();
        $isFirst = $user->deliveryAddresses()->count() === 0;
        $makeDefault = $request->boolean('is_default') || $isFirst;

        DB::transaction(function () use ($user, $request, $makeDefault) {
            $address = $user->deliveryAddresses()->create(
                array_merge($request->safe()->except('is_default'), ['is_default' => $makeDefault])
            );

            if ($makeDefault) {
                $this->promoteDefault($user->id, $address->id);
            }
        });

        return back()->with('status', 'Delivery address saved.');
    }

    public function update(StoreDeliveryAddressRequest $request, DeliveryAddress $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);

        $makeDefault = $request->boolean('is_default');

        DB::transaction(function () use ($request, $address, $makeDefault) {
            $address->update($request->safe()->except('is_default'));

            if ($makeDefault) {
                $this->promoteDefault($address->user_id, $address->id);
            }
        });

        return back()->with('status', 'Delivery address updated.');
    }

    public function setDefault(Request $request, DeliveryAddress $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);
        $this->promoteDefault($address->user_id, $address->id);

        return back()->with('status', 'Default delivery address updated.');
    }

    public function destroy(Request $request, DeliveryAddress $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);

        $wasDefault = $address->is_default;
        $address->delete();

        // Promote another address to default if we just removed the default one.
        if ($wasDefault) {
            $next = $request->user()->deliveryAddresses()->latest()->first();
            if ($next) {
                $this->promoteDefault($next->user_id, $next->id);
            }
        }

        return back()->with('status', 'Delivery address removed.');
    }

    /**
     * Make one address the sole default for the user.
     */
    private function promoteDefault(int $userId, int $addressId): void
    {
        DeliveryAddress::where('user_id', $userId)->update(['is_default' => false]);
        DeliveryAddress::whereKey($addressId)->update(['is_default' => true]);
    }

    private function maxReached(Request $request): bool
    {
        return $request->user()->deliveryAddresses()->count()
            >= (int) config('lyvo.customer.max_delivery_addresses', 3);
    }

    private function authorizeAddress(Request $request, DeliveryAddress $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403);
    }
}
