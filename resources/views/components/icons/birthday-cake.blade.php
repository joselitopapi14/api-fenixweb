@props(['class' => 'h-6 w-6'])

<svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <!-- Torta base -->
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 18h18v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3z"></path>
    <!-- Torta medio -->
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 15h16v3H4v-3z"></path>
    <!-- Velas -->
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 15V8m0 0a1 1 0 011-1h0a1 1 0 011 1v7m-2-7V5m8 10V8m0 0a1 1 0 011-1h0a1 1 0 011 1v7m-2-7V5"></path>
    <!-- Llamas -->
    <ellipse cx="8" cy="4" rx="0.5" ry="1" stroke-width="1.5"></ellipse>
    <ellipse cx="16" cy="4" rx="0.5" ry="1" stroke-width="1.5"></ellipse>
</svg>
