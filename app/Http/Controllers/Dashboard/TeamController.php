<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Membership;
use App\Models\StaffSchedule;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamController extends DashboardController
{
    public function index()
    {
        $this->authorizeAbility('hours.manage');
        $b = $this->business();

        return view('dashboard.team', [
            'members' => $b->memberships()->with('user')->orderByRaw("case role when 'owner' then 0 when 'manager' then 1 else 2 end")->get(),
            'schedules' => $b->staffSchedules()->get()->groupBy('user_id'),
            'canManageTeam' => $this->membership()->can('team.manage'),
            'staffLimit' => $b->subscription?->staff_limit ?? 3,
        ]);
    }

    /** Owner adds a team member. New users get a one-time temporary password to change after sign-in. */
    public function store(Request $request)
    {
        $this->authorizeAbility('team.manage');
        $b = $this->business();
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:190', 'role' => 'required|in:manager,staff']);
        $limit = $b->subscription?->staff_limit ?? 3;
        if ($b->memberships()->where('is_active', true)->count() >= $limit + 1) {
            return back()->withErrors(['rule' => "Your plan allows {$limit} team members besides the owner."]);
        }

        $temp = null;
        $user = User::where('email', strtolower($data['email']))->first();
        if (! $user) {
            $temp = Str::password(14, symbols: false);
            $user = User::create(['name' => $data['name'], 'email' => strtolower($data['email']), 'password' => $temp]);
        }
        if ($b->memberships()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['rule' => 'That person is already on the team.']);
        }
        $m = new Membership(['user_id' => $user->id, 'role' => $data['role']]);
        $m->business_id = $b->id;
        $m->save();
        AuditLogger::log($b->id, 'team.added', $m, ['user' => $user->email, 'role' => $data['role']]);

        return back()->with('status', $temp
            ? "Added {$user->name}. Temporary password (shown once): {$temp}"
            : "Added {$user->name}. They can sign in with their existing Vendra account.");
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeAbility('team.manage');
        $m = $this->business()->memberships()->findOrFail($id);
        abort_if($m->role === 'owner', 422, 'Use ownership transfer to change the owner.');
        $data = $request->validate(['role' => 'required|in:manager,staff', 'is_active' => 'required|boolean']);
        $before = $m->only('role', 'is_active');
        $m->update($data);
        if (! $m->is_active) {
            // Disabled staff lose access immediately; historical actions remain attributed.
            DB::table('sessions')->where('user_id', $m->user_id)->delete();
        }
        AuditLogger::log($m->business_id, 'team.updated', $m, ['before' => $before, 'after' => $m->only('role', 'is_active')]);

        return back()->with('status', 'Team member updated.');
    }

    public function schedule(Request $request, int $userId)
    {
        $this->authorizeAbility('hours.manage');
        $b = $this->business();
        abort_unless($b->memberships()->where('user_id', $userId)->exists(), 404);
        $data = $request->validate(['days' => 'array', 'days.*.start' => 'nullable|date_format:H:i', 'days.*.end' => 'nullable|date_format:H:i']);
        DB::transaction(function () use ($b, $userId, $data) {
            StaffSchedule::forBusiness($b)->where('user_id', $userId)->delete();
            foreach ($data['days'] ?? [] as $weekday => $d) {
                if (! empty($d['start']) && ! empty($d['end']) && $d['start'] < $d['end']) {
                    $s = new StaffSchedule(['user_id' => $userId, 'weekday' => (int) $weekday, 'starts_at' => $d['start'], 'ends_at' => $d['end']]);
                    $s->business_id = $b->id;
                    $s->save();
                }
            }
        });
        AuditLogger::log($b->id, 'schedule.updated', null, ['user_id' => $userId]);

        return back()->with('status', 'Schedule saved.');
    }
}
