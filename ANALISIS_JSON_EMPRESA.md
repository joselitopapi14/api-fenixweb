# Análisis del JSON para Crear Empresa

## ❌ Problemas en tu JSON

### 1. **tipo_responsabilidad_id** - INCORRECTO
```json
"tipo_responsabilidad_id": "O-13"  // ❌ INCORRECTO - Estás enviando el CODE
```

**Problema:** Estás enviando el **código** (`"O-13"`) en lugar del **ID** (número).

**Corrección:** Debes enviar el **ID numérico**:
```json
"tipo_responsabilidad_id": 1  // ✅ CORRECTO - ID de "Gran contribuyente"
```

**Tabla de referencia:**
| ID | Nombre | Code |
|----|--------|------|
| 1 | Gran contribuyente | O-13 |
| 2 | Autorretenedor | O-15 |
| 3 | Agente de retención IVA | O-23 |
| 4 | Régimen simple de tributación | O-47 |
| 5 | No responsable | R-99-PN |

### 2. **tipo_documento_id** - POSIBLE ERROR
```json
"tipo_documento_id": 11  // ⚠️ VERIFICA - ¿Existe el ID 11?
```

**Problema:** El ID 11 podría no existir. Verifica con:
```http
GET /api/test/tipos-documento
```

**Tipos de documento comunes:**
| ID | Code | Nombre |
|----|------|--------|
| ? | 13 | Cédula de ciudadanía |
| ? | 31 | NIT |

---

## ✅ JSON Corregido

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
    "tipo_documento_id": 6,
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

**Cambios realizados:**
- ✅ `tipo_responsabilidad_id`: `"O-13"` → `1` (ID numérico)
- ✅ `tipo_documento_id`: `11` → `6` (ID de NIT, verifica el correcto)

---

## 📊 Estructura Completa de la Tabla `empresas`

### Campos de la tabla (según migraciones):

```php
// Información básica
'nit'                       // string(20) - REQUERIDO, único
'dv'                        // string(1) - REQUERIDO
'razon_social'              // string(255) - REQUERIDO
'direccion'                 // text - REQUERIDO

// Ubicación (todos nullable)
'departamento_id'           // foreignId - OPCIONAL
'municipio_id'              // foreignId - OPCIONAL
'comuna_id'                 // foreignId - OPCIONAL
'barrio_id'                 // foreignId - OPCIONAL

// Contacto
'telefono_fijo'             // string(20) - OPCIONAL
'celular'                   // string(20) - OPCIONAL
'email'                     // string - OPCIONAL
'pagina_web'                // string - OPCIONAL

// Software y certificados DIAN
'software_id'               // string - OPCIONAL
'software_pin'              // string - OPCIONAL
'certificate_path'          // string - OPCIONAL (archivo)
'certificate_password'      // string - OPCIONAL

// Archivos
'logo'                      // string - OPCIONAL (archivo)

// Representante legal
'representante_legal'       // string(255) - REQUERIDO
'cedula_representante'      // string(20) - REQUERIDO
'email_representante'       // string - OPCIONAL
'direccion_representante'   // text - REQUERIDO

// Clasificación
'tipo_persona_id'           // foreignId - OPCIONAL
'tipo_responsabilidad_id'   // foreignId - OPCIONAL
'tipo_documento_id'         // foreignId - OPCIONAL

// Estado
'activa'                    // boolean - default: true
```

---

## 🔍 Cómo Obtener los IDs Correctos

### 1. Tipos de Responsabilidad
```http
GET /api/test/tipos-responsabilidad
```

**Respuesta:**
```json
[
    {"id": 1, "name": "Gran contribuyente", "code": "O-13"},
    {"id": 2, "name": "Autorretenedor", "code": "O-15"},
    {"id": 3, "name": "Agente de retención IVA", "code": "O-23"},
    {"id": 4, "name": "Régimen simple de tributación", "code": "O-47"},
    {"id": 5, "name": "No responsable", "code": "R-99-PN"}
]
```

**Para "Gran contribuyente" (O-13):**
```json
"tipo_responsabilidad_id": 1  // ✅ Usar el ID, no el code
```

### 2. Tipos de Documento
```http
GET /api/test/tipos-documento
```

**Busca el ID correspondiente a "NIT" (code: 31):**
```json
"tipo_documento_id": 6  // ⚠️ Verifica el ID correcto
```

### 3. Tipos de Persona
```http
GET /api/test/tipos-persona
```

**Para "Persona Jurídica" (code: 1):**
```json
"tipo_persona_id": 2  // ✅ Ya está correcto
```

---

## 🚨 Error de Autenticación

Como los endpoints de test funcionan pero los normales no, el problema es **autenticación**.

### Solución: Obtener y Usar Token

#### 1. Login
```http
POST /api/login
Content-Type: application/json

{
    "email": "tu_email@example.com",
    "password": "tu_password"
}
```

**Respuesta:**
```json
{
    "token": "1|abcdefghijklmnopqrstuvwxyz123456",
    "user": {
        "id": 1,
        "name": "Usuario",
        "email": "tu_email@example.com"
    }
}
```

#### 2. Usar el Token en Crear Empresa
```http
POST /api/empresas
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456
Content-Type: application/json

{
    "nit": "900123456",
    "dv": "7",
    ...
}
```

---

## 📝 Validaciones del Controlador

El controlador valida:

```php
'nit' => 'required|string|max:20|unique:empresas,nit'
'dv' => 'required|string|max:1'
'razon_social' => 'required|string|max:255'
'direccion' => 'required|string|max:255'
'email' => 'required|email|max:255'
'tipo_persona_id' => 'required|exists:tipo_personas,id'
'tipo_responsabilidad_id' => 'required|exists:tipo_responsabilidades,id'
'tipo_documento_id' => 'required|exists:tipo_documentos,id'
'representante_legal' => 'nullable|string|max:255'
'cedula_representante' => 'nullable|string|max:20'
'direccion_representante' => 'nullable|string|max:255'
```

**Nota:** Algunos campos son `required` en el controlador pero `nullable` en la BD.

---

## ✅ Checklist Final

Antes de crear la empresa:

- [ ] Obtén un token de autenticación (`POST /api/login`)
- [ ] Verifica los IDs correctos:
  - [ ] `GET /api/test/tipos-persona`
  - [ ] `GET /api/test/tipos-responsabilidad`
  - [ ] `GET /api/test/tipos-documento`
- [ ] Usa IDs numéricos, NO códigos
- [ ] Incluye el token en el header `Authorization: Bearer {token}`
- [ ] Usa `Content-Type: application/json`

---

## 🎯 Próximos Pasos

1. **Obtén los IDs correctos:**
   ```bash
   GET /api/test/tipos-responsabilidad
   GET /api/test/tipos-documento
   ```

2. **Actualiza tu JSON** con los IDs correctos

3. **Haz login** para obtener el token

4. **Crea la empresa** con el token en el header

---

## 💡 Ejemplo Completo con cURL

```bash
# 1. Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'

# Respuesta: {"token": "1|abc123...", ...}

# 2. Crear empresa
curl -X POST http://localhost:8000/api/empresas \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{
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
    "tipo_documento_id": 6,
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
  }'
```
