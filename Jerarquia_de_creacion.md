
# Nivel 1: Entidades Base (sin dependencias)

Estas deben crearse primero:


## Países (paises)
Tipos de Documento (tipo_documentos)
Tipos de Persona (tipo_personas)
Tipos de Responsabilidad (tipo_responsabilidades)
Redes Sociales (redes_sociales)
Tipos de Medida (tipo_medidas)
Impuestos (impuestos)
Medios de Pago (medio_pagos)
Tipos de Pago (tipo_pagos)
Tipos de Factura (tipo_facturas)
Tipos de Retención (tipo_retenciones)
Usuarios (users)

## Nivel 2: Ubicaciones Geográficas (dependencia jerárquica)
Departamentos (depende de: País)
Municipios (depende de: Departamento)
Comunas (depende de: Municipio)
Barrios (depende de: Comuna)

## Nivel 3: Empresas
Empresa (depende de: Departamento, Municipio, Comuna, Barrio, Tipo Persona, Tipo Responsabilidad, Tipo Documento)

## Nivel 4: Entidades de Empresa
Sedes (depende de: Empresa + ubicaciones)
Tipo Interés (depende de: Empresa - opcional)
Tipo Movimiento (depende de: Empresa)
Tipo Producto (depende de: Empresa - opcional)
Tipo Oro (depende de: Empresa - opcional)
Conceptos (depende de: Empresa - opcional)
Resoluciones de Facturación (depende de: Empresa)

## Nivel 5: Productos y Clientes
Productos (depende de: Empresa, Tipo Producto, Tipo Oro, Tipo Medida)
Clientes (depende de: Empresa + ubicaciones + tipos)

## Nivel 6: Transacciones
Boletas de Empeño (depende de: Cliente, Empresa, Sede, Tipo Interés, Tipo Movimiento)
Facturas (depende de: Cliente, Empresa, Usuario, Tipos varios, Medio Pago, Tipo Pago)
Movimientos de Inventario (depende de: Empresa, Tipo Movimiento, Usuario)

## Nivel 7: Detalles de Transacciones
Cuotas (depende de: Boleta Empeño, Usuario)
Productos de Boleta (depende de: Boleta Empeño, Producto)
Productos de Factura (depende de: Factura, Producto)
Movimiento Inventario Productos (depende de: Movimiento Inventario, Producto)

## PAYLOADS DE CREACION DE ENTIDADES

### User
{
  "name": "string (max:255, requerido)",
  "email": "string email (max:255, único, requerido)",
  "password": "string (min:8, confirmado, requerido)",
  "password_confirmation": "string",
  "roles": ["array de role IDs (opcional)"]
}

### empresa

{
  "nit": "string (max:20, único, requerido)",
  "dv":  "string (max:1, requerido)",
  "razon_social": "string (max:255, requerido)",
  "direccion": "text (requerido)",
  "departamento_id": "integer|exists:departamentos,id (nullable)",
  "municipio_id": "integer|exists:municipios,id (nullable)",
  "comuna_id": "integer|exists: comunas,id (nullable)",
  "barrio_id": "integer|exists:barrios,id (nullable)",
  "tipo_persona_id": "integer|exists:tipo_personas,id (requerido)",
  "tipo_responsabilidad_id": "integer|exists:tipo_responsabilidades,id (requerido)",
  "tipo_documento_id":  "integer|exists:tipo_documentos,id (requerido)",
  "telefono_fijo": "string (max: 20, nullable)",
  "celular": "string (max:20, nullable)",
  "email": "email (max:255, nullable)",
  "pagina_web": "url (max:255, nullable)",
  "software_id": "string (max:255, nullable)",
  "software_pin": "string (max:255, nullable)",
  "certificate":  "file (max:5120 KB, nullable)",
  "certificate_password": "string (max:255, nullable)",
  "logo": "image|mimes:jpeg,png,jpg,gif (max:2048 KB, nullable)",
  "representante_legal": "string (max:255, requerido)",
  "cedula_representante": "string (max:20, requerido)",
  "email_representante": "email (max:255, nullable)",
  "direccion_representante": "string (requerido)",
  "redes_sociales": [
    {
      "red_social_id": "integer|exists:redes_sociales,id",
      "usuario":  "string (max:255)"
    }
  ]
}

### sede

{
  "empresa_id": "integer|exists: empresas,id (requerido)",
  "nombre": "string (requerido)",
  "direccion": "text (requerido)",
  "telefono": "string (max:20, nullable)",
  "email": "email (nullable)",
  "departamento_id": "integer|exists:departamentos,id (nullable)",
  "municipio_id":  "integer|exists:municipios,id (nullable)",
  "comuna_id": "integer|exists: comunas,id (nullable)",
  "barrio_id": "integer|exists:barrios,id (nullable)",
  "es_principal": "boolean (default:false)",
  "activa": "boolean (default:true)",
  "observaciones": "text (nullable)"
}

