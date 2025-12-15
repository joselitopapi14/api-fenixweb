<!-- Chat System -->
<div x-data="chatSystem()" @close-chat.window="closeChat()" class="fixed bottom-4 right-4 z-40">
    <!-- Botón flotante del chat -->
    <button
        @click="toggleChat()"
        class="w-14 h-14 bg-primary-500 hover:bg-primary-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center focus:outline-none focus:ring-4 focus:ring-primary-500/30 group"
        :class="{ 'rotate-45': isOpen }"
    >
        <div class="relative">
            <!-- Logo cuando está cerrado -->
            <img
                src="{{ asset('images/logo.png') }}"
                alt="Chat"
                class="w-8 h-8 transition-opacity duration-200"
                :class="{ 'opacity-0': isOpen, 'opacity-100': !isOpen }"
            >
            <!-- Ícono X cuando está abierto -->
            <svg
                class="w-6 h-6 absolute inset-0 transition-opacity duration-200"
                :class="{ 'opacity-100': isOpen, 'opacity-0': !isOpen }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
    </button>

    <!-- Indicador de notificación (opcional para futuras implementaciones) -->
    <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center" style="display: none;">
        !
    </div>

    <!-- Tooltip -->
    <div
        class="absolute right-16 bottom-1/2 transform translate-y-1/2 bg-gray-900 text-white px-3 py-1 rounded-lg text-sm whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none"
        :class="{ 'hidden': isOpen }"
    >
        Asistente Fenix Gold
        <div class="absolute left-full top-1/2 transform -translate-y-1/2 border-4 border-transparent border-l-gray-900"></div>
    </div>

    <!-- Widget del chat -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-2"
        class="absolute bottom-16 right-0 w-80 h-96 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700"
        style="display: none;"
    >
        <x-chat-widget />
    </div>
</div>

<script>
function chatSystem() {
    return {
        isOpen: false,

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                // Disparar evento para que el widget se inicialice
                this.$nextTick(() => {
                    this.$dispatch('chat-opened');
                });
            }
        },

        closeChat() {
            this.isOpen = false;
        }
    }
}
</script>
