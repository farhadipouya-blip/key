@props([
    'title' => null,
    'icon' => null,
    'actions' => null,
    'bodyClass' => '',
])

<div {{ $attributes->merge(['class' => 'card mb-3']) }}>
    @if ($title || $actions)
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 d-flex align-items-center gap-2">
                @if ($icon)<i class="bi {{ $icon }} text-primary"></i>@endif
                {{ $title }}
            </h6>
            <div class="d-flex gap-2">
                {!! $actions !!}
            </div>
        </div>
    @endif
    <div class="card-body {{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>
