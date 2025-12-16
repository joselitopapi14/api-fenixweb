# CRUD de Clientes - Implementación Completa

## ✅ Implementado

Se ha creado el controlador completo `ClienteController` con todos los métodos CRUD y las rutas correspondientes.

## 📋 Endpoints Disponibles

### 1. **GET /api/clientes** - Listar clientes
**Descripción**: Obtiene una lista paginada de clientes

**Query Parameters**:
- `empresa_id` (opcional): Filtrar por empresa específica
- `search` (opcional): Buscar por nombres, apellidos, razón social, email, documento o celular
- `tipo_persona_id` (opcional): Filtrar por tipo de persona (1=Natural, 2=Jurídica)
- `tipo_documento_id` (opcional): Filtrar por tipo de documento
- `departamento_id` (opcional): Filtrar por departamento
- `municipio_id` (opcional): Filtrar por municipio
- `sort_by` (opcional): Campo para ordenar (default: created_at)
- `sort_order` (opcional): Orden (asc/desc, default: desc)
- `per_page` (opcional): Resultados por página (default: 15)

**Ejemplo**:
```
GET /api/clientes?empresa_id=1&search=juan&tipo_persona_id=1&page=1
```

---

### 2. **POST /api/clientes** - Crear cliente
**Descripción**: Crea un nuevo cliente

**Body (JSON) - Persona Natural**:
```json
{
    "empresa_id": 1,
    "tipo_documento_id": 3,
    "tipo_persona_id": 1,
    "tipo_responsabilidad_id": 5,
    "cedula_nit": "1234567890",
    "nombres": "JUAN",
    "apellidos": "PÉREZ GÓMEZ",
    "email": "juan.perez@example.com",
    "celular": "3001234567",
    "direccion": "CALLE 10 # 20-30",
    "departamento_id": 1,
    "municipio_id": 1
}
```

**Body (JSON) - Persona Jurídica**:
```json
{
    "empresa_id": 1,
    "tipo_documento_id": 6,
    "tipo_persona_id": 2,
    "tipo_responsabilidad_id": 1,
    "cedula_nit": "900123456",
    "dv": "7",
    "razon_social": "CLIENTE CORPORATIVO S.A.S",
    "email": "contacto@clientecorp.com",
    "celular": "3009876543",
    "telefono_fijo": "6012345678",
    "direccion": "CARRERA 50 # 100-200",
    "representante_legal": "MARÍA RODRÍGUEZ",
    "cedula_representante": "9876543210",
    "email_representante": "maria@clientecorp.com"
}
```

**Campos opcionales**:
- `dv` (dígito de verificación para NIT)
- `telefono_fijo`
- `comuna_id`
- `barrio_id`
- `fecha_nacimiento`
- `representante_legal` (para personas jurídicas)
- `cedula_representante`
- `email_representante`
- `direccion_representante`
- `foto` (archivo de imagen)

---

### 3. **GET /api/clientes/{id}** - Ver detalle de cliente
**Descripción**: Obtiene los detalles completos de un cliente

**Ejemplo**:
```
GET /api/clientes/1
```

**Respuesta incluye**:
- Datos del cliente
- Relaciones: tipo documento, tipo persona, tipo responsabilidad
- Ubicación: departamento, municipio, comuna, barrio
- Empresa asociada
- Redes sociales (si tiene)

---

### 4. **PUT/PATCH /api/clientes/{id}** - Actualizar cliente
**Descripción**: Actualiza los datos de un cliente existente

**Body (JSON)** - Todos los campos son opcionales:
```json
{
    "nombres": "JUAN CARLOS",
    "apellidos": "PÉREZ LÓPEZ",
    "email": "nuevo@email.com",
    "celular": "3009999999",
    "direccion": "NUEVA DIRECCIÓN"
}
```

**Ejemplo**:
```
PUT /api/clientes/1
```

**Nota**: No se puede cambiar la `empresa_id` de un cliente existente.

---

### 5. **DELETE /api/clientes/{id}** - Eliminar cliente
**Descripción**: Elimina un cliente (soft delete)

**Ejemplo**:
```
DELETE /api/clientes/1
```

---

## 🔐 Permisos y Seguridad

### Listar clientes (index):
- **Admin global**: Ve todos los clientes
- **Usuario normal**: Solo ve clientes de las empresas a las que pertenece
- **Con empresa_id**: Verifica que el usuario tenga acceso a esa empresa

### Ver detalle (show):
- **Admin global**: Puede ver cualquier cliente
- **Usuario normal**: Solo puede ver clientes de sus empresas

