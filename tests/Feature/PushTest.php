<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Services\Notifier;
use App\Services\WebPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PushTest extends TestCase
{
    use RefreshDatabase;

    private array $keys;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keys = WebPush::generateKeys();
        config(['services.webpush.public_key' => $this->keys['public'], 'services.webpush.private_key' => $this->keys['private'], 'services.webpush.subject' => 'mailto:owner@example.test']);
    }

    public function test_dashboard_is_installable(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->get(route('dashboard.overview'))->assertOk()
            ->assertSee('rel="manifest"', false)->assertSee("register('/sw.js', { scope: '/app' })", false);
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertSame('/app', $manifest['start_url']);
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->actingAs($owner)->get(route('dashboard.account'))->assertSee('Turn on alerts on this device')->assertSee($this->keys['public'], false);
    }

    public function test_subscribing_only_accepts_browser_push_services(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->postJson(route('dashboard.push.subscribe'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123'])->assertOk();
        foreach (['https://evil.example/x', 'http://fcm.googleapis.com/fcm/send/x', 'https://fcm.googleapis.com.evil.example/x', 'https://169.254.169.254/latest'] as $bad) {
            $this->actingAs($owner)->postJson(route('dashboard.push.subscribe'), ['endpoint' => $bad])->assertStatus(422);
        }
        $this->assertSame(1, PushSubscription::count());
        $this->actingAs($owner)->postJson(route('dashboard.push.subscribe'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123'])->assertOk();
        $this->assertSame(1, PushSubscription::count(), 'same device is not stored twice');

        $this->actingAs($owner)->postJson(route('dashboard.push.unsubscribe'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123'])->assertOk();
        $this->assertSame(0, PushSubscription::count());
        $this->app['auth']->forgetGuards();
        $this->postJson(route('dashboard.push.subscribe'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/z'])->assertUnauthorized();
    }

    public function test_new_activity_wakes_devices_with_a_signed_empty_push(): void
    {
        Http::fake(['fcm.googleapis.com/*' => Http::response('', 201), 'updates.push.services.mozilla.com/*' => Http::response('', 410)]);
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->postJson(route('dashboard.push.subscribe'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/dev1'])->assertOk();
        $this->actingAs($owner)->postJson(route('dashboard.push.subscribe'), ['endpoint' => 'https://updates.push.services.mozilla.com/wpush/v2/old'])->assertOk();

        Notifier::staff($b, 'New booking APT-123456', 'Hair Color for Ana – Confirmed', route('dashboard.overview'));

        Http::assertSent(function ($request) {
            if (! str_starts_with($request->url(), 'https://fcm.googleapis.com/')) {
                return false;
            }
            $this->assertSame('', $request->body(), 'no customer data goes through the push service');
            $this->assertSame('86400', $request->header('TTL')[0]);
            preg_match('/^vapid t=([^,]+), k=(.+)$/', $request->header('Authorization')[0], $m);
            $this->assertSame($this->keys['public'], $m[2]);
            [$h, $c, $sig] = explode('.', $m[1]);
            $claims = json_decode(WebPush::b64urlDecode($c), true);
            $this->assertSame('https://fcm.googleapis.com', $claims['aud']);
            $this->assertSame('mailto:owner@example.test', $claims['sub']);
            $this->assertGreaterThan(now()->timestamp, $claims['exp']);

            return $this->verifyEs256("$h.$c", WebPush::b64urlDecode($sig));
        });
        $this->assertSame(1, PushSubscription::count(), 'a subscription the browser removed (410) is deleted');

        // The service worker then asks what to show, with the user's own session.
        $this->actingAs($owner)->getJson(route('dashboard.push.latest'))->assertOk()
            ->assertJson(['title' => 'New booking APT-123456', 'body' => 'Hair Color for Ana – Confirmed', 'url' => route('dashboard.overview')]);
        $this->app['auth']->forgetGuards();
        $this->getJson(route('dashboard.push.latest'))->assertUnauthorized();
    }

    public function test_push_is_off_without_keys(): void
    {
        config(['services.webpush.public_key' => null]);
        Http::fake();
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->postJson(route('dashboard.push.subscribe'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/x'])->assertNotFound();
        $this->actingAs($owner)->get(route('dashboard.account'))->assertSee('Device alerts aren’t set up on this server yet', false);
        Notifier::staff($b, 'Hi', 'There');
        Http::assertNothingSent();
    }

    private function verifyEs256(string $input, string $raw): bool
    {
        $int = function (string $x) { $x = ltrim($x, "\0"); if (ord($x[0]) & 0x80) { $x = "\0".$x; } return "\x02".chr(strlen($x)).$x; };
        $seq = $int(substr($raw, 0, 32)).$int(substr($raw, 32));
        $der = "\x30".chr(strlen($seq)).$seq;
        $spki = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').WebPush::b64urlDecode($this->keys['public']);
        $pem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($spki), 64, "\n")."-----END PUBLIC KEY-----\n";

        return openssl_verify($input, $der, $pem, OPENSSL_ALGO_SHA256) === 1;
    }
}
