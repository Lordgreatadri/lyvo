<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * LYVO verifies email + phone with OTP codes (see App\Services\OtpService),
     * so the default Breeze email-link listener is intentionally NOT registered.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \App\Events\PaymentSettled::class => [
            \App\Listeners\AdvanceEscrowOnPayment::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
