# SMS Gateway — Feature Documentation

> Technical reference for the LYVO SMS layer. It covers the architecture, how to send an
> SMS from anywhere in the app, the admin console, delivery webhooks, low-credit alerts,
> configuration and testing. For authorization see [authorization.md](authorization.md).

## 1. Overview

Every outbound SMS in LYVO flows through **one** reusable entry point and a **swappable
provider**. The goal is that the rest of the application never knows or cares which
gateway is in use.

Design principles:

- **Single call site** — features call `send_sms(...)` (or `sms()->send(...)`); they never
  talk to a gateway directly.
- **Provider-agnostic** — every gateway implements
  `Src\Domain\Sms\Contracts\SmsProviderInterface`. Production uses **Moolre**; local and
  test environments use a network-free **`log`** driver.
- **Isolated/decoupled** — all SMS code lives under `src/Domain/Sms` (PSR-4 `Src\`).
  Adding a gateway does not touch a single call site.
- **Secrets stay in the environment** — API keys live in `.env` only, never in the
  database, so they are never editable from the UI and never leak into a config cache.
- **Performance first** — balance and sender-ID lookups are cached; the message log is
  indexed on `[status, created_at]`, `context`, `recipient` and `user_id`.

## 2. Sending an SMS

Use the global helper anywhere (controllers, jobs, notifications, services):

```php
// send_sms(recipient, message, context = 'manual', ?userId = null): SmsResult
$result = send_sms('0201234567', 'Your LYVO code is 123456', 'otp', $user->id);

if ($result->success) {
    // queued with the gateway
}
```

- **recipient** — any local or international format; it is normalised to `+233…` via
  `format_phone_for_sms()`.
- **context** — a short tag for reporting/filtering (`otp`, `marketing`, `admin-test`, …).
- **userId** — optional owner, stored on the message row for auditing.

Equivalent service call: `sms()->send($recipient, $message, $context, $userId)`.

Each send persists an `SmsMessage` row (status `Pending` → `Queued`/`Failed`), records the
detected encoding and segment count, and returns an `SmsResult` (`success`, `status`,
`message`, `providerId`, `rawResponse`). Failures never throw out of `send()` — they are
caught, the row is marked `Failed`, and a failure `SmsResult` is returned.

## 3. Architecture

```
src/Domain/Sms/
├── Contracts/SmsProviderInterface.php   # name/send/sendBatch/statuses/balance/senderIds
├── Providers/
│   ├── MoolreSmsProvider.php            # live Guzzle client (X-API-VASKEY)
│   └── LogSmsProvider.php               # network-free dev/test driver
├── DTOs/
│   ├── SmsMessageDto.php                # recipient/message/senderId/ref + encoding()
│   └── SmsResult.php                    # success()/failure() outcome
├── Support/SmsEncoding.php              # GSM-7 vs UCS-2 detection + segment maths
├── SmsService.php                       # application entry point
└── ../helpers.php                       # send_sms(), sms(), format_phone_for_sms()
```

Supporting pieces outside the domain:

| Concern              | Class                                                   |
| -------------------- | ------------------------------------------------------- |
| Container binding    | `App\Providers\SmsServiceProvider` (deferred)           |
| Persisted message    | `App\Models\SmsMessage`                                 |
| Runtime settings     | `App\Models\SmsSetting` (single row, memoised)          |
| Status enum          | `App\Enums\SmsStatus`                                    |
| Admin UI             | `App\Http\Controllers\Admin\SmsController`              |
| Webhook processing   | `App\Jobs\ProcessMoolreSmsWebhookJob`                   |
| Webhook signature    | `App\Support\Webhooks\MoolreSignatureValidator`         |
| Low-credit alert     | `App\Console\Commands\CheckSmsBalance` + notification   |

**Provider resolution.** `SmsServiceProvider` reads the active provider name from
`SmsSetting::current()->provider` (falling back to `config('sms.default')`) and binds the
matching implementation. Admins can therefore switch gateways at runtime from the console.

## 4. Encoding & segments

`Src\Domain\Sms\Support\SmsEncoding` decides how a message is billed:

| Encoding | Single segment | Concatenated segment |
| -------- | -------------- | -------------------- |
| GSM-7    | 160 chars      | 153 chars            |
| UCS-2    | 70 chars       | 67 chars             |

Any character outside the GSM-7 alphabet (emoji, many accents, non-Latin scripts) forces
UCS-2. The detected `encoding` and `segments` are stored on every `SmsMessage` for cost
reporting.

## 5. Admin console

Route group `admin.sms.*` (auth + verified contacts + `account:admin`), view
`resources/views/admin/sms/index.blade.php`:

| Route                  | Method | Purpose                                             |
| ---------------------- | ------ | --------------------------------------------------- |
| `admin.sms.index`      | GET    | Balance, status breakdown, settings, message log    |
| `admin.sms.settings`   | PUT    | Update provider / sender ID / low-credit threshold  |
| `admin.sms.balance`    | POST   | Force a fresh balance lookup                         |
| `admin.sms.test`       | POST   | Send a test message (`context = admin-test`)         |

Permissions (in `App\Support\Permissions`, group **Messaging (SMS)**):

- `sms.view` — view the console.
- `sms.manage` — update settings / refresh balance.
- `sms.send` — send test messages.

Admins are super-admins via `Gate::before`, so they hold all three automatically.

The console also lists **approved sender IDs** returned by the gateway so an admin can
pick a valid one. Balance and sender-ID lists are cached (see config) to keep the page
fast and avoid hammering the provider.

## 6. Delivery webhooks

Moolre posts delivery receipts to **`POST /api/webhooks/moolre/sms`**.

- Registered centrally in `routes/webhook.php` (loaded under the `api/webhooks` prefix
  with the `api` middleware group — stateless, no CSRF).
- Wired through the Spatie webhook-client `moolre` config in `config/webhook-client.php`.
- `MoolreSignatureValidator` compares the shared secret (constant-time) against the
  `X-Moolre-Signature` header. When no secret is configured (local dev) validation is
  skipped so callbacks can be simulated.
- `ProcessMoolreSmsWebhookJob` (extends `Spatie\WebhookClient\Jobs\ProcessWebhookJob`)
  reconciles each `{ref, status}` — single or `{data:[…]}` batch — via
  `SmsService::applyStatus()`, setting `delivered_at` / `failed_at` as appropriate.

**Adding another provider webhook:** add a named config in `config/webhook-client.php`
(validator + `ProcessWebhookJob`) and one `Route::webhooks($path, $name)` line in
`routes/webhook.php`. Nothing else changes.

## 7. Balance monitoring & low-credit alerts

- `SmsSetting` stores a configurable `low_credit_threshold` (seeded from
  `config('sms.low_credit_threshold')`) and caches the last known balance.
- `php artisan sms:check-balance` (add `--force` to bypass the cache) fetches the balance,
  compares it to the threshold, and notifies every admin via `LowSmsCreditNotification`
  when it is below. Alerts are throttled to once per 6 hours (`low_credit_alerted_at`).
- Scheduled **hourly** in `App\Console\Kernel`.

## 8. Configuration

`config/sms.php` (env-driven):

| Key                        | Env                             | Default                    |
| -------------------------- | ------------------------------- | -------------------------- |
| `default`                  | `SMS_PROVIDER`                  | `log`                      |
| `sender_id`                | `MOOLRE_SMS_SENDER_ID`          | `LYVO`                     |
| `country_code`             | `SMS_COUNTRY_CODE`              | `233`                      |
| `low_credit_threshold`     | `SMS_LOW_CREDIT_THRESHOLD`      | `100`                      |
| `balance_cache_minutes`    | `SMS_BALANCE_CACHE_MINUTES`     | `15`                       |
| `sender_ids_cache_minutes` | `SMS_SENDER_IDS_CACHE_MINUTES`  | `60`                       |
| `log_channel`              | `SMS_LOG_CHANNEL`               | logging default            |
| `providers.moolre.base_uri`| `MOOLRE_SMS_BASE_URI`           | `https://api.moolre.com`   |
| `providers.moolre.vas_key` | `MOOLRE_SMS_VASKEY`             | —                          |
| `providers.moolre.timeout` | `MOOLRE_SMS_TIMEOUT`            | `25`                       |
| `webhook.secret`           | `MOOLRE_WEBHOOK_SECRET`         | —                          |
| `webhook.header`           | `MOOLRE_WEBHOOK_HEADER`         | `X-Moolre-Signature`       |

Required production `.env`:

```dotenv
SMS_PROVIDER=moolre
MOOLRE_SMS_VASKEY=your-vas-key
MOOLRE_SMS_SENDER_ID=LYVO
MOOLRE_WEBHOOK_SECRET=shared-secret-with-moolre
```

> The sender ID must be **approved** in the Moolre dashboard (app.moolre.com) before live
> sends succeed — an unapproved ID returns `ASMS07` even though authentication passes.

## 9. Moolre API reference

Base URI `https://api.moolre.com`, auth header `X-API-VASKEY`.

| Action      | Endpoint            | Payload             | Response                                   |
| ----------- | ------------------- | ------------------- | ------------------------------------------ |
| Send        | `/open/sms/send`    | `type:1`, messages  | `status===1`, code `SMS01`                 |
| Status      | `/open/sms/status`  | `type:5`, `ref:[…]` | `data:[{ref,status}]`                       |
| Balance     | `/open/sms/status`  | `type:2`            | `data.balance`                             |
| Sender IDs  | `/open/sms/status`  | `type:7`            | `data:[{id,senderid,approval,whitelisted}]`|

Moolre delivery status codes map to `App\Enums\SmsStatus` via `SmsStatus::fromMoolre()`:
`1 → Sent`, `2 → Delivered`, `3 → Failed`, otherwise `Queued`.

## 10. Testing

- **`phpunit.xml` forces `SMS_PROVIDER=log` and blank `MOOLRE_WEBHOOK_SECRET`** so tests
  never hit the live gateway. There is **no `.env.testing`**, so unset overrides leak from
  `.env` — every new external integration needs the same treatment.
- `MoolreSmsProviderTest` exercises the real provider against a **Guzzle `MockHandler`**,
  asserting the `X-API-VASKEY` header and request shape.
- `SmsEncodingTest` is plain PHPUnit (no Laravel) for fast segment-boundary checks.
- Other suites: `SmsServiceTest`, `OtpSmsDeliveryTest`, `MoolreWebhookTest`,
  `CheckSmsBalanceCommandTest`, `Admin\SmsManagementTest`.

```bash
php artisan test --filter="Sms|Moolre|Webhook"
```

## 11. Adding a new gateway

1. Implement `Src\Domain\Sms\Contracts\SmsProviderInterface` in
   `src/Domain/Sms/Providers/YourProvider.php`.
2. Add a credentials block under `config/sms.php` → `providers.your_provider`.
3. Teach `SmsServiceProvider::makeProvider()` to build it for that name.
4. (If it posts callbacks) add a `config/webhook-client.php` config + a
   `Route::webhooks()` line in `routes/webhook.php` and a `ProcessWebhookJob`.
5. Switch the active provider from the admin console (or `SMS_PROVIDER`).

No existing call site changes — that is the whole point.

## 12. Gotchas / lessons

- **Query builder does not cast enums.** `applyStatus()` must persist `$status->value`,
  not the enum object, or the `UPDATE` fails.
- **`X-API-VASKEY` must be sent per request** (not only on a self-built client's
  defaults) so injected/mocked clients still authenticate.
- **Remove the stock empty `default` webhook-client config** — building the
  `WebhookConfigRepository` validates *every* config, and an empty `process_webhook_job`
  throws for *all* webhook routes.
- **`SmsSetting::current()` is memoised**; `TestCase::setUp()` flushes it between tests.

