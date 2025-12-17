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
use App\Models\TipoPersona;
use App\Models\TipoResponsabilidad;
use App\Models\TipoDocumento;
use App\Models\Departamento;

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

// ========================================
// Catálogos Públicos (Sin autenticación)
// ========================================
// Endpoints estandarizados con soporte para singular y plural

// Catálogos para Productos
Route::get('/tipos-producto', function () {
    return response()->json(TipoProducto::orderBy('nombre')->get(['id', 'nombre']));
});
Route::get('/tipo-productos', function () {
    return response()->json(TipoProducto::orderBy('nombre')->get(['id', 'nombre']));
});

Route::get('/tipos-oro', function () {
    return response()->json(TipoOro::orderBy('nombre')->get(['id', 'nombre']));
});
Route::get('/tipo-oros', function () {
    return response()->json(TipoOro::orderBy('nombre')->get(['id', 'nombre']));
});

Route::get('/tipos-medida', function () {
    return response()->json(TipoMedida::orderBy('nombre')->get(['id', 'nombre', 'abreviatura']));
});
Route::get('/tipo-medidas', function () {
    return response()->json(TipoMedida::orderBy('nombre')->get(['id', 'nombre', 'abreviatura']));
});

// Catálogos para Empresas y Clientes
Route::get('/tipos-persona', function () {
    return response()->json(TipoPersona::orderBy('name')->get(['id', 'name', 'code']));
});
Route::get('/tipo-personas', function () {
    return response()->json(TipoPersona::orderBy('name')->get(['id', 'name', 'code']));
});

Route::get('/tipos-responsabilidad', function () {
    return response()->json(TipoResponsabilidad::orderBy('name')->get(['id', 'name', 'code']));
});
Route::get('/tipo-responsabilidades', function () {
    return response()->json(TipoResponsabilidad::orderBy('name')->get(['id', 'name', 'code']));
});

Route::get('/tipos-documento', function () {
    return response()->json(TipoDocumento::orderBy('name')->get(['id', 'name', 'code']));
});
Route::get('/tipo-documentos', function () {
    return response()->json(TipoDocumento::orderBy('name')->get(['id', 'name', 'code']));
});

// Catálogos de Ubicación
Route::get('/departamentos', function () {
    return response()->json(Departamento::orderBy('name')->get(['id', 'name', 'code']));
});

Route::get('/municipios', function () {
    $departamentoId = request('departamento_id');
    return response()->json(\App\Models\Municipio::when($departamentoId, function($query, $departamentoId) {
        return $query->where('departamento_id', $departamentoId);
    })->orderBy('name')->get(['id', 'name', 'code', 'departamento_id']));
});

