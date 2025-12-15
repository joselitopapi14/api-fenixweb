<!-- Desktop View -->
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Contrato
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Cliente
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Acciones
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Tipo Movimiento
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Monto
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Productos
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Fecha
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Estado
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Empresa
                </th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
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
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    <!-- Número de Contrato -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $numeroContrato }}
                            @if(!$esBoleta)
                                <span class="text-xs text-blue-600 dark:text-blue-400">(Desempeño)</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $movimiento->created_at->format('d/m/Y') }}
                        </div>
                        @if(!$esBoleta && $movimiento->fecha_abono)
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Abono: {{ \Carbon\Carbon::parse($movimiento->fecha_abono)->format('d/m/Y') }}
                            </div>
                        @endif
                    </td>

                    <!-- Cliente -->
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            @if($cliente)
                                {{ $cliente->razon_social ?: trim($cliente->nombres . ' ' . $cliente->apellidos) }}
                            @else
                                <span class="text-gray-400">Cliente eliminado</span>
                            @endif
                        </div>
                        @if($cliente)
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $cliente->tipoDocumento ? $cliente->tipoDocumento->abreviacion . ': ' : '' }}{{ $cliente->cedula_nit }}
                            </div>
                            @if($cliente->telefono_fijo || $cliente->celular)
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    @if($cliente->telefono_fijo)
                                        Tel. Fijo: {{ $cliente->telefono_fijo }}
                                    @endif
                                    @if($cliente->telefono_fijo && $cliente->celular)
                                        <span class="mx-1">|</span>
                                    @endif
                                    @if($cliente->celular)
                                        Celular: {{ $cliente->celular }}
                                    @endif
                                </div>
                            @endif
                        @endif
                    </td>

                    <!-- Acciones -->
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                        <div class="flex items-center justify-center space-x-3">
                            @if($esBoleta)
                                <a href="{{ route('boletas-empeno.show', $movimiento) }}"
                                   class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                                    Ver Detalle
                                </a>
                                <button
                                    onclick="abrirModalUbicacion({{ $movimiento->id }}, '{{ addslashes($movimiento->ubicacion ?? '') }}')"
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                                    title="Gestionar ubicación">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </button>
                            @else
                                <a href="{{ route('boletas-empeno.show', $movimiento->boletaEmpeno) }}"
                                   class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                                    Ver Boleta Origen
                                </a>
                            @endif
                        </div>
                    </td>

                    <!-- Tipo de Movimiento -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($movimiento->tipoMovimiento)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $movimiento->tipoMovimiento->es_suma ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                                {{ $movimiento->tipoMovimiento->nombre }}
                                {{ $movimiento->tipoMovimiento->es_suma ? '(+)' : '(-)' }}
                            </span>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </td>

                    <!-- Monto -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold {{ $movimiento->tipoMovimiento && $movimiento->tipoMovimiento->es_suma ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            @if($movimiento->tipoMovimiento && !$movimiento->tipoMovimiento->es_suma)
                                -${{ number_format($movimiento->monto, 2) }}
                            @else
                                ${{ number_format($movimiento->monto, 2) }}
                            @endif
                        </div>
                        @if($esBoleta && $movimiento->fecha_vencimiento)
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Vence: {{ $movimiento->fecha_vencimiento->format('d/m/Y') }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                @php
                                    $diasRestantes = now()->diffInDays($movimiento->fecha_vencimiento->startOfDay(), false);
                                @endphp
                                @if($diasRestantes > 0)
                                    Faltan: {{ floor($diasRestantes) }} día(s)
                                @elseif($diasRestantes === 0)
                                    Vence hoy
                                @else
                                    Venció hace {{ abs($diasRestantes) }} día(s)
                                @endif
                            </div>
                        @endif
                        @if($esBoleta && $movimiento->ubicacion)
                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                <span class="inline-flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    {{ Str::limit($movimiento->ubicacion, 30) }}
                                </span>
                            </div>
                        @endif
                        @if(!$esBoleta && $movimiento->observaciones)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ Str::limit($movimiento->observaciones, 50) }}
                            </div>
                        @endif
                    </td>

                    <!-- Productos -->
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            @if($fotoPrenda)
                                <div class="flex-shrink-0 h-12 w-12">
                                    <img class="h-12 w-12 rounded-lg object-cover border border-gray-200 dark:border-gray-600"
                                         src="{{ $fotoPrendaUrl }}"
                                         alt="Foto de la prenda"
                                         onerror="this.style.display='none'"
                                         title="Foto de la prenda empeñada">
                                </div>
                            @endif
                            <div class="max-w-xs">
                                @if($productos && $productos->count() > 0)
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $productos->count() }} producto(s)
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                        @php
                                            $tiposProducto = $productos->map(function($prod) {
                                                return $prod->producto->tipoProducto->nombre ?? 'N/A';
                                            })->unique();
                                        @endphp
                                        {{ $tiposProducto->implode(', ') }}
                                    </div>
                                @else
                                    <span class="text-gray-400">
                                        @if(!$esBoleta)
                                            Ver boleta origen
                                        @else
                                            Sin productos
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>

                    <!-- Fecha -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-white">
                            {{ $movimiento->created_at->format('d/m/Y') }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $movimiento->created_at->format('H:i') }}
                        </div>
                    </td>

                    <!-- Estado -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($esBoleta)
                            @if($movimiento->anulada)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                    Anulada
                                </span>
                            @elseif($movimiento->es_vencida)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                    Vencida
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    Activa
                                </span>
                            @endif
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                {{ ucfirst($movimiento->estado) }}
                            </span>
                        @endif
                    </td>

                    <!-- Empresa -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-white">
                            {{ $empresa->razon_social ?? 'N/A' }}
                        </div>
                        @if($sede)
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $sede->nombre }}
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center">
                        <div class="text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay movimientos</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron movimientos con los filtros aplicados.
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if($movimientos->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $movimientos->links() }}
    </div>
@endif
