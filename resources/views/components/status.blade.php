@props(['value', 'label' => null])
<span class="badge badge-{{ status_tone($value) }}">
    <span aria-hidden="true">●</span> {{ $label ?? ucfirst(str_replace('_', ' ', $value)) }}
</span>
