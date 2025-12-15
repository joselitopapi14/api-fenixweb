# Documentación Técnica: Proceso de Guardado de una Factura Completa

## Resumen Ejecutivo

Este documento describe el proceso técnico completo de cómo se guarda una factura electrónica en el sistema de facturación backend, excluyendo el envío a la DIAN. El proceso involucra validaciones, cálculos automáticos, actualización de inventario y persistencia de datos en múltiples tablas relacionadas.

## Arquitectura General

### Componentes Principales
- **Controlador**: `FacturaController` en `app/Http/Controllers/Api/Factura/`
- **Modelo**: `Factura` en `app/Models/Api/`
- **Tablas Involucradas**:
  - `facturas` (tabla principal)
  - `factura_has_products` (productos de la factura)
  - `factura_has_retenciones` (retenciones aplicadas)
  - `factura_has_impuestos` (impuestos calculados - opcional)
  - `factura_has_errors` (errores de validación)

### Flujo de Proceso
1. Recepción y limpieza de datos
2. Validaciones básicas
3. Validación de productos e impuestos
5. Generación de número de factura
6. Cálculo de montos totales
7. Validación de reglas de negocio
9. Transacción de base de datos
10. Procesamiento automático (opcional)

## Detalle del Proceso

### 1. Recepción y Limpieza de Datos

**Método**: `store()` en `FacturaController`

**Acciones**:
- Se remueven campos calculados automáticamente del request: `total`, `subtotal`, `valor_impuestos`
- Se limpia el request eliminando campos no permitidos
- Se asigna el `vendedor_id` (usuario autenticado o especificado)

**Código relevante**:
```php
$request->offsetUnset('total');
$request->offsetUnset('subtotal');
$request->offsetUnset('valor_impuestos');
$this->cleanRequestData($request);
```

### 2. Validaciones Básicas

**Método**: `validateBasicData()`

**Campos validados**:
- `tipo_movimiento_id`: Existencia en tabla `tipo_movimientos`
- `tipo_factura_id`: Existencia en tabla `tipo_facturas`
- `cliente_id`: Existencia en tabla `clientes`
- `empresa_id`: Existencia en tabla `empresas`
- `medio_pago_id`: Existencia en tabla `medio_pagos`
- `tipo_pagos_id`: Existencia en tabla `tipo_pagos`
- `centro_costo_id`: Existencia en tabla `centro_costos`
- `productos`: Array de productos con ID y cantidad
- `retenciones`: Array opcional de retenciones

### 3. Validación de Productos e Impuestos

**Método**: `validateTaxesProducts()`

**Validaciones**:
- Verificación de que todos los productos existen
- Verificación de que los productos pertenecen a la empresa especificada
- Validación de configuración de impuestos por producto
- Cálculo de tasas de impuesto válidas

### 5. Generación de Número de Factura

**Método**: `generateFacturaNumber()` y `validateTipoMovimiento()`

**Proceso**:
- Valida que el `tipo_movimiento` tenga rango de numeración autorizado
- Si no se proporciona `numero_factura`, genera uno nuevo basado en el consecutivo del tipo de movimiento
- Si se proporciona, valida que esté dentro del rango autorizado
- Actualiza el consecutivo en la tabla `tipo_movimientos`

### 6. Cálculo de Montos Totales

**Método**: `calculateFacturaAmounts()`

**Cálculos realizados**:

#### Para cada producto:
1. **Subtotal base**: `cantidad × precio_venta`
2. **Aplicación de descuentos/recargos**: 
   - Si descuento > 0: `subtotal_base - descuento`
   - Si recargo > 0: `subtotal_base + recargo`
3. **Cálculo de impuestos**:
   - Suma de porcentajes de impuestos del producto
   - Base gravable = subtotal_ajustado / (1 + tasa_impuesto_total)
   - Valor impuesto = subtotal_ajustado - base_gravable

#### Totales acumulados:
- `subtotal`: Suma de todas las bases gravables
- `valor_impuestos`: Suma de todos los valores de impuesto
- `total`: Suma de subtotal + valor_impuestos

**Fórmula general**:
```
base_gravable = subtotal_con_descuento_recargo / (1 + tasa_impuesto_total)
valor_impuesto = subtotal_con_descuento_recargo - base_gravable
```

### 7. Validación de Reglas de Negocio

**Método**: `validateBusinessRules()`

