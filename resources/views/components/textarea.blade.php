@props([
    'name' => '',
    'label' => null,
    'value' => null,
    'rows' => 4,
    'placeholder' => '',
    'required' => false,
    'help' => null,
])

<div class="mb-3">
    @if ($label)
        <label class="form-label" for="fld-{{ $name }}">
            {{ $label }} @if ($required)<span class="text-danger">*</span>@endif
        </label>
    @endif
    <textarea id="fld-{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
              placeholder="{{ $placeholder }}"
              class="form-control"
              @if ($required) required @endif>{{ $value }}</textarea>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @error($name)
        <div class="small text-danger mt-1">{{ $message }}</div>
    @enderror
</div>
