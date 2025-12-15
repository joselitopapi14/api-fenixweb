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
                @forelse($boletaDesempenos as $boletaDesempeno)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                Boleta: {{ $boletaDesempeno->boletaEmpeno->numero_contrato }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $boletaDesempeno->boletaEmpeno->cliente->nombres ?? '' }} {{ $boletaDesempeno->boletaEmpeno->cliente->apellidos ?? '' }}
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-2">
                                <span>{{ $boletaDesempeno->boletaEmpeno->cliente->cedula_nit ?? 'Sin cédula' }}</span>
                                @if($boletaDesempeno->boletaEmpeno->cliente->celular || $boletaDesempeno->boletaEmpeno->cliente->telefono_fijo)
                                    <span class="text-gray-300 dark:text-gray-600">|</span>
                                    <span class="flex gap-1">
                                        @if($boletaDesempeno->boletaEmpeno->cliente->celular)
                                            <span class="text-blue-600 dark:text-blue-400">{{ $boletaDesempeno->boletaEmpeno->cliente->celular }}</span>
                                        @endif
                                        @if($boletaDesempeno->boletaEmpeno->cliente->telefono_fijo && $boletaDesempeno->boletaEmpeno->cliente->celular)
                                            <span class="text-gray-400">/</span>
                                        @endif
                                        @if($boletaDesempeno->boletaEmpeno->cliente->telefono_fijo)
                                            <span class="text-green-600 dark:text-green-400">{{ $boletaDesempeno->boletaEmpeno->cliente->telefono_fijo }}</span>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('boleta-desempenos.show', $boletaDesempeno) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition-colors duration-200"
                               title="Ver detalles">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>

                            {{-- Edit action removed per request --}}

                            <a href="{{ route('boletas-empeno.show', $boletaDesempeno->boletaEmpeno) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors duration-200"
                               title="Ver boleta">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </a>

                            <a href="{{ route('boleta-desempenos.pdf', $boletaDesempeno) }}"
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
                            {{ $boletaDesempeno->fecha_abono->format('d/m/Y') }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $boletaDesempeno->created_at->format('H:i:s') }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            ${{ number_format($boletaDesempeno->monto_pagado, 2) }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-white">
                            {{ $boletaDesempeno->usuario->name ?? 'No registrado' }}
                        </div>
                    </td>
                    @if(auth()->user()->esAdministradorGlobal())
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-white">
                            {{ $boletaDesempeno->boletaEmpeno->empresa->nombre ?? 'N/A' }}
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
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No hay boletas de desempeño registradas</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Aún no se han registrado boletas de desempeño con los filtros seleccionados.</p>
                            @can('registros.create')
                            <a href="{{ route('boleta-desempenos.create') }}"
                               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-md transition-colors duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Registrar Primera Boleta Desempeño
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($boletaDesempenos->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $boletaDesempenos->links() }}
    </div>
    @endif
</div>

<!-- Mobile Card View -->
<div class="md:hidden space-y-4">
    @forelse($boletaDesempenos as $boletaDesempeno)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <!-- Header with Boleta and Status -->
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                    Boleta: {{ $boletaDesempeno->boletaEmpeno->numero_contrato }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $boletaDesempeno->boletaEmpeno->cliente->nombres ?? '' }} {{ $boletaDesempeno->boletaEmpeno->cliente->apellidos ?? '' }}
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-2 mt-1">
                    <span>CC: {{ $boletaDesempeno->boletaEmpeno->cliente->cedula_nit ?? 'Sin cédula' }}</span>
                    @if($boletaDesempeno->boletaEmpeno->cliente->celular || $boletaDesempeno->boletaEmpeno->cliente->telefono_fijo)
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span class="flex gap-1">
                            @if($boletaDesempeno->boletaEmpeno->cliente->celular)
                                <span class="text-blue-600 dark:text-blue-400">{{ $boletaDesempeno->boletaEmpeno->cliente->celular }}</span>
                            @endif
                            @if($boletaDesempeno->boletaEmpeno->cliente->telefono_fijo && $boletaDesempeno->boletaEmpeno->cliente->celular)
                                <span class="text-gray-400">/</span>
                            @endif
                            @if($boletaDesempeno->boletaEmpeno->cliente->telefono_fijo)
                                <span class="text-green-600 dark:text-green-400">{{ $boletaDesempeno->boletaEmpeno->cliente->telefono_fijo }}</span>
                            @endif
                        </span>
                    @endif
                </div>
            </div>
            <div class="flex flex-col items-end space-y-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $boletaDesempeno->estado === 'pagada' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' }}">
                    {{ ucfirst($boletaDesempeno->estado) }}
                </span>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $boletaDesempeno->fecha_abono->format('d/m/Y') }} - {{ $boletaDesempeno->created_at->format('H:i') }}
                </div>
            </div>
        </div>

        @if(auth()->user()->esAdministradorGlobal())
        <!-- Empresa -->
        <div class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-600">
            <div class="text-xs text-gray-500 dark:text-gray-400">Empresa</div>
            <div class="text-sm text-gray-900 dark:text-white">
                {{ $boletaDesempeno->boletaEmpeno->empresa->nombre ?? 'N/A' }}
            </div>
        </div>
        @endif

        <!-- Financial Information -->
        <div class="grid grid-cols-2 gap-4 mb-3">
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Monto Sugerido</div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                    ${{ number_format($boletaDesempeno->monto_sugerido, 0, '.', ',') }}
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Monto Pagado</div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                    ${{ number_format($boletaDesempeno->monto_pagado, 0, '.', ',') }}
                </div>
            </div>
        </div>

        <!-- User -->
        <div class="mb-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Atendido por</div>
            <div class="text-sm text-gray-900 dark:text-white">
                {{ $boletaDesempeno->usuario->name ?? 'No registrado' }}
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap gap-2 pt-3">
            <a href="{{ route('boleta-desempenos.show', $boletaDesempeno) }}"
               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition-colors duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Ver
            </a>

            {{-- Edit action removed per request --}}

            <a href="{{ route('boletas-empeno.show', $boletaDesempeno->boletaEmpeno) }}"
               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Boleta
            </a>

            <a href="{{ route('boleta-desempenos.pdf', $boletaDesempeno) }}"
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
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No hay boletas de desempeño registradas</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4 text-center">Aún no se han registrado boletas de desempeño con los filtros seleccionados.</p>
            @can('registros.create')
            <a href="{{ route('boleta-desempenos.create') }}"
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-md transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Registrar Primera Boleta Desempeño
            </a>
            @endcan
        </div>
    </div>
    @endforelse

    @if($boletaDesempenos->hasPages())
    <div class="mt-6">
        {{ $boletaDesempenos->links() }}
    </div>
    @endif
</div>
