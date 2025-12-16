<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Exports\ProductosExport;
use App\Exports\ProductosExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\ProductoResource;
use App\Http\Resources\ProductoCollection;
use App\Models\Impuesto; // Added import

class ProductoController extends Controller
{
    /**
     * Listar productos con filtros y paginación
     */
    public function index(Request $request)
    {
        $query = Producto::with(['tipoProducto', 'tipoOro', 'tipoMedida', 'empresa', 'impuestos.impuestoPorcentajes']);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                  ->orWhere('descripcion', 'ilike', "%{$search}%")
                  ->orWhere('codigo_barras', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('tipo_producto_id')) {
            $query->where('tipo_producto_id', $request->tipo_producto_id);
        }

        if ($request->filled('tipo_oro_id')) {
            $query->where('tipo_oro_id', $request->tipo_oro_id);
        }

        if ($request->filled('codigo_barras')) {
            $query->where('codigo_barras', $request->codigo_barras);
        }

        if ($request->filled('precio_venta_min')) {
            $query->where('precio_venta', '>=', $request->precio_venta_min);
        }
        if ($request->filled('precio_venta_max')) {
            $query->where('precio_venta', '<=', $request->precio_venta_max);
        }

        if ($request->filled('precio_compra_min')) {
            $query->where('precio_compra', '>=', $request->precio_compra_min);
        }
        if ($request->filled('precio_compra_max')) {
            $query->where('precio_compra', '<=', $request->precio_compra_max);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Ordenamiento
        $sort = $request->get('sort', 'nombre_asc');
        switch ($sort) {
            case 'nombre_desc': $query->orderBy('nombre', 'desc'); break;
            case 'precio_asc': $query->orderBy('precio_venta', 'asc'); break;
            case 'precio_desc': $query->orderBy('precio_venta', 'desc'); break;
            case 'newest': $query->orderBy('created_at', 'desc'); break;
            case 'oldest': $query->orderBy('created_at', 'asc'); break;
            default: $query->orderBy('nombre', 'asc'); break;
        }

        // Paginación
        $perPage = $request->get('per_page', 10);
        $productos = $query->paginate($perPage);

        // Stats calculation (Global or Enterprise scoped)
        $statsQuery = Producto::query();
        if ($request->filled('empresa_id')) {
            $statsQuery->where('empresa_id', $request->empresa_id);
        }

        $stats = [
            'total_productos' => $statsQuery->count(),
            'productos_este_ano' => $statsQuery->clone()->whereYear('created_at', now()->year)->count(),
            'productos_este_mes' => $statsQuery->clone()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];
        
        return new ProductoCollection($productos, $stats);
    }

    /**
     * Obtener un producto específico
     */
    public function show($id)
    {
        $producto = Producto::with(['tipoProducto', 'tipoOro', 'tipoMedida', 'empresa', 'impuestos.impuestoPorcentajes'])->find($id);

        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        // Verificar acceso a empresa si es necesario
        // ...

        return new ProductoResource($producto);
    }

    /**
     * Crear un nuevo producto
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo_producto_id' => 'required|exists:tipo_productos,id',
            'tipo_oro_id' => 'required_if:tipo_producto_id,1|nullable|exists:tipo_oros,id', // Asumo 1 es Oro
            'empresa_id' => 'nullable|exists:empresas,id',
            'codigo_barras' => [
                'nullable', 
                'string', 
                'max:255', 
                Rule::unique('productos')->where(function ($query) use ($request) {
                    if ($request->empresa_id) {
                        return $query->where('empresa_id', $request->empresa_id);
                    }
                    return $query->whereNull('empresa_id');
                })
            ], 
            'precio_venta' => 'nullable|numeric|min:0',
            'precio_compra' => 'nullable|numeric|min:0',
            'peso' => 'nullable|numeric|min:0',
            'tipo_medida_id' => 'nullable|exists:tipo_medidas,id',
            'imagen' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
            'impuestos' => 'nullable|array',
            'impuestos.*' => 'exists:impuestos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->except(['imagen', 'impuestos']);
            
            // Manejo de imagen
            if ($request->hasFile('imagen')) {
                $path = $request->file('imagen')->store('productos', 'public');
                $data['imagen'] = $path;
            }

            $producto = Producto::create($data);

            if ($request->has('impuestos')) {
                $syncData = [];
                $impuestos = Impuesto::with('impuestoPorcentajes')->findMany($request->impuestos);
                foreach ($impuestos as $impuesto) {
                    $porcentaje = $impuesto->impuestoPorcentajes->first()->percentage ?? 0;
                    $syncData[$impuesto->id] = ['porcentaje' => $porcentaje];
                }
                $producto->impuestos()->sync($syncData);
            }

            DB::commit();

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'data' => new ProductoResource($producto->load(['tipoProducto', 'tipoOro', 'tipoMedida', 'empresa', 'impuestos']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando producto API: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Actualizar producto
     */
    public function update(Request $request, $id)
    {
        $producto = Producto::find($id);
        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo_producto_id' => 'sometimes|required|exists:tipo_productos,id',
            'tipo_oro_id' => 'nullable|exists:tipo_oros,id', 
            // empresa_id usualmente no se cambia, pero lo permitimos si es necesario
            'codigo_barras' => [
                'nullable', 
                'string', 
                'max:255', 
                Rule::unique('productos')->where(function ($query) use ($request, $producto) {
                   $empresaId = $request->empresa_id ?? $producto->empresa_id;
                   if ($empresaId) {
                       return $query->where('empresa_id', $empresaId);
                   }
                   return $query->whereNull('empresa_id');
                })->ignore($producto->id)
            ],
            'precio_venta' => 'nullable|numeric|min:0',
            'precio_compra' => 'nullable|numeric|min:0',
            'peso' => 'nullable|numeric|min:0',
            'tipo_medida_id' => 'nullable|exists:tipo_medidas,id',
            'imagen' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
            'impuestos' => 'nullable|array',
            'impuestos.*' => 'exists:impuestos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->except(['imagen', 'impuestos', 'empresa_id']); // Evitar cambiar empresa_id accidentalmente si no se desea
            if ($request->has('empresa_id')) $data['empresa_id'] = $request->empresa_id;

            if ($request->hasFile('imagen')) {
                // Borrar anterior si existe?
                // if ($producto->imagen) Storage::disk('public')->delete($producto->imagen);
                $path = $request->file('imagen')->store('productos', 'public');
                $data['imagen'] = $path;
            }

            $producto->update($data);

            if ($request->has('impuestos')) {
                $syncData = [];
                $impuestos = Impuesto::with('impuestoPorcentajes')->findMany($request->impuestos);
                foreach ($impuestos as $impuesto) {
                    $porcentaje = $impuesto->impuestoPorcentajes->first()->percentage ?? 0;
                    $syncData[$impuesto->id] = ['porcentaje' => $porcentaje];
                }
                $producto->impuestos()->sync($syncData);
            }

            DB::commit();

            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'data' => new ProductoResource($producto->fresh()->load(['tipoProducto', 'tipoOro', 'tipoMedida', 'empresa', 'impuestos']))
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando producto API: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Eliminar producto
     */
    public function destroy($id)
    {
        $producto = Producto::find($id);
        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        // Validar dependencias (Ejemplo básico, idealmente usar foreign keys on delete restrict o lógica manual)
        // ...

        try {
            $producto->delete();
            return response()->json(['message' => 'Producto eliminado exitosamente'], 200);
        } catch (\Exception $e) {
             // Si hay restricción de integridad
             if (str_contains($e->getMessage(), 'Constraint violation') || str_contains($e->getMessage(), 'Foreign key violation')) {
                return response()->json([
                    'message' => 'No se puede eliminar el producto porque tiene registros asociados',
                    // details...
                ], 409);
             }
             return response()->json(['message' => 'Error al eliminar producto'], 500);
        }
    }

    /**
     * Exportar productos
     */
    public function export(Request $request)
    {
        // Reutilizamos ProductosExport si es compatible, o creamos lógica
        // Asumiendo que ProductosExport acepta parametros en constructor o usa request request()
        
        return Excel::download(new ProductosExport($request->all()), 'productos.xlsx');
    }
}
