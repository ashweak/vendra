<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\AiUsage;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** Basic reporting. Submitted, confirmed and completed figures are kept separate (P5). */
class ReportController extends DashboardController
{
    public function index(Request $request)
    {
        $this->authorizeAbility('reports.view');
        $b = $this->business();
        $tz = $b->timezone;
        $from = CarbonImmutable::parse($request->query('from', CarbonImmutable::now($tz)->startOfMonth()->toDateString()), $tz)->startOfDay();
        $to = CarbonImmutable::parse($request->query('to', CarbonImmutable::now($tz)->toDateString()), $tz)->endOfDay();
        $range = [$from->utc(), $to->utc()];

        $appt = $b->appointments()->whereBetween('starts_at', $range)->selectRaw('status, count(*) as n, sum(price_cents) as value')->groupBy('status')->get()->keyBy('status');
        $orders = $b->orders()->whereBetween('created_at', $range)->selectRaw('status, count(*) as n, sum(total_cents) as value')->groupBy('status')->get()->keyBy('status');
        $quotes = $b->quotations()->whereBetween('created_at', $range)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $requests = $b->serviceRequests()->whereBetween('created_at', $range)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $conversations = $b->conversations()->whereBetween('created_at', $range);
        $handoffs = $b->auditLogs()->where('action', 'conversation.handoff')->whereBetween('created_at', $range)->count();

        return view('dashboard.reports', [
            'from' => $from, 'to' => $to,
            'appt' => $appt, 'orders' => $orders, 'quotes' => $quotes, 'requests' => $requests,
            'conversationCount' => (clone $conversations)->count(),
            'handoffs' => $handoffs,
            'usage' => AiUsage::forBusiness($b)->orderByDesc('period')->limit(6)->get(),
            'aiLimit' => $b->subscription?->ai_monthly_limit ?? config('vendra.ai.default_monthly_limit'),
            'byStaff' => $b->appointments()->whereBetween('starts_at', $range)->where('status', 'completed')
                ->join('users', 'users.id', '=', 'appointments.staff_id')
                ->selectRaw('users.name as name, count(*) as n')->groupBy('users.name')->get(),
        ]);
    }

    public function logs(Request $request)
    {
        $this->authorizeAbility('logs.view');
        $logs = $this->business()->auditLogs()->with('actor')
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', 'like', $a.'%'))
            ->latest()->paginate(50)->withQueryString();

        return view('dashboard.logs', compact('logs'));
    }
}
