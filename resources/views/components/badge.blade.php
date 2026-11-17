@props([
    'status' => 'info',
    'colors' => [
        'primary' => 'badge-soft-primary',
        'success' => 'badge-soft-success',
        'danger' => 'badge-soft-danger',
        'warning' => 'badge-soft-warning',
        'info' => 'badge-soft-info',
        'secondary' => 'badge-soft-secondary',
    ],
    'labels' => [],
])

@php
    $color = $colors[$status] ?? 'badge-soft-secondary';
    $label = $labels[$status] ?? $status;
@endphp

<span {{ $attributes->merge(['class' => 'badge rounded-pill ' . $color]) }}>{{ $label }}</span>
