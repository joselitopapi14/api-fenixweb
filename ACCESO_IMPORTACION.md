# Acceso al Sistema de Importación de Productos

## 🚀 Formas de Acceder

### 1. **Desde la Vista de Productos (Recomendado)**
- Ve a la sección **"Gestión de Productos"**
- En la esquina superior derecha verás un botón verde **"Importar Excel"**
- Haz clic en ese botón para acceder al sistema de importación

### 2. **URL Directa**
Puedes acceder directamente navegando a:
```
/productos/import
```

### 3. **Ruta Completa**
Si tienes el dominio configurado:
```
https://tu-dominio.com/productos/import
```

## 🔐 Permisos Requeridos

Para acceder al sistema de importación necesitas:
- **`registros.create`** - Permiso para crear productos
- **`empresa.access`** - Acceso basado en empresa

## 📍 Ubicación en el Sistema

```
Menú Principal
├── Productos
│   ├── Gestión de Productos ← AQUÍ está el botón "Importar Excel"
│   ├── Crear Producto
│   └── ...
```

## 🎯 Flujo de Navegación

1. **Login** → Dashboard
2. **Menú Productos** → Gestión de Productos  
3. **Botón "Importar Excel"** → Sistema de Importación
4. **Descargar Plantilla** → Completar datos
5. **Subir archivo** → Procesar importación

## ⚡ Funciones Disponibles

Una vez en `/productos/import` tendrás acceso a:

- **📥 Importar Productos** - Subir archivo Excel/CSV
- **📄 Descargar Plantilla** - Obtener formato correcto
- **👁️ Previsualizar** - Ver datos antes de importar
- **⚙️ Configurar Empresa** - Asignar productos a empresa específica
- **🔄 Modos de Importación** - Crear, actualizar o ambos

## 📱 Interfaz Responsive

El sistema funciona en:
- 💻 **Desktop** - Interfaz completa
- 📱 **Mobile** - Adaptada para móviles
- 🖥️ **Tablet** - Optimizada para tablets

## 🔧 Características del Botón

El botón **"Importar Excel"** es:
- **Color verde** - Para destacar la funcionalidad
- **Icono de subida** - Visual intuitivo
- **Ubicado junto a "Nuevo Producto"** - Fácil acceso
- **Solo visible con permisos** - Seguridad integrada
