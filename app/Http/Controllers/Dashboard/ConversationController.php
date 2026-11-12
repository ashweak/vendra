<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\Request;

class ConversationController extends DashboardController
{
    public function __construct(private ConversationService $service) {}

    public function index(Request $request)
    {
        $b = $this->business();
        $state = $request->query('state');
        $q = $b->conversations()->with('customer', 'assignee')
            ->when($state, fn ($q) => $q->where('state', $state))
            ->where('channel', 'web')
            ->latest('last_activity_at');
        if (! $this->membership()->can('conversations.all')) {
            $q->where('assigned_to', auth()->id());
        }

        return view('dashboard.conversations.index', ['conversations' => $q->paginate(25)->withQueryString(), 'state' => $state]);
    }

    public function show(int $id)
    {
        $c = $this->find($id);
        $c->load(['messages.user', 'customer']);

        return view('dashboard.conversations.show', [
            'conversation' => $c,
            'conversations' => $this->scopeVisible($this->business()->conversations()->where('channel', 'web')->with('customer'))->latest('last_activity_at')->limit(30)->get(),
            'team' => $this->business()->activeStaff()->get(),
        ]);
    }

    public function reply(Request $request, int $id)
    {
        $c = $this->find($id);
        $data = $request->validate(['body' => 'required|string|max:2000', 'internal' => 'nullable|boolean']);
        $request->boolean('internal')
            ? $this->service->note($c, $request->user(), $data['body'])
            : $this->service->staffReply($c, $request->user(), $data['body']);

        return back();
    }

    public function takeOver(Request $request, int $id)
    {
        $this->service->takeOver($this->find($id), $request->user());

        return back()->with('status', 'You are now handling this conversation. The AI is paused.');
    }

    public function resume(Request $request, int $id)
    {
        $this->service->resumeAi($this->find($id), $request->user());

        return back()->with('status', 'AI assistant resumed.');
    }

    public function assign(Request $request, int $id)
    {
        $this->authorizeAbility('conversations.assign');
        $c = $this->find($id);
        $this->service->assign($c, $request->integer('user_id') ?: null);

        return back()->with('status', 'Assignment updated.');
    }

    public function close(int $id)
    {
        $this->service->close($this->find($id));

        return back()->with('status', 'Conversation closed.');
    }

    private function find(int $id): Conversation
    {
        $c = $this->business()->conversations()->findOrFail($id);
        $this->authorizeRecord($c, 'conversations.reply');

        return $c;
    }
}
