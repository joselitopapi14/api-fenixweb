# Documentación CRUD de Empresas

## Endpoint Base
```
POST /api/empresas
```

## 🔐 Autenticación
Requiere autenticación con token Sanctum:
```
Authorization: Bearer {token}
```

---

## 📝 Payload Completo

### ✅ Tu JSON está CASI correcto, pero hay ajustes necesarios:

```json
{
    "nit": "900123456",
    "dv": "7",
    "razon_social": "Joyería El Dorado S.A.S",
    "direccion": "Calle 50 #45-30",
    "departamento_id": 1,
    "municipio_id": 1,
    "comuna_id": 1,
    "barrio_id": 1,
    "tipo_persona_id": 2,
    "tipo_responsabilidad_id": 1,
    "tipo_documento_id": 1,
    "telefono_fijo": "6015551234",
    "celular": "3001234567",
    "email": "contacto@eldorado.com",
    "pagina_web": "https://www.eldorado.com",
    "software_id": "SW123456",
    "software_pin": "PIN987654",
    "representante_legal": "Juan Pérez García",
    "cedula_representante": "1234567890",
    "email_representante": "juan.perez@eldorado.com",
    "direccion_representante": "Calle 60 #50-20",
    "certificate_password": "password123",
    "activa": true
}
```

---

## ⚠️ Diferencias con tu JSON Original

### 1. **Archivos (logo y certificate)**
❌ **NO se envían en JSON**, se envían como **multipart/form-data**

**Correcto:**
```javascript
// Usando FormData en JavaScript
const formData = new FormData();
formData.append('nit', '900123456');
formData.append('dv', '7');
// ... otros campos ...
formData.append('logo', fileInputLogo.files[0]); // Archivo real
formData.append('certificate_path', fileInputCert.files[0]); // Archivo real
```

**Validaciones de archivos:**
- **logo**: 
  - Formatos: jpeg, png, jpg, gif
  - Tamaño máximo: 2MB (2048KB)
  - Campo: `logo` (no "FILE_OBJECT")
  
- **certificate** (Certificado digital):
  - Formatos: p12, pfx
  - Tamaño máximo: 5MB (5120KB)
  - Campo: `certificate_path` (no "certificate")

### 2. **Redes Sociales**
❌ **NO se manejan en el controlador actual**

El modelo `Empresa` tiene la relación `redesSociales()`, pero el controlador **NO procesa** el array `redes_sociales` en el método `store()`.

**Opciones:**
1. **Crear las redes sociales después** de crear la empresa
2. **Modificar el controlador** para aceptar redes sociales en el payload

### 3. **Campo `activa`**
✅ Opcional, por defecto es `true` (boolean)

---

## 📋 Validaciones Completas

### Campos Requeridos
| Campo | Tipo | Validación |
|-------|------|------------|
| `nit` | string | Requerido, máx 20 caracteres, único |
| `dv` | string | Requerido, 1 carácter |
| `razon_social` | string | Requerido, máx 255 caracteres |
| `direccion` | string | Requerido, máx 255 caracteres |
| `email` | string | Requerido, email válido, máx 255 |
| `tipo_persona_id` | integer | Requerido, debe existir en `tipo_personas` |
| `tipo_responsabilidad_id` | integer | Requerido, debe existir en `tipo_responsabilidades` |
| `tipo_documento_id` | integer | Requerido, debe existir en `tipo_documentos` |

### Campos Opcionales
| Campo | Tipo | Validación |
|-------|------|------------|
| `telefono_fijo` | string | Opcional, máx 20 caracteres |
| `celular` | string | Opcional, máx 20 caracteres |
| `pagina_web` | string | Opcional, URL válida, máx 255 |
| `departamento_id` | integer | Opcional, debe existir en `departamentos` |
| `municipio_id` | integer | Opcional, debe existir en `municipios` |
| `comuna_id` | integer | Opcional, debe existir en `comunas` |
| `barrio_id` | integer | Opcional, debe existir en `barrios` |
| `representante_legal` | string | Opcional, máx 255 caracteres |
| `cedula_representante` | string | Opcional, máx 20 caracteres |
| `email_representante` | string | Opcional, email válido, máx 255 |
| `direccion_representante` | string | Opcional, máx 255 caracteres |
| `software_id` | string | Opcional, máx 255 caracteres |
| `software_pin` | string | Opcional, máx 255 caracteres |
| `certificate_password` | string | Opcional, máx 255 caracteres |
| `logo` | file | Opcional, imagen (jpeg/png/jpg/gif), máx 2MB |
| `certificate_path` | file | Opcional, certificado (p12/pfx), máx 5MB |
| `activa` | boolean | Opcional, por defecto `true` |

