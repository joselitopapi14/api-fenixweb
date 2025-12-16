# Seeders Creados para API Fenix Web

## 📋 Resumen

Se han creado **14 seeders** para poblar la base de datos con todos los catálogos necesarios y datos de prueba.

## 🗂️ Seeders de Catálogos

### 1. **TipoFacturaSeeder**
- Factura de Venta (código 01)
- Nota Crédito (código 91)
- Nota Débito (código 92)

### 2. **MedioPagoSeeder**
- Efectivo
- Tarjeta Débito
- Tarjeta Crédito
- Transferencia Bancaria
- PSE
- Consignación Bancaria

### 3. **TipoPagoSeeder**
- Contado
- Crédito

### 4. **ImpuestoSeeder**
Crea impuestos y sus porcentajes:
- **IVA**: 0%, 5%, 19%
- **INC**: 4%, 8%, 16%
- **ICA**: 0.966%

### 5. **TipoProductoSeeder**
- Producto
- Servicio
- Oro

### 6. **TipoMedidaSeeder**
- Unidad (UND)
- Kilogramo (KG)
- Gramo (GR)
- Metro (MT)
- Litro (LT)
- Caja (CJ)

### 7. **TipoRetencionSeeder**
- ReteIVA (código 05)
- ReteICA (código 07)
- ReteFuente (código 06)
- ReteRenta (código 01)

### 8. **ConceptoRetencionSeeder**
Conceptos de retención con sus porcentajes:
- **ReteIVA**: Bienes (15%), Servicios (15%)
- **ReteICA**: Actividades Industriales (0.414%), Comerciales (0.966%), Servicios (0.966%)
- **ReteFuente**: Compras (2.5%), Honorarios (11%), Servicios (4%), Arrendamientos (3.5%)

### 9. **TipoDocumentoSeeder**
- RC (Registro Civil)
- TI (Tarjeta de Identidad)
- CC (Cédula de Ciudadanía)
- TE (Tarjeta de Extranjería)
- CE (Cédula de Extranjería)
- NIT (Número de Identificación Tributaria)
- PP (Pasaporte)
- DIE (Documento de Identificación Extranjero)

### 10. **TipoPersonaSeeder**
- Persona Natural
- Persona Jurídica

### 11. **TipoResponsabilidadSeeder**
- Gran Contribuyente (O-13)
- Autorretenedor (O-15)
- Agente de Retención IVA (O-23)
- Régimen Simple de Tributación (O-47)
- No Responsable de IVA (R-99-PN)

## 🧪 Seeders de Datos de Prueba

### 12. **EmpresaSeeder**
Crea 1 empresa de prueba:
- Razón Social: EMPRESA DE PRUEBA S.A.S
- NIT: 900123456-7
- Email: contacto@empresaprueba.com

### 13. **ClienteSeeder**
Crea 2 clientes de prueba:
1. **Persona Natural**: Juan Pérez Gómez (CC 1234567890)
2. **Persona Jurídica**: Cliente Corporativo S.A.S (NIT 900987654-3)

### 14. **ProductoSeeder**
Crea 2 productos de prueba con IVA 19%:
1. **Producto de Prueba** - $50,000
2. **Servicio de Consultoría** - $150,000

## 🚀 Cómo ejecutar los seeders

### Opción 1: Ejecutar todos los seeders
```bash
php artisan db:seed
```

### Opción 2: Ejecutar un seeder específico
```bash
php artisan db:seed --class=TipoFacturaSeeder
```

### Opción 3: Refrescar la base de datos y ejecutar seeders
```bash
php artisan migrate:fresh --seed
```

## ⚠️ Importante

- Los seeders se ejecutan en el orden definido en `DatabaseSeeder.php`
- Asegúrate de que las migraciones estén ejecutadas antes de correr los seeders
- Los datos de prueba (Empresa, Cliente, Producto) usan IDs fijos (1, 2, etc.)

## ✅ Después de ejecutar los seeders

Podrás crear facturas usando:
- **empresa_id**: 1
- **cliente_id**: 1 o 2
- **productos**: ID 1 o 2
- **tipo_factura_id**: 1 (Factura de Venta)
- **medio_pago_id**: 1 (Efectivo)
- **tipo_pagos_id**: 1 (Contado)
- **tipo_movimiento_id**: Debes crear una resolución de facturación primero

## 📝 Próximos pasos

1. Ejecutar los seeders
2. Crear una resolución de facturación para la empresa
3. Probar la creación de facturas con los datos de prueba
