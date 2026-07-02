<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SmsStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSmsSettingsRequest;
use App\Models\SmsMessage;
use App\Models\SmsSetting;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Src\Domain\Sms\SmsService;

/**
 * Admin\SmsController
 * -------------------
 * Operational console for the SMS gateway: live credit balance, the low-credit
 * alert threshold, registered sender IDs and a searchable message log. Every
 * query here is written to be cheap — the balance and sender-ID lists are cached
 * by SmsService, the message log is paginated with a narrow column selection,
 * and the status breakdown is a single grouped aggregate.
 */
class SmsController extends Controller
{
    public function index(Request $request, SmsService $sms): View
    {
        $this->authorize(Permissions::SMS_VIEW);

        $settings = SmsSetting::current();

        // Cached — hits the gateway at most once per cache window.
        $balance = $sms->balance();
        $senderIds = $sms->senderIds();

        // Single grouped aggregate instead of one count query per status.
        $counts = SmsMessage::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusFilter = $request->query('status');

        $messages = SmsMessage::query()
            ->select(['id', 'ref', 'recipient', 'context', 'status', 'segments', 'provider', 'created_at'])
            ->when(
                in_array($statusFilter, array_column(SmsStatus::cases(), 'value'), true),
                fn ($q) => $q->where('status', $statusFilter),
            )
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.sms.index', [
            'settings' => $settings,
            'balance' => $balance,
            'senderIds' => $senderIds,
            'counts' => $counts,
            'messages' => $messages,
            'statusFilter' => $statusFilter,
            'providers' => $this->providerOptions(),
            'statuses' => SmsStatus::cases(),
            'belowThreshold' => $settings->isBelowThreshold(),
        ]);
    }

    public function updateSettings(UpdateSmsSettingsRequest $request): RedirectResponse
    {
        $settings = SmsSetting::current();

        $data = $request->validated();

        // Switching providers invalidates any balance cached from the previous
        // gateway. Clear it so the console never shows the old provider's balance
        // (and belowThreshold cannot misfire) until the next live lookup.
        if (array_key_exists('provider', $data) && $data['provider'] !== $settings->provider) {
            $data['cached_balance'] = null;
            $data['cached_balance_snapshot'] = null;
            $data['balance_checked_at'] = null;
            $data['low_credit_alerted_at'] = null;
        }

        $settings->update($data);
        SmsSetting::flushCache();

        return back()->with('status', 'SMS settings updated.');
    }

    public function refreshBalance(SmsService $sms): RedirectResponse
    {
        // Refreshing hits the provider and rewrites the cached balance, so it is
        // a management action rather than a read-only view.
        $this->authorize(Permissions::SMS_MANAGE);

        $balance = $sms->balance(force: true);

        return back()->with('status', 'Balance refreshed: '.number_format($balance['balance']).' credits.');
    }

    public function sendTest(Request $request, SmsService $sms): RedirectResponse
    {
        $this->authorize(Permissions::SMS_SEND);

        $data = $request->validate([
            'recipient' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:640'],
        ]);

        $result = $sms->send($data['recipient'], $data['message'], 'admin-test', $request->user()->id);

        return back()->with('status', $result->success
            ? 'Test message accepted by the gateway.'
            : 'Test message failed: '.$result->message);
    }

    /**
     * @return array<string, string>
     */
    private function providerOptions(): array
    {
        return [
            'log' => 'Log driver (development — no real SMS)',
            'moolre' => 'Moolre SMS',
        ];
    }
}
