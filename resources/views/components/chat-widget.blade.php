<div x-data="chatWidget()" @chat-opened.window="onChatOpened()">
    <!-- Header del chat -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-primary-500 text-white rounded-t-lg">
        <div class="flex items-center space-x-2">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-6 h-6 rounded">
            <div>
                <h3 class="font-semibold text-sm">Asistente Fenix Gold</h3>
                <p class="text-xs text-primary-100" x-text="chatTitle" x-show="currentChatId"></p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('chat.history') }}"
               class="text-white hover:text-gray-200 focus:outline-none p-1 rounded"
               title="Ver historial de chats">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </a>
            <button @click="closeChat()" class="text-white hover:text-gray-200 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Área de mensajes -->
    <div class="flex-1 overflow-y-auto p-4 h-64" x-ref="messagesContainer">
        <div class="space-y-3">
            <!-- Mensaje de bienvenida -->
            <div class="flex items-start space-x-2">
                <div class="w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <img src="{{ asset('images/logo.png') }}" alt="Bot" class="w-4 h-4 rounded">
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-2 max-w-xs">
                    <p class="text-sm text-gray-800 dark:text-gray-200">¡Hola <strong>{{ Auth::user()->name }}</strong>! Soy tu asistente virtual de Fenix Gold. ¿En qué puedo ayudarte hoy?</p>
                    <span class="text-xs opacity-70">Ahora</span>
                </div>
            </div>

            <!-- Mensajes dinámicos -->
            <template x-for="message in messages" :key="message.id">
                <div class="flex items-start space-x-2" :class="message.isUser ? 'justify-end' : ''">
                    <div x-show="!message.isUser" class="w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <img src="{{ asset('images/logo.png') }}" alt="Bot" class="w-4 h-4 rounded">
                    </div>
                    <div class="rounded-lg p-2 max-w-xs markdown-content" :class="message.isUser ? 'bg-primary-500 text-white ml-auto' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'">
                        <p class="text-sm whitespace-pre-wrap" x-html="message.isUser ? escapeHtml(message.text).replace(/\n/g, '<br>') : formatMessage(message.text)"></p>
                        <span class="text-xs opacity-70" x-text="message.time"></span>
                    </div>
                    <div x-show="message.isUser" class="w-6 h-6 bg-gray-400 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-xs font-semibold" x-text="userInitial"></span>
                    </div>
                </div>
            </template>

            <!-- Indicador de escritura -->
            <div x-show="isTyping" class="flex items-start space-x-2">
                <div class="w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <img src="{{ asset('images/logo.png') }}" alt="Bot" class="w-4 h-4 rounded">
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-2">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Input de mensaje -->
    <div class="border-t border-gray-200 dark:border-gray-700 p-4">
        <form @submit.prevent="sendMessage()" class="flex space-x-2">
            <input
                x-model="currentMessage"
                type="text"
                placeholder="Escribe tu mensaje..."
                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                :disabled="isLoading"
                x-ref="messageInput"
                @keydown.enter.prevent="sendMessage()"
                maxlength="2000"
            >
            <button
                type="submit"
                class="px-3 py-2 bg-primary-500 text-white rounded-md hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                :disabled="isLoading || !currentMessage.trim()"
                title="Enviar mensaje"
            >
                <svg x-show="!isLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
                <svg x-show="isLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </form>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-show="currentMessage.length > 0">
            <span x-text="currentMessage.length"></span>/2000 caracteres
        </div>
    </div>
</div>

