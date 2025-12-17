# Debugging Error 500 - Crear Empresa

## 🔍 Diagnóstico del Problema

### Posibles Causas del Error 500

1. **Problema de Autenticación**
   - Los endpoints de catálogos requieren autenticación (`auth:sanctum`)
   - Si no envías el token, deberías recibir 401, no 500
   - Si recibes 500, podría ser un problema con el middleware de autenticación

2. **Problema con el Trait LogsActivity**
   - Los modelos usan el trait `LogsActivity` de Spatie
   - Este trait se activa en eventos de Eloquent (created, updated, deleted)
   - Las consultas SELECT no deberían activarlo

3. **Problema con SoftDeletes**
   - Algunos modelos usan `SoftDeletes`
   - Esto podría causar problemas si la columna `deleted_at` no existe

---

## 🧪 Endpoints de Prueba Creados

He creado endpoints **SIN autenticación** para debugging:

### 1. Test Tipos de Persona
```http
GET /api/test/tipos-persona
```

### 2. Test Tipos de Responsabilidad
```http
GET /api/test/tipos-responsabilidad
```

### 3. Test Tipos de Documento
```http
GET /api/test/tipos-documento
```

**Estos endpoints mostrarán el error exacto si hay alguno.**

---

## 📝 Pasos para Debugging

### Paso 1: Probar Endpoints de Prueba (Sin Autenticación)

```bash
# Usando curl
curl http://localhost:8000/api/test/tipos-persona

# O en el navegador
http://localhost:8000/api/test/tipos-persona
```

**Resultado esperado:**
```json
[
    {
        "id": 1,
        "name": "Persona Natural",
        "code": "2"
    },
    {
        "id": 2,
        "name": "Persona Jurídica",
        "code": "1"
    }
]
```

**Si hay error:**
```json
{
    "error": "Mensaje del error",
    "file": "/ruta/al/archivo.php",
    "line": 123
}
```

### Paso 2: Verificar Autenticación

Si los endpoints de prueba funcionan, el problema es la autenticación.

**Verifica que estés enviando el token:**
```http
GET /api/tipos-persona
Authorization: Bearer tu_token_aqui
```

**Para obtener un token:**
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
    "token": "1|abcdef123456...",
    "user": {...}
}
```

### Paso 3: Verificar Logs de Laravel

```bash
# Ver últimas 50 líneas del log
tail -n 50 storage/logs/laravel.log

# O en Windows PowerShell
Get-Content storage\logs\laravel.log -Tail 50
```

Busca líneas que contengan:
- `ERROR`
- `Exception`
- `SQLSTATE`

### Paso 4: Verificar Configuración de Sanctum

```bash
php artisan config:cache
php artisan route:cache
php artisan cache:clear
```

---

## 🔧 Soluciones Comunes

### Solución 1: Limpiar Caché

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Solución 2: Verificar Variables de Entorno

Verifica en `.env`:
```env
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SESSION_DRIVER=cookie
```

### Solución 3: Verificar Middleware

En `app/Http/Kernel.php`, verifica que `\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class` esté en el grupo `api`.

### Solución 4: Verificar Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate:status

# Si falta alguna tabla
php artisan migrate
```

---

## 📊 Verificación de Datos

### Script de Diagnóstico

He creado `diagnostico_catalogos.php` que verifica:
- ✅ Todos los modelos funcionan
- ✅ Todos los IDs del payload existen
- ✅ Las consultas SELECT funcionan

**Ejecutar:**
```bash
php diagnostico_catalogos.php
```

---

## 🚨 Errores Comunes y Soluciones

### Error: "Class 'TipoPersona' not found"
**Solución:**
```bash
composer dump-autoload
```

### Error: "SQLSTATE[42S02]: Base table or view not found"
**Solución:**
```bash
php artisan migrate
```

### Error: "Unauthenticated" (401)
**Solución:** Envía el token en el header:
```
Authorization: Bearer {token}
```

### Error: "The given data was invalid" (422)
**Solución:** Verifica que todos los IDs en el payload existan en la BD.

---

## 📋 Checklist de Verificación

- [ ] Los endpoints de prueba (`/api/test/*`) funcionan
- [ ] Tienes un token de autenticación válido
- [ ] Estás enviando el token en el header `Authorization`
- [ ] Todos los IDs del payload existen en la BD
- [ ] Las migraciones están ejecutadas
- [ ] La caché está limpia

---

## 💡 Próximos Pasos

1. **Prueba los endpoints de prueba** (`/api/test/tipos-persona`)
2. **Comparte el error exacto** que recibes
3. **Verifica los logs** de Laravel
4. **Confirma que estás autenticado** correctamente

---

## 📞 Información para Reportar

Si el problema persiste, proporciona:

1. **URL exacta** que estás llamando
2. **Método HTTP** (GET, POST, etc.)
3. **Headers** que estás enviando
4. **Body** del request (si aplica)
5. **Respuesta completa** del servidor
6. **Últimas 50 líneas** del log de Laravel

---

## ⚠️ Nota Importante

Los endpoints de prueba (`/api/test/*`) son **TEMPORALES** y **NO requieren autenticación**. 

**Elimínalos en producción** por seguridad.
