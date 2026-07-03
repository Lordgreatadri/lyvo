<?php

namespace App\Support;

use App\Enums\AccountType;

/**
 * Permissions
 * -----------
 * Single source of truth for every authorization permission on LYVO. Both the
 * RolePermissionSeeder and the admin "Roles & permissions" UI read from here, so
 * adding a capability is a one-line change that automatically flows to seeding,
 * the management screen and the default role matrix.
 *
 * Permissions are namespaced by domain ("users.view", "escrow.manage", …) to
 * keep them readable and to leave room for the platform to grow.
 */
class Permissions
{
    // ---- Verification centre ------------------------------------------------
    public const VERIFICATION_VIEW = 'verification.view';
    public const VERIFICATION_REVIEW = 'verification.review';
    public const VERIFICATION_APPROVE = 'verification.approve';
    public const VERIFICATION_REJECT = 'verification.reject';

    // ---- User management ----------------------------------------------------
    public const USERS_VIEW = 'users.view';
    public const USERS_APPROVE = 'users.approve';
    public const USERS_SUSPEND = 'users.suspend';
    public const USERS_DELETE = 'users.delete';

    // ---- Roles & permissions ------------------------------------------------
    public const ROLES_VIEW = 'roles.view';
    public const ROLES_MANAGE = 'roles.manage';
    public const ROLES_ASSIGN = 'roles.assign';

    // ---- Escrow -------------------------------------------------------------
    public const ESCROW_VIEW = 'escrow.view';
    public const ESCROW_MANAGE = 'escrow.manage';
    public const ESCROW_TRANSACT = 'escrow.transact';

    // ---- Disputes -----------------------------------------------------------
    public const DISPUTES_VIEW = 'disputes.view';
    public const DISPUTES_RESOLVE = 'disputes.resolve';

    // ---- Products & services ------------------------------------------------
    public const PRODUCTS_VIEW = 'products.view';
    public const PRODUCTS_MANAGE = 'products.manage';

    // ---- Reviews ------------------------------------------------------------
    public const REVIEWS_CREATE = 'reviews.create';
    public const REVIEWS_MODERATE = 'reviews.moderate';

    // ---- Reports & fraud ----------------------------------------------------
    public const REPORTS_VIEW = 'reports.view';
    public const REPORTS_RESOLVE = 'reports.resolve';

    // ---- Directory ----------------------------------------------------------
    public const DIRECTORY_VIEW = 'directory.view';

    // ---- Messaging (SMS) ----------------------------------------------------
    public const SMS_VIEW = 'sms.view';
    public const SMS_MANAGE = 'sms.manage';
    public const SMS_SEND = 'sms.send';

    // ---- Payments (gateway) -------------------------------------------------
    public const PAYMENTS_VIEW = 'payments.view';
    public const PAYMENTS_MANAGE = 'payments.manage';

    // ---- Customer self-service ---------------------------------------------
    public const ADDRESSES_MANAGE = 'addresses.manage';
    public const PAYMENT_METHODS_MANAGE = 'payment-methods.manage';

    /**
     * Every permission grouped for the management UI. The outer key is the group
     * heading; each entry is [permission => human description].
     *
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'Verification centre' => [
                self::VERIFICATION_VIEW => 'View operator applications and submitted documents',
                self::VERIFICATION_REVIEW => 'Move an application into "under review"',
                self::VERIFICATION_APPROVE => 'Approve an operator and unlock their dashboard',
                self::VERIFICATION_REJECT => 'Reject an operator application',
            ],
            'User management' => [
                self::USERS_VIEW => 'Browse and search all platform users',
                self::USERS_APPROVE => 'Approve pending accounts',
                self::USERS_SUSPEND => 'Freeze (suspend) or unfreeze user accounts',
                self::USERS_DELETE => 'Permanently remove a user account',
            ],
            'Roles & permissions' => [
                self::ROLES_VIEW => 'View roles and their permissions',
                self::ROLES_MANAGE => 'Reassign permissions to roles',
                self::ROLES_ASSIGN => 'Assign roles to users',
            ],
            'Escrow' => [
                self::ESCROW_VIEW => 'View escrow transactions',
                self::ESCROW_MANAGE => 'Advance / release escrow transactions',
                self::ESCROW_TRANSACT => 'Initiate an escrow-protected payment',
            ],
            'Disputes' => [
                self::DISPUTES_VIEW => 'View raised disputes',
                self::DISPUTES_RESOLVE => 'Resolve disputes (refund or release)',
            ],
            'Products & services' => [
                self::PRODUCTS_VIEW => 'View products and services',
                self::PRODUCTS_MANAGE => 'Add, edit and remove products and services',
            ],
            'Reviews' => [
                self::REVIEWS_CREATE => 'Leave a review on an operator',
                self::REVIEWS_MODERATE => 'Moderate or remove reviews',
            ],
            'Reports & fraud' => [
                self::REPORTS_VIEW => 'View fraud reports and trust violations',
                self::REPORTS_RESOLVE => 'Action fraud reports',
            ],
            'Discovery' => [
                self::DIRECTORY_VIEW => 'Browse the verified operator directory',
            ],
            'Messaging (SMS)' => [
                self::SMS_VIEW => 'View SMS balance, sender IDs and the message log',
                self::SMS_MANAGE => 'Configure the SMS gateway, sender ID and credit alerts',
                self::SMS_SEND => 'Send SMS messages (including test sends)',
            ],
            'Payments (gateway)' => [
                self::PAYMENTS_VIEW => 'View payment transactions and settlement analytics',
                self::PAYMENTS_MANAGE => 'Configure the payment gateway and reconcile transactions',
            ],
            'Customer self-service' => [
                self::ADDRESSES_MANAGE => 'Manage delivery addresses',
                self::PAYMENT_METHODS_MANAGE => 'Manage saved payment methods',
            ],
        ];
    }

    /**
     * Flat list of every permission name.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        $all = [];

        foreach (self::groups() as $permissions) {
            foreach (array_keys($permissions) as $name) {
                $all[] = $name;
            }
        }

        return $all;
    }

    /**
     * Default permission set granted to each account-type role at seed time.
     * Admins receive everything (see the seeder + the super-admin gate).
     *
     * @return array<int, string>
     */
    public static function forRole(AccountType $role): array
    {
        return match ($role) {
            AccountType::Admin => self::all(),

            AccountType::Operator => [
                self::DIRECTORY_VIEW,
                self::PRODUCTS_VIEW,
                self::PRODUCTS_MANAGE,
                self::ESCROW_VIEW,
                self::ESCROW_MANAGE,
                self::DISPUTES_VIEW,
                self::VERIFICATION_VIEW,
                self::REVIEWS_CREATE,
            ],

            AccountType::Customer => [
                self::DIRECTORY_VIEW,
                self::ESCROW_VIEW,
                self::ESCROW_TRANSACT,
                self::DISPUTES_VIEW,
                self::REVIEWS_CREATE,
                self::ADDRESSES_MANAGE,
                self::PAYMENT_METHODS_MANAGE,
            ],
        };
    }
}
