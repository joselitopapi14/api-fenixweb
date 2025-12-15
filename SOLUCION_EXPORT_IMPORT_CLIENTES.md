# Solución de Problemas: Export/Import de Clientes

## ❌ Problema Original

### Error SQL en Export de Clientes
```
SQLSTATE[22P02]: Invalid text representation: 7 ERROR: invalid input syntax for type bigint: "export" 
CONTEXT: unnamed portal parameter $1 = '...'
select * from "clientes" where "id" = export and "clientes"."deleted_at" is null limit 1
```

### Causa Raíz
El problema era causado por el **orden incorrecto de las rutas** en `routes/web.php`. Laravel interpretaba "export" como el parámetro `{cliente}` en lugar de reconocer la ruta específica de export.

```php
// ❌ ORDEN INCORRECTO (ANTES)
Route::get('/{empresa}/clientes/{cliente}', [ClienteController::class, 'show']);  // Esta ruta capturaba "export"
Route::get('/{empresa}/clientes/export', [ClienteController::class, 'export']);  // Esta nunca se ejecutaba
```

## ✅ Soluciones Implementadas

### 1. Reordenamiento de Rutas
```php
// ✅ ORDEN CORRECTO (DESPUÉS)
Route::get('/{empresa}/clientes', [ClienteController::class, 'index']);
Route::get('/{empresa}/clientes/export', [ClienteController::class, 'export']);    // ANTES de {cliente}
Route::get('/{empresa}/clientes/create', [ClienteController::class, 'create']);
Route::get('/{empresa}/clientes/{cliente}', [ClienteController::class, 'show']);   // DESPUÉS de rutas específicas
```

### 2. Middleware `empresa.access` Agregado
```php
// ANTES: Solo permission:empresas.edit
Route::prefix('clientes/import')->middleware(['permission:empresas.edit'])->group(function () {

// DESPUÉS: Incluye empresa.access para consistencia
Route::prefix('clientes/import')->middleware(['permission:empresas.edit', 'empresa.access'])->group(function () {
```

### 3. Creación de Directorios de Almacenamiento
```
storage/app/imports/
├── productos/
│   └── .gitkeep
└── clientes/
    └── .gitkeep
```

## 🔒 Control de Acceso por Empresas

### Respuesta a la Pregunta del Usuario
**¿Todo esto de los exports, imports está condicionado por el acceso a empresas como está lo demás?**

**SÍ**, ahora está completamente condicionado:

#### **Import/Export de Productos**
```php
// ✅ Con empresa.access
Route::prefix('productos/import')->middleware(['permission:registros.create', 'empresa.access'])
Route::prefix('productos')->middleware(['permission:registros.view', 'empresa.access'])
```

#### **Import/Export de Clientes**
```php
// ✅ Con empresa.access (CORREGIDO)
Route::prefix('clientes/import')->middleware(['permission:empresas.edit', 'empresa.access'])

// ✅ Clientes ya estaban dentro del grupo empresas con empresa.access
Route::prefix('empresas')->middleware('empresa.access')->group(function () {
    Route::get('/{empresa}/clientes/export', [ClienteController::class, 'export']);
});
```

## 🛡️ Funcionamiento del Middleware `empresa.access`

### Lógica de Control
```php
class EmpresaAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Super Admin puede acceder a todo
        if ($user->esAdministradorGlobal()) {
            return $next($request);
        }

        // Verificar acceso a empresa específica
        $empresaId = $request->route('empresa') ?? $request->input('empresa_id');
        
        if ($empresaId && !$user->puedeAccederAEmpresa($empresaId)) {
            abort(403, 'No tienes acceso a esta empresa.');
        }

        // Usuario debe tener al menos una empresa asociada
        if (!$user->empresasActivas()->exists()) {
            abort(403, 'No tienes acceso a ninguna empresa.');
        }

        return $next($request);
    }
}
```

### Tipos de Usuarios y Acceso

#### **Super Admin**
- ✅ Acceso a **todas las empresas**
- ✅ Puede importar/exportar datos de cualquier empresa
- ✅ Ve todos los historiales de importación

#### **Usuario de Empresa**
- ✅ Solo acceso a **sus empresas asignadas**
- ✅ Solo puede importar/exportar de empresas donde tiene acceso
- ✅ Solo ve historiales de sus empresas

#### **Usuario Sin Empresas**
- ❌ **Acceso denegado** (403)
- ❌ No puede acceder a ninguna funcionalidad

## 🔄 Flujo de Seguridad

### Export de Clientes
1. Usuario accede a `/empresas/{empresa}/clientes/export`
2. Middleware `empresa.access` verifica acceso a la empresa
3. Si tiene acceso → procesa export solo de esa empresa
4. Si no tiene acceso → Error 403

### Import de Clientes
1. Usuario accede a `/clientes/import`
2. Middleware `empresa.access` verifica que tenga empresas asignadas
3. En el formulario solo ve empresas a las que tiene acceso
4. Al procesar, valida que la empresa seleccionada esté en su lista

### Historial de Importaciones
1. Solo muestra importaciones de empresas del usuario
2. Filtros automáticos por empresa según permisos
3. Descarga de archivos solo si fue el usuario que importó o es admin

## 📝 Archivos Modificados

1. **`routes/web.php`**
   - Reordenamiento de rutas de clientes
   - Agregado middleware `empresa.access` a import de clientes

2. **`storage/app/imports/`**
   - Creación de directorios para productos y clientes
   - Archivos `.gitkeep` para mantener estructura

## 🧪 Verificación de la Solución

### Comandos de Verificación
```bash
# Verificar rutas de clientes
php artisan route:list | Select-String "clientes"

# Verificar estructura de directorios
ls storage/app/imports -Recurse

# Verificar que export funcione
curl -H "Authorization: Bearer {token}" GET /empresas/1/clientes/export
```

### Estado Final
- ✅ **Error SQL resuelto** - Rutas en orden correcto
- ✅ **Consistencia de seguridad** - Middleware empresa.access aplicado
- ✅ **Almacenamiento persistente** - Directorios creados y protegidos
- ✅ **Control de acceso uniforme** - Misma lógica para productos y clientes

## 🚀 Beneficios de las Correcciones

1. **Seguridad Mejorada**: Control granular por empresa
2. **Consistencia**: Mismo comportamiento para productos y clientes  
3. **Persistencia**: Los archivos ya no se "eliminan" (ahora se almacenan correctamente)
4. **Mantenibilidad**: Estructura clara y documentada
5. **Escalabilidad**: Preparado para múltiples empresas

---

**Nota**: Todas las funcionalidades de import/export ahora están **completamente alineadas** con el sistema de control de acceso por empresas existente.
