<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\AssistantGap;
use App\Models\KnowledgeEntry;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/** Questions the auto-assistant couldn't answer; one click turns an answer into a published FAQ. */
class AssistantGapController extends DashboardController
{
    public function index(Request $request)
    {
        $this->authorizeAbility('settings.manage');
        $status = in_array($request->query('show'), ['answered', 'dismissed'], true) ? $request->query('show') : 'open';
        $gaps = AssistantGap::forBusiness($this->business())->where('status', $status)
            ->with('knowledgeEntry')->orderByDesc('times_asked')->orderByDesc('last_asked_at')->paginate(20)->withQueryString();

        return view('dashboard.settings.unanswered', ['gaps' => $gaps, 'status' => $status]);
    }

    public function answer(Request $request, int $id)
    {
        $this->authorizeAbility('settings.manage');
        $gap = AssistantGap::forBusiness($this->business())->findOrFail($id);
        $data = $request->validate(['title' => 'required|string|max:160', 'body' => 'required|string|max:3000']);
        $entry = new KnowledgeEntry(['kind' => 'faq', 'title' => $data['title'], 'body' => $data['body'], 'is_published' => true]);
        $entry->business_id = $gap->business_id;
        $entry->save();
        $gap->update(['status' => 'answered', 'knowledge_entry_id' => $entry->id]);
        AuditLogger::log($gap->business_id, 'knowledge.created', $entry, ['title' => $entry->title, 'published' => true, 'from_unanswered_question' => $gap->id]);

        return back()->with('status', 'Added to your FAQ. The auto-assistant will use this answer from now on.');
    }

    public function dismiss(int $id)
    {
        $this->authorizeAbility('settings.manage');
        $gap = AssistantGap::forBusiness($this->business())->findOrFail($id);
        $gap->update(['status' => 'dismissed']);

        return back()->with('status', 'Dismissed. It comes back if customers keep asking.');
    }

    public static function openCount(int $businessId): int
    {
        return AssistantGap::where('business_id', $businessId)->where('status', 'open')->count();
    }
}
