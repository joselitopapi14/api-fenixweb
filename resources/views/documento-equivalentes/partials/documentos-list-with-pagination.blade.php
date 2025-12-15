<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="table-auto w-full">
            <thead class="text-xs uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-900/20 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                        <div class="font-semibold">Acciones</div>
                    </th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                        <div class="font-semibold">Cliente</div>
                    </th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                        <div class="font-semibold">Concepto</div>
                    </th>
                    @if(auth()->user()->esAdministradorGlobal())
                        <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                            <div class="font-semibold">Empresa</div>
                        </th>
                    @endif
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                        <div class="font-semibold">Monto</div>
                    </th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                        <div class="font-semibold">Descripción</div>
                    </th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                        <div class="font-semibold">Estado</div>
                    </th>
                    <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                        <div class="font-semibold">Fecha</div>
                    </th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($documentos as $documento)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/20">
                        <!-- Acciones -->
                        <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                            <div class="flex items-center space-x-1">
                                <!-- Ver -->
                                <a href="{{ route('documento-equivalentes.show', $documento) }}"
                                   class="inline-flex items-center px-2 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition-colors duration-200"
                                   title="Ver detalles">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>

                                <!-- PDF -->
                                <a href="{{ route('documento-equivalentes.pdf', $documento) }}"
                                   class="inline-flex items-center px-2 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-md transition-colors duration-200"
                                   title="Descargar PDF"
                                   target="_blank">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </a>
                            </div>
                        </td>

                        <!-- Cliente -->
                        <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="font-medium text-gray-800 dark:text-gray-100">
                                    {{ $documento->cliente->nombre_completo }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $documento->cliente->numero_documento }}
                                </div>
                            </div>
                        </td>

                        <!-- Concepto -->
                        <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                            <div class="font-medium text-gray-800 dark:text-gray-100">
                                {{ $documento->concepto->nombre }}
                            </div>
                        </td>

                        @if(auth()->user()->esAdministradorGlobal())
                            <!-- Empresa -->
                            <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-800 dark:text-gray-100">
                                    {{ $documento->empresa->razon_social }}
                                </div>
                            </td>
                        @endif

                        <!-- Monto -->
                        <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                            <div class="text-right font-medium text-gray-800 dark:text-gray-100">
                                ${{ number_format($documento->monto, 0, ',', '.') }}
                            </div>
                        </td>

                        <!-- Descripción -->
                        <td class="px-2 first:pl-5 last:pr-5 py-3">
                            <div class="text-gray-800 dark:text-gray-100 max-w-xs truncate" title="{{ $documento->descripcion }}">
                                {{ $documento->descripcion }}
                            </div>
                        </td>

                        <!-- Estado -->
                        <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                            @if($documento->estado === 'activo')
                                <div class="inline-flex items-center text-xs bg-emerald-100 dark:bg-emerald-400/30 text-emerald-600 dark:text-emerald-400 rounded-full text-center px-2.5 py-1">
                                    <svg class="w-2 h-2 mr-1" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z" fill="currentColor" />
                                    </svg>
                                    <span>Activo</span>
                                </div>
                            @else
                                <div class="inline-flex items-center text-xs bg-red-100 dark:bg-red-400/30 text-red-600 dark:text-red-400 rounded-full text-center px-2.5 py-1">
                                    <svg class="w-2 h-2 mr-1" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9.707 2.293a1 1 0 0 1 0 1.414L7.414 6l2.293 2.293a1 1 0 1 1-1.414 1.414L6 7.414 3.707 9.707a1 1 0 0 1-1.414-1.414L4.586 6 2.293 3.707a1 1 0 0 1 1.414-1.414L6 4.586l2.293-2.293a1 1 0 0 1 1.414 0Z" fill="currentColor" />
                                    </svg>
                                    <span>Anulado</span>
                                </div>
                            @endif
                        </td>

                        <!-- Fecha -->
                        <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                            <div class="text-gray-800 dark:text-gray-100">
                                {{ $documento->fecha_documento ? $documento->fecha_documento->format('d/m/Y') : 'Sin fecha' }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Creado: {{ $documento->created_at->format('d/m/Y H:i') }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->esAdministradorGlobal() ? '8' : '7' }}" class="px-2 first:pl-5 last:pr-5 py-8 text-center">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                @if(request()->hasAny(['search', 'empresa_id', 'concepto_id', 'estado']))
                                    <p class="text-lg font-medium mb-2">No se encontraron documentos equivalentes</p>
                                    <p>Intenta ajustar los filtros de búsqueda</p>
                                @else
                                    <p class="text-lg font-medium mb-2">No hay documentos equivalentes registrados</p>
                                    @can('registros.create')
                                        <p>
                                            <a href="{{ route('documento-equivalentes.create') }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                                Crear el primer documento equivalente
                                            </a>
                                        </p>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($documentos->hasPages())
        <div class="border-t border-gray-200 dark:border-gray-700 px-5 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-4 sm:mb-0">
                    Mostrando {{ $documentos->firstItem() }} a {{ $documentos->lastItem() }} de {{ $documentos->total() }} resultados
                </div>
                <nav class="flex items-center space-x-1">
                    @if ($documentos->onFirstPage())
                        <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:bg-gray-800 dark:border-gray-600">
                            « Anterior
                        </span>
                    @else
                        <a href="{{ $documentos->previousPageUrl() }}" data-ajax-pagination class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-400 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300">
                            « Anterior
                        </a>
                    @endif

                    @foreach ($documentos->getUrlRange(1, $documentos->lastPage()) as $page => $url)
                        @if ($page == $documentos->currentPage())
                            <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-indigo-500 border border-indigo-500 cursor-default leading-5 rounded-md">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" data-ajax-pagination class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-400 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if ($documentos->hasMorePages())
                        <a href="{{ $documentos->nextPageUrl() }}" data-ajax-pagination class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-400 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300">
                            Siguiente »
                        </a>
                    @else
                        <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:bg-gray-800 dark:border-gray-600">
                            Siguiente »
                        </span>
                    @endif
                </nav>
            </div>
        </div>
    @endif
</div>
