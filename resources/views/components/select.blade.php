@props([
    'name' => '',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'انتخاب کنید',
    'required' => false,
    'help' => null,
])

<div class="mb-3">
    @if ($label)
        <label class="form-label" for="fld-{{ $name }}">
            {{ $label }} @if ($required)<span class="text-danger">*</span>@endif
        </label>
    @endif
    <select id="fld-{{ $name }}" name="{{ $name }}"
            class="form-select"
            @if ($required) required @endif>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $key => $text)
            <option value="{{ $key }}"
                @if ((string) $value !== '' && (string) $value === (string) $key) selected @endif>
                {{ $text }}
            </option>
        @endforeach
    </select>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @error($name)
        <div class="small text-danger mt-1">{{ $message }}</div>
    @enderror
</div>
