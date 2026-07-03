# Payment Gateway — Feature Documentation

> Technical reference for the LYVO payment layer (Phase 1: the isolated Moolre collection
> integration). It covers the architecture, how to collect a payment from anywhere in the
> app, the transaction lifecycle, settlement webhooks, configuration and testing. Platform
> orders/escrow (Phase 2) and dashboards (Phase 3) build on this foundation.
> For the SMS layer this mirrors, see [sms-api.md](sms-api.md).

## 1. Overview

Every platform collection in LYVO flows through **one** service and a **swappable
provider**, so the rest of the application never knows or cares which gateway is in use.

Design principles:

- **Single entry point** — features call `PaymentService::charge(...)`; they never talk to
  a gateway directly.
- **Provider-agnostic** — every gateway implements
  `Src\Domain\Payment\Contracts\PaymentProviderInterface`. Production uses **Moolre**;
  local and test environments use a network-free **`log`** driver.
- **Isolated/decoupled** — all payment code lives under `src/Domain/Payment` (PSR-4
  `Src\`). Adding a gateway does not touch a single call site.
- **Secrets stay in the environment** — API keys and the account number live in `.env`
  only, never in the database, so they never leak into a config cache or the UI.
- **Performance first** — the ledger is indexed on `[status, created_at]`, `payer`,
  `user_id`, `context` and `provider_transaction_id`; reconciliation targets a single
  indexed row by `ref`, never a scan.
- **Auditable** — every gateway exchange is written to the dedicated `moolre_paymentapi`
  log channel (`storage/logs/moolrepaymentapi.log`).

## 2. Collecting a payment

```php
use App\Enums\PaymentChannel;
use Src\Domain\Payment\PaymentService;

/** @var PaymentService $payments */
$payments = app(PaymentService::class);

$transaction = $payments->charge(
    amount: 40.00,
    payer: '0201234567',            // normalised to +233… internally
    channel: PaymentChannel::Mtn,   // MTN / Telecel / AirtelTigo
    context: 'order',               // reporting tag
    userId: $user->id,              // optional owner
    reference: 'ORD-1001',          // optional human reference
    payable: $order,                // optional polymorphic link (e.g. an escrow order)
);
```

`charge()` persists a `PaymentTransaction` (status `pending`), calls the gateway, and folds
the result onto the row. It never throws on a gateway error — the failure is captured on
the returned transaction (`status = failed`, `failure_reason` set).

### OTP flow

Moolre may require the payer to supply an OTP before the USSD prompt is triggered (response
code `TP14`). In that case the transaction lands in `awaiting_otp` with `otp_required = true`.
Complete it with the code the payer received:

```php
$transaction = $payments->submitOtp($transaction, '123456');
// → awaiting_approval (USSD prompt dispatched to the handset)
```

If no OTP is needed the charge lands directly in `awaiting_approval`.

### Reconciling status

Settlement is confirmed by the webhook (preferred) or by polling:

```php
$payments->syncStatus($transaction);   // POST /open/transact/status → applyStatus(...)
```

## 3. Transaction lifecycle (`PaymentStatus`)

| Status              | Meaning                                                        |
| ------------------- | ------------------------------------------------------------- |
| `pending`           | Row persisted, not yet sent to the gateway                    |
| `awaiting_otp`      | Gateway asked the payer for an OTP (Moolre code `TP14`)        |
| `awaiting_approval` | USSD prompt sent to the payer's handset                       |
| `processing`        | Accepted / collecting (Moolre `txstatus 0`)                   |
| `successful`        | Funds settled (Moolre `txstatus 1`) — terminal                |
| `failed`            | Rejected or undeliverable (Moolre `txstatus 2`) — terminal    |

`isTerminal()` is true for `successful`/`failed`; `color()` maps to Tailwind badge tokens
(amber / sky / emerald / rose).

## 4. Moolre contract

- Base URI `https://api.moolre.com`. Auth headers `X-API-USER` + `X-API-PUBKEY` (public
  key = collections).
- **Charge** — `POST /open/transact/payment`
  `{ type:1, channel, currency, payer, amount, externalref, otpcode?, reference?, sessionid?, accountnumber }`.
