@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null])

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        {{ $attributes->class('w-full rounded-chip border border-line bg-surface px-4 py-2.5 outline-none focus:border-accent') }}>
    @error($name) <p class="mt-1 text-xs text-sale">{{ $message }}</p> @enderror
</div>
