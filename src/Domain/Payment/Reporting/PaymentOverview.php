<?php

namespace Src\Domain\Payment\Reporting;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PaymentOverview
 * ---------------
 * Read model that powers the admin dashboard's "Payments (Moolre)" panel. It is
 * deliberately performance-first:
 *
 *   • the headline figures come from ONE aggregate query over
 *     `payment_transactions` using conditional SUM/COUNT (no N+1, no per-status
 *     round-trips), backed by the `[status, created_at]` index;
 *   • the channel split and recent list are small, indexed reads;
 *   • the whole payload is cached for a short TTL so repeated dashboard loads do
 *     not re-run the aggregates.
 *
 * It never mutates state and is safe to call from a controller.
 */
class PaymentOverview
{
    /**
     * The full overview payload for the admin dashboard.
     *
     * @return array{
     *   metrics: array<int, array{label:string, value:string, delta:string, icon:string}>,
     *   totals: array<string, mixed>,
     *   channels: Collection<int, array{label:string, count:int, volume:string}>,
     *   recent: Collection<int, PaymentTransaction>
     * }
     */
    public function forAdmin(): array
    {
        $ttl = (int) config('payment.overview_cache_seconds', 60);

        return cache()->remember('payment.overview.admin', $ttl, function (): array {
            $totals = $this->totals();

            return [
                'metrics' => $this->metrics($totals),
                'totals' => $totals,
                'channels' => $this->channels(),
                'recent' => $this->recent(),
            ];
        });
    }

    /**
     * Single-pass aggregate of the whole ledger plus the current calendar month.
     *
     * @return array<string, mixed>
     */
    private function totals(): array
    {
        $monthStart = Carbon::now()->startOfMonth();

        $success = PaymentStatus::Successful->value;
        $failed = PaymentStatus::Failed->value;

        $row = PaymentTransaction::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(status = ?) as successful', [$success])
            ->selectRaw('SUM(status = ?) as failed', [$failed])
            ->selectRaw('SUM(status NOT IN (?, ?)) as open', [$success, $failed])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as collected', [$success])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? AND created_at >= ? THEN amount ELSE 0 END), 0) as collected_month', [$success, $monthStart])
            ->selectRaw('SUM(created_at >= ?) as count_month', [$monthStart])
            ->first();

        $total = (int) ($row->total ?? 0);
        $successful = (int) ($row->successful ?? 0);
        $failed = (int) ($row->failed ?? 0);
        $settled = $successful + $failed;

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'open' => (int) ($row->open ?? 0),
            'collected' => (float) ($row->collected ?? 0),
            'collected_month' => (float) ($row->collected_month ?? 0),
            'count_month' => (int) ($row->count_month ?? 0),
            // Success rate is measured against settled (terminal) transactions only.
            'success_rate' => $settled > 0 ? round($successful / $settled * 100) : 0,
        ];
    }

    /**
     * Four KPI cards mirroring the dashboard's stat-card shape.
     *
     * @param  array<string, mixed>  $totals
     * @return array<int, array{label:string, value:string, delta:string, icon:string}>
     */
    private function metrics(array $totals): array
    {
        return [
            [
                'label' => 'Collected (all time)',
                'value' => $this->money($totals['collected']),
                'delta' => $this->money($totals['collected_month']) . ' this month',
                'icon' => 'wallet',
            ],
            [
                'label' => 'Successful payments',
                'value' => number_format($totals['successful']),
                'delta' => $totals['count_month'] . ' txns this month',
                'icon' => 'check-circle',
            ],
            [
                'label' => 'Success rate',
                'value' => $totals['success_rate'] . '%',
                'delta' => number_format($totals['failed']) . ' failed',
                'icon' => 'trending',
            ],
            [
                'label' => 'In progress',
                'value' => number_format($totals['open']),
                'delta' => 'Awaiting settlement',
                'icon' => 'chart',
            ],
        ];
    }

    /**
     * Volume collected per mobile-money channel (successful only).
     *
     * @return Collection<int, array{label:string, count:int, volume:string}>
     */
    private function channels(): Collection
    {
        return PaymentTransaction::query()
            ->where('status', PaymentStatus::Successful->value)
            ->groupBy('channel')
            ->select('channel', DB::raw('COUNT(*) as txns'), DB::raw('COALESCE(SUM(amount), 0) as volume'))
            ->get()
            ->map(function ($row): array {
                $channel = $row->channel instanceof PaymentChannel
                    ? $row->channel
                    : PaymentChannel::tryFrom((string) $row->channel);

                return [
                    'label' => $channel?->label() ?? 'Unknown',
                    'count' => (int) $row->txns,
                    'volume' => $this->money((float) $row->volume),
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    /**
     * The most recent transactions for the activity list.
     *
     * @return Collection<int, PaymentTransaction>
     */
    private function recent(): Collection
    {
        return PaymentTransaction::query()
            ->latest('id')
            ->limit(6)
            ->get(['id', 'ref', 'channel', 'amount', 'currency', 'payer', 'status', 'context', 'created_at']);
    }

    private function money(float $amount): string
    {
        $currency = (string) config('payment.currency', 'GHS');
        $symbol = $currency === 'GHS' ? 'GH₵' : $currency;

        if ($amount >= 1000) {
            return $symbol . ' ' . rtrim(rtrim(number_format($amount / 1000, 1), '0'), '.') . 'k';
        }

        return $symbol . ' ' . number_format($amount, 2);
    }
}
