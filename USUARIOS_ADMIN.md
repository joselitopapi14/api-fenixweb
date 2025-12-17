# Usuarios Admin Creados

## Credenciales de Acceso

Después de ejecutar las migraciones y seeders, estos usuarios estarán disponibles:

### 1. Ronal Blanquicett
- **Email**: `ronalabn@gmail.com`
- **Contraseña**: `Ronal2024!`
- **Rol**: Administrador Global

### 2. Gabriel Galeano Guerra
- **Email**: `ggaleanoguerra@gmail.com`
- **Contraseña**: `Gabriel2024!`
- **Rol**: Administrador Global

### 3. Jose
- **Email**: `jose@fenixweb.com`
- **Contraseña**: `Jose2024!`
- **Rol**: Administrador Global

---

## Cómo Ejecutar

```bash
# Ejecutar migraciones y seeders
php artisan migrate:fresh --seed

# O solo seeders si ya tienes las migraciones
php artisan db:seed
```

---

## ⚠️ IMPORTANTE - SEGURIDAD

**CAMBIAR CONTRASEÑAS DESPUÉS DEL PRIMER LOGIN**

Estas contraseñas están hardcodeadas en el código fuente y son visibles para cualquiera con acceso al repositorio.

**Pasos recomendados:**
1. Hacer login con las credenciales por defecto
2. Ir a perfil/configuración
3. Cambiar la contraseña inmediatamente
4. En producción, usar contraseñas fuertes y únicas

---

## Archivos Modificados

- ✅ **Creado**: `database/seeders/AdminUsersSeeder.php`
- ✅ **Actualizado**: `database/seeders/DatabaseSeeder.php`
- ✅ **Eliminado**: `database/seeders/LegacyDefaultUsersSeeder.php`

---

## Verificación

Para verificar que los usuarios se crearon correctamente:

```bash
php artisan tinker
>>> User::whereIn('email', ['ronalabn@gmail.com', 'ggaleanoguerra@gmail.com', 'jose@fenixweb.com'])->get(['name', 'email']);
```
