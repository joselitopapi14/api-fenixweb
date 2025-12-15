<!-- Nombre de la Comuna -->
<div>
    <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Nombre de la Comuna <span class="text-red-500">*</span>
    </label>
    <input type="text"
           name="nombre"
           id="nombre"
           value="{{ old('nombre', $comuna->nombre ?? '') }}"
           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
           placeholder="Ingrese el nombre de la comuna"
           required>
    @error('nombre')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<!-- Departamento -->
<div>
    <label for="departamento_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Departamento <span class="text-red-500">*</span>
    </label>
    <select name="departamento_id"
            id="departamento_id"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
            onchange="loadMunicipios()"
            required>
        <option value="">Seleccione un departamento</option>
        @foreach($departamentos as $departamento)
            <option value="{{ $departamento->id }}"
                    data-pais="{{ $departamento->pais->name ?? 'N/A' }}"
                    {{ old('departamento_id', $comuna->municipio->departamento_id ?? '') == $departamento->id ? 'selected' : '' }}>
                {{ $departamento->name }} - {{ $departamento->pais->name ?? 'N/A' }}
            </option>
        @endforeach
    </select>
    @error('departamento_id')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<!-- Municipio -->
<div>
    <label for="municipio_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Municipio <span class="text-red-500">*</span>
    </label>
    <select name="municipio_id"
            id="municipio_id"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
            required>
        <option value="">Seleccione primero un departamento</option>
        @if(isset($municipios))
            @foreach($municipios as $municipio)
                <option value="{{ $municipio->id }}"
                        {{ old('municipio_id', $comuna->municipio_id ?? '') == $municipio->id ? 'selected' : '' }}>
                    {{ $municipio->name }}
                </option>
            @endforeach
        @endif
    </select>
    @error('municipio_id')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<script>
    // Guardar el municipio seleccionado inicialmente
    const initialMunicipioId = '{{ old('municipio_id', $comuna->municipio_id ?? '') }}';

    async function loadMunicipios() {
        const departamentoSelect = document.getElementById('departamento_id');
        const municipioSelect = document.getElementById('municipio_id');
        const departamentoId = departamentoSelect.value;

        // Limpiar municipios
        municipioSelect.innerHTML = '<option value="">Cargando municipios...</option>';
        municipioSelect.disabled = true;

        if (!departamentoId) {
            municipioSelect.innerHTML = '<option value="">Seleccione primero un departamento</option>';
            return;
        }

        try {
            const response = await fetch(`{{ route('api.municipios.by-departamento', '') }}/${departamentoId}`);

            if (!response.ok) {
                throw new Error('Error al cargar municipios');
            }

            const municipios = await response.json();

            // Limpiar y agregar nueva opción por defecto
            municipioSelect.innerHTML = '<option value="">Seleccione un municipio</option>';

            // Agregar municipios
            municipios.forEach(municipio => {
                const option = document.createElement('option');
                option.value = municipio.id;
                option.textContent = municipio.name;

                // Seleccionar el municipio inicial si coincide
                if (municipio.id == initialMunicipioId) {
                    option.selected = true;
                }

                municipioSelect.appendChild(option);
            });

            municipioSelect.disabled = false;

        } catch (error) {
            console.error('Error:', error);
            municipioSelect.innerHTML = '<option value="">Error al cargar municipios</option>';

            // Mostrar notificación de error
            if (window.showToast) {
                window.showToast({
                    title: 'Error',
                    message: 'No se pudieron cargar los municipios. Inténtelo de nuevo.',
                    status: 'error'
                });
            }
        }
    }

    // Inicializar al cargar la página si hay un departamento preseleccionado
    document.addEventListener('DOMContentLoaded', function() {
        const departamentoSelect = document.getElementById('departamento_id');
        if (departamentoSelect.value) {
            loadMunicipios();
        }
    });
</script>
