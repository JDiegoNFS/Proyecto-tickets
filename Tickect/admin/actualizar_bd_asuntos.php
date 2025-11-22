<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION["usuario_id"]) || $_SESSION["rol"] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

try {
    // Verificar si la tabla departamentos existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'departamentos'");
    $departamentos_exists = $stmt->fetch();
    
    if (!$departamentos_exists) {
        // Crear la tabla departamentos si no existe
        $pdo->exec("
            CREATE TABLE departamentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL UNIQUE,
                descripcion TEXT,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    // Verificar si la tabla categorias existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'categorias'");
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        // Crear la tabla categorias si no existe
        $pdo->exec("
            CREATE TABLE categorias (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL UNIQUE,
                descripcion TEXT,
                color VARCHAR(7) DEFAULT '#4a90e2',
                departamento_id INT NULL,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL
            )
        ");
        $mensaje = 'Base de datos actualizada exitosamente. Se crearon las tablas departamentos y categorias con relación.';
        $tipo_mensaje = 'success';
    } else {
        // Verificar si la columna departamento_id ya existe
        $stmt = $pdo->query("SHOW COLUMNS FROM categorias LIKE 'departamento_id'");
        $column_exists = $stmt->fetch();
        
        if (!$column_exists) {
            // Agregar la columna departamento_id
            $pdo->exec("ALTER TABLE categorias ADD COLUMN departamento_id INT NULL AFTER color");
            $pdo->exec("ALTER TABLE categorias ADD FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL");
            $mensaje = 'Base de datos actualizada exitosamente. Se agregó la relación entre asuntos y departamentos.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'La base de datos ya está actualizada.';
            $tipo_mensaje = 'info';
        }
    }
    
    // Verificar si la tabla tickets tiene la columna categoria_id
    $stmt = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'categoria_id'");
    $categoria_id_exists = $stmt->fetch();
    
    if (!$categoria_id_exists) {
        // Verificar si existe asunto_id para migrar datos
        $stmt = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'asunto_id'");
        $asunto_id_exists = $stmt->fetch();
        
        if ($asunto_id_exists) {
            // Agregar columna categoria_id y migrar datos
            $pdo->exec("ALTER TABLE tickets ADD COLUMN categoria_id INT NULL AFTER asunto_id");
            $pdo->exec("UPDATE tickets SET categoria_id = asunto_id WHERE asunto_id IS NOT NULL");
            $pdo->exec("ALTER TABLE tickets ADD FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL");
            $pdo->exec("ALTER TABLE tickets DROP COLUMN asunto_id");
            $mensaje .= ' Se migró la tabla tickets para usar categorias.';
        } else {
            // Solo agregar la columna categoria_id
            $pdo->exec("ALTER TABLE tickets ADD COLUMN categoria_id INT NULL");
            $pdo->exec("ALTER TABLE tickets ADD FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL");
            $mensaje .= ' Se agregó la columna categoria_id a la tabla tickets.';
        }
    }
} catch (Exception $e) {
    $mensaje = 'Error al actualizar la base de datos: ' . $e->getMessage();
    $tipo_mensaje = 'error';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Base de Datos - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_admin_dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    
<div class="page-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-text">
                <h1 class="titulo">🔧 Actualizar Base de Datos</h1>
                <p class="subtitulo">Configuración de asuntos por departamento</p>
            </div>
            <div class="header-actions">
                <a href="../dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Message Alert -->
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>

    <!-- Info Section -->
    <div class="form-section">
        <div class="form-card">
            <h3><i class="fas fa-info-circle"></i> Información</h3>
            <p>Esta actualización configura la base de datos para el sistema de categorías por departamento.</p>
            <p><strong>Cambios realizados:</strong></p>
            <ul>
                <li>Se creó la tabla <code>departamentos</code> (si no existía)</li>
                <li>Se creó la tabla <code>categorias</code> (si no existía)</li>
                <li>Se agregó la columna <code>departamento_id</code> a la tabla <code>categorias</code></li>
                <li>Se estableció una relación de clave foránea entre categorías y departamentos</li>
                <li>Se migró la tabla <code>tickets</code> para usar <code>categoria_id</code> en lugar de <code>asunto_id</code></li>
                <li>Ahora las categorías pueden estar asociadas a departamentos específicos</li>
                <li>Al crear tickets, se filtran categorías por departamento seleccionado</li>
            </ul>
            
            <div class="form-actions">
                <a href="crear_categoria.php" class="btn btn-primary">
                    <i class="fas fa-tags"></i> Gestionar Categorías
                </a>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Ir al Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
