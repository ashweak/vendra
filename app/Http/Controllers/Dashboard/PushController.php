<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\WebPush;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Turning dashboard alerts on/off for this device, and the alert text the service worker shows. */
class PushController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        abort_unless(WebPush::enabled(), 404);
        $data = $request->validate(['endpoint' => 'required|string|max:500|url:https']);
        if (! WebPush::endpointAllowed($data['endpoint'])) {
            return response()->json(['message' => 'This browser’s push service isn’t supported.'], 422);
        }
        $user = $request->user();
        abort_if(PushSubscription::where('user_id', $user->id)->count() >= 10
            && ! PushSubscription::where('endpoint_hash', hash('sha256', $data['endpoint']))->exists(), 422, 'Too many devices. Turn alerts off on one first.');

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            ['user_id' => $user->id, 'endpoint' => $data['endpoint'], 'user_agent' => Str::limit((string) $request->userAgent(), 150, '')],
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => 'required|string|max:500']);
        PushSubscription::where('user_id', $request->user()->id)->where('endpoint_hash', hash('sha256', $data['endpoint']))->delete();

        return response()->json(['ok' => true]);
    }

    /** Newest unread alert for the signed-in user (only titles and links already shown in the dashboard bell). */
    public function latest(Request $request): JsonResponse
    {
        $n = $request->user()->unreadNotifications()->latest()->first();
        $data = $n?->data ?? [];
        $url = is_string($data['url'] ?? null) && str_starts_with($data['url'], url('/')) ? $data['url'] : route('dashboard.overview');

        return response()->json([
            'title' => $n ? Str::limit((string) ($data['title'] ?? 'New activity'), 80) : 'Vendra',
            'body' => $n ? Str::limit((string) ($data['body'] ?? ''), 140) : 'Open Vendra to see what’s new.',
            'url' => $url,
            'tag' => $n ? 'vendra-'.$n->id : 'vendra',
        ])->header('Cache-Control', 'no-store');
    }
}
