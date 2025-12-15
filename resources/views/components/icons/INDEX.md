# 📦 Índice de Íconos Disponibles

Lista completa de todos los íconos modulares disponibles en el sistema.

## 🏠 Navegación Principal

| Ícono | Componente | Descripción | Uso Principal |
|-------|------------|-------------|---------------|
| 🏠 | `<x-icons.home />` | Casa/Inicio | Logo, breadcrumbs |
| 📊 | `<x-icons.dashboard />` | Dashboard/Grid | Página principal, tableros |
| 👥 | `<x-icons.users />` | Usuarios/Grupo | Gestión de usuarios |
| 📋 | `<x-icons.reports />` | Reportes/Documentos | Informes, documentación |
| 📈 | `<x-icons.analytics />` | Analytics/Gráficos | Estadísticas, métricas |

## 👤 Cuenta y Perfil

| Ícono | Componente | Descripción | Uso Principal |
|-------|------------|-------------|---------------|
| 👤 | `<x-icons.profile />` | Perfil/Usuario | Configuración personal |
| ⚙️ | `<x-icons.settings />` | Configuración | Ajustes, preferencias |
| 🚪 | `<x-icons.logout />` | Cerrar Sesión | Salir del sistema |

## 🔧 Acciones y Utilidades

| Ícono | Componente | Descripción | Uso Principal |
|-------|------------|-------------|---------------|
| ➕ | `<x-icons.plus />` | Agregar/Más | Crear nuevos elementos |
| 🔍 | `<x-icons.search />` | Buscar/Lupa | Formularios de búsqueda |
| ❌ | `<x-icons.close />` | Cerrar/X | Modales, sidebars móviles |
| 🍔 | `<x-icons.menu />` | Menú/Hamburguesa | Navegación móvil |

## 🔔 Comunicación

| Ícono | Componente | Descripción | Uso Principal |
|-------|------------|-------------|---------------|
| 🔔 | `<x-icons.notification />` | Notificaciones/Campana | Alertas, avisos |
| ✉️ | `<x-icons.mail />` | Correo/Sobre | Mensajes, email |
| 📅 | `<x-icons.calendar />` | Calendario | Fechas, eventos |

## 🎨 Ejemplos de Uso Rápido

### Sidebar Navigation
```blade
<x-icons.dashboard /> Dashboard
<x-icons.users /> Usuarios  
<x-icons.analytics /> Analytics
```

### Botones de Acción
```blade
<x-icons.plus class="h-4 w-4 mr-2" /> Crear
<x-icons.search class="h-4 w-4 mr-2" /> Buscar
<x-icons.settings class="h-4 w-4 mr-2" /> Configurar
```

### Con Colores Personalizados
```blade
<x-icons.users class="h-6 w-6 text-blue-500" />
<x-icons.analytics class="h-5 w-5 text-green-600" />
<x-icons.notification class="h-4 w-4 text-red-500" />
```

## 📏 Tamaños Recomendados

| Contexto | Clases Recomendadas | Ejemplo |
|----------|---------------------|---------|
| Sidebar | `h-5 w-5` | `<x-icons.dashboard class="h-5 w-5" />` |
| Botones pequeños | `h-4 w-4` | `<x-icons.plus class="h-4 w-4" />` |
| Headers | `h-6 w-6` o `h-8 w-8` | `<x-icons.analytics class="h-6 w-6" />` |
| Mobile menu | `h-6 w-6` | `<x-icons.menu class="h-6 w-6" />` |

## 🎯 Próximas Adiciones

Íconos que podrían agregarse en el futuro:

- `<x-icons.edit />` - Editar/Lápiz
- `<x-icons.delete />` - Eliminar/Papelera
- `<x-icons.download />` - Descargar
- `<x-icons.upload />` - Subir
- `<x-icons.star />` - Favoritos
- `<x-icons.filter />` - Filtros
- `<x-icons.sort />` - Ordenar
- `<x-icons.refresh />` - Actualizar
- `<x-icons.info />` - Información
- `<x-icons.warning />` - Advertencia
- `<x-icons.success />` - Éxito/Check
- `<x-icons.error />` - Error
- `<x-icons.loading />` - Cargando/Spinner

---

**💡 Tip:** Para agregar un nuevo ícono, crea un archivo `.blade.php` en `resources/views/components/icons/` siguiendo la plantilla del README.md
