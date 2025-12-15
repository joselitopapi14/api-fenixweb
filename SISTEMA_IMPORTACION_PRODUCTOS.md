# Sistema de Importación de Productos - Fenix Gold

## Descripción
Sistema completo para importar productos desde archivos Excel (.xlsx, .xls, .csv) con análisis inteligente de tipos de producto y oro. **La plantilla de importación es idéntica al archivo de exportación de productos.**

## ⚠️ IMPORTANTE: Estructura de la Plantilla

La plantilla de importación debe tener exactamente las mismas columnas que el export de productos:

### Columnas Requeridas (en orden):
1. **ID** - Se puede dejar vacío para productos nuevos
2. **Nombre** - OBLIGATORIO
3. **Descripción** - Opcional
4. **Código de Barras** - Opcional
5. **Tipo de Producto** - Opcional (usa ID 2 por defecto si está vacío)
6. **Tipo de Oro** - Opcional
7. **Precio de Venta** - Opcional (acepta formato con $ y separadores)
8. **Precio de Compra** - Opcional (acepta formato con $ y separadores)
9. **Empresa** - Opcional (usa empresa del usuario si está vacío)
10. **Fecha de Creación** - Se ignora en importación
11. **Última Actualización** - Se ignora en importación

### Archivos de Plantilla:
- **`plantilla_importacion_productos.csv`** - Plantilla principal
- **`ejemplo_productos.csv`** - Plantilla legacy (mantener compatibilidad)

## Archivos Creados

### Backend (Laravel)

#### Controladores
- **`app/Http/Controllers/ImportProductosController.php`**
  - Controlador principal para manejar la importación
  - Métodos: `index()`, `import()`, `descargarPlantilla()`, `previsualizarArchivo()`

#### Requests
- **`app/Http/Requests/ImportProductosRequest.php`**
  - Validación del formulario de importación
  - Valida archivo, empresa y modo de importación

#### Imports (Laravel Excel)
- **`app/Imports/ProductosImport.php`**
  - Clase principal para procesar el Excel
  - Análisis inteligente de datos
  - Creación automática de tipos de producto y oro
  - Manejo de errores y duplicados

#### Jobs (Opcional para archivos grandes)
- **`app/Jobs/ProcesarImportacionProductos.php`**
  - Procesamiento asíncrono para archivos grandes
  - Sin notificaciones por email

### Plantilla Excel
- **`public/assets/excel/plantilla_productos.xlsx`**
  - Archivo estático con formato y ejemplos
  - Se descarga directamente desde el servidor

### Frontend

#### Vistas
- **`resources/views/admin/productos/import.blade.php`**
  - Formulario completo de importación
  - Previsualización de archivos
  - Drag & drop functionality
  - Alertas y manejo de errores

### Rutas
```php
// En routes/web.php
Route::prefix('productos/import')->middleware(['permission:registros.create', 'empresa.access'])->group(function () {
    Route::get('/', [ImportProductosController::class, 'index'])->name('productos.import.index');
    Route::post('/procesar', [ImportProductosController::class, 'import'])->name('productos.import.procesar');
    Route::get('/plantilla', [ImportProductosController::class, 'descargarPlantilla'])->name('productos.import.plantilla');
    Route::post('/preview', [ImportProductosController::class, 'previsualizarArchivo'])->name('productos.import.preview');
});
```

## Formato del Excel

### Columnas Requeridas
1. **nombre** (OBLIGATORIO) - Nombre único del producto
2. **descripcion** (OPCIONAL) - Descripción del producto
3. **tipo_producto** (OPCIONAL) - Tipo de producto (se crea automáticamente, si está vacío usa tipo ID 2)
4. **tipo_oro** (OPCIONAL) - Tipo de oro (se crea automáticamente)

### Ejemplo de datos:
| nombre | descripcion | tipo_producto | tipo_oro |
|--------|-------------|---------------|----------|
| Anillo de Oro 18K | Anillo elegante de oro 18K | Joyería | Oro 18K |
| Cadena de Plata | Cadena de plata 925 | | |
| Lingote de Oro | Lingote de inversión | Inversión | Oro Puro |

**Nota**: Si el campo `tipo_producto` está vacío o no existe, se asigna automáticamente el tipo de producto con ID 2.

## Características Principales

### 1. Análisis Inteligente
- Detecta automáticamente tipos de producto y oro
- Crea registros si no existen
- Valida duplicados por empresa

### 2. Modos de Importación
- **Solo Crear**: Solo productos nuevos
- **Solo Actualizar**: Solo productos existentes  
- **Crear y Actualizar**: Modo completo

### 3. Gestión por Empresa
- Productos específicos por empresa
- Productos globales (sin empresa)
- Validación de unicidad por empresa

### 4. Validaciones
- Campos obligatorios
- Formatos de archivo
- Tamaño máximo (10MB)
- Detección de duplicados

### 5. Manejo de Errores
- Logs detallados
- Resumen de importación
- Lista de errores específicos
- Continuación en caso de errores

### 6. Previsualización
- Vista previa del archivo antes de importar
- Validación de estructura
- Información de filas totales

## Uso del Sistema

### 1. Acceso
Navegar a: `/productos/import`

### 2. Descargar Plantilla
- Clic en "Descargar Plantilla"
- Completar con datos reales
- Seguir formato de ejemplo

### 3. Configurar Importación
- Seleccionar empresa (opcional)
- Elegir modo de importación
- Subir archivo Excel

### 4. Procesar
- El sistema valida y procesa
- Muestra resumen de resultados
- Lista errores si los hay

## Permisos Requeridos
- `registros.create` - Para importar productos
- `empresa.access` - Para acceso por empresa

## Archivos Soportados
- Excel: .xlsx, .xls
- CSV: .csv
- Tamaño máximo: 10MB

## Logging
Todos los eventos se registran en logs de Laravel:
- Importaciones exitosas
- Errores de procesamiento
- Creación de tipos automáticos
- Estadísticas de importación

## Consideraciones Técnicas

### Performance
- Procesamiento por chunks (100 filas)
- Validación por lotes
- Optimización de queries

### Seguridad
- Validación de tipos de archivo
- Sanitización de datos
- Permisos por empresa

### Escalabilidad
- Job asíncrono disponible
- Manejo de archivos grandes
- Limpieza automática de temporales
