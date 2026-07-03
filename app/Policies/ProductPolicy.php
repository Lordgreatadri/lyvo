<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Permissions;

/**
 * ProductPolicy
 * -------------
 * Authorizes operator catalogue actions. The super-admin gate short-circuits
 * every check for admins. For operators, holding `products.manage` is necessary
 * but not sufficient: an operator may only touch items in their *own* catalogue,
 * which `owns()` enforces via the profile relationship.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::PRODUCTS_VIEW);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can(Permissions::PRODUCTS_VIEW) && $this->owns($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::PRODUCTS_MANAGE)
            && (bool) $user->operatorProfile?->isApproved();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can(Permissions::PRODUCTS_MANAGE) && $this->owns($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can(Permissions::PRODUCTS_MANAGE) && $this->owns($user, $product);
    }

    /** True when the item belongs to the acting operator's profile. */
    private function owns(User $user, Product $product): bool
    {
        $profileId = $user->operatorProfile?->getKey();

        return $profileId !== null && (int) $product->operator_profile_id === (int) $profileId;
    }
}