### Cliente

{
  "empresa_id": "integer|exists:empresas,id (requerido)",
  "tipo_documento_id": "integer|exists: tipo_documentos,id (requerido)",
  "cedula_nit": "string (max:20, requerido, único por empresa)",
  "nombres": "string (max:100, requerido)",
  "apellidos": "string (max: 100, requerido)",
  "fecha_nacimiento": "date (nullable)",
  "direccion": "string (nullable)",
  "telefono": "string (max:20, nullable)",
  "celular": "string (max:20, nullable)",
  "email": "email (nullable)",
  "departamento_id": "integer|exists:departamentos,id (nullable)",
  "municipio_id":  "integer|exists:municipios,id (nullable)",
  "comuna_id": "integer|exists: comunas,id (nullable)",
  "barrio_id": "integer|exists:barrios,id (nullable)",
  "tipo_persona_id": "integer|exists:tipo_personas,id (nullable)",
  "tipo_responsabilidad_id": "integer|exists:tipo_responsabilidades,id (nullable)",
  "foto": "image (max:2048 KB, nullable)",
  "redes_sociales": [
    {
      "red_social_id": "integer|exists:redes_sociales,id",
      "usuario": "string (max: 255)"
    }
  ]
}

### Producto

{
  "nombre": "string (max:100, requerido)",
  "descripcion": "text (nullable)",
  "tipo_producto_id": "integer|exists:tipo_productos,id (requerido)",
  "tipo_oro_id": "integer|exists: tipo_oros,id (requerido si tipo_producto_id=1)",
  "empresa_id": "integer|exists: empresas,id (requerido para no-admins)",
  "codigo_barras": "string (nullable)",
  "precio_venta": "numeric (min:0, max:99999999.99, nullable)",
  "precio_compra": "numeric (min:0, max:99999999.99, nullable)",
  "peso": "numeric (min:0, nullable)",
  "tipo_medida_id": "integer|exists:tipo_medidas,id (nullable)",
  "imagen": "file (max:2048 KB, nullable)",
  "impuestos": ["array de impuesto IDs (nullable)"]
}

### Tipo interes

{
  "nombre": "string (max:100, requerido)",
  "porcentaje": "decimal (min:0, max:100, requerido)",
  "descripcion": "text (nullable)",
  "activo": "boolean (default:true)",
  "empresa_id": "integer|exists:empresas,id (nullable para admin global)"
}

### Boleta empeno

{
  "empresa_id": "integer|exists: empresas,id (requerido)",
  "sede_id": "integer|exists: sedes,id (nullable)",
  "cliente_id": "integer|exists: clientes,id (requerido)",
  "tipo_interes_id": "integer|exists:tipo_interes,id (requerido)",
  "monto_prestamo": "numeric (min:0, requerido)",
  "fecha_vencimiento": "date (after:today, requerido)",
  "observaciones": "string (max:1000, nullable)",
  "productos": [
    {
      "producto_id": "integer|exists:productos,id (requerido)",
      "cantidad": "numeric (min:0.01, requerido)",
      "descripcion_adicional": "string (max:500, nullable)",
      "foto_producto": "image|mimes:jpeg,png,jpg,gif (max:2048 KB, nullable)"
    }
  ]
}

### Cuota

{
  "bolleta_empeno_id": "integer|exists:bolleta_empenos,id (requerido)",
  "monto_pagado": "numeric (min:0, requerido)",
  "fecha_abono": "date (requerido)",
  "observaciones": "text (nullable)"
}

### Factura (API)

{
  "tipo_movimiento_id": "integer|exists:tipo_movimientos,id (requerido)",
  "tipo_factura_id": "integer|exists:tipo_facturas,id (requerido)",
  "cliente_id": "integer|exists: clientes,id (requerido)",
  "empresa_id": "integer|exists:empresas,id (requerido)",
  "medio_pago_id": "integer|exists:medio_pagos,id (requerido)",
  "tipo_pagos_id": "integer|exists: tipo_pagos,id (requerido)",
  "productos": [
    {
      "id": "integer|exists:productos,id (requerido)",
      "cantidad": "numeric (min: 0.01, requerido)",
      "descuento": "numeric (min: 0, nullable)",
      "recargo": "numeric (min:0, nullable)"
    }
  ],
  "retenciones": [
    {
      "retencion_id": "integer|exists:tipo_retenciones,id (requerido)",
      "concepto_retencion_id": "integer|exists:concepto_retenciones,id (requerido)",
      "porcentaje_valor": "numeric (min:0, max:100, requerido)"
    }
  ],
  "valor_recibido": "numeric (min:0, nullable)",
  "observaciones": "string (nullable)"
}

