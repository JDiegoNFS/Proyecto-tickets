# 🚀 Guía de Instalación Rápida

## Instalación en 5 Minutos

### Opción 1: XAMPP (Recomendado para Windows)

1. **Descargar XAMPP**
   - Ve a https://www.apachefriends.org/
   - Descarga e instala XAMPP

2. **Clonar el proyecto**
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/JDiegoNFS/Proyecto-tickets.git
   ```

3. **Iniciar servicios**
   - Abre XAMPP Control Panel
   - Inicia Apache y MySQL

4. **Crear base de datos**
   - Abre http://localhost/phpmyadmin
   - Crea una base de datos llamada `sistema_tickets`
   - Importa el archivo `sistema_tickets.sql`

5. **Acceder al sistema**
   - Ve a http://localhost/Proyecto-tickets/Tickect/
   - Usuario: `admin` / Contraseña: `123`

### Opción 2: WAMP (Windows)

1. **Descargar WAMP**
   - Ve a https://www.wampserver.com/
   - Descarga e instala WAMP

2. **Clonar el proyecto**
   ```bash
   cd C:\wamp64\www
   git clone https://github.com/JDiegoNFS/Proyecto-tickets.git
   ```

3. **Seguir pasos 3-5** de la opción XAMPP

### Opción 3: LAMP (Linux)

1. **Instalar LAMP**
   ```bash
   sudo apt update
   sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql
   ```

2. **Clonar el proyecto**
   ```bash
   cd /var/www/html
   sudo git clone https://github.com/JDiegoNFS/Proyecto-tickets.git
   sudo chown -R www-data:www-data Proyecto-tickets
   ```

3. **Crear base de datos**
   ```bash
   sudo mysql -u root -p
   CREATE DATABASE sistema_tickets;
   exit;
   sudo mysql -u root -p sistema_tickets < Proyecto-tickets/sistema_tickets.sql
   ```

4. **Configurar permisos**
   ```bash
   sudo chmod -R 755 Proyecto-tickets
   sudo chmod -R 777 Proyecto-tickets/Tickect/uploads
   ```

5. **Acceder**
   - http://localhost/Proyecto-tickets/Tickect/

### Opción 4: MAMP (Mac)

1. **Descargar MAMP**
   - Ve a https://www.mamp.info/
   - Descarga e instala MAMP

2. **Clonar el proyecto**
   ```bash
   cd /Applications/MAMP/htdocs
   git clone https://github.com/JDiegoNFS/Proyecto-tickets.git
   ```

3. **Seguir pasos similares** a XAMPP

## Configuración de Base de Datos

Si necesitas cambiar las credenciales, edita `Tickect/includes/db.php`:

```php
$host = 'localhost';
$dbname = 'sistema_tickets';
$user = 'tu_usuario';
$pass = 'tu_contraseña';
```

## Solución de Problemas Comunes

### Error: "No se puede conectar a la base de datos"
- Verifica que MySQL esté corriendo
- Revisa las credenciales en `db.php`
- Asegúrate de haber importado el SQL

### Error: "No se pueden subir archivos"
```bash
# Linux/Mac
sudo chmod -R 777 Tickect/uploads

# Windows
# Click derecho en la carpeta uploads > Propiedades > Seguridad
# Dar permisos completos
```

### Error: "Página en blanco"
- Activa la visualización de errores en PHP
- Revisa los logs de Apache
- Verifica que todas las extensiones PHP estén activas

### Puerto 80 ocupado
- Cambia el puerto en la configuración de Apache
- O detén el servicio que está usando el puerto 80

## Verificación de Instalación

1. **Accede al login**: http://localhost/Proyecto-tickets/Tickect/
2. **Inicia sesión**: admin / 123
3. **Verifica el dashboard**: Deberías ver gráficos y estadísticas
4. **Cambia de tema**: Click en el botón 🎨

## Usuarios de Prueba

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| admin | 123 | Administrador |
| Jorge | 123 | Usuario |
| jefe_norte_1 | 123 | Cliente |

## Requisitos del Sistema

- **PHP**: 7.4 o superior
- **MySQL**: 5.7 o superior
- **Apache**: 2.4 o superior
- **Navegador**: Chrome, Firefox, Edge (últimas versiones)

## Extensiones PHP Requeridas

- mysqli o PDO
- json
- session
- fileinfo (para subida de archivos)

## Configuración Recomendada

En `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

## Siguiente Paso

Una vez instalado, lee el [README.md](README.md) para conocer todas las funcionalidades.

## Soporte

¿Problemas con la instalación?
- Abre un [Issue](https://github.com/JDiegoNFS/Proyecto-tickets/issues)
- Incluye detalles de tu sistema operativo y versiones

---

**¡Listo para usar! 🎉**
