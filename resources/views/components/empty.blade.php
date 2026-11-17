@props([
    'icon' => 'bi-inbox',
    'title' => 'موردی یافت نشد',
    'message' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

<div class="text-center py-5">
    <i class="bi {{ $icon }} text-muted" style="font-size:2.6rem"></i>
    <h6 class="mt-3 mb-1">{{ $title }}</h6>
    @if ($message)
        <p class="text-muted small mb-3">{{ $message }}</p>
    @endif
    @if ($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn btn-sm btn-primary">{{ $actionLabel }}</a>
    @endif
</div>
