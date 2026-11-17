@props([
    'name' => '',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => '',
    'required' => false,
    'help' => null,
    'money' => false,
])

<div class="mb-3">
    @if ($label)
        <label class="form-label" for="fld-{{ $name }}">
            {{ $label }} @if ($required)<span class="text-danger">*</span>@endif
        </label>
    @endif
    <input type="{{ $type }}"
           id="fld-{{ $name }}"
           name="{{ $name }}"
           @if ($value !== null) value="{{ $value }}" @endif
           placeholder="{{ $placeholder }}"
           class="form-control @if ($money) text-start @endif"
           @if ($required) required @endif
           @if ($money) data-money @endif>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @error($name)
        <div class="small text-danger mt-1">{{ $message }}</div>
    @enderror
</div>
