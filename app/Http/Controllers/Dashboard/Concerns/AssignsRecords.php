<?php

namespace App\Http\Controllers\Dashboard\Concerns;

use App\Services\AuditLogger;
use Illuminate\Http\Request;

trait AssignsRecords
{
    public function assign(Request $request, int $id)
    {
        $this->authorizeAbility('conversations.assign');
        $record = $this->assignableQuery()->findOrFail($id);
        $uid = $request->integer('user_id') ?: null;
        abort_if($uid && ! $this->business()->memberships()->where('user_id', $uid)->where('is_active', true)->exists(), 422);
        $record->update(['assigned_to' => $uid]);
        AuditLogger::log($record->business_id, strtolower(class_basename($record)).'.assigned', $record, ['assigned_to' => $uid]);

        return back()->with('status', 'Assignment updated.');
    }
}
