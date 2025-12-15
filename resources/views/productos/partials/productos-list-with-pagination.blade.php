<!-- Desktop View -->
<div class="hidden lg:block bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full w-max divide-y divide-gray-200 dark:divide-gray-700" style="min-width: 900px;">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[200px]">
                        Producto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[120px]">
                        Tipo
                    </th>
                    <th scope="col" class="relative px-6 py-3 whitespace-nowrap min-w-[120px]">
                        <span class="sr-only">Acciones</span>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[100px]">
                        Medida
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[100px]">
                        Precio Venta
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[100px]">
                        Impuestos
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[120px]">
                        Tipo de Oro
                    </th>
                    @if(auth()->user()->esAdministradorGlobal())
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[150px]">
                        Empresa
                    </th>
                    @endif
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[100px]">
                        Fecha
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($productos as $producto)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-12 w-12 mr-4">
                                    <img class="h-12 w-12 rounded-lg object-cover border border-gray-200 dark:border-gray-600"
                                         src="{{ $producto->imagen_url ?: asset('images/producto-default.svg') }}"
                                         alt="{{ $producto->nombre }}"
                                         onerror="this.src='{{ asset('images/producto-default.svg') }}'">
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $producto->nombre }}
                                    </div>
                                    @if($producto->descripcion)
                                        <div class="text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                            {{ $producto->descripcion }}
                                        </div>
                                    @endif
                                    @if($producto->codigo_barras)
                                        <div class="text-xs text-gray-400 dark:text-gray-500">
                                            Código: {{ $producto->codigo_barras }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $producto->tipoProducto->id == 1
                                    ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
                                    : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' }}">
                                {{ $producto->tipoProducto->nombre }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('productos.show', $producto) }}"
                                   class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 transition-colors duration-200"
                                   title="Ver detalles">
                                    <x-icons.eye class="h-5 w-5" />
                                </a>
                                @can('registros.edit')
                                <a href="{{ route('productos.edit', $producto) }}"
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200"
                                   title="Editar">
                                    <x-icons.pencil class="h-5 w-5" />
                                </a>
                                @endcan
                                @can('registros.delete')
                                <button onclick="confirmDelete('{{ $producto->id }}', '{{ $producto->nombre }}')"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
                                        title="Eliminar">
                                    <x-icons.trash class="h-5 w-5" />
                                </button>
                                @endcan
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            @if($producto->tipoMedida)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    {{ $producto->tipoMedida->abreviatura }}
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            @if($producto->precio_venta)
                                <span class="font-semibold text-green-600 dark:text-green-400">
                                    ${{ number_format($producto->precio_venta, 2) }}
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            @if($producto->impuestos->count() > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($producto->impuestos->take(2) as $impuesto)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                            {{ $impuesto->nombre }}
                                        </span>
                                    @endforeach
                                    @if($producto->impuestos->count() > 2)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            +{{ $producto->impuestos->count() - 2 }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            @if($producto->tipoOro)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-400">
                                    {{ $producto->tipoOro->nombre }}
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        @if(auth()->user()->esAdministradorGlobal())
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            @if($producto->empresa)
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $producto->empresa->razon_social }}</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400">
                                    Global
                                </span>
                            @endif
                        </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $producto->created_at ? $producto->created_at->format('d/m/Y') : 'N/A' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->esAdministradorGlobal() ? '9' : '8' }}" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icons.box class="h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No hay productos</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Comienza creando tu primer producto.</p>
                                @can('registros.create')
                                <a href="{{ route('productos.create') }}"
                                   class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                                    <x-icons.plus class="w-4 h-4 mr-2" />
                                    Crear Primer Producto
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile View -->
<div class="lg:hidden space-y-4">
    @forelse($productos as $producto)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <img class="h-16 w-16 rounded-lg object-cover border border-gray-200 dark:border-gray-600"
                         src="{{ $producto->imagen_url ?: asset('images/producto-default.svg') }}"
                         alt="{{ $producto->nombre }}"
                         onerror="this.src='{{ asset('images/producto-default.svg') }}'">
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                {{ $producto->nombre }}
                            </h3>
                            @if($producto->descripcion)
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                    {{ $producto->descripcion }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $producto->tipoProducto->id == 1
                                ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
                                : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' }}">
                            {{ $producto->tipoProducto->nombre }}
                        </span>

                        @if($producto->tipoOro)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-400">
                                {{ $producto->tipoOro->nombre }}
                            </span>
                        @endif

                        @if($producto->tipoMedida)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                {{ $producto->tipoMedida->nombre }}
                            </span>
                        @endif

                        @if($producto->precio_venta)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                ${{ number_format($producto->precio_venta, 2) }}
                            </span>
                        @endif

                        @if(auth()->user()->esAdministradorGlobal())
                            @if($producto->empresa)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">
                                    {{ $producto->empresa->razon_social }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400">
                                    Global
                                </span>
                            @endif
                        @endif
                    </div>

                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $producto->created_at ? $producto->created_at->format('d/m/Y') : 'N/A' }}
                        </span>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('productos.show', $producto) }}"
                               class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 transition-colors duration-200">
                                <x-icons.eye class="h-5 w-5" />
                            </a>
                            @can('registros.edit')
                            <a href="{{ route('productos.edit', $producto) }}"
                               class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
                                <x-icons.pencil class="h-5 w-5" />
                            </a>
                            @endcan
                            @can('registros.delete')
                            <button onclick="confirmDelete('{{ $producto->id }}', '{{ $producto->nombre }}')"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200">
                                <x-icons.trash class="h-5 w-5" />
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
            <x-icons.box class="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No hay productos</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Comienza creando tu primer producto.</p>
            @can('registros.create')
            <a href="{{ route('productos.create') }}"
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200">
                <x-icons.plus class="w-4 h-4 mr-2" />
                Crear Primer Producto
            </a>
            @endcan
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if($productos->hasPages())
    <div class="mt-6">
        {{ $productos->links() }}
    </div>
@endif

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20">
                <x-icons.trash class="h-6 w-6 text-red-600 dark:text-red-400" />
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-4">Eliminar Producto</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    ¿Estás seguro de que deseas eliminar el producto "<span id="deleteProductoName" class="font-semibold"></span>"?
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <form id="deleteForm" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Eliminar
                    </button>
                </form>
                <button onclick="closeDeleteModal()"
                        class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-gray-100 text-base font-medium rounded-md w-24 hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(productoId, productoNombre) {
        document.getElementById('deleteProductoName').textContent = productoNombre;
        document.getElementById('deleteForm').action = `/productos/${productoId}`;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
</script>
