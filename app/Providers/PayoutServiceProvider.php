<?php

namespace App\Providers;

use App\Models\PaymentSetting;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Src\Domain\Payout\Contracts\PayoutProviderInterface;
use Src\Domain\Payout\PayoutService;
use Src\Domain\Payout\Providers\LogPayoutProvider;
use Src\Domain\Payout\Providers\MoolrePayoutProvider;

/**
 * PayoutServiceProvider
 * ---------------------
 * Wires the active disbursement gateway into the container. The concrete gateway
 * follows the same admin-editable PaymentSetting used for collections (falling
 * back to config), so payouts and collections always run on the same provider.
 * Deferred so it never runs on requests that make no payout.
 */
class PayoutServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(PayoutProviderInterface::class, function (): PayoutProviderInterface {
            return $this->makeProvider($this->activeProviderName());
        });

        $this->app->bind(PayoutService::class, function ($app): PayoutService {
            return new PayoutService($app->make(PayoutProviderInterface::class));
        });
    }

    /** Resolve the configured provider name (settings row overrides config). */
    private function activeProviderName(): string
    {
        try {
            return PaymentSetting::current()->provider ?: (string) config('payment.default', 'log');
        } catch (\Throwable) {
            return (string) config('payment.default', 'log');
        }
    }

    private function makeProvider(string $name): PayoutProviderInterface
    {
        return match ($name) {
            'moolre' => new MoolrePayoutProvider(config('payment.providers.moolre', [])),
            default => new LogPayoutProvider(),
        };
    }

    /** @return array<int, string> */
    public function provides(): array
    {
        return [PayoutProviderInterface::class, PayoutService::class];
    }
}
