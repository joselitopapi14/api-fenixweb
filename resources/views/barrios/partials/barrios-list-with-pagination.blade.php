<!-- Desktop Table View -->
<div class="hidden lg:block bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Barrio
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Comuna
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Municipio
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Departamento
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        País
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Fecha
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($barrios as $barrio)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-gradient-to-r from-primary-600 to-primary-800 flex items-center justify-center text-white font-semibold text-sm">
                                    {{ substr($barrio->nombre, 0, 1) }}
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $barrio->nombre }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        ID: {{ $barrio->id }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $barrio->comuna->nombre ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $barrio->comuna->municipio->name ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $barrio->comuna->municipio->departamento->name ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $barrio->comuna->municipio->departamento->pais->name ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $barrio->created_at ? $barrio->created_at->format('d/m/Y') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('barrios.show', $barrio) }}"
                                   class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 transition-colors duration-200"
                                   title="Ver detalles">
                                    <x-icons.eye class="w-4 h-4" />
                                </a>
                                <a href="{{ route('barrios.edit', $barrio) }}"
                                   class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 transition-colors duration-200"
                                   title="Editar">
                                    <x-icons.pencil class="w-4 h-4" />
                                </a>
                                <form action="{{ route('barrios.destroy', $barrio) }}" method="POST" class="inline-block" onsubmit="return confirmDelete(event, '{{ $barrio->nombre }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
                                            title="Eliminar">
                                        <x-icons.trash class="w-4 h-4" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icons.map-pin class="w-12 h-12 text-gray-400 mb-3" />
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Sin barrios encontrados</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">No hay barrios que coincidan con los criterios de búsqueda.</p>
                                <a href="{{ route('barrios.create') }}"
                                   class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow-sm transition-colors duration-200">
                                    <x-icons.plus class="w-4 h-4 mr-2" />
                                    Crear Primer Barrio
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile Card View -->
<div class="lg:hidden space-y-4">
    @forelse($barrios as $barrio)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-primary-600 to-primary-800 flex items-center justify-center text-white font-bold text-lg">
                        {{ substr($barrio->nombre, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white truncate">
                            {{ $barrio->nombre }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            ID: {{ $barrio->id }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-4 space-y-3">
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Comuna
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $barrio->comuna->nombre ?? 'N/A' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Ubicación
                    </dt>
                    <dd class="mt-1">
                        <div class="text-sm text-gray-900 dark:text-white">
                            <div>{{ $barrio->comuna->municipio->name ?? 'N/A' }}</div>
                            <div class="text-gray-500 dark:text-gray-400">{{ $barrio->comuna->municipio->departamento->name ?? 'N/A' }}</div>
                        </div>
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        País
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $barrio->comuna->municipio->departamento->pais->name ?? 'N/A' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Fecha de Creación
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $barrio->created_at ? $barrio->created_at->format('d/m/Y') : 'N/A' }}
                    </dd>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                <div class="flex space-x-2">
                    <a href="{{ route('barrios.show', $barrio) }}"
                       class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 dark:text-primary-400 dark:bg-primary-900/20 dark:hover:bg-primary-900/30 rounded-lg transition-colors">
                        <x-icons.eye class="w-3 h-3 mr-1" />
                        Ver
                    </a>
                    <a href="{{ route('barrios.edit', $barrio) }}"
                       class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-green-600 bg-green-50 hover:bg-green-100 dark:text-green-400 dark:bg-green-900/20 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                        <x-icons.pencil class="w-3 h-3 mr-1" />
                        Editar
                    </a>
                    <form action="{{ route('barrios.destroy', $barrio) }}" method="POST" class="flex-1" onsubmit="return confirmDelete(event, '{{ $barrio->nombre }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 dark:text-red-400 dark:bg-red-900/20 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                            <x-icons.trash class="w-3 h-3 mr-1" />
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
            <x-icons.map-pin class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Sin barrios encontrados</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">No hay barrios que coincidan con los criterios de búsqueda.</p>
            <a href="{{ route('barrios.create') }}"
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow-sm transition-colors duration-200">
                <x-icons.plus class="w-4 h-4 mr-2" />
                Crear Primer Barrio
            </a>
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if($barrios->hasPages())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Mostrando {{ $barrios->firstItem() ?? 0 }} a {{ $barrios->lastItem() ?? 0 }}
                de {{ $barrios->total() }} barrios
            </div>
            <div class="pagination">
                {{ $barrios->links() }}
            </div>
        </div>
    </div>
@endif

<script>
    async function confirmDelete(event, nombreBarrio) {
        event.preventDefault();

        const confirmed = await window.confirmDialog({
            title: '¿Estás seguro?',
            message: `El barrio "${nombreBarrio}" será eliminado permanentemente. Esta acción no se puede deshacer.`,
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            confirmStyle: 'danger'
        });

        if (confirmed) {
            event.target.submit();
        }

        return false;
    }
</script>
