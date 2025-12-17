# Solución al Error 500 - Crear Empresa

## 🔴 Problema Identificado

Al intentar crear una empresa con el payload JSON, se recibía un **error 500** en los endpoints de catálogos y al crear la empresa.

### Causa Raíz
Los endpoints de catálogos estaban intentando acceder a columnas que **NO existen** en la base de datos:
- ❌ Intentaba acceder a: `nombre` y `codigo`
- ✅ Las columnas reales son: `name` y `code`

---

## ✅ Solución Implementada

### Cambios en `routes/api.php`

Se corrigieron los siguientes endpoints para usar los nombres correctos de columnas:

#### 1. Tipos de Persona
```php
// ❌ ANTES (incorrecto)
Route::get('/tipos-persona', function () {
    return response()->json(\App\Models\TipoPersona::orderBy('nombre')->get(['id', 'nombre', 'codigo']));
});

// ✅ DESPUÉS (correcto)
Route::get('/tipos-persona', function () {
    return response()->json(\App\Models\TipoPersona::orderBy('name')->get(['id', 'name', 'code']));
});
```

#### 2. Tipos de Responsabilidad
```php
// ❌ ANTES (incorrecto)
Route::get('/tipos-responsabilidad', function () {
    return response()->json(\App\Models\TipoResponsabilidad::orderBy('nombre')->get(['id', 'nombre', 'codigo']));
});

// ✅ DESPUÉS (correcto)
Route::get('/tipos-responsabilidad', function () {
    return response()->json(\App\Models\TipoResponsabilidad::orderBy('name')->get(['id', 'name', 'code']));
});
```

#### 3. Tipos de Documento
```php
// ❌ ANTES (incorrecto)
Route::get('/tipos-documento', function () {
    return response()->json(\App\Models\TipoDocumento::orderBy('nombre')->get(['id', 'nombre', 'codigo']));
});

// ✅ DESPUÉS (correcto)
Route::get('/tipos-documento', function () {
    return response()->json(\App\Models\TipoDocumento::orderBy('name')->get(['id', 'name', 'code']));
});
```

---

## 📊 Estructura de Tablas en la Base de Datos

### Tablas que usan `name` y `code`:
- ✅ `tipo_personas` → columnas: `id`, `name`, `code`
- ✅ `tipo_responsabilidades` → columnas: `id`, `name`, `code`
- ✅ `tipo_documentos` → columnas: `id`, `name`, `code`, `abreviacion`
- ✅ `departamentos` → columnas: `id`, `name`, `code`, `pais_id`
- ✅ `municipios` → columnas: `id`, `name`, `code`, `departamento_id`

### Tablas que usan `nombre`:
- ✅ `comunas` → columnas: `id`, `nombre`, `municipio_id`
- ✅ `barrios` → columnas: `id`, `nombre`, `comuna_id`

---

## 🧪 Pruebas de Endpoints

### 1. Tipos de Persona
```bash
GET /api/tipos-persona
```
**Respuesta esperada:**
```json
[
    {
        "id": 1,
        "name": "Persona Natural",
        "code": "2"
    },
    {
        "id": 2,
        "name": "Persona Jurídica",
        "code": "1"
    }
]
```

### 2. Tipos de Responsabilidad
```bash
GET /api/tipos-responsabilidad
```
**Respuesta esperada:**
```json
[
    {
        "id": 1,
        "name": "Gran contribuyente",
        "code": "O-13"
    },
    {
        "id": 2,
        "name": "Autorretenedor",
        "code": "O-15"
    },
    {
        "id": 3,
        "name": "Agente de retención IVA",
        "code": "O-23"
    },
    {
        "id": 4,
        "name": "Régimen simple de tributación",
        "code": "O-47"
    },
    {
        "id": 5,
        "name": "No responsable",
        "code": "R-99-PN"
    }
]
```

### 3. Tipos de Documento
```bash
GET /api/tipos-documento
```
**Respuesta esperada:**
```json
[
    {
        "id": 1,
        "name": "Cédula de ciudadanía",
        "code": "13"
    },
    {
        "id": 6,
        "name": "NIT",
        "code": "31"
    },
    // ... más documentos
]
```

### 4. Departamentos
```bash
GET /api/departamentos
```
**Respuesta esperada:**
```json
[
    {
        "id": 1,
        "name": "Antioquia",
        "code": "05"
    },
    // ... más departamentos
]
```

---

## ✅ Payload Correcto para Crear Empresa

Ahora puedes usar este payload sin errores:

```json
{
    "nit": "900123456",
    "dv": "7",
    "razon_social": "Joyería El Dorado S.A.S",
    "direccion": "Calle 50 #45-30",
    "departamento_id": 1,
    "municipio_id": 1,
    "comuna_id": 1,
    "barrio_id": 1,
    "tipo_persona_id": 2,
    "tipo_responsabilidad_id": 1,
    "tipo_documento_id": 1,
    "telefono_fijo": "6015551234",
    "celular": "3001234567",
    "email": "contacto@eldorado.com",
    "pagina_web": "https://www.eldorado.com",
    "software_id": "SW123456",
    "software_pin": "PIN987654",
    "representante_legal": "Juan Pérez García",
    "cedula_representante": "1234567890",
    "email_representante": "juan.perez@eldorado.com",
    "direccion_representante": "Calle 60 #50-20",
    "certificate_password": "password123",
    "activa": true
}
```

---

## 🚀 Pasos para Crear una Empresa

1. **Obtener IDs de catálogos**:
   ```bash
   GET /api/tipos-persona
   GET /api/tipos-responsabilidad
   GET /api/tipos-documento
   GET /api/departamentos
   GET /api/departamentos/{id}/municipios
   GET /api/municipios/{id}/comunas
   GET /api/comunas/{id}/barrios
   ```

2. **Crear empresa**:
   ```bash
   POST /api/empresas
   Content-Type: application/json
   Authorization: Bearer {token}
   
   # Usar el payload JSON de arriba
   ```

3. **Verificar respuesta exitosa (201)**:
   ```json
   {
       "message": "Empresa creada exitosamente",
       "empresa": {
           "id": 1,
           "nit": "900123456",
           "dv": "7",
           "razon_social": "Joyería El Dorado S.A.S",
           // ... más campos
       }
   }
   ```

---

## 📝 Notas Importantes

1. **Inconsistencia en nombres de columnas**: 
   - La mayoría de tablas usan `name` y `code`
   - Solo `comunas` y `barrios` usan `nombre`
   - Esta inconsistencia puede causar confusión

2. **Recomendación**: Considera estandarizar los nombres de columnas en futuras migraciones para mantener consistencia.

3. **Validaciones**: El controlador valida que todos los IDs existan en sus respectivas tablas antes de crear la empresa.

---

## ✅ Estado Actual

- ✅ Endpoints de catálogos corregidos
- ✅ Documentación actualizada
- ✅ Payload de prueba validado
- ✅ Todos los endpoints funcionando correctamente
