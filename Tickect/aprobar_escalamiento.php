<?php
require_once 'includes/db.php';
require_once 'includes/escalamiento.php';

$token = $_GET['token'] ?? '';
$accion = $_GET['accion'] ?? '';

if (empty($token) || empty($accion)) {
    $error = "Parámetros inválidos";
    include 'error_aprobacion.php';
    exit();
}

if (!in_array($accion, ['aprobar', 'rechazar'])) {
    $error = "Acción no válida";
    include 'error_aprobacion.php';
    exit();
}

try {
    // Buscar el escalamiento por token
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            t.descripcion,
            u.usuario, u.nombre as usuario_nombre
        FROM escalamientos e
        LEFT JOIN tickets t ON e.ticket_id = t.id
        LEFT JOIN usuarios u ON e.usuario_id = u.id
        WHERE e.token_aprobacion = ? AND e.estado IN ('enviado', 'pendiente')
    ");
    $stmt->execute([$token]);
    $escalamiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$escalamiento) {
        $error = "Token no válido o escalamiento ya procesado";
        include 'error_aprobacion.php';
        exit();
    }
    
    // Verificar que no haya pasado mucho tiempo (opcional: 30 días)
    $fecha_escalamiento = new DateTime($escalamiento['fecha_escalamiento']);
    $fecha_actual = new DateTime();
    $diferencia = $fecha_actual->diff($fecha_escalamiento);
    
    if ($diferencia->days > 30) {
        $error = "Este enlace ha expirado (más de 30 días)";
        include 'error_aprobacion.php';
        exit();
    }
    
    // Procesar la aprobación/rechazo
    $pdo->beginTransaction();
    
    $nuevo_estado = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';
    $respuesta = ($accion === 'aprobar') ? 'Solicitud aprobada' : 'Solicitud rechazada';
    
    // Actualizar el escalamiento
    $stmt = $pdo->prepare("
        UPDATE escalamientos 
        SET estado = ?, 
            fecha_respuesta = NOW(), 
            respuesta_destinatario = ?,
            ip_respuesta = ?,
            user_agent_respuesta = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $nuevo_estado,
        $respuesta,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $escalamiento['id']
    ]);
    
    // Registrar en historial del ticket
    $accion_historial = "Escalamiento " . ($accion === 'aprobar' ? 'APROBADO' : 'RECHAZADO') . " por " . $escalamiento['nombre_destinatario'] . " (" . $escalamiento['cargo_destinatario'] . ")";
    $stmt = $pdo->prepare("
        INSERT INTO historial_tickets (ticket_id, usuario_id, rol, accion, fecha) 
        VALUES (?, ?, 'sistema', ?, NOW())
    ");
    $stmt->execute([$escalamiento['ticket_id'], $escalamiento['usuario_id'], $accion_historial]);
    
    // Si es aprobado, actualizar el estado del ticket
    if ($accion === 'aprobar') {
        $stmt = $pdo->prepare("UPDATE tickets SET estado = 'en_proceso' WHERE id = ?");
        $stmt->execute([$escalamiento['ticket_id']]);
    }
    
    $pdo->commit();
    
    // Mostrar página de confirmación
    $mensaje_exito = ($accion === 'aprobar') ? 
        "✅ Solicitud APROBADA exitosamente" : 
        "❌ Solicitud RECHAZADA";
    
    $color_estado = ($accion === 'aprobar') ? '#28a745' : '#dc3545';
    $icono_estado = ($accion === 'aprobar') ? '✅' : '❌';
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Error procesando aprobación: " . $e->getMessage());
    $error = "Error procesando la solicitud: " . $e->getMessage();
    include 'error_aprobacion.php';
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuesta de Escalamiento - Sistema de Tickets</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            max-width: 600px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .status-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .status-title {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .status-message {
            color: #6c757d;
            font-size: 1.2rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .ticket-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .ticket-info h3 {
            color: #495057;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #6c757d;
        }
        
        .info-value {
            flex: 1;
            color: #495057;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #357abd, #4a90e2);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 144, 226, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #6c757d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-icon" style="color: <?php echo $color_estado; ?>;">
            <?php echo $icono_estado; ?>
        </div>
        
        <h1 class="status-title" style="color: <?php echo $color_estado; ?>;">
            <?php echo $mensaje_exito; ?>
        </h1>
        
        <p class="status-message">
            Su respuesta ha sido registrada exitosamente en el sistema de tickets.
            El solicitante será notificado automáticamente de su decisión.
        </p>
        
        <div class="ticket-info">
            <h3>📋 Detalles del Ticket</h3>
            <div class="info-row">
                <div class="info-label">Ticket ID:</div>
                <div class="info-value">#<?php echo $escalamiento['ticket_id']; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Solicitante:</div>
                <div class="info-value"><?php echo htmlspecialchars($escalamiento['usuario_nombre'] ?: $escalamiento['usuario']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tipo de solicitud:</div>
                <div class="info-value"><?php echo htmlspecialchars($escalamiento['tipo_solicitud']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de escalamiento:</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($escalamiento['fecha_escalamiento'])); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de respuesta:</div>
                <div class="info-value"><?php echo date('d/m/Y H:i'); ?></div>
            </div>
        </div>
        
        <div class="actions">
            <a href="mailto:<?php echo $escalamiento['email_destinatario']; ?>" class="btn btn-primary">
                <i class="fas fa-envelope"></i>
                Enviar Correo de Seguimiento
            </a>
            <a href="javascript:window.close();" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Cerrar Ventana
            </a>
        </div>
        
        <div class="footer">
            <p>Esta respuesta fue procesada automáticamente por el Sistema de Tickets.</p>
            <p><small>Token: <?php echo substr($token, 0, 8); ?>...</small></p>
        </div>
    </div>
</body>
</html>
