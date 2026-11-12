<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsage;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\SubscriptionPayment;
use App\Services\AuditLogger;
use App\Support\Money;
use Illuminate\Http\Request;

/**
 * Platform administration (P4/P8): accounts, plans, health/usage, support grants, admin logs.
 * No customer conversation feed. Business content only through an active owner grant.
 */
class AdminController extends Controller
{
    public function index()
    {
        $period = now()->format('Y-m');

        return view('admin.index', [
            'businesses' => Business::with('subscription')->withCount(['memberships', 'customers'])->orderBy('name')->get(),
            'usage' => AiUsage::where('period', $period)->get()->keyBy('business_id'),
            'failedJobs' => \Illuminate\Support\Facades\DB::table('failed_jobs')->count(),
            'pendingJobs' => \Illuminate\Support\Facades\DB::table('jobs')->count(),
        ]);
    }

    public function show(Business $business)
    {
        $grant = $business->supportGrants()->where('admin_user_id', auth()->id())->whereNull('revoked_at')->where('expires_at', '>', now())->first();
        if ($grant) {
            AuditLogger::log($business->id, 'support.accessed', $grant, [], 'Viewed business summary under grant', 'platform');
        }

        return view('admin.show', [
            'business' => $business->load('subscription.payments'),
            'grant' => $grant,
            'owner' => $business->owner(),
            'conversations' => $grant ? $business->conversations()->where('channel', 'web')->latest('last_activity_at')->limit(20)->get() : collect(),
            'counts' => [
                'appointments' => $business->appointments()->count(), 'orders' => $business->orders()->count(),
                'quotations' => $business->quotations()->count(), 'requests' => $business->serviceRequests()->count(),
            ],
        ]);
    }

    public function updateSubscription(Request $request, Business $business)
    {
        $data = $request->validate([
            'plan_name' => 'required|string|max:60', 'status' => 'required|in:trial,active,past_due,cancelled',
            'monthly_fee' => 'required|numeric|min:0', 'ai_monthly_limit' => 'required|integer|min:0|max:100000',
            'staff_limit' => 'required|integer|min:0|max:100', 'renews_on' => 'nullable|date', 'notes' => 'nullable|string|max:1000',
        ]);
        $sub = $business->subscription ?? $business->subscription()->make(['starts_on' => now()->toDateString()]);
        $sub->fill([
            'plan_name' => $data['plan_name'], 'status' => $data['status'], 'monthly_fee_cents' => Money::toCents($data['monthly_fee']),
            'ai_monthly_limit' => $data['ai_monthly_limit'], 'staff_limit' => $data['staff_limit'],
            'renews_on' => $data['renews_on'] ?? null, 'notes' => $data['notes'] ?? null,
        ]);
        $sub->business_id = $business->id;
        $sub->save();
        AuditLogger::log(null, 'platform.subscription_updated', $sub, ['business' => $business->slug] + $sub->only('plan_name', 'status', 'monthly_fee_cents', 'ai_monthly_limit'), null, 'platform');

        return back()->with('status', 'Subscription saved.');
    }

    /** Vendra fees are recorded separately from merchants’ customer payments; verify before marking paid. */
    public function recordPayment(Request $request, Business $business)
    {
        $data = $request->validate(['amount' => 'required|numeric|min:1', 'reference' => 'required|string|max:120', 'verified' => 'accepted']);
        $sub = $business->subscription ?? abort(422, 'No subscription.');
        $p = SubscriptionPayment::create([
            'subscription_id' => $sub->id, 'amount_cents' => Money::toCents($data['amount']), 'reference' => $data['reference'],
            'verified_by' => $request->user()->id, 'verified_at' => now(),
        ]);
        AuditLogger::log(null, 'platform.payment_recorded', $p, ['business' => $business->slug, 'amount_cents' => $p->amount_cents], null, 'platform');

        return back()->with('status', 'Payment recorded.');
    }

    public function setStatus(Request $request, Business $business)
    {
        $data = $request->validate(['status' => 'required|in:active,suspended', 'reason' => 'required|string|max:255']);
        $business->update(['status' => $data['status']]);
        AuditLogger::log(null, 'platform.business_'.$data['status'], $business, ['business' => $business->slug], $data['reason'], 'platform');

        return back()->with('status', 'Business status updated.');
    }

    public function logs()
    {
        return view('admin.logs', ['logs' => AuditLog::whereNull('business_id')->orWhere('actor_type', 'platform')->latest()->paginate(50)]);
    }
}
