# 🔧 Manual de Operaciones - Sistema de Gestión de Tickets

## Introducción

Este manual está dirigido a administradores de sistemas y equipos de soporte técnico responsables de la operación, mantenimiento y configuración del Sistema de Gestión de Tickets.

---

## 📑 Tabla de Contenidos

1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Instalación y Configuración](#instalación-y-configuración)
3. [Gestión de Usuarios](#gestión-de-usuarios)
4. [Configuración de Departamentos](#configuración-de-departamentos)
5. [Gestión de Categorías](#gestión-de-categorías)
6. [Sistema de Permisos](#sistema-de-permisos)
7. [Monitoreo y Reportes](#monitoreo-y-reportes)
8. [Mantenimiento](#mantenimiento)
9. [Respaldos y Recuperación](#respaldos-y-recuperación)
10. [Solución de Problemas Técnicos](#solución-de-problemas-técnicos)
11. [Seguridad](#seguridad)
12. [Optimización](#optimización)

---

## Arquitectura del Sistema

### Stack Tecnológico

**Backend:**
- PHP 7.4+ (recomendado 8.0+)
- MySQL 5.7+ / MariaDB 10.3+
- Apache 2.4+ / Nginx 1.18+

**Frontend:**
- HTML5, CSS3
- JavaScript ES6+
- Chart.js 4.x (visualización de datos)
- Font Awesome 6.5 (iconografía)

**Librerías PHP:**
- PDO (acceso a base de datos)
- PHPMailer (envío de correos)
- Session management (autenticación)

### Estructura de Directorios

```
Tickect/
├── admin/                  # Módulos administrativos
│   ├── crear_usuario.php
│   ├── crear_departamento.php
│   ├── crear_categoria.php
│   ├── gestionar_destinatarios.php
│   └── reporte.php
├── cliente/                # Módulos de cliente
│   ├── crear_ticket.php
│   ├── ver_tickets.php
│   └── responder_ticket.php
├── usuario/                # Módulos de usuario/soporte
│   ├── ver_tickets.php
│   ├── tomar_ticket.php
│   └── responder_ticket.php
├── css/                    # Estilos
│   ├── themes.css
│   ├── global-theme-styles.css
│   └── [otros estilos]
├── js/                     # JavaScript
│   ├── theme-manager.js
│   └── [otros scripts]
├── includes/               # Configuración y funciones
│   ├── db.php             # Conexión BD
│   ├── auth.php           # Autenticación
│   ├── config_correos.php # Configuración email
│   └── [otras funciones]
├── uploads/                # Archivos subidos
│   ├── tickets/
│   └── pasted_images/
├── index.php               # Login
└── dashboard.php           # Dashboard principal
```


### Base de Datos

**Tablas Principales:**

```sql
usuarios                    # Usuarios del sistema
├── id_usuario
├── nombre_usuario
├── contrasena
├── rol (admin/usuario/cliente)
├── jerarquia
└── departamento_id

departamentos              # Departamentos organizacionales
├── id_departamento
├── nombre_departamento
└── descripcion

categorias                 # Categorías de tickets
├── id_categoria
├── nombre_categoria
├── departamento_id
└── activo

tickets                    # Tickets principales
├── id_ticket
├── asunto
├── descripcion
├── estado (pendiente/abierto/en_proceso/cerrado)
├── prioridad (baja/media/alta)
├── usuario_creador_id
├── usuario_asignado_id
├── categoria_id
├── fecha_creacion
└── fecha_actualizacion

respuestas                 # Respuestas a tickets
├── id_respuesta
├── ticket_id
├── usuario_id
├── mensaje
└── fecha_respuesta

archivos_adjuntos         # Archivos subidos
├── id_archivo
├── ticket_id
├── nombre_archivo
├── ruta_archivo
└── fecha_subida

escalamientos             # Sistema de escalamiento
├── id_escalamiento
├── ticket_id
├── usuario_solicita_id
├── usuario_aprueba_id
├── estado
└── justificacion
```

---

## Instalación y Configuración

### Requisitos del Servidor

**Mínimos:**
- CPU: 2 cores
- RAM: 2 GB
- Disco: 10 GB
- PHP 7.4+
- MySQL 5.7+

**Recomendados:**
- CPU: 4 cores
- RAM: 4 GB
- Disco: 20 GB SSD
- PHP 8.0+
- MySQL 8.0+

### Instalación Paso a Paso

#### 1. Preparar el Servidor

**En Linux (Ubuntu/Debian):**
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar LAMP stack
sudo apt install apache2 mysql-server php php-mysql php-mbstring php-xml -y

# Habilitar módulos Apache
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**En Windows (XAMPP):**
```bash
# Descargar XAMPP desde https://www.apachefriends.org/
# Instalar en C:\xampp
# Iniciar Apache y MySQL desde el panel de control
```

#### 2. Configurar Base de Datos

```bash
# Acceder a MySQL
mysql -u root -p

# Crear base de datos
CREATE DATABASE sistema_tickets CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Crear usuario dedicado (recomendado para producción)
CREATE USER 'tickets_user'@'localhost' IDENTIFIED BY 'password_seguro';
GRANT ALL PRIVILEGES ON sistema_tickets.* TO 'tickets_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Importar esquema
mysql -u root -p sistema_tickets < sistema_tickets.sql
```

#### 3. Clonar/Copiar Proyecto

```bash
# Linux
cd /var/www/html
sudo git clone https://github.com/JDiegoNFS/Proyecto-tickets.git
sudo chown -R www-data:www-data Proyecto-tickets

# Windows (XAMPP)
cd C:\xampp\htdocs
git clone https://github.com/JDiegoNFS/Proyecto-tickets.git
```

#### 4. Configurar Conexión a Base de Datos

Editar `Tickect/includes/db.php`:

```php
<?php
// Configuración de producción
$host = 'localhost';
$dbname = 'sistema_tickets';
$user = 'tickets_user';
$pass = 'password_seguro';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    die("Error de conexión a la base de datos");
}
?>
```

#### 5. Configurar Permisos de Archivos

```bash
# Linux
sudo chmod -R 755 /var/www/html/Proyecto-tickets
sudo chmod -R 777 /var/www/html/Proyecto-tickets/Tickect/uploads
sudo chown -R www-data:www-data /var/www/html/Proyecto-tickets

# Verificar permisos
ls -la /var/www/html/Proyecto-tickets/Tickect/uploads
```

#### 6. Configurar PHP

Editar `php.ini`:

```ini
# Límites de subida de archivos
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M

# Zona horaria
date.timezone = America/Mexico_City

# Mostrar errores (solo desarrollo)
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

#### 7. Configurar Apache Virtual Host (Opcional)

Crear `/etc/apache2/sites-available/tickets.conf`:

```apache
<VirtualHost *:80>
    ServerName tickets.tuempresa.com
    DocumentRoot /var/www/html/Proyecto-tickets/Tickect
    
    <Directory /var/www/html/Proyecto-tickets/Tickect>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/tickets_error.log
    CustomLog ${APACHE_LOG_DIR}/tickets_access.log combined
</VirtualHost>
```

Habilitar sitio:
```bash
sudo a2ensite tickets.conf
sudo systemctl reload apache2
```

#### 8. Configurar Correo Electrónico

Editar `Tickect/includes/config_correos.php`:

```php
<?php
// Configuración SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tickets@tuempresa.com');
define('SMTP_PASS', 'password_aplicacion');
define('SMTP_FROM', 'tickets@tuempresa.com');
define('SMTP_FROM_NAME', 'Sistema de Tickets');

// Para Gmail, habilitar "Acceso de aplicaciones menos seguras"
// O usar contraseña de aplicación con 2FA
?>
```

#### 9. Verificar Instalación

Acceder a: `http://localhost/Proyecto-tickets/Tickect/`

Credenciales por defecto:
- Usuario: `admin`
- Contraseña: `123`

**⚠️ IMPORTANTE:** Cambiar contraseña inmediatamente después del primer acceso.


---

## Gestión de Usuarios

### Crear Usuario

**Interfaz Web:**
1. Login como administrador
2. Ir a **Admin → Crear Usuario**
3. Llenar formulario:
   - Nombre de usuario (único)
   - Contraseña
   - Rol (Admin/Usuario/Cliente)
   - Jerarquía (si aplica)
   - Departamento
4. Guardar

**Directamente en Base de Datos:**

```sql
-- Crear usuario básico
INSERT INTO usuarios (nombre_usuario, contrasena, rol, departamento_id) 
VALUES ('nuevo_usuario', '123', 'usuario', 1);

-- Crear usuario con jerarquía
INSERT INTO usuarios (nombre_usuario, contrasena, rol, jerarquia, departamento_id) 
VALUES ('gerente_norte', '123', 'cliente', 'Gerente', 2);
```

**⚠️ Nota de Seguridad:** En producción, usar `password_hash()` para contraseñas:

```php
$password_hash = password_hash('contraseña', PASSWORD_DEFAULT);
```

### Roles y Permisos

**Administrador:**
- Acceso completo al sistema
- Crear/editar/eliminar usuarios
- Configurar departamentos y categorías
- Ver todos los tickets
- Generar reportes globales
- Gestionar configuración del sistema

**Usuario (Personal de Soporte):**
- Ver tickets de su departamento
- Tomar y asignar tickets
- Responder tickets
- Cambiar estado de tickets
- Ver reportes de su departamento
- Escalar tickets

**Cliente:**
- Crear tickets
- Ver solo sus propios tickets
- Responder a sus tickets
- Ver historial de sus tickets
- Visibilidad según jerarquía

### Jerarquías de Cliente

```
Gerente de Tienda (nivel más alto)
    ↓
Sub-Gerente
    ↓
Asistente
    ↓
Jefe (nivel más bajo)
```

**Reglas de Visibilidad:**
- Gerente: Ve tickets de toda su tienda
- Sub-Gerente: Ve tickets de asistentes y jefes
- Asistente: Ve tickets de jefes
- Jefe: Solo ve sus propios tickets

### Modificar Usuario

```sql
-- Cambiar contraseña
UPDATE usuarios SET contrasena = 'nueva_contraseña' WHERE id_usuario = 5;

-- Cambiar rol
UPDATE usuarios SET rol = 'admin' WHERE id_usuario = 5;

-- Cambiar departamento
UPDATE usuarios SET departamento_id = 3 WHERE id_usuario = 5;

-- Desactivar usuario (agregar campo 'activo' si no existe)
ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) DEFAULT 1;
UPDATE usuarios SET activo = 0 WHERE id_usuario = 5;
```

### Eliminar Usuario

**⚠️ Precaución:** Eliminar usuarios puede romper referencias en tickets.

**Opción 1: Desactivar (Recomendado)**
```sql
UPDATE usuarios SET activo = 0 WHERE id_usuario = 5;
```

**Opción 2: Eliminar (No recomendado)**
```sql
-- Primero reasignar tickets
UPDATE tickets SET usuario_asignado_id = NULL WHERE usuario_asignado_id = 5;
UPDATE tickets SET usuario_creador_id = 1 WHERE usuario_creador_id = 5;

-- Luego eliminar
DELETE FROM usuarios WHERE id_usuario = 5;
```

### Listar Usuarios

```sql
-- Todos los usuarios
SELECT id_usuario, nombre_usuario, rol, departamento_id, activo 
FROM usuarios 
ORDER BY rol, nombre_usuario;

-- Usuarios por rol
SELECT * FROM usuarios WHERE rol = 'usuario';

-- Usuarios activos
SELECT * FROM usuarios WHERE activo = 1;

-- Usuarios con estadísticas
SELECT 
    u.nombre_usuario,
    u.rol,
    COUNT(t.id_ticket) as total_tickets,
    SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) as tickets_cerrados
FROM usuarios u
LEFT JOIN tickets t ON u.id_usuario = t.usuario_asignado_id
GROUP BY u.id_usuario;
```

---

## Configuración de Departamentos

### Crear Departamento

**Interfaz Web:**
1. Admin → Crear Departamento
2. Nombre del departamento
3. Descripción (opcional)
4. Guardar

**SQL:**
```sql
INSERT INTO departamentos (nombre_departamento, descripcion) 
VALUES ('Sistemas', 'Departamento de TI y soporte técnico');
```

### Departamentos Comunes

```sql
-- Departamentos típicos
INSERT INTO departamentos (nombre_departamento, descripcion) VALUES
('Sistemas', 'Tecnología e infraestructura'),
('Recursos Humanos', 'Gestión de personal'),
('Mantenimiento', 'Mantenimiento de instalaciones'),
('Compras', 'Adquisiciones y proveedores'),
('Comercial', 'Ventas y atención al cliente'),
('Finanzas', 'Contabilidad y finanzas'),
('Operaciones', 'Logística y operaciones');
```

### Modificar Departamento

```sql
-- Cambiar nombre
UPDATE departamentos 
SET nombre_departamento = 'TI y Sistemas' 
WHERE id_departamento = 1;

-- Agregar descripción
UPDATE departamentos 
SET descripcion = 'Soporte técnico y desarrollo' 
WHERE id_departamento = 1;
```

### Eliminar Departamento

**⚠️ Precaución:** Verificar que no tenga tickets o usuarios asignados.

```sql
-- Verificar dependencias
SELECT COUNT(*) FROM tickets WHERE categoria_id IN 
    (SELECT id_categoria FROM categorias WHERE departamento_id = 1);
SELECT COUNT(*) FROM usuarios WHERE departamento_id = 1;

-- Si no hay dependencias, eliminar
DELETE FROM categorias WHERE departamento_id = 1;
DELETE FROM departamentos WHERE id_departamento = 1;
```

### Listar Departamentos con Estadísticas

```sql
SELECT 
    d.id_departamento,
    d.nombre_departamento,
    COUNT(DISTINCT c.id_categoria) as total_categorias,
    COUNT(DISTINCT u.id_usuario) as total_usuarios,
    COUNT(t.id_ticket) as total_tickets
FROM departamentos d
LEFT JOIN categorias c ON d.id_departamento = c.departamento_id
LEFT JOIN usuarios u ON d.id_departamento = u.departamento_id
LEFT JOIN tickets t ON c.id_categoria = t.categoria_id
GROUP BY d.id_departamento;
```

---

## Gestión de Categorías

### Crear Categoría

**Interfaz Web:**
1. Admin → Crear Categoría
2. Nombre de categoría
3. Seleccionar departamento
4. Guardar

**SQL:**
```sql
INSERT INTO categorias (nombre_categoria, departamento_id, activo) 
VALUES ('Soporte Técnico', 1, 1);
```

### Categorías por Departamento

```sql
-- Sistemas
INSERT INTO categorias (nombre_categoria, departamento_id) VALUES
('Soporte Técnico', 1),
('Accesos y Permisos', 1),
('Hardware', 1),
('Software', 1),
('Redes', 1);

-- Recursos Humanos
INSERT INTO categorias (nombre_categoria, departamento_id) VALUES
('Vacaciones', 2),
('Incapacidades', 2),
('Nómina', 2),
('Capacitación', 2);

-- Mantenimiento
INSERT INTO categorias (nombre_categoria, departamento_id) VALUES
('Instalaciones', 3),
('Equipos', 3),
('Limpieza', 3);
```

### Activar/Desactivar Categoría

```sql
-- Desactivar (ocultar pero mantener histórico)
UPDATE categorias SET activo = 0 WHERE id_categoria = 5;

-- Reactivar
UPDATE categorias SET activo = 1 WHERE id_categoria = 5;
```

### Listar Categorías

```sql
-- Todas las categorías con su departamento
SELECT 
    c.id_categoria,
    c.nombre_categoria,
    d.nombre_departamento,
    c.activo,
    COUNT(t.id_ticket) as total_tickets
FROM categorias c
JOIN departamentos d ON c.departamento_id = d.id_departamento
LEFT JOIN tickets t ON c.id_categoria = t.categoria_id
GROUP BY c.id_categoria
ORDER BY d.nombre_departamento, c.nombre_categoria;

-- Solo categorías activas
SELECT * FROM categorias WHERE activo = 1;
```

---

## Sistema de Permisos

### Matriz de Permisos

| Acción | Admin | Usuario | Cliente |
|--------|-------|---------|---------|
| Ver todos los tickets | ✅ | ❌ | ❌ |
| Ver tickets del departamento | ✅ | ✅ | ❌ |
| Ver propios tickets | ✅ | ✅ | ✅ |
| Crear ticket | ✅ | ✅ | ✅ |
| Asignar ticket | ✅ | ✅ | ❌ |
| Cambiar estado | ✅ | ✅ | ❌ |
| Cerrar ticket | ✅ | ✅ | ❌ |
| Escalar ticket | ✅ | ✅ | ✅ |
| Crear usuario | ✅ | ❌ | ❌ |
| Crear departamento | ✅ | ❌ | ❌ |
| Ver reportes globales | ✅ | ❌ | ❌ |
| Ver reportes departamento | ✅ | ✅ | ❌ |

### Implementación de Permisos

En cada archivo PHP, verificar permisos:

```php
<?php
session_start();
require_once '../includes/auth.php';

// Verificar que esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

// Verificar rol específico
if ($_SESSION['rol'] !== 'admin') {
    die('Acceso denegado');
}

// Verificar múltiples roles
if (!in_array($_SESSION['rol'], ['admin', 'usuario'])) {
    die('Acceso denegado');
}
?>
```

### Permisos por Jerarquía

```php
<?php
// Función para verificar si puede ver un ticket
function puedeVerTicket($usuario_id, $ticket_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT t.*, u.jerarquia as creador_jerarquia
        FROM tickets t
        JOIN usuarios u ON t.usuario_creador_id = u.id_usuario
        WHERE t.id_ticket = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    // Admin ve todo
    if ($_SESSION['rol'] === 'admin') return true;
    
    // Usuario ve tickets de su departamento
    if ($_SESSION['rol'] === 'usuario') {
        // Verificar departamento
        return true; // Implementar lógica
    }
    
    // Cliente ve según jerarquía
    if ($_SESSION['rol'] === 'cliente') {
        $jerarquias = ['Jefe' => 1, 'Asistente' => 2, 'Sub-Gerente' => 3, 'Gerente' => 4];
        $nivel_usuario = $jerarquias[$_SESSION['jerarquia']] ?? 0;
        $nivel_creador = $jerarquias[$ticket['creador_jerarquia']] ?? 0;
        
        return $nivel_usuario >= $nivel_creador;
    }
    
    return false;
}
?>
```


---

## Monitoreo y Reportes

### Dashboard Administrativo

El dashboard muestra métricas clave en tiempo real:

**Estadísticas Principales:**
- Total de tickets activos
- Tickets por estado (pendiente, abierto, en proceso, cerrado)
- Tickets por prioridad
- Usuarios activos
- Departamentos con más carga

**Gráficos:**
- Distribución de tickets por estado (gráfico de dona)
- Tendencia mensual (gráfico de línea)
- Tickets por departamento (gráfico de barras)
- Top 5 usuarios más activos

### Consultas SQL Útiles

#### Tickets Pendientes por Departamento

```sql
SELECT 
    d.nombre_departamento,
    COUNT(t.id_ticket) as tickets_pendientes,
    AVG(TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW())) as horas_promedio_espera
FROM tickets t
JOIN categorias c ON t.categoria_id = c.id_categoria
JOIN departamentos d ON c.departamento_id = d.id_departamento
WHERE t.estado = 'pendiente'
GROUP BY d.id_departamento
ORDER BY tickets_pendientes DESC;
```

#### Tiempo Promedio de Resolución

```sql
SELECT 
    d.nombre_departamento,
    AVG(TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_actualizacion)) as horas_promedio,
    COUNT(t.id_ticket) as total_cerrados
FROM tickets t
JOIN categorias c ON t.categoria_id = c.id_categoria
JOIN departamentos d ON c.departamento_id = d.id_departamento
WHERE t.estado = 'cerrado'
    AND t.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY d.id_departamento;
```

#### Usuarios Más Activos

```sql
SELECT 
    u.nombre_usuario,
    COUNT(t.id_ticket) as tickets_atendidos,
    SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) as tickets_cerrados,
    ROUND(SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) * 100.0 / COUNT(t.id_ticket), 2) as tasa_cierre
FROM usuarios u
JOIN tickets t ON u.id_usuario = t.usuario_asignado_id
WHERE t.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY u.id_usuario
ORDER BY tickets_atendidos DESC
LIMIT 10;
```

#### Tickets por Prioridad y Estado

```sql
SELECT 
    prioridad,
    estado,
    COUNT(*) as total,
    AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, 
        CASE WHEN estado = 'cerrado' THEN fecha_actualizacion ELSE NOW() END)) as horas_promedio
FROM tickets
WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY prioridad, estado
ORDER BY 
    FIELD(prioridad, 'alta', 'media', 'baja'),
    FIELD(estado, 'pendiente', 'abierto', 'en_proceso', 'cerrado');
```

#### Tickets Sin Asignar

```sql
SELECT 
    t.id_ticket,
    t.asunto,
    t.prioridad,
    d.nombre_departamento,
    TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()) as horas_sin_asignar
FROM tickets t
JOIN categorias c ON t.categoria_id = c.id_categoria
JOIN departamentos d ON c.departamento_id = d.id_departamento
WHERE t.usuario_asignado_id IS NULL
    AND t.estado = 'pendiente'
ORDER BY t.prioridad DESC, t.fecha_creacion ASC;
```

#### Escalamientos Pendientes

```sql
SELECT 
    e.id_escalamiento,
    t.id_ticket,
    t.asunto,
    u_solicita.nombre_usuario as solicitante,
    u_aprueba.nombre_usuario as aprobador,
    e.estado,
    e.justificacion,
    TIMESTAMPDIFF(HOUR, e.fecha_solicitud, NOW()) as horas_pendiente
FROM escalamientos e
JOIN tickets t ON e.ticket_id = t.id_ticket
JOIN usuarios u_solicita ON e.usuario_solicita_id = u_solicita.id_usuario
LEFT JOIN usuarios u_aprueba ON e.usuario_aprueba_id = u_aprueba.id_usuario
WHERE e.estado = 'pendiente'
ORDER BY e.fecha_solicitud ASC;
```

### Generar Reportes

#### Reporte Mensual por Departamento

```sql
SELECT 
    DATE_FORMAT(t.fecha_creacion, '%Y-%m') as mes,
    d.nombre_departamento,
    COUNT(t.id_ticket) as total_tickets,
    SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) as cerrados,
    SUM(CASE WHEN t.prioridad = 'alta' THEN 1 ELSE 0 END) as alta_prioridad,
    AVG(TIMESTAMPDIFF(HOUR, t.fecha_creacion, 
        CASE WHEN t.estado = 'cerrado' THEN t.fecha_actualizacion ELSE NOW() END)) as horas_promedio
FROM tickets t
JOIN categorias c ON t.categoria_id = c.id_categoria
JOIN departamentos d ON c.departamento_id = d.id_departamento
WHERE t.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY mes, d.id_departamento
ORDER BY mes DESC, d.nombre_departamento;
```

#### Exportar a CSV

```bash
# Desde línea de comandos
mysql -u root -p -D sistema_tickets -e "
SELECT 
    t.id_ticket,
    t.asunto,
    t.estado,
    t.prioridad,
    d.nombre_departamento,
    u.nombre_usuario as asignado,
    t.fecha_creacion
FROM tickets t
JOIN categorias c ON t.categoria_id = c.id_categoria
JOIN departamentos d ON c.departamento_id = d.id_departamento
LEFT JOIN usuarios u ON t.usuario_asignado_id = u.id_usuario
WHERE t.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
" | sed 's/\t/,/g' > reporte_tickets.csv
```

### Alertas Automáticas

#### Tickets Sin Atender (Cron Job)

Crear script `check_tickets_pendientes.php`:

```php
<?php
require_once 'includes/db.php';
require_once 'includes/config_correos.php';

// Buscar tickets pendientes por más de 2 horas
$stmt = $pdo->query("
    SELECT t.*, d.nombre_departamento
    FROM tickets t
    JOIN categorias c ON t.categoria_id = c.id_categoria
    JOIN departamentos d ON c.departamento_id = d.id_departamento
    WHERE t.estado = 'pendiente'
        AND t.usuario_asignado_id IS NULL
        AND TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()) > 2
");

$tickets = $stmt->fetchAll();

if (count($tickets) > 0) {
    // Enviar email a administradores
    $mensaje = "Hay " . count($tickets) . " tickets sin asignar por más de 2 horas:\n\n";
    foreach ($tickets as $ticket) {
        $mensaje .= "Ticket #{$ticket['id_ticket']}: {$ticket['asunto']} - {$ticket['nombre_departamento']}\n";
    }
    
    // Enviar email (implementar con PHPMailer)
    enviarAlerta('admin@empresa.com', 'Tickets sin asignar', $mensaje);
}
?>
```

Configurar cron (Linux):
```bash
# Ejecutar cada hora
0 * * * * php /var/www/html/Proyecto-tickets/Tickect/check_tickets_pendientes.php
```

---

## Mantenimiento

### Tareas Diarias

**Verificar Estado del Sistema:**
```bash
# Verificar servicios
sudo systemctl status apache2
sudo systemctl status mysql

# Verificar logs
tail -f /var/log/apache2/error.log
tail -f /var/log/mysql/error.log
```

**Monitorear Espacio en Disco:**
```bash
# Ver uso de disco
df -h

# Ver tamaño de uploads
du -sh /var/www/html/Proyecto-tickets/Tickect/uploads/

# Ver tamaño de base de datos
mysql -u root -p -e "
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'sistema_tickets'
GROUP BY table_schema;
"
```

### Tareas Semanales

**Limpiar Archivos Temporales:**
```bash
# Limpiar archivos antiguos (más de 90 días)
find /var/www/html/Proyecto-tickets/Tickect/uploads/pasted_images/ -type f -mtime +90 -delete

# Limpiar logs antiguos
find /var/log/apache2/ -name "*.log" -mtime +30 -delete
```

**Optimizar Base de Datos:**
```sql
-- Optimizar tablas
OPTIMIZE TABLE tickets;
OPTIMIZE TABLE respuestas;
OPTIMIZE TABLE archivos_adjuntos;

-- Analizar tablas
ANALYZE TABLE tickets;
ANALYZE TABLE usuarios;

-- Verificar integridad
CHECK TABLE tickets;
```

### Tareas Mensuales

**Archivar Tickets Antiguos:**
```sql
-- Crear tabla de archivo si no existe
CREATE TABLE IF NOT EXISTS tickets_archivo LIKE tickets;

-- Mover tickets cerrados de más de 6 meses
INSERT INTO tickets_archivo
SELECT * FROM tickets
WHERE estado = 'cerrado'
    AND fecha_actualizacion < DATE_SUB(NOW(), INTERVAL 6 MONTH);

-- Verificar antes de eliminar
SELECT COUNT(*) FROM tickets_archivo;

-- Eliminar de tabla principal (opcional)
-- DELETE FROM tickets WHERE id_ticket IN (SELECT id_ticket FROM tickets_archivo);
```

**Actualizar Estadísticas:**
```sql
-- Actualizar estadísticas de MySQL
ANALYZE TABLE tickets;
ANALYZE TABLE usuarios;
ANALYZE TABLE departamentos;
```

### Limpieza de Datos

**Eliminar Respuestas Huérfanas:**
```sql
-- Verificar respuestas sin ticket
SELECT COUNT(*) FROM respuestas r
LEFT JOIN tickets t ON r.ticket_id = t.id_ticket
WHERE t.id_ticket IS NULL;

-- Eliminar si es necesario
DELETE r FROM respuestas r
LEFT JOIN tickets t ON r.ticket_id = t.id_ticket
WHERE t.id_ticket IS NULL;
```

**Eliminar Archivos Sin Referencia:**
```bash
# Script para limpiar archivos huérfanos
php -r "
require 'includes/db.php';

\$archivos_bd = \$pdo->query('SELECT ruta_archivo FROM archivos_adjuntos')->fetchAll(PDO::FETCH_COLUMN);
\$archivos_disco = glob('uploads/tickets/*');

foreach (\$archivos_disco as \$archivo) {
    if (!in_array(\$archivo, \$archivos_bd)) {
        echo 'Eliminando: ' . \$archivo . PHP_EOL;
        unlink(\$archivo);
    }
}
"
```

### Actualización del Sistema

**Antes de Actualizar:**
1. Hacer respaldo completo
2. Probar en ambiente de desarrollo
3. Notificar a usuarios
4. Programar ventana de mantenimiento

**Proceso de Actualización:**
```bash
# Hacer respaldo
./backup.sh

# Descargar nueva versión
cd /var/www/html/Proyecto-tickets
git fetch origin
git checkout tags/v2.0.0

# Aplicar migraciones de BD si existen
mysql -u root -p sistema_tickets < migrations/v2.0.0.sql

# Limpiar caché
rm -rf /tmp/php_cache/*

# Reiniciar servicios
sudo systemctl restart apache2

# Verificar
curl -I http://localhost/Proyecto-tickets/Tickect/
```


---

## Respaldos y Recuperación

### Estrategia de Respaldos

**Tipos de Respaldo:**
- **Completo**: Base de datos + archivos (semanal)
- **Incremental**: Solo cambios (diario)
- **Archivos**: Carpeta uploads (diario)

**Retención:**
- Respaldos diarios: 7 días
- Respaldos semanales: 4 semanas
- Respaldos mensuales: 12 meses

### Script de Respaldo Automático

Crear `backup.sh`:

```bash
#!/bin/bash

# Configuración
BACKUP_DIR="/backups/tickets"
DB_NAME="sistema_tickets"
DB_USER="root"
DB_PASS="password"
PROJECT_DIR="/var/www/html/Proyecto-tickets/Tickect"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=7

# Crear directorio si no existe
mkdir -p $BACKUP_DIR

# Respaldo de base de datos
echo "Respaldando base de datos..."
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Respaldo de archivos
echo "Respaldando archivos..."
tar -czf $BACKUP_DIR/files_$DATE.tar.gz $PROJECT_DIR/uploads/

# Respaldo de configuración
echo "Respaldando configuración..."
tar -czf $BACKUP_DIR/config_$DATE.tar.gz $PROJECT_DIR/includes/

# Eliminar respaldos antiguos
echo "Limpiando respaldos antiguos..."
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "files_*.tar.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "config_*.tar.gz" -mtime +$RETENTION_DAYS -delete

# Verificar respaldo
if [ -f "$BACKUP_DIR/db_$DATE.sql.gz" ]; then
    echo "Respaldo completado exitosamente: $DATE"
    # Enviar notificación (opcional)
    # echo "Respaldo completado" | mail -s "Backup OK" admin@empresa.com
else
    echo "ERROR: Respaldo falló"
    # echo "Respaldo falló" | mail -s "Backup FAILED" admin@empresa.com
    exit 1
fi
```

Hacer ejecutable:
```bash
chmod +x backup.sh
```

### Configurar Cron para Respaldos Automáticos

```bash
# Editar crontab
crontab -e

# Respaldo diario a las 2 AM
0 2 * * * /var/www/html/Proyecto-tickets/backup.sh >> /var/log/backup.log 2>&1

# Respaldo semanal completo (domingos a las 3 AM)
0 3 * * 0 /var/www/html/Proyecto-tickets/backup_full.sh >> /var/log/backup.log 2>&1
```

### Respaldo Manual

**Base de Datos:**
```bash
# Respaldo completo
mysqldump -u root -p sistema_tickets > backup_$(date +%Y%m%d).sql

# Respaldo comprimido
mysqldump -u root -p sistema_tickets | gzip > backup_$(date +%Y%m%d).sql.gz

# Respaldo de tabla específica
mysqldump -u root -p sistema_tickets tickets > tickets_backup.sql

# Respaldo sin datos (solo estructura)
mysqldump -u root -p --no-data sistema_tickets > estructura.sql
```

**Archivos:**
```bash
# Respaldo de uploads
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz Tickect/uploads/

# Respaldo completo del proyecto
tar -czf proyecto_completo_$(date +%Y%m%d).tar.gz Proyecto-tickets/
```

### Restauración

**Restaurar Base de Datos:**
```bash
# Desde archivo SQL
mysql -u root -p sistema_tickets < backup_20250125.sql

# Desde archivo comprimido
gunzip < backup_20250125.sql.gz | mysql -u root -p sistema_tickets

# Restaurar tabla específica
mysql -u root -p sistema_tickets < tickets_backup.sql
```

**Restaurar Archivos:**
```bash
# Restaurar uploads
cd /var/www/html/Proyecto-tickets/Tickect/
tar -xzf /backups/tickets/uploads_backup_20250125.tar.gz

# Restaurar proyecto completo
cd /var/www/html/
tar -xzf /backups/tickets/proyecto_completo_20250125.tar.gz
```

### Respaldo en la Nube

**Usando rsync a servidor remoto:**
```bash
# Sincronizar respaldos a servidor remoto
rsync -avz --delete /backups/tickets/ usuario@servidor-remoto:/backups/tickets/

# Agregar a cron
0 4 * * * rsync -avz --delete /backups/tickets/ usuario@servidor-remoto:/backups/tickets/
```

**Usando AWS S3:**
```bash
# Instalar AWS CLI
sudo apt install awscli

# Configurar credenciales
aws configure

# Subir respaldo
aws s3 cp /backups/tickets/db_$(date +%Y%m%d).sql.gz s3://mi-bucket/tickets/

# Script automático
#!/bin/bash
DATE=$(date +%Y%m%d)
aws s3 sync /backups/tickets/ s3://mi-bucket/tickets/ --delete
```

### Verificación de Respaldos

**Script de Verificación:**
```bash
#!/bin/bash

BACKUP_FILE="/backups/tickets/db_latest.sql.gz"

# Verificar que el archivo existe
if [ ! -f "$BACKUP_FILE" ]; then
    echo "ERROR: Archivo de respaldo no encontrado"
    exit 1
fi

# Verificar que no está corrupto
gunzip -t $BACKUP_FILE
if [ $? -eq 0 ]; then
    echo "OK: Respaldo verificado correctamente"
else
    echo "ERROR: Respaldo corrupto"
    exit 1
fi

# Verificar tamaño mínimo (ej: 1MB)
SIZE=$(stat -f%z "$BACKUP_FILE" 2>/dev/null || stat -c%s "$BACKUP_FILE")
if [ $SIZE -lt 1048576 ]; then
    echo "WARNING: Respaldo muy pequeño ($SIZE bytes)"
fi
```

### Plan de Recuperación ante Desastres

**Escenario 1: Pérdida de Base de Datos**
1. Detener Apache: `sudo systemctl stop apache2`
2. Restaurar BD desde último respaldo
3. Verificar integridad de datos
4. Iniciar Apache: `sudo systemctl start apache2`
5. Probar funcionalidad

**Escenario 2: Pérdida de Archivos**
1. Restaurar carpeta uploads desde respaldo
2. Verificar permisos: `chmod -R 777 uploads/`
3. Verificar referencias en BD

**Escenario 3: Pérdida Total del Servidor**
1. Instalar nuevo servidor con requisitos
2. Restaurar código del proyecto
3. Restaurar base de datos
4. Restaurar archivos
5. Configurar servicios
6. Probar completamente

**Tiempo de Recuperación Objetivo (RTO):**
- Pérdida de BD: 30 minutos
- Pérdida de archivos: 1 hora
- Pérdida total: 4 horas

**Punto de Recuperación Objetivo (RPO):**
- Máxima pérdida de datos: 24 horas (respaldo diario)

---

## Solución de Problemas Técnicos

### Problemas Comunes

#### 1. Error de Conexión a Base de Datos

**Síntomas:**
- Mensaje: "Error de conexión a la base de datos"
- Página en blanco

**Diagnóstico:**
```bash
# Verificar que MySQL esté corriendo
sudo systemctl status mysql

# Probar conexión
mysql -u root -p -e "SELECT 1"

# Ver logs
tail -f /var/log/mysql/error.log
```

**Solución:**
```bash
# Reiniciar MySQL
sudo systemctl restart mysql

# Verificar credenciales en db.php
cat Tickect/includes/db.php

# Verificar permisos de usuario
mysql -u root -p -e "SHOW GRANTS FOR 'tickets_user'@'localhost'"
```

#### 2. No se Pueden Subir Archivos

**Síntomas:**
- Error al adjuntar archivos
- Mensaje: "No se pudo subir el archivo"

**Diagnóstico:**
```bash
# Verificar permisos
ls -la Tickect/uploads/

# Verificar espacio en disco
df -h

# Ver configuración PHP
php -i | grep upload
```

**Solución:**
```bash
# Corregir permisos
sudo chmod -R 777 Tickect/uploads/
sudo chown -R www-data:www-data Tickect/uploads/

# Aumentar límite en php.ini
sudo nano /etc/php/8.0/apache2/php.ini
# Cambiar:
# upload_max_filesize = 10M
# post_max_size = 10M

# Reiniciar Apache
sudo systemctl restart apache2
```

#### 3. Sesiones No Persisten

**Síntomas:**
- Se cierra sesión constantemente
- Necesita login repetidamente

**Diagnóstico:**
```bash
# Verificar directorio de sesiones
php -i | grep session.save_path

# Verificar permisos
ls -la /var/lib/php/sessions/
```

**Solución:**
```bash
# Crear directorio si no existe
sudo mkdir -p /var/lib/php/sessions

# Corregir permisos
sudo chmod 1733 /var/lib/php/sessions
sudo chown root:root /var/lib/php/sessions

# Verificar configuración en php.ini
session.save_path = "/var/lib/php/sessions"
session.gc_maxlifetime = 1440
```

#### 4. Correos No se Envían

**Síntomas:**
- No llegan notificaciones por email
- Error al escalar tickets

**Diagnóstico:**
```bash
# Verificar configuración SMTP
cat Tickect/includes/config_correos.php

# Probar envío manual
php -r "mail('test@example.com', 'Test', 'Test message');"

# Ver logs de PHP
tail -f /var/log/apache2/error.log
```

**Solución:**
```php
// Verificar configuración en config_correos.php
// Para Gmail:
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tu_email@gmail.com');
define('SMTP_PASS', 'contraseña_de_aplicación'); // No la contraseña normal

// Habilitar en Gmail:
// 1. Activar verificación en 2 pasos
// 2. Generar contraseña de aplicación
// 3. Usar esa contraseña en SMTP_PASS
```

#### 5. Página en Blanco (White Screen)

**Síntomas:**
- Pantalla completamente blanca
- Sin mensajes de error

**Diagnóstico:**
```bash
# Habilitar errores temporalmente
sudo nano /etc/php/8.0/apache2/php.ini
# Cambiar:
# display_errors = On
# error_reporting = E_ALL

# Reiniciar Apache
sudo systemctl restart apache2

# Ver logs
tail -f /var/log/apache2/error.log
tail -f /var/log/php_errors.log
```

**Causas Comunes:**
- Error de sintaxis PHP
- Memoria insuficiente
- Archivo corrupto
- Permisos incorrectos

#### 6. Rendimiento Lento

**Síntomas:**
- Páginas tardan en cargar
- Timeouts frecuentes

**Diagnóstico:**
```bash
# Ver procesos MySQL
mysql -u root -p -e "SHOW PROCESSLIST"

# Ver uso de recursos
top
htop

# Ver queries lentas
mysql -u root -p -e "SHOW VARIABLES LIKE 'slow_query%'"
```

**Solución:**
```sql
-- Agregar índices faltantes
CREATE INDEX idx_ticket_estado ON tickets(estado);
CREATE INDEX idx_ticket_fecha ON tickets(fecha_creacion);
CREATE INDEX idx_ticket_usuario ON tickets(usuario_asignado_id);

-- Optimizar tablas
OPTIMIZE TABLE tickets;
OPTIMIZE TABLE respuestas;
```

```bash
# Aumentar memoria PHP
sudo nano /etc/php/8.0/apache2/php.ini
# memory_limit = 256M

# Habilitar caché de opcodes
sudo apt install php-opcache
sudo systemctl restart apache2
```

### Logs y Debugging

**Ubicación de Logs:**
```bash
# Apache
/var/log/apache2/error.log
/var/log/apache2/access.log

# PHP
/var/log/php_errors.log

# MySQL
/var/log/mysql/error.log
/var/log/mysql/slow-query.log

# Sistema
/var/log/syslog
```

**Ver Logs en Tiempo Real:**
```bash
# Apache errors
tail -f /var/log/apache2/error.log

# Todos los logs de Apache
tail -f /var/log/apache2/*.log

# Filtrar por palabra clave
grep "error" /var/log/apache2/error.log

# Últimas 100 líneas
tail -n 100 /var/log/apache2/error.log
```

**Habilitar Debug en PHP:**
```php
<?php
// Al inicio del archivo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_debug.log');

// Debug de variables
var_dump($variable);
print_r($array);

// Debug de queries
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    error_log("SQL Error: " . $e->getMessage());
    error_log("SQL Query: " . $sql);
    die("Error en la consulta");
}
?>
```


---

## Seguridad

### Mejores Prácticas de Seguridad

#### 1. Contraseñas Seguras

**Implementar Hash de Contraseñas:**

Actualizar `includes/auth.php`:

```php
<?php
// Al crear usuario
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, contrasena, rol) VALUES (?, ?, ?)");
$stmt->execute([$username, $password_hash, $rol]);

// Al verificar login
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['contrasena'])) {
    // Login exitoso
    $_SESSION['usuario_id'] = $user['id_usuario'];
    $_SESSION['rol'] = $user['rol'];
} else {
    // Login fallido
    $error = "Usuario o contraseña incorrectos";
}
?>
```

**Migrar Contraseñas Existentes:**

```php
<?php
// Script de migración: migrate_passwords.php
require_once 'includes/db.php';

$stmt = $pdo->query("SELECT id_usuario, contrasena FROM usuarios");
$usuarios = $stmt->fetchAll();

foreach ($usuarios as $usuario) {
    // Si la contraseña no está hasheada (longitud < 60)
    if (strlen($usuario['contrasena']) < 60) {
        $new_hash = password_hash($usuario['contrasena'], PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?");
        $update->execute([$new_hash, $usuario['id_usuario']]);
        echo "Usuario {$usuario['id_usuario']} migrado\n";
    }
}

echo "Migración completada\n";
?>
```

#### 2. Protección SQL Injection

**Siempre usar Prepared Statements:**

```php
<?php
// ❌ INCORRECTO - Vulnerable a SQL Injection
$sql = "SELECT * FROM usuarios WHERE nombre_usuario = '$username'";
$result = $pdo->query($sql);

// ✅ CORRECTO - Seguro
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = ?");
$stmt->execute([$username]);
$result = $stmt->fetch();

// ✅ CORRECTO - Con nombres
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = :username");
$stmt->execute(['username' => $username]);
?>
```

#### 3. Protección XSS (Cross-Site Scripting)

**Sanitizar Salidas:**

```php
<?php
// Función de sanitización
function sanitize_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Uso en vistas
echo sanitize_output($ticket['asunto']);
echo sanitize_output($usuario['nombre_usuario']);

// Para HTML permitido (descripciones ricas)
function sanitize_html($html) {
    // Usar librería como HTML Purifier
    return strip_tags($html, '<p><br><b><i><u><a>');
}
?>
```

#### 4. Protección CSRF (Cross-Site Request Forgery)

**Implementar Tokens CSRF:**

```php
<?php
// Generar token
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// En formularios
?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <!-- otros campos -->
</form>

<?php
// Verificar token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token CSRF inválido');
    }
    // Procesar formulario
}
?>
```

#### 5. Validación de Subida de Archivos

**Validar Archivos Correctamente:**

```php
<?php
function validar_archivo($file) {
    // Verificar que se subió correctamente
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir archivo'];
    }
    
    // Verificar tamaño (10MB máximo)
    $max_size = 10 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Archivo muy grande'];
    }
    
    // Verificar tipo MIME real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    if (!in_array($mime, $allowed_mimes)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nuevo_nombre = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    
    return ['success' => true, 'nombre' => $nuevo_nombre];
}

// Uso
if (isset($_FILES['archivo'])) {
    $validacion = validar_archivo($_FILES['archivo']);
    if ($validacion['success']) {
        $ruta = 'uploads/tickets/' . $validacion['nombre'];
        move_uploaded_file($_FILES['archivo']['tmp_name'], $ruta);
    } else {
        echo $validacion['error'];
    }
}
?>
```

#### 6. Configuración Segura de PHP

**php.ini para Producción:**

```ini
; Ocultar versión de PHP
expose_php = Off

; Deshabilitar funciones peligrosas
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

; Límites de recursos
max_execution_time = 30
max_input_time = 60
memory_limit = 128M
post_max_size = 10M
upload_max_filesize = 10M

; Errores
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; Sesiones
session.cookie_httponly = 1
session.cookie_secure = 1  ; Solo si usas HTTPS
session.use_strict_mode = 1
session.cookie_samesite = Strict
```

#### 7. Configuración Segura de Apache

**Crear .htaccess en directorio raíz:**

```apache
# Prevenir listado de directorios
Options -Indexes

# Proteger archivos sensibles
<FilesMatch "^(db\.php|config.*\.php)$">
    Require all denied
</FilesMatch>

# Forzar HTTPS (si está configurado)
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Prevenir acceso a archivos ocultos
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

# Headers de seguridad
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

#### 8. Configuración de MySQL

**Seguridad de Base de Datos:**

```sql
-- Eliminar usuarios anónimos
DELETE FROM mysql.user WHERE User='';

-- Eliminar base de datos de prueba
DROP DATABASE IF EXISTS test;

-- Crear usuario con permisos limitados
CREATE USER 'tickets_user'@'localhost' IDENTIFIED BY 'password_fuerte_aqui';
GRANT SELECT, INSERT, UPDATE, DELETE ON sistema_tickets.* TO 'tickets_user'@'localhost';
FLUSH PRIVILEGES;

-- Deshabilitar acceso remoto a root
UPDATE mysql.user SET Host='localhost' WHERE User='root';
FLUSH PRIVILEGES;
```

#### 9. Firewall y Acceso

**Configurar UFW (Linux):**

```bash
# Habilitar firewall
sudo ufw enable

# Permitir SSH
sudo ufw allow 22/tcp

# Permitir HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Denegar todo lo demás
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Ver estado
sudo ufw status
```

**Limitar Acceso a phpMyAdmin:**

```apache
# En /etc/apache2/conf-available/phpmyadmin.conf
<Directory /usr/share/phpmyadmin>
    # Solo permitir desde IPs específicas
    Require ip 192.168.1.0/24
    Require ip 10.0.0.5
</Directory>
```

#### 10. Auditoría y Logging

**Implementar Log de Auditoría:**

```php
<?php
// Función de auditoría
function log_auditoria($accion, $detalles = '') {
    global $pdo;
    
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $pdo->prepare("
        INSERT INTO auditoria (usuario_id, accion, detalles, ip, user_agent, fecha)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$usuario_id, $accion, $detalles, $ip, $user_agent]);
}

// Uso
log_auditoria('LOGIN', 'Usuario inició sesión');
log_auditoria('TICKET_CREADO', 'Ticket #123 creado');
log_auditoria('USUARIO_ELIMINADO', 'Usuario ID 45 eliminado');
?>
```

**Crear Tabla de Auditoría:**

```sql
CREATE TABLE auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    detalles TEXT,
    ip VARCHAR(45),
    user_agent VARCHAR(255),
    fecha DATETIME NOT NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha),
    INDEX idx_accion (accion)
);
```

### Checklist de Seguridad

**Antes de Producción:**

- [ ] Contraseñas hasheadas con `password_hash()`
- [ ] Prepared statements en todas las queries
- [ ] Sanitización de salidas con `htmlspecialchars()`
- [ ] Tokens CSRF en formularios
- [ ] Validación de archivos subidos
- [ ] `display_errors = Off` en php.ini
- [ ] Permisos correctos en archivos (755/644)
- [ ] Permisos 777 solo en uploads
- [ ] .htaccess configurado
- [ ] Firewall habilitado
- [ ] HTTPS configurado (certificado SSL)
- [ ] Respaldos automáticos configurados
- [ ] Logs de auditoría activos
- [ ] Contraseñas por defecto cambiadas
- [ ] phpMyAdmin protegido o deshabilitado
- [ ] MySQL accesible solo desde localhost

---

## Optimización

### Optimización de Base de Datos

#### Índices Recomendados

```sql
-- Tickets
CREATE INDEX idx_ticket_estado ON tickets(estado);
CREATE INDEX idx_ticket_prioridad ON tickets(prioridad);
CREATE INDEX idx_ticket_fecha_creacion ON tickets(fecha_creacion);
CREATE INDEX idx_ticket_usuario_asignado ON tickets(usuario_asignado_id);
CREATE INDEX idx_ticket_usuario_creador ON tickets(usuario_creador_id);
CREATE INDEX idx_ticket_categoria ON tickets(categoria_id);

-- Respuestas
CREATE INDEX idx_respuesta_ticket ON respuestas(ticket_id);
CREATE INDEX idx_respuesta_usuario ON respuestas(usuario_id);
CREATE INDEX idx_respuesta_fecha ON respuestas(fecha_respuesta);

-- Usuarios
CREATE INDEX idx_usuario_rol ON usuarios(rol);
CREATE INDEX idx_usuario_departamento ON usuarios(departamento_id);

-- Archivos
CREATE INDEX idx_archivo_ticket ON archivos_adjuntos(ticket_id);
```

#### Consultas Optimizadas

**Antes (Lento):**
```sql
SELECT * FROM tickets 
WHERE usuario_asignado_id = 5;
```

**Después (Rápido):**
```sql
SELECT id_ticket, asunto, estado, prioridad, fecha_creacion 
FROM tickets 
WHERE usuario_asignado_id = 5 
    AND estado != 'cerrado'
ORDER BY prioridad DESC, fecha_creacion ASC
LIMIT 50;
```

#### Configuración de MySQL

**my.cnf / my.ini:**

```ini
[mysqld]
# Memoria
innodb_buffer_pool_size = 1G
key_buffer_size = 256M
max_connections = 100

# Query cache (MySQL 5.7)
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M

# Logs
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2

# InnoDB
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
```

### Optimización de PHP

#### Habilitar OPcache

```bash
# Instalar
sudo apt install php-opcache

# Configurar en php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1

# Reiniciar
sudo systemctl restart apache2
```

#### Caché de Sesiones

```php
<?php
// Usar Redis o Memcached para sesiones
// En php.ini:
// session.save_handler = redis
// session.save_path = "tcp://127.0.0.1:6379"
?>
```

### Optimización de Apache

**Habilitar Compresión:**

```apache
# Habilitar mod_deflate
sudo a2enmod deflate

# Configurar
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

**Habilitar Caché:**

```apache
# Habilitar mod_expires
sudo a2enmod expires

# Configurar
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Optimización de Frontend

**Minificar CSS/JS:**

```bash
# Instalar herramientas
npm install -g uglify-js clean-css-cli

# Minificar JavaScript
uglifyjs js/theme-manager.js -o js/theme-manager.min.js

# Minificar CSS
cleancss -o css/themes.min.css css/themes.css
```

**Lazy Loading de Imágenes:**

```html
<img src="placeholder.jpg" data-src="imagen-real.jpg" loading="lazy" alt="Descripción">
```

### Monitoreo de Rendimiento

**Herramientas Recomendadas:**

```bash
# Instalar herramientas de monitoreo
sudo apt install htop iotop nethogs

# Monitorear en tiempo real
htop           # Uso de CPU/RAM
iotop          # Uso de disco
nethogs        # Uso de red
```

**Monitoreo de MySQL:**

```sql
-- Ver queries lentas
SELECT * FROM mysql.slow_log ORDER BY query_time DESC LIMIT 10;

-- Ver procesos activos
SHOW FULL PROCESSLIST;

-- Ver estado del servidor
SHOW STATUS;
SHOW VARIABLES;
```

---

## Apéndices

### A. Comandos Útiles

```bash
# Reiniciar servicios
sudo systemctl restart apache2
sudo systemctl restart mysql

# Ver logs en tiempo real
tail -f /var/log/apache2/error.log

# Verificar sintaxis Apache
sudo apache2ctl configtest

# Verificar sintaxis PHP
php -l archivo.php

# Limpiar caché
sudo service apache2 reload

# Ver conexiones activas
netstat -an | grep :80 | wc -l

# Ver uso de disco
du -sh /var/www/html/Proyecto-tickets/

# Buscar archivos grandes
find /var/www/html/Proyecto-tickets/ -type f -size +10M
```

### B. Estructura de Tablas SQL

Ver archivo `sistema_tickets.sql` para estructura completa.

### C. Variables de Sesión

```php
$_SESSION['usuario_id']      // ID del usuario logueado
$_SESSION['nombre_usuario']  // Nombre de usuario
$_SESSION['rol']             // admin/usuario/cliente
$_SESSION['jerarquia']       // Jefe/Asistente/Sub-Gerente/Gerente
$_SESSION['departamento_id'] // ID del departamento
$_SESSION['csrf_token']      // Token CSRF
```

### D. Contactos de Soporte

**Soporte Técnico:**
- Email: soporte@empresa.com
- Teléfono: +52 XXX XXX XXXX
- Horario: Lunes a Viernes 9:00 - 18:00

**Desarrollador:**
- GitHub: https://github.com/JDiegoNFS
- Email: diego@empresa.com

---

## Conclusión

Este manual proporciona las herramientas y conocimientos necesarios para operar y mantener el Sistema de Gestión de Tickets de manera efectiva. Se recomienda:

1. Realizar respaldos diarios automáticos
2. Monitorear logs regularmente
3. Mantener el sistema actualizado
4. Implementar todas las medidas de seguridad
5. Optimizar según crecimiento
6. Documentar cambios realizados

Para soporte adicional o consultas técnicas, contactar al equipo de desarrollo.

---

**Manual de Operaciones v1.0**  
**Última actualización**: Noviembre 2025  
**Autor**: Equipo de Desarrollo Sistema de Tickets