Route::get('/comunas', function () {
    try {
        $municipioId = request('municipio_id');
        $query = \App\Models\Comuna::query();
        
        if ($municipioId) {
            $query->where('municipio_id', $municipioId);
        }
        
        return response()->json($query->orderBy('nombre')->get(['id', 'nombre', 'municipio_id']));
    } catch (\Exception $e) {
        \Log::error('Error en /comunas: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/barrios', function () {
    try {
        $comunaId = request('comuna_id');
        $query = \App\Models\Barrio::query();
        
        if ($comunaId) {
            $query->where('comuna_id', $comunaId);
        }
        
        return response()->json($query->orderBy('nombre')->get(['id', 'nombre', 'comuna_id']));
    } catch (\Exception $e) {
        \Log::error('Error en /barrios: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/redes-sociales', function () {
    try {
        return response()->json(\App\Models\RedSocial::orderBy('nombre')->get(['id', 'nombre', 'icono']));
    } catch (\Exception $e) {
        \Log::error('Error en /redes-sociales: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// Rutas específicas de ubicación (mantener compatibilidad)
Route::get('/departamentos/{departamento}/municipios', [UbicacionController::class, 'municipios']);
Route::get('/municipios/{municipio}/comunas', [UbicacionController::class, 'comunas']);
Route::get('/comunas/{comuna}/barrios', [UbicacionController::class, 'barrios']);

// Catálogos para Facturación
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

// Tipos de Movimiento (puede filtrar por empresa)
Route::get('/tipos-movimiento', function () {
    $empresaId = request('empresa_id');
    return response()->json(\App\Models\TipoMovimiento::activos()
        ->when($empresaId, function($query, $empresaId) {
            return $query->where('empresa_id', $empresaId);
        })
        ->orderBy('nombre')
        ->get(['id', 'nombre', 'es_suma', 'descripcion', 'empresa_id']));
});

// Resoluciones (puede filtrar por empresa)
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

// ENDPOINT DE PRUEBA - Crear empresa SIN autenticación (SOLO LOCAL)
if (app()->isLocal()) {
    Route::post('/test/crear-empresa', function(Request $request) {
        $empresaController = new \App\Http\Controllers\Api\EmpresaController();
        return $empresaController->store($request);
    });
}

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth Management
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    // Endpoint de prueba simple
    Route::get('/test-auth', function (Request $request) {
        return response()->json([
            'authenticated' => true,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email ?? 'N/A',
            'message' => 'Authentication working'
        ]);
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

    // Empresas - Full CRUD
    Route::apiResource('empresas', \App\Http\Controllers\Api\EmpresaController::class);

    // --- Facturacion API ---
    // Standardized CRUD for Facturas
    Route::apiResource('facturas', ApiFacturaController::class);

    // Clientes - Full CRUD
    Route::apiResource('clientes', \App\Http\Controllers\Api\ClienteController::class);


// Productos - Importación (Debe ir antes de resource para evitar conflicto con {id})
Route::prefix('productos')->group(function () {
    Route::match(['get', 'post'], 'export', [ProductoController::class, 'export']);
    
    Route::prefix('import')->group(function () {
        Route::get('template', [ProductoImportController::class, 'template']);
        Route::post('preview', [ProductoImportController::class, 'preview']);
        Route::post('/', [ProductoImportController::class, 'import']); // POST /api/productos/import
        Route::get('history', [ProductoImportController::class, 'history']);
    });
});

// Productos - CRUD (REST Standard)
Route::apiResource('productos', ProductoController::class);

// ========================================
// Rutas de Compatibilidad para Frontend
// ========================================
// Estas rutas permiten que el frontend use /producto/create, /cliente/create, etc.
// Son aliases a los métodos REST estándar

Route::prefix('producto')->group(function () {
    Route::post('/', [ProductoController::class, 'store']); // POST /producto (sin create)
    Route::post('create', [ProductoController::class, 'store']); // POST /producto/create
    Route::get('{id}', [ProductoController::class, 'show']); // Alias de GET /productos/{id}
    Route::put('{id}', [ProductoController::class, 'update']); // Alias de PUT /productos/{id}
    Route::delete('{id}', [ProductoController::class, 'destroy']); // Alias de DELETE /productos/{id}
});

Route::prefix('cliente')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\ClienteController::class, 'store']);
    Route::post('create', [\App\Http\Controllers\Api\ClienteController::class, 'store']);
    Route::get('{id}', [\App\Http\Controllers\Api\ClienteController::class, 'show']);
    Route::put('{id}', [\App\Http\Controllers\Api\ClienteController::class, 'update']);
    Route::delete('{id}', [\App\Http\Controllers\Api\ClienteController::class, 'destroy']);
});

Route::prefix('empresa')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\EmpresaController::class, 'store']);
    Route::post('create', [\App\Http\Controllers\Api\EmpresaController::class, 'store']);
    Route::get('{id}', [\App\Http\Controllers\Api\EmpresaController::class, 'show']);
    Route::put('{id}', [\App\Http\Controllers\Api\EmpresaController::class, 'update']);
    Route::delete('{id}', [\App\Http\Controllers\Api\EmpresaController::class, 'destroy']);
});

// Resoluciones de Facturación
Route::post('resoluciones-facturacion/sincronizar', [ResolucionFacturacionController::class, 'sincronizar']);
Route::apiResource('resoluciones-facturacion', ResolucionFacturacionController::class);

}); // End of auth middleware group
