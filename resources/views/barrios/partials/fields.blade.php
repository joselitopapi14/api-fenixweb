<!-- Nombre del Barrio -->
<div>
    <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Nombre del Barrio <span class="text-red-500">*</span>
    </label>
    <input type="text"
           name="nombre"
           id="nombre"
           value="{{ old('nombre', $barrio->nombre ?? '') }}"
           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
           placeholder="Ingrese el nombre del barrio"
           required>
    @error('nombre')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<!-- País (Solo informativo) -->
<div>
    <label for="pais_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        País <span class="text-red-500">*</span>
    </label>
    <select name="pais_id"
            id="pais_id"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
            onchange="loadDepartamentos()"
            required>
        <option value="">Seleccione un país</option>
        @foreach($paises as $pais)
            <option value="{{ $pais->id }}"
                    {{ old('pais_id', $barrio->comuna->municipio->departamento->pais_id ?? '') == $pais->id ? 'selected' : '' }}>
                {{ $pais->name }}
            </option>
        @endforeach
    </select>
    @error('pais_id')
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
        <option value="">Seleccione primero un país</option>
        @if(isset($departamentos))
            @foreach($departamentos as $departamento)
                <option value="{{ $departamento->id }}"
                        data-pais="{{ $departamento->pais_id }}"
                        {{ old('departamento_id', $barrio->comuna->municipio->departamento_id ?? '') == $departamento->id ? 'selected' : '' }}>
                    {{ $departamento->name }}
                </option>
            @endforeach
        @endif
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
            onchange="loadComunas()"
            required>
        <option value="">Seleccione primero un departamento</option>
        @if(isset($municipios))
            @foreach($municipios as $municipio)
                <option value="{{ $municipio->id }}"
                        {{ old('municipio_id', $barrio->comuna->municipio_id ?? '') == $municipio->id ? 'selected' : '' }}>
                    {{ $municipio->name }}
                </option>
            @endforeach
        @endif
    </select>
    @error('municipio_id')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<!-- Comuna -->
