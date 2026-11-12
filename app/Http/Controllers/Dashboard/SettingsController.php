<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Conversation;
use App\Models\KnowledgeEntry;
use App\Models\SupportGrant;
use App\Models\User;
use App\Services\Ai\AssistantService;
use App\Services\AuditLogger;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Owner-only business configuration (P4). */
class SettingsController extends DashboardController
{
    public function profile()
    {
        $this->authorizeAbility('settings.manage');

        return view('dashboard.settings.profile');
    }

    public function updateProfile(Request $request)
    {
        $this->authorizeAbility('settings.manage');
        $data = $request->validate([
            'name' => 'required|string|max:120', 'description' => 'nullable|string|max:1000', 'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:190', 'address' => 'nullable|string|max:255',
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $b = $this->business();
        $b->update($data);
        AuditLogger::log($b->id, 'settings.profile_updated', $b, $data);

        return back()->with('status', 'Profile saved.');
    }

    public function workflow()
    {
        $this->authorizeAbility('settings.manage');

        return view('dashboard.settings.workflow', ['modules' => config('vendra.modules')]);
    }

    public function updateWorkflow(Request $request)
    {
        $this->authorizeAbility('settings.manage');
        $data = $request->validate([
            'modules' => 'array', 'modules.*' => 'in:'.implode(',', array_keys(config('vendra.modules'))),
            'booking_approval' => 'required|in:auto,manual', 'pending_deadline_hours' => 'required|integer|min:1|max:168',
            'slot_interval_minutes' => 'required|integer|in:15,30,60', 'booking_lead_minutes' => 'required|integer|min:0|max:2880',
            'booking_horizon_days' => 'required|integer|min:1|max:180', 'cancellation_hours' => 'required|integer|min:0|max:168',
            'order_approval' => 'required|in:auto,manual', 'order_reservation_hours' => 'required|integer|min:1|max:336',
            'fulfillment_methods' => 'array', 'fulfillment_methods.*' => 'in:pickup,delivery',
            'quote_valid_days' => 'required|integer|min:1|max:90', 'response_hours' => 'required|string|max:120',
        ]);
        $b = $this->business();
        $modules = array_values($data['modules'] ?? []);
        if (! $modules) {
            return back()->withErrors(['rule' => 'Enable at least one module.']);
        }
        $settings = array_merge($b->settings ?? [], collect($data)->except('modules')->all());
        $settings['fulfillment_methods'] = array_values($data['fulfillment_methods'] ?? ['pickup']) ?: ['pickup'];
        $before = $b->modules;
        // Disabling a module stops new submissions; existing work stays visible and manageable.
        $b->update(['modules' => $modules, 'settings' => $settings, 'onboarding_step' => max(3, $b->onboarding_step)]);
        AuditLogger::log($b->id, 'settings.workflow_updated', $b, ['modules' => [$before, $modules]]);

        return back()->with('status', 'Workflow settings saved.');
    }

    public function hours()
    {
        $this->authorizeAbility('hours.manage');

        return view('dashboard.settings.hours', ['hours' => $this->business()->hours()->orderBy('weekday')->get()->keyBy('weekday')]);
    }

    public function updateHours(Request $request)
    {
        $this->authorizeAbility('hours.manage');
        $data = $request->validate(['days' => 'required|array', 'days.*.opens_at' => 'nullable|date_format:H:i', 'days.*.closes_at' => 'nullable|date_format:H:i']);
        $b = $this->business();
        foreach (range(0, 6) as $d) {
            $row = $data['days'][$d] ?? [];
            $closed = empty($row['opens_at']) || empty($row['closes_at']) || $row['opens_at'] >= $row['closes_at'];
            $b->hours()->updateOrCreate(['weekday' => $d], [
                'opens_at' => $closed ? null : $row['opens_at'], 'closes_at' => $closed ? null : $row['closes_at'], 'is_closed' => $closed,
            ]);
        }
        AuditLogger::log($b->id, 'settings.hours_updated', $b);

        return back()->with('status', 'Hours saved. Existing bookings are not changed.');
    }

    public function knowledge()
    {
        $this->authorizeAbility('settings.manage');

        return view('dashboard.settings.knowledge', ['entries' => $this->business()->knowledgeEntries()->latest()->get()]);
    }

    public function storeKnowledge(Request $request)
    {
        $this->authorizeAbility('settings.manage');
        $data = $request->validate(['kind' => 'required|in:faq,policy', 'title' => 'required|string|max:160', 'body' => 'required|string|max:3000']);
        $e = new KnowledgeEntry($data + ['is_published' => $request->boolean('is_published')]);
        $e->business_id = $this->business()->id;
        $e->save();
        AuditLogger::log($e->business_id, 'knowledge.created', $e, ['title' => $e->title, 'published' => $e->is_published]);

        return back()->with('status', 'Saved. Only published entries are used by the assistant.');
    }

    public function updateKnowledge(Request $request, int $id)
    {
        $this->authorizeAbility('settings.manage');
        $e = $this->business()->knowledgeEntries()->findOrFail($id);
        if ($request->has('delete')) {
            $e->delete();
            AuditLogger::log($e->business_id, 'knowledge.deleted', $e, ['title' => $e->title]);

            return back()->with('status', 'Deleted.');
        }
        $data = $request->validate(['title' => 'required|string|max:160', 'body' => 'required|string|max:3000']);
        $e->update($data + ['is_published' => $request->boolean('is_published')]);
        AuditLogger::log($e->business_id, 'knowledge.updated', $e, ['published' => $e->is_published]);

        return back()->with('status', 'Updated.');
    }

    public function assistant(Request $request, ConversationService $conversations)
    {
        $this->authorizeAbility('settings.manage');
        $test = $this->testConversation($conversations);

        return view('dashboard.settings.assistant', [
            'messages' => $test->messages()->orderBy('id')->get(),
            'provider' => config('vendra.ai.provider') === 'gemini' && config('vendra.ai.gemini_key') ? 'Gemini ('.config('vendra.ai.gemini_model').')' : 'Offline rules engine (no API key set)',
        ]);
    }

    public function updateAssistant(Request $request)
    {
        $this->authorizeAbility('settings.manage');
        $data = $request->validate(['ai_enabled' => 'nullable|boolean', 'ai_tone' => 'required|in:friendly,formal,casual', 'greeting' => 'nullable|string|max:300']);
        $b = $this->business();
        $b->update(['ai_enabled' => $request->boolean('ai_enabled'), 'ai_tone' => $data['ai_tone'], 'greeting' => $data['greeting'] ?? null]);
        AuditLogger::log($b->id, 'settings.assistant_updated', $b, $b->only('ai_enabled', 'ai_tone'));

        return back()->with('status', 'Assistant settings saved.');
    }

    /** Owner tests the assistant against live business data in a private test conversation. */
    public function testAssistant(Request $request, ConversationService $conversations, AssistantService $assistant)
    {
        $this->authorizeAbility('settings.manage');
        $data = $request->validate(['message' => 'required|string|max:1000']);
        $c = $this->testConversation($conversations);
        if ($request->has('reset')) {
            $c->messages()->delete();
            $c->update(['state' => 'ai_active']);

            return back();
        }
        if ($c->state !== 'ai_active') {
            $c->update(['state' => 'ai_active']);
        }
        $conversations->addMessage($c, 'customer', $data['message']);
        $assistant->reply($c, $data['message']);
        $b = $this->business();
        $b->update(['settings' => array_merge($b->settings, ['assistant_tested' => true])]);

        return back();
    }

    private function testConversation(ConversationService $conversations): Conversation
    {
        $b = $this->business();
        $c = $b->conversations()->where('channel', 'test')->first();
        if (! $c) {
            [$c] = $conversations->start($b);
            $c->update(['channel' => 'test', 'state' => 'ai_active']);
        }

        return $c;
    }

    // ------------------------------------------------------------ Support access, data, closure

    public function data()
    {
        $this->authorizeAbility('settings.manage');
        $b = $this->business();

        return view('dashboard.settings.data', [
            'grants' => $b->supportGrants()->with('admin')->latest()->get(),
            'admins' => User::where('is_platform_admin', true)->get(),
            'subscription' => $b->subscription,
        ]);
    }

    /** Platform support may see business content only through an explicit, time-limited, revocable grant. */
    public function grantSupport(Request $request)
    {
        $this->authorizeAbility('settings.manage');
        $data = $request->validate(['admin_user_id' => 'required|integer', 'hours' => 'required|integer|in:1,4,24,72', 'reason' => 'required|string|max:255']);
        abort_unless(User::where('id', $data['admin_user_id'])->where('is_platform_admin', true)->exists(), 422);
        $g = SupportGrant::create([
            'business_id' => $this->business()->id, 'granted_by' => $request->user()->id, 'admin_user_id' => $data['admin_user_id'],
            'reason' => $data['reason'], 'expires_at' => now()->addHours((int) $data['hours']),
        ]);
        AuditLogger::log($g->business_id, 'support.granted', $g, ['hours' => $data['hours']], $data['reason']);

        return back()->with('status', 'Support access granted until '.$g->expires_at->timezone($this->business()->timezone)->format('M j, g:i A').'.');
    }

    public function revokeSupport(int $id)
    {
        $this->authorizeAbility('settings.manage');
        $g = $this->business()->supportGrants()->findOrFail($id);
        $g->update(['revoked_at' => now()]);
        AuditLogger::log($g->business_id, 'support.revoked', $g);

        return back()->with('status', 'Support access revoked.');
    }

    /** Owner exports: logged, generated on demand, exclude credentials and platform-only details. */
    public function export(Request $request, string $type)
    {
        $this->authorizeAbility('exports.create');
        $b = $this->business();
        abort_unless(in_array($type, ['customers', 'appointments', 'orders', 'quotations', 'service_requests'], true), 404);
        AuditLogger::log($b->id, 'export.created', null, ['type' => $type]);

        $rows = match ($type) {
            'customers' => $b->customers()->get(['id', 'name', 'email', 'phone', 'marketing_consent', 'created_at'])->toArray(),
            'appointments' => $b->appointments()->with('customer:id,name')->get()->map(fn ($a) => [
                'reference' => $a->reference, 'customer' => $a->customer?->name, 'service' => $a->service_name, 'price' => $a->price_cents / 100,
                'starts_at' => $a->starts_at->timezone($b->timezone)->toDateTimeString(), 'status' => $a->status, 'source' => $a->source,
            ])->toArray(),
            'orders' => $b->orders()->with('customer:id,name')->get()->map(fn ($o) => [
                'reference' => $o->reference, 'customer' => $o->customer?->name, 'status' => $o->status, 'payment' => $o->payment_status,
                'method' => $o->fulfillment_method, 'total' => $o->total_cents / 100, 'created_at' => $o->created_at->timezone($b->timezone)->toDateTimeString(),
            ])->toArray(),
            'quotations' => $b->quotations()->with('customer:id,name', 'currentVersion')->get()->map(fn ($q) => [
                'reference' => $q->reference, 'customer' => $q->customer?->name, 'status' => $q->status,
                'version' => $q->currentVersion?->version, 'total' => ($q->currentVersion?->total_cents ?? 0) / 100,
            ])->toArray(),
            'service_requests' => $b->serviceRequests()->with('customer:id,name')->get()->map(fn ($s) => [
                'reference' => $s->reference, 'customer' => $s->customer?->name, 'status' => $s->status, 'location' => $s->location,
                'preferred_date' => $s->preferred_date?->toDateString(), 'created_at' => $s->created_at->timezone($b->timezone)->toDateTimeString(),
            ])->toArray(),
        };

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($rows) {
                fputcsv($out, array_keys($rows[0]));
                foreach ($rows as $r) {
                    fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'".$v : $v, $r)); // CSV injection guard
                }
            }
            fclose($out);
        }, "vendra-{$b->slug}-{$type}-".now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** Closure: stops new submissions, shows unresolved work, 30-day recovery/export period. */
    public function close(Request $request)
    {
        $this->authorizeAbility('business.close');
        $request->validate(['confirm_name' => 'required|string']);
        $b = $this->business();
        if ($request->input('confirm_name') !== $b->name) {
            return back()->withErrors(['rule' => 'Type the exact business name to confirm.']);
        }
        if ($request->has('cancel')) {
            $b->update(['status' => 'active', 'closing_requested_at' => null]);
            AuditLogger::log($b->id, 'business.closure_cancelled', $b);

            return back()->with('status', 'Closure cancelled.');
        }
        $b->update(['status' => 'closing', 'closing_requested_at' => now()]);
        AuditLogger::log($b->id, 'business.closure_requested', $b);

        return back()->with('status', 'Closure requested. New submissions are stopped. You have 30 days to export or cancel.');
    }
}
