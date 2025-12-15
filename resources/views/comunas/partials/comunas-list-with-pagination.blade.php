<!-- Desktop Table (hidden on mobile) -->
<div class="hidden md:block bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
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
                        Barrios
                    </th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Acciones</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($comunas as $comuna)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-primary-400 to-primary-600 flex items-center justify-center text-white font-semibold">
                                    {{ substr($comuna->nombre, 0, 1) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $comuna->nombre }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {{ $comuna->municipio->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {{ $comuna->municipio->departamento->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            {{ $comuna->municipio->departamento->pais->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                {{ $comuna->barrios->count() }} barrios
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <a href="{{ route('comunas.show', $comuna) }}"
                                   class="inline-flex items-center text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                    <x-icons.eye class="w-4 h-4 mr-1" />
                                    Ver
                                </a>
                                <a href="{{ route('comunas.edit', $comuna) }}"
                                   class="inline-flex items-center text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                    <x-icons.pencil class="w-4 h-4 mr-1" />
                                    Editar
                                </a>
                                <form action="{{ route('comunas.destroy', $comuna) }}" method="POST" class="inline-block delete-comuna-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        <x-icons.trash class="w-4 h-4 mr-1" />
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icons.building class="w-12 h-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No se encontraron comunas</h3>
                                <p class="text-gray-500 dark:text-gray-400">Intenta ajustar tus filtros de búsqueda.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($comunas->hasPages())
        <div class="bg-gray-50 dark:bg-gray-700 px-6 py-3">
            {{ $comunas->appends(request()->query())->links() }}
        </div>
    @endif
</div>

<!-- Mobile Cards (visible only on mobile) -->
<div class="md:hidden space-y-4">
    @forelse($comunas as $comuna)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <!-- Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-primary-400 to-primary-600 flex items-center justify-center text-white font-semibold text-lg">
                        {{ substr($comuna->nombre, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white truncate">
                            {{ $comuna->nombre }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            {{ $comuna->municipio->name ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-4 space-y-3">
                <!-- Location Info -->
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Ubicación
                    </dt>
                    <dd class="mt-1">
                        <div class="text-sm text-gray-900 dark:text-white">
                            <div>{{ $comuna->municipio->departamento->name ?? 'N/A' }}</div>
                            <div class="text-gray-500 dark:text-gray-400">{{ $comuna->municipio->departamento->pais->name ?? 'N/A' }}</div>
                        </div>
                    </dd>
                </div>

                <!-- Barrios Count -->
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Barrios
                    </dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            {{ $comuna->barrios->count() }} barrios
                        </span>
                    </dd>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                <div class="flex space-x-2">
                    <a href="{{ route('comunas.show', $comuna) }}"
                       class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 dark:text-primary-400 dark:bg-primary-900/20 dark:hover:bg-primary-900/30 rounded-lg transition-colors">
                        <x-icons.eye class="w-3 h-3 mr-1" />
                        Ver
                    </a>
                    <a href="{{ route('comunas.edit', $comuna) }}"
                       class="flex-1 inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 dark:text-primary-400 dark:bg-primary-900/20 dark:hover:bg-primary-900/30 rounded-lg transition-colors">
                        <x-icons.pencil class="w-3 h-3 mr-1" />
                        Editar
                    </a>
                    <form action="{{ route('comunas.destroy', $comuna) }}" method="POST" class="flex-1 delete-comuna-form">
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
        <!-- Empty State for Mobile -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-8">
            <div class="text-center">
                <x-icons.building class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No se encontraron comunas</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Intenta ajustar tus filtros de búsqueda.</p>
            </div>
        </div>
    @endforelse

    @if($comunas->hasPages())
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-4 py-3">
            {{ $comunas->appends(request()->query())->links() }}
        </div>
    @endif
</div>