<script>
function chatWidget() {
    return {
        messages: [],
        currentMessage: '',
        isLoading: false,
        isTyping: false,
        userInitial: '{{ substr(Auth::user()->name, 0, 1) }}',
        maxRetries: 3,
        retryCount: 0,
        currentChatId: null,
        chatTitle: 'Nueva conversación',

        onChatOpened() {
            this.$nextTick(() => {
                this.focusInput();
                this.scrollToBottom();
            });
        },

        closeChat() {
            // Disparar evento para cerrar el chat desde el parent
            this.$dispatch('close-chat');
        },

        focusInput() {
            try {
                if (this.$refs.messageInput && typeof this.$refs.messageInput.focus === 'function') {
                    this.$refs.messageInput.focus();
                }
            } catch (e) {
                console.warn('No se pudo hacer focus en el input del chat:', e);
            }
        },

        formatMessage(text) {
            if (!text) return '';

            // Escapar HTML básico para seguridad
            let formatted = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#x27;');

            // Headers (###, ##, #)
            formatted = formatted.replace(/^### (.*$)/gim, '<h3 class="text-sm font-semibold mt-2 mb-1">$1</h3>');
            formatted = formatted.replace(/^## (.*$)/gim, '<h2 class="text-base font-semibold mt-2 mb-1">$1</h2>');
            formatted = formatted.replace(/^# (.*$)/gim, '<h1 class="text-lg font-bold mt-2 mb-1">$1</h1>');

            // Negrita (**texto** o __texto__)
            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold">$1</strong>');
            formatted = formatted.replace(/__(.*?)__/g, '<strong class="font-bold">$1</strong>');

            // Cursiva (*texto* o _texto_)
            formatted = formatted.replace(/\*(.*?)\*/g, '<em class="italic">$1</em>');
            formatted = formatted.replace(/_(.*?)_/g, '<em class="italic">$1</em>');

            // Código inline (`código`)
            formatted = formatted.replace(/`(.*?)`/g, '<code class="bg-gray-200 dark:bg-gray-600 px-1 py-0.5 rounded text-xs font-mono">$1</code>');

            // Enlaces [texto](url)
            formatted = formatted.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-blue-600 dark:text-blue-400 hover:underline text-xs" target="_blank" rel="noopener noreferrer">$1</a>');

            // Bloques de código con ```
            formatted = formatted.replace(/```([\s\S]*?)```/g, '<pre class="bg-gray-100 dark:bg-gray-800 p-2 rounded text-xs my-1 overflow-x-auto"><code class="font-mono">$1</code></pre>');

            // Listas con viñetas (- elemento)
            formatted = formatted.replace(/^- (.+)$/gim, '<li class="ml-4 text-sm">• $1</li>');
            // Agrupar elementos de lista consecutivos
            formatted = formatted.replace(/(<li[^>]*>.*<\/li>\s*)+/g, function(match) {
                return '<ul class="list-none space-y-1 my-1">' + match + '</ul>';
            });

            // Listas numeradas (1. elemento)
            formatted = formatted.replace(/^\d+\. (.+)$/gim, '<li class="ml-4 text-sm">$1</li>');

            // Líneas horizontales (--- o ***)
            formatted = formatted.replace(/^(---|\*\*\*)$/gim, '<hr class="border-t border-gray-300 dark:border-gray-600 my-2">');

            // Saltos de línea dobles a párrafos
            formatted = formatted.replace(/\n\n/g, '</p><p class="mb-1">');
            formatted = '<p class="mb-1">' + formatted + '</p>';

            // Saltos de línea simples a <br>
            formatted = formatted.replace(/\n/g, '<br>');

            // Limpiar párrafos vacíos
            formatted = formatted.replace(/<p class="mb-1"><\/p>/g, '');

            return formatted;
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        async sendMessage() {
            if (!this.currentMessage.trim() || this.isLoading) return;

            const messageText = this.currentMessage.trim();
            const messageId = Date.now();

            // Agregar mensaje del usuario
            this.messages.push({
                id: messageId,
                text: messageText,
                isUser: true,
                time: this.formatTime(new Date())
            });

            this.currentMessage = '';
            this.isLoading = true;
            this.isTyping = true;
            this.retryCount = 0;
            this.scrollToBottom();

            await this.attemptSendMessage(messageText);
        },

        async attemptSendMessage(messageText) {
            try {
                const response = await fetch('{{ route("chat.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        message: messageText,
                        chat_id: this.currentChatId
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                this.isTyping = false;
                this.retryCount = 0;

                if (data.success) {
                    // Actualizar chat ID si es nuevo
                    if (data.chat_id && !this.currentChatId) {
                        this.currentChatId = data.chat_id;
                    }

                    // Actualizar título del chat si cambió
                    if (data.chat_title && data.chat_title !== this.chatTitle) {
                        this.chatTitle = data.chat_title;
                    }

                    this.messages.push({
                        id: data.message_id || Date.now(),
                        text: data.message,
                        isUser: false,
                        time: this.formatTime(new Date())
                    });
                } else {
                    this.addErrorMessage(data.error || 'Error desconocido al procesar tu mensaje.');
                }
            } catch (error) {
                this.isTyping = false;
                console.error('Chat error:', error);

                if (this.retryCount < this.maxRetries) {
                    this.retryCount++;
                    this.addRetryMessage(messageText);
                } else {
                    this.addErrorMessage('Error de conexión. Por favor, verifica tu conexión a internet e inténtalo de nuevo.');
                }
            } finally {
                this.isLoading = false;
                this.isTyping = false;
                this.scrollToBottom();
            }
        },

        addErrorMessage(errorText) {
            this.messages.push({
                id: Date.now(),
                text: `❌ ${errorText}`,
                isUser: false,
                time: this.formatTime(new Date())
            });
        },

        addRetryMessage(originalMessage) {
            this.messages.push({
                id: Date.now(),
                text: `⚠️ Error de conexión. Reintentando... (${this.retryCount}/${this.maxRetries})`,
                isUser: false,
                time: this.formatTime(new Date())
            });

            setTimeout(() => {
                this.isLoading = true;
                this.isTyping = true;
                this.attemptSendMessage(originalMessage);
            }, 1000 * this.retryCount);
        },

        formatTime(date) {
            return date.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
        },

        scrollToBottom() {
            this.$nextTick(() => {
                try {
                    if (this.$refs.messagesContainer && this.$refs.messagesContainer.scrollTop !== undefined) {
                        this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
                    }
                } catch (e) {
                    console.warn('No se pudo hacer scroll al final del chat:', e);
                }
            });
        },

        // Método para limpiar chat (futuro)
        clearChat() {
            this.messages = [];
        }
    }
}
</script>

<!-- Estilos para contenido Markdown en el chat widget -->
<style>
/* Estilos específicos para el chat widget */
.markdown-content p {
    margin-bottom: 0.25rem;
}

.markdown-content p:last-child {
    margin-bottom: 0;
}

.markdown-content h1,
.markdown-content h2,
.markdown-content h3 {
    margin-top: 0.5rem;
    margin-bottom: 0.25rem;
}

.markdown-content h1:first-child,
.markdown-content h2:first-child,
.markdown-content h3:first-child {
    margin-top: 0;
}

.markdown-content ul {
    margin: 0.25rem 0;
}

.markdown-content li {
    margin: 0.125rem 0;
    line-height: 1.4;
}

.markdown-content pre {
    margin: 0.5rem 0;
    font-size: 0.75rem;
}

.markdown-content pre:first-child {
    margin-top: 0;
}

.markdown-content pre:last-child {
    margin-bottom: 0;
}

.markdown-content code {
    font-size: 0.75rem;
}

.markdown-content hr {
    margin: 0.5rem 0;
}

.markdown-content a {
    word-break: break-word;
}

/* Ajustes específicos para el chat widget */
.markdown-content strong {
    font-weight: 600;
}

.markdown-content em {
    font-style: italic;
}

/* Mejores colores para enlaces en el chat widget */
.bg-gray-100 .markdown-content a,
.dark .bg-gray-700 .markdown-content a {
    color: #2563EB;
}

.dark .bg-gray-700 .markdown-content a {
    color: #60A5FA;
}

/* Mejor contraste para código inline en el widget */
.bg-gray-100 .markdown-content code {
    background-color: #E5E7EB;
    color: #374151;
}

.dark .bg-gray-700 .markdown-content code {
    background-color: #4B5563;
    color: #F9FAFB;
}
</style>
