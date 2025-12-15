<div data-mobile-grid class="space-y-3">
    @if($clientes->count() > 0)
        @foreach($clientes as $cliente)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start space-x-3">
                    <!-- Avatar -->
                    <div class="flex-shrink-0">
                        <img class="h-12 w-12 rounded-full object-cover"
                             src="{{ $cliente->foto_url }}"
                             alt="{{ $cliente->nombre_completo }}">
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $cliente->nombre_completo }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $cliente->tipoDocumento->abreviacion ?? 'N/A' }}: {{ $cliente->documento_completo }}
                                </p>
                                @if($cliente->email)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $cliente->email }}
                                    </p>
                                @endif
                                <div class="flex items-center mt-1">
                                    <x-icons.phone class="w-3 h-3 mr-1 text-gray-400 flex-shrink-0" />
                                    <span class="text-xs text-gray-600 dark:text-gray-300">
                                        {{ $cliente->celular }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex space-x-1 ml-2">
                                <a href="{{ route('clientes.show', ['empresa' => $empresa, 'cliente' => $cliente]) }}"
                                   class="p-1 text-primary-600 hover:text-primary-800 dark:text-primary-400">
                                    <x-icons.eye class="w-4 h-4" />
                                </a>
                                <a href="{{ route('clientes.edit', ['empresa' => $empresa, 'cliente' => $cliente]) }}"
                                   class="p-1 text-yellow-600 hover:text-yellow-800 dark:text-yellow-400">
                                    <x-icons.pencil class="w-4 h-4" />
                                </a>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="flex items-center mt-2">
                            <x-icons.map-pin class="w-3 h-3 mr-1 text-gray-400 flex-shrink-0" />
                            <span class="text-xs text-gray-600 dark:text-gray-300 truncate">
                                {{ $cliente->barrio->nombre ?? 'N/A' }}, {{ $cliente->municipio->name ?? 'N/A' }}
                            </span>
                        </div>

                        <!-- Social Networks -->
                        @if($cliente->redesSociales->count() > 0)
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($cliente->redesSociales->take(2) as $redSocial)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ $redSocial->nombre }}
                                    </span>
                                @endforeach
                                @if($cliente->redesSociales->count() > 2)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        +{{ $cliente->redesSociales->count() - 2 }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        <!-- Date -->
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            Registrado: {{ $cliente->created_at->format('d/m/Y') }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Mobile Pagination -->
        @if($clientes->hasPages())
            <div class="mt-4">
                {{ $clientes->links() }}
            </div>
        @endif

    @else
        <!-- Mobile Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
            <x-icons.users class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Sin clientes</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">No se encontraron clientes.</p>
            <a href="{{ route('clientes.create', $empresa) }}"
               class="inline-flex items-center px-3 py-2 bg-primary-600 text-white text-sm font-semibold rounded-lg">
                <x-icons.plus class="w-4 h-4 mr-2" />
                Crear Cliente
            </a>
        </div>
    @endif
</div>
