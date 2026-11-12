<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Conversation;
use App\Services\Ai\AssistantService;
use App\Services\ConversationService;
use App\Services\Notifier;
use Illuminate\Http\Request;

/** Customer chat widget. Conversations are bound to an unguessable token kept in the customer's session. */
class ChatController extends Controller
{
    public function __construct(private ConversationService $conversations, private AssistantService $assistant) {}

    public function history(Request $request, Business $business)
    {
        $c = $this->current($request, $business);

        return response()->json($this->payload($c));
    }

    public function send(Request $request, Business $business)
    {
        abort_if($business->status === 'suspended', 404);
        $data = $request->validate(['message' => 'required|string|max:1000']);
        $c = $this->current($request, $business, create: true);

        $this->conversations->addMessage($c, 'customer', $data['message']);
        $wasWaiting = $c->state !== 'ai_active';
        $result = $this->assistant->reply($c, $data['message']);

        if ($result['handed_off'] || $wasWaiting) {
            Notifier::staff($business, 'Customer waiting in chat', 'A customer needs a reply. '.($c->fresh()->handoff_summary ?? ''),
                route('dashboard.conversations.show', $c->id), $c->assigned_to);
        }

        return response()->json($this->payload($c->fresh()));
    }

    public function handoff(Request $request, Business $business)
    {
        $c = $this->current($request, $business, create: true);
        if ($c->state === 'ai_active') {
            $this->conversations->handoff($c, 'Customer tapped "Talk to staff".', 'customer');
            $this->conversations->addMessage($c, 'system', 'A staff member will reply here during '.$business->setting('response_hours').'.');
            Notifier::staff($business, 'Customer asked for staff', 'Open the conversation to reply.', route('dashboard.conversations.show', $c->id));
        }

        return response()->json($this->payload($c->fresh()));
    }

    private function current(Request $request, Business $business, bool $create = false): ?Conversation
    {
        $key = 'vendra.chat.'.$business->id;
        $c = $this->conversations->findByToken($business, $request->session()->get($key));
        if (! $c && $create) {
            [$c, $token] = $this->conversations->start($business);
            $request->session()->put($key, $token);
        }

        return $c;
    }

    private function payload(?Conversation $c): array
    {
        if (! $c) {
            return ['state' => 'new', 'messages' => []];
        }

        return [
            'state' => $c->state,
            'messages' => $c->messages()->customerVisible()->orderBy('id')->get()->map(fn ($m) => [
                'id' => $m->id,
                'from' => $m->sender_type,
                'name' => $m->sender_type === 'staff' ? ($m->user?->name ? explode(' ', $m->user->name)[0].' (staff)' : 'Staff') : null,
                'body' => $m->body,
                'draft' => $m->meta['draft'] ?? null,
                'at' => $m->created_at->toIso8601String(),
            ]),
        ];
    }
}
