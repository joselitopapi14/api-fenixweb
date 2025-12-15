<div class="p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Movimientos de Inventario
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                ({{ number_format($movimientos->total()) }} total{{ $movimientos->total() !== 1 ? 'es' : '' }})
            </span>
        </h3>
    </div>

    @if($movimientos->count() > 0)
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Número</th>
                        <th scope="col" class="px-4 py-3">Fecha</th>
                        <th scope="col" class="px-4 py-3">Empresa</th>
                        <th scope="col" class="px-4 py-3">Tipo</th>
                        <th scope="col" class="px-4 py-3">Usuario</th>
                        <th scope="col" class="px-4 py-3">Productos</th>
                        <th scope="col" class="px-4 py-3">Estado</th>
                        <th scope="col" class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                    @foreach($movimientos as $movimiento)
                        <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200 {{ ($movimiento->anulado || $movimiento->anulada) ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <!-- Número de Contrato -->
                            <td class="px-4 py-4">
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-900 dark:text-white font-mono {{ ($movimiento->anulado || $movimiento->anulada) ? 'line-through text-red-600 dark:text-red-400' : '' }}">
                                        {{ $movimiento->numero_contrato }}
                                    </span>
                                    @if($movimiento->observaciones)
                                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 {{ ($movimiento->anulado || $movimiento->anulada) ? 'line-through' : '' }}">
                                            {{ Str::limit($movimiento->observaciones, 30) }}
                                        </span>
                                    @endif
                                    @if(($movimiento->anulado || $movimiento->anulada) && ($movimiento->razon_anulacion ?? $movimiento->motivo_anulacion))
                                        <span class="text-xs text-red-600 dark:text-red-400 mt-1 font-medium">
                                            Anulado: {{ Str::limit($movimiento->razon_anulacion ?? $movimiento->motivo_anulacion, 30) }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Fecha -->
                            <td class="px-4 py-4">
                                <div class="flex flex-col">
                                    <span class="text-gray-900 dark:text-white">
                                        @if($movimiento->fecha_movimiento)
                                            {{ \Carbon\Carbon::parse($movimiento->fecha_movimiento)->format('d/m/Y') }}
                                        @elseif($movimiento->fecha_prestamo)
                                            {{ \Carbon\Carbon::parse($movimiento->fecha_prestamo)->format('d/m/Y') }}
                                        @elseif($movimiento->fecha_abono)
                                            {{ \Carbon\Carbon::parse($movimiento->fecha_abono)->format('d/m/Y') }}
                                        @else
                                            {{ $movimiento->created_at->format('d/m/Y') }}
                                        @endif
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $movimiento->created_at->format('H:i') }}
                                    </span>
                                </div>
                            </td>

                            <!-- Empresa -->
                            <td class="px-4 py-4">
                                <div class="flex flex-col">
                                    <span class="text-gray-900 dark:text-white">
                                        {{ $movimiento->empresa->razon_social ?? 'N/A' }}
                                    </span>
                                    @if($movimiento->sede)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $movimiento->sede->nombre }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Tipo de Movimiento -->
                            <td class="px-4 py-4">
                                @if($movimiento->tipoMovimiento)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md flex items-center justify-center {{ $movimiento->tipoMovimiento->es_suma ? 'bg-green-500' : 'bg-red-500' }}">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($movimiento->tipoMovimiento->es_suma)
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                @endif
                                            </svg>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-gray-900 dark:text-white text-sm">
                                                {{ $movimiento->tipoMovimiento->nombre }}
                                            </span>
                                            <span class="text-xs {{ $movimiento->tipoMovimiento->es_suma ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ $movimiento->tipoMovimiento->es_suma ? 'Entrada (+)' : 'Salida (-)' }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">N/A</span>
                                @endif
                            </td>

                            <!-- Usuario -->
                            <td class="px-4 py-4">
                                <span class="text-gray-900 dark:text-white">
                                    {{ $movimiento->usuario->name ?? 'N/A' }}
                                </span>
                            </td>

                            <!-- Productos -->
                            <td class="px-4 py-4">
                                @php
                                    // Obtener productos según el tipo de registro
                                    $productos = null;
                                    if ($movimiento->tipo_registro === 'empeno') {
                                        $productos = $movimiento->productos;
                                    } elseif ($movimiento->tipo_registro === 'desempeno') {
                                        $productos = $movimiento->boletaEmpeno?->productos;
                                    } elseif ($movimiento->tipo_registro === 'movimiento_inventario') {
                                        $productos = $movimiento->productos;
                                    }
                                @endphp

                                @if($productos && $productos->count() > 0)
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                            {{ $productos->count() }} producto(s)
                                        </span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-32">
                                            @php
                                                $tiposProducto = $productos->map(function($prod) {
                                                    return $prod->producto->tipoProducto->nombre ?? 'N/A';
                                                })->unique();
                                            @endphp
                                            {{ $tiposProducto->implode(', ') }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">
                                        @if($movimiento->tipo_registro === 'desempeno')
                                            Ver boleta origen
                                        @else
                                            Sin productos
                                        @endif
                                    </span>
                                @endif
                            </td>

                            <!-- Estado -->
                            <td class="px-4 py-4">
                                @if($movimiento->anulado || $movimiento->anulada)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                        Anulado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Activo
                                    </span>
                                @endif
                            </td>

                            <!-- Acciones -->
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center space-x-2">
                                    <!-- Ver -->
                                    <a href="{{ route('movimientos-inventario.show', $movimiento) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-lg transition-colors duration-200"
                                       title="Ver detalles">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>

                                    <!-- Anular -->
                                    @if(!($movimiento->anulado || $movimiento->anulada) && auth()->user()->can('registros.edit'))
                                        <button onclick="abrirModalAnular({{ $movimiento->id }}, '{{ $movimiento->numero_contrato }}')"
                                                class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-lg transition-colors duration-200"
                                                title="Anular movimiento">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Grid -->
        <div class="block md:hidden space-y-4">
            @foreach($movimientos as $movimiento)
                <div class="rounded-lg p-4 border {{ ($movimiento->anulado || $movimiento->anulada) ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600' }}">
                    <!-- Header with number and date -->
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white font-mono {{ ($movimiento->anulado || $movimiento->anulada) ? 'line-through text-red-600 dark:text-red-400' : '' }}">
                                {{ $movimiento->numero_contrato }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 {{ ($movimiento->anulado || $movimiento->anulada) ? 'line-through' : '' }}">
                                @if($movimiento->fecha_movimiento)
                                    {{ \Carbon\Carbon::parse($movimiento->fecha_movimiento)->format('d/m/Y') }}
                                @elseif($movimiento->fecha_prestamo)
                                    {{ \Carbon\Carbon::parse($movimiento->fecha_prestamo)->format('d/m/Y') }}
                                @elseif($movimiento->fecha_abono)
                                    {{ \Carbon\Carbon::parse($movimiento->fecha_abono)->format('d/m/Y') }}
                                @else
                                    {{ $movimiento->created_at->format('d/m/Y') }}
                                @endif
                                • {{ $movimiento->created_at->format('H:i') }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($movimiento->anulado || $movimiento->anulada)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                    Anulado
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    Activo
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Info Grid -->
                    <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Empresa:</span>
                            <p class="text-gray-900 dark:text-white font-medium">{{ $movimiento->empresa->razon_social ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Usuario:</span>
                            <p class="text-gray-900 dark:text-white font-medium">{{ $movimiento->usuario->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Tipo:</span>
                            @if($movimiento->tipoMovimiento)
                                <div class="flex items-center gap-1 mt-1">
                                    <div class="w-4 h-4 rounded-sm flex items-center justify-center {{ $movimiento->tipoMovimiento->es_suma ? 'bg-green-500' : 'bg-red-500' }}">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($movimiento->tipoMovimiento->es_suma)
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            @endif
                                        </svg>
                                    </div>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ $movimiento->tipoMovimiento->nombre }}</span>
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400">N/A</p>
                            @endif
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Productos:</span>
                            @php
                                // Obtener productos según el tipo de registro (igual que desktop)
                                $productos = null;
                                if ($movimiento->tipo_registro === 'empeno') {
                                    $productos = $movimiento->productos;
                                } elseif ($movimiento->tipo_registro === 'desempeno') {
                                    $productos = $movimiento->boletaEmpeno?->productos;
                                } elseif ($movimiento->tipo_registro === 'movimiento_inventario') {
                                    $productos = $movimiento->productos;
                                }
                            @endphp

                            @if($productos && $productos->count() > 0)
                                <p class="text-gray-900 dark:text-white font-medium">{{ $productos->count() }} producto(s)</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    @php
                                        $tiposProducto = $productos->map(function($prod) {
                                            return $prod->producto->tipoProducto->nombre ?? 'N/A';
                                        })->unique();
                                    @endphp
                                    {{ $tiposProducto->implode(', ') }}
                                </p>
                            @else
                                <p class="text-gray-500 dark:text-gray-400">
                                    @if($movimiento->tipo_registro === 'desempeno')
                                        Ver boleta origen
                                    @else
                                        Sin productos
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>

                    @if($movimiento->observaciones)
                        <div class="mb-3">
                            <span class="text-gray-500 dark:text-gray-400 text-xs">Observaciones:</span>
                            <p class="text-gray-900 dark:text-white text-sm {{ ($movimiento->anulado || $movimiento->anulada) ? 'line-through' : '' }}">{{ Str::limit($movimiento->observaciones, 80) }}</p>
                        </div>
                    @endif

                    @if(($movimiento->anulado || $movimiento->anulada) && ($movimiento->razon_anulacion ?? $movimiento->motivo_anulacion))
                        <div class="mb-3">
                            <span class="text-red-500 dark:text-red-400 text-xs font-medium">Razón de anulación:</span>
                            <p class="text-red-600 dark:text-red-400 text-sm font-medium">{{ Str::limit($movimiento->razon_anulacion ?? $movimiento->motivo_anulacion, 80) }}</p>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-2">
                        <a href="{{ route('movimientos-inventario.show', $movimiento) }}"
                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-md transition-colors duration-200">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Ver
                        </a>

                        @if(!($movimiento->anulado || $movimiento->anulada) && auth()->user()->can('registros.edit'))
                            <button onclick="abrirModalAnular({{ $movimiento->id }}, '{{ $movimiento->numero_contrato }}')"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-md transition-colors duration-200">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Anular
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Mostrando {{ $movimientos->firstItem() ?? 0 }} a {{ $movimientos->lastItem() ?? 0 }} de {{ number_format($movimientos->total()) }} movimientos
            </div>

            <div class="flex items-center space-x-2">
                {{ $movimientos->links() }}
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No hay movimientos de inventario</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comienza creando un nuevo movimiento de inventario.</p>
            @can('registros.create')
            <div class="mt-6">
                <a href="{{ route('movimientos-inventario.create') }}"
                   class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo Movimiento
                </a>
            </div>
            @endcan
        </div>
    @endif
</div>
