<?php

namespace App\Http\Middleware;

use App\Models\Membership;
use App\Support\CurrentBusiness;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/** Dashboard requests operate on one explicitly selected business the user actively belongs to. */
class SetCurrentBusiness
{
    public function __construct(private CurrentBusiness $current) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $memberships = Membership::with('business')->where('user_id', $user->id)->where('is_active', true)->get();
        if ($memberships->isEmpty()) {
            return $user->is_platform_admin ? redirect()->route('admin.index') : redirect()->route('onboarding.business');
        }

        $membership = $memberships->firstWhere('business_id', (int) $request->session()->get('active_business_id')) ?? $memberships->first();
        $request->session()->put('active_business_id', $membership->business_id);
        $this->current->set($membership->business, $membership);

        View::share('currentBusiness', $membership->business);
        View::share('currentMembership', $membership);
        View::share('allMemberships', $memberships);
        View::share('unreadNotifications', $user->unreadNotifications()->get()
            ->filter(fn ($n) => (int) ($n->data['business_id'] ?? 0) === (int) $membership->business_id)->count());

        return $next($request);
    }
}
