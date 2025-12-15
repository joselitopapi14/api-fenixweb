@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-gray-900 dark:text-gray-100 mb-2']) }}>
    {{ $value ?? $slot }}
</label>
