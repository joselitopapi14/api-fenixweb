# Endpoints y Payloads Mínimos para Crear Entidades

## 🏢 EMPRESA

### Endpoints Disponibles (Todos funcionan):
```
POST /api/empresa          ← Recomendado (más corto)
POST /api/empresa/create   ← También funciona
POST /api/empresas         ← REST estándar
```

### Payload Mínimo:
```json
{
  "nit": "900123456",
  "dv": "7",
  "razon_social": "Mi Empresa SAS",
  "direccion": "Calle 123 #45-67",
  "departamento_id": 1,
  "municipio_id": 1,
  "representante_legal": "Juan Pérez",
  "cedula_representante": "1234567890",
  "direccion_representante": "Calle 100 #20-30"
}
```

### Payload Completo (Opcional):
```json
{
  "nit": "900123456",
  "dv": "7",
  "razon_social": "Mi Empresa SAS",
  "direccion": "Calle 123 #45-67",
  "departamento_id": 1,
  "municipio_id": 1,
  "comuna_id": 5,
  "barrio_id": 10,
  "tipo_persona_id": 2,
  "tipo_responsabilidad_id": 1,
  "tipo_documento_id": 6,
  "telefono_fijo": "6012345678",
  "celular": "3001234567",
  "email": "contacto@miempresa.com",
  "pagina_web": "https://miempresa.com",
  "software_id": "SOFTWARE123",
  "software_pin": "PIN123",
  "certificate_password": "password123",
  "representante_legal": "Juan Pérez",
  "cedula_representante": "1234567890",
  "email_representante": "juan@miempresa.com",
  "direccion_representante": "Calle 100 #20-30",
  "redes_sociales": [
    {
      "red_social_id": 1,
      "usuario": "@miempresa"
    }
  ]
}
```

---

## 👤 CLIENTE

### Endpoints Disponibles:
```
POST /api/cliente          ← Recomendado
POST /api/cliente/create   ← También funciona
POST /api/clientes         ← REST estándar
```

### Payload Mínimo:
```json
{
  "empresa_id": 1,
  "tipo_documento_id": 1,
  "cedula_nit": "1234567890",
  "nombres": "Juan",
  "apellidos": "Pérez"
}
```

### Payload Completo (Opcional):
```json
{
  "empresa_id": 1,
  "tipo_documento_id": 1,
  "cedula_nit": "1234567890",
  "nombres": "Juan",
  "apellidos": "Pérez",
  "fecha_nacimiento": "1990-01-15",
  "direccion": "Calle 50 #30-20",
  "telefono": "6012345678",
  "celular": "3001234567",
  "email": "juan@example.com",
  "departamento_id": 1,
  "municipio_id": 1,
  "comuna_id": 5,
  "barrio_id": 10,
  "tipo_persona_id": 1,
  "tipo_responsabilidad_id": 1,
  "redes_sociales": [
    {
      "red_social_id": 1,
      "usuario": "@juanperez"
    }
  ]
}
```

---

## 📦 PRODUCTO

### Endpoints Disponibles:
```
POST /api/producto         ← Recomendado (lo que usa tu frontend)
POST /api/producto/create  ← También funciona
POST /api/productos        ← REST estándar
```

### Payload Mínimo:
```json
{
  "nombre": "Producto de Prueba",
  "tipo_producto_id": 1
}
```

### Payload Completo (Opcional):
```json
{
  "nombre": "Anillo de Oro 18K",
  "descripcion": "Anillo de oro de 18 kilates",
  "tipo_producto_id": 1,
  "tipo_oro_id": 1,
  "empresa_id": 1,
  "codigo_barras": "7891234567890",
  "precio_venta": 500000,
  "precio_compra": 300000,
  "peso": 5.5,
  "tipo_medida_id": 1,
  "impuestos": [1, 2]
}
```

---

## 🔑 Obtener IDs de Catálogos

### Departamentos:
```
GET /api/departamentos
```

### Municipios de un Departamento:
```
GET /api/departamentos/{departamento_id}/municipios
```

### Tipos de Documento:
```
GET /api/tipos-documento
```

### Tipos de Persona:
```
GET /api/tipos-persona
```

### Tipos de Responsabilidad:
```
GET /api/tipos-responsabilidad
```

### Tipos de Producto:
```
GET /api/tipos-producto
```

### Tipos de Oro:
```
GET /api/tipos-oro
```

### Tipos de Medida:
```
GET /api/tipos-medida
```

---

## 🧪 Ejemplo de Uso en JavaScript

### Crear Empresa:
```javascript
const response = await api.post('/empresa', {
  nit: "900123456",
  dv: "7",
  razon_social: "Mi Empresa SAS",
  direccion: "Calle 123 #45-67",
  departamento_id: 1,
  municipio_id: 1,
  representante_legal: "Juan Pérez",
  cedula_representante: "1234567890",
  direccion_representante: "Calle 100 #20-30"
});
```

### Crear Cliente:
```javascript
const response = await api.post('/cliente', {
  empresa_id: 1,
  tipo_documento_id: 1,
  cedula_nit: "1234567890",
  nombres: "Juan",
  apellidos: "Pérez"
});
```

### Crear Producto:
```javascript
const response = await api.post('/producto', {
  nombre: "Producto de Prueba",
  tipo_producto_id: 1
});
```

---

## ⚠️ Notas Importantes

1. **Autenticación**: Todos estos endpoints requieren autenticación con Sanctum
2. **Headers necesarios**:
   ```javascript
   {
     'Content-Type': 'application/json',
     'Accept': 'application/json',
     'Authorization': 'Bearer YOUR_TOKEN'
   }
   ```
3. **CORS**: Ya está configurado para `localhost:5173` y `web.fenix-crud.dev`
4. **Archivos**: Para subir `logo` o `certificate`, usa `FormData` con `Content-Type: multipart/form-data`

---

## 🎯 Resumen Rápido

| Entidad | Endpoint | Campos Mínimos |
|---------|----------|----------------|
| Empresa | `POST /api/empresa` | nit, dv, razon_social, direccion, departamento_id, municipio_id, representante_legal, cedula_representante, direccion_representante |
| Cliente | `POST /api/cliente` | empresa_id, tipo_documento_id, cedula_nit, nombres, apellidos |
| Producto | `POST /api/producto` | nombre, tipo_producto_id |