### Crear (store):
- Requiere pertenecer a la empresa especificada
- Valida que no exista otro cliente con el mismo documento en la empresa

### Actualizar (update):
- Requiere pertenecer a la empresa del cliente
- No permite cambiar la empresa del cliente
- Valida duplicados de documento si se cambia

### Eliminar (destroy):
- Requiere pertenecer a la empresa del cliente

---

## 📁 Manejo de Archivos

### Foto del cliente:
- **Campo**: `foto`
- **Tipo**: Imagen (jpeg, png, jpg, gif)
- **Tamaño máximo**: 2MB
- **Almacenamiento**: `storage/app/public/clientes/fotos/`

**Nota**: Al actualizar la foto, la imagen anterior se elimina automáticamente.

---

## ✨ Características Implementadas

1. ✅ **Validación completa** de todos los campos
2. ✅ **Control de permisos** basado en empresas
3. ✅ **Paginación** en el listado
4. ✅ **Búsqueda** por múltiples campos (nombres, documento, email, etc.)
5. ✅ **Filtros** por tipo de persona, documento, ubicación
6. ✅ **Ordenamiento** personalizable
7. ✅ **Soft deletes** (eliminación lógica)
8. ✅ **Manejo de foto** del cliente
9. ✅ **Validación de duplicados** de documento por empresa
10. ✅ **Logging** de errores
11. ✅ **Try-catch** en todos los métodos
12. ✅ **Relaciones eager loading** para optimizar consultas
13. ✅ **Validación condicional** (nombres/apellidos para natural, razón social para jurídica)
14. ✅ **Mutators automáticos** (mayúsculas, limpieza de números)

---

## 🧪 Ejemplos de Uso con cURL

### Crear cliente persona natural:
```bash
curl -X POST http://localhost:8000/api/clientes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "empresa_id": 1,
    "tipo_documento_id": 3,
    "tipo_persona_id": 1,
    "tipo_responsabilidad_id": 5,
    "cedula_nit": "1234567890",
    "nombres": "JUAN",
    "apellidos": "PÉREZ",
    "email": "juan@example.com",
    "celular": "3001234567",
    "direccion": "CALLE 1 # 2-3"
  }'
```

### Crear cliente persona jurídica:
```bash
curl -X POST http://localhost:8000/api/clientes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "empresa_id": 1,
    "tipo_documento_id": 6,
    "tipo_persona_id": 2,
    "tipo_responsabilidad_id": 1,
    "cedula_nit": "900123456",
    "dv": "7",
    "razon_social": "MI EMPRESA S.A.S",
    "email": "contacto@miempresa.com",
    "celular": "3001234567",
    "direccion": "CARRERA 1 # 2-3",
    "representante_legal": "JUAN PÉREZ",
    "cedula_representante": "1234567890"
  }'
```

### Listar clientes con filtros:
```bash
curl -X GET "http://localhost:8000/api/clientes?empresa_id=1&search=juan&tipo_persona_id=1&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Actualizar cliente:
```bash
curl -X PUT http://localhost:8000/api/clientes/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "nuevo@email.com",
    "celular": "3009999999"
  }'
```

---

## 📝 Validaciones Especiales

### Persona Natural (tipo_persona_id = 1):
- **Requiere**: `nombres` y `apellidos`
- **Opcional**: `razon_social` (se ignora)

### Persona Jurídica (tipo_persona_id = 2):
- **Requiere**: `razon_social`
- **Opcional**: `nombres` y `apellidos` (se ignoran)
- **Recomendado**: Datos del representante legal

### Documento único:
- El `cedula_nit` debe ser único **por empresa**
- Dos empresas diferentes pueden tener clientes con el mismo documento

---

## 🔄 Mutators Automáticos del Modelo

El modelo `Cliente` aplica automáticamente:
- **MAYÚSCULAS**: nombres, apellidos, razón social, dirección
- **Solo números**: cedula_nit, celular, telefono_fijo

---

## 📊 Accessors Disponibles

- `nombre_completo`: Retorna nombres + apellidos o razón social
- `documento_completo`: Retorna cedula_nit + dv (si aplica)
- `foto_url`: URL completa de la foto o avatar generado
- `ubicacion_completa`: Dirección completa con barrio, comuna, municipio, departamento
- `telefono`: Retorna telefono_fijo o celular (el que esté disponible)

---

## 🎯 Próximos Pasos

Ahora que el CRUD de Clientes está completo:
1. ✅ Ejecutar los seeders para crear datos de prueba
2. ✅ Probar la creación de facturas con empresas, clientes y productos
3. 📝 Actualizar la colección de Postman con los nuevos endpoints
