<?php
/**
 * Configuración para el sistema de correos
 */

// Configuración SMTP
define('SMTP_HOST', 'smtp.gmail.com'); // Cambiar por tu servidor SMTP
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'tu_email@gmail.com'); // Cambiar por tu email
define('SMTP_PASSWORD', 'tu_password_app'); // Cambiar por tu contraseña de aplicación
define('SMTP_ENCRYPTION', 'tls');

// Configuración del remitente
define('FROM_EMAIL', 'sistema.tickets@empresa.com');
define('FROM_NAME', 'Sistema de Tickets');

// Configuración de la empresa
define('EMPRESA_NOMBRE', 'Tu Empresa');
define('EMPRESA_LOGO_URL', 'http://localhost:3000/assets/logo.png');

// Tipos de solicitud disponibles para escalamiento
$tipos_solicitud = [
    'cambio_horario' => 'Cambio de Horario',
    'incidencia_critica' => 'Incidencia Crítica',
    'aprobacion_especial' => 'Aprobación Especial',
    'solicitud_gerencial' => 'Solicitud Gerencial',
    'escalamiento_general' => 'Escalamiento General'
];

// Plantillas de asunto por tipo de solicitud
$asuntos_plantilla = [
    'cambio_horario' => '[URGENTE] Solicitud de Cambio de Horario - Ticket #{ticket_id}',
    'incidencia_critica' => '[CRÍTICO] Incidencia Requiere Atención Inmediata - Ticket #{ticket_id}',
    'aprobacion_especial' => '[APROBACIÓN] Solicitud Requiere Autorización Superior - Ticket #{ticket_id}',
    'solicitud_gerencial' => '[GERENCIAL] Solicitud para Revisión Gerencial - Ticket #{ticket_id}',
    'escalamiento_general' => '[ESCALAMIENTO] Ticket Requiere Atención Superior - Ticket #{ticket_id}'
];

/**
 * Función para enviar correo usando PHPMailer
 */
function enviarCorreoEscalamiento($destinatario, $asunto, $mensaje_html, $mensaje_texto = '') {
    // Verificar si PHPMailer está disponible
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Si no está disponible, usar mail() nativo de PHP
        return enviarCorreoNativo($destinatario, $asunto, $mensaje_html);
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Remitente
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        
        // Destinatario
        $mail->addAddress($destinatario);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje_html;
        if ($mensaje_texto) {
            $mail->AltBody = $mensaje_texto;
        }
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error enviando correo: " . $e->getMessage());
        return false;
    }
}

/**
 * Función alternativa usando mail() nativo de PHP
 */
function enviarCorreoNativo($destinatario, $asunto, $mensaje_html) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($destinatario, $asunto, $mensaje_html, $headers);
}

/**
 * Generar plantilla HTML para correo de escalamiento
 */
