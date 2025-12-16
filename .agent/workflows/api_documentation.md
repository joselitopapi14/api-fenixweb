---
description: Documentación de los endpoints API 100% implementados
---

# API FenixWeb - Endpoints Implementados (Stateless)

Este documento detalla los endpoints que **ya están implementados, probados y funcionando al 100%** para la arquitectura *stateless* (sin estado, usando tokens). 

Todas las rutas base son: `{{base_url}}/api/...`

## 1. Autenticación y Usuarios

Estas rutas manejan el ingreso al sistema y la gestión de permisos. El usuario "Admin" (configurado en seeders) tiene acceso total.

| Método | Endpoint | Descripción | Requiere Auth | Estado |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/login` | Iniciar sesión y obtener Token Bearer. | No | ✅ 100% |
| `POST` | `/register` | Registrar nuevo usuario. | No | ✅ 100% |
| `POST` | `/logout` | Revocar token actual. | Sí (Sanctum) | ✅ 100% |
| `GET` | `/user` | Obtener datos del usuario logueado. | Sí (Sanctum) | ✅ 100% |
| `GET` | `/users` | Listar usuarios (Gestión Admin). | Sí (Sanctum) | ✅ 100% |
| `POST` | `/users` | Crear usuarios (Gestión Admin). | Sí (Sanctum) | ✅ 100% |
| `GET` | `/roles` | Listar Roles disponibles. | Sí (Sanctum) | ✅ 100% |
| `GET` | `/permissions` | Listar Permisos disponibles. | Sí (Sanctum) | ✅ 100% |

> **Nota sobre Roles:** Sí, existen funcionalidades para asignar roles. Los endpoints `/users` (crear/editar) y `/roles` permiten esta gestión. El usuario *Admin* inicial se crea vía Seeder (`UserSeeder`).

## 2. Catálogos Principales (Core Catalogs)

Endpoints de consulta para listas desplegables y datos maestros.

| Método | Endpoint | Descripción | Parámetros | Estado |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/empresas` | Listar empresas a las que tiene acceso el usuario. | - | ✅ 100% |
| `GET` | `/tipos-producto` | Catálogo de tipos de producto (Joyería, Reloj, etc.) | - | ✅ 100% |
| `GET` | `/tipos-oro` | Catálogo de quilates (18k, 24k, etc.) | - | ✅ 100% |
| `GET` | `/tipos-medida` | Unidades de medida (Gramos, Unidades). | - | ✅ 100% |
| `GET` | `/tipos-factura` | Tipos de documento fiscal. | - | ✅ 100% |
| `GET` | `/medios-pago` | Formas de pago (Efectivo, Tarjeta). | - | ✅ 100% |
| `GET` | `/tipos-pago` | Modalidad de pago (Contado, Crédito). | - | ✅ 100% |
| `GET` | `/retenciones` | Tipos de retención fiscal. | - | ✅ 100% |

## 3. Datos de Negocio (Business Data)

Endpoints que retornan información operativa filtrada por empresa.

| Método | Endpoint | Descripción | Parámetros Query | Estado |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/clientes` | Listar clientes de una empresa. | `?empresa_id=X` | ✅ 100% |
| `GET` | `/productos` | Inventario de productos. | `?empresa_id=X` | ✅ 100% |
| `GET` | `/resoluciones` | Resoluciones de facturación DIAN activas. | `?empresa_id=X` | ✅ 100% |

## 4. Ubicación Geográfica

Endpoints en cascada para selección de ubicación.

| Método | Endpoint | Descripción | Estado |
| :--- | :--- | :--- | :--- |
| `GET` | `/departamentos/{id}/municipios` | Municipios de un departamento. | ✅ 100% |
| `GET` | `/municipios/{id}/comunas` | Comunas de un municipio. | ✅ 100% |
| `GET` | `/comunas/{id}/barrios` | Barrios de una comuna. | ✅ 100% |

## 5. Facturación (Core Feature)

Módulo transaccional principal de la API.

| Método | Endpoint | Descripción | Body (JSON) | Estado |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/facturas` | **Crear Factura Completa**. Incluye validaciones, cálculo de impuestos, retenciones y guardado de detalles. | Ver estructura JSON abajo | ✅ 100% |

### Estructura JSON para Crear Factura
```json
{
    "cliente_id": 1,
    "empresa_id": 1,
    "resolucion_id": 1,
    "tipo_factura_id": 1,
    "medio_pago_id": 1,
    "tipo_pagos_id": 1, // Contado o Crédito
    "observaciones": "Venta mostrador",
    "productos": [
        {
            "id": 10,
            "cantidad": 1,
            "descuento": 0,
            "recargo": 0
        }
    ],
    "retenciones": [ // Opcional
        {
            "retencion_id": 1,
            "concepto_retencion_id": 5,
            "porcentaje_valor": 2.5
        }
    ],
    "valor_recibido": 500000 // Para calcular cambio
}
```

---
**Resumen Técnico:**
Todos estos endpoints están construidos siguiendo principios **REST**, retornan **JSON** y utilizan autenticación **Bearer Token (Laravel Sanctum)**. La migración del monolito a API para estas funciones está completa.