### Red Social

{
  "nombre": "string (max:100, único, requerido)"
}

### Tipo Oro

{
  "nombre": "string (max: 100, requerido)",
  "valor_de_mercado": "numeric (min:0, requerido)",
  "observacion": "string (max:1000, nullable)",
  "empresa_id": "integer|exists: empresas,id (nullable para admin global, requerido para usuarios normales)"
}

### Dependencias: Empresa (opcional)

### Tipo movimiento

{
  "nombre": "string (max:255, requerido, único por empresa)",
  "descripcion": "string (max:1000, nullable)",
  "empresa_id": "integer|exists: empresas,id (requerido)",
  "es_suma": "boolean (requerido)",
  "activo": "boolean (nullable, default:true)"
}

## Dependencias: Empresa Nota: es_suma define si el movimiento suma o resta en el inventario

### Concepto

{
  "nombre": "string (max:100, requerido)",
  "descripcion": "string (max: 500, nullable)",
  "activo": "boolean (nullable, default:true)",
  "empresa_id": "integer|exists:empresas,id (nullable para admin global)"
}

### Dependencias: Empresa (opcional)

### MOvimiento de inventario

{
  "empresa_id": "integer|exists: empresas,id (requerido)",
  "sede_id": "integer|exists:sedes,id (nullable)",
  "tipo_movimiento_id": "integer|exists:tipo_movimientos,id (requerido)",
  "concepto_id": "integer|exists:conceptos,id (requerido)",
  "fecha_movimiento": "date (requerido)",
  "observaciones": "string (max:1000, nullable)",
  "productos": [
    {
      "producto_id": "integer|exists:productos,id (requerido)",
      "cantidad": "numeric (min:0.01, requerido)",
      "descripcion_adicional": "string (max:500, nullable)"
    }
  ]
}

### Dependencias: Empresa, Sede, Tipo Movimiento, Concepto, Productos

## Resolucion de facturacion

{
  "empresa_id": "integer|exists:empresas,id (requerido)",
  "numero_resolucion": "string (max: 50, requerido, único)",
  "prefijo": "string (max:10, nullable)",
  "desde": "integer (min:1, requerido)",
  "hasta": "integer (min:1, gt:desde, requerido)",
  "fecha_inicio": "date (requerido)",
  "fecha_fin": "date (after:fecha_inicio, requerido)",
  "clave_tecnica": "string (nullable)",
  "activa": "boolean (default:true)"
}

### Dependencias: Empresa

## Impuesto

{
  "name": "string (max:255, único, requerido)",
  "code": "string (max:10, único, nullable)"
}

### Dependencias: Ninguna

## Porcentaje de impuesto

{
  "impuesto_id": "integer|exists:impuestos,id (implícito en la ruta)",
  "percentage": "numeric (min:0, max:100, requerido)"
}

### Dependencias: Impuesto

## Role (Rol)

{
  "name": "string (max:255, único, requerido)",
  "permissions": ["array de permission IDs (opcional)"]
}

### Dependencias: Ninguna Nota: Usa Spatie Permission Package

## Permission (permiso)

{
  "name": "string (max:255, único, requerido)"
}

### Dependencias: Ninguna Nota: Usa Spatie Permission Package

## Tipo medida

{
  "nombre": "string (max:100, requerido)",
  "abreviatura": "string (max: 10, requerido)",
  "descripcion": "string (nullable)"
}

### Dependencias: Ninguna


## Nota importante

### Usuarios no-admin: No pueden crear entidades globales (empresa_id es obligatorio)

### Administradores globales: Pueden crear entidades globales (empresa_id nullable)

### Transacciones DB: La mayoría de operaciones usan DB::beginTransaction() y DB::commit()

### Soft Deletes: Muchos modelos usan SoftDeletes trait

## Validaciones Especiales

### Tipo Movimiento: El campo nombre debe ser único dentro de la misma empresa (unique compuesto)

### Resolución de Facturación:

- El campo hasta debe ser mayor que desde
- La fecha_fin debe ser posterior a fecha_inicio

### Movimiento de Inventario:

- Requiere al menos un producto
- La cantidad mínima por producto es 0.01

### Roles y Permisos:

- Los permisos se sincronizan con syncPermissions()
- Los roles se asignan a usuarios con assignRole()