function generarPlantillaCorreo($datos_ticket, $historial_conversacion, $datos_escalamiento, $token_aprobacion = null) {
    $html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Escalamiento de Ticket</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px; }
            .header h1 { margin: 0; font-size: 24px; }
            .header p { margin: 5px 0 0 0; opacity: 0.9; }
            .ticket-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .ticket-info h3 { margin-top: 0; color: #495057; }
            .info-row { display: flex; margin-bottom: 8px; }
            .info-label { font-weight: bold; width: 150px; color: #6c757d; }
            .info-value { flex: 1; }
            .conversacion { margin-top: 20px; }
            .mensaje { background: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin-bottom: 15px; border-radius: 0 5px 5px 0; }
            .mensaje-header { font-weight: bold; color: #495057; margin-bottom: 8px; }
            .mensaje-fecha { color: #6c757d; font-size: 0.9em; }
            .mensaje-contenido { margin-top: 10px; }
            .urgente { border-left-color: #dc3545; background: #fff5f5; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; text-align: center; color: #6c757d; font-size: 0.9em; }
            .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
            .btn:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🚨 Escalamiento de Ticket</h1>
                <p>Sistema de Tickets - ' . EMPRESA_NOMBRE . '</p>
            </div>
            
            <div class="ticket-info">
                <h3>📋 Información del Ticket</h3>
                <div class="info-row">
                    <div class="info-label">Ticket ID:</div>
                    <div class="info-value">#' . $datos_ticket['id'] . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Estado:</div>
                    <div class="info-value">' . ucfirst($datos_ticket['estado']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Departamento:</div>
                    <div class="info-value">' . htmlspecialchars($datos_ticket['departamento']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Categoría:</div>
                    <div class="info-value">' . htmlspecialchars($datos_ticket['categoria']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Creado por:</div>
                    <div class="info-value">' . htmlspecialchars($datos_ticket['creador_nombre']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha creación:</div>
                    <div class="info-value">' . date('d/m/Y H:i', strtotime($datos_ticket['fecha_creacion'])) . '</div>
                </div>
            </div>
            
            <div class="ticket-info">
                <h3>📝 Descripción del Ticket</h3>
                <div class="mensaje-contenido">' . nl2br(htmlspecialchars($datos_ticket['descripcion'])) . '</div>
            </div>
            
            <div class="conversacion">
                <h3>💬 Historial de Conversación</h3>';
    
    // Agregar cada mensaje del historial
    foreach ($historial_conversacion as $mensaje) {
        $clase_urgente = ($mensaje['rol'] === 'cliente' && strpos(strtolower($mensaje['mensaje']), 'urgente') !== false) ? 'urgente' : '';
        $html .= '
                <div class="mensaje ' . $clase_urgente . '">
                    <div class="mensaje-header">
                        ' . ($mensaje['rol'] === 'cliente' ? '👤 Cliente' : '👨‍💼 Usuario Asignado') . '
                    </div>
                    <div class="mensaje-fecha">' . date('d/m/Y H:i', strtotime($mensaje['fecha_respuesta'])) . '</div>
                    <div class="mensaje-contenido">' . nl2br(htmlspecialchars($mensaje['mensaje'])) . '</div>
                </div>';
    }
    
    $html .= '
            </div>
            
            <div class="ticket-info">
                <h3>📧 Información del Escalamiento</h3>
                <div class="info-row">
                    <div class="info-label">Tipo de solicitud:</div>
                    <div class="info-value">' . htmlspecialchars($datos_escalamiento['tipo_solicitud']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Escalado por:</div>
                    <div class="info-value">' . htmlspecialchars($datos_escalamiento['usuario_nombre']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha escalamiento:</div>
                    <div class="info-value">' . date('d/m/Y H:i') . '</div>
                </div>';
    
    if (!empty($datos_escalamiento['mensaje_personalizado'])) {
        $html .= '
                <div class="info-row">
                    <div class="info-label">Mensaje adicional:</div>
                    <div class="info-value">' . nl2br(htmlspecialchars($datos_escalamiento['mensaje_personalizado'])) . '</div>
                </div>';
    }
    
    $html .= '
            </div>
            
            <div class="ticket-info">
                <h3>⚡ Acción Requerida</h3>
                <p>Por favor, revise la información anterior y tome una decisión sobre esta solicitud:</p>';
    
    // Agregar botones de aprobación si hay token
    if ($token_aprobacion) {
        $base_url = 'http://localhost:3000'; // Cambiar por tu dominio
        $html .= '
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $base_url . '/aprobar_escalamiento.php?token=' . $token_aprobacion . '&accion=aprobar" 
                       class="btn btn-success" style="background: #28a745; margin-right: 15px;">
                        ✅ APROBAR SOLICITUD
                    </a>
                    <a href="' . $base_url . '/aprobar_escalamiento.php?token=' . $token_aprobacion . '&accion=rechazar" 
                       class="btn btn-danger" style="background: #dc3545;">
                        ❌ RECHAZAR SOLICITUD
                    </a>
                </div>
                <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px;">
                    <p style="margin: 0; color: #1976d2; font-size: 0.9em;">
                        <strong>💡 Nota:</strong> Al hacer clic en cualquiera de los botones, su decisión será registrada automáticamente 
                        en el sistema y notificada al solicitante. No es necesario responder este correo.
                    </p>
                </div>';
    }
    
    $html .= '
            </div>
            
            <div class="footer">
                <p>Este correo fue generado automáticamente por el Sistema de Tickets.</p>
                <p>Para responder, por favor contacte directamente con el solicitante o acceda al sistema.</p>';
    
    if ($token_aprobacion) {
        $html .= '<p><small>Token de seguridad: ' . substr($token_aprobacion, 0, 8) . '...</small></p>';
    }
    
    $html .= '
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>
