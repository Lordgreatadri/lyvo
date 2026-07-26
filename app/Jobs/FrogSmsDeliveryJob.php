<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FrogSmsDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The queue this job runs on.
     * SMS delivery is time-sensitive, so we use the high-priority queue.
     *
     * @var string
     */
    // public $queue = 'high';

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds between retries (exponential: 10s → 30s → 60s).
     *
     * @var array<int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Timeout for a single attempt in seconds.
     * FrogSMS can be slow; give it a generous window.
     */
    public int $timeout = 90;

    /**
     * Create a new job instance.
     *
     * @param string $phoneNumber  E.164-formatted phone number (e.g. +1234567890)
     * @param string $message      The SMS body to deliver
     */
    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $message,
    ) {
        $this->onQueue('high');
    }

    /**
     * Execute the job — deliver the SMS via FrogSMS HTTP API.
     *
     * This method performs the actual outbound HTTP call so that the
     * web/CLI request that triggered the SMS is not blocked.
     */
    public function handle(): void
    {
        $config = config('services.frogsms');
        $phone = ltrim($this->phoneNumber, '+');
        logger()->channel('frogapi')->info('FrogSMS creds', [
            'creds'   => $config,
        ]);

        $response = Http::timeout(70)->get($config['base_url'], [
            'username'   => $config['username'],
            'password'   => $config['password'],
            'from'   => $config['senderid'],
            'to'   => $phone,
            'message'    => $this->message,
            'msgType'    => 'text',
        ]);

        //$phone = ltrim($request->phone, '+');
        
        // $body = $response->body();

        Log::channel('frogapi')->info('FrogSMS delivery', [
            'phone'   => $phone,
            // 'status'  => $response->status(),
            'attempt' => $this->attempts(),
            'body'    => $response,
            'response' => $response,
        ]);

        // FrogSMS returns non-2xx or an error body on failure.
        // Throwing an exception here will trigger the backoff retry.
        if (! $response->successful()) {
            throw new \RuntimeException(
                "FrogSMS HTTP {$response->status()} for {$phone}: {$body}"
            );
        }

        // Some providers return 200 with an error string in the body.
        if (str_contains(strtolower($body), 'error')) {
            throw new \RuntimeException(
                "FrogSMS API error for {$this->phoneNumber}: {$body}"
            );
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $phone = ltrim($this->phoneNumber, '+');
        Log::channel('frogapi')->error('FrogSMS delivery FAILED (all retries exhausted)', [
            'phone'     => $phone,
            'exception' => $exception->getMessage(),
        ]);
    }
}
