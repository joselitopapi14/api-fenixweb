<div class="space-y-4">
    @if($boletas->count() > 0)
        @foreach($boletas as $boleta)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $boleta->numero_contrato }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $boleta->productos->count() }} productos</p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        @php
                            $estadoClasses = [
                                'activa' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                'pagada' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
                                'vencida' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                'cancelada' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $estadoClasses[$boleta->estado] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400' }}">
                            {{ ucfirst($boleta->estado) }}
                        </span>
                        @if($boleta->anulada)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                ANULADA
                            </span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Cliente:</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $boleta->cliente->nombre_completo ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $boleta->cliente->documento_completo ?? 'N/A' }}</p>
                        @if($boleta->cliente->telefono_fijo)
                            <p class="text-xs text-gray-500 dark:text-gray-400">Teléfono fijo: {{ $boleta->cliente->telefono_fijo }}</p>
                        @endif
                        @if($boleta->cliente->celular)
                            <p class="text-xs text-gray-500 dark:text-gray-400">Celular: {{ $boleta->cliente->celular }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Valores:</p>
                        <p class="font-medium text-gray-900 dark:text-white">Compra: ${{ number_format($boleta->monto_prestamo, 2) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Venta: ${{ number_format($boleta->valor_total_venta, 2) }}</p>
                    </div>
                </div>

                @if(auth()->user()->esAdministradorGlobal())
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-600 dark:text-gray-400">Empresa:</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $boleta->empresa->razon_social ?? 'N/A' }}</p>
                    @if($boleta->sede)
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $boleta->sede->nombre }}</p>
                    @endif
                </div>
                @endif

                <div class="mt-4 flex justify-between items-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $boleta->created_at->format('d/m/Y H:i') }}
                        @if($boleta->fecha_vencimiento)
                            <br>Vence: {{ $boleta->fecha_vencimiento->format('d/m/Y') }}
                        @endif
                    </p>
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('boletas-empeno.show', $boleta) }}"
                           class="inline-flex items-center p-1.5 text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                           title="Ver detalles">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </a>
                        @can('registros.edit')
                        <a href="{{ route('boletas-empeno.upload-foto', $boleta) }}"
                           class="inline-flex items-center p-1.5 text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                           title="Subir foto de prenda">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </a>
                        @endcan
                        <a href="{{ route('boletas-empeno.pdf', $boleta) }}" target="_blank"
                           class="inline-flex items-center p-1.5 text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                           title="Generar PDF">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v6m0 0l-3-3m3 3l3-3M21 12v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6" />
                            </svg>
                        </a>
                        @if(!$boleta->anulada)
                            @can('registros.edit')
                                @php
                                    $noPermiteAnular = $boleta->estado === 'pagada' || ($boleta->cuotas ? $boleta->cuotas->count() > 0 : $boleta->cuotas()->exists());
                                    $tooltip = $boleta->estado === 'pagada' ? 'No se puede anular: la boleta está pagada' : ($boleta->cuotas && $boleta->cuotas->count() > 0 ? 'No se puede anular: la boleta tiene cuotas registradas' : 'Anular boleta');
                                @endphp

                                @if($noPermiteAnular)
                                    <button disabled class="inline-flex items-center p-1.5 text-gray-400 cursor-not-allowed" title="{{ $tooltip }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                @else
                                    <button onclick="anularBoleta({{ $boleta->id }}, '{{ $boleta->numero_contrato }}')"
                                           class="inline-flex items-center p-1.5 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                           title="Anular boleta">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                @endif
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Mobile Pagination -->
        <div class="px-4 py-3">
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
                   class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
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
