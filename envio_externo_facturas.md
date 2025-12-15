# Envío Externo de Facturas a la DIAN

## Descripción

Este documento describe la funcionalidad implementada para el envío de facturas a la DIAN de forma externa, sin dependencia de la base de datos. La implementación usa exactamente los mismos templates y lógica que el sistema interno.

## Endpoints Disponibles

### 1. Envío de Factura Externa
`POST /api/enviar-factura-external`

Este endpoint procesa una factura completa y la envía a la DIAN usando la misma lógica del modelo `Factura`.

### 2. Verificación de Estado de ZIP (Externa)
`POST /api/verificar-estado-zip-external`

Permite verificar el estado de un ZIP enviado a la DIAN usando solo los datos del certificado.

## Estructura de Datos Requerida

### Ejemplo de JSON para Envío de Factura

```json
{
  "factura": {
    "numero_factura": "FACT001",
    "subtotal": 100000,
    "valor_impuestos": 19000,
    "total": 119000,
    "due_date": "2024-12-31",
    "observaciones": "Factura de prueba"
  },
  "empresa": {
    "name": "Mi Empresa SAS",
    "nit": "900123456",
    "dv": "1",
    "software_id": "SOFTWARE_ID_DIAN",
    "software_pin": "SOFTWARE_PIN_DIAN",
    "certificate_base64": "BASE64_DEL_CERTIFICADO_P12",
    "certificate_password": "PASSWORD_CERTIFICADO",
    "tipo_ambiente": 2,
    "test_set_id": "TEST_SET_ID_HABILITACION",
    "address": "Calle 123 #45-67",
    "phone": "3001234567",
    "email": "empresa@ejemplo.com",
    "matricula_mercantil": "MATRICULA123",
    "municipio": {
      "code": "11001",
      "name": "Bogotá D.C."
    },
    "departamento": {
      "code": "11",
      "name": "Bogotá D.C."
    },
    "tipoDocumento": {
      "code": "31"
    },
    "tipoResponsabilidad": {
      "code": "O-13"
    }
  },
  "cliente": {
    "name": "Cliente Ejemplo",
    "document": "12345678",
    "dv": "9",
    "address": "Carrera 98 #76-54",
    "phone": "3009876543",
    "email": "cliente@ejemplo.com",
    "municipio": {
      "code": "11001",
      "name": "Bogotá D.C."
    },
    "departamento": {
      "code": "11",
      "name": "Bogotá D.C."
    },
    "tipoDocumento": {
      "code": "13"
    },
    "tipoPersona": {
      "code": "1"
    },
    "tipoResponsabilidad": {
      "code": "R-99-PN"
    }
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
    "clave_tecnica": "fc8eac422eba16e42ffc1108766c64e5b2542c3"
  },
  "productos": [
    {
      "cantidad": 1,
      "precio_unitario": 100000,
      "descuento": 0,
      "recargo": 0,
      "producto": {
        "id": "PROD001",
        "nombre": "Producto de Ejemplo",
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

### Ejemplo de JSON para Verificación de Estado de ZIP

```json
{
  "zip_key": "CLAVE_ZIP_DEVUELTA_POR_DIAN",
  "empresa": {
    "certificate_base64": "BASE64_DEL_CERTIFICADO_P12",
    "certificate_password": "PASSWORD_CERTIFICADO"
  }
}
```

## Respuestas de la API

### Respuesta Exitosa del Envío de Factura

```json
{
  "success": true,
  "data": {
    "is_valid": true,
    "xml_url": "https://tu-dominio.com/facturas/FACT001_12345.xml",
    "cufe": "abc123def456...sha384_hash",
    "numero_factura": "FACT001",
    "estado": "enviado a dian",
    "zip_key": "CLAVE_ZIP_DIAN",
    "errors": [],
    "raw_response": "..."
  }
}
```

### Respuesta con Errores

```json
{
  "success": true,
  "data": {
    "is_valid": false,
    "xml_url": "https://tu-dominio.com/facturas/FACT001_12345.xml",
    "cufe": "abc123def456...sha384_hash",
    "numero_factura": "FACT001",
    "estado": "error dian",
    "zip_key": null,
    "errors": [
      "Regla: FAD04, Rechazo: Error en la validación"
    ],
    "raw_response": "..."
  }
}
```

## Características Implementadas

### ✅ Funcionalidades Completas

1. **Generación XML idéntica al sistema interno**
   - Usa el mismo template `invoice.blade.php`
   - Cálculo de CUFE exactamente igual al modelo `Factura`
   - Soporte para productos con impuestos, descuentos y recargos

2. **Firma digital**
   - Usa certificado en base64 (no requiere archivo en servidor)
   - Misma clase `SignInvoice2` que el sistema interno

3. **Envío a DIAN**
   - Soporte para ambiente de habilitación (`SendTestSetAsync`)
   - Soporte para ambiente de producción (`SendBillSync`)
   - Manejo de errores idéntico al sistema interno

4. **Generación de attached_document**
   - XML final con respuesta de DIAN incluida
   - Solo cuando la factura es válida

5. **Verificación de estado**
   - Endpoint para verificar estado de ZIP
   - Útil para ambiente de habilitación

### 🔧 Validaciones Implementadas

- Estructura completa de empresa, cliente, productos
- Validación de certificado en base64
- Validación de impuestos y códigos DIAN
- Soporte para retenciones (opcional)

### 📝 Logging

- Log completo del proceso de envío
- Debug del cálculo de CUFE
- Errores detallados para troubleshooting

## Diferencias con el Sistema Interno

| Aspecto | Sistema Interno | Sistema Externo |
|---------|----------------|-----------------|
| Datos | Base de datos | JSON payload |
| Certificado | Archivo en servidor | Base64 en request |
| Persistencia | Guarda en DB | Solo archivos XML |
| Relaciones | Eloquent relationships | Objetos simulados |
| Validación | Model validation | Request validation |

## Uso Recomendado

1. **Ambiente de Habilitación**: Usar `tipo_ambiente: 3` y proporcionar `test_set_id`
2. **Ambiente de Producción**: Usar `tipo_ambiente: 1` o `tipo_ambiente: 2`
3. **Verificación de Estado**: Usar el `zip_key` devuelto en ambiente de habilitación

## Consideraciones Importantes

- El certificado debe estar en formato base64 válido
- Los códigos de impuestos deben seguir estándares DIAN (01=IVA, 04=Consumo, 03=ICA)
- La estructura de municipios y departamentos debe ser válida según DIAN
- El `test_set_id` es obligatorio para ambiente de habilitación

## Ejemplo de Integración

```php
// Ejemplo de uso desde otra aplicación
$facturaData = [
    // ... estructura completa como se muestra arriba
];

$response = Http::post('https://tu-api.com/api/enviar-factura-external', $facturaData);

if ($response->successful()) {
    $result = $response->json();
    if ($result['data']['is_valid']) {
        echo "Factura enviada exitosamente: " . $result['data']['xml_url'];
    } else {
        echo "Errores DIAN: " . implode(', ', $result['data']['errors']);
    }
}
```

Esta implementación garantiza que el envío externo funciona exactamente igual que el sistema interno, usando los mismos templates, validaciones y lógica de negocio.
