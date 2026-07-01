# Change Log

All notable changes to the LYVO platform are documented here. Dates use `YYYY-MM-DD`.

---

## [Unreleased] — Authorization (RBAC)

> Role-based access control built on **spatie/laravel-permission**. Roles map 1:1 to
> `AccountType` (admin / customer / operator); guests remain unauthenticated public
> visitors (session flag, not a role). A central permission catalog is the single
> source of truth, admins get a super-admin gate, and every privileged action is
> gated by a policy or `permission:*` check. Admins can manage users, approve
> operators, freeze/unfreeze accounts, assign roles, and tune each role's permissions.

### Added

**Authorization core**
- `Support\Permissions` — single source of truth for the permission catalog. Grouped
  by domain (verification, users, roles, escrow, disputes, products, reviews, reports,
  directory, addresses, payment methods) with `groups()` (UI), `all()` (flat) and
  `forRole(AccountType)` (default matrix).
- `Policies\UserPolicy` — `viewAny/view/approve/suspend/assignRoles/delete`; self-guard
  prevents an admin from freezing / re-roling / deleting their own account.
- `Policies\RolePolicy` — `viewAny/update`.
- `Providers\AuthServiceProvider` — registers policies and a `Gate::before`
  super-admin short-circuit for the admin role.

**Admin user & role management**
- `Admin\UserController` — searchable/filterable user directory (type, status, pending
  operators, free-text), user detail, approve operator, freeze/unfreeze, assign roles.
- `Admin\RoleController` — grouped permission editor per role.
- `Services\OperatorReviewService` — shared operator verification state machine
  (`markInReview/approve/reject`) with transactional audit events, reused by the
  operator-approval and user-management controllers.
- Form requests `Admin\UpdateUserRolesRequest`, `Admin\UpdateRolePermissionsRequest`.
- Views `admin/users/index`, `admin/users/show`, `admin/roles/index`.

**Profile & settings (all roles)**
- Branded, role-aware `settings/profile` page (personal info, password, delete account)
  rendered inside each dashboard. Changing email or phone resets its verification and
  re-issues an OTP.

### Changed
- `User` model — account-status helpers `isActive/isFrozen/isBanned/freeze/unfreeze`.
- `UserStatus` enum — `badgeColor()` + `description()` for status UI.
- `RolePermissionSeeder` — idempotent; builds roles from `AccountType` and syncs the
  default permission matrix from `Support\Permissions`.
- `Admin\OperatorApprovalController` — refactored onto `OperatorReviewService`, with
  `permission:*` authorization on review/approve/reject.
- `ProfileController` + `ProfileUpdateRequest` — real profile updates (name, email,
  phone) with contact re-verification.
- Dashboard navigation — admin **Users** → user management, new **Roles** entry, and a
  **Settings** entry for every role.
- `routes/web.php` — admin `users.*` and `roles.*` routes under
  `auth + verified.contacts + account:admin`.

### Notes
- Guests are unauthenticated read-only visitors (session flag `lyvo_guest`), not a
  Spatie role.
- Re-seed permissions after changes: `php artisan db:seed --class=RolePermissionSeeder`.

---

## [Unreleased] — Authentication

> Single `users` table for every actor (admin / customer / operator), discriminated
> by `account_type`. Email **and** phone are verified via OTP (logged locally, SMS
> later). Operators require admin approval before their dashboard unlocks. Records
> are resolved by `uuid`, never the auto-increment primary key.

### Added

**Enums** (`app/Enums`)
- `AccountType` — admin / customer / operator; maps to home route + Spatie role.
- `UserStatus` — active / suspended / pending lifecycle states.
- `OperatorVerificationStatus` — pending / in_review / approved / rejected.
- `OtpChannel` — email / sms.
- `OtpPurpose` — email & phone verification (extensible for password reset, login).
- `PaymentMethodType` — supported saved payment instruments.

**Models** (`app/Models`)
- `User` — `MustVerifyEmail`, soft deletes, UUID binding, Sanctum, Spatie roles;
  relations to profiles, delivery addresses, payment methods, verification codes;
  helpers `isAdmin/isCustomer/isOperator`, `hasVerifiedPhone`, `isFullyVerified`, `homeRoute`.
