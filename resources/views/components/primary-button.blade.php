<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2.5 bg-primary-500 hover:bg-primary-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 active:bg-primary-700 transition-all duration-150 ease-in-out']) }}>
    {{ $slot }}
</button>
