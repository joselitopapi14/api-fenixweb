@props(['position' => 'top-right'])

@php
    $positionClasses = [
        'top-right' => 'fixed top-0 right-0 z-50 w-full sm:w-96 p-4 space-y-4 pointer-events-none',
        'top-left' => 'fixed top-0 left-0 z-50 w-full sm:w-96 p-4 space-y-4 pointer-events-none',
        'bottom-right' => 'fixed bottom-0 right-0 z-50 w-full sm:w-96 p-4 space-y-4 pointer-events-none',
        'bottom-left' => 'fixed bottom-0 left-0 z-50 w-full sm:w-96 p-4 space-y-4 pointer-events-none',
        'top-center' => 'fixed top-0 left-1/2 transform -translate-x-1/2 z-50 w-full sm:w-96 p-4 space-y-4 pointer-events-none',
        'bottom-center' => 'fixed bottom-0 left-1/2 transform -translate-x-1/2 z-50 w-full sm:w-96 p-4 space-y-4 pointer-events-none',
    ];
@endphp

<div
    id="toast-container"
    x-data="toastContainer()"
    @toast-show.window="showToast($event.detail)"
    class="{{ $positionClasses[$position] }}"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.show"
            x-transition:enter="transform ease-out duration-500 transition"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2 scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95 translate-x-2"
            @mouseenter="pauseToast(toast.id)"
            @mouseleave="resumeToast(toast.id)"
            class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 transform-gpu relative"
        >
            <!-- Progress Bar -->
            <div x-show="toast.duration > 0" class="absolute bottom-0 left-0 h-1 bg-gray-200 dark:bg-gray-700 w-full">
                <div
                    class="h-full transition-all duration-75 ease-linear"
                    :class="{
                        'bg-green-500': toast.type === 'success',
                        'bg-red-500': toast.type === 'error',
                        'bg-yellow-500': toast.type === 'warning',
                        'bg-primary-500': toast.type === 'info'
                    }"
                    :style="`width: ${toast.progress || 0}%`"
                ></div>
            </div>
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <!-- Success Icon -->
                        <template x-if="toast.type === 'success'">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </template>

                        <!-- Error Icon -->
                        <template x-if="toast.type === 'error'">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        </template>

                        <!-- Warning Icon -->
                        <template x-if="toast.type === 'warning'">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/30">
                                <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                        </template>

                        <!-- Info Icon -->
                        <template x-if="toast.type === 'info'">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30">
                                <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                            </div>
                        </template>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p x-show="toast.title" x-text="toast.title" class="text-sm font-medium text-gray-900 dark:text-white"></p>
                        <div x-html="toast.message" class="mt-1 text-sm text-gray-500 dark:text-gray-300" :class="{ 'mt-0': !toast.title }"></div>
                    </div>
                    <div class="ml-4 flex flex-shrink-0">
                        <button @click="hideToast(toast.id)" class="inline-flex rounded-md bg-white dark:bg-gray-800 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
