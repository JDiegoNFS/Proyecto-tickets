<?php
require_once '../includes/auth.php';
verificarRol('usuario');
require_once '../includes/db.php';
require_once '../includes/funciones_mensajes.php';

$usuario_id = $_SESSION['usuario_id'];
$ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

// Obtener información del ticket
try {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               c.nombre as categoria_nombre, 
               d.nombre as departamento_nombre,
               u.usuario as creador_nombre,
               u2.usuario as asignado_nombre
        FROM tickets t
        LEFT JOIN categorias c ON t.categoria_id = c.id
        LEFT JOIN departamentos d ON t.departamento_id = d.id
        LEFT JOIN usuarios u ON t.usuario_id = u.id
        LEFT JOIN usuarios u2 ON t.cliente_id = u2.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        die("Ticket no encontrado.");
    }
} catch (Exception $e) {
    die("Error al obtener el ticket: " . $e->getMessage());
}

// Verificar que el usuario puede ver este ticket (mismo departamento)
$stmt = $pdo->prepare("SELECT departamento_id FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario_departamento = $stmt->fetchColumn();

if ($ticket['departamento_id'] != $usuario_departamento && $ticket['usuario_id'] != $usuario_id) {
    die("No tienes permisos para ver este ticket.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Ticket #<?php echo $ticket['id']; ?> - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_usuario_crear_ticket.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .ticket-details {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
            width: 150px;
            flex-shrink: 0;
        }
        .detail-value {
            color: #6c757d;
            flex: 1;
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
        .description-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-top: 10px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 300px;
            overflow-y: auto;
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
        .btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
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
                <a href="ver_tickets.php" class="breadcrumb-link">
                    <i class="fas fa-arrow-left"></i> Volver a Mis Tickets
                </a>
            </div>
            <h1 class="page-title">
                <i class="fas fa-eye"></i>
                Visualizar Ticket #<?php echo $ticket['id']; ?>
            </h1>
        </div>

        <div class="ticket-details">
            <div class="detail-row">
                <div class="detail-label">
                    <i class="fas fa-tag"></i> Categoría:
                </div>
                <div class="detail-value">
                    <?php echo htmlspecialchars($ticket['categoria_nombre'] ?? 'Sin categoría'); ?>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">
                    <i class="fas fa-building"></i> Departamento:
                </div>
                <div class="detail-value">
                    <?php echo htmlspecialchars($ticket['departamento_nombre'] ?? 'Sin departamento'); ?>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">
                    <i class="fas fa-user"></i> Creado por:
                </div>
                <div class="detail-value">
                    <?php echo htmlspecialchars($ticket['creador_nombre'] ?? 'Usuario desconocido'); ?>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">
                    <i class="fas fa-user-check"></i> Asignado a:
                </div>
                <div class="detail-value">
                    <?php 
                    if ($ticket['estado'] === 'pendiente') {
                        echo 'Sin asignar';
                    } else {
                        echo htmlspecialchars($ticket['asignado_nombre'] ?? 'Sin asignar');
                    }
                    ?>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">
                    <i class="fas fa-info-circle"></i> Estado:
                </div>
                <div class="detail-value">
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
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">
                    <i class="fas fa-calendar"></i> Fecha de creación:
                </div>
                <div class="detail-value">
                    <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                </div>
            </div>

            <?php if ($ticket['fecha_inicio']): ?>
            <div class="detail-row">
                <div class="detail-label">
                    <i class="fas fa-play"></i> Fecha de inicio:
                </div>
                <div class="detail-value">
                    <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_inicio'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($ticket['fecha_cierre']): ?>
            <div class="detail-row">
                <div class="detail-label">
                    <i class="fas fa-check"></i> Fecha de cierre:
                </div>
                <div class="detail-value">
                    <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_cierre'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="detail-row" style="align-items: flex-start;">
                <div class="detail-label">
                    <i class="fas fa-comment-alt"></i> Descripción:
                </div>
                <div class="detail-value">
                    <div class="description-box">
                        <?php echo htmlspecialchars(strip_tags($ticket['descripcion'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <?php if ($ticket['estado'] === 'pendiente'): ?>
                <form method="post" action="tomar_ticket.php" style="display: inline;">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-hand-paper"></i>
                        Tomar Ticket
                    </button>
                </form>
            <?php endif; ?>
            
            <a href="ver_tickets.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Volver
            </a>
        </div>
    </div>
</body>
</html>

