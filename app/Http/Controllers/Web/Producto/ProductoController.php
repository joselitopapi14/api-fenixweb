<?php

namespace App\Http\Controllers\Web\Producto;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\TipoProducto;
use App\Models\TipoOro;
use App\Models\Empresa;
use App\Models\TipoMedida;
use App\Models\Impuesto;
use App\Exports\ProductosExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $query = Producto::with(['tipoProducto', 'tipoOro', 'empresa', 'tipoMedida', 'impuestos']);

            // Filtrar por empresa si no es admin global
            if (!$user->esAdministradorGlobal()) {
                $empresasIds = $user->empresasActivas()->pluck('empresas.id');
                $query->where(function ($q) use ($empresasIds) {
                    $q->whereIn('empresa_id', $empresasIds)
                      ->orWhereNull('empresa_id'); // Incluir productos globales
                });
            }

            // Filtros de búsqueda
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = strtolower($request->search);
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhere('codigo_barras', 'like', "%{$searchTerm}%");
                });
            }

            // Filtro por tipo de producto
            if ($request->filled('tipo_producto_id')) {
                if ($request->tipo_producto_id === 'global') {
                    $query->whereNull('tipo_producto_id');
                } else {
                    $query->where('tipo_producto_id', $request->tipo_producto_id);
                }
            }

            // Filtro por tipo de oro
            if ($request->filled('tipo_oro_id')) {
                $query->where('tipo_oro_id', $request->tipo_oro_id);
            }

            // Filtro por empresa (para admin global)
            if ($request->filled('empresa_id')) {
                if ($request->empresa_id === 'global') {
                    $query->whereNull('empresa_id');
                } else {
                    $query->where('empresa_id', $request->empresa_id);
                }
            }

            // Filtro por código de barras
            if ($request->filled('codigo_barras')) {
                $query->where('codigo_barras', 'like', '%' . $request->codigo_barras . '%');
            }

            // Filtros de precio de venta
            if ($request->filled('precio_venta_min')) {
                $query->where('precio_venta', '>=', $request->precio_venta_min);
            }

            if ($request->filled('precio_venta_max')) {
                $query->where('precio_venta', '<=', $request->precio_venta_max);
            }

            // Filtros de precio de compra
            if ($request->filled('precio_compra_min')) {
                $query->where('precio_compra', '>=', $request->precio_compra_min);
            }

            if ($request->filled('precio_compra_max')) {
                $query->where('precio_compra', '<=', $request->precio_compra_max);
            }

            // Filtro por fechas de creación
            if ($request->filled('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            $sortBy = $request->get('sort', 'nombre_asc');
            switch ($sortBy) {
                case 'nombre_desc':
                    $query->orderBy('nombre', 'desc');
                    break;
                case 'precio_venta_asc':
                    $query->orderBy('precio_venta', 'asc');
                    break;
                case 'precio_venta_desc':
                    $query->orderBy('precio_venta', 'desc');
                    break;
                case 'precio_compra_asc':
                    $query->orderBy('precio_compra', 'asc');
                    break;
                case 'precio_compra_desc':
                    $query->orderBy('precio_compra', 'desc');
                    break;
                case 'created_at_desc':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'created_at_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
                default:
                    $query->orderBy('nombre', 'asc');
                    break;
            }

            $productos = $query->paginate(10);

            // Para admin global, obtener lista de empresas para el filtro
            $empresas = null;
            $tiposProducto = null;
            $tiposOro = null;

            if ($user->esAdministradorGlobal()) {
                $empresas = Empresa::activas()->orderBy('razon_social')->get();
            }

            // Obtener tipos de producto y tipos de oro para filtros
            $tiposProducto = TipoProducto::orderBy('nombre')->get();
            $tiposOro = TipoOro::orderBy('nombre')->get();

            if ($request->ajax()) {
                return view('productos.partials.productos-list-with-pagination', compact('productos'))->render();
            }

            // Calcular estadísticas
            $statsQuery = Producto::query();

            // Aplicar los mismos filtros de empresa que en la consulta principal
            if (!$user->esAdministradorGlobal()) {
                $empresasIds = $user->empresasActivas()->pluck('empresas.id');
                $statsQuery->where(function ($q) use ($empresasIds) {
                    $q->whereIn('empresa_id', $empresasIds)
                      ->orWhereNull('empresa_id'); // Incluir productos globales
                });
            }

            $totalProductos = $statsQuery->count();
            $productosEsteAno = (clone $statsQuery)
                ->whereYear('created_at', now()->year)
                ->count();
            $productosEsteMes = (clone $statsQuery)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            return view('productos.index', compact('productos', 'empresas', 'tiposProducto', 'tiposOro', 'totalProductos', 'productosEsteAno', 'productosEsteMes'));
        } catch (Exception $e) {
            Log::error('Error al listar productos: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar productos.'], 500);
            }
            return redirect()->back()->with('error', 'Error al cargar productos.');
        }
    }

    public function create()
    {
        $user = auth()->user();
        $empresas = null;
        $tiposProducto = TipoProducto::orderBy('nombre')->get();
        $tiposOro = TipoOro::orderBy('nombre')->get();
        $tiposMedida = TipoMedida::activos()->orderBy('nombre')->get();
        $impuestos = Impuesto::orderBy('name')->get();

        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::activas()->orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        return view('productos.create', compact('empresas', 'tiposProducto', 'tiposOro', 'tiposMedida', 'impuestos'));
    }

    public function store(Request $request)
    {
        Log::info('Intento de crear producto', [
            'user_id' => auth()->id(),
            'request_data' => $request->all()
        ]);

        $user = auth()->user();

        // Validación base
        $rules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo_producto_id' => 'required|exists:tipo_productos,id',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'codigo_barras' => 'nullable|string|max:255|unique:productos,codigo_barras',
            'precio_venta' => 'nullable|numeric|min:0|max:99999999.99',
            'precio_compra' => 'nullable|numeric|min:0|max:99999999.99',
            'tipo_medida_id' => 'nullable|exists:tipo_medidas,id',
            'impuestos' => 'nullable|array',
            'impuestos.*' => 'exists:impuestos,id',
        ];

        // Si el tipo de producto es 1 (oro), tipo_oro_id es obligatorio
        if ($request->tipo_producto_id == 1) {
            $rules['tipo_oro_id'] = 'required|exists:tipo_oros,id';
        }

        // Si no es admin global, empresa_id es obligatorio para usuarios normales
        if (!$user->esAdministradorGlobal()) {
            $rules['empresa_id'] = 'required|exists:empresas,id';
        } else {
            $rules['empresa_id'] = 'nullable|exists:empresas,id';
        }

        Log::info('Reglas de validación aplicadas', ['rules' => $rules]);

        $request->validate($rules);

        // Verificar unicidad del nombre considerando la empresa
        $existingQuery = Producto::where('nombre', $request->nombre);

        if ($request->filled('empresa_id')) {
            $existingQuery->where('empresa_id', $request->empresa_id);
        } else {
            $existingQuery->whereNull('empresa_id');
        }

        if ($existingQuery->exists()) {
            Log::warning('Intento de crear producto duplicado', ['nombre' => $request->nombre, 'empresa_id' => $request->empresa_id]);
            return back()->withErrors([
                'nombre' => 'Ya existe un producto con este nombre en esta empresa.'
            ])->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->only(['nombre', 'descripcion', 'tipo_producto_id', 'empresa_id', 'codigo_barras', 'precio_venta', 'precio_compra', 'tipo_medida_id']);

            // Solo agregar tipo_oro_id si el tipo de producto es oro (id = 1)
            if ($request->tipo_producto_id == 1 && $request->filled('tipo_oro_id')) {
                $data['tipo_oro_id'] = $request->tipo_oro_id;
            }

            // Manejar subida de imagen
            if ($request->hasFile('imagen')) {
                $path = $request->file('imagen')->store('productos', 'public');
                $data['imagen'] = $path;
                Log::info('Imagen subida', ['path' => $path]);
            }

            Log::info('Datos para crear producto', ['data' => $data]);

            $producto = Producto::create($data);

            // Sincronizar impuestos si se enviaron
            if ($request->has('impuestos')) {
                $impuestosSyncData = [];
                foreach ($request->impuestos as $impuestoId) {
                    // Buscar el porcentaje más reciente para este impuesto
                    // Asumimos que ImpuestoPorcentaje tiene el historial y queremos el actual
                    // O si el porcentaje está en la tabla impuestos (depende de tu lógica de negocio)
                    // Basado en los modelos vistos: Impuesto tiene many ImpuestoPorcentaje.
                    
                    $porcentaje = 0;
                    $impuestoPorcentaje = \App\Models\ImpuestoPorcentaje::where('impuesto_id', $impuestoId)
                                            ->latest() // O el criterio que uses para "actual"
                                            ->first();
                    
                    if ($impuestoPorcentaje) {
                        $porcentaje = $impuestoPorcentaje->percentage;
                    } else {
                        // Fallback o error si no hay porcentaje definido?
                        Log::warning("Impuesto ID {$impuestoId} no tiene porcentaje definido en ImpuestoPorcentaje. Usando 0.");
                    }

                    $impuestosSyncData[$impuestoId] = ['porcentaje' => $porcentaje];
                }

                $producto->impuestos()->sync($impuestosSyncData);
                Log::info('Impuestos sincronizados', ['impuestos' => $impuestosSyncData]);
            }

            DB::commit();

            Log::info('Producto creado exitosamente', ['id' => $producto->id]);

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Producto creado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('productos.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error FATAL al crear producto: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['imagen'])
            ]);
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al crear el producto. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    public function show(Producto $producto)
    {
        $producto->load(['tipoProducto', 'tipoOro', 'empresa', 'tipoMedida', 'impuestos']);
        return view('productos.show', compact('producto'));
    }

    public function edit(Producto $producto)
    {
        $user = auth()->user();
        $empresas = null;
        $tiposProducto = TipoProducto::orderBy('nombre')->get();
        $tiposOro = TipoOro::orderBy('nombre')->get();
        $tiposMedida = TipoMedida::activos()->orderBy('nombre')->get();
        $impuestos = Impuesto::orderBy('name')->get();

        // Cargar impuestos actuales del producto
        $producto->load('impuestos');

        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::activas()->orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        return view('productos.edit', compact('producto', 'empresas', 'tiposProducto', 'tiposOro', 'tiposMedida', 'impuestos'));
    }

    public function update(Request $request, Producto $producto)
    {
        try {
            $user = auth()->user();

            // Validación base
            $rules = [
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:1000',
                'tipo_producto_id' => 'required|exists:tipo_productos,id',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'codigo_barras' => 'nullable|string|max:255|unique:productos,codigo_barras,' . $producto->id,
                'precio_venta' => 'nullable|numeric|min:0|max:99999999.99',
                'precio_compra' => 'nullable|numeric|min:0|max:99999999.99',
                'tipo_medida_id' => 'nullable|exists:tipo_medidas,id',
                'impuestos' => 'nullable|array',
                'impuestos.*' => 'exists:impuestos,id',
            ];

            // Si el tipo de producto es 1 (oro), tipo_oro_id es obligatorio
            if ($request->tipo_producto_id == 1) {
                $rules['tipo_oro_id'] = 'required|exists:tipo_oros,id';
            }

            // Si no es admin global, empresa_id es obligatorio para usuarios normales
            if (!$user->esAdministradorGlobal()) {
                $rules['empresa_id'] = 'required|exists:empresas,id';
            } else {
                $rules['empresa_id'] = 'nullable|exists:empresas,id';
            }

            $request->validate($rules);

            // Verificar unicidad del nombre considerando la empresa (excluyendo el producto actual)
            $existingQuery = Producto::where('nombre', $request->nombre)
                ->where('id', '!=', $producto->id);

            if ($request->filled('empresa_id')) {
                $existingQuery->where('empresa_id', $request->empresa_id);
            } else {
                $existingQuery->whereNull('empresa_id');
            }

            if ($existingQuery->exists()) {
                return back()->withErrors([
                    'nombre' => 'Ya existe un producto con este nombre en esta empresa.'
                ])->withInput();
            }

            DB::beginTransaction();

            $data = $request->only(['nombre', 'descripcion', 'tipo_producto_id', 'empresa_id', 'codigo_barras', 'precio_venta', 'precio_compra', 'tipo_medida_id']);

            // Manejar tipo_oro_id
            if ($request->tipo_producto_id == 1 && $request->filled('tipo_oro_id')) {
                $data['tipo_oro_id'] = $request->tipo_oro_id;
            } else {
                $data['tipo_oro_id'] = null;
            }

            // Manejar subida de imagen
            if ($request->hasFile('imagen')) {
                // Eliminar imagen anterior si existe
                if ($producto->imagen) {
                    Storage::disk('public')->delete($producto->imagen);
                }
                $data['imagen'] = $request->file('imagen')->store('productos', 'public');
            }

            $producto->update($data);

            // Sincronizar impuestos si se enviaron
            if ($request->has('impuestos')) {
                $producto->impuestos()->sync($request->impuestos);
            } else {
                // Si no se enviaron impuestos, limpiar la relación
                $producto->impuestos()->sync([]);
            }

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Producto actualizado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('productos.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar el producto. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    public function destroy(Producto $producto)
    {
        try {
            DB::beginTransaction();

            // Eliminar imagen si existe
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }

            $producto->delete();

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Producto eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('productos.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar producto: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al eliminar el producto. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }

    public function export(Request $request)
    {
        try {
            $filename = 'productos_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return (new ProductosExport($request))->download($filename);
        } catch (Exception $e) {
            Log::error('Error al exportar productos: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al exportar los productos. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }

    public function crearAjax(Request $request)
    {
        try {
            $user = auth()->user();

            // Validación base
            $rules = [
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:1000',
                'tipo_producto_id' => 'required|exists:tipo_productos,id',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'codigo_barras' => 'nullable|string|max:255|unique:productos,codigo_barras',
                'precio_venta' => 'nullable|numeric|min:0|max:99999999.99',
                'precio_compra' => 'nullable|numeric|min:0|max:99999999.99',
                'peso' => 'nullable|numeric|min:0',
                'tipo_medida_id' => 'nullable|exists:tipo_medidas,id',
                'impuestos' => 'nullable|array',
                'impuestos.*' => 'exists:impuestos,id',
            ];

            // Si el tipo de producto es 1 (oro), tipo_oro_id es obligatorio
            if ($request->tipo_producto_id == 1) {
                $rules['tipo_oro_id'] = 'required|exists:tipo_oros,id';
            }

            // Si no es admin global, empresa_id es obligatorio para usuarios normales
            if (!$user->esAdministradorGlobal()) {
                $rules['empresa_id'] = 'required|exists:empresas,id';
            } else {
                $rules['empresa_id'] = 'nullable|exists:empresas,id';
            }

            $request->validate($rules);

            // Verificar unicidad del nombre considerando la empresa
            $existingQuery = Producto::where('nombre', $request->nombre);

            if ($request->filled('empresa_id')) {
                $existingQuery->where('empresa_id', $request->empresa_id);
            } else {
                $existingQuery->whereNull('empresa_id');
            }

            if ($existingQuery->exists()) {
                return response()->json([
                    'message' => 'Ya existe un producto con este nombre en esta empresa.'
                ], 422);
            }

            DB::beginTransaction();

            $data = $request->only(['nombre', 'descripcion', 'tipo_producto_id', 'empresa_id', 'codigo_barras', 'precio_venta', 'precio_compra', 'peso', 'tipo_medida_id']);

            // Solo agregar tipo_oro_id si el tipo de producto es oro (id = 1)
            if ($request->tipo_producto_id == 1 && $request->filled('tipo_oro_id')) {
                $data['tipo_oro_id'] = $request->tipo_oro_id;
            }

            // Manejar subida de imagen
            if ($request->hasFile('imagen')) {
                $data['imagen'] = $request->file('imagen')->store('productos', 'public');
            }

            $producto = Producto::create($data);

            // Sincronizar impuestos si se enviaron
            if ($request->has('impuestos')) {
                $producto->impuestos()->sync($request->impuestos);
            }

            // Cargar relaciones para devolver el producto completo
            $producto->load(['tipoProducto', 'tipoOro', 'empresa', 'tipoMedida', 'impuestos']);

            // Agregar URL de imagen si existe
            if ($producto->imagen) {
                $producto->imagen_url = Storage::url($producto->imagen);
            }

            DB::commit();

            return response()->json($producto, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear producto via AJAX: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ocurrió un error al crear el producto. Inténtelo de nuevo.'
            ], 500);
        }
    }
}
