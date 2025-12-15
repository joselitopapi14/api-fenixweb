# Integración de Facturación con DIAN

## Descripción

Se ha implementado la integración del módulo de facturación con la API externa de la DIAN, siguiendo el mismo patrón y estructura documentada en `envio_externo_facturas.md`.

## Cambios Realizados

### FacturaController.php

#### 1. Imports Agregados
```php
use App\Models\ResolucionFacturacion;
use App\Models\FacturaHasProduct;
use App\Models\FacturaHasRetencione;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;
```

#### 2. Método `store()` Refactorizado

El método ahora:
- ✅ Decodifica los JSON de productos y retenciones del request
- ✅ Valida que exista al menos un producto
- ✅ Verifica si la resolución requiere envío a DIAN (`envia_dian = true`)
- ✅ Si requiere DIAN:
  - Construye el payload según la estructura de `envio_externo_facturas.md`
  - Envía la factura a la API externa
  - Valida la respuesta de la DIAN
  - Si es rechazada, hace rollback y muestra los errores
  - Si es aprobada, guarda la factura con CUFE, XML URL y estado
- ✅ Si NO requiere DIAN:
  - Crea la factura normalmente sin integración
- ✅ Guarda los productos con sus cantidades, descuentos y recargos
- ✅ Guarda las retenciones si existen
- ✅ Actualiza el consecutivo de la resolución
- ✅ Usa transacciones DB para garantizar integridad

#### 3. Nuevo Método `enviarFacturaDian()`

Método privado que maneja toda la lógica de integración con la DIAN:

**Funcionalidades:**
- ✅ Carga todas las relaciones necesarias (empresa, cliente, tipo factura, medios de pago, etc.)
- ✅ Carga productos con sus impuestos
- ✅ Convierte el certificado a base64
- ✅ Calcula el siguiente consecutivo y genera el número de factura
- ✅ Construye el payload completo según la estructura documentada
- ✅ Prepara productos con sus impuestos (o agrega IVA 0% por defecto)
- ✅ Incluye retenciones si existen
- ✅ Determina el ambiente (producción o habilitación)
- ✅ Genera headers de autenticación con token MD5
- ✅ Envía la petición HTTP a `/api/enviar-factura-external`
- ✅ Procesa la respuesta y extrae:
  - `is_valid`: Si la DIAN validó la factura
  - `xml_url`: URL del XML generado
  - `cufe`: Código único de factura electrónica
  - `estado`: Estado de la factura
  - `errors`: Errores de validación DIAN
  - `zip_key`: Clave del ZIP (para ambiente habilitación)

## Estructura del Payload Enviado

```json
{
  "factura": {
    "numero_factura": "FACT0001",
    "subtotal": 361344.54,
    "valor_impuestos": 68655.46,
    "total": 430000,
    "due_date": "2025-11-02",
    "observaciones": "..."
  },
  "empresa": {
    "name": "Nombre Empresa",
    "nit": "900123456",
    "dv": "1",
    "software_id": "...",
    "software_pin": "...",
    "certificate_base64": "...",
    "certificate_password": "...",
    "tipo_ambiente": 2,
    "test_set_id": "...",
    "address": "...",
    "phone": "...",
    "email": "...",
    "municipio": {...},
    "departamento": {...},
    "tipoDocumento": {...},
    "tipoResponsabilidad": {...}
  },
  "cliente": {
    "name": "...",
    "document": "...",
    "dv": "...",
    "address": "...",
    "phone": "...",
    "email": "...",
    "municipio": {...},
    "departamento": {...},
    "tipoDocumento": {...},
    "tipoPersona": {...},
    "tipoResponsabilidad": {...}
  },
  "tipo_factura": {
    "code": "01",
    "name": "Factura de Venta"
  },
  "tipo_movimiento": {
    "resolucion": "18760000001",
    "fecha_inicial": "2024-01-01",
    "fecha_final": "2024-12-31",
    "prefijo": "FACT",
    "consecutivo_inicial": "1",
    "consecutivo_final": "5000",
    "clave_tecnica": "..."
  },
  "productos": [
    {
      "cantidad": 1,
      "precio_unitario": 100000,
      "descuento": 20000,
      "recargo": 0,
      "producto": {
        "id": 29,
        "nombre": "Producto X",
        "codigo": "COD001",
        "unidad_medida": "UND",
        "impuestos": [
          {
            "porcentaje": 19,
            "impuesto": {
              "code": "01",
              "name": "IVA",
              "percentage": 19
            }
          }
        ]
      }
    }
  ],
  "medio_pago": {
    "code": "10",
    "name": "Efectivo"
  },
  "tipoPago": {
    "code": "1",
    "name": "Contado"
  },
  "retenciones": []
}
```

## Datos del Request Esperados

