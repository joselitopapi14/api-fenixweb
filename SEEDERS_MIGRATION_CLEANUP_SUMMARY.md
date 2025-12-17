# Resumen de Cambios: Separación de Migraciones y Seeders

## ✅ Completado

### Seeders Actualizados con Datos Completos:
1. **TipoDocumentoSeeder** - 11 registros (code, name, abreviacion, activo)
2. **TipoPersonaSeeder** - 2 registros (name, code)
3. **TipoResponsabilidadSeeder** - 5 registros (name, code)
4. **TipoMedidaSeeder** - 9 registros (nombre, abreviatura, descripcion)
5. **ImpuestoSeeder** - 17 registros completos (name, code) + porcentajes
6. **TipoPagoSeeder** - 2 registros (ya estaba correcto)
7. **MedioPagoSeeder** - 73 registros completos (name, code)
8. **TipoFacturaSeeder** - 7 registros completos (name, code)
9. **UserSeeder** - 3 usuarios admin (Gabriel, Ronal, Jose)

### Migraciones Limpiadas (solo estructura, sin datos):
1. `2025_08_22_211821_create_tipo_documentos_table.php` ✅
2. `2025_08_31_093506_create_tipo_personas_table.php` ✅
3. `2025_08_31_093605_create_tipo_responsabilidads_table.php` ✅
4. `2025_08_23_170856_create_tipo_medidas_table.php` ✅
5. `2025_08_23_172243_create_impuestos_table.php` ✅
6. `2025_08_31_093213_create_tipo_pagos_table.php` ✅
7. `2025_08_31_093245_create_medio_pagos_table.php` ✅
8. `2025_08_31_093822_create_tipo_facturas_table.php` ✅

## Pendientes (si existen más migraciones con datos)
- TipoRetencionSeeder
- ConceptoRetencionSeeder
- ImpuestoPorcentajeSeeder
- Otros según las migraciones restantes

## Comando para Ejecutar
```bash
php artisan migrate:fresh --seed
```

Este comando ahora:
1. Eliminará todas las tablas
2. Creará todas las tablas (solo estructura)
3. Ejecutará todos los seeders (insertando todos los datos)