---

## 🔄 Endpoints Disponibles

### 1. Listar Empresas
```http
GET /api/empresas
```

**Query Parameters:**
- `search`: Buscar por razón social, NIT o email
- `activa`: Filtrar por estado (true/false)
- `sort_by`: Campo para ordenar (default: razon_social)
- `sort_order`: Orden (asc/desc, default: asc)
- `per_page`: Registros por página (default: 15)

**Respuesta:**
```json
{
    "data": [
        {
            "id": 1,
            "nit": "900123456",
            "dv": "7",
            "razon_social": "Joyería El Dorado S.A.S",
            "nit_completo": "900123456-7",
            "departamento": {...},
            "municipio": {...},
            "tipo_persona": {...},
            "tipo_responsabilidad": {...}
        }
    ],
    "current_page": 1,
    "total": 10,
    "per_page": 15
}
```

### 2. Crear Empresa
```http
POST /api/empresas
Content-Type: multipart/form-data
```

**Respuesta exitosa (201):**
```json
{
    "message": "Empresa creada exitosamente",
    "empresa": {
        "id": 1,
        "nit": "900123456",
        "dv": "7",
        "razon_social": "Joyería El Dorado S.A.S",
        "logo": "empresas/logos/abc123.jpg",
        "certificate_path": "empresas/certificates/cert123.p12",
        "departamento": {...},
        "municipio": {...},
        "tipo_persona": {...},
        "tipo_responsabilidad": {...}
    }
}
```

### 3. Ver Empresa
```http
GET /api/empresas/{id}
```

**Respuesta:**
```json
{
    "id": 1,
    "nit": "900123456",
    "dv": "7",
    "razon_social": "Joyería El Dorado S.A.S",
    "direccion_completa": "Calle 50 #45-30, Barrio Centro, Comuna 1, Medellín, Antioquia",
    "departamento": {...},
    "municipio": {...},
    "comuna": {...},
    "barrio": {...},
    "tipo_persona": {...},
    "tipo_responsabilidad": {...},
    "tipo_documento": {...},
    "usuarios": [...],
    "administradores": [...]
}
```

### 4. Actualizar Empresa
```http
PUT /api/empresas/{id}
Content-Type: multipart/form-data
```

**Nota:** Solo administradores de la empresa o admin global pueden actualizar.

### 5. Eliminar Empresa (Soft Delete)
```http
DELETE /api/empresas/{id}
```

**Nota:** Solo admin global puede eliminar empresas.

---

## 🔗 Catálogos Necesarios

**✅ Todos los endpoints de catálogos están implementados:**

### 1. Tipos de Persona
```http
GET /api/tipos-persona
```
**Respuesta:** `[{id, name, code}]`

### 2. Tipos de Responsabilidad
```http
GET /api/tipos-responsabilidad
```
**Respuesta:** `[{id, name, code}]`

### 3. Tipos de Documento
```http
GET /api/tipos-documento
```
**Respuesta:** `[{id, name, code}]`

### 4. Departamentos
```http
GET /api/departamentos
```
**Respuesta:** `[{id, name, code}]`

### 5. Ubicación Jerárquica
```http
GET /api/departamentos/{id}/municipios
```
**Respuesta:** `[{id, name, code, departamento_id}]`

```http
GET /api/municipios/{id}/comunas
```
**Respuesta:** `[{id, nombre, municipio_id}]`

