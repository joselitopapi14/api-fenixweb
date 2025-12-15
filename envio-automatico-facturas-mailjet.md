# Documentación: Envío Automático de Facturas usando Mailjet

## Índice
1. [Descripción General](#descripción-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Configuración](#configuración)
4. [Flujo de Proceso](#flujo-de-proceso)
5. [Componentes del Sistema](#componentes-del-sistema)
6. [Archivos Adjuntos](#archivos-adjuntos)
7. [Manejo de Errores](#manejo-de-errores)
8. [Monitoreo y Logs](#monitoreo-y-logs)
9. [Configuración de Mailjet](#configuración-de-mailjet)
10. [Resolución de Problemas](#resolución-de-problemas)

---

## Descripción General

El sistema de envío automático de facturas permite el envío de facturas electrónicas por correo de manera automatizada una vez que la factura ha sido procesada exitosamente por la DIAN. Este proceso utiliza:

- **Sistema de colas de Laravel** para el procesamiento asíncrono
- **Mailjet** como proveedor de servicios de correo electrónico
- **Generación automática de PDF** de la factura
- **Adjuntos ZIP** con PDF y XML de la factura

---

## Arquitectura del Sistema

```
Factura Procesada por DIAN
         ↓
    Job encolado (EnviarCorreoFactura)
         ↓
    Generación de PDF
         ↓
    Descarga de XML
         ↓
    Creación de ZIP (PDF + XML)
         ↓
    Envío vía Mailjet
         ↓
    Limpieza de archivos temporales
```

---

## Configuración

### Variables de Entorno

Para configurar Mailjet, debe establecer las siguientes variables en su archivo `.env`:

```bash
# Configuración de correo con Mailjet
MAIL_MAILER=smtp
MAIL_HOST=in-v3.mailjet.com
MAIL_PORT=587
MAIL_USERNAME=tu_api_key_mailjet
MAIL_PASSWORD=tu_secret_key_mailjet
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@dominio.com
MAIL_FROM_NAME="Tu Empresa"

# Configuración de colas (recomendado: database o redis)
QUEUE_CONNECTION=database
```

### Configuración de la Empresa

En el modelo `Empresa`, debe activar el envío automático:

```php
// Campo en la tabla empresas
'envios_automaticos' => true
```

### Configuración del Tipo de Movimiento

En el modelo `TipoMovimiento`, debe activar el envío automático:

```php
// Campo en la tabla tipo_movimientos
'envio_automatico' => true
```

---

## Flujo de Proceso

### 1. Disparo Inicial

El proceso se inicia en el método `sendToDian()` del modelo `Factura.php`:

```php
// Líneas 480-495 del archivo Factura.php
if ($isValid && $this->empresa && $this->empresa->envios_automaticos) {
    try {
        \App\Jobs\EnviarCorreoFactura::dispatch($this->id)
            ->delay(now()->addSeconds(10));
        
        \Log::info('Job de correo automático encolado', [
            'factura_id' => $this->id,
            'numero_factura' => $this->numero_factura,
            'empresa_id' => $this->empresa_id,
            'cliente_email' => $this->cliente->email ?? 'sin email'
        ]);
    } catch (\Exception $e) {
        \Log::error('Error al encolar job de correo automático', [
            'factura_id' => $this->id,
            'error' => $e->getMessage()
        ]);
    }
}
```

**Condiciones para el envío:**
- La factura debe ser válida (`$isValid = true`)
- La empresa debe tener `envios_automaticos = true`
- El tipo de movimiento debe tener `envio_automatico = true`

### 2. Encolado del Job

El job `EnviarCorreoFactura` se encola con un retraso de 10 segundos para asegurar que todos los procesos anteriores estén completos.

---

## Componentes del Sistema

### 1. Job: EnviarCorreoFactura

**Archivo:** `app/Jobs/EnviarCorreoFactura.php`

**Propiedades principales:**
```php
public $tries = 3;          // Número máximo de intentos
public $timeout = 120;      // Timeout en segundos
```

**Método principal:**
```php
public function handle(): void
```

**Validaciones que realiza:**
- Verifica que la factura exista
- Verifica que la empresa tenga envíos automáticos habilitados
- Verifica que el cliente tenga email
- Verifica que la factura tenga XML generado

### 2. Mailable: FacturaEnviada

**Archivo:** `app/Mail/FacturaEnviada.php`

**Responsabilidades:**
- Define la estructura del correo electrónico
- Adjunta el archivo ZIP con PDF y XML
- Establece el asunto del correo

**Método principal:**
```php
public function build()
{
    $asunto = $this->asuntoPersonalizado ?: "Factura Electrónica No. {$this->factura->numero_factura}";
    
    $mail = $this->subject($asunto)
                ->view('emails.factura-enviada')
                ->with([
                    'factura' => $this->factura,
                    'empresa' => $this->factura->empresa,
                    'cliente' => $this->factura->cliente,
                ]);

    if ($this->zipPath && file_exists($this->zipPath)) {
        $mail->attach($this->zipPath, [
            'as' => "factura_{$this->factura->numero_factura}.zip",
            'mime' => 'application/zip',
        ]);
    }

    return $mail;
}
```

### 3. Vista del Correo

**Archivo:** `resources/views/emails/factura-enviada.blade.php`

Contiene el diseño HTML del correo electrónico que incluye:
- Información de la empresa
- Datos de la factura
- Información del cliente
- Diseño responsive

---

## Archivos Adjuntos

### Generación de PDF

El sistema genera un PDF de la factura utilizando DOMPDF con las siguientes características:

**Configuración optimizada:**
```php
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('defaultPaperSize', 'A4');
$options->set('dpi', 96);
```

**Tipos de formato:**
- **Letter (A4):** Para empresas con `tipo_impresion.code = '01'`
- **POS (80mm):** Para impresión térmica, papel de 80mm

**Incluye código QR:**
- URL de verificación según el ambiente de la empresa
- Producción: `https://catalogo-vpfe.dian.gov.co/Document/ShowDocumentToPublic/{cufe}`
- Habilitación: `https://catalogo-vpfe-hab.dian.gov.co/Document/ShowDocumentToPublic/{cufe}`

### Descarga de XML

El sistema descarga el XML firmado desde la URL almacenada en `factura.xml_url`.

### Creación del ZIP

Se crea un archivo ZIP que contiene:
- `factura_{numero_factura}.pdf`
- `factura_{numero_factura}.xml`

---

## Manejo de Errores

### Reintentos Automáticos

```php
public $tries = 3;  // Máximo 3 intentos
```

### Método de Fallo

```php
public function failed(\Throwable $exception): void
{
    Log::error("Falló definitivamente el envío de correo para factura", [
        'factura_id' => $this->facturaId,
        'error' => $exception->getMessage(),
        'attempts' => $this->attempts(),
    ]);
}
```

### Casos de Error Comunes

1. **Cliente sin email:** El job se cancela sin error
2. **Factura sin XML:** El job se cancela sin error
3. **Error de red/Mailjet:** Se reintenta automáticamente
4. **Error en generación de PDF:** Se registra error y se reintenta

---

## Monitoreo y Logs

### Logs de Información

```php
Log::info("Correo enviado exitosamente", [
    'factura_id' => $this->facturaId,
    'numero_factura' => $factura->numero_factura,
    'email' => $emailCliente,
    'asunto' => $asunto
]);
```

### Métodos de Monitoreo

El job incluye métodos estáticos para monitoreo:

```php
// Verificar si hay jobs pendientes para una factura
EnviarCorreoFactura::hasPendingJobs($facturaId);

// Obtener estadísticas generales
EnviarCorreoFactura::getEmailJobStats();
```

### Estadísticas Disponibles

```php
[
    'pendientes' => 5,        // Jobs en cola
    'fallidos_total' => 2,    // Jobs fallidos total
    'fallidos_hoy' => 1       // Jobs fallidos hoy
]
```

---

## Configuración de Mailjet

### 1. Crear Cuenta en Mailjet

1. Registrarse en [https://www.mailjet.com](https://www.mailjet.com)
2. Verificar el dominio de envío
3. Obtener las credenciales API

### 2. Configuración DNS

Para mejor deliverability, configurar registros SPF y DKIM:

```dns
TXT @ "v=spf1 include:spf.mailjet.com ?all"
TXT mailjet._domainkey "k=rsa; p=[clave_publica_dkim]"
```

### 3. Configuración en Laravel

**Archivo `config/mail.php`:**
```php
'mailers' => [
    'smtp' => [
        'transport' => 'smtp',
        'host' => 'in-v3.mailjet.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'timeout' => null,
        'auth_mode' => null,
    ],
],
```

### 4. Variables de Entorno para Mailjet

```bash
MAIL_MAILER=smtp
MAIL_HOST=in-v3.mailjet.com
MAIL_PORT=587
MAIL_USERNAME=tu_api_key_mailjet
MAIL_PASSWORD=tu_secret_key_mailjet
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=facturas@tuempresa.com
MAIL_FROM_NAME="Tu Empresa - Facturación"
```

---

## Resolución de Problemas

### Problema: Jobs no se procesan

**Causa:** Worker de colas no está ejecutándose

**Solución:**
```bash
php artisan queue:work --tries=3 --timeout=120
```

### Problema: Emails no llegan

**Verificaciones:**
1. Credenciales de Mailjet correctas
2. Dominio verificado en Mailjet
3. Cliente tiene email válido
4. Revisar logs de Laravel: `storage/logs/laravel.log`

### Problema: Error en generación de PDF

**Verificaciones:**
1. Vista del PDF existe y es accesible
2. Datos de la factura están completos
3. Suficiente memoria disponible para DOMPDF

### Problema: XML no disponible

**Verificaciones:**
1. `factura.xml_url` no está vacío
2. URL del XML es accesible
3. Factura fue procesada exitosamente por DIAN

### Comandos Útiles

```bash
# Ver jobs en cola
php artisan queue:work --once

# Ver jobs fallidos
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all

# Limpiar jobs fallidos
php artisan queue:flush

# Monitorear cola en tiempo real
php artisan queue:listen

# Ver estadísticas de cola
php artisan queue:monitor database
```

---

## Estructura de Archivos

```
app/
├── Jobs/
│   └── EnviarCorreoFactura.php
├── Mail/
│   └── FacturaEnviada.php
├── Models/Api/
│   └── Factura.php (método sendToDian)
└── ...

resources/views/
├── emails/
│   └── factura-enviada.blade.php
└── pdf/
    ├── letter/
    │   └── invoice.blade.php
    └── pos/
        └── invoice.blade.php

storage/app/
├── temp/           # Archivos temporales
├── facturas/       # XMLs firmados
└── ...

config/
├── mail.php
├── queue.php
└── ...
```

---

## Consideraciones de Seguridad

1. **Credenciales:** Nunca exponer las credenciales de Mailjet en el código
2. **Archivos temporales:** Se eliminan automáticamente después del envío
3. **Validación de emails:** Se valida que el cliente tenga email antes del envío
4. **Límites de adjuntos:** Los archivos ZIP típicamente son menores a 1MB

---

## Consideraciones de Performance

1. **Colas asíncronas:** El envío no bloquea la respuesta al usuario
2. **Delay de 10 segundos:** Asegura que todos los procesos previos estén completos
3. **Timeout de 120 segundos:** Suficiente tiempo para generar PDF y enviar
4. **Cleanup automático:** Los archivos temporales se eliminan automáticamente

---

## Conclusión

Este sistema proporciona un mecanismo robusto y automatizado para el envío de facturas electrónicas, integrando perfectamente con el flujo de procesamiento de la DIAN y utilizando Mailjet como proveedor confiable de servicios de correo electrónico.
