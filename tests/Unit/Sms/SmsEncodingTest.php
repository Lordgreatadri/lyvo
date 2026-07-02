<?php

namespace Tests\Unit\Sms;

use PHPUnit\Framework\TestCase;
use Src\Domain\Sms\Support\SmsEncoding;

/**
 * Encoding + segment maths drive SMS billing, so they are pinned down precisely.
 */
class SmsEncodingTest extends TestCase
{
    public function test_plain_text_is_detected_as_gsm7(): void
    {
        $this->assertSame(SmsEncoding::GSM7, SmsEncoding::detect('Hello, your code is 123456.'));
    }

    public function test_emoji_forces_ucs2(): void
    {
        $this->assertSame(SmsEncoding::UCS2, SmsEncoding::detect('Balance low 💰'));
    }

    public function test_non_latin_script_forces_ucs2(): void
    {
        $this->assertSame(SmsEncoding::UCS2, SmsEncoding::detect('你好'));
    }

    public function test_gsm7_segment_boundaries(): void
    {
        $this->assertSame(1, SmsEncoding::segments(str_repeat('a', 160)));
        $this->assertSame(2, SmsEncoding::segments(str_repeat('a', 161)));
        $this->assertSame(2, SmsEncoding::segments(str_repeat('a', 306)));
        $this->assertSame(3, SmsEncoding::segments(str_repeat('a', 307)));
    }

    public function test_gsm7_extended_characters_cost_two_septets(): void
    {
        // 80 '€' signs = 160 septets = still a single segment.
        $this->assertSame(1, SmsEncoding::segments(str_repeat('€', 80)));
        // 81 '€' signs = 162 septets = two segments.
        $this->assertSame(2, SmsEncoding::segments(str_repeat('€', 81)));
    }

    public function test_ucs2_segment_boundaries(): void
    {
        $this->assertSame(1, SmsEncoding::segments(str_repeat('中', 70)));
        $this->assertSame(2, SmsEncoding::segments(str_repeat('中', 71)));
    }

    public function test_empty_message_has_no_segments(): void
    {
        $this->assertSame(0, SmsEncoding::segments(''));
    }

    public function test_analyse_returns_full_meta(): void
    {
        $meta = SmsEncoding::analyse('Hello 💰');

        $this->assertSame(SmsEncoding::UCS2, $meta['encoding']);
        $this->assertSame(1, $meta['segments']);
        $this->assertSame(7, $meta['length']);
    }
}
