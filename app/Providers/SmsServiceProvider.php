<?php

namespace App\Providers;

use App\Models\SmsSetting;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Src\Domain\Sms\Contracts\SmsProviderInterface;
use Src\Domain\Sms\Providers\LogSmsProvider;
use Src\Domain\Sms\Providers\MoolreSmsProvider;
use Src\Domain\Sms\SmsService;

/**
 * SmsServiceProvider
 * ------------------
 * Wires the active SMS gateway into the container. The concrete provider is
 * chosen from the admin-editable SmsSetting row (falling back to config), so the
 * whole application talks to one SmsService without knowing which gateway backs
 * it. Deferred so it never runs on requests that send no SMS.
 */
class SmsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(SmsProviderInterface::class, function (): SmsProviderInterface {
            return $this->makeProvider($this->activeProviderName());
        });

        $this->app->bind(SmsService::class, function ($app): SmsService {
            return new SmsService(
                $app->make(SmsProviderInterface::class),
                SmsSetting::current(),
            );
        });
    }

    /** Resolve the configured provider name (settings row overrides config). */
    private function activeProviderName(): string
    {
        try {
            return SmsSetting::current()->provider ?: (string) config('sms.default', 'log');
        } catch (\Throwable) {
            // Settings table may not exist yet (e.g. before migrations run).
            return (string) config('sms.default', 'log');
        }
    }

    private function makeProvider(string $name): SmsProviderInterface
    {
        return match ($name) {
            'moolre' => new MoolreSmsProvider(config('sms.providers.moolre', [])),
            default => new LogSmsProvider(),
        };
    }

    /** @return array<int, string> */
    public function provides(): array
    {
        return [SmsProviderInterface::class, SmsService::class];
    }
}
