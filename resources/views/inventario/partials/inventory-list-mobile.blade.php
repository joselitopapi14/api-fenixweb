<!-- Mobile View -->
<div class="divide-y divide-gray-200 dark:divide-gray-700">
    @forelse($movimientos as $movimiento)
        @php
            // Determinar si es boleta de empeño o desempeño
            $esBoleta = $movimiento->tipo_registro === 'empeno';
            $cliente = $esBoleta ? $movimiento->cliente : $movimiento->boletaEmpeno?->cliente;
            $empresa = $esBoleta ? $movimiento->empresa : $movimiento->boletaEmpeno?->empresa;
            $sede = $esBoleta ? $movimiento->sede : $movimiento->boletaEmpeno?->sede;
            $productos = $esBoleta ? $movimiento->productos : $movimiento->boletaEmpeno?->productos;
            $numeroContrato = $esBoleta ? $movimiento->numero_contrato : ($movimiento->boletaEmpeno?->numero_contrato ?? 'N/A');
            $fotoPrenda = $esBoleta ? $movimiento->foto_prenda : $movimiento->boletaEmpeno?->foto_prenda;
            $fotoPrendaUrl = $esBoleta
                ? ($movimiento->foto_prenda ? asset('storage/' . $movimiento->foto_prenda) : null)
                : ($movimiento->boletaEmpeno?->foto_prenda ? asset('storage/' . $movimiento->boletaEmpeno->foto_prenda) : null);
        @endphp
        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
            <!-- Header con contrato y estado -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $numeroContrato }}
                            @if(!$esBoleta)
                                <span class="text-xs text-blue-600 dark:text-blue-400">(Desempeño)</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $movimiento->created_at->format('d/m/Y H:i') }}
                        </p>
                        @if(!$esBoleta && $movimiento->fecha_abono)
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Abono: {{ \Carbon\Carbon::parse($movimiento->fecha_abono)->format('d/m/Y') }}
                            </p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col items-end space-y-1">
                    @if($esBoleta)
                        @if($movimiento->anulada)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                Anulada
                            </span>
                        @elseif($movimiento->es_vencida)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                Vencida
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                Activa
                            </span>
                        @endif
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                            {{ ucfirst($movimiento->estado) }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Cliente -->
            <div class="mb-3">
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    @if($cliente)
                        {{ $cliente->razon_social ?: trim($cliente->nombres . ' ' . $cliente->apellidos) }}
                    @else
                        <span class="text-gray-400">Cliente eliminado</span>
                    @endif
                </p>
                @if($cliente)
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $cliente->tipoDocumento ? $cliente->tipoDocumento->abreviacion . ': ' : '' }}{{ $cliente->cedula_nit }}
                    </p>
                    @if($cliente->telefono_fijo || $cliente->celular)
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            @if($cliente->telefono_fijo)
                                <div class="flex items-center text-xs text-gray-600 dark:text-gray-300">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    {{ $cliente->telefono_fijo }}
                                </div>
                            @endif
                            @if($cliente->celular)
                                <div class="flex items-center text-xs text-gray-600 dark:text-gray-300">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ $cliente->celular }}
                                </div>
                            @endif
                        </div>
                    @endif
                @endif
            </div>

            <!-- Foto de la Prenda -->
            @if($fotoPrenda)
            <div class="mb-3">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <img class="h-16 w-16 rounded-lg object-cover border border-gray-200 dark:border-gray-600"
                             src="{{ $fotoPrendaUrl }}"
                             alt="Foto de la prenda"
                             onerror="this.style.display='none'"
                             title="Foto de la prenda empeñada">
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Foto de la Prenda</p>
                        <p class="text-sm text-gray-900 dark:text-white">Disponible</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Información principal -->
            <div class="grid grid-cols-2 gap-3 mb-3">
                <!-- Monto -->
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Monto</p>
                    <p class="text-sm font-semibold {{ $movimiento->tipoMovimiento && $movimiento->tipoMovimiento->es_suma ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        @if($movimiento->tipoMovimiento && !$movimiento->tipoMovimiento->es_suma)
                            -${{ number_format($movimiento->monto, 2) }}
                        @else
                            ${{ number_format($movimiento->monto, 2) }}
                        @endif
                    </p>
                </div>

                <!-- Empresa -->
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Empresa</p>
                    <p class="text-sm text-gray-900 dark:text-white">
                        {{ $empresa->razon_social ?? 'N/A' }}
                    </p>
                </div>
            </div>

            <!-- Tipo de movimiento y productos -->
            <div class="mb-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Tipo Movimiento</p>
                        @if($movimiento->tipoMovimiento)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $movimiento->tipoMovimiento->es_suma ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                                {{ $movimiento->tipoMovimiento->nombre }}
                                {{ $movimiento->tipoMovimiento->es_suma ? '(+)' : '(-)' }}
                            </span>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Productos</p>
                        <p class="text-sm text-gray-900 dark:text-white">
                            @if($productos && $productos->count() > 0)
                                {{ $productos->count() }} producto(s)
                            @else
                                @if(!$esBoleta)
                                    Ver boleta origen
                                @else
                                    Sin productos
                                @endif
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tipos de producto -->
            @if($productos && $productos->count() > 0)
                <div class="mb-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Tipos de Producto</p>
                    @php
                        $tiposProducto = $productos->map(function($prod) {
                            return $prod->producto->tipoProducto->nombre ?? 'N/A';
                        })->unique();
                    @endphp
                    <p class="text-sm text-gray-900 dark:text-white">
                        {{ $tiposProducto->implode(', ') }}
                    </p>
                </div>
            @endif

            <!-- Fechas -->
            @if($esBoleta && $movimiento->fecha_vencimiento)
                <div class="mb-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Fecha Vencimiento</p>
                    <p class="text-sm text-gray-900 dark:text-white">
                        {{ $movimiento->fecha_vencimiento->format('d/m/Y') }}
                    </p>
                </div>
                <div class="mb-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Días Restantes</p>
                    <p class="text-sm text-gray-900 dark:text-white">
                        @php
                            $diasRestantes = now()->diffInDays($movimiento->fecha_vencimiento->startOfDay(), false);
                        @endphp
                        {{ $diasRestantes > 0 ? $diasRestantes . ' día(s)' : 'Vencido' }}
                    </p>
                </div>
            @endif

            <!-- Observaciones para desempeños -->
            @if(!$esBoleta && $movimiento->observaciones)
                <div class="mb-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Observaciones</p>
                    <p class="text-sm text-gray-900 dark:text-white">
                        {{ $movimiento->observaciones }}
                    </p>
                </div>
            @endif

            <!-- Sede -->
            @if($sede)
                <div class="mb-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Sede</p>
                    <p class="text-sm text-gray-900 dark:text-white">
                        {{ $sede->nombre }}
                    </p>
                </div>
            @endif

            <!-- Ubicación -->
            @if($esBoleta && $movimiento->ubicacion)
                <div class="mb-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Ubicación</p>
                    <p class="text-sm text-blue-600 dark:text-blue-400">
                        <span class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ $movimiento->ubicacion }}
                        </span>
                    </p>
                </div>
            @endif

            <!-- Botones de acción -->
            <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
                @if($esBoleta)
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('boletas-empeno.show', $movimiento) }}"
                           class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Ver Detalle
                        </a>
                        <button
                            onclick="abrirModalUbicacion({{ $movimiento->id }}, '{{ addslashes($movimiento->ubicacion ?? '') }}')"
                            class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                            title="Gestionar ubicación">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Ubicación
                        </button>
                    </div>
                @else
                    <div class="w-full">
                        <a href="{{ route('boletas-empeno.show', $movimiento->boletaEmpeno) }}"
                           class="inline-flex items-center justify-center w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Ver Boleta Origen
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="p-8 text-center">
            <div class="text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay movimientos</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    No se encontraron movimientos con los filtros aplicados.
                </p>
            </div>
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if($movimientos->hasPages())
    <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $movimientos->links() }}
    </div>
@endif
