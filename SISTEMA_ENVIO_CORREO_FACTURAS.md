# Sistema de Envío de Facturas por Correo Electrónico

## Descripción General

Se ha implementado un sistema completo para el envío de facturas por correo electrónico desde el índice de facturas. Esta funcionalidad permite enviar archivos ZIP que contienen:

- **PDF de la representación gráfica** de la factura
- **XML oficial** validado por la DIAN  
- **Attached Document** (contenedor XML oficial)

## Características Implementadas

### 1. Botón de Envío en el Índice

- **Ubicación**: Tabla de facturas, columna de acciones (al lado del botón PDF)
- **Icono**: Sobre de correo electrónico (verde)
- **Condiciones de habilitación**:
  - Factura debe estar en estado "enviado a dian"
  - Debe tener CUFE generado
  - Cliente debe tener email asociado
- **Funcionalidad**: Muestra botón deshabilitado con tooltip explicativo cuando no se cumplen las condiciones

### 2. Procesamiento Asíncrono

- **Queue System**: Utiliza el sistema de colas de Laravel
- **Job**: `EnviarCorreoFactura` - maneja todo el procesamiento en segundo plano
- **Timeout**: 120 segundos por intento
- **Reintentos**: Máximo 3 intentos automáticos
- **Delay**: 5 segundos de retraso para asegurar disponibilidad de datos

### 3. Generación de Archivos

#### PDF
- Utiliza la misma vista que el sistema existente: `facturas.pdf.factura`
- Configuración DomPDF optimizada para email
- Incluye código QR de verificación

#### XML
- Descarga el XML desde la URL almacenada en `factura.xml_url`
- Validación de disponibilidad del archivo
- Manejo de errores de red

#### Attached Document
- Genera el XML contenedor usando la vista `attached_documents.invoice`
- Incluye la respuesta de la DIAN si está disponible
- Estructura UBL 2.1 compatible

### 4. Empaquetado ZIP

El archivo ZIP contiene:
- `factura_{numero_factura}.pdf`
- `factura_{numero_factura}.xml` 
- `attached_document_{numero_factura}.xml`

### 5. Envío de Correo

- **Template**: Vista diseñada específicamente para facturas electrónicas
- **Destinatario**: Email del cliente asociado a la factura
- **Asunto**: "Factura Electrónica No. {numero_factura}"
- **Adjunto**: Archivo ZIP con todos los documentos

## Archivos Creados/Modificados

### Nuevos Archivos

1. **`app/Jobs/EnviarCorreoFactura.php`**
   - Job principal para procesamiento asíncrono
   - Manejo de errores y reintentos
   - Generación de archivos y envío

2. **`app/Mail/FacturaEnviada.php`** 
   - Mailable para el correo electrónico
   - Configuración de adjuntos
   - Compatible con Laravel 10+

3. **`resources/views/emails/factura-enviada.blade.php`**
   - Template HTML del correo
   - Diseño responsive
   - Información completa de la factura

### Archivos Modificados

1. **`app/Http/Controllers/FacturaController.php`**
   - Nuevo método `enviarPorCorreo()`
   - Validaciones de seguridad
   - Logging detallado

2. **`resources/views/facturas/index.blade.php`**
   - Botón de envío por correo en acciones
   - Lógica condicional de habilitación
   - Confirmación de usuario

3. **`routes/web.php`**
   - Nueva ruta: `facturas/{factura}/enviar-correo`
   - Middleware de permisos aplicado

## Validaciones de Seguridad

### Pre-envío
- ✅ Factura debe existir
- ✅ Factura debe estar enviada a DIAN
- ✅ Cliente debe tener email
- ✅ Empresa debe estar configurada
- ✅ No duplicar envíos pendientes

### Durante el procesamiento
- ✅ Validación de disponibilidad de XML
- ✅ Verificación de integridad de archivos
- ✅ Manejo de errores de red
- ✅ Limpieza de archivos temporales

## Monitoreo y Logs

### Eventos Registrados
- Inicio del job de envío
- Generación de archivos
- Envío exitoso de correo
- Errores y excepciones
- Limpieza de archivos temporales

### Métodos de Consulta
```php
// Verificar jobs pendientes para una factura
EnviarCorreoFactura::hasPendingJobs($facturaId);

// Obtener estadísticas generales
EnviarCorreoFactura::getEmailJobStats();
```

## Configuración Requerida

### Variables de Entorno
```env
# Configuración de correo (Mailjet recomendado)
MAIL_MAILER=smtp
MAIL_HOST=in-v3.mailjet.com
MAIL_PORT=587
MAIL_USERNAME=tu_api_key_mailjet
MAIL_PASSWORD=tu_secret_key_mailjet
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@dominio.com
MAIL_FROM_NAME="Tu Empresa"

# Sistema de colas
QUEUE_CONNECTION=database
```

### Base de Datos
- Tabla `jobs` para el sistema de colas
- Tabla `failed_jobs` para jobs fallidos
- Ambas incluidas en las migraciones estándar de Laravel

## Flujo de Operación

1. **Usuario hace clic** en el botón de envío por correo
2. **Validaciones iniciales** en el controlador
3. **Job encolado** con delay de 5 segundos
4. **Procesamiento asíncrono**:
   - Cargar factura con relaciones
   - Validar condiciones de envío
   - Generar PDF de la factura
   - Descargar XML desde URL
   - Generar attached document
   - Crear archivo ZIP
   - Enviar correo con adjunto
   - Limpiar archivos temporales
5. **Notificación** al usuario del resultado

## Manejo de Errores

### Errores Comunes
- **Cliente sin email**: Job se cancela sin error
- **Factura sin XML**: Job se cancela sin error  
- **Error de red**: Reintento automático
- **Error de generación**: Log detallado y reintento

### Recuperación
- Hasta 3 reintentos automáticos
- Jobs fallidos registrados en `failed_jobs`
- Logs detallados para debugging
- Limpieza automática de archivos temporales

## Uso

### Para Usuarios
1. Ir al índice de facturas
2. Localizar la factura enviada a DIAN
3. Hacer clic en el botón verde de correo
4. Confirmar el envío
5. Esperar la notificación de éxito

### Para Administradores
- Monitorear logs en `storage/logs/laravel.log`
- Revisar jobs fallidos en la tabla `failed_jobs`
- Configurar sistema de colas en producción
- Asegurar configuración correcta de Mailjet

## Beneficios

✅ **Automatización completa** del envío de documentos fiscales
✅ **Procesamiento asíncrono** sin bloquear la interfaz
✅ **Archivos completos** incluyendo attached document
✅ **Validaciones robustas** de seguridad
✅ **Monitoreo detallado** de operaciones
✅ **Manejo inteligente** de errores
✅ **Interfaz intuitiva** para usuarios
