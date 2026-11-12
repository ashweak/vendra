@props(['value', 'label' => null])
<span class="badge badge-{{ status_tone($value) }}">
    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70" aria-hidden="true"></span>{{ $label ?? ucfirst(str_replace('_', ' ', $value)) }}
</span>
