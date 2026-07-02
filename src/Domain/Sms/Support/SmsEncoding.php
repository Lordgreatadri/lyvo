<?php

namespace Src\Domain\Sms\Support;

/**
 * SmsEncoding
 * -----------
 * Determines the character encoding a message will be billed under and how many
 * carrier segments it consumes. Getting this right matters for cost control:
 * a single emoji forces the whole message onto UCS-2 and can multiply the
 * billable segment count several times over.
 *
 *   GSM-7  : 160 chars single, 153 per part when concatenated.
 *   UCS-2  :  70 chars single,  67 per part when concatenated.
 *
 * Everything here is pure/static — no state, no I/O — so it is trivial to unit
 * test and cheap to call on the hot path before every send.
 */
final class SmsEncoding
{
    public const GSM7 = 'gsm-7';
    public const UCS2 = 'ucs-2';

    /** Basic GSM 03.38 characters (each counts as one septet). */
    private const GSM_BASIC =
        "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?"
        . "¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    /** GSM 03.38 characters that occupy two septets (the escape table). */
    private const GSM_EXTENDED = "^{}\\[~]|€";

    /**
     * Detect which encoding the message will be billed under.
     *
     * @return string  self::GSM7 or self::UCS2
     */
    public static function detect(string $message): string
    {
        $basic = self::gsmCharSet();

        foreach (self::characters($message) as $char) {
            if (! isset($basic[$char])) {
                return self::UCS2;
            }
        }

        return self::GSM7;
    }

    /**
     * Count how many GSM septets the message occupies (extended chars count 2).
     * Only meaningful when the message is GSM-7.
     */
    public static function gsmLength(string $message): int
    {
        $extended = self::splitToArray(self::GSM_EXTENDED);
        $length = 0;

        foreach (self::characters($message) as $char) {
            $length += isset($extended[$char]) ? 2 : 1;
        }

        return $length;
    }

    /**
     * Number of billable segments the message consumes under its encoding.
     */
    public static function segments(string $message): int
    {
        if ($message === '') {
            return 0;
        }

        if (self::detect($message) === self::UCS2) {
            $length = count(self::characters($message));

            return $length <= 70 ? 1 : (int) ceil($length / 67);
        }

        $length = self::gsmLength($message);

        return $length <= 160 ? 1 : (int) ceil($length / 153);
    }

    /**
     * Convenience meta bundle persisted alongside every message row.
     *
     * @return array{encoding:string, segments:int, length:int}
     */
    public static function analyse(string $message): array
    {
        return [
            'encoding' => self::detect($message),
            'segments' => self::segments($message),
            'length' => count(self::characters($message)),
        ];
    }

    /** GSM basic + extended set as a lookup map (char => true) built once. */
    private static function gsmCharSet(): array
    {
        static $set = null;

        if ($set === null) {
            $set = self::splitToArray(self::GSM_BASIC) + self::splitToArray(self::GSM_EXTENDED);
        }

        return $set;
    }

    /** @return array<string, true> */
    private static function splitToArray(string $value): array
    {
        $map = [];

        foreach (self::characters($value) as $char) {
            $map[$char] = true;
        }

        return $map;
    }

    /**
     * Split a UTF-8 string into an array of individual characters.
     *
     * @return array<int, string>
     */
    private static function characters(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
