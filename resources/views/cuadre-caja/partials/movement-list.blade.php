@if($movimientosPaginados && $movimientosPaginados->count() > 0)
    <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Fecha/Hora
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Contrato/Documento
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Empresa
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Productos
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Monto
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Impacto en Caja
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($movimientosPaginados as $movimiento)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <!-- Fecha/Hora -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <div class="font-medium">
                                    {{ $movimiento->created_at->format('d/m/Y') }}
                                </div>
                                <div class="text-gray-500 dark:text-gray-400">
                                    {{ $movimiento->created_at->format('H:i:s') }}
                                </div>
                            </td>

                            <!-- Tipo de Movimiento -->
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            </td>

                            <!-- Contrato/Documento -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <div class="font-medium">
                                    {{ $movimiento->numero_contrato ?? 'N/A' }}
                                </div>
                                @if($movimiento->tipo_registro === 'cuota')
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Cuota ID: {{ $movimiento->id }}
                                    </div>
                                @endif
                            </td>

                            <!-- Cliente -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                @if($movimiento->cliente)
                                    <div class="font-medium">
                                        {{ $movimiento->cliente->razon_social ?:
                                           trim($movimiento->cliente->nombres . ' ' . $movimiento->cliente->apellidos) }}
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-400">
                                        {{ $movimiento->cliente->cedula_nit }}
                                    </div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">Sin cliente</span>
                                @endif
                            </td>

                            <!-- Empresa -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                @if($movimiento->empresa)
                                    <div class="font-medium">{{ $movimiento->empresa->razon_social }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $movimiento->empresa->razon_social }}
                                    </div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">Sin empresa</span>
                                @endif
                            </td>

                            <!-- Productos -->
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                @if($movimiento->productos && $movimiento->productos->count() > 0)
                                    <div class="space-y-1">
                                        @foreach($movimiento->productos->take(3) as $producto)
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
                                                <span class="text-xs text-gray-600 dark:text-gray-400">
                                                    {{ $producto->producto->descripcion }}
                                                </span>
                                            </div>
                                        @endforeach
                                        @if($movimiento->productos->count() > 3)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                +{{ $movimiento->productos->count() - 3 }} más
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">Sin productos</span>
                                @endif
                            </td>

                            <!-- Monto -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <span class="text-gray-900 dark:text-gray-100">
                                    ${{ number_format($movimiento->monto ?? 0, 0, ',', '.') }}
                                </span>
                            </td>

                            <!-- Impacto en Caja -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($movimiento->signo_movimiento === 'suma')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        SUMA
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        RESTA
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($movimientosPaginados->hasPages())
            <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex justify-between flex-1 sm:hidden">
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

                        @if($movimientosPaginados->hasMorePages())
                            <a href="{{ $movimientosPaginados->nextPageUrl() }}"
                               class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                Siguiente
                            </a>
                        @else
                            <span class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                                Siguiente
                            </span>
                        @endif
                    </div>

                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Mostrando
                                <span class="font-medium">{{ $movimientosPaginados->firstItem() }}</span>
                                a
                                <span class="font-medium">{{ $movimientosPaginados->lastItem() }}</span>
                                de
                                <span class="font-medium">{{ $movimientosPaginados->total() }}</span>
                                resultados
                            </p>
                        </div>

                        <div>
                            {{ $movimientosPaginados->links() }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@else
    <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8">
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
