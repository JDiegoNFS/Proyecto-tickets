<?php
/**
 * Funciones para manejar escalamientos de tickets
 */

require_once 'db.php';
require_once 'config_correos.php';

/**
 * Obtener destinatarios disponibles para un tipo de solicitud
 */
function obtenerDestinatariosEscalamiento($tipo_solicitud) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id, email_destinatario, nombre_destinatario, cargo_destinatario 
        FROM escalamiento_destinatarios 
        WHERE tipo_solicitud = ? AND activo = 1 
        ORDER BY nombre_destinatario
    ");
    $stmt->execute([$tipo_solicitud]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener todos los tipos de solicitud disponibles
 */
function obtenerTiposSolicitud() {
    global $tipos_solicitud;
    return $tipos_solicitud;
}

/**
 * Generar token único para aprobación
 */
function generarTokenAprobacion() {
    return bin2hex(random_bytes(32));
}

/**
 * Registrar escalamiento en la base de datos
 */
function registrarEscalamiento($ticket_id, $usuario_id, $tipo_solicitud, $email_destinatario, $nombre_destinatario, $cargo_destinatario, $asunto, $mensaje_personalizado = '') {
    global $pdo;
    
    try {
        $token_aprobacion = generarTokenAprobacion();
        
        $stmt = $pdo->prepare("
            INSERT INTO escalamientos 
            (ticket_id, usuario_id, tipo_solicitud, email_destinatario, nombre_destinatario, cargo_destinatario, asunto, mensaje_personalizado, token_aprobacion, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
        ");
        
        $stmt->execute([
            $ticket_id, 
            $usuario_id, 
            $tipo_solicitud, 
            $email_destinatario, 
            $nombre_destinatario, 
            $cargo_destinatario, 
            $asunto, 
            $mensaje_personalizado,
            $token_aprobacion
        ]);
        
        return [
            'id' => $pdo->lastInsertId(),
            'token' => $token_aprobacion
        ];
    } catch (Exception $e) {
        error_log("Error registrando escalamiento: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener datos completos del ticket para el correo
 */
function obtenerDatosTicketCompleto($ticket_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            t.id, t.descripcion, t.estado, t.fecha_creacion,
            c.nombre as categoria,
            d.nombre as departamento,
            u_creador.usuario as creador_usuario,
            u_creador.nombre as creador_nombre
        FROM tickets t
        LEFT JOIN categorias c ON t.categoria_id = c.id
        LEFT JOIN departamentos d ON t.departamento_id = d.id
        LEFT JOIN usuarios u_creador ON t.cliente_id = u_creador.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtener historial completo de conversación del ticket
 */
function obtenerHistorialConversacion($ticket_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            r.mensaje, r.fecha_respuesta, r.rol,
            u.usuario, u.nombre
        FROM respuestas r
        LEFT JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.ticket_id = ?
        ORDER BY r.fecha_respuesta ASC
    ");
    $stmt->execute([$ticket_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Procesar escalamiento completo
 */
function procesarEscalamiento($ticket_id, $usuario_id, $tipo_solicitud, $destinatario_id, $mensaje_personalizado = '') {
    global $pdo, $asuntos_plantilla;
    
    try {
        $pdo->beginTransaction();
        
        // Obtener datos del destinatario
        $stmt = $pdo->prepare("
            SELECT email_destinatario, nombre_destinatario, cargo_destinatario 
            FROM escalamiento_destinatarios 
            WHERE id = ? AND activo = 1
        ");
        $stmt->execute([$destinatario_id]);
        $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$destinatario) {
            throw new Exception("Destinatario no encontrado o inactivo");
        }
        
        // Obtener datos del usuario que hace el escalamiento
        $stmt = $pdo->prepare("SELECT usuario, nombre FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener datos del ticket
        $datos_ticket = obtenerDatosTicketCompleto($ticket_id);
        if (!$datos_ticket) {
            throw new Exception("Ticket no encontrado");
        }
        
        // Obtener historial de conversación
        $historial = obtenerHistorialConversacion($ticket_id);
        
        // Generar asunto
        $asunto = str_replace('{ticket_id}', $ticket_id, $asuntos_plantilla[$tipo_solicitud] ?? $asuntos_plantilla['escalamiento_general']);
        
        // Preparar datos para la plantilla
        $datos_escalamiento = [
            'tipo_solicitud' => $tipo_solicitud,
            'usuario_nombre' => $usuario['nombre'] ?: $usuario['usuario'],
            'mensaje_personalizado' => $mensaje_personalizado
        ];
        
        // Registrar escalamiento primero para obtener el token
        $escalamiento_result = registrarEscalamiento(
            $ticket_id, 
            $usuario_id, 
            $tipo_solicitud, 
            $destinatario['email_destinatario'], 
            $destinatario['nombre_destinatario'], 
            $destinatario['cargo_destinatario'], 
            $asunto, 
            $mensaje_personalizado
        );
        
        if (!$escalamiento_result) {
            throw new Exception("Error registrando escalamiento");
        }
        
        $escalamiento_id = $escalamiento_result['id'];
        $token_aprobacion = $escalamiento_result['token'];
        
        // Generar plantilla HTML con token
        $mensaje_html = generarPlantillaCorreo($datos_ticket, $historial, $datos_escalamiento, $token_aprobacion);
        
        // Enviar correo
        $correo_enviado = enviarCorreoEscalamiento(
            $destinatario['email_destinatario'], 
            $asunto, 
            $mensaje_html
        );
        
        // Actualizar estado del escalamiento
        $estado = $correo_enviado ? 'enviado' : 'error';
        $stmt = $pdo->prepare("UPDATE escalamientos SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $escalamiento_id]);
        
        // Registrar en historial del ticket
        $accion = "Ticket escalado a " . $destinatario['nombre_destinatario'] . " (" . $destinatario['cargo_destinatario'] . ")";
        $stmt = $pdo->prepare("
            INSERT INTO historial_tickets (ticket_id, usuario_id, rol, accion, fecha) 
            VALUES (?, ?, 'cliente', ?, NOW())
        ");
        $stmt->execute([$ticket_id, $usuario_id, $accion]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'escalamiento_id' => $escalamiento_id,
            'correo_enviado' => $correo_enviado,
            'destinatario' => $destinatario
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error procesando escalamiento: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Obtener escalamientos de un ticket
 */
function obtenerEscalamientosTicket($ticket_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            u.usuario, u.nombre as usuario_nombre
        FROM escalamientos e
        LEFT JOIN usuarios u ON e.usuario_id = u.id
        WHERE e.ticket_id = ?
        ORDER BY e.fecha_escalamiento DESC
    ");
    $stmt->execute([$ticket_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Procesar respuesta de aprobación desde correo
 */
function procesarRespuestaAprobacion($token, $accion) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
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
            throw new Exception("Token no válido o escalamiento ya procesado");
        }
        
        // Verificar que no haya pasado mucho tiempo (30 días)
        $fecha_escalamiento = new DateTime($escalamiento['fecha_escalamiento']);
        $fecha_actual = new DateTime();
        $diferencia = $fecha_actual->diff($fecha_escalamiento);
        
        if ($diferencia->days > 30) {
            throw new Exception("Este enlace ha expirado (más de 30 días)");
        }
        
        // Procesar la aprobación/rechazo
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
        
        return [
            'success' => true,
            'escalamiento' => $escalamiento,
            'accion' => $accion
        ];
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error procesando aprobación: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
