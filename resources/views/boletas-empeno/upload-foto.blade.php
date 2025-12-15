<x-app-layout>
    <x-slot name="header">
    </x-slot>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Subir Foto de Prenda</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Boleta de Empeño: <span class="font-semibold">{{ $boletaEmpeno->numero_contrato }}</span>
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('boletas-empeno.show', $boletaEmpeno) }}"
               class="inline-flex items-center justify-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-sm transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver a la Boleta
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Información de la Boleta -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información de la Boleta</h3>

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Número de Contrato:</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $boletaEmpeno->numero_contrato }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Cliente:</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $boletaEmpeno->cliente->razon_social ?: trim($boletaEmpeno->cliente->nombres . ' ' . $boletaEmpeno->cliente->apellidos) }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Monto Préstamo:</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($boletaEmpeno->monto_prestamo, 2) }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Fecha Creación:</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $boletaEmpeno->created_at->format('d/m/Y H:i') }}</span>
                </div>

                @if($boletaEmpeno->productos->count() > 0)
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Productos:</span>
                        <div class="mt-2 space-y-1">
                            @foreach($boletaEmpeno->productos as $producto)
                                <div class="text-sm text-gray-900 dark:text-white">
                                    • {{ $producto->producto->nombre ?? 'Producto eliminado' }}
                                    <span class="text-gray-500">({{ number_format($producto->cantidad, 2) }})</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Formulario de Subida de Foto -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Foto de la Prenda</h3>

            <!-- Foto Actual -->
            @if($boletaEmpeno->foto_prenda)
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Foto Actual</h4>
                    <div class="relative">
                        <img src="{{ $boletaEmpeno->foto_prenda_url }}"
                             alt="Foto de la prenda"
                             class="w-full h-48 object-cover rounded-lg border border-gray-300 dark:border-gray-600">
                        <button type="button" id="delete-current-photo"
                                class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white rounded-full p-2 shadow-lg transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1-1H8a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Formulario de Subida -->
            <form id="upload-photo-form" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="foto_prenda" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $boletaEmpeno->foto_prenda ? 'Cambiar Foto' : 'Seleccionar Foto' }}
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500 transition-colors duration-200"
                         id="drop-zone">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                <label for="foto_prenda" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                    <span>Seleccionar archivo</span>
                                    <input id="foto_prenda" name="foto_prenda" type="file" class="sr-only" accept="image/*" required>
                                </label>
                                <p class="pl-1">o arrastra y suelta</p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                PNG, JPG, GIF hasta 5MB
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div id="preview-container" class="mb-4 hidden">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Vista Previa</h4>
                    <div class="relative">
                        <img id="preview-image" src="" alt="Vista previa" class="w-full h-48 object-cover rounded-lg border border-gray-300 dark:border-gray-600">
                        <button type="button" id="remove-preview"
                                class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white rounded-full p-2 shadow-lg transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex gap-3">
                    <button type="submit" id="upload-btn"
                            class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow-sm transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span id="upload-text">Subir Foto</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('upload-photo-form');
            const fileInput = document.getElementById('foto_prenda');
            const dropZone = document.getElementById('drop-zone');
            const previewContainer = document.getElementById('preview-container');
            const previewImage = document.getElementById('preview-image');
            const removePreviewBtn = document.getElementById('remove-preview');
            const uploadBtn = document.getElementById('upload-btn');
            const uploadText = document.getElementById('upload-text');
            const deleteCurrentBtn = document.getElementById('delete-current-photo');

            // Drag and drop functionality
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                dropZone.classList.add('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/20');
            }

            function unhighlight() {
                dropZone.classList.remove('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/20');
            }

            dropZone.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                fileInput.files = files;
                handleFiles(files);
            }

            // File input change
            fileInput.addEventListener('change', function(e) {
                handleFiles(e.target.files);
            });

            function handleFiles(files) {
                if (files.length > 0) {
                    const file = files[0];
                    if (file.type.startsWith('image/')) {
                        showPreview(file);
                    }
                }
            }

            function showPreview(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }

            // Remove preview
            removePreviewBtn.addEventListener('click', function() {
                previewContainer.classList.add('hidden');
                fileInput.value = '';
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!fileInput.files[0]) {
                    if (window.Toast) {
                        window.Toast.fire({
                            icon: 'warning',
                            title: 'Por favor selecciona una imagen'
                        });
                    }
                    return;
                }

                const formData = new FormData();
                formData.append('foto_prenda', fileInput.files[0]);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                // Disable button and show loading
                uploadBtn.disabled = true;
                uploadText.textContent = 'Subiendo...';

                fetch('{{ route("boletas-empeno.store-foto", $boletaEmpeno) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (window.Toast) {
                            window.Toast.fire({
                                icon: 'success',
                                title: data.message
                            });
                        }

                        // Redirect to show page
                        setTimeout(() => {
                            window.location.href = '{{ route("boletas-empeno.show", $boletaEmpeno) }}';
                        }, 1500);
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (window.Toast) {
                        window.Toast.fire({
                            icon: 'error',
                            title: error.message || 'Error al subir la foto'
                        });
                    }
                })
                .finally(() => {
                    uploadBtn.disabled = false;
                    uploadText.textContent = 'Subir Foto';
                });
            });

            // Delete current photo
            if (deleteCurrentBtn) {
                deleteCurrentBtn.addEventListener('click', function() {
                    if (!confirm('¿Estás seguro de que deseas eliminar la foto actual?')) {
                        return;
                    }

                    fetch('{{ route("boletas-empeno.delete-foto", $boletaEmpeno) }}', {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (window.Toast) {
                                window.Toast.fire({
                                    icon: 'success',
                                    title: data.message
                                });
                            }

                            // Reload page to show updated state
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            throw new Error(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (window.Toast) {
                            window.Toast.fire({
                                icon: 'error',
                                title: error.message || 'Error al eliminar la foto'
                            });
                        }
                    });
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
