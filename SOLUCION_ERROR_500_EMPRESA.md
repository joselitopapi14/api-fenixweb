# Solución al Error 500 al Crear Empresa

## Problema Identificado

El error 500 al crear una empresa puede deberse a varias causas:

### 1. ✅ SOLUCIONADO: Eager Loading de Relaciones Nullable
**Problema**: El código intentaba cargar `tipoPersona` y `tipoResponsabilidad` que son nullable.
**Solución**: Removidas del `load()`.

### 2. ⚠️ POSIBLE: Falta de Datos en Catálogos

Para crear una empresa necesitas que existan:
- Departamentos (departamento_id: 1)
- Municipios (municipio_id: 1)

## Verificar que Existan los Datos

### Opción A: Desde el Frontend
```javascript
// Verificar departamentos
const deps = await api.get('/departamentos');
console.log(deps.data);

// Verificar municipios del departamento 1
const muns = await api.get('/departamentos/1/municipios');
console.log(muns.data);
```

### Opción B: Desde Laravel Tinker
```bash
php artisan tinker
>>> \App\Models\Departamento::find(1)
>>> \App\Models\Municipio::find(1)
```

### Opción C: Ejecutar Seeders
```bash
# Si no hay datos, ejecuta los seeders
php artisan db:seed --class=DepartamentosSeeder
php artisan db:seed --class=MunicipiosSeeder
```

## Payload Actualizado para Prueba

### Con IDs Reales
Primero obtén los IDs reales:
```bash
GET /api/departamentos
```

Luego usa esos IDs:
```json
{
  "nit": "900123456",
  "dv": "7",
  "razon_social": "Mi Empresa SAS",
  "direccion": "Calle 123 #45-67",
  "departamento_id": <ID_REAL_DEL_DEPARTAMENTO>,
  "municipio_id": <ID_REAL_DEL_MUNICIPIO>,
  "representante_legal": "Juan Pérez",
  "cedula_representante": "1234567890",
  "direccion_representante": "Calle 100 #20-30"
}
```

## Comandos para Aplicar el Fix

**En Docker:**
```bash
docker exec -it <contenedor> bash -c "cd /var/www/html && php artisan config:clear && php artisan cache:clear"
```

**En Local:**
```bash
php artisan config:clear
php artisan cache:clear
```

## Ver Logs del Error Real

**En Docker:**
```bash
docker exec -it <contenedor> bash -c "cd /var/www/html && tail -100 storage/logs/laravel.log"
```

**En Local:**
```bash
Get-Content storage\logs\laravel.log -Tail 50
```

## Prueba Rápida

Después de aplicar el fix, prueba con este payload mínimo:

```bash
curl -X POST https://web.fenix-crud.dev/api/empresa/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "nit": "900123456",
    "dv": "7",
    "razon_social": "Test SAS",
    "direccion": "Calle 1",
    "departamento_id": 1,
    "municipio_id": 1,
    "representante_legal": "Test",
    "cedula_representante": "123",
    "direccion_representante": "Calle 2"
  }'
```

## Próximos Pasos

1. ✅ Aplicar el fix del código (ya hecho)
2. ⏳ Limpiar caché
3. ⏳ Verificar que existan departamentos y municipios
4. ⏳ Probar nuevamente con IDs reales
