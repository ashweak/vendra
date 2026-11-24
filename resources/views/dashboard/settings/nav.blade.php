@php
    $openGaps = \App\Http\Controllers\Dashboard\AssistantGapController::openCount($currentBusiness->id);
@endphp
<nav class="mb-5 flex flex-wrap gap-1 border-b border-line pb-2 text-sm" aria-label="Settings">
    @foreach ([['Profile', 'dashboard.settings.profile'], ['Appearance', 'dashboard.settings.appearance'], ['Photos', 'dashboard.settings.photos'], ['Modules & workflow', 'dashboard.settings.workflow'], ['Hours', 'dashboard.settings.hours'], ['Policies & FAQ', 'dashboard.settings.knowledge'], ['Unanswered questions', 'dashboard.settings.unanswered'], ['Auto-assistant', 'dashboard.settings.assistant'], ['Data, support & plan', 'dashboard.settings.data'], ['Setup checklist', 'dashboard.onboarding']] as [$l, $r])
        <a href="{{ route($r) }}" @class(['inline-flex items-center gap-1.5 rounded-lg px-3 py-2', 'bg-ink-50 font-medium text-ink-700' => request()->routeIs($r), 'text-ink-600 hover:bg-ink-100' => ! request()->routeIs($r)]) @if (request()->routeIs($r)) aria-current="page" @endif>{{ $l }}@if ($r === 'dashboard.settings.unanswered' && $openGaps)<span class="rounded-full bg-sun px-1.5 text-xs font-semibold text-ink-900">{{ $openGaps }}<span class="sr-only"> open</span></span>@endif</a>
    @endforeach
</nav>
