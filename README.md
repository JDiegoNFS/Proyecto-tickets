# 🎫 Sistema de Tickets - Gestión Profesional

Sistema completo de gestión de tickets con múltiples roles, jerarquías organizacionales y sistema de temas avanzado.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)

## ✨ Características Principales

### 🎨 Sistema de Temas Avanzado
- **6 Temas Únicos**: Claro, Oscuro, Ejecutivo, Corporativo, Natural y Atardecer
- **Adaptación Inteligente**: Sugerencias automáticas según la hora del día
- **Persistencia**: Recuerda tu tema favorito
- **Responsive**: Funciona perfectamente en todos los dispositivos

### 📊 Dashboard Ejecutivo
- **Gráficos Interactivos**: Visualización de datos con Chart.js
- **Estadísticas en Tiempo Real**: Métricas clave del sistema
- **Tendencias**: Análisis de tickets por mes, departamento y estado
- **Usuarios Activos**: Top 5 de usuarios más comprometidos

### 👥 Sistema de Roles y Jerarquías
- **3 Roles**: Administrador, Usuario y Cliente
- **4 Niveles Jerárquicos**: Jefe, Asistente, Sub-Gerente y Gerente de Tienda
- **Permisos Granulares**: Control de acceso basado en jerarquía
- **Visibilidad Controlada**: Los usuarios solo ven tickets según su nivel

### 🎯 Gestión de Tickets
- **Estados**: Pendiente, Abierto, En Proceso y Cerrado
- **Categorías**: Organizadas por departamento
- **Asignación**: Sistema de asignación de tickets a usuarios
- **Historial**: Registro completo de todas las acciones
- **Archivos Adjuntos**: Soporte para imágenes y documentos
- **Escalamiento**: Sistema de escalamiento con aprobaciones por email

## 🚀 Instalación

### Requisitos Previos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- XAMPP, WAMP o similar (para desarrollo local)

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/JDiegoNFS/Proyecto-tickets.git
cd Proyecto-tickets
```

2. **Configurar la base de datos**
```bash
# Crear la base de datos en MySQL
mysql -u root -p

# Importar el esquema
mysql -u root -p < sistema_tickets.sql
```

3. **Configurar la conexión**
Editar `Tickect/includes/db.php` con tus credenciales:
```php
$host = 'localhost';
$dbname = 'sistema_tickets';
$user = 'root';
$pass = '';
```

4. **Iniciar el servidor**
```bash
# Si usas XAMPP, coloca el proyecto en htdocs
# Accede a: http://localhost/Proyecto-tickets/Tickect/
```

## 👤 Usuarios de Prueba

| Usuario | Contraseña | Rol | Descripción |
|---------|------------|-----|-------------|
| `admin` | `123` | Administrador | Acceso completo al sistema |
| `Jorge` | `123` | Usuario | Departamento Comercial |
| `brus` | `123` | Usuario | Departamento Comercial |
| `jefe_norte_1` | `123` | Cliente | Jefe de Tienda Norte |
| `gerente_norte_1` | `123` | Cliente | Gerente de Tienda Norte |

## 📸 Capturas de Pantalla

### Login con Sistema de Temas
El sistema de login se adapta a cada tema manteniendo siempre la legibilidad perfecta.

### Dashboard con Gráficos
Dashboard ejecutivo con estadísticas visuales, gráficos interactivos y métricas en tiempo real.

### Gestión de Tickets
Interfaz intuitiva para crear, asignar y gestionar tickets con sistema de estados y categorías.

## 🎨 Temas Disponibles

1. **☀️ Claro** - Tema clásico y limpio
2. **🌙 Oscuro** - Perfecto para trabajar de noche
3. **💼 Ejecutivo** - Elegante y profesional
4. **🏢 Corporativo** - Azul empresarial
5. **🌿 Natural** - Verde relajante
6. **🌅 Atardecer** - Cálido y acogedor

## 🛠️ Tecnologías Utilizadas

- **Backend**: PHP 8.x
- **Base de Datos**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Gráficos**: Chart.js 4.x
- **Iconos**: Font Awesome 6.5
- **Arquitectura**: MVC simplificado

## 📁 Estructura del Proyecto

```
Proyecto-tickets/
├── Tickect/
│   ├── admin/              # Módulos de administración
│   ├── cliente/            # Módulos de cliente
│   ├── usuario/            # Módulos de usuario
│   ├── css/                # Estilos CSS
│   │   ├── themes.css      # Sistema de temas
│   │   └── ...
│   ├── js/                 # JavaScript
│   │   ├── theme-manager.js # Gestor de temas
│   │   └── ...
│   ├── includes/           # Archivos de configuración
│   │   ├── db.php          # Conexión a BD
│   │   ├── auth.php        # Autenticación
│   │   └── ...
│   ├── index.php           # Login
│   └── dashboard.php       # Dashboard principal
├── sistema_tickets.sql     # Base de datos
└── README.md              # Este archivo
```

## 🔧 Configuración Avanzada

### Sistema de Temas
El sistema de temas se puede personalizar editando `Tickect/css/themes.css`. Cada tema define sus propias variables CSS:

```css
[data-theme="custom"] {
    --primary-color: #your-color;
    --bg-gradient: linear-gradient(...);
    /* ... más variables */
}
```

### Atajos de Teclado
- `Ctrl + Shift + T` - Abrir selector de temas
- `Esc` - Cerrar selector de temas

## 📊 Características del Dashboard

- **Estadísticas en Tiempo Real**: Tickets activos, usuarios, departamentos
- **Gráfico de Dona**: Distribución de tickets por estado
- **Gráfico de Línea**: Tendencia mensual de tickets
- **Gráfico de Barras**: Tickets por departamento
- **Top Usuarios**: Los 5 usuarios más activos

## 🔐 Seguridad

⚠️ **IMPORTANTE**: Este proyecto está configurado para desarrollo local. Para producción:

- Implementar hash de contraseñas con `password_hash()`
- Agregar protección CSRF
- Validar y sanitizar todos los inputs
- Usar prepared statements (ya implementado)
- Configurar HTTPS
- Actualizar credenciales de base de datos

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📝 Licencia

Este proyecto es de código abierto y está disponible bajo la licencia MIT.

## 👨‍💻 Autor

**Diego** - [JDiegoNFS](https://github.com/JDiegoNFS)

## 🙏 Agradecimientos

- Chart.js por los gráficos interactivos
- Font Awesome por los iconos
- La comunidad de PHP y MySQL

## 📞 Soporte

Si tienes preguntas o problemas:
- Abre un [Issue](https://github.com/JDiegoNFS/Proyecto-tickets/issues)
- Contacta al autor

---

⭐ Si este proyecto te fue útil, considera darle una estrella en GitHub!

**Desarrollado con ❤️ para la comunidad**
