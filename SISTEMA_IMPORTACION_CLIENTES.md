# Sistema de Importación y Exportación de Clientes

## Descripción
Este sistema implementa las funcionalidades de importación y exportación de clientes siguiendo exactamente el mismo patrón que el sistema de productos existente, garantizando una experiencia de usuario consistente y retrocompatible.

## Características Principales

### 🔄 Exportación de Clientes
- **Ruta**: `/empresas/{empresa}/clientes/export`
- **Formato**: Excel (.xlsx)
- **Columnas incluidas**:
  - Información básica: ID, nombres, apellidos, email, teléfono
  - Documentación: tipo y número de documento
  - Ubicación geográfica: departamento, municipio, comuna, barrio
  - Redes sociales: Instagram, Facebook, TikTok, YouTube
  - Datos adicionales: género, fecha de nacimiento, dirección
  - Campos condicionales según tipo de persona (natural/jurídica)

### 📥 Importación de Clientes
- **Ruta**: `/clientes/import`
- **Formato soportado**: Excel (.xlsx, .xls), CSV
- **Características**:
  - Validación automática de datos
  - Previsualización antes de importar
  - Manejo de errores detallado
  - Procesamiento en segundo plano para archivos grandes
  - Historial completo de importaciones

### 📊 Validaciones Implementadas

#### Validaciones Básicas
- **Email**: formato válido y único por empresa
- **Teléfono**: formato numérico válido
- **Documento**: único por empresa según tipo

#### Validaciones Condicionales (según tipo de documento)
**Persona Natural (tipo_documento_id != 6)**:
- Nombres y apellidos obligatorios
- Fecha de nacimiento opcional
- Género opcional

**Persona Jurídica (tipo_documento_id == 6)**:
- Solo razón social obligatoria
- Nombres, apellidos, fecha nacimiento y género se ignoran

#### Validaciones Geográficas
- **Departamento**: debe existir en la base de datos
- **Municipio**: debe pertenecer al departamento especificado
- **Comuna**: debe pertenecer al municipio especificado
- **Barrio**: debe pertenecer a la comuna especificada
- Resolución automática por nombres (case-insensitive)

## Archivos del Sistema

### Clases Principales
```
app/
├── Exports/
│   └── ClientesExport.php          # Clase de exportación
├── Imports/
│   └── ClientesImport.php          # Clase de importación
├── Http/Controllers/
│   └── ImportClientesController.php # Controlador principal
├── Models/
│   └── ClientImportHistory.php     # Modelo de historial
└── Http/Requests/
    └── ImportClientesRequest.php   # Validación de requests
```

### Vistas del Sistema
```
resources/views/clientes/
├── import/
│   ├── index.blade.php           # Página principal de importación
│   ├── historial.blade.php       # Historial de importaciones
│   └── detalle.blade.php         # Detalle de importación específica
```

### Base de Datos
```
database/migrations/
└── 2025_08_23_143340_create_client_import_histories_table.php
```

## Rutas del Sistema

### Rutas de Importación
```php
GET    /clientes/import                     # Página principal
POST   /clientes/import/procesar           # Procesar importación
GET    /clientes/import/plantilla          # Descargar plantilla
POST   /clientes/import/preview            # Previsualizar archivo

// Historial
GET    /clientes/import/historial          # Lista de historiales
GET    /clientes/import/historial/{id}     # Detalle específico
GET    /clientes/import/historial/{id}/descargar # Descargar archivo
GET    /clientes/import/api/historial      # API para DataTables
```

### Ruta de Exportación
```php
GET    /empresas/{empresa}/clientes/export # Exportar clientes
```

## Flujo de Trabajo

### 1. Exportación
1. Usuario hace clic en "Exportar" desde la lista de clientes
2. Sistema genera archivo Excel con todos los clientes de la empresa
3. Descarga automática del archivo

### 2. Importación
1. Usuario accede a `/clientes/import`
2. Descarga plantilla Excel (opcional)
3. Completa datos en la plantilla
4. Sube archivo para previsualización
5. Revisa datos y confirma importación
6. Sistema procesa en segundo plano
7. Notificación de resultado y acceso al historial

