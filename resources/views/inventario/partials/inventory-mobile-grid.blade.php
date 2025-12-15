<div class="space-y-4">
    @if($movimientos->count() > 0)
        @foreach($movimientos as $movimiento)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <!-- Header del movimiento -->
                <div class="flex justify-between items-start mb-3">
                    <div>
                        @if($movimiento->tipo_registro === 'empeno')
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $movimiento->numero_contrato }}</h4>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    EMPEÑO
                                </span>
                                @php
                                    $estadoClasses = [
                                        'activa' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
                                        'pagada' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                        'vencida' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                        'cancelada' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $estadoClasses[$movimiento->estado] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ strtoupper($movimiento->estado) }}
                                </span>
                            </div>
                        @else
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ optional($movimiento->boletaEmpeno)->numero_contrato }}</h4>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                    DESEMPEÑO
                                </span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                    {{ strtoupper($movimiento->estado) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold {{ optional($movimiento->tipoMovimiento)->es_suma ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            ${{ number_format($movimiento->monto, 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $movimiento->created_at->format('d/m/Y') }}
                        </p>
                    </div>
                </div>

                <!-- Información del cliente -->
                <div class="mb-3">
                    @php
                        $cliente = $movimiento->tipo_registro === 'empeno'
                            ? $movimiento->cliente
                            : optional($movimiento->boletaEmpeno)->cliente;
                    @endphp
                    @if($cliente)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $cliente->razon_social ?: ($cliente->nombres . ' ' . $cliente->apellidos) }}
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                {{ optional($cliente->tipoDocumento)->abreviacion }}: {{ $cliente->cedula_nit }}
                            </p>
                            @if($cliente->telefono_fijo || $cliente->celular)
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    @if($cliente->telefono_fijo)
                                        Tel: {{ $cliente->telefono_fijo }}
                                    @endif
                                    @if($cliente->celular)
                                        @if($cliente->telefono_fijo) | @endif
                                        Cel: {{ $cliente->celular }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Cliente no disponible</p>
                        </div>
                    @endif
                </div>

                <!-- Información adicional en grid -->
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">Empresa:</p>
                        <p class="text-gray-900 dark:text-white text-xs">
                            @if($movimiento->tipo_registro === 'empeno')
                                {{ optional($movimiento->empresa)->razon_social ?: 'N/A' }}
                            @else
                                {{ optional(optional($movimiento->boletaEmpeno)->empresa)->razon_social ?: 'N/A' }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">Tipo Movimiento:</p>
                        <p class="text-gray-900 dark:text-white text-xs">
                            {{ optional($movimiento->tipoMovimiento)->nombre ?: 'N/A' }}
                        </p>
                    </div>
                </div>

                <!-- Productos -->
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-gray-600 dark:text-gray-400 font-medium text-sm mb-2">Productos:</p>
                    @php
                        $productos = $movimiento->tipo_registro === 'empeno'
                            ? $movimiento->productos
                            : optional($movimiento->boletaEmpeno)->productos;
                    @endphp
                    @if($productos && $productos->count() > 0)
                        <div class="space-y-1">
                            @foreach($productos->take(3) as $prod)
                                @if($prod->producto)
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-gray-900 dark:text-white">{{ $prod->producto->nombre }}</span>
                                        <div class="flex items-center space-x-2">
                                            @if($prod->producto->tipoOro)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                                    {{ $prod->producto->tipoOro->nombre }}
                                                </span>
                                            @elseif($prod->producto->tipoProducto)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400">
                                                    {{ $prod->producto->tipoProducto->nombre }}
                                                </span>
                                            @endif
                                            @if($prod->cantidad)
                                                <span class="text-gray-500 dark:text-gray-400">{{ $prod->cantidad }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            @if($productos->count() > 3)
                                <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                                    +{{ $productos->count() - 3 }} productos más
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sin productos registrados</p>
                    @endif
                </div>

                <!-- Ubicación y acciones -->
                @if($movimiento->tipo_registro === 'empeno')
                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 font-medium text-xs">Ubicación:</p>
                            <p class="text-gray-900 dark:text-white text-xs">
                                {{ $movimiento->ubicacion ?: 'Sin especificar' }}
                            </p>
                        </div>
                        @can('registros.edit')
                            <button onclick="abrirModalUbicacion({{ $movimiento->id }}, '{{ $movimiento->ubicacion }}')"
                                    class="inline-flex items-center px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Ubicar
                            </button>
                        @endcan
                    </div>
                @endif

                @if($movimiento->tipo_registro === 'desempeno' && $movimiento->observaciones)
                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-gray-600 dark:text-gray-400 font-medium text-xs">Observaciones:</p>
                        <p class="text-gray-900 dark:text-white text-xs">{{ $movimiento->observaciones }}</p>
                    </div>
                @endif
            </div>
        @endforeach

        <!-- Mobile Pagination -->
        @if($movimientos->hasPages())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Mostrando {{ $movimientos->firstItem() }} a {{ $movimientos->lastItem() }} de {{ $movimientos->total() }} resultados
                    </div>
                    <div class="flex space-x-1">
                        @if($movimientos->onFirstPage())
                            <span class="px-3 py-1 text-sm text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">Anterior</span>
                        @else
                            <a href="{{ $movimientos->previousPageUrl() }}" class="px-3 py-1 text-sm text-primary-600 hover:text-primary-800 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded">Anterior</a>
                        @endif

                        @if($movimientos->hasMorePages())
                            <a href="{{ $movimientos->nextPageUrl() }}" class="px-3 py-1 text-sm text-primary-600 hover:text-primary-800 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded">Siguiente</a>
                        @else
                            <span class="px-3 py-1 text-sm text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">Siguiente</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay movimientos</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    No se encontraron movimientos con los filtros seleccionados.
                </p>
            </div>
        </div>
    @endif
</div>
