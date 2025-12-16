# CRUD de Empresas - Implementación Completa

## ✅ Implementado

Se ha creado el controlador completo `EmpresaController` con todos los métodos CRUD y las rutas correspondientes.

## 📋 Endpoints Disponibles

### 1. **GET /api/empresas** - Listar empresas
**Descripción**: Obtiene una lista paginada de empresas

**Query Parameters**:
- `search` (opcional): Buscar por razón social, NIT o email
- `activa` (opcional): Filtrar por estado (true/false)
- `sort_by` (opcional): Campo para ordenar (default: razon_social)
- `sort_order` (opcional): Orden (asc/desc, default: asc)
- `per_page` (opcional): Resultados por página (default: 15)

**Ejemplo**:
```
GET /api/empresas?search=prueba&activa=true&page=1
```

---

### 2. **POST /api/empresas** - Crear empresa
**Descripción**: Crea una nueva empresa

**Body (JSON)**:
```json
{
    "nit": "900123456",
    "dv": "7",
    "razon_social": "EMPRESA DE PRUEBA S.A.S",
    "direccion": "CALLE 123 # 45-67",
    "email": "contacto@empresaprueba.com",
    "celular": "3001234567",
    "tipo_persona_id": 2,
    "tipo_responsabilidad_id": 1,
    "tipo_documento_id": 6,
    "departamento_id": 1,
    "municipio_id": 1,
    "activa": true
}
```

**Campos opcionales**:
- `telefono_fijo`
- `pagina_web`
- `comuna_id`
- `barrio_id`
- `representante_legal`
- `cedula_representante`
- `email_representante`
- `direccion_representante`
- `software_id`
- `software_pin`
- `certificate_password`
- `logo` (archivo de imagen)
- `certificate_path` (archivo .p12 o .pfx)

---

### 3. **GET /api/empresas/{id}** - Ver detalle de empresa
**Descripción**: Obtiene los detalles completos de una empresa

**Ejemplo**:
```
GET /api/empresas/1
```

**Respuesta incluye**:
- Datos de la empresa
- Relaciones: departamento, municipio, comuna, barrio
- Tipos: persona, responsabilidad, documento
- Usuarios asociados y administradores

---

### 4. **PUT/PATCH /api/empresas/{id}** - Actualizar empresa
**Descripción**: Actualiza los datos de una empresa existente

**Body (JSON)** - Todos los campos son opcionales:
```json
{
    "razon_social": "EMPRESA ACTUALIZADA S.A.S",
    "direccion": "NUEVA DIRECCIÓN",
    "email": "nuevo@email.com",
    "activa": true
}
```

**Ejemplo**:
```
PUT /api/empresas/1
```

---

### 5. **DELETE /api/empresas/{id}** - Eliminar empresa
**Descripción**: Elimina una empresa (soft delete)

**Permisos**: Solo administradores globales

**Ejemplo**:
```
DELETE /api/empresas/1
```

---

## 🔐 Permisos y Seguridad

### Listar empresas (index):
- **Admin global**: Ve todas las empresas
- **Usuario normal**: Solo ve las empresas a las que pertenece

### Ver detalle (show):
- **Admin global**: Puede ver cualquier empresa
- **Usuario normal**: Solo puede ver empresas a las que pertenece

### Crear (store):
- Cualquier usuario autenticado puede crear empresas
- El usuario que crea la empresa se asocia automáticamente como administrador (si no es admin global)

### Actualizar (update):
- **Admin global**: Puede actualizar cualquier empresa
- **Administrador de empresa**: Solo puede actualizar las empresas que administra

### Eliminar (destroy):
- **Solo admin global** puede eliminar empresas

---

## 📁 Manejo de Archivos

### Logo de la empresa:
- **Campo**: `logo`
- **Tipo**: Imagen (jpeg, png, jpg, gif)
- **Tamaño máximo**: 2MB
- **Almacenamiento**: `storage/app/public/empresas/logos/`

### Certificado digital:
- **Campo**: `certificate_path`
- **Tipo**: Archivo .p12 o .pfx
- **Tamaño máximo**: 5MB
- **Almacenamiento**: `storage/app/empresas/certificates/` (privado)

**Nota**: Al actualizar logo o certificado, el archivo anterior se elimina automáticamente.

---

## ✨ Características Implementadas

1. ✅ **Validación completa** de todos los campos
2. ✅ **Control de permisos** basado en roles
3. ✅ **Paginación** en el listado
4. ✅ **Búsqueda** por múltiples campos
5. ✅ **Filtros** por estado activo/inactivo
6. ✅ **Ordenamiento** personalizable
7. ✅ **Soft deletes** (eliminación lógica)
8. ✅ **Manejo de archivos** (logo y certificado)
9. ✅ **Logging** de errores
10. ✅ **Try-catch** en todos los métodos
11. ✅ **Relaciones eager loading** para optimizar consultas
12. ✅ **Asociación automática** de usuario creador

---

## 🧪 Ejemplos de Uso con cURL

### Crear empresa:
```bash
curl -X POST http://localhost:8000/api/empresas \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nit": "900123456",
    "dv": "7",
    "razon_social": "MI EMPRESA S.A.S",
    "direccion": "CALLE 1 # 2-3",
    "email": "contacto@miempresa.com",
    "celular": "3001234567",
    "tipo_persona_id": 2,
    "tipo_responsabilidad_id": 1,
    "tipo_documento_id": 6
  }'
```

### Listar empresas:
```bash
curl -X GET "http://localhost:8000/api/empresas?search=empresa&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Actualizar empresa:
```bash
curl -X PUT http://localhost:8000/api/empresas/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "razon_social": "EMPRESA ACTUALIZADA",
    "activa": true
  }'
```

---

## 📝 Notas Importantes

1. **NIT único**: El NIT debe ser único en el sistema
2. **Relaciones opcionales**: Las ubicaciones (departamento, municipio, etc.) son opcionales
3. **Certificado sensible**: El `certificate_password` nunca se retorna en las respuestas
4. **Multi-tenancy**: El sistema soporta múltiples empresas por usuario
5. **Activación**: Las empresas pueden estar activas o inactivas

---

## 🔄 Próximos Pasos

Ahora que el CRUD de Empresas está completo, puedes:
1. Ejecutar los seeders para crear datos de prueba
2. Implementar el CRUD de Clientes
3. Probar la creación de facturas con los datos completos
