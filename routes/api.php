<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UbicacionController;
use App\Http\Controllers\Api\Factura\FacturaController as ApiFacturaController;
use App\Models\TipoProducto;
use App\Models\TipoOro;
use App\Models\TipoMedida;
use App\Models\TipoFactura;
use App\Models\MedioPago;
use App\Models\TipoPago;
use App\Models\TipoRetencion;
use App\Models\ConceptoRetencione;
use App\Models\Empresa;
use App\Models\ResolucionFacturacion;
use App\Models\Cliente;
use App\Models\Producto;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth Routes (Public)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth Management
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Users Resource
    Route::apiResource('users', UserController::class);

    // --- Core Data Endpoints ---

    // Empresas
    Route::get('/empresas', function () {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        
        if (method_exists($user, 'esAdministradorGlobal') && $user->esAdministradorGlobal()) {
            // Activas scope usually exists on model
            return response()->json(Empresa::activas()->orderBy('razon_social')->get(['id', 'razon_social']));
        } else {
            if (method_exists($user, 'empresasActivas')) {
                return response()->json($user->empresasActivas()->get(['id', 'razon_social']));
            }
            return response()->json([]);
        }
    });

    // Catalogos Generales
    Route::get('/tipos-producto', function () {
        return response()->json(TipoProducto::orderBy('nombre')->get(['id', 'nombre']));
    });

    Route::get('/tipos-oro', function () {
        return response()->json(TipoOro::orderBy('nombre')->get(['id', 'nombre']));
    });

    Route::get('/tipos-medida', function () {
        return response()->json(TipoMedida::orderBy('nombre')->get(['id', 'nombre', 'abreviatura']));
    });

    // Ubicacion
    Route::get('/departamentos/{departamento}/municipios', [UbicacionController::class, 'municipios']);
    Route::get('/municipios/{municipio}/comunas', [UbicacionController::class, 'comunas']);
    Route::get('/comunas/{comuna}/barrios', [UbicacionController::class, 'barrios']);

    // --- Facturacion API ---
    
    Route::post('/facturas', [ApiFacturaController::class, 'store']);

    Route::get('/tipos-factura', function() {
        return TipoFactura::select('id', 'name')->get()->map(function($tipo) {
            return ['id' => $tipo->id, 'name' => $tipo->name, 'nombre' => $tipo->name];
        });
    });

    Route::get('/medios-pago', function() {
        return MedioPago::select('id', 'name')->get()->map(function($medio) {
            return ['id' => $medio->id, 'name' => $medio->name, 'nombre' => $medio->name];
        });
    });

    Route::get('/tipos-pago', function() {
        return TipoPago::select('id', 'name', 'code')->get()->map(function($tipo) {
            return ['id' => $tipo->id, 'name' => $tipo->name, 'nombre' => $tipo->name, 'code' => $tipo->code];
        });
    });

    Route::get('/retenciones', function() {
        return TipoRetencion::select('id', 'name', 'code')
            ->where('name', '!=', 'ReteRenta')
            ->get();
    });

    Route::get('/conceptos-retencion', function() {
        $retencionId = request('retencion_id');
        return ConceptoRetencione::select('id', 'name', 'percentage')
            ->when($retencionId, function($query, $retencionId) {
                return $query->where('tipo_retencion_id', $retencionId);
            })->get();
    });

    // Resoluciones
    Route::get('/resoluciones', function() {
        $empresaId = request('empresa_id');
        return ResolucionFacturacion::when($empresaId, function($query, $empresaId) {
            return $query->where('empresa_id', $empresaId);
        })->where('envia_dian', true)
          ->whereNotNull('clave_tecnica')
          ->where('clave_tecnica', '!=', '')
          ->select('id', 'prefijo', 'resolucion', 'consecutivo_actual', 'consecutivo_final')
          ->get()
          ->map(function($resolucion) {
              return [
                  'id' => $resolucion->id,
                  'name' => $resolucion->prefijo,
                  'prefijo' => $resolucion->prefijo,
                  'resolucion' => $resolucion->resolucion,
                  'consecutivo_actual' => $resolucion->consecutivo_actual,
                  'consecutivo_final' => $resolucion->consecutivo_final
              ];
          });
    });

    // Clientes
    Route::get('/clientes', function() {
        $empresaId = request('empresa_id');
        return Cliente::when($empresaId, function($query, $empresaId) {
            return $query->where('empresa_id', $empresaId);
        })
        ->select('id', 'nombres', 'apellidos', 'razon_social', 'cedula_nit', 'dv', 'tipo_documento_id', 'email', 'celular', 'direccion')
        ->get()
        ->map(function($cliente) {
            return [
                'id' => $cliente->id,
                'name' => $cliente->nombre_completo ?? ($cliente->nombres . ' ' . $cliente->apellidos),
                'nombre_completo' => $cliente->nombre_completo ?? ($cliente->nombres . ' ' . $cliente->apellidos),
                'cedula_nit' => $cliente->cedula_nit,
                'documento_completo' => $cliente->documento_completo ?? $cliente->cedula_nit,
                'email' => $cliente->email,
                'celular' => $cliente->celular,
                'direccion' => $cliente->direccion,
                'tipo_documento_id' => $cliente->tipo_documento_id
            ];
        });
    });

    // Productos
    Route::get('/productos', function(Request $request) {
        $query = Producto::with(['tipoProducto', 'tipoMedida', 'tipoProducto', 'tipoOro', 'impuestos'])
            ->select('id', 'nombre', 'descripcion', 'tipo_producto_id', 'tipo_medida_id', 'precio_venta', 'codigo_barras', 'imagen');

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        return $query->get()->map(function($producto) {
            return [
                'id' => $producto->id,
                'name' => $producto->nombre,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'precio_venta' => $producto->precio_venta,
                'codigo_barras' => $producto->codigo_barras,
                'imagen' => $producto->imagen,
                'imagen_url' => $producto->imagen ? asset('storage/' . $producto->imagen) : null,
                'tipo_producto' => $producto->tipo_producto,
                'tipo_oro' => $producto->tipo_oro,
                'impuestos' => $producto->impuestos->map(function($impuesto) {
                    return [
                        'id' => $impuesto->id,
                        'name' => $impuesto->name,
                        'code' => $impuesto->code,
                        'percentage' => $impuesto->pivot->porcentaje ?? 0,
                    ];
                })
            ];
        });
    });

});
