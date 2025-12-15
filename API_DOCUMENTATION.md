# API Documentation

Base URL: `http://localhost:8000/api`

## Authentication

### Register
**POST** `/register`

**Body (JSON):**
```json
{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "c_password": "password123"
}
```

### Login
**POST** `/login`

**Body (JSON):**
```json
{
    "email": "test@example.com",
    "password": "password123"
}
```

### Logout
**POST** `/logout`
*Headers:* `Authorization: Bearer <token>`

---

## Users (Protected)
*Headers:* `Authorization: Bearer <token>`

### Get Current User
**GET** `/user`

### List Users
**GET** `/users`

### Create User
**POST** `/users`
```json
{
    "name": "New User",
    "email": "newuser@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "roles": ["admin"]
}
```

---

## Core Data (Protected)
*Headers:* `Authorization: Bearer <token>`

### List Empresas
**GET** `/empresas`

### Catalogs
*   **GET** `/tipos-producto`
*   **GET** `/tipos-oro`
*   **GET** `/tipos-medida`
*   **GET** `/tipos-factura`
*   **GET** `/medios-pago`
*   **GET** `/tipos-pago`
*   **GET** `/retenciones`

### Ubicación
*   **GET** `/departamentos/{id}/municipios`
*   **GET** `/municipios/{id}/comunas`
*   **GET** `/comunas/{id}/barrios`

---

## Business Resources (Protected)
*Headers:* `Authorization: Bearer <token>`

### Clientes
**GET** `/clientes?empresa_id=1`

### Productos
**GET** `/productos?empresa_id=1`

### Resoluciones
**GET** `/resoluciones?empresa_id=1`

---

## Invoices (Protected)
*Headers:* `Authorization: Bearer <token>`

### Create Invoice
**POST** `/facturas`

**Body (Example):**
```json
{
    "cliente_id": 1,
    "empresa_id": 1,
    "resolucion_id": 1,
    "tipo_factura_id": 1,
    "medio_pago_id": 1,
    "observaciones": "Invoice note",
    "items": [
        {
            "producto_id": 12,
            "cantidad": 1,
            "precio": 50000,
            "impuestos": [
                { "id": 1, "porcentaje": 19 }
            ]
        }
    ]
}
```
