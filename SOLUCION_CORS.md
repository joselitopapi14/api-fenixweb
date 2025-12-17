# Solución a Problemas de CORS y URL Duplicada

## Problemas Identificados

### 1. ❌ URL Duplicada
```
https://web.fenix-crud.dev/apiapi/producto
                          ^^^^^^ - "api" duplicado
```

**Causa**: El frontend está agregando `/api` cuando el baseURL ya lo incluye.

### 2. ❌ Error CORS
```
Access to fetch at 'https://web.fenix-crud.dev/apiapi/producto' 
from origin 'http://localhost:5173' has been blocked by CORS policy
```

## Soluciones Aplicadas

### ✅ 1. Configuración CORS Actualizada

**Archivo**: `config/cors.php`

```php
'allowed_origins' => [
    'http://localhost:5173',  // Vite dev server
    'http://localhost:3000',  // Alternativo
    'http://localhost:8080',  // Alternativo
    'https://web.fenix-crud.dev',
    'https://fenix-crud.dev',
],

'supports_credentials' => true,
'max_age' => 86400, // 24 horas
```

### ✅ 2. Comandos para Aplicar

**En Docker:**
```bash
docker exec -it <contenedor> bash -c "cd /var/www/html && php artisan config:clear && php artisan cache:clear"
```

**En Local:**
```bash
php artisan config:clear
php artisan cache:clear
```

---

## 🔧 Arreglar URL Duplicada en Frontend

El problema está en el **cliente HTTP del frontend**. Necesitas revisar:

### Opción 1: Verificar baseURL

**Archivo**: Probablemente `http-client.ts` o similar

```typescript
// ❌ INCORRECTO - Si baseURL ya tiene /api
const api = axios.create({
  baseURL: 'https://web.fenix-crud.dev/api'
});

// Y luego haces:
api.post('/api/producto/create') // Resultado: /api/api/producto/create

// ✅ CORRECTO - Usa una de estas opciones:

// Opción A: baseURL con /api, rutas sin /api
const api = axios.create({
  baseURL: 'https://web.fenix-crud.dev/api'
});
api.post('/producto/create') // Resultado: /api/producto/create

// Opción B: baseURL sin /api, rutas con /api
const api = axios.create({
  baseURL: 'https://web.fenix-crud.dev'
});
api.post('/api/producto/create') // Resultado: /api/producto/create
```

### Opción 2: Buscar en el Código Frontend

```bash
# Buscar donde se define el baseURL
grep -r "baseURL" src/
grep -r "api.fenix" src/
grep -r "web.fenix" src/
```

---

## 📋 Checklist de Verificación

- [x] Actualizar `config/cors.php`
- [ ] Ejecutar `php artisan config:clear`
- [ ] Ejecutar `php artisan cache:clear`
- [ ] Revisar `http-client.ts` en el frontend
- [ ] Verificar que baseURL no duplique `/api`
- [ ] Probar la petición nuevamente

---

## 🧪 Prueba Manual

Después de aplicar los cambios, prueba con curl:

```bash
# Desde tu máquina local
curl -X POST https://web.fenix-crud.dev/api/producto/create \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{"nombre":"Test","tipo_producto_id":1}'
```

Deberías ver los headers CORS en la respuesta:
```
Access-Control-Allow-Origin: http://localhost:5173
Access-Control-Allow-Credentials: true
```

---

## 🎯 Próximo Paso

**Encuentra y corrige el archivo del frontend que duplica `/api`**

Probablemente está en:
- `src/services/http-client.ts`
- `src/api/client.ts`
- `src/config/api.ts`
- O similar

Busca donde se define el `baseURL` y asegúrate de que no se duplique `/api`.