```php
[
  "_token" => "...",
  "empresa_id" => "1",
  "resolucion_id" => "6",
  "cliente_id" => "5",
  "tipo_factura_id" => "1",
  "medio_pago_id" => "1",
  "tipo_pago_id" => "1",
  "plazo_dias" => "30",
  "due_date" => "2025-11-02",
  "subtotal" => "361344.54",
  "valor_impuestos" => "68655.46",
  "total" => "430000",
  "valor_recibido" => null,
  "cambio" => "0",
  "observaciones" => null,
  "productos" => '[{"id":29,"cantidad":1,"descuento":20000,"recargo":0}]',
  "retenciones" => '[]'
]
```

## Respuesta de la API Externa

### Factura Válida
```json
{
  "success": true,
  "data": {
    "is_valid": true,
    "xml_url": "https://dominio.com/facturas/FACT0001_12345.xml",
    "cufe": "abc123def456...sha384_hash",
    "numero_factura": "FACT0001",
    "estado": "enviado a dian",
    "zip_key": "CLAVE_ZIP_DIAN",
    "errors": []
  }
}
```

### Factura Rechazada
```json
{
  "success": true,
  "data": {
    "is_valid": false,
    "xml_url": "https://dominio.com/facturas/FACT0001_12345.xml",
    "cufe": "abc123def456...sha384_hash",
    "numero_factura": "FACT0001",
    "estado": "error dian",
    "zip_key": null,
    "errors": [
      "Regla: FAD04, Rechazo: Error en la validación",
      "..."
    ]
  }
}
```

## Flujo de Operación

1. Usuario envía el formulario de factura con productos
2. Sistema valida datos del request
3. Sistema verifica si la resolución tiene `envia_dian = true`
4. **Si envía a DIAN:**
   - Carga todas las relaciones necesarias
   - Construye el payload completo
   - Obtiene certificado en base64
   - Genera headers de autenticación
   - Envía a `/api/enviar-factura-external`
   - Valida respuesta de la DIAN
   - Si es válida: guarda factura con CUFE y XML
   - Si es inválida: hace rollback y muestra errores
5. **Si NO envía a DIAN:**
   - Crea factura normalmente
   - Guarda productos y retenciones
   - Actualiza consecutivo

## Variables de Entorno Necesarias

```env
# URL de la API externa
API_URL=https://tu-api-externa.com

# Clave secreta para autenticación
CLAVE_SECRETA=tu_clave_secreta_aqui

# Ambiente DIAN
DIAN_ENV=production  # o "habilitacion"

# Test Set ID (solo para habilitación)
DIAN_TEST_SET_ID=tu_test_set_id
```

## Campos Agregados a la Tabla `facturas`

Asegúrate de que la tabla `facturas` tenga estos campos:
- `cufe` (string, nullable) - Código único de factura electrónica
- `xml_url` (string, nullable) - URL del XML generado
- `estado` (string) - Estado de la factura (pendiente, enviado a dian, error dian)

## Validaciones Implementadas

- ✅ Verificación de que el certificado existe y es válido
- ✅ Validación de estructura de productos
- ✅ Validación de respuesta de API externa
- ✅ Manejo de errores con rollback de transacción
- ✅ Logging completo del proceso
- ✅ Validación de consecutivos disponibles

## Manejo de Errores

- Si la API externa falla: Se captura la excepción y se devuelve un error estructurado
- Si la DIAN rechaza la factura: Se hace rollback y se muestran los errores específicos
- Si falta configuración: Se informa al usuario claramente
- Todos los errores se registran en los logs para debugging

## Logs Generados

```
[INFO] Creando factura
[INFO] Preparando integración con DIAN para factura
[INFO] Enviando factura a API externa DIAN
[INFO] Respuesta recibida de API externa DIAN
[INFO] Factura creada exitosamente con integración DIAN
```

O en caso de error:
```
[ERROR] Error al crear factura
[ERROR] Error al enviar factura a DIAN
[ERROR] Factura rechazada por DIAN
```

## Compatibilidad

Esta implementación es 100% compatible con:
- ✅ La estructura documentada en `envio_externo_facturas.md`
- ✅ El sistema de documentos equivalentes existente
- ✅ Los templates XML y lógica de firma del sistema
- ✅ Ambiente de habilitación y producción de la DIAN

## Próximos Pasos Recomendados

1. **Testing**: Probar con facturas reales en ambiente de habilitación
2. **Validación de Certificados**: Verificar que los certificados estén correctamente configurados
3. **Manejo de Respuesta Asíncrona**: Implementar verificación de estado de ZIP para habilitación
4. **Notificaciones**: Agregar notificaciones al usuario sobre el estado del envío
5. **Reportes**: Crear reporte de facturas enviadas vs rechazadas por la DIAN
