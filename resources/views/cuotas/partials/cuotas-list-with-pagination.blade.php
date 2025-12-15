<!-- Desktop Table View -->
<div class="hidden md:block bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Boleta / Cliente
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Acciones
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Fecha Abono
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Monto Pagado
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Atendido por
                    </th>
                    @if(auth()->user()->esAdministradorGlobal())
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Empresa
                    </th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($cuotas as $cuota)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                Boleta: {{ $cuota->boletaEmpeno->numero_contrato }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $cuota->boletaEmpeno->cliente->nombres ?? '' }} {{ $cuota->boletaEmpeno->cliente->apellidos ?? '' }}
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-2">
                                <span>{{ $cuota->boletaEmpeno->cliente->cedula_nit ?? 'Sin cédula' }}</span>
                                @if($cuota->boletaEmpeno->cliente->celular || $cuota->boletaEmpeno->cliente->telefono_fijo)
                                    <span class="text-gray-300 dark:text-gray-600">|</span>
                                    <span class="flex gap-1">
                                        @if($cuota->boletaEmpeno->cliente->celular)
                                            <span class="text-blue-600 dark:text-blue-400">{{ $cuota->boletaEmpeno->cliente->celular }}</span>
                                        @endif
                                        @if($cuota->boletaEmpeno->cliente->telefono_fijo && $cuota->boletaEmpeno->cliente->celular)
                                            <span class="text-gray-400">/</span>
                                        @endif
                                        @if($cuota->boletaEmpeno->cliente->telefono_fijo)
                                            <span class="text-green-600 dark:text-green-400">{{ $cuota->boletaEmpeno->cliente->telefono_fijo }}</span>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('cuotas.show', $cuota) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition-colors duration-200"
                               title="Ver detalles">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>

                            @can('registros.edit')
                            <a href="{{ route('cuotas.edit', $cuota) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 text-xs font-medium rounded-md transition-colors duration-200"
                               title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            @endcan

                            <a href="{{ route('boletas-empeno.show', $cuota->boletaEmpeno) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors duration-200"
                               title="Ver boleta">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </a>

                            <a href="{{ route('cuotas.pdf', $cuota) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-md transition-colors duration-200"
                               title="Generar PDF"
                               target="_blank">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </a>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $cuota->fecha_abono->format('d/m/Y') }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $cuota->created_at->format('H:i:s') }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            ${{ number_format($cuota->monto_pagado, 2) }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-white">
                            {{ $cuota->usuario->name ?? 'No registrado' }}
                        </div>
                    </td>
                    @if(auth()->user()->esAdministradorGlobal())
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-white">
                            {{ $cuota->boletaEmpeno->empresa->nombre ?? 'N/A' }}
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ auth()->user()->esAdministradorGlobal() ? '6' : '5' }}" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No hay cuotas registradas</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Aún no se han registrado cuotas con los filtros seleccionados.</p>
                            @can('registros.create')
                            <a href="{{ route('cuotas.create') }}"
                               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-md transition-colors duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Registrar Primera Cuota
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($cuotas->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $cuotas->links() }}
    </div>
    @endif
</div>

<!-- Mobile Card View -->
<div class="md:hidden space-y-4">
    @forelse($cuotas as $cuota)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <!-- Header with Boleta and Status -->
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                    Boleta: {{ $cuota->boletaEmpeno->numero_contrato }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $cuota->boletaEmpeno->cliente->nombres ?? '' }} {{ $cuota->boletaEmpeno->cliente->apellidos ?? '' }}
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-2 mt-1">
                    <span>CC: {{ $cuota->boletaEmpeno->cliente->cedula_nit ?? 'Sin cédula' }}</span>
                    @if($cuota->boletaEmpeno->cliente->celular || $cuota->boletaEmpeno->cliente->telefono_fijo)
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span class="flex gap-1">
                            @if($cuota->boletaEmpeno->cliente->celular)
                                <span class="text-blue-600 dark:text-blue-400">{{ $cuota->boletaEmpeno->cliente->celular }}</span>
                            @endif
                            @if($cuota->boletaEmpeno->cliente->telefono_fijo && $cuota->boletaEmpeno->cliente->celular)
                                <span class="text-gray-400">/</span>
                            @endif
                            @if($cuota->boletaEmpeno->cliente->telefono_fijo)
                                <span class="text-green-600 dark:text-green-400">{{ $cuota->boletaEmpeno->cliente->telefono_fijo }}</span>
                            @endif
                        </span>
                    @endif
                </div>
            </div>
            <div class="flex flex-col items-end space-y-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $cuota->estado === 'pagada' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' }}">
                    {{ ucfirst($cuota->estado) }}
                </span>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $cuota->fecha_abono->format('d/m/Y') }} - {{ $cuota->created_at->format('H:i') }}
                </div>
            </div>
        </div>

        @if(auth()->user()->esAdministradorGlobal())
        <!-- Empresa -->
        <div class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-600">
            <div class="text-xs text-gray-500 dark:text-gray-400">Empresa</div>
            <div class="text-sm text-gray-900 dark:text-white">
                {{ $cuota->boletaEmpeno->empresa->nombre ?? 'N/A' }}
            </div>
        </div>
        @endif

        <!-- Financial Information -->
        <div class="grid grid-cols-2 gap-4 mb-3">
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Monto Sugerido</div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                    ${{ number_format($cuota->monto_sugerido, 0, '.', ',') }}
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Monto Pagado</div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                    ${{ number_format($cuota->monto_pagado, 0, '.', ',') }}
                </div>
            </div>
        </div>



        <!-- User -->
        <div class="mb-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Atendido por</div>
            <div class="text-sm text-gray-900 dark:text-white">
                {{ $cuota->usuario->name ?? 'No registrado' }}
            </div>
        </div>

        <!-- Actions (moved up to appear after Boleta/Cliente) -->
        <div class="flex flex-wrap gap-2 pt-3">
            <a href="{{ route('cuotas.show', $cuota) }}"
               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition-colors duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Ver
            </a>

            @can('registros.edit')
            <a href="{{ route('cuotas.edit', $cuota) }}"
               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 text-xs font-medium rounded-md transition-colors duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Editar
            </a>
            @endcan

            <a href="{{ route('boletas-empeno.show', $cuota->boletaEmpeno) }}"
               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Boleta
            </a>

            <a href="{{ route('cuotas.pdf', $cuota) }}"
               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-md transition-colors duration-200"
               target="_blank">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                PDF
            </a>

        </div>
    </div>
    @empty
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
        <div class="flex flex-col items-center">
            <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No hay cuotas registradas</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4 text-center">Aún no se han registrado cuotas con los filtros seleccionados.</p>
            @can('registros.create')
            <a href="{{ route('cuotas.create') }}"
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-md transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Registrar Primera Cuota
            </a>
            @endcan
        </div>
    </div>
    @endforelse

    @if($cuotas->hasPages())
    <div class="mt-6">
        {{ $cuotas->links() }}
    </div>
    @endif
</div>