```http
GET /api/comunas/{id}/barrios
```
**Respuesta:** `[{id, nombre, comuna_id}]`

**Nota:** Departamentos y municipios usan `name`, mientras que comunas y barrios usan `nombre`.

---

## 📤 Ejemplo Completo con cURL

### Sin archivos (JSON)
```bash
curl -X POST https://api.example.com/api/empresas \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nit": "900123456",
    "dv": "7",
    "razon_social": "Joyería El Dorado S.A.S",
    "direccion": "Calle 50 #45-30",
    "departamento_id": 1,
    "municipio_id": 1,
    "tipo_persona_id": 2,
    "tipo_responsabilidad_id": 1,
    "tipo_documento_id": 1,
    "email": "contacto@eldorado.com",
    "celular": "3001234567"
  }'
```

### Con archivos (multipart/form-data)
```bash
curl -X POST https://api.example.com/api/empresas \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "nit=900123456" \
  -F "dv=7" \
  -F "razon_social=Joyería El Dorado S.A.S" \
  -F "direccion=Calle 50 #45-30" \
  -F "email=contacto@eldorado.com" \
  -F "tipo_persona_id=2" \
  -F "tipo_responsabilidad_id=1" \
  -F "tipo_documento_id=1" \
  -F "logo=@/path/to/logo.png" \
  -F "certificate_path=@/path/to/certificate.p12" \
  -F "certificate_password=password123"
```

---

## 🔒 Seguridad

### Campos Ocultos en Respuestas
El campo `certificate_password` **NUNCA** se devuelve en las respuestas JSON por seguridad.

### Almacenamiento de Archivos
- **Logo**: Se guarda en `storage/app/public/empresas/logos/`
- **Certificado**: Se guarda en `storage/app/empresas/certificates/` (privado)

### Permisos
- **Crear**: Cualquier usuario autenticado
- **Ver**: Usuario debe pertenecer a la empresa o ser admin global
- **Actualizar**: Administrador de la empresa o admin global
- **Eliminar**: Solo admin global

---

## 🚨 Errores Comunes

### Error 422: Validación
```json
{
    "message": "Error de validación",
    "errors": {
        "nit": ["El campo nit ya ha sido registrado."],
        "email": ["El campo email debe ser una dirección de correo válida."]
    }
}
```

### Error 403: Sin permisos
```json
{
    "message": "No tiene permisos para ver esta empresa"
}
```

### Error 404: No encontrada
```json
{
    "message": "Empresa no encontrada"
}
```

---

## 💡 Notas Importantes

1. **Asociación automática**: Al crear una empresa, si el usuario NO es admin global, se asocia automáticamente como administrador de la empresa.

2. **Redes Sociales**: Actualmente NO se procesan en el controlador. Necesitas:
   - Crear la empresa primero
   - Luego asociar redes sociales mediante otro endpoint (si existe)
   - O modificar el controlador para aceptar `redes_sociales` en el payload

3. **Accessors disponibles**:
   - `nit_completo`: Retorna "NIT-DV" (ej: "900123456-7")
   - `direccion_completa`: Retorna dirección con ubicación completa
   - `nombre`: Alias de `razon_social`

4. **Soft Delete**: Las empresas eliminadas se marcan como eliminadas pero no se borran físicamente de la BD.

---

## 🔧 Modificación Sugerida para Redes Sociales

Si quieres manejar redes sociales en el mismo payload, necesitas modificar el controlador:

```php
// En EmpresaController@store, después de crear la empresa (línea 120):

// Guardar redes sociales si se proporcionan
if ($request->has('redes_sociales') && is_array($request->redes_sociales)) {
    foreach ($request->redes_sociales as $redSocial) {
        $empresa->redesSociales()->attach($redSocial['red_social_id'], [
            'usuario_red_social' => $redSocial['usuario']
        ]);
    }
}
```

Y agregar validación:
```php
'redes_sociales' => 'nullable|array',
'redes_sociales.*.red_social_id' => 'required|exists:redes_sociales,id',
'redes_sociales.*.usuario' => 'required|string|max:255',
```
