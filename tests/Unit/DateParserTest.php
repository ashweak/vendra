<?php

namespace Tests\Unit;

use App\Services\Ai\DateParser;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DateParserTest extends TestCase
{
    public function test_relative_dates_in_english_and_tagalog(): void
    {
        // now = Monday 2026-11-16 (TestCase)
        $tz = 'Asia/Manila';
        $this->assertSame('2026-11-17', DateParser::date('pwede bukas 3pm?', $tz));
        $this->assertSame('2026-11-17', DateParser::date('tomorrow please', $tz));
        $this->assertSame('2026-11-16', DateParser::date('ngayon po', $tz));
        $this->assertSame('2026-11-21', DateParser::date('sa sabado', $tz));
        $this->assertSame('2026-11-20', DateParser::date('this friday', $tz));
        $this->assertSame('2026-11-23', DateParser::date('next monday', $tz));
        $this->assertSame('2026-12-05', DateParser::date('Dec 5', $tz));
        $this->assertSame('2026-11-18', DateParser::date('sa makalawa', $tz));
    }

    public function test_bukas_as_open_is_not_tomorrow(): void
    {
        $this->assertFalse(DateParser::bukasMeansTomorrow('bukas ba kayo?'));
        $this->assertTrue(DateParser::bukasMeansTomorrow('bukas ng 2pm'));
    }

    public function test_times(): void
    {
        $this->assertSame('15:00', DateParser::time('3pm'));
        $this->assertSame('09:30', DateParser::time('9:30 am'));
        $this->assertSame('15:00', DateParser::time('alas tres'));
        $this->assertSame('10:00', DateParser::time('alas diyes ng umaga'));
        $this->assertSame('14:30', DateParser::time('alas dos y medya'));
        $this->assertNull(DateParser::time('haircut please'));
    }
}