**Validaciones**:
- `valor_recibido >= total` (si se proporciona)
- `cambio = valor_recibido - total` (si se proporciona)
- Reglas específicas del negocio


### 9. Transacción de Base de Datos

**Método**: Transacción en `store()`

**Pasos en orden**:
1. **Crear factura principal**: `Factura::create($validatedData)`
2. **Guardar productos**: `saveFacturaDetails()`
3. **Guardar retenciones**: `saveFacturaRetenciones()` (si aplica)
4. **Actualizar consecutivo**: `updateConsecutiveNumber()`

#### Guardado de Productos (`saveFacturaDetails()`)
- Crea registros en `factura_has_products`
- Campos: `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`, `descuento`, `recargo`

#### Guardado de Retenciones (`saveFacturaRetenciones()`)
- Crea registros en `factura_has_retenciones`
- Calcula valor de retención: `(total_factura × porcentaje) / 100`
- Campos: `factura_id`, `retencion_id`, `concepto_retencion_id`, `valor`, `percentage`

### 10. Procesamiento Automático

**Método**: `processAutomaticSubmission()`

- Verifica si el `tipo_movimiento` tiene `envio_automatico` habilitado
- Si sí, inicia el proceso de envío a DIAN (excluido de este documento)

## Estructura de Datos

### Request de Entrada
```json
{
  "tipo_movimiento_id": 1,
  "tipo_factura_id": 1,
  "cliente_id": 1,
  "empresa_id": 1,
  "medio_pago_id": 1,
  "tipo_pagos_id": 1,
  "centro_costo_id": 1,
  "productos": [
    {
      "id": 1,
      "cantidad": 2,
      "descuento": 1000,
      "recargo": 0
    }
  ],
  "retenciones": [
    {
      "retencion_id": 1,
      "concepto_retencion_id": 1,
      "porcentaje_valor": 2.5
    }
  ],
  "valor_recibido": 100000,
  "observaciones": "Factura de prueba"
}
```

### Datos Calculados Automáticamente
```json
{
  "subtotal": 50000.00,
  "valor_impuestos": 9500.00,
  "total": 59500.00,
  "cambio": 40500.00,
  "numero_factura": "FV001-0001",
  "issue_date": "2025-09-14",
  "estado": "creada"
}
```

## Manejo de Errores y Rollback

### Reversión de Inventario
- Si ocurre un error después de actualizar inventario, se revierte automáticamente
- Método: `revertProductInventory()`

### Logging Extensivo
- Todos los pasos críticos se registran con `Log::debug()` y `Log::error()`
- Incluye IDs de usuario, timestamps y detalles de operaciones

### Validaciones con Mensajes Detallados
- Errores de validación incluyen códigos específicos y datos de contexto
- Método: `logValidationError()`

## Consideraciones Técnicas

### Transacciones de Base de Datos
- Todo el proceso de guardado ocurre dentro de `DB::transaction()`
- Garantiza atomicidad: todo o nada

### Soft Deletes
- Todas las tablas relacionadas usan `SoftDeletes`
- Permite recuperación de datos eliminados accidentalmente

### Índices y Constraints
- Claves foráneas con `onDelete('cascade')` para integridad referencial
- Índices en campos frecuentemente consultados

### Cálculos de Precisión
- Todos los cálculos usan `round( , 2)` para evitar errores de punto flotante
- Ajustes automáticos por redondeo cuando es necesario

## Casos de Uso Especiales

### Facturas con Retenciones
- Las retenciones se calculan sobre el total de la factura
- Se almacenan en tabla separada para trazabilidad

### Facturas sin Impuestos
- Productos sin configuración de impuestos tienen `base_gravable = subtotal_ajustado`
- `valor_impuesto = 0`

### Descuentos y Recargos
- Aplicados a nivel de producto individual
- Valores absolutos, no porcentajes

## Monitoreo y Auditoría

### Logs de Operaciones
- Cada factura creada registra métricas de rendimiento
- Incluye tiempo de procesamiento y conteo de productos

### Estados de Factura
- `creada`: Factura guardada exitosamente
- `enviado a dian`: Después del envío (no cubierto aquí)

### Trazabilidad
- Todos los cambios se auditan con timestamps
- Relaciones permiten reconstruir estado completo en cualquier momento

---

**Fecha de creación**: Septiembre 14, 2025
**Versión**: 1.0
**Autor**: Sistema de Documentación Automática</content>
<filePath">docs/guardado_factura_completa.md
