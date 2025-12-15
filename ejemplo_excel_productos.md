# Ejemplo de Excel para Importación de Productos

## Estructura del archivo Excel

El archivo Excel debe tener las siguientes columnas (en la primera fila como encabezados):

| nombre | descripcion | tipo_producto | tipo_oro |
|--------|-------------|---------------|----------|
| Anillo de Oro 18K Clásico | Anillo elegante de oro de 18 kilates con diseño clásico atemporal | Joyería | Oro 18K |
| Cadena de Plata 925 | Cadena de plata 925 con eslabones tradicionales, 50cm de largo | Joyería | |
| Lingote de Oro 1oz | Lingote de oro puro 999.9 de una onza para inversión | Inversión | Oro Puro |
| Reloj de Oro Amarillo | Reloj de lujo con caja de oro amarillo de 14 kilates | Relojería | Oro 14K |
| Aretes de Oro Blanco | Par de aretes en oro blanco 18K con diseño minimalista | Joyería | Oro Blanco 18K |

## Campos:

### nombre (OBLIGATORIO)
- Nombre único del producto
- Máximo 255 caracteres
- No puede estar vacío

### descripcion (OPCIONAL)
- Descripción detallada del producto
- Puede estar vacío

### tipo_producto (OBLIGATORIO)
- Tipo de producto (ej: Joyería, Inversión, Relojería)
- Máximo 255 caracteres
- Se creará automáticamente si no existe
- No puede estar vacío

### tipo_oro (OPCIONAL)
- Tipo de oro específico (ej: Oro 18K, Oro 14K, Oro Puro)
- Máximo 255 caracteres
- Se creará automáticamente si no existe
- Puede estar vacío para productos que no son de oro

## Características del Sistema:

1. **Análisis Inteligente**: El sistema analiza automáticamente los datos y crea tipos de producto y oro si no existen.

2. **Selección de Empresa**: Puedes elegir a qué empresa asignar los productos o dejarlos como globales.

3. **Modos de Importación**:
   - **Solo Crear**: Solo crea productos nuevos, omite los existentes
   - **Solo Actualizar**: Solo actualiza productos existentes, omite los nuevos
   - **Crear y Actualizar**: Modo completo que crea nuevos y actualiza existentes

4. **Validaciones**:
   - Nombres únicos por empresa
   - Validación de campos obligatorios
   - Detección de duplicados
   - Logging de errores detallado

5. **Formatos Soportados**: .xlsx, .xls, .csv (hasta 10MB)

## Ejemplos de Tipos de Producto:
- Joyería
- Inversión
- Relojería
- Numismática
- Decoración
- Artesanía

## Ejemplos de Tipos de Oro:
- Oro 24K (Oro Puro)
- Oro 18K
- Oro 14K
- Oro 10K
- Oro Blanco 18K
- Oro Rosa 14K
- Oro Amarillo