- `CustomerProfile`, `OperatorProfile` (Spatie media library for Ghana Card + video),
  `OperatorVerificationEvent` (approval audit trail), `VerificationCode` (hashed OTP),
  `DeliveryAddress`, `PaymentMethod`, `GuestCustomer`, `BusinessCategory`.

**Migrations** (`database/migrations`)
- Rebuilt `users` table: `uuid`, `account_type`, `status`, `phone` + `phone_verified_at`,
  `last_login_at`, soft deletes.
- `business_categories`, `customer_profiles`, `operator_profiles`,
  `operator_verification_events`, `verification_codes`, `delivery_addresses`,
  `payment_methods`, `guest_customers`.

**Controllers**
- `Auth\RegisteredUserController` — customer self-registration.
- `Auth\OperatorRegistrationController` — operator onboarding wizard (business info,
  Ghana Card, verification video) creating a pending account.
- `Auth\OtpVerificationController` — send/verify email & phone codes.
- `Admin\OperatorApprovalController` — review / approve / reject operators.
- `Customer\DeliveryAddressController` — address book (max 3, one default).
- `Customer\PaymentMethodController` — saved payment methods.
- `Operator\DashboardController` — pending status page + gated dashboard.
- `Concerns\RedirectsUsers` — shared post-auth redirect logic.

**Middleware** (`app/Http/Middleware`, aliased in `Http/Kernel`)
- `EnsureAccountType` (`account:*`) — restrict routes by account type.
- `EnsureContactsVerified` (`verified.contacts`) — require verified email + phone.
- `EnsureOperatorApproved` (`operator.approved`) — gate operator dashboard on approval.

**Services / Notifications**
- `Services\OtpService` — issue, throttle, verify and deliver one-time codes.
- `OtpNotification` — email delivery of codes.

**Form Requests**
- `Auth\CustomerRegistrationRequest`, `Auth\OperatorRegistrationRequest`,
  `Customer\*` request validation.

**Config**
- `config/lyvo.php` — OTP settings (length, expiry, attempts, resend throttle,
  log toggle) and customer limit (`max_delivery_addresses = 3`).

**Seeders**
- `RolePermissionSeeder` — admin / customer / operator roles.
- `BusinessCategorySeeder` — operator categories.
- `DemoUserSeeder` — one fully-verified account per type + a pending operator.

**Views**
- OTP verification, operator pending status, admin operator review, customer
  address book and payment-method screens.

### Changed
- `routes/web.php` & `routes/auth.php` — real auth/verification/account-type gating;
  unified OTP flow replaces Breeze's email-link verification.
- `AuthenticatedSessionController`, `LoginRequest` — login by email or phone,
  `last_login_at` tracking, type-aware redirect.
- `EventServiceProvider`, `DatabaseSeeder` — wired new seeders/listeners.

### Fixed
- **Logout** — the dashboard sidebar control was a leftover "Exit demo" link to `home`;
  replaced with a proper `POST` logout form so it terminates the session.
- **Pending operator login** — `RedirectsUsers` now sends verified-but-unapproved
  operators straight to `operator.pending`; the status page greets the operator by
  name and explains they'll be notified by email + SMS once approved or otherwise.
- **Operator registration submit** — raised local PHP limits (`post_max_size`,
  `upload_max_filesize` → 64M, `memory_limit` → 256M) so the Ghana Card images + video
  submission no longer exceeds `post_max_size`, which was silently dropping the POST
  (and its CSRF token), making the submit button appear to do nothing.

### Notes
- `QUEUE_CONNECTION=sync`; OTP codes are written to the log channel locally.
- Verify locally: `php artisan migrate --seed`, then `php artisan serve`.
- Demo logins (password `password`): `admin@lyvo.test`, `customer@lyvo.test`,
  `operator@lyvo.test` (approved), `pending-operator@lyvo.test` (awaiting approval).

---

## [0.1.0] — Prototype (Phase 1)
- LYVO design system (Tailwind brand tokens, component classes, layouts).
- Placeholder data layer (`App\Support\DemoData`) with UUID-resolved routes.
- Public landing, operator directory/profile, onboarding wizard, customer/operator/admin
  dashboards, escrow walkthrough.
