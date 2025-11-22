<?php
require_once '../includes/auth.php';
verificarRol('cliente');
require_once '../includes/db.php';
require_once '../includes/funciones_mensajes.php';
require_once '../includes/funciones_jerarquias.php';
require_once '../includes/escalamiento.php';

if (!isset($_GET['ticket_id'])) {
    die("ID de ticket no especificado.");
}

$ticket_id = intval($_GET['ticket_id']);
$usuario_id = $_SESSION['usuario_id'];

// Validar permisos usando jerarquías
if (!puedeVerTicket($usuario_id, $ticket_id)) {
    $mensaje_error = "No tienes permiso para ver este ticket.";
    include 'error_template.php';
    exit();
}

// Obtener ticket
$stmtValidar = $pdo->prepare("SELECT id, descripcion, categoria_id, departamento_id, estado, fecha_cierre, usuario_id, cliente_id FROM tickets WHERE id = ?");
$stmtValidar->execute([$ticket_id]);
$ticket = $stmtValidar->fetch();

if (!$ticket) {
    $mensaje_error = "Ticket no encontrado.";
    include 'error_template.php';
    exit();
}

// Obtener datos de la categoría y departamento
$stmtDetalles = $pdo->prepare("
    SELECT c.nombre AS categoria, d.nombre AS departamento
    FROM categorias c, departamentos d
    WHERE c.id = ? AND d.id = ?
");
$stmtDetalles->execute([$ticket['categoria_id'], $ticket['departamento_id']]);
$detalles = $stmtDetalles->fetch();

// Obtener información del usuario asignado al ticket
$usuarioAsignado = null;
if ($ticket['usuario_id']) {
    $stmtUsuarioAsignado = $pdo->prepare("SELECT usuario, nombre FROM usuarios WHERE id = ?");
    $stmtUsuarioAsignado->execute([$ticket['usuario_id']]);
    $usuarioAsignado = $stmtUsuarioAsignado->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si se está escalando el ticket
    if (isset($_POST['escalar_ticket'])) {
        $tipo_solicitud = $_POST['tipo_solicitud'] ?? '';
        $destinatario_id = $_POST['destinatario_id'] ?? '';
        $mensaje_personalizado = $_POST['mensaje_personalizado'] ?? '';
        
        if (empty($tipo_solicitud) || empty($destinatario_id)) {
            $error_estado = "Por favor, selecciona el tipo de solicitud y destinatario";
        } else {
            $resultado = procesarEscalamiento($ticket_id, $usuario_id, $tipo_solicitud, $destinatario_id, $mensaje_personalizado);
            
            if ($resultado['success']) {
                $mensaje_estado = "✅ Escalamiento enviado exitosamente a " . $resultado['destinatario']['nombre_destinatario'];
            } else {
                $error_estado = "❌ Error al enviar escalamiento: " . $resultado['error'];
            }
        }
    }
    // Verificar si se está reabriendo el ticket
    elseif (isset($_POST['reabrir_ticket'])) {
        // Verificar que el ticket esté cerrado y dentro del plazo de 1 día
        if ($ticket['estado'] === 'cerrado' && $ticket['fecha_cierre']) {
            $fecha_cierre = new DateTime($ticket['fecha_cierre']);
            $fecha_actual = new DateTime();
            $diferencia = $fecha_actual->diff($fecha_cierre);
            
            // Verificar si han pasado menos de 24 horas
            if ($diferencia->days < 1) {
                // Reabrir el ticket
                $stmt = $pdo->prepare("UPDATE tickets SET estado = 'en_proceso' WHERE id = ?");
                $stmt->execute([$ticket_id]);
                
                // Registrar en el historial
                $historial = $pdo->prepare("INSERT INTO historial_tickets (ticket_id, usuario_id, rol, accion, fecha) VALUES (?, ?, 'cliente', 'Cliente reabrió el ticket', NOW())");
                $historial->execute([$ticket_id, $usuario_id]);
                
                header("Location: responder_ticket.php?ticket_id=$ticket_id&mensaje=reabierto");
                exit();
            } else {
                // Ticket cerrado definitivamente
                header("Location: responder_ticket.php?ticket_id=$ticket_id&error=cerrado_definitivamente");
                exit();
            }
        }
    }
    
    $mensaje = $_POST['mensaje'];

    if (!empty($mensaje)) {
        $stmt = $pdo->prepare("INSERT INTO respuestas (ticket_id, usuario_id, mensaje, fecha_respuesta, rol) VALUES (?, ?, ?, NOW(), 'cliente')");
        $stmt->execute([$ticket_id, $usuario_id, $mensaje]);
        $respuesta_id = $pdo->lastInsertId();

        // Subir archivos
        if (!empty($_FILES['archivos']['name'][0])) {
            $directorio = "../uploads/";
            if (!is_dir($directorio)) {
                mkdir($directorio, 0777, true);
            }

            $permitidos = [
                'image/jpeg', 'image/png',
                'application/pdf', 'text/plain',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip', 'application/x-rar-compressed'
            ];

            foreach ($_FILES['archivos']['name'] as $i => $nombre) {
                $tmp = $_FILES['archivos']['tmp_name'][$i];
                $tipo = $_FILES['archivos']['type'][$i];
                $nombre_limpio = basename($nombre);
                $ruta = $directorio . uniqid() . "_" . $nombre_limpio;

                if (in_array($tipo, $permitidos)) {
                    if (move_uploaded_file($tmp, $ruta)) {
                        $ruta_db = str_replace("../", "", $ruta);
                        $stmtArchivo = $pdo->prepare("INSERT INTO archivos (respuesta_id, nombre_archivo, ruta_archivo) VALUES (?, ?, ?)");
                        $stmtArchivo->execute([$respuesta_id, $nombre_limpio, $ruta_db]);
                    }
                }
            }
        }

        $historial = $pdo->prepare("INSERT INTO historial_tickets (ticket_id, usuario_id, rol, accion, fecha) VALUES (?, ?, 'cliente', 'Cliente respondió el ticket', NOW())");
        $historial->execute([$ticket_id, $usuario_id]);

        header("Location: responder_ticket.php?ticket_id=$ticket_id");
        exit();
    }
}

// Obtener respuestas
$stmtMensajes = $pdo->prepare("
    SELECT r.id, r.mensaje, r.fecha_respuesta, r.usuario_id, u.usuario, r.rol
    FROM respuestas r
    JOIN usuarios u ON r.usuario_id = u.id
    WHERE r.ticket_id = ?
    ORDER BY r.fecha_respuesta ASC
");
$stmtMensajes->execute([$ticket_id]);
$respuestas = $stmtMensajes->fetchAll();

foreach ($respuestas as &$respuesta) {
    $stmtArchivos = $pdo->prepare("SELECT nombre_archivo, ruta_archivo FROM archivos WHERE respuesta_id = ?");
    $stmtArchivos->execute([$respuesta['id']]);
    $respuesta['archivos'] = $stmtArchivos->fetchAll();
}
unset($respuesta);

// Verificar si el ticket puede ser reabierto
$puede_reabrir = false;
$cerrado_definitivamente = false;
$tiempo_restante = null;

if ($ticket['estado'] === 'cerrado' && $ticket['fecha_cierre']) {
    $fecha_cierre = new DateTime($ticket['fecha_cierre']);
    $fecha_actual = new DateTime();
    $diferencia = $fecha_actual->diff($fecha_cierre);
    
    if ($diferencia->days < 1) {
        $puede_reabrir = true;
        // Calcular tiempo restante
        $horas_restantes = 24 - ($diferencia->h + ($diferencia->days * 24));
        $minutos_restantes = 60 - $diferencia->i;
        if ($minutos_restantes == 60) {
            $minutos_restantes = 0;
            $horas_restantes++;
        }
        $tiempo_restante = $horas_restantes . "h " . $minutos_restantes . "m";
    } else {
        $cerrado_definitivamente = true;
    }
}

// Obtener mensajes de estado
$mensaje_estado = '';
$error_estado = '';

if (isset($_GET['mensaje'])) {
    switch ($_GET['mensaje']) {
        case 'reabierto':
            $mensaje_estado = 'Ticket reabierto exitosamente.';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'cerrado_definitivamente':
            $error_estado = 'Este ticket ha sido cerrado definitivamente. No se puede reabrir.';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responder Ticket #<?php echo $ticket_id; ?> - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_cliente_responder.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="page-container">
        <div class="header-section">
            <div class="header-content">
                <div class="header-left">
                    <div class="breadcrumb">
                        <a href="ver_tickets.php" class="breadcrumb-link">
                            <i class="fas fa-arrow-left"></i> Volver a Mis Tickets
                        </a>
                    </div>
                    <h1 class="page-title">
                        <i class="fas fa-comments"></i>
                        Ticket #<?php echo $ticket_id; ?>
                    </h1>
                    <p class="page-subtitle">Conversación y seguimiento del ticket</p>
                </div>
                <div class="header-right">
                    <a href="ver_tickets.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i>
                        Ver Todos
                    </a>
                </div>
            </div>
        </div>

        <!-- Mensajes de estado -->
        <?php if ($mensaje_estado): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($mensaje_estado); ?>
        </div>
        <?php endif; ?>

        <?php if ($error_estado): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error_estado); ?>
        </div>
        <?php endif; ?>

        <div class="ticket-info-card">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Categoría</div>
                        <div class="info-value"><?php echo htmlspecialchars($detalles['categoria'] ?? ''); ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Departamento</div>
                        <div class="info-value"><?php echo htmlspecialchars($detalles['departamento'] ?? ''); ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Estado</div>
                        <div class="info-value">
                            <span class="estado-badge estado-<?php echo $ticket['estado']; ?>">
                                <i class="fas fa-<?php echo getEstadoIcon($ticket['estado']); ?>"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $ticket['estado'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Usuario Asignado</div>
                        <div class="info-value">
                            <?php if ($usuarioAsignado): ?>
                                <span class="usuario-asignado">
                                    <i class="fas fa-user-check"></i>
                                    <?php echo htmlspecialchars($usuarioAsignado['nombre'] ?? $usuarioAsignado['usuario']); ?>
                                </span>
                            <?php else: ?>
                                <span class="sin-asignar">
                                    <i class="fas fa-user-clock"></i>
                                    Sin asignar
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ticket-description">
            <h3><i class="fas fa-align-left"></i> Descripción del Ticket</h3>
            <div class="description-content scrollable-description">
                <?php 
                echo mostrarDescripcionCompleta($ticket['descripcion'] ?? ''); 
                ?>
            </div>
        </div>

        <div class="chat-container">
            <div class="chat-header">
                <h3><i class="fas fa-comments"></i> Historial de Conversación</h3>
                <div class="chat-stats">
                    <span class="stat-item">
                        <i class="fas fa-message"></i>
                        <?php echo count($respuestas); ?> mensajes
                    </span>
                </div>
            </div>

            <div class="chat-messages">
                <?php if (empty($respuestas)): ?>
                    <div class="empty-chat">
                        <div class="empty-icon">
                            <i class="fas fa-comment-slash"></i>
                        </div>
                        <h4>No hay mensajes aún</h4>
                        <p>Se el primero en iniciar la conversación</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($respuestas as $respuesta): ?>
                        <?php 
                        // Determinar si el mensaje es del usuario actual o de otro
                        $is_own_message = ($respuesta['rol'] === 'cliente' && $respuesta['usuario_id'] == $usuario_id);
                        ?>
                        <div class="message-wrapper <?php echo $is_own_message ? 'own-message' : 'other-message'; ?>">
                            <div class="message-bubble" data-rol="<?php echo $respuesta['rol']; ?>">
                                <div class="message-header">
                                    <div class="message-author">
                                        <i class="fas fa-<?php echo $respuesta['rol'] === 'cliente' ? 'user' : 'user-tie'; ?>"></i>
                                        <?php echo htmlspecialchars($respuesta['usuario']); ?>
                                        <span class="message-role">(<?php echo ucfirst($respuesta['rol']); ?>)</span>
                                    </div>
                                    <div class="message-time">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date("d/m/Y H:i", strtotime($respuesta['fecha_respuesta'])); ?>
                                    </div>
                                </div>
                                
                                <div class="message-text">
                                    <?php 
                                    require_once '../includes/funciones_mensajes.php';
                                    echo procesarMensaje($respuesta['mensaje']); 
                                    ?>
                                </div>
                                
                                <?php if (!empty($respuesta['archivos'])): ?>
                                    <div class="message-attachments">
                                        <div class="attachments-title">
                                            <i class="fas fa-paperclip"></i>
                                            Archivos adjuntos
                                        </div>
                                        <div class="attachments-list">
                                            <?php foreach ($respuesta['archivos'] as $archivo): ?>
                                                <a href="../<?php echo htmlspecialchars($archivo['ruta_archivo']); ?>" 
                                                   target="_blank" 
                                                   class="attachment-item">
                                                    <i class="fas fa-file"></i>
                                                    <span class="attachment-name">
                                                        <?php echo htmlspecialchars($archivo['nombre_archivo']); ?>
                                                    </span>
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Indicador de estado del mensaje -->
                                <div class="message-status">
                                    <i class="fas fa-check-double"></i>
                                    <span>Enviado</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($ticket['estado'] === 'cerrado'): ?>
                <!-- Ticket cerrado - Mostrar opciones de reabrir -->
                <div class="ticket-cerrado-section">
                    <?php if ($puede_reabrir): ?>
                        <div class="cerrado-info">
                            <div class="cerrado-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="cerrado-content">
                                <h3>Ticket Cerrado</h3>
                                <p>Este ticket está cerrado. Puedes reabrirlo dentro de las próximas <strong><?php echo $tiempo_restante; ?></strong>.</p>
                                <form method="post" style="display: inline;">
                                    <button type="submit" name="reabrir_ticket" class="btn btn-warning" onclick="return confirm('¿Estás seguro de que quieres reabrir este ticket?')">
                                        <i class="fas fa-unlock"></i>
                                        Reabrir Ticket
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php elseif ($cerrado_definitivamente): ?>
                        <div class="cerrado-info cerrado-definitivo">
                            <div class="cerrado-icon">
                                <i class="fas fa-ban"></i>
                            </div>
                            <div class="cerrado-content">
                                <h3>Ticket Cerrado Definitivamente</h3>
                                <p>Este ticket ha sido cerrado definitivamente. Han pasado más de 24 horas desde su cierre y ya no se puede reabrir.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Ticket activo - Mostrar formulario de respuesta -->
                <div class="chat-input-section">
                    <form method="post" enctype="multipart/form-data" class="chat-form">
                        <div class="input-group">
                            <label for="mensaje" class="form-label">
                                <i class="fas fa-edit"></i>
                                Tu respuesta
                            </label>
                            <textarea 
                                name="mensaje" 
                                id="mensaje" 
                                rows="4" 
                                required 
                                class="form-textarea"
                                placeholder="Escribe tu respuesta aquí..."
                            ></textarea>
                        </div>

                        <div class="file-upload-section">
                            <label for="archivos" class="file-upload-label">
                                <i class="fas fa-paperclip"></i>
                                Adjuntar archivos
                            </label>
                            <input type="file" 
                                   name="archivos[]" 
                                   id="archivos" 
                                   multiple 
                                   class="file-input"
                                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
                            <div class="file-info">
                                <span id="file-count">Sin archivos seleccionados</span>
                                <div class="file-types">
                                    Tipos permitidos: JPG, PNG, PDF, DOC, XLS, TXT, ZIP, RAR
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Enviar Respuesta
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i>
                                Limpiar
                            </button>
                            <button type="button" class="btn btn-warning" onclick="abrirModalEscalamiento()">
                                <i class="fas fa-arrow-up"></i>
                                Escalar Ticket
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Contador de archivos seleccionados
        document.getElementById('archivos').addEventListener('change', function() {
            const fileCount = this.files.length;
            const fileCountSpan = document.getElementById('file-count');
            
            if (fileCount === 0) {
                fileCountSpan.textContent = 'Sin archivos seleccionados';
            } else if (fileCount === 1) {
                fileCountSpan.textContent = `1 archivo seleccionado`;
            } else {
                fileCountSpan.textContent = `${fileCount} archivos seleccionados`;
            }
        });

        // Validación del formulario
        document.querySelector('.chat-form').addEventListener('submit', function(e) {
            const mensaje = document.getElementById('mensaje').value.trim();
            
            if (!mensaje) {
                e.preventDefault();
                alert('Por favor, escribe un mensaje antes de enviar.');
                return false;
            }
            
            if (mensaje.length < 5) {
                e.preventDefault();
                alert('El mensaje debe tener al menos 5 caracteres.');
                return false;
            }
        });

        // Auto-resize del textarea
        const textarea = document.getElementById('mensaje');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 200) + 'px';
        });

        // Auto-scroll to bottom of chat
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Funciones para el modal de escalamiento
        function abrirModalEscalamiento() {
            document.getElementById('modalEscalamiento').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalEscalamiento() {
            document.getElementById('modalEscalamiento').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('modalEscalamiento');
            if (event.target === modal) {
                cerrarModalEscalamiento();
            }
        }

        // Cargar destinatarios cuando cambie el tipo de solicitud
        document.getElementById('tipo_solicitud').addEventListener('change', function() {
            const tipoSolicitud = this.value;
            const destinatarioSelect = document.getElementById('destinatario_id');
            const options = destinatarioSelect.querySelectorAll('option[data-tipo]');
            
            // Ocultar todos los destinatarios
            options.forEach(option => {
                option.style.display = 'none';
            });
            
            if (!tipoSolicitud) {
                destinatarioSelect.innerHTML = '<option value="">-- Selecciona un destinatario --</option>';
                return;
            }
            
            // Mostrar solo los destinatarios del tipo seleccionado
            let destinatariosEncontrados = 0;
            options.forEach(option => {
                if (option.getAttribute('data-tipo') === tipoSolicitud) {
                    option.style.display = 'block';
                    destinatariosEncontrados++;
                }
            });
            
            // Si no hay destinatarios, mostrar mensaje
            if (destinatariosEncontrados === 0) {
                destinatarioSelect.innerHTML = '<option value="">No hay destinatarios disponibles para este tipo</option>';
            }
        });

        // Validación del formulario de escalamiento
        document.getElementById('formEscalamiento').addEventListener('submit', function(e) {
            const tipoSolicitud = document.getElementById('tipo_solicitud').value;
            const destinatario = document.getElementById('destinatario_id').value;
            
            if (!tipoSolicitud || !destinatario) {
                e.preventDefault();
                alert('Por favor, completa todos los campos obligatorios.');
                return false;
            }
            
            if (!confirm('¿Estás seguro de que quieres escalar este ticket? Se enviará un correo al destinatario seleccionado.')) {
                e.preventDefault();
                return false;
            }
        });

        // Efectos de notificación para mensajes
        function addMessageNotification() {
            const messages = document.querySelectorAll('.message-wrapper');
            const lastMessage = messages[messages.length - 1];
            
            if (lastMessage) {
                // Agregar clase de nuevo mensaje
                lastMessage.classList.add('new-message');
                
                // Crear efecto de sonido visual (vibración del chat)
                if (chatMessages) {
                    chatMessages.style.animation = 'pulse 0.3s ease-out';
                    setTimeout(() => {
                        chatMessages.style.animation = '';
                    }, 300);
                }
                
                // Auto-scroll suave al nuevo mensaje
                setTimeout(() => {
                    lastMessage.scrollIntoView({ behavior: 'smooth', block: 'end' });
                }, 100);
            }
        }

        // Detectar si hay un nuevo mensaje (comparar con mensajes anteriores)
        function checkForNewMessages() {
            const currentMessageCount = document.querySelectorAll('.message-wrapper').length;
            const storedCount = localStorage.getItem('messageCount_<?php echo $ticket_id; ?>') || 0;
            
            if (currentMessageCount > storedCount) {
                addMessageNotification();
                localStorage.setItem('messageCount_<?php echo $ticket_id; ?>', currentMessageCount);
            }
        }

        // Ejecutar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            checkForNewMessages();
            
            // Agregar efecto hover a los mensajes
            const messageBubbles = document.querySelectorAll('.message-bubble');
            messageBubbles.forEach(bubble => {
                bubble.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                bubble.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });

        // Auto-refresh cada 30 segundos para detectar nuevos mensajes
        setInterval(function() {
            // Solo si la página está visible
            if (!document.hidden) {
                location.reload();
            }
        }, 30000);

        // Función para toggle de imágenes
        function toggleImage(button) {
            const imageContent = button.parentElement.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (imageContent.style.display === 'none' || imageContent.style.display === '') {
                imageContent.style.display = 'block';
                icon.className = 'fas fa-compress';
                button.title = 'Ocultar imagen';
            } else {
                imageContent.style.display = 'none';
                icon.className = 'fas fa-expand';
                button.title = 'Mostrar imagen';
            }
        }
    </script>

    <!-- Estilos CSS para los nuevos elementos -->
    <style>
        /* Alertas de estado */
        .alert {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Sección de ticket cerrado */
        .ticket-cerrado-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .cerrado-info {
            display: flex;
            align-items: center;
            gap: 20px;
            text-align: left;
        }

        .cerrado-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .cerrado-info .cerrado-icon {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }

        .cerrado-definitivo .cerrado-icon {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .cerrado-content h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.3rem;
        }

        .cerrado-content p {
            margin: 0 0 15px 0;
            color: #666;
            line-height: 1.5;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
            background: linear-gradient(135deg, #e0a800, #d39e00);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .cerrado-info {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .cerrado-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }

            .cerrado-content h3 {
                font-size: 1.1rem;
            }
        }
        
        /* Estilos para el modal de escalamiento */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.5rem;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, #e0a800, #ffc107);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.3);
        }
    </style>
    
    <!-- Modal de Escalamiento -->
    <div id="modalEscalamiento" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-arrow-up"></i> Escalar Ticket</h3>
                <span class="close" onclick="cerrarModalEscalamiento()">&times;</span>
            </div>
            
            <form method="post" id="formEscalamiento">
                <div class="form-group">
                    <label for="tipo_solicitud">
                        <i class="fas fa-tag"></i> Tipo de Solicitud <span style="color: #e74c3c;">*</span>
                    </label>
                    <select name="tipo_solicitud" id="tipo_solicitud" required>
                        <option value="">-- Selecciona el tipo de solicitud --</option>
                        <?php 
                        $tipos = obtenerTiposSolicitud();
                        foreach ($tipos as $key => $nombre): 
                        ?>
                            <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="destinatario_id">
                        <i class="fas fa-user-tie"></i> Destinatario <span style="color: #e74c3c;">*</span>
                    </label>
                    <select name="destinatario_id" id="destinatario_id" required>
                        <option value="">-- Selecciona un destinatario --</option>
                        <?php 
                        // Cargar todos los destinatarios disponibles
                        try {
                            $stmt = $pdo->query("
                                SELECT id, tipo_solicitud, nombre_destinatario, cargo_destinatario 
                                FROM escalamiento_destinatarios 
                                WHERE activo = 1 
                                ORDER BY tipo_solicitud, nombre_destinatario
                            ");
                            $todos_destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($todos_destinatarios as $dest) {
                                echo '<option value="' . $dest['id'] . '" data-tipo="' . $dest['tipo_solicitud'] . '" style="display: none;">';
                                echo htmlspecialchars($dest['nombre_destinatario'] . ' (' . $dest['cargo_destinatario'] . ')');
                                echo '</option>';
                            }
                        } catch (Exception $e) {
                            echo '<option value="">Error al cargar destinatarios</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="mensaje_personalizado">
                        <i class="fas fa-comment"></i> Mensaje Adicional (Opcional)
                    </label>
                    <textarea name="mensaje_personalizado" id="mensaje_personalizado" 
                              placeholder="Agrega cualquier información adicional que consideres importante para el destinatario..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalEscalamiento()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="escalar_ticket" class="btn btn-warning">
                        <i class="fas fa-paper-plane"></i> Enviar Escalamiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php
function getEstadoIcon($estado) {
    switch ($estado) {
        case 'pendiente':
            return 'clock';
        case 'abierto':
            return 'folder-open';
        case 'en_proceso':
            return 'cogs';
        case 'cerrado':
            return 'check-circle';
        default:
            return 'question';
    }
}
?>