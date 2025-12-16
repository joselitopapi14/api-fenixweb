<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UbicacionController;
use App\Http\Controllers\Api\Factura\FacturaController as ApiFacturaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProductoImportController;
use App\Http\Controllers\Api\ResolucionFacturacionController;
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
use App\Models\Impuesto;

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

    Route::get('/debug-permissions', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'check_can_users_view' => $user->can('users.view'),
            'current_guard_config' => config('auth.guards.api'),
            
            // Inspección directa a la BD para ver qué guard tienen realmente
            'roles_in_db' => \Spatie\Permission\Models\Role::where('name', 'role.admin')->get(['id', 'name', 'guard_name']),
            'permissions_in_db' => \Spatie\Permission\Models\Permission::where('name', 'users.view')->get(['id', 'name', 'guard_name']),
            
            // Verificar si el rol tiene el permiso asignado
            'role_has_permission' => \Spatie\Permission\Models\Role::where('name', 'role.admin')->first()->hasPermissionTo('users.view'),
            
            // Ver si hay permisos asociados al rol
            'role_permissions_count' => \Spatie\Permission\Models\Role::where('name', 'role.admin')->first()->permissions()->count(),
        ]);
    });

    // Users Resource
    // La autorización se maneja en el controlador
    Route::apiResource('users', UserController::class);
    
    // Roles & Permissions Resource
    Route::apiResource('roles', \App\Http\Controllers\Api\RoleController::class);
    Route::get('/permissions', [\App\Http\Controllers\Api\RoleController::class, 'allPermissions']);

    // --- Core Data Endpoints ---

    // Empresas
    Route::get('/empresas', function () {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([], 401);
        }

        // Verificar si es admin global
        // Usamos try-catch interno por si Spatie falla, pero no bloqueamos todo el endpoint
        $isAdmin = false;
        try {
            $isAdmin = $user->hasRole('role.admin');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error verificando rol admin: ' . $e->getMessage());
        }

        if ($isAdmin) {
            return response()->json(Empresa::activas()->orderBy('razon_social')->get(['id', 'razon_social']));
        } else {
            if (method_exists($user, 'empresasActivas')) {
                // FIXED: Column reference "id" is ambiguous. Specificamos la tabla.
                return response()->json($user->empresasActivas()->get(['empresas.id', 'empresas.razon_social']));
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
    
    // --- Facturacion API ---
    // Standardized CRUD for Facturas
    Route::apiResource('facturas', ApiFacturaController::class);

    Route::get('/tipos-factura', function() {
        return TipoFactura::select('id', 'name', 'code')->get();
    });

    Route::get('/medios-pago', function() {
        return MedioPago::select('id', 'name', 'code')->get();
    });

    Route::get('/tipos-pago', function() {
        return TipoPago::select('id', 'name', 'code')->get();
    });

    Route::get('/retenciones', function() {
        return TipoRetencion::select('id', 'name', 'code')
            ->where('name', '!=', 'ReteRenta')
            ->get();
    });

    Route::get('/impuestos', function() {
        return Impuesto::select('id', 'name', 'code')->get();
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

    // Clientes logic...
    // ...


// Productos - Importación (Debe ir antes de resource para evitar conflicto con {id})
Route::prefix('productos')->group(function () {
    Route::match(['get', 'post'], 'export', [ProductoController::class, 'export']);
    
    Route::prefix('import')->group(function () {
        Route::get('template', [ProductoImportController::class, 'template']);
        Route::post('preview', [ProductoImportController::class, 'preview']);
        Route::post('/', [ProductoImportController::class, 'import']); // POST /api/productos/import
        Route::get('history', [ProductoImportController::class, 'history']);
    });
    
    // Explicit create route REMOVED to enforce REST standard (use POST /productos)
    // Route::post('create', [ProductoController::class, 'store']);
});

// Productos - CRUD
Route::apiResource('productos', ProductoController::class);

// Resoluciones de Facturación
Route::post('resoluciones-facturacion/sincronizar', [ResolucionFacturacionController::class, 'sincronizar']);
Route::apiResource('resoluciones-facturacion', ResolucionFacturacionController::class);

}); // End of auth middleware group
