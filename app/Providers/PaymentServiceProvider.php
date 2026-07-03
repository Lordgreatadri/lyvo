<?php

namespace App\Providers;

use App\Models\PaymentSetting;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Src\Domain\Payment\Contracts\PaymentProviderInterface;
use Src\Domain\Payment\PaymentService;
use Src\Domain\Payment\Providers\LogPaymentProvider;
use Src\Domain\Payment\Providers\MoolrePaymentProvider;

/**
 * PaymentServiceProvider
 * ----------------------
 * Wires the active payment gateway into the container. The concrete provider is
 * chosen from the admin-editable PaymentSetting row (falling back to config), so
 * the whole application collects money through one PaymentService without
 * knowing which gateway backs it. Deferred so it never runs on requests that
 * take no payment.
 */
class PaymentServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentProviderInterface::class, function (): PaymentProviderInterface {
            return $this->makeProvider($this->activeProviderName());
        });

        $this->app->bind(PaymentService::class, function ($app): PaymentService {
            return new PaymentService(
                $app->make(PaymentProviderInterface::class),
                PaymentSetting::current(),
            );
        });
    }

    /** Resolve the configured provider name (settings row overrides config). */
    private function activeProviderName(): string
    {
        try {
            return PaymentSetting::current()->provider ?: (string) config('payment.default', 'log');
        } catch (\Throwable) {
            // Settings table may not exist yet (e.g. before migrations run).
            return (string) config('payment.default', 'log');
        }
    }

    private function makeProvider(string $name): PaymentProviderInterface
    {
        return match ($name) {
            'moolre' => new MoolrePaymentProvider(config('payment.providers.moolre', [])),
            default => new LogPaymentProvider(),
        };
    }

    /** @return array<int, string> */
    public function provides(): array
    {
        return [PaymentProviderInterface::class, PaymentService::class];
    }
}
