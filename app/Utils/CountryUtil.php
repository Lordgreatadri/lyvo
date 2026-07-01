<?php

namespace App\Utils;

use App\Exceptions\CountryNotSupportedException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class CountryUtil
{
    /**
     * Convert country calling code to Iso2 code
     * Example: '+233' => 'gh'
     *
     * @throws CountryNotSupportedException|Throwable
     */
    public static function codeToIso2(string $code): string
    {
        $isoCode = Arr::get(self::CODE_TO_ISO2_MAP, self::normalizeCode($code));

        throw_if(is_null($isoCode), CountryNotSupportedException::class);

        return $isoCode;
    }

    /**
     * Convert iso2 code to calling code
     * Example: 'gh' => '+233'
     */
    public static function Iso2ToCode(string $isoCode): string
    {
        $isoCode = Arr::get(array_flip(self::CODE_TO_ISO2_MAP), trim($isoCode));

        throw_if(is_null($isoCode), CountryNotSupportedException::class);

        return $isoCode;
    }

    /**
     * Remove country code prefix is provided
     * E.g: 00233 => 233, +233 => 233
     */
    public static function normalizeCode(string $code): string
    {
        return Str::of($code)->trim()->ltrim('0')->after('+');
    }

    private const CODE_TO_ISO2_MAP = [
        '233' => 'gh',
        '254' => 'ke',
        '234' => 'ng',
    ];
}
