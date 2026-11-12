@if (session('status'))
    <div role="status" class="mb-5 flex items-start gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" /><span>{{ session('status') }}</span></div>
@endif
@if ($errors->any())
    <div role="alert" class="mb-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-900">
        <p class="font-medium">Please check the following:</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
