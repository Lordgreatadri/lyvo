# Change Log

All notable changes to the LYVO platform are documented here. Dates use `YYYY-MM-DD`.

---

## [Unreleased] — Payment Gateway (Moolre) — Phase 1

> A scalable, provider-agnostic payment layer living under `src/Domain/Payment`, built to
> the same isolated/decoupled pattern as the SMS domain. Every platform collection flows
> through one `PaymentService`, backed by a swappable provider (Moolre in production, a
> network-free `log` driver in dev/test). A durable `payment_transactions` ledger records
> each collection and its full lifecycle (pending → OTP/approval → settled), and Moolre
> settlement callbacks reconcile status via a signed Spatie webhook. New gateways drop in
> without touching call sites. Every gateway exchange is written to the dedicated
> `moolre_paymentapi` log channel. This phase delivers the isolated gateway integration
> and its tests; platform orders/escrow (Phase 2) and dashboards (Phase 3) build on top.

### Added

**Payment domain (`src/Domain/Payment`, PSR-4 `Src\`)**
- `Contracts\PaymentProviderInterface` — provider contract: `name/charge/status`.
- `Providers\MoolrePaymentProvider` — live Guzzle client, `X-API-USER` + `X-API-PUBKEY`
  auth headers; charge `POST /open/transact/payment` (`type:1`), status
  `POST /open/transact/status` (`type:1`, `idtype:externalref`). Interprets Moolre
  `status===1` as accepted, code `TP14` as OTP-required, and maps `txstatus`
  (0/1/2 → Processing/Successful/Failed).
- `Providers\LogPaymentProvider` — network-free driver used in local/testing so no real
  money moves.
- `PaymentService` — the application entry point: normalises the payer, persists a
  `PaymentTransaction`, delegates to the active provider, drives the OTP flow
  (`charge/submitOtp`), and reconciles settlement (`syncStatus/applyStatus`).
- `DTOs\PaymentRequestDto` (immutable, uuid `externalRef` idempotency key, `withOtp()`),
  `DTOs\PaymentResult` (`accepted/failed` factories).

**Enums / models / migrations**
- `Enums\PaymentStatus` (pending, awaiting_otp, awaiting_approval, processing, successful,
  failed) with `label/color/isTerminal/isOpen` and `fromMoolreTxStatus()`;
  `Enums\PaymentChannel` (MTN/Telecel/AirtelTigo) with `moolreCode()` 13/6/7.
- `PaymentTransaction` — one row per collection (ref, provider ids, channel, amount/value,
  payer, status, polymorphic `payable`, otp flags, settlement timestamps, `meta`) with
  `[status, created_at]` + payer/user/context/provider-txn indexes.
- `PaymentSetting` — single-row config cache (provider, currency) via memoised
  `current()`/`flushCache()`.
- Migrations: `create_payment_settings_table` (standalone payment-integration migration,
  first), then `create_payment_transactions_table`.

**Webhooks**
- Spatie webhook-client `moolre-payment` config → `Jobs\ProcessMoolrePaymentWebhookJob`
  reconciles settlement by `externalref`; `Support\Webhooks\MoolrePaymentSignatureValidator`
  verifies the shared secret carried in the request **body** (`data.secret`, unlike the
  header-based SMS validator), constant-time, skipping only when no secret is configured.
- Route `/api/webhooks/moolre/payment` registered in the central `routes/webhook.php`.

**Config / permissions**
- `config/payment.php` — default driver, currency (GHS), country code (233), status cache
  TTL, `moolre_paymentapi` log channel, the `moolre` / `log` provider blocks and the
  webhook secret.
- Permissions `payments.view`, `payments.manage` (new **Payments (gateway)** group in
  `Support\Permissions`).
- `App\Providers\PaymentServiceProvider` (deferred) — binds the active provider from
  `PaymentSetting::current()->provider` (falling back to `config('payment.default')`),
  registered in `config/app.php`.

### Notes
- Provider is swappable at runtime via the payment settings; add a new gateway by
  implementing `PaymentProviderInterface`, registering a `config/payment.php` block and a
  webhook config — no call sites change.
- **Test isolation:** `phpunit.xml` forces `PAYMENT_PROVIDER=log` and blank
  `MOOLRE_PAY_WEBHOOK_SECRET`, so tests never reach the live gateway or move money.
- The Moolre payment webhook secret is in the **body** (`data.secret`), not a header —
  the validator differs from the SMS one accordingly.
- Query builder does **not** cast enums — `applyStatus()` persists `$status->value`.
- Full docs: [DevDocs/payment-api.md](DevDocs/payment-api.md).

### Tests
- `Unit\Payment\PaymentEnumsTest`, `Payment\MoolrePaymentProviderTest` (Guzzle mock),
  `Payment\PaymentServiceTest`, `Payment\MoolrePaymentWebhookTest` — 14 tests; full suite
  81 passing.

### Roadmap (upcoming)
- **Phase 2** — platform order/escrow migration (after the payment migration), Order model
  with a `payable` morph to `PaymentTransaction`, escrow lifecycle service and the
  customer-pay / operator-fulfil / buyer-confirm flows.
- **Phase 3** — admin payments analytics dashboard, operator transaction details and
  customer escrow views.

---

## [Unreleased] — SMS Gateway (Moolre)

> A scalable, provider-agnostic SMS layer living under `src/Domain/Sms`. Every SMS in
> the application is sent through one reusable `send_sms()` helper, backed by a swappable
> provider (Moolre in production, a network-free `log` driver in dev/test). Admins can
> configure the sender ID, watch the account balance, set a low-credit threshold that
> auto-alerts them, browse approved sender IDs, and send test messages. Delivery receipts
> arrive via a signed Spatie webhook and reconcile each message's status by reference.
> The integration is fully isolated/decoupled so new gateways drop in without touching
> call sites.

### Added

**SMS domain (`src/Domain/Sms`, PSR-4 `Src\`)**
- `Contracts\SmsProviderInterface` — provider contract: `name/send/sendBatch/statuses/
  balance/senderIds`.
- `Providers\MoolreSmsProvider` — live Guzzle client, `X-API-VASKEY` auth (per-request
  header), maps Moolre payloads (send `type:1`; status `type:5`; balance `type:2`;
  sender IDs `type:7`).
- `Providers\LogSmsProvider` — network-free driver used in local/testing so no real
  messages are sent or charged.
- `SmsService` — the application entry point: normalises the recipient, persists an
  `SmsMessage`, delegates to the active provider, records the outcome, and exposes
  `reconcileStatuses/applyStatus`, cached `balance()` and `senderIds()`.
- `DTOs\SmsMessageDto`, `DTOs\SmsResult`, `Support\SmsEncoding` (GSM-7 160/153 vs
  UCS-2 70/67 segment maths), `helpers.php` (`send_sms()`, `sms()`,
  `format_phone_for_sms()` → `+233…`).

**Models / migrations**
- `SmsMessage` — one row per outbound message (ref, provider, recipient, status,
  encoding, segments, timestamps) with `[status, created_at]` + context/recipient/user
  indexes.
- `SmsSetting` — single-row config cache (provider, sender ID, low-credit threshold,
  cached balance + snapshot, alert throttle).

**Admin console**
- `Admin\SmsController` + `admin/sms/index` view — balance card + refresh, status
  breakdown, settings form (provider / sender ID / threshold), approved sender-ID list,
  test-send form and a paginated, filterable message log.
- Routes `admin.sms.{index,settings,balance,test}`; permissions `sms.view`,
  `sms.manage`, `sms.send` (new **Messaging (SMS)** group in `Support\Permissions`);
  **SMS** nav entry for admins.

**Webhooks & alerts**
- Spatie webhook-client `moolre` config → `Jobs\ProcessMoolreSmsWebhookJob` reconciles
  delivery receipts by reference; `Support\Webhooks\MoolreSignatureValidator` verifies
  the shared secret (constant-time), skipping only when no secret is configured.
- `Console\Commands\CheckSmsBalance` (`sms:check-balance`, scheduled hourly) alerts
  admins via `LowSmsCreditNotification` when the balance drops below the threshold,
  throttled to once per 6h.

**Config**
- `config/sms.php` — default driver, sender ID, country code (233), low-credit
  threshold, balance/sender-ID cache TTLs and the `moolre` / `log` provider blocks.

### Changed
- `Services\OtpService` — the SMS channel now sends through `send_sms()` instead of
  logging only.
- `App\Providers\SmsServiceProvider` (deferred) — binds the active provider from
  `SmsSetting::current()->provider` (falling back to `config('sms.default')`).
- Webhook routing centralised: all inbound gateway callbacks live in `routes/webhook.php`
  (loaded under the `api/webhooks` prefix, `api` middleware) — Moolre posts to
  `/api/webhooks/moolre/sms`. Removed the stock empty `default` webhook-client config.
- `Console\Kernel` — schedules `sms:check-balance` hourly.

### Notes
- Provider is swappable at runtime via the SMS settings; add a new gateway by
  implementing `SmsProviderInterface`, registering a `config/sms.php` block and a
  webhook config — no call sites change.
- **Test isolation:** `phpunit.xml` forces `SMS_PROVIDER=log` and blank
  `MOOLRE_WEBHOOK_SECRET`, so tests never reach the live gateway. Any future external
  API needs the same override (no `.env.testing` exists — `.env` leaks into tests).
- Query builder does **not** cast enums — `applyStatus()` persists `$status->value`.
- Full docs: [DevDocs/sms-api.md](docs/sms-api.md).

### Tests
- `Sms\SmsEncodingTest`, `Sms\MoolreSmsProviderTest` (Guzzle mock), `Sms\SmsServiceTest`,
  `Sms\OtpSmsDeliveryTest`, `Sms\MoolreWebhookTest`, `Sms\CheckSmsBalanceCommandTest`,
  `Admin\SmsManagementTest` — 29 tests; full suite 67 passing.

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

### Fixed (PR review)
- **Super-admin gate no longer defeats self-guards** — `Gate::before` now defers the
  self-guarded policy abilities (`suspend`, `assignRoles`, `delete`) to the policy, so
  an admin still cannot freeze / re-role / delete their own account.
- **Status vs. reactivation mismatch** — `User::unfreeze()` only reactivates a *frozen*
  (suspended) account and leaves *banned* accounts untouched; added `ban()` / `unban()`
  so the "permanently blocked" description holds true.
- **PATCH profile semantics** — `ProfileUpdateRequest` uses `sometimes` so partial
  updates (e.g. name + email only) are still valid; contacts are validated only when
  present.
- **Permission constants** — `OperatorApprovalController` authorizes via
  `Support\Permissions::VERIFICATION_*` constants instead of raw strings.
- **UI copy** — fixed double-escaped "&amp;" in the Profile and Roles page headings.
- **Tests** — added `Admin\UserManagementTest` and `Admin\RolePermissionTest` covering
  access control, freeze/unfreeze, role assignment, operator approval and the self-guard
  rails; `UserFactory` now seeds `account_type` + `status`.

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
