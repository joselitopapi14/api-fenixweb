# Sistema de Íconos Modulares

Este directorio contiene componentes de íconos modulares para el sidebar de Laravel. En lugar de tener SVGs directamente en el archivo del sidebar, ahora cada ícono es un componente Blade independiente.

## 📁 Estructura

```
resources/views/components/icons/
├── analytics.blade.php    # Ícono de gráficos/analytics
├── calendar.blade.php     # Ícono de calendario
├── close.blade.php        # Ícono de cerrar (X)
├── dashboard.blade.php    # Ícono de dashboard/grid
├── home.blade.php         # Ícono de casa/home
├── logout.blade.php       # Ícono de logout/salir
├── mail.blade.php         # Ícono de correo/sobre
├── menu.blade.php         # Ícono de menú hamburguesa
├── notification.blade.php # Ícono de campana/notificaciones
├── plus.blade.php         # Ícono de más/agregar
├── profile.blade.php      # Ícono de perfil/usuario
├── reports.blade.php      # Ícono de reportes/documentos
├── search.blade.php       # Ícono de búsqueda/lupa
├── settings.blade.php     # Ícono de configuración/engranaje
└── users.blade.php        # Ícono de usuarios/grupo
```

## 🚀 Uso

### Uso Básico
Para usar un ícono en cualquier archivo Blade, simplemente llama al componente:

```blade
<x-icons.dashboard />
<x-icons.users />
<x-icons.settings />
```

### Con Clases CSS Personalizadas
Puedes agregar clases CSS adicionales:

```blade
<x-icons.dashboard class="h-6 w-6 text-blue-500" />
<x-icons.users class="h-4 w-4 text-gray-400 hover:text-gray-600" />
```

### En el Sidebar
Así se usan en el sidebar actual:

```blade
<a href="{{ route('dashboard') }}" class="group flex gap-x-3...">
    <x-icons.dashboard />
    Dashboard
</a>

<a href="#" class="group flex gap-x-3...">
    <x-icons.users />
    Users
</a>
```

## ⚙️ Configuración por Defecto

Cada componente de ícono tiene configuraciones por defecto:
- **Clases por defecto**: `h-5 w-5 shrink-0` (puedes sobrescribirlas)
- **ViewBox**: `0 0 24 24` (estándar Heroicons)
- **Stroke**: `currentColor` (hereda el color del texto)
- **Fill**: `none` (solo contornos)
- **Stroke-width**: `1.5`

## 🎨 Personalización

### Modificar un Ícono Existente
1. Abre el archivo del ícono en `resources/views/components/icons/`
2. Modifica el SVG path según necesites
3. Guarda el archivo

### Agregar un Nuevo Ícono
1. Crea un nuevo archivo `.blade.php` en `resources/views/components/icons/`
2. Usa esta plantilla:

```blade
{{-- Descripción del ícono --}}
<svg {{ $attributes->merge(['class' => 'h-5 w-5 shrink-0']) }} fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" d="TU_SVG_PATH_AQUÍ" />
</svg>
```

3. Úsalo con `<x-icons.nombre-del-archivo />`

### Cambiar Tamaños por Defecto
Para cambiar el tamaño por defecto de un ícono específico, modifica la clase `class` en su archivo:

```blade
{{-- Para un ícono más grande por defecto --}}
<svg {{ $attributes->merge(['class' => 'h-6 w-6 shrink-0']) }} ...>
```

## 🌟 Ventajas de este Sistema

1. **Modularidad**: Cada ícono es independiente y reutilizable
2. **Mantenibilidad**: Fácil de actualizar íconos sin tocar el sidebar
3. **Consistencia**: Todos los íconos siguen el mismo patrón
4. **Flexibilidad**: Puedes usar los íconos en cualquier parte de la aplicación
5. **Performance**: Laravel cachea los componentes automáticamente
6. **Escalabilidad**: Fácil agregar nuevos íconos según necesites

## 📖 Ejemplos de Uso

### En el Sidebar
```blade
<li>
    <a href="{{ route('dashboard') }}" class="sidebar-link">
        <x-icons.dashboard />
        Dashboard
    </a>
</li>
```

### En Botones
```blade
<button class="btn btn-primary">
    <x-icons.plus class="h-4 w-4 mr-2" />
    Agregar Nuevo
</button>
```

### En Headers
```blade
<h1 class="page-title">
    <x-icons.analytics class="h-8 w-8 mr-3" />
    Analytics Dashboard
</h1>
```

### En Notificaciones
```blade
<div class="notification">
    <x-icons.notification class="h-5 w-5 text-yellow-500" />
    Tienes 3 notificaciones nuevas
</div>
```

## 🔄 Migración desde SVGs Directos

Si tienes SVGs directos en otros archivos, puedes migrarlos fácilmente:

**Antes:**
```blade
<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
</svg>
```

**Después:**
```blade
<x-icons.plus />
```

## 🎯 Próximos Pasos

- Agregar más íconos según las necesidades de la aplicación
- Crear categorías de íconos (por ejemplo: `x-icons.social.facebook`)
- Implementar íconos con estados (activo/inactivo)
- Agregar soporte para íconos de diferentes librerías (Feather, FontAwesome, etc.)
