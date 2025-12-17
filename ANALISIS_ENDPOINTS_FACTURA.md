# Análisis de Endpoints para Creación de Facturas

## Payload de Ejemplo
```json
{
    "cliente_id": 1,
    "empresa_id": 1,
    "tipo_movimiento_id": 1,
    "tipo_factura_id": 1,
    "medio_pago_id": 1,
    "tipo_pagos_id": 1,
    "observaciones": "Factura de prueba",
    "productos": [
        {
            "id": 4,
            "cantidad": 2,
            "descuento": 0,
            "recargo": 0
        }
    ],
    "valor_recibido": 100000,
    "retenciones": []
}
```

## ✅ Endpoints YA Implementados

### 1. **Clientes** - `cliente_id`
- **Endpoint**: `GET/POST/PUT/DELETE /api/clientes`
- **Estado**: ✅ **IMPLEMENTADO** (Línea 158 en `api.php`)
- **Controlador**: `App\Http\Controllers\Api\ClienteController`
- **Documentación**: Ver `CRUD_CLIENTES_DOCUMENTATION.md`

### 2. **Empresas** - `empresa_id`
- **Endpoint**: `GET/POST/PUT/DELETE /api/empresas`
- **Estado**: ✅ **IMPLEMENTADO** (Línea 79 en `api.php`)
- **Controlador**: `App\Http\Controllers\Api\EmpresaController`

### 3. **Productos** - `productos[].id`
- **Endpoint**: `GET/POST/PUT/DELETE /api/productos`
- **Estado**: ✅ **IMPLEMENTADO** (Línea 177 en `api.php`)
- **Controlador**: `App\Http\Controllers\Api\ProductoController`
- **Funcionalidades adicionales**:
  - Importación: `POST /api/productos/import`
  - Exportación: `GET/POST /api/productos/export`
  - Template: `GET /api/productos/import/template`
  - Preview: `POST /api/productos/import/preview`
  - History: `GET /api/productos/import/history`

### 4. **Tipo Factura** - `tipo_factura_id`
- **Endpoint**: `GET /api/tipos-factura`
- **Estado**: ✅ **IMPLEMENTADO** (Línea 105-107 en `api.php`)
- **Tipo**: Endpoint de solo lectura (catálogo)
- **Respuesta**: `[{id, name, code}]`

### 5. **Medio de Pago** - `medio_pago_id`
- **Endpoint**: `GET /api/medios-pago`
- **Estado**: ✅ **IMPLEMENTADO** (Línea 109-111 en `api.php`)
- **Tipo**: Endpoint de solo lectura (catálogo)
- **Respuesta**: `[{id, name, code}]`

### 6. **Tipo de Pago** - `tipo_pagos_id`
- **Endpoint**: `GET /api/tipos-pago`
- **Estado**: ✅ **IMPLEMENTADO** (Línea 113-115 en `api.php`)
- **Tipo**: Endpoint de solo lectura (catálogo)
- **Respuesta**: `[{id, name, code}]`

### 7. **Retenciones** - `retenciones[]`
- **Endpoint**: `GET /api/retenciones`
- **Estado**: ✅ **IMPLEMENTADO** (Línea 117-121 en `api.php`)
- **Tipo**: Endpoint de solo lectura (catálogo)
- **Respuesta**: `[{id, name, code}]` (excluye 'ReteRenta')
- **Endpoint adicional**: `GET /api/conceptos-retencion?retencion_id={id}` (Línea 127-133)

### 8. **Facturas** - Endpoint principal
- **Endpoint**: `GET/POST/PUT/DELETE /api/facturas`
- **Estado**: ✅ **IMPLEMENTADO** (Línea 103 en `api.php`)
- **Controlador**: `App\Http\Controllers\Api\Factura\FacturaController`

---

## ✅ Endpoint AGREGADO

### **Tipo de Movimiento** - `tipo_movimiento_id`
- **Endpoint**: `GET /api/tipos-movimiento`
- **Estado**: ✅ **IMPLEMENTADO** (Agregado en línea 94 en `api.php`)
- **Modelo**: ✅ Existe (`App\Models\TipoMovimiento`)
- **Parámetros opcionales**: `?empresa_id={id}` (filtra por empresa)
- **Respuesta**: `[{id, nombre, es_suma, descripcion, empresa_id}]`
- **Filtros**: Solo devuelve tipos de movimiento activos

---

## 📋 Resumen de Estado

| Entidad | Campo en Payload | Endpoint | Estado |
|---------|------------------|----------|--------|
| Cliente | `cliente_id` | `/api/clientes` | ✅ Implementado |
| Empresa | `empresa_id` | `/api/empresas` | ✅ Implementado |
| **Tipo Movimiento** | `tipo_movimiento_id` | `/api/tipos-movimiento` | ✅ **Implementado** |
| Tipo Factura | `tipo_factura_id` | `/api/tipos-factura` | ✅ Implementado |
| Medio Pago | `medio_pago_id` | `/api/medios-pago` | ✅ Implementado |
| Tipo Pago | `tipo_pagos_id` | `/api/tipos-pago` | ✅ Implementado |
| Producto | `productos[].id` | `/api/productos` | ✅ Implementado |
| Retenciones | `retenciones[]` | `/api/retenciones` | ✅ Implementado |
| Conceptos Retención | - | `/api/conceptos-retencion` | ✅ Implementado |



---

## 📝 Validaciones en el Controlador de Facturas

El `FacturaController` ya valida que todos los IDs existan:

```php
// Línea 169-187 en FacturaController.php
'tipo_movimiento_id' => 'required|exists:tipo_movimientos,id',
'tipo_factura_id' => 'required|exists:tipo_facturas,id',
'cliente_id' => 'required|exists:clientes,id',
'empresa_id' => 'required|exists:empresas,id',
'medio_pago_id' => 'required|exists:medio_pagos,id',
'tipo_pagos_id' => 'required|exists:tipo_pagos,id',
'productos.*.id' => 'required|exists:productos,id',
```

Por lo tanto, **antes de crear una factura**, necesitas:

1. ✅ Tener un **cliente** creado (usa `/api/clientes`)
2. ✅ Tener una **empresa** creada (usa `/api/empresas`)
3. ✅ Tener **productos** creados (usa `/api/productos`)
4. ❌ Tener un **tipo de movimiento** válido (necesitas crear el endpoint)
5. ✅ Los demás son catálogos que ya deberían existir en la BD

---

## 🚀 Orden de Creación Recomendado

**Todos los endpoints necesarios están implementados. Puedes crear facturas siguiendo este orden:**

1. **Crear Empresa** → `POST /api/empresas`
2. **Crear Cliente** → `POST /api/clientes`
3. **Crear Productos** → `POST /api/productos`
4. **Verificar catálogos disponibles**:
   - `GET /api/tipos-movimiento?empresa_id={id}`
   - `GET /api/tipos-factura`
   - `GET /api/medios-pago`
   - `GET /api/tipos-pago`
   - `GET /api/retenciones`
5. **Crear Factura** → `POST /api/facturas` con el payload proporcionado

---

## 📌 Notas Adicionales

- El controlador de facturas calcula automáticamente: `subtotal`, `valor_impuestos`, `total`, `cambio`
- El `numero_factura` se genera automáticamente si no se proporciona
- El `user_id` (vendedor) se asigna automáticamente del usuario autenticado
- La `issue_date` se establece automáticamente a la fecha actual
- El `estado` inicial es siempre `'creada'`
