<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\AiUsage;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class OverviewController extends DashboardController
{
    public function index()
    {
        $b = $this->business();
        $m = $this->membership();
        $tz = $b->timezone;
        $dayStart = CarbonImmutable::now($tz)->startOfDay()->utc();
        $dayEnd = $dayStart->addDay();

        $appointmentsToday = $this->scopeVisible($b->appointments()->whereIn('status', ['pending_approval', 'confirmed', 'completed'])
            ->whereBetween('starts_at', [$dayStart, $dayEnd]))->with('customer', 'staff')->orderBy('starts_at')->get();

        return view('dashboard.overview', [
            'appointmentsToday' => $appointmentsToday,
            'counts' => [
                'waiting' => $this->scopeVisible($b->conversations()->where('state', 'waiting_for_staff'))->count(),
                'pending_bookings' => $this->scopeVisible($b->appointments()->where('status', 'pending_approval'))->count(),
                'pending_orders' => $this->scopeVisible($b->orders()->where('status', 'pending_approval'))->count(),
                'open_quotes' => $this->scopeVisible($b->quotations()->whereIn('status', ['submitted', 'under_review', 'awaiting_owner_approval']))->count(),
                'open_requests' => $this->scopeVisible($b->serviceRequests()->whereIn('status', ['submitted', 'under_review']))->count(),
            ],
            'usage' => AiUsage::forBusiness($b)->where('period', now()->format('Y-m'))->first(),
            'aiLimit' => $b->subscription?->ai_monthly_limit ?? config('vendra.ai.default_monthly_limit'),
            'notifications' => auth()->user()->unreadNotifications()->latest()->get()->filter(fn ($n) => (int) ($n->data['business_id'] ?? 0) === (int) $b->id)->take(8),
            'showSetup' => ! $b->activated_at && $m->isOwner(),
        ]);
    }

    public function switch(Request $request)
    {
        $id = (int) $request->input('business_id');
        abort_unless($request->user()->membershipFor($id), 403);
        $request->session()->put('active_business_id', $id);

        return redirect()->route('dashboard.overview');
    }

    public function readNotifications(Request $request)
    {
        $b = $this->business();
        $request->user()->unreadNotifications()->get()->filter(fn ($n) => (int) ($n->data['business_id'] ?? 0) === (int) $b->id)->each->markAsRead();

        return back();
    }

    /** Onboarding checklist: prerequisites must be met before activation (P8). */
    public function onboarding()
    {
        $this->authorizeAbility('settings.manage');

        return view('dashboard.onboarding', ['steps' => $this->steps()]);
    }

    public function activate()
    {
        $this->authorizeAbility('settings.manage');
        $b = $this->business();
        $missing = collect($this->steps())->filter(fn ($s) => $s['required'] && ! $s['done']);
        if ($missing->isNotEmpty()) {
            return back()->withErrors(['rule' => 'Finish these first: '.$missing->pluck('label')->implode(', ').'.']);
        }
        $b->update(['activated_at' => now(), 'onboarding_step' => 9]);
        AuditLogger::log($b->id, 'business.activated', $b);

        return redirect()->route('dashboard.overview')->with('status', 'Your customer page is live for fictional-data demos. Share: '.route('public.home', $b));
    }

    private function steps(): array
    {
        $b = $this->business();
        $needsServices = $b->moduleEnabled('appointments');
        $needsProducts = $b->moduleEnabled('orders');

        return [
            ['label' => 'Business details', 'done' => (bool) ($b->name && ($b->phone || $b->email)), 'required' => true, 'url' => route('dashboard.settings.profile')],
            ['label' => 'Choose modules', 'done' => ! empty($b->modules), 'required' => true, 'url' => route('dashboard.settings.workflow')],
            ['label' => 'Opening hours', 'done' => $b->hours()->where('is_closed', false)->exists(), 'required' => $needsServices, 'url' => route('dashboard.settings.hours')],
            ['label' => 'Services in catalog', 'done' => ! $needsServices || $b->catalogItems()->services()->active()->exists(), 'required' => $needsServices, 'url' => route('dashboard.catalog.index')],
            ['label' => 'Products in catalog', 'done' => ! $needsProducts || $b->catalogItems()->products()->active()->exists(), 'required' => $needsProducts, 'url' => route('dashboard.catalog.index')],
            ['label' => 'Workflow settings', 'done' => $b->onboarding_step >= 3, 'required' => true, 'url' => route('dashboard.settings.workflow')],
            ['label' => 'Policies & FAQ published', 'done' => $b->knowledgeEntries()->where('is_published', true)->exists(), 'required' => false, 'url' => route('dashboard.settings.knowledge')],
            ['label' => 'Invite team', 'done' => $b->memberships()->count() > 1, 'required' => false, 'url' => route('dashboard.team.index')],
            ['label' => 'Test the assistant', 'done' => (bool) ($b->settings['assistant_tested'] ?? false), 'required' => $b->ai_enabled, 'url' => route('dashboard.settings.assistant')],
        ];
    }
}
