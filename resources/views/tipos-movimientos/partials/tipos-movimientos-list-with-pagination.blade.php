<!-- Desktop Table (hidden on mobile) -->
<div class="hidden md:block bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Tipo
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Nombre
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Descripción
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Empresa
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($tiposMovimientos as $tipoMovimiento)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            {!! $tipoMovimiento->icono_movimiento !!}
                            <span class="ml-3 text-sm font-medium {{ $tipoMovimiento->color_movimiento }}">
                                {{ $tipoMovimiento->tipo_movimiento }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {{ $tipoMovimiento->nombre }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <div class="max-w-xs">
                            {{ Str::limit($tipoMovimiento->descripcion, 80) }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        {{ $tipoMovimiento->empresa->nombre }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($tipoMovimiento->activo)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Activo
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Inactivo
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center space-x-3">
                            @can('users.view')
                            <a href="{{ route('tipos-movimientos.show', $tipoMovimiento) }}"
                               class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200"
                               title="Ver detalles">
                                <x-icons.eye class="w-5 h-5" />
                            </a>
                            @endcan

                            @can('users.update')
                            <a href="{{ route('tipos-movimientos.edit', $tipoMovimiento) }}"
                               class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors duration-200"
                               title="Editar">
                                <x-icons.pencil class="w-5 h-5" />
                            </a>
                            @endcan

                            @can('users.delete')
                            <button type="button"
                                    onclick="confirmarEliminacion({{ $tipoMovimiento->id }}, '{{ $tipoMovimiento->nombre }}')"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
                                    title="Eliminar">
                                <x-icons.trash class="w-5 h-5" />
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                            <p class="text-lg font-medium">No se encontraron tipos de movimientos</p>
                            <p class="text-sm text-gray-400 mt-1">Comienza creando tu primer tipo de movimiento</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile Cards (visible on mobile, hidden on desktop) -->
<div class="md:hidden space-y-4">
    @forelse($tiposMovimientos as $tipoMovimiento)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                {!! $tipoMovimiento->icono_movimiento !!}
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $tipoMovimiento->nombre }}</h3>
                    <p class="text-sm {{ $tipoMovimiento->color_movimiento }}">{{ $tipoMovimiento->tipo_movimiento }}</p>
                </div>
            </div>

            @if($tipoMovimiento->activo)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Activo
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    Inactivo
                </span>
            @endif
        </div>

        @if($tipoMovimiento->descripcion)
        <div class="mb-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $tipoMovimiento->descripcion }}</p>
        </div>
        @endif

        <div class="mb-4">
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                {{ $tipoMovimiento->empresa->nombre }}
            </div>
        </div>

        <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Creado: {{ $tipoMovimiento->created_at ? $tipoMovimiento->created_at->format('d/m/Y') : 'N/A' }}
            </div>

            <div class="flex items-center space-x-3">
                @can('users.view')
                <a href="{{ route('tipos-movimientos.show', $tipoMovimiento) }}"
                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200"
                   title="Ver detalles">
                    <x-icons.eye class="w-5 h-5" />
                </a>
                @endcan

                @can('users.update')
                <a href="{{ route('tipos-movimientos.edit', $tipoMovimiento) }}"
                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors duration-200"
                   title="Editar">
                    <x-icons.pencil class="w-5 h-5" />
                </a>
                @endcan

                @can('users.delete')
                <button type="button"
                        onclick="confirmarEliminacion({{ $tipoMovimiento->id }}, '{{ $tipoMovimiento->nombre }}')"
                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
                        title="Eliminar">
                    <x-icons.trash class="w-5 h-5" />
                </button>
                @endcan
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
        </svg>
        <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">No se encontraron tipos de movimientos</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Comienza creando tu primer tipo de movimiento</p>
        @can('users.create')
        <a href="{{ route('tipos-movimientos.create') }}"
           class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors duration-200">
            <x-icons.plus class="w-4 h-4 mr-2" />
            Crear Tipo de Movimiento
        </a>
        @endcan
    </div>
    @endforelse
</div>

<!-- Pagination -->
@if($tiposMovimientos->hasPages())
<div class="mt-6">
    {{ $tiposMovimientos->links() }}
</div>
@endif
