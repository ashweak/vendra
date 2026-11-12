<nav class="mb-5 flex flex-wrap gap-1 border-b border-line pb-2 text-sm" aria-label="Settings">
    @foreach ([['Profile', 'dashboard.settings.profile'], ['Modules & workflow', 'dashboard.settings.workflow'], ['Hours', 'dashboard.settings.hours'], ['Policies & FAQ', 'dashboard.settings.knowledge'], ['AI assistant', 'dashboard.settings.assistant'], ['Data, support & plan', 'dashboard.settings.data'], ['Setup checklist', 'dashboard.onboarding']] as [$l, $r])
        <a href="{{ route($r) }}" @class(['rounded-lg px-3 py-2', 'bg-ink-50 font-medium text-ink-700' => request()->routeIs($r), 'text-ink-600 hover:bg-ink-100' => ! request()->routeIs($r)])>{{ $l }}</a>
    @endforeach
</nav>
