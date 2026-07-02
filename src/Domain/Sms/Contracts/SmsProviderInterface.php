<?php

namespace Src\Domain\Sms\Contracts;

use Src\Domain\Sms\DTOs\SmsMessageDto;
use Src\Domain\Sms\DTOs\SmsResult;

/**
 * SmsProviderInterface
 * --------------------
 * Every SMS gateway integration lives behind this contract so the rest of the
 * application depends only on the abstraction, never on a concrete gateway.
 * Adding a new provider means writing one class — no call site changes.
 */
interface SmsProviderInterface
{
    /** Unique provider slug, e.g. "moolre" | "log". */
    public function name(): string;

    /** Send a single SMS message. */
    public function send(SmsMessageDto $message): SmsResult;

    /**
     * Send several messages in one request where the gateway supports it.
     * Returns a result keyed by each message's ref.
     *
     * @param  array<int, SmsMessageDto>  $messages
     * @return array<string, SmsResult>
     */
    public function sendBatch(array $messages): array;

    /**
     * Look up delivery status for the given references.
     *
     * @param  array<int, string>  $refs
     * @return array<string, \App\Enums\SmsStatus>  ref => canonical status
     */
    public function statuses(array $refs): array;

    /**
     * Retrieve the account credit balance.
     *
     * @return array{balance: float, raw: array}
     */
    public function balance(): array;

    /**
     * List the registered sender IDs and their approval state.
     *
     * @return array<int, array{id:int|null, senderid:string, approval:string, whitelisted:bool}>
     */
    public function senderIds(): array;
}
