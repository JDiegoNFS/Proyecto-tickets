<?php
require_once '../includes/auth.php';
verificarRol(['cliente', 'usuario']);

require_once '../includes/db.php';

if (!isset($_GET['ticket_id'])) {
    die("ID de ticket no especificado.");
}

$ticket_id = intval($_GET['ticket_id']);

// Obtener información completa del ticket
$stmtTicket = $pdo->prepare("
    SELECT t.*, 
           c.nombre AS categoria_nombre, 
           d.nombre AS departamento_nombre,
           u.usuario as creador_nombre,
           u2.usuario as asignado_nombre
    FROM tickets t
    LEFT JOIN categorias c ON t.categoria_id = c.id
    LEFT JOIN departamentos d ON t.departamento_id = d.id
    LEFT JOIN usuarios u ON t.cliente_id = u.id
    LEFT JOIN usuarios u2 ON t.usuario_id = u2.id
    WHERE t.id = ?
");
$stmtTicket->execute([$ticket_id]);
$ticket = $stmtTicket->fetch();

if (!$ticket) {
    die("Ticket no encontrado.");
}

// Obtener historial del ticket
$stmtHistorial = $pdo->prepare("
    SELECT h.*, u.usuario
    FROM historial_tickets h
    JOIN usuarios u ON h.usuario_id = u.id
    WHERE h.ticket_id = ?
    ORDER BY h.fecha ASC
");
$stmtHistorial->execute([$ticket_id]);
$historial = $stmtHistorial->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial del Ticket #<?php echo $ticket['id']; ?> - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_usuario_crear_ticket.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .header-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .breadcrumb {
            margin-bottom: 15px;
        }
        
        .breadcrumb-link {
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .breadcrumb-link:hover {
            color: #007bff;
        }
        
        .page-title {
            color: #495057;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        
        .ticket-summary {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .summary-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .summary-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        .summary-content h4 {
            margin: 0 0 5px 0;
            color: #495057;
            font-size: 14px;
            font-weight: 600;
        }
        
        .summary-content p {
            margin: 0;
            color: #6c757d;
            font-size: 13px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pendiente {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }
        
        .status-en_proceso {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        
        .status-cerrado {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        .timeline-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .timeline-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .timeline-title {
            color: #495057;
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
            max-height: 600px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .timeline::-webkit-scrollbar {
            width: 6px;
        }
        
        .timeline::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .timeline::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .timeline::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #007bff, #6c757d);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        
        .timeline-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -37px;
            top: 15px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #007bff;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #e9ecef;
        }
        
        .timeline-item.cliente::before {
            background: #28a745;
        }
        
        .timeline-item.usuario::before {
            background: #007bff;
        }
        
        .timeline-item.admin::before {
            background: #dc3545;
        }
        
        .timeline-header-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .timeline-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #007bff, #0056b3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }
        
        .user-info h5 {
            margin: 0;
            color: #495057;
            font-size: 13px;
            font-weight: 600;
        }
        
        .user-role {
            font-size: 11px;
            color: #6c757d;
            text-transform: capitalize;
        }
        
        .timeline-date {
            color: #6c757d;
            font-size: 11px;
            font-weight: 500;
        }
        
        .timeline-action {
            color: #495057;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-icon {
            font-size: 48px;
            color: #dee2e6;
            margin-bottom: 15px;
        }
        
        .empty-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #495057;
        }
        
        .empty-description {
            font-size: 14px;
            color: #6c757d;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="header-section">
            <div class="breadcrumb">
                <?php if ($_SESSION['rol'] === 'usuario'): ?>
                    <a href="ver_tickets.php" class="breadcrumb-link">
                        <i class="fas fa-arrow-left"></i> Volver a Mis Tickets
                    </a>
                <?php elseif ($_SESSION['rol'] === 'cliente'): ?>
                    <a href="../cliente/ver_tickets.php" class="breadcrumb-link">
                        <i class="fas fa-arrow-left"></i> Volver a Mis Tickets
                    </a>
                <?php endif; ?>
            </div>
            <h1 class="page-title">
                <i class="fas fa-history"></i>
                Historial del Ticket #<?php echo $ticket['id']; ?>
            </h1>
        </div>

        <div class="ticket-summary">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-icon" style="background: linear-gradient(135deg, #007bff, #0056b3);">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="summary-content">
                        <h4>Categoría</h4>
                        <p><?php echo htmlspecialchars($ticket['categoria_nombre'] ?? 'Sin categoría'); ?></p>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="summary-content">
                        <h4>Departamento</h4>
                        <p><?php echo htmlspecialchars($ticket['departamento_nombre'] ?? 'Sin departamento'); ?></p>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-icon" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="summary-content">
                        <h4>Creado por</h4>
                        <p><?php echo htmlspecialchars($ticket['creador_nombre'] ?? 'Usuario desconocido'); ?></p>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-icon" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="summary-content">
                        <h4>Estado</h4>
                        <p>
                            <span class="status-badge status-<?php echo $ticket['estado']; ?>">
                                <?php 
                                $estados = [
                                    'pendiente' => 'Pendiente',
                                    'en_proceso' => 'En Proceso',
                                    'cerrado' => 'Cerrado'
                                ];
                                echo $estados[$ticket['estado']] ?? $ticket['estado'];
                                ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="timeline-container">
            <div class="timeline-header">
                <h2 class="timeline-title">
                    <i class="fas fa-clock"></i>
                    Cronología de Eventos
                </h2>
        </div>

        <?php if (count($historial) === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="empty-title">Sin historial registrado</div>
                    <div class="empty-description">Este ticket aún no tiene eventos registrados en su historial.</div>
                </div>
        <?php else: ?>
                <div class="timeline">
                        <?php foreach ($historial as $h): ?>
                        <div class="timeline-item <?php echo $h['rol']; ?>">
                            <div class="timeline-header-item">
                                <div class="timeline-user">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($h['usuario'], 0, 2)); ?>
                                    </div>
                                    <div class="user-info">
                                        <h5><?php echo htmlspecialchars($h['usuario']); ?></h5>
                                        <div class="user-role"><?php echo ucfirst($h['rol']); ?></div>
                                    </div>
                                </div>
                                <div class="timeline-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($h['fecha'])); ?>
                                </div>
                            </div>
                            <div class="timeline-action">
                                <?php echo htmlspecialchars($h['accion']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="<?php echo $_SESSION['rol'] === 'usuario' ? 'ver_tickets.php' : '../cliente/ver_tickets.php'; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Volver a Mis Tickets
            </a>
        </div>
    </div>
</body>
</html>
