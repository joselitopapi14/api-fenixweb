@props(['size' => 'md', 'position' => 'inline'])

@php
$sizeClasses = match ($size) {
    'sm' => 'h-8 w-8 p-1.5',
    'lg' => 'h-12 w-12 p-3',
    default => 'h-10 w-10 p-2.5',
};

$positionClasses = match ($position) {
    'fixed' => 'fixed bottom-6 right-6 z-50 shadow-lg',
    'sticky' => 'sticky top-4 z-40',
    default => '',
};
@endphp

<button
    x-data="themeToggle"
    @click="toggle()"
    {{ $attributes->merge(['class' => "theme-toggle relative inline-flex items-center justify-center rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all duration-200 ease-in-out $sizeClasses $positionClasses"]) }}
    :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    type="button"
>
    <!-- Light mode icon (sun) -->
    <svg x-show="!isDark"
         x-transition:enter="transition ease-in-out duration-300"
         x-transition:enter-start="opacity-0 rotate-90 scale-75"
         x-transition:enter-end="opacity-100 rotate-0 scale-100"
         x-transition:leave="transition ease-in-out duration-200"
         x-transition:leave-start="opacity-100 rotate-0 scale-100"
         x-transition:leave-end="opacity-0 -rotate-90 scale-75"
         class="theme-toggle-icon absolute inset-0 h-full w-full p-2"
         fill="none"
         viewBox="0 0 30 30"
         stroke-width="1.5"
         stroke="currentColor"
         aria-hidden="true">
         <path d="M 14.984375 0.98632812 A 1.0001 1.0001 0 0 0 14 2 L 14 5 A 1.0001 1.0001 0 1 0 16 5 L 16 2 A 1.0001 1.0001 0 0 0 14.984375 0.98632812 z M 5.796875 4.7988281 A 1.0001 1.0001 0 0 0 5.1015625 6.515625 L 7.2226562 8.6367188 A 1.0001 1.0001 0 1 0 8.6367188 7.2226562 L 6.515625 5.1015625 A 1.0001 1.0001 0 0 0 5.796875 4.7988281 z M 24.171875 4.7988281 A 1.0001 1.0001 0 0 0 23.484375 5.1015625 L 21.363281 7.2226562 A 1.0001 1.0001 0 1 0 22.777344 8.6367188 L 24.898438 6.515625 A 1.0001 1.0001 0 0 0 24.171875 4.7988281 z M 15 8 A 7 7 0 0 0 8 15 A 7 7 0 0 0 15 22 A 7 7 0 0 0 22 15 A 7 7 0 0 0 15 8 z M 2 14 A 1.0001 1.0001 0 1 0 2 16 L 5 16 A 1.0001 1.0001 0 1 0 5 14 L 2 14 z M 25 14 A 1.0001 1.0001 0 1 0 25 16 L 28 16 A 1.0001 1.0001 0 1 0 28 14 L 25 14 z M 7.9101562 21.060547 A 1.0001 1.0001 0 0 0 7.2226562 21.363281 L 5.1015625 23.484375 A 1.0001 1.0001 0 1 0 6.515625 24.898438 L 8.6367188 22.777344 A 1.0001 1.0001 0 0 0 7.9101562 21.060547 z M 22.060547 21.060547 A 1.0001 1.0001 0 0 0 21.363281 22.777344 L 23.484375 24.898438 A 1.0001 1.0001 0 1 0 24.898438 23.484375 L 22.777344 21.363281 A 1.0001 1.0001 0 0 0 22.060547 21.060547 z M 14.984375 23.986328 A 1.0001 1.0001 0 0 0 14 25 L 14 28 A 1.0001 1.0001 0 1 0 16 28 L 16 25 A 1.0001 1.0001 0 0 0 14.984375 23.986328 z"></path>
    </svg>


    <!-- Dark mode icon (moon) -->
    <svg x-show="isDark"
         x-transition:enter="transition ease-in-out duration-300"
         x-transition:enter-start="opacity-0 rotate-90 scale-75"
         x-transition:enter-end="opacity-100 rotate-0 scale-100"
         x-transition:leave="transition ease-in-out duration-200"
         x-transition:leave-start="opacity-100 rotate-0 scale-100"
         x-transition:leave-end="opacity-0 -rotate-90 scale-75"
         class="theme-toggle-icon absolute inset-0 h-full w-full p-2"
         fill="none"
         viewBox="0 0 24 24"
         stroke-width="1.5"
         stroke="currentColor"
         aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
    </svg>
</button>