## Formato de Plantilla Excel

### Columnas Obligatorias
- `tipo_documento_id`: ID del tipo de documento
- `email`: Correo electrónico único
- `departamento_nombre`: Nombre del departamento
- `municipio_nombre`: Nombre del municipio  
- `comuna_nombre`: Nombre de la comuna
- `barrio_nombre`: Nombre del barrio

### Columnas Condicionales
**Para Persona Natural**:
- `nombres`: Obligatorio
- `apellidos`: Obligatorio
- `fecha_nacimiento`: Opcional (formato: YYYY-MM-DD)
- `genero`: Opcional (M/F)

**Para Persona Jurídica**:
- `razon_social`: Obligatorio

### Columnas Opcionales
- `numero_documento`: Número de identificación
- `telefono`: Teléfono de contacto
- `direccion`: Dirección física
- `instagram`: Usuario de Instagram
- `facebook`: Perfil de Facebook
- `tiktok`: Usuario de TikTok
- `youtube`: Canal de YouTube

## Manejo de Errores

### Tipos de Errores
1. **Errores de Formato**: archivo no válido, columnas faltantes
2. **Errores de Validación**: datos que no cumplen reglas de negocio
3. **Errores de Base de Datos**: violaciones de unicidad, referencias inexistentes

### Reporte de Errores
- Cada error incluye número de fila y descripción detallada
- Errores se almacenan en el historial para revisión posterior
- Estadísticas de éxito/error por importación

## Seguridad y Permisos

### Permisos Requeridos
- **Importación**: `empresas.edit`
- **Exportación**: acceso a la empresa correspondiente
- **Historial**: `empresas.edit`

### Validaciones de Seguridad
- Usuarios solo pueden importar/exportar clientes de sus empresas asignadas
- Archivos se almacenan de forma segura en storage privado
- Validación de tipos de archivo permitidos

## Integración con UI Existente

### Botones en Lista de Clientes
El sistema se integra perfectamente con la interfaz existente mediante un dropdown que incluye:
- **Exportar**: descarga inmediata de datos
- **Importar**: acceso al sistema de importación
- **Historial**: revisar importaciones anteriores

### Consistencia Visual
- Mismos estilos que el sistema de productos
- Iconografía consistente
- Mensajes de usuario uniformes
- Navegación intuitiva

## Monitoreo y Auditoría

### Historial Completo
Cada importación registra:
- Usuario que realizó la operación
- Archivo original (almacenado para auditoría)
- Fecha y hora de procesamiento
- Estadísticas detalladas (exitosos, errores, duplicados)
- Lista completa de errores encontrados

### Métricas Disponibles
- Número total de registros procesados
- Tasa de éxito por importación
- Errores más comunes
- Tiempo de procesamiento

## Mantenimiento

### Limpieza de Archivos
- Archivos de importación se mantienen para auditoría
- Recomendado implementar limpieza periódica de archivos antiguos
- Configuración de retención en `config/excel.php`

### Monitoreo de Performance
- Importaciones grandes se procesan en cola
- Monitoreo de memoria y tiempo de ejecución
- Logs detallados en `storage/logs/`

## Casos de Uso Comunes

### 1. Migración de Sistema Anterior
1. Exportar datos del sistema anterior a Excel
2. Adaptar formato a la plantilla de Fenix Gold
3. Importar por lotes pequeños para validar
4. Revisar errores y corregir datos
5. Importación final completa

### 2. Actualización Masiva de Datos
1. Exportar clientes actuales
2. Modificar datos en Excel
3. Importar con modo de actualización (por email único)
4. Verificar cambios en el historial

### 3. Ingreso de Nuevos Clientes
1. Descargar plantilla limpia
2. Completar datos de nuevos clientes
3. Validar con previsualización
4. Importar y verificar resultados

---

**Nota**: Este sistema mantiene 100% de compatibilidad con el flujo de trabajo existente del sistema de productos, garantizando una curva de aprendizaje mínima para los usuarios.
