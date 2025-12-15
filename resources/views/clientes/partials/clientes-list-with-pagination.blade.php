<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
    @if($clientes->count() > 0)
        <!-- Table Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Clientes Registrados
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $clientes->total() }} cliente(s) encontrado(s)
            </p>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Identificación
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Contacto
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Ubicación
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Tipo Persona
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Tipo Responsabilidad
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Redes Sociales
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Registro
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($clientes as $cliente)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <img class="h-10 w-10 rounded-full object-cover"
                                             src="{{ $cliente->foto_url }}"
                                             alt="{{ $cliente->nombre_completo }}">
                                    </div>
                                    <div class="ml-4 min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">
                                            {{ $cliente->nombre_completo }}
                                        </div>
                                        @if($cliente->email)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                {{ $cliente->email }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        {{ $cliente->tipoDocumento->abreviacion ?? 'N/A' }}
                                    </div>
                                    <div class="font-mono text-sm">
                                        {{ $cliente->documento_completo }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <div class="flex items-center">
                                        <x-icons.phone class="w-4 h-4 mr-1 text-gray-400" />
                                        {{ $cliente->celular }}
                                    </div>
                                    @if($cliente->telefono_fijo)
                                        <div class="flex items-center mt-1">
                                            <x-icons.phone class="w-4 h-4 mr-1 text-gray-400" />
                                            {{ $cliente->telefono_fijo }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white max-w-xs">
                                    <div class="flex items-center">
                                        <x-icons.map-pin class="w-4 h-4 mr-1 text-gray-400 flex-shrink-0" />
                                        <span class="truncate">
                                            {{ $cliente->barrio->nombre ?? 'N/A' }},
                                            {{ $cliente->municipio->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-5 truncate">
                                        {{ $cliente->departamento->name ?? 'N/A' }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    @if($cliente->tipoPersona)
                                        {{ $cliente->tipoPersona->name }}
                                    @else
                                        <span class="text-gray-400">No especificado</span>
                                    @endif
                                </div>
                                @if($cliente->tipoPersona)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $cliente->tipoPersona->code }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    @if($cliente->tipoResponsabilidad)
                                        {{ $cliente->tipoResponsabilidad->name }}
                                    @else
                                        <span class="text-gray-400">No especificado</span>
                                    @endif
                                </div>
                                @if($cliente->tipoResponsabilidad)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $cliente->tipoResponsabilidad->code }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($cliente->redesSociales->count() > 0)
                                    <div class="flex space-x-1">
                                        @foreach($cliente->redesSociales->take(3) as $redSocial)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $redSocial->nombre }}
                                            </span>
                                        @endforeach
                                        @if($cliente->redesSociales->count() > 3)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                +{{ $cliente->redesSociales->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Sin redes</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $cliente->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <a href="{{ route('clientes.show', ['empresa' => $empresa, 'cliente' => $cliente]) }}"
                                       class="inline-flex items-center text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 transition-colors duration-200"
                                       title="Ver cliente">
                                        <x-icons.eye class="w-4 h-4" />
                                    </a>
                                    <a href="{{ route('clientes.edit', ['empresa' => $empresa, 'cliente' => $cliente]) }}"
                                       class="inline-flex items-center text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 transition-colors duration-200"
                                       title="Editar cliente">
                                        <x-icons.pencil class="w-4 h-4" />
                                    </a>
                                    <form action="{{ route('clientes.destroy', ['empresa' => $empresa, 'cliente' => $cliente]) }}"
                                          method="POST"
                                          class="inline-block"
                                          onsubmit="return confirm('¿Estás seguro de que deseas eliminar este cliente?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
                                                title="Eliminar cliente">
                                            <x-icons.trash class="w-4 h-4" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($clientes->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $clientes->links() }}
            </div>
        @endif

    @else
        <!-- Empty State -->
        <div class="p-12 text-center">
            <x-icons.users class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Sin clientes encontrados</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">No hay clientes que coincidan con los criterios de búsqueda.</p>
            <a href="{{ route('clientes.create', $empresa) }}"
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow-sm transition-colors duration-200">
                <x-icons.plus class="w-4 h-4 mr-2" />
                Crear Primer Cliente
            </a>
        </div>
    @endif
</div>