- **Status** — `POST /open/transact/status` `{ type:1, idtype:"externalref", id, accountnumber }`.
- Success is signalled by `status === 1` in the JSON body (not merely a 200 HTTP code).
  Code `TP14` = OTP required & sent. `txstatus`: 0 = Pending, 1 = Successful, 2 = Failed.
- Channel codes: MTN `13`, Telecel `6`, AirtelTigo `7` (see `PaymentChannel::moolreCode()`).

## 5. Settlement webhook

Moolre posts settlement callbacks to **`POST /api/webhooks/moolre/payment`** (registered in
the central `routes/webhook.php`, under the `api/webhooks` prefix, `api` middleware).

- Config: the `moolre-payment` entry in `config/webhook-client.php`.
- Validation: `App\Support\Webhooks\MoolrePaymentSignatureValidator` compares the shared
  secret carried in the request **body** at `data.secret` (constant-time). **Unlike the
  SMS webhook**, the secret is not a header. When no secret is configured (dev/sandbox)
  validation is skipped.
- Processing: `App\Jobs\ProcessMoolrePaymentWebhookJob` reads `data.{externalref, txstatus,
  transactionid, value, thirdpartyref}` and calls `PaymentService::applyStatus()`, which
  updates the single indexed row and stamps `completed_at` / `failed_at`.

Example payload:

```json
{
  "data": {
    "txstatus": 1,
    "payer": "233201234567",
    "accountnumber": "10000123",
    "amount": "40.00",
    "value": "39.50",
    "transactionid": "TX123",
    "externalref": "…uuid…",
    "thirdpartyref": "MO-…",
    "secret": "…shared secret…",
    "ts": "…"
  }
}
```

## 6. Configuration

`config/payment.php`:

| Key                                | Env                          | Default            |
| ---------------------------------- | ---------------------------- | ------------------ |
| `default`                          | `PAYMENT_PROVIDER`           | `log`              |
| `currency`                         | —                            | `GHS`              |
| `country_code`                     | `SMS_COUNTRY_CODE`           | `233`              |
| `log_channel`                      | `PAYMENT_LOG_CHANNEL`        | `moolre_paymentapi`|
| `providers.moolre.api_user`        | `MOOLRE_PAY_API_USER`        | —                  |
| `providers.moolre.pub_key`         | `MOOLRE_PAY_PUBKEY`          | —                  |
| `providers.moolre.priv_key`        | `MOOLRE_PAY_PRIVKEY`         | —                  |
| `providers.moolre.account_number`  | `MOOLRE_PAY_ACCOUNT_NUMBER`  | —                  |
| `webhook.secret`                   | `MOOLRE_PAY_WEBHOOK_SECRET`  | —                  |

The active provider can also be switched at runtime via the `payment_settings` row
(`PaymentSetting::current()->provider`), which overrides `config('payment.default')`.

Permissions: `payments.view`, `payments.manage` (the **Payments (gateway)** group in
`App\Support\Permissions`).

## 7. Adding another gateway

1. Implement `Src\Domain\Payment\Contracts\PaymentProviderInterface`.
2. Add a `config/payment.php` provider block.
3. Register it in `PaymentServiceProvider::makeProvider()`.
4. (If it posts callbacks) add a `config/webhook-client.php` config, a `ProcessWebhookJob`
   and a one-line route in `routes/webhook.php`.

No call sites change.

## 8. Testing

- `phpunit.xml` forces `PAYMENT_PROVIDER=log` and a blank `MOOLRE_PAY_WEBHOOK_SECRET`, so
  tests never reach the live gateway or move money (there is no `.env.testing`, so `.env`
  leaks into tests — every external service needs a phpunit override).
- Suites: `Unit\Payment\PaymentEnumsTest`, `Payment\MoolrePaymentProviderTest` (Guzzle
  mock — asserts the `X-API-USER`/`X-API-PUBKEY` headers and request shape),
  `Payment\PaymentServiceTest` (charge persistence, OTP flow, `applyStatus`/`syncStatus`),
  `Payment\MoolrePaymentWebhookTest` (endpoint accepts + stores `webhook_calls`, job
  reconciles Successful/Failed).

```powershell
php artisan test --filter="Payment"
```

## 9. Gotchas

- The Laravel query builder does **not** cast enums — `applyStatus()` persists
  `$status->value`, not the enum.
- The payment webhook secret is in the request **body** (`data.secret`), not a header.
- After editing `config/*`, run `php artisan config:clear`.
