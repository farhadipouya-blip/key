@props([
    'icon' => 'bi-star',
    'label' => '',
    'value' => '',
    'hint' => null,
    'color' => 'primary',
    'url' => null,
])

@php
    $bg = [
        'primary' => 'rgba(47,91,234,.1)',
        'success' => 'rgba(22,163,74,.1)',
        'danger' => 'rgba(220,38,38,.1)',
        'warning' => 'rgba(245,158,11,.15)',
        'info' => 'rgba(8,145,178,.1)',
        'secondary' => 'rgba(107,117,144,.14)',
    ][$color] ?? 'rgba(47,91,234,.1)';
@endphp

@php $tag = $url ? 'a' : 'div'; @endphp
<{{ $tag }} {{ $url ? 'href="'.$url.'"' : '' }} class="d-block text-decoration-none">
    <div class="card stat-card h-100 {{ $url ? 'text-decoration-none' : '' }} {{ $tag === 'a' ? 'text-body' : '' }}">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon" style="background: {{ $bg }}; color: var(--key-{{ $color }});">
                <i class="bi {{ $icon }}"></i>
            </div>
            <div>
                <div class="stat-value">{{ $value }}</div>
                <div class="small text-muted">{{ $label }}</div>
                @if ($hint)<div class="small text-muted">{{ $hint }}</div>@endif
            </div>
        </div>
    </div>
</{{ $tag }}>