<div>
    <label for="comuna_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Comuna <span class="text-red-500">*</span>
    </label>
    <select name="comuna_id"
            id="comuna_id"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
            required>
        <option value="">Seleccione primero un municipio</option>
        @if(isset($comunas))
            @foreach($comunas as $comuna)
                <option value="{{ $comuna->id }}"
                        {{ old('comuna_id', $barrio->comuna_id ?? '') == $comuna->id ? 'selected' : '' }}>
                    {{ $comuna->nombre }}
                </option>
            @endforeach
        @endif
    </select>
    @error('comuna_id')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<script>
    // Guardar valores iniciales
    const initialPaisId = '{{ old('pais_id', $barrio->comuna->municipio->departamento->pais_id ?? '') }}';
    const initialDepartamentoId = '{{ old('departamento_id', $barrio->comuna->municipio->departamento_id ?? '') }}';
    const initialMunicipioId = '{{ old('municipio_id', $barrio->comuna->municipio_id ?? '') }}';
    const initialComunaId = '{{ old('comuna_id', $barrio->comuna_id ?? '') }}';

    async function loadDepartamentos() {
        const paisSelect = document.getElementById('pais_id');
        const departamentoSelect = document.getElementById('departamento_id');
        const municipioSelect = document.getElementById('municipio_id');
        const comunaSelect = document.getElementById('comuna_id');
        const paisId = paisSelect.value;

        // Limpiar selects dependientes
        departamentoSelect.innerHTML = '<option value="">Cargando departamentos...</option>';
        municipioSelect.innerHTML = '<option value="">Seleccione primero un departamento</option>';
        comunaSelect.innerHTML = '<option value="">Seleccione primero un municipio</option>';
        departamentoSelect.disabled = true;
        municipioSelect.disabled = true;
        comunaSelect.disabled = true;

        if (!paisId) {
            departamentoSelect.innerHTML = '<option value="">Seleccione primero un país</option>';
            return;
        }

        try {
            // Filtrar departamentos por país (ya están cargados en el blade)
            const departamentos = @json($departamentos ?? []);
            departamentoSelect.innerHTML = '<option value="">Seleccione un departamento</option>';

            departamentos.forEach(departamento => {
                if (departamento.pais_id == paisId) {
                    const option = document.createElement('option');
                    option.value = departamento.id;
                    option.textContent = departamento.name;
                    option.setAttribute('data-pais', departamento.pais_id);

                    if (departamento.id == initialDepartamentoId) {
                        option.selected = true;
                    }

                    departamentoSelect.appendChild(option);
                }
            });

            departamentoSelect.disabled = false;

            // Si hay un departamento seleccionado, cargar municipios
            if (departamentoSelect.value) {
                loadMunicipios();
            }

        } catch (error) {
            console.error('Error:', error);
            departamentoSelect.innerHTML = '<option value="">Error al cargar departamentos</option>';
        }
    }

    async function loadMunicipios() {
        const departamentoSelect = document.getElementById('departamento_id');
        const municipioSelect = document.getElementById('municipio_id');
        const comunaSelect = document.getElementById('comuna_id');
        const departamentoId = departamentoSelect.value;

        // Limpiar municipios y comunas
        municipioSelect.innerHTML = '<option value="">Cargando municipios...</option>';
        comunaSelect.innerHTML = '<option value="">Seleccione primero un municipio</option>';
        municipioSelect.disabled = true;
        comunaSelect.disabled = true;

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

            municipioSelect.innerHTML = '<option value="">Seleccione un municipio</option>';

            municipios.forEach(municipio => {
                const option = document.createElement('option');
                option.value = municipio.id;
                option.textContent = municipio.name;

                if (municipio.id == initialMunicipioId) {
                    option.selected = true;
                }

                municipioSelect.appendChild(option);
            });

            municipioSelect.disabled = false;

            // Si hay un municipio seleccionado, cargar comunas
            if (municipioSelect.value) {
                loadComunas();
            }

        } catch (error) {
            console.error('Error:', error);
            municipioSelect.innerHTML = '<option value="">Error al cargar municipios</option>';
        }
    }

    async function loadComunas() {
        const municipioSelect = document.getElementById('municipio_id');
        const comunaSelect = document.getElementById('comuna_id');
        const municipioId = municipioSelect.value;

        // Limpiar comunas
        comunaSelect.innerHTML = '<option value="">Cargando comunas...</option>';
        comunaSelect.disabled = true;

        if (!municipioId) {
            comunaSelect.innerHTML = '<option value="">Seleccione primero un municipio</option>';
            return;
        }

        try {
            const response = await fetch(`{{ route('api.comunas.by-municipio', '') }}/${municipioId}`);

            if (!response.ok) {
                throw new Error('Error al cargar comunas');
            }

            const comunas = await response.json();

            comunaSelect.innerHTML = '<option value="">Seleccione una comuna</option>';

            comunas.forEach(comuna => {
                const option = document.createElement('option');
                option.value = comuna.id;
                option.textContent = comuna.nombre;

                if (comuna.id == initialComunaId) {
                    option.selected = true;
                }

                comunaSelect.appendChild(option);
            });

            comunaSelect.disabled = false;

        } catch (error) {
            console.error('Error:', error);
            comunaSelect.innerHTML = '<option value="">Error al cargar comunas</option>';
        }
    }

    // Inicializar al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const paisSelect = document.getElementById('pais_id');
        const departamentoSelect = document.getElementById('departamento_id');
        const municipioSelect = document.getElementById('municipio_id');

        // Si hay un país preseleccionado, cargar departamentos
        if (paisSelect.value) {
            loadDepartamentos();
        }

        // Si hay un departamento preseleccionado, cargar municipios
        if (departamentoSelect.value) {
            loadMunicipios();
        }

        // Si hay un municipio preseleccionado, cargar comunas
        if (municipioSelect.value) {
            loadComunas();
        }
    });
</script>
