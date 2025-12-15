<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
    @if($boletas->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Contrato
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Acciones
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Valores
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Fecha
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Estado
                        </th>
                        @if(auth()->user()->esAdministradorGlobal())
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Empresa
                        </th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($boletas as $boleta)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <!-- Contrato -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $boleta->numero_contrato }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $boleta->productos->count() }} productos
                                    </div>
                                </div>
                            </td>
                            <!-- Acciones -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('boletas-empeno.show', $boleta) }}"
                                       class="inline-flex items-center p-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200"
                                       title="Ver detalles">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @can('registros.edit')
                                    <a href="{{ route('boletas-empeno.upload-foto', $boleta) }}"
                                       class="inline-flex items-center p-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-blue-700 dark:text-blue-400 bg-white dark:bg-gray-800 hover:bg-blue-50 dark:hover:bg-blue-900/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                                       title="Subir foto de prenda">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    <a href="{{ route('boletas-empeno.pdf', $boleta) }}" target="_blank"
                                       class="inline-flex items-center p-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200"
                                       title="Generar PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v6m0 0l-3-3m3 3l3-3M21 12v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6" />
                                        </svg>
                                    </a>
                                    @if(!$boleta->anulada)
                                        @can('registros.edit')
                                            @php
                                                // Condiciones que impiden la anulación
                                                $noPermiteAnular = $boleta->estado === 'pagada' || ($boleta->cuotas ? $boleta->cuotas->count() > 0 : $boleta->cuotas()->exists());
                                                $tooltip = $boleta->estado === 'pagada' ? 'No se puede anular: la boleta está pagada' : ($boleta->cuotas && $boleta->cuotas->count() > 0 ? 'No se puede anular: la boleta tiene cuotas registradas' : 'Anular boleta');
                                            @endphp

                                            @if($noPermiteAnular)
                                                <button disabled
                                                        class="inline-flex items-center p-1.5 border border-gray-200 rounded-md text-gray-400 bg-white dark:bg-gray-800 cursor-not-allowed"
                                                        title="{{ $tooltip }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <button onclick="anularBoleta({{ $boleta->id }}, '{{ $boleta->numero_contrato }}')"
                                                       class="inline-flex items-center p-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-red-700 dark:text-red-400 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                                                       title="Anular boleta">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        @endcan
                                    @else
                                        <span class="inline-flex items-center p-1.5 text-gray-400 dark:text-gray-600" title="Boleta anulada">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <!-- Valores -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <div>Compra: ${{ number_format($boleta->monto_prestamo, 2) }}</div>
                                    <div class="text-gray-500 dark:text-gray-400">Venta: ${{ number_format($boleta->valor_total_venta, 2) }}</div>
                                </div>
                            </td>
                            <!-- Cliente -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $boleta->cliente->nombre_completo ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $boleta->cliente->documento_completo ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            Teléfono fijo: {{ $boleta->cliente->telefono_fijo ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            Celular: {{ $boleta->cliente->celular ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <!-- Fecha -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <div>{{ $boleta->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs">{{ $boleta->created_at->format('H:i') }}</div>
                            </td>
                            <!-- Estado -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $estadoClasses = [
                                        'activa' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                        'pagada' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
                                        'vencida' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                        'cancelada' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
                                    ];
                                @endphp
                                <div class="flex flex-col space-y-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $estadoClasses[$boleta->estado] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400' }}">
                                        {{ ucfirst($boleta->estado) }}
                                    </span>
                                    @if($boleta->anulada)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                            ANULADA
                                        </span>
                                    @endif
                                </div>
                                @if($boleta->fecha_vencimiento)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Vence: {{ $boleta->fecha_vencimiento->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>
                            <!-- Empresa (al final, solo para admin global) -->
                            @if(auth()->user()->esAdministradorGlobal())
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $boleta->empresa->razon_social ?? 'N/A' }}</div>
                                @if($boleta->sede)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $boleta->sede->nombre }}</div>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $boletas->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No hay boletas de empeño</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comienza creando una nueva boleta de empeño.</p>
            @can('registros.create')
            <div class="mt-6">
                <a href="{{ route('boletas-empeno.create') }}"
                   class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nueva Boleta
                </a>
            </div>
            @endcan
        </div>
    @endif
</div>
