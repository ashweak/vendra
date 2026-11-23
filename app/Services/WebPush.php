<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Web Push without any paid service or extra library (RFC 8030 + VAPID, RFC 8292).
 * Pushes carry no data, so nothing needs encrypting: the dashboard's service worker
 * wakes up, asks /app/push/latest for the newest alert (with the user's own session),
 * and shows it. Customer details never pass through the browser vendor's push servers.
 */
class WebPush
{
    /** Push services run by browser vendors. Anything else is refused, so the server can't be pointed at other URLs. */
    private const HOSTS = ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com'];

    private const HOST_SUFFIXES = ['.push.services.mozilla.com', '.notify.windows.com', '.push.apple.com'];

    public static function enabled(): bool
    {
        return self::publicKey() !== null && filled(config('services.webpush.private_key'));
    }

    /** Uncompressed P-256 public key, base64url (what the browser's applicationServerKey expects). */
    public static function publicKey(): ?string
    {
        $key = config('services.webpush.public_key');

        return is_string($key) && strlen(self::b64urlDecode($key)) === 65 ? $key : null;
    }

    public static function endpointAllowed(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['port'])) {
            return false;
        }
        $host = strtolower($parts['host']);
        if (in_array($host, self::HOSTS, true)) {
            return true;
        }
        foreach (self::HOST_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /** Wakes every device the user turned alerts on for. Returns how many accepted. Never throws. */
    public function notifyUser(User $user): int
    {
        if (! self::enabled()) {
            return 0;
        }
        $ok = 0;
        foreach (PushSubscription::where('user_id', $user->id)->get() as $sub) {
            if ($this->send($sub)) {
                $ok++;
            }
        }

        return $ok;
    }

    public function send(PushSubscription $sub): bool
    {
        if (! self::endpointAllowed($sub->endpoint)) {
            $sub->delete();

            return false;
        }
        try {
            $response = Http::timeout(5)->withHeaders([
                'TTL' => '86400',
                'Urgency' => 'high',
                'Authorization' => 'vapid t='.$this->jwt($sub->endpoint).', k='.self::publicKey(),
            ])->withBody('', 'application/octet-stream')->post($sub->endpoint);
        } catch (\Throwable $e) {
            Log::warning('Web push failed: '.$e->getMessage());

            return false;
        }
        if (in_array($response->status(), [404, 410], true)) {
            $sub->delete(); // the browser unsubscribed or the subscription expired

            return false;
        }
        if ($response->successful()) {
            $sub->forceFill(['last_used_at' => now()])->save();

            return true;
        }
        Log::warning('Web push rejected', ['status' => $response->status()]);

        return false;
    }

    /** VAPID token: an ES256-signed JWT for the push service's origin. */
    public function jwt(string $endpoint): string
    {
        $parts = parse_url($endpoint);
        $header = self::b64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64url(json_encode([
            'aud' => $parts['scheme'].'://'.$parts['host'],
            'exp' => now()->addHours(12)->timestamp,
            'sub' => config('services.webpush.subject') ?: 'mailto:'.(config('vendra.contact_email') ?: 'no-reply@vendra.local'),
        ]));
        $input = $header.'.'.$claims;
        if (! openssl_sign($input, $der, self::privateKeyPem(), OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Could not sign the push token. Check VAPID_PRIVATE_KEY.');
        }

        return $input.'.'.self::b64url(self::derToRaw($der));
    }

    /** Builds a PEM key from the raw 32-byte private value and the 65-byte public key (SEC 1 / RFC 5915). */
    public static function privateKeyPem(): string
    {
        $d = self::b64urlDecode((string) config('services.webpush.private_key'));
        $pub = self::b64urlDecode((string) self::publicKey());
        if (strlen($d) !== 32 || strlen($pub) !== 65) {
            throw new \RuntimeException('VAPID keys are missing or invalid. Run: php artisan vendra:vapid');
        }
        $der = "\x30\x77\x02\x01\x01\x04\x20".$d."\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\xa1\x44\x03\x42\x00".$pub;

        return "-----BEGIN EC PRIVATE KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END EC PRIVATE KEY-----\n";
    }

    /** @return array{public: string, private: string} new base64url VAPID keys */
    public static function generateKeys(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $ec = openssl_pkey_get_details($key)['ec'];
        $pad = fn (string $v) => str_pad($v, 32, "\0", STR_PAD_LEFT);

        return ['public' => self::b64url("\x04".$pad($ec['x']).$pad($ec['y'])), 'private' => self::b64url($pad($ec['d']))];
    }

    /** OpenSSL gives an ASN.1 DER signature; JWT wants the two 32-byte numbers side by side. */
    public static function derToRaw(string $der): string
    {
        $offset = 3 + (ord($der[1]) & 0x80 ? ord($der[1]) & 0x7f : 0);
        $out = '';
        foreach ([0, 1] as $_) {
            $len = ord($der[$offset]);
            $int = substr($der, $offset + 1, $len);
            $out .= str_pad(ltrim($int, "\0"), 32, "\0", STR_PAD_LEFT);
            $offset += 2 + $len;
        }

        return $out;
    }

    public static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function b64urlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
