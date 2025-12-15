# Sincronización de Plantilla Import/Export de Productos

## Resumen de Cambios

Se ha modificado el sistema de importación de productos para que la plantilla sea **exactamente igual** al archivo que genera el export de productos.

## Cambios Realizados

### 1. ProductosImport.php - Actualizado
- ✅ Soporte para todas las columnas del export
- ✅ Compatibilidad con formato legacy
- ✅ Procesamiento de precios con formato de moneda
- ✅ Manejo de códigos de barras
- ✅ Soporte para empresa específica en cada fila

### 2. ImportProductosController.php - Actualizado
- ✅ Generación dinámica de plantilla basada en ProductosExport
- ✅ Plantilla con mismo formato y estilos que el export
- ✅ Datos de ejemplo realistas

### 3. Nuevos Archivos de Plantilla
- ✅ `plantilla_importacion_productos.csv` - Nueva plantilla principal
- ✅ Mantiene `ejemplo_productos.csv` para compatibilidad

## Estructura de Columnas

### Columnas Soportadas (Formato Nuevo - Igual al Export):
1. **ID** - Ignorado en importación
2. **Nombre** - OBLIGATORIO
3. **Descripción** - Opcional
4. **Código de Barras** - Opcional
5. **Tipo de Producto** - Opcional (usa ID 2 por defecto)
6. **Tipo de Oro** - Opcional
7. **Precio de Venta** - Opcional (acepta formato $123.456,78)
8. **Precio de Compra** - Opcional (acepta formato $123.456,78)
9. **Empresa** - Opcional (usa empresa del usuario si vacío)
10. **Fecha de Creación** - Ignorado en importación
11. **Última Actualización** - Ignorado en importación

### Compatibilidad con Formato Legacy:
- `nombre` ✅
- `descripcion` ✅  
- `tipo_producto` ✅ (mapea a `tipo_de_producto`)
- `tipo_oro` ✅ (mapea a `tipo_de_oro`)

## Características Implementadas

### Procesamiento de Precios
```php
// Acepta formatos como:
"$250.000,00"    // Con símbolo y separadores
"250000.50"      // Solo números
"250,000.50"     // Formato internacional
```

### Manejo de Empresas
- Si no se especifica empresa → usa empresa del usuario
- Si dice "Global" → producto global (empresa_id = null)
- Si especifica empresa → busca por razón social

### Compatibilidad Total
- ✅ Archivos antiguos (formato legacy) siguen funcionando
- ✅ Archivos nuevos (formato export) funcionan perfectamente
- ✅ Plantilla descargable siempre actualizada

## Beneficios

1. **Consistencia Total**: Import = Export
2. **Sin Confusión**: Usuario descarga export, modifica, importa
3. **Mantiene Compatibilidad**: Archivos existentes siguen funcionando
4. **Más Información**: Soporte para códigos de barras, precios, empresas
5. **Mejor UX**: Una sola estructura para todo

## Uso Recomendado

1. **Para nuevas importaciones**: Usar export como base
2. **Para archivos legacy**: Siguen funcionando sin cambios
3. **Para plantilla**: Descargar desde el sistema (siempre actualizada)

## Archivos Afectados

- `app/Imports/ProductosImport.php` ✅ Actualizado
- `app/Http/Controllers/ImportProductosController.php` ✅ Actualizado  
- `plantilla_importacion_productos.csv` ✅ Creado
- `SISTEMA_IMPORTACION_PRODUCTOS.md` ✅ Actualizado
