@props([
    'trigger' => '',
    'title' => '¿Estás seguro?',
    'message' => 'Esta acción no se puede deshacer.',
    'confirmText' => 'Confirmar',
    'cancelText' => 'Cancelar',
    'confirmStyle' => 'danger' // danger, primary, secondary
])

<div x-data="confirmDialog({
    title: '{{ $title }}',
    message: '{{ $message }}',
    confirmText: '{{ $confirmText }}',
    cancelText: '{{ $cancelText }}',
    confirmStyle: '{{ $confirmStyle }}'
})"
@open-confirm-dialog.window="openDialog($event.detail)">
    <!-- Backdrop -->
    <div x-show="show"
         x-transition.opacity.duration.300ms
         class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50"
         style="display: none;"
         @click="cancel()">
    </div>

    <!-- Dialog -->
    <div x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20 sm:mx-0 sm:h-10 sm:w-10" x-show="currentConfirmStyle === 'danger'">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 sm:mx-0 sm:h-10 sm:w-10" x-show="currentConfirmStyle === 'primary'">
                        <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                        <h3 x-text="currentTitle" class="text-base font-semibold leading-6 text-gray-900 dark:text-white"></h3>
                        <div class="mt-2">
                            <p x-text="currentMessage" class="text-sm text-gray-500 dark:text-gray-300"></p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                    <button @click="confirm()"
                            type="button"
                            class="inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm sm:w-auto"
                            :class="{
                                'bg-red-600 hover:bg-red-500 dark:bg-red-600 dark:hover:bg-red-700': currentConfirmStyle === 'danger',
                                'bg-primary-600 hover:bg-primary-500 dark:bg-primary-600 dark:hover:bg-primary-700': currentConfirmStyle === 'primary',
                                'bg-gray-600 hover:bg-gray-500 dark:bg-gray-600 dark:hover:bg-gray-700': currentConfirmStyle === 'secondary'
                            }"
                            x-text="currentConfirmText">
                    </button>
                    <button @click="cancel()"
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto"
                            x-text="currentCancelText">
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
