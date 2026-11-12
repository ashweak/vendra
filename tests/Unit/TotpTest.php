<?php

namespace Tests\Unit;

use App\Support\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    public function test_matches_rfc6238_sha1_vector(): void
    {
        // RFC 6238 Appendix B, T = 59 → 94287082 (last six digits)
        $secret = Totp::base32Encode('12345678901234567890');
        $this->assertSame('287082', Totp::code($secret, 59));
        $this->assertSame('081804', Totp::code($secret, 1111111109));
    }

    public function test_verify_accepts_adjacent_window_only(): void
    {
        $secret = Totp::generateSecret();
        $now = 1_800_000_000;
        $this->assertTrue(Totp::verify($secret, Totp::code($secret, $now - 30), 1, $now));
        $this->assertFalse(Totp::verify($secret, Totp::code($secret, $now - 120), 1, $now));
        $this->assertFalse(Totp::verify($secret, 'abc', 1, $now));
    }
}
