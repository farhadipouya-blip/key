@props([
    'name' => '',
    'label' => null,
    'checked' => false,
    'help' => null,
])

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" role="switch"
           id="fld-{{ $name }}" name="{{ $name }}" value="1"
           @if ($checked) checked @endif>
    @if ($label)
        <label class="form-check-label" for="fld-{{ $name }}">{{ $label }}</label>
    @endif
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
