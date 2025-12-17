# Plan de Actualización de Seeders

## Objetivo
Mover TODOS los datos de las migraciones a sus respectivos seeders, dejando las migraciones solo con la estructura de tablas.

## Seeders a Actualizar/Crear

### 1. ✅ TipoDocumentoSeeder - COMPLETADO
### 2. ✅ TipoPersonaSeeder - COMPLETADO  
### 3. ✅ TipoResponsabilidadSeeder - COMPLETADO
### 4. ✅ TipoMedidaSeeder - COMPLETADO

### 5. ImpuestoSeeder - PENDIENTE
- Migración: `2025_08_23_172243_create_impuestos_table.php`
- Actualizar con TODOS los impuestos de la migración (17 registros)

### 6. TipoPagoSeeder - OK (ya tiene los datos correctos)

### 7. MedioPagoSeeder - PENDIENTE
- Migración: `2025_08_31_093245_create_medio_pagos_table.php`
- Actualizar con TODOS los medios de pago (73 registros)

### 8. TipoFacturaSeeder - PENDIENTE
- Migración: `2025_08_31_093822_create_tipo_facturas_table.php`
- Actualizar con TODOS los tipos de factura (7 registros)

### 9. TipoRetencionSeeder - PENDIENTE
- Migración: `2025_09_14_145646_create_tipo_retencions_table.php`

### 10. ConceptoRetencionSeeder - PENDIENTE
- Migración: `2025_09_14_145716_create_concepto_retenciones_table.php`

### 11. ImpuestoPorcentajeSeeder - CREAR NUEVO
- Migración: `2025_09_14_151026_create_impuesto_porcentajes_table.php`

### 12. Otros seeders según las migraciones encontradas

## Migraciones a Limpiar
Todas las migraciones que actualmente tienen `DB::table()->insert()` deben ser limpiadas, dejando solo la estructura `Schema::create()`.

## UserSeeder
Debe tener 3 usuarios admin como solicitó el usuario.
