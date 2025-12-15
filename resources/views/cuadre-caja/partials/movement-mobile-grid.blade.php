@if($movimientosPaginados && $movimientosPaginados->count() > 0)
    <div class="space-y-4">
        @foreach($movimientosPaginados as $movimiento)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <!-- Header de la card -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        @switch($movimiento->tipo_registro)
                            @case('empeno')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    Empeño
                                </span>
                                @break
                            @case('cuota')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Cuota
                                </span>
                                @break
                            @case('desempeno')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-10.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 6.414V10a1 1 0 102 0V6.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    Desempeño
                                </span>
                                @break
                            @case('documento_equivalente')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Doc. Equivalente
                                </span>
                                @break
                        @endswitch

                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $movimiento->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>

                    <!-- Impacto en Caja -->
                    @if($movimiento->signo_movimiento === 'suma')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            +${{ number_format($movimiento->monto ?? 0, 0, ',', '.') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            -${{ number_format($movimiento->monto ?? 0, 0, ',', '.') }}
                        </span>
                    @endif
                </div>

                <!-- Información principal -->
                <div class="space-y-3">
                    <!-- Contrato/Documento y Cliente -->
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $movimiento->numero_contrato ?? 'Sin número' }}
                                    </div>
                                    @if($movimiento->tipo_registro === 'cuota')
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Cuota ID: {{ $movimiento->id }}
                                        </div>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                                        ${{ number_format($movimiento->monto ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cliente -->
                        @if($movimiento->cliente)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $movimiento->cliente->razon_social ?:
                                       trim($movimiento->cliente->nombres . ' ' . $movimiento->cliente->apellidos) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $movimiento->cliente->cedula_nit }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Empresa -->
                    @if($movimiento->empresa)
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $movimiento->empresa->razon_social }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $movimiento->empresa->razon_social }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Productos -->
                    @if($movimiento->productos && $movimiento->productos->count() > 0)
                        <div>
                            <div class="flex items-center space-x-2 mb-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Productos ({{ $movimiento->productos->count() }})
                                </span>
                            </div>
                            <div class="space-y-2">
                                @foreach($movimiento->productos->take(2) as $producto)
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                @if($producto->producto->tipoOro)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                        {{ $producto->producto->tipoOro->nombre }}
                                                    </span>
                                                @elseif($producto->producto->tipoProducto)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                        {{ $producto->producto->tipoProducto->nombre }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($producto->peso)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $producto->peso }} gr
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-900 dark:text-white mt-1">
                                            {{ $producto->producto->descripcion }}
                                        </div>
                                    </div>
                                @endforeach
                                @if($movimiento->productos->count() > 2)
                                    <div class="text-center">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            +{{ $movimiento->productos->count() - 2 }} productos más
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Observaciones -->
                    @if($movimiento->observaciones)
                        <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                            <div class="flex items-start space-x-2">
                                <svg class="w-4 h-4 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                <div>
                                    <div class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Observaciones
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $movimiento->observaciones }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <!-- Pagination móvil -->
        @if($movimientosPaginados->hasPages())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    @if($movimientosPaginados->onFirstPage())
                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                            Anterior
                        </span>
                    @else
                        <a href="{{ $movimientosPaginados->previousPageUrl() }}"
                           class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            Anterior
                        </a>
                    @endif

                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        {{ $movimientosPaginados->firstItem() }}-{{ $movimientosPaginados->lastItem() }}
                        de {{ $movimientosPaginados->total() }}
                    </div>

                    @if($movimientosPaginados->hasMorePages())
                        <a href="{{ $movimientosPaginados->nextPageUrl() }}"
                           class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            Siguiente
                        </a>
                    @else
                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                            Siguiente
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@else
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay movimientos</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                No se encontraron movimientos para los filtros seleccionados.
            </p>
        </div>
    </div>
@endif
