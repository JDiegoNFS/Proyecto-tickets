<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION["usuario_id"]) || $_SESSION["rol"] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Obtener notificaciones recientes
try {
    // Tickets nuevos (últimas 24 horas)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM tickets 
        WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $tickets_nuevos = $stmt->fetch()['count'];
    
    // Tickets pendientes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tickets WHERE estado = 'pendiente'");
    $tickets_pendientes = $stmt->fetch()['count'];
    
    // Mensajes sin leer
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM mensajes m 
        JOIN tickets t ON m.ticket_id = t.id 
        WHERE m.leido = 0 AND m.remitente != 'admin'
    ");
    $mensajes_sin_leer = $stmt->fetch()['count'];
    
    // Tickets vencidos (más de 48 horas sin respuesta)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM tickets 
        WHERE estado IN ('pendiente', 'en_proceso') 
        AND fecha_creacion < DATE_SUB(NOW(), INTERVAL 48 HOUR)
    ");
    $tickets_vencidos = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    $tickets_nuevos = 0;
    $tickets_pendientes = 0;
    $mensajes_sin_leer = 0;
    $tickets_vencidos = 0;
}

// Crear array de notificaciones
$notificaciones = [];

if ($tickets_nuevos > 0) {
    $notificaciones[] = [
        'tipo' => 'info',
        'icono' => 'fas fa-ticket-alt',
        'titulo' => 'Tickets Nuevos',
        'mensaje' => "Se han creado {$tickets_nuevos} tickets en las últimas 24 horas",
        'url' => 'reporte.php'
    ];
}

if ($tickets_pendientes > 0) {
    $notificaciones[] = [
        'tipo' => 'warning',
        'icono' => 'fas fa-clock',
        'titulo' => 'Tickets Pendientes',
        'mensaje' => "Hay {$tickets_pendientes} tickets pendientes de revisión",
        'url' => 'reporte.php'
    ];
}

if ($mensajes_sin_leer > 0) {
    $notificaciones[] = [
        'tipo' => 'success',
        'icono' => 'fas fa-comments',
        'titulo' => 'Mensajes Sin Leer',
        'mensaje' => "Tienes {$mensajes_sin_leer} mensajes sin leer",
        'url' => 'reporte.php'
    ];
}

if ($tickets_vencidos > 0) {
    $notificaciones[] = [
        'tipo' => 'danger',
        'icono' => 'fas fa-exclamation-triangle',
        'titulo' => 'Tickets Vencidos',
        'mensaje' => "Hay {$tickets_vencidos} tickets que requieren atención urgente",
        'url' => 'reporte.php'
    ];
}

// Si no hay notificaciones, agregar una de bienvenida
if (empty($notificaciones)) {
    $notificaciones[] = [
        'tipo' => 'success',
        'icono' => 'fas fa-check-circle',
        'titulo' => 'Todo en Orden',
        'mensaje' => 'No hay notificaciones pendientes. El sistema está funcionando correctamente.',
        'url' => 'dashboard.php'
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_admin_dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    
<div class="page-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-text">
                <h1 class="titulo">🔔 Notificaciones del Sistema</h1>
                <p class="subtitulo">Estado actual y alertas importantes</p>
            </div>
            <div class="header-actions">
                <a href="../dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Notifications Grid -->
    <div class="notifications-grid">
        <?php foreach ($notificaciones as $notif): ?>
        <div class="notification-card notification-<?php echo $notif['tipo']; ?>">
            <div class="notification-icon">
                <i class="<?php echo $notif['icono']; ?>"></i>
            </div>
            <div class="notification-content">
                <h3><?php echo htmlspecialchars($notif['titulo']); ?></h3>
                <p><?php echo htmlspecialchars($notif['mensaje']); ?></p>
            </div>
            <div class="notification-actions">
                <a href="<?php echo $notif['url']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye"></i> Ver
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- System Status -->
    <div class="status-section">
        <div class="status-card">
            <h3><i class="fas fa-server"></i> Estado del Sistema</h3>
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-icon status-online">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="status-content">
                        <h4>Base de Datos</h4>
                        <p>Conectada y funcionando</p>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-icon status-online">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="status-content">
                        <h4>Servidor Web</h4>
                        <p>Operativo</p>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-icon status-online">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="status-content">
                        <h4>Sistema de Mensajes</h4>
                        <p>Funcionando correctamente</p>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-icon status-online">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="status-content">
                        <h4>Reportes</h4>
                        <p>Generando estadísticas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="quick-stats-card">
            <h3><i class="fas fa-chart-bar"></i> Estadísticas Rápidas</h3>
            <div class="stats-row">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $tickets_nuevos; ?></span>
                    <span class="stat-label">Tickets Nuevos (24h)</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $tickets_pendientes; ?></span>
                    <span class="stat-label">Pendientes</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $mensajes_sin_leer; ?></span>
                    <span class="stat-label">Mensajes Sin Leer</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $tickets_vencidos; ?></span>
                    <span class="stat-label">Vencidos</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh cada 30 segundos
setInterval(function() {
    location.reload();
}, 30000);

// Mostrar notificación de actualización
setTimeout(function() {
    const notification = document.createElement('div');
    notification.className = 'toast-notification';
    notification.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizando datos...';
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 2000);
}, 25000);
</script>

</body>
</html>
