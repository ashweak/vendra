<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Membership;
use App\Support\CurrentBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class DashboardController extends Controller
{
    protected function business(): Business
    {
        return app(CurrentBusiness::class)->get();
    }

    protected function membership(): Membership
    {
        return app(CurrentBusiness::class)->membership();
    }

    protected function authorizeAbility(string $ability): void
    {
        abort_unless($this->membership()->can($ability), 403, 'You do not have permission to do that.');
    }

    /** Ability + record-level check: staff only act on records assigned to them. Cross-tenant → 404. */
    protected function authorizeRecord(Model $record, string $ability): void
    {
        abort_unless((int) $record->business_id === (int) $this->business()->id, 404);
        $this->authorizeAbility($ability);
        abort_unless($this->membership()->canAccessRecord($record), 403, 'This record is not assigned to you.');
    }

    /** Staff see only assigned records in lists. */
    protected function scopeVisible($query)
    {
        return $this->membership()->isManagerOrOwner() ? $query : $query->where('assigned_to', auth()->id());
    }

    protected function requireModule(string $module): void
    {
        abort_unless($this->business()->moduleEnabled($module), 404);
    }
}
