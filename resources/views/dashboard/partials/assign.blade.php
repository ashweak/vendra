@if ($currentMembership->can('conversations.assign'))
<form method="POST" action="{{ $action }}" class="card text-sm">
    @csrf
    <label class="label" for="assign">Assigned to</label>
    <select id="assign" name="user_id" class="input" onchange="this.form.submit()">
        <option value="">Unassigned</option>
        @foreach ($currentBusiness->activeStaff()->get() as $t)<option value="{{ $t->id }}" @selected((int) $record->assigned_to === (int) $t->id)>{{ $t->name }} ({{ $t->role }})</option>@endforeach
    </select>
    <noscript><button class="btn btn-secondary btn-sm mt-2">Save</button></noscript>
</form>
@endif
