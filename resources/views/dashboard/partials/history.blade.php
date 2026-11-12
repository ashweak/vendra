@if ($logs->isNotEmpty())
<div class="card text-sm">
    <h2 class="font-semibold">History</h2>
    <ul class="mt-2 space-y-2">
        @foreach ($logs as $l)
            <li><span class="font-medium">{{ str_replace(['.', '_'], [' · ', ' '], $l->action) }}</span>
                <span class="block text-xs text-gray-500">{{ local_time($l->created_at, $currentBusiness->timezone) }} · {{ $l->actor_type === 'user' ? ($l->actor?->name ?? 'User') : ucfirst($l->actor_type) }}{{ $l->reason ? ' · '.$l->reason : '' }}</span></li>
        @endforeach
    </ul>
</div>
@endif
