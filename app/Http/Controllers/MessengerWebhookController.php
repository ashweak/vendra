<?php

namespace App\Http\Controllers;

use App\Services\Messenger\MessengerClient;
use App\Services\Messenger\MessengerInbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Facebook Messenger webhook: Meta's verification handshake, then signed message events. */
class MessengerWebhookController extends Controller
{
    public function verify(Request $request)
    {
        abort_unless(MessengerClient::enabled(), 404);
        $token = (string) $request->query('hub_verify_token', '');
        if ($request->query('hub_mode') === 'subscribe' && hash_equals((string) config('services.messenger.verify_token'), $token)) {
            return response((string) $request->query('hub_challenge', ''), 200, ['Content-Type' => 'text/plain']);
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request, MessengerInbox $inbox)
    {
        abort_unless(MessengerClient::enabled(), 404);
        $raw = $request->getContent();
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256='.hash_hmac('sha256', $raw, (string) config('services.messenger.app_secret'));
        if ($raw === '' || ! hash_equals($expected, $signature)) {
            return response('Invalid signature', 403); // only Facebook, signed with our app secret
        }
        try {
            $inbox->handle((array) json_decode($raw, true));
        } catch (\Throwable $e) {
            report($e); // still answer 200, or Facebook retries and eventually disables the webhook
            Log::warning('Messenger webhook handling failed');
        }

        return response('EVENT_RECEIVED', 200);
    }
}
