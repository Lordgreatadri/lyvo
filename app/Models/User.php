<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use Dyrynda\Database\Support\BindsOnUuid;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * User
 * ----
 * Single authentication identity for every actor on LYVO. The `account_type`
 * column (admin | customer | operator) decides which dashboard the user is
 * routed to and which Spatie role they carry. Type-specific data lives in the
 * related profile (customerProfile / operatorProfile) to keep this table lean.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use BindsOnUuid, GeneratesUuid, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_type',
        'status',
        'name',
        'email',
        'phone',
        'password',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'uuid' => 'string',
        'account_type' => AccountType::class,
        'status' => UserStatus::class,
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    /* ----------------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------------*/

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function operatorProfile(): HasOne
    {
        return $this->hasOne(OperatorProfile::class);
    }

    public function deliveryAddresses(): HasMany
    {
        return $this->hasMany(DeliveryAddress::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function verificationCodes(): HasMany
    {
        return $this->hasMany(VerificationCode::class);
    }

    /* ----------------------------------------------------------------------
     | Type & status helpers
     * --------------------------------------------------------------------*/

    public function isAdmin(): bool
    {
        return $this->account_type === AccountType::Admin;
    }

    public function isCustomer(): bool
    {
        return $this->account_type === AccountType::Customer;
    }

    public function isOperator(): bool
    {
        return $this->account_type === AccountType::Operator;
    }

    public function hasVerifiedPhone(): bool
    {
        return ! is_null($this->phone_verified_at);
    }

    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill(['phone_verified_at' => $this->freshTimestamp()])->save();
    }

    /**
     * Whether this user has completed both contact verifications.
     */
    public function isFullyVerified(): bool
    {
        return $this->hasVerifiedEmail() && $this->hasVerifiedPhone();
    }

    /* ----------------------------------------------------------------------
     | Account status (admin moderation)
     * --------------------------------------------------------------------*/

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isFrozen(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    public function isBanned(): bool
    {
        return $this->status === UserStatus::Banned;
    }

    /**
     * Freeze (suspend) the account, blocking future logins.
     */
    public function freeze(): bool
    {
        return $this->forceFill(['status' => UserStatus::Suspended])->save();
    }

    /**
     * Reactivate a frozen/banned account.
     */
    public function unfreeze(): bool
    {
        return $this->forceFill(['status' => UserStatus::Active])->save();
    }

    /**
     * Route the user should land on after login, based on account type.
     */
    public function homeRoute(): string
    {
        return $this->account_type->homeRoute();
    }
}
