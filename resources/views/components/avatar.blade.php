@props(['user'])
{{-- Profile picture, or initials when there's none. Size comes from the class. --}}
@if ($user?->avatar_path)
    <img src="{{ $user->avatarUrl() }}" alt="" {{ $attributes->merge(['class' => 'shrink-0 rounded-full object-cover']) }}>
@else
    <span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-full bg-ink-900 font-semibold text-sun']) }} aria-hidden="true">{{ $user?->initials() ?: '?' }}</span>
@endif
