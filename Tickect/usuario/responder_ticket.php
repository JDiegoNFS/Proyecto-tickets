<?php
require_once '../includes/auth.php';
verificarRol('usuario');
require_once '../includes/db.php';
require_once '../includes/funciones_mensajes.php';

if (!isset($_GET['ticket_id'])) {
    die("ID de ticket no especificado.");
}

$ticket_id = intval($_GET['ticket_id']);
$usuario_id = $_SESSION['usuario_id'];

// Validar que el ticket le pertenece al usuario (usuario_id es el usuario asignado)
$stmtValidar = $pdo->prepare("SELECT id, descripcion, categoria_id, departamento_id, estado, usuario_id, cliente_id FROM tickets WHERE id = ? AND usuario_id = ?");
$stmtValidar->execute([$ticket_id, $usuario_id]);
$ticket = $stmtValidar->fetch();

if (!$ticket) {
    die("No tienes permiso para ver este ticket.");
}

// Obtener datos de la categoría y departamento
$stmtDetalles = $pdo->prepare("SELECT c.nombre AS categoria, d.nombre AS departamento FROM categorias c JOIN departamentos d ON d.id = ? WHERE c.id = ?");
$stmtDetalles->execute([$ticket['departamento_id'], $ticket['categoria_id']]);
$detalles = $stmtDetalles->fetch();

// Obtener información del creador del ticket
$creadorTicket = null;
if ($ticket['cliente_id']) {
    $stmtCreador = $pdo->prepare("SELECT usuario, nombre FROM usuarios WHERE id = ?");
    $stmtCreador->execute([$ticket['cliente_id']]);
    $creadorTicket = $stmtCreador->fetch();
}

// Procesar el envío de respuesta, cierre de ticket o reasignación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si se está reasignando el ticket
    if (isset($_POST['reasignar_ticket'])) {
        $nuevo_usuario_id = intval($_POST['nuevo_usuario_id']);
        
        if ($nuevo_usuario_id && $nuevo_usuario_id != $usuario_id) {
            // Obtener información del usuario actual y nuevo
            $stmtUsuarioActual = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ?");
            $stmtUsuarioActual->execute([$usuario_id]);
            $usuarioActual = $stmtUsuarioActual->fetch();
            
            $stmtNuevoUsuario = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ? AND departamento_id = ?");
            $stmtNuevoUsuario->execute([$nuevo_usuario_id, $ticket['departamento_id']]);
            $nuevoUsuario = $stmtNuevoUsuario->fetch();
            
            if ($nuevoUsuario) {
                // Reasignar el ticket
                $stmt = $pdo->prepare("UPDATE tickets SET usuario_id = ? WHERE id = ?");
                $stmt->execute([$nuevo_usuario_id, $ticket_id]);
                
                // Registrar en el historial
                $historial = $pdo->prepare("INSERT INTO historial_tickets (ticket_id, usuario_id, rol, accion, fecha) VALUES (?, ?, 'usuario', ?, NOW())");
                $accion = "Ticket reasignado de '{$usuarioActual['usuario']}' a '{$nuevoUsuario['usuario']}'";
                $historial->execute([$ticket_id, $usuario_id, $accion]);
                
                // Crear mensaje automático en el chat
                $mensajeReasignacion = "🔄 **REASIGNACIÓN DE TICKET**\n\nEste ticket ha sido reasignado de **{$usuarioActual['usuario']}** a **{$nuevoUsuario['usuario']}**.\n\nEl nuevo responsable del ticket es: **{$nuevoUsuario['usuario']}**";
                $stmtMensaje = $pdo->prepare("INSERT INTO respuestas (ticket_id, usuario_id, mensaje, fecha_respuesta, rol) VALUES (?, ?, ?, NOW(), 'sistema')");
                $stmtMensaje->execute([$ticket_id, $usuario_id, $mensajeReasignacion]);
                
                header("Location: ver_tickets.php?mensaje=ticket_reasignado");
                exit();
            } else {
                header("Location: responder_ticket.php?ticket_id=$ticket_id&error=usuario_no_valido");
                exit();
            }
        } else {
            header("Location: responder_ticket.php?ticket_id=$ticket_id&error=usuario_invalido");
            exit();
        }
    }
    
    // Verificar si se está cerrando el ticket
    if (isset($_POST['cerrar_ticket'])) {
        $stmt = $pdo->prepare("UPDATE tickets SET estado = 'cerrado', fecha_cierre = NOW() WHERE id = ?");
        $stmt->execute([$ticket_id]);
        
        $historial = $pdo->prepare("INSERT INTO historial_tickets (ticket_id, usuario_id, rol, accion, fecha) VALUES (?, ?, 'usuario', 'Usuario cerró el ticket', NOW())");
        $historial->execute([$ticket_id, $usuario_id]);
        
        header("Location: ver_tickets.php?mensaje=ticket_cerrado");
        exit();
    }
    
    $mensaje = trim($_POST['mensaje']);

    if (!empty($mensaje)) {
        $stmt = $pdo->prepare("INSERT INTO respuestas (ticket_id, usuario_id, mensaje, fecha_respuesta, rol) VALUES (?, ?, ?, NOW(), 'usuario')");
        $stmt->execute([$ticket_id, $usuario_id, $mensaje]);
        $respuesta_id = $pdo->lastInsertId();

        if (!empty($_FILES['archivos']['name'][0])) {
            $directorio = "../uploads/";
            if (!is_dir($directorio)) {
                mkdir($directorio, 0777, true);
            }

            $permitidos = [
                'image/jpeg', 'image/png', 'application/pdf', 'text/plain',
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

        $historial = $pdo->prepare("INSERT INTO historial_tickets (ticket_id, usuario_id, rol, accion, fecha) VALUES (?, ?, 'usuario', 'Usuario respondió el ticket', NOW())");
        $historial->execute([$ticket_id, $usuario_id]);

        header("Location: responder_ticket.php?ticket_id=$ticket_id");
        exit();
    }
}

// Obtener todas las respuestas del ticket
$stmtMensajes = $pdo->prepare("SELECT r.id, r.mensaje, r.fecha_respuesta, r.usuario_id, u.usuario, r.rol FROM respuestas r JOIN usuarios u ON r.usuario_id = u.id WHERE r.ticket_id = ? ORDER BY r.fecha_respuesta ASC");
$stmtMensajes->execute([$ticket_id]);
$respuestas = $stmtMensajes->fetchAll();

// Obtener usuarios del mismo departamento para reasignación
$stmtUsuarios = $pdo->prepare("SELECT id, usuario FROM usuarios WHERE departamento_id = ? AND rol = 'usuario' AND id != ? ORDER BY usuario ASC");
$stmtUsuarios->execute([$ticket['departamento_id'], $usuario_id]);
$usuariosDisponibles = $stmtUsuarios->fetchAll();

// Obtener mensajes de estado
$mensaje_estado = '';
$error_estado = '';

if (isset($_GET['mensaje'])) {
    switch ($_GET['mensaje']) {
        case 'ticket_reasignado':
            $mensaje_estado = 'Ticket reasignado exitosamente.';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'usuario_no_valido':
            $error_estado = 'El usuario seleccionado no pertenece al mismo departamento.';
            break;
        case 'usuario_invalido':
            $error_estado = 'No puedes reasignar el ticket a ti mismo.';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?php echo $ticket['id']; ?> - Chat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style_cliente_responder.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="page-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-left">
                    <div class="breadcrumb">
                        <a href="ver_tickets.php" class="breadcrumb-link">
                            <i class="fas fa-arrow-left"></i>
                            Volver a Tickets
                        </a>
                    </div>
                    <h1 class="page-title">
                        <i class="fas fa-comments"></i>
                        Ticket #<?php echo $ticket['id']; ?>
                    </h1>
                    <p class="page-subtitle">Conversación y gestión del ticket</p>
                </div>
                <div class="header-right">
                    <?php if ($ticket['estado'] !== 'cerrado' && !empty($usuariosDisponibles)): ?>
                    <button type="button" class="btn btn-warning" onclick="mostrarModalReasignar()" style="margin-right: 10px;">
                        <i class="fas fa-user-exchange"></i>
                        Reasignar Ticket
                    </button>
                    <?php endif; ?>
                    <?php if ($ticket['estado'] !== 'cerrado'): ?>
                    <button type="button" class="btn btn-danger" onclick="mostrarModalCerrar()" style="margin-right: 10px;">
                        <i class="fas fa-lock"></i>
                        Cerrar Ticket
                    </button>
                    <?php endif; ?>
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

        <!-- Ticket Info Card -->
        <div class="ticket-info-card">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Categoría</div>
                        <div class="info-value"><?php echo htmlspecialchars($detalles['categoria']); ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Departamento</div>
                        <div class="info-value"><?php echo htmlspecialchars($detalles['departamento']); ?></div>
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
                                <?php echo ucfirst($ticket['estado']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Creado por</div>
                        <div class="info-value">
                            <?php if ($creadorTicket): ?>
                                <span class="usuario-creador">
                                    <i class="fas fa-user-edit"></i>
                                    <?php echo htmlspecialchars($creadorTicket['nombre'] ?? $creadorTicket['usuario']); ?>
                                </span>
                            <?php else: ?>
                                <span class="sin-creador">
                                    <i class="fas fa-question-circle"></i>
                                    Sin creador
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="ticket-description">
                <h3><i class="fas fa-align-left"></i> Descripción del Ticket</h3>
                <div class="description-content scrollable-description">
                    <?php 
                    echo mostrarDescripcionCompleta($ticket['descripcion']); 
                    ?>
                </div>
            </div>
        </div>

        <!-- Chat Container -->
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
                        $is_own_message = ($respuesta['rol'] === 'usuario' && $respuesta['usuario_id'] == $usuario_id);
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
                                
                                <?php
                                $stmtArchivos = $pdo->prepare("SELECT nombre_archivo, ruta_archivo FROM archivos WHERE respuesta_id = ?");
                                $stmtArchivos->execute([$respuesta['id']]);
                                $archivos = $stmtArchivos->fetchAll();
                                
                                if (!empty($archivos)): ?>
                                    <div class="message-attachments">
                                        <div class="attachments-title">
                                            <i class="fas fa-paperclip"></i>
                                            Archivos adjuntos
                                        </div>
                                        <div class="attachments-list">
                                            <?php foreach ($archivos as $archivo): ?>
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
        </div>
    
        <?php if ($ticket['estado'] !== 'cerrado'): ?>
        <!-- Chat Input Section -->
        <div class="chat-input-section">
            <form method="post" enctype="multipart/form-data" class="chat-form">
                <div class="input-group">
                    <label class="form-label">
                        <i class="fas fa-comment-dots"></i>
                        Escribe tu respuesta
                    </label>
                    <textarea name="mensaje" class="form-textarea" 
                              placeholder="Escribe tu mensaje aquí..." 
                              required></textarea>
                </div>
                
                <div class="file-upload-section">
                    <label for="archivos" class="file-upload-label">
                        <i class="fas fa-paperclip"></i>
                        Adjuntar archivos
                    </label>
                    <input type="file" id="archivos" name="archivos[]" multiple class="file-input" 
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt,.zip,.rar">
                    <div class="file-info">
                        <div id="file-count">Ningún archivo seleccionado</div>
                        <div class="file-types">Tipos permitidos: PDF, DOC, XLS, JPG, PNG, TXT, ZIP</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-enviar">
                        <i class="fas fa-paper-plane"></i>
                        Enviar Respuesta
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="ticket-cerrado">
            <i class="fas fa-lock"></i>
            <p><strong>Este ticket está cerrado.</strong> No puedes enviar más respuestas.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Confirmación Moderno -->
    <div id="modalCerrarTicket" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="modal-title">Confirmar Cierre de Ticket</h3>
                <button class="modal-close" onclick="cerrarModalCerrar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <p class="modal-message">
                    ¿Estás seguro de que quieres cerrar este ticket?
                </p>
                <div class="modal-warning">
                    <i class="fas fa-info-circle"></i>
                    <span>Una vez cerrado, no podrás enviar más respuestas a este ticket.</span>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="cerrarModalCerrar()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="button" class="btn-modal btn-confirm" onclick="confirmarCerrarTicket()">
                    <i class="fas fa-lock"></i>
                    Sí, Cerrar Ticket
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Reasignación -->
    <div id="modalReasignarTicket" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-user-exchange"></i>
                </div>
                <h3 class="modal-title">Reasignar Ticket</h3>
                <button class="modal-close" onclick="cerrarModalReasignar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <p class="modal-message">
                    Selecciona el usuario al que quieres reasignar este ticket:
                </p>
                
                <form method="post" id="formReasignar">
                    <div class="form-group">
                        <label for="nuevo_usuario_id" class="form-label">
                            <i class="fas fa-users"></i>
                            Nuevo Responsable
                        </label>
                        <select name="nuevo_usuario_id" id="nuevo_usuario_id" class="form-select" required>
                            <option value="">Selecciona un usuario...</option>
                            <?php foreach ($usuariosDisponibles as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>">
                                    <?php echo htmlspecialchars($usuario['usuario']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="modal-warning">
                        <i class="fas fa-info-circle"></i>
                        <span>El ticket será reasignado al usuario seleccionado y se creará un mensaje automático en el chat.</span>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="cerrarModalReasignar()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="button" class="btn-modal btn-confirm" onclick="confirmarReasignarTicket()">
                    <i class="fas fa-user-exchange"></i>
                    Reasignar Ticket
                </button>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of chat
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // File upload handling
        const fileInput = document.getElementById('archivos');
        const fileCount = document.getElementById('file-count');
        
        if (fileInput && fileCount) {
            fileInput.addEventListener('change', function() {
                const files = this.files;
                if (files.length > 0) {
                    fileCount.textContent = `${files.length} archivo(s) seleccionado(s)`;
                } else {
                    fileCount.textContent = 'Ningún archivo seleccionado';
                }
            });
        }

        // Form validation
        const form = document.querySelector('.chat-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const mensaje = document.querySelector('textarea[name="mensaje"]');
                if (!mensaje.value.trim()) {
                    e.preventDefault();
                    alert('Por favor, escribe un mensaje antes de enviar.');
                    mensaje.focus();
                }
            });
        }

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

        // Función para simular notificación de mensaje entrante
        function simulateIncomingMessage() {
            const chatContainer = document.querySelector('.chat-messages');
            if (chatContainer) {
                chatContainer.style.animation = 'pulse 0.5s ease-out';
                setTimeout(() => {
                    chatContainer.style.animation = '';
                }, 500);
            }
        }

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

        // Funciones para el modal de confirmación
        function mostrarModalCerrar() {
            const modal = document.getElementById('modalCerrarTicket');
            
            // Mostrar el modal
            modal.style.display = 'flex';
            modal.style.opacity = '0';
            modal.style.transform = 'scale(0.8)';
            
            // Animación de entrada suave
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.transform = 'scale(1)';
            }, 50);
        }

        function cerrarModalCerrar() {
            const modal = document.getElementById('modalCerrarTicket');
            modal.style.opacity = '0';
            modal.style.transform = 'scale(0.8)';
            
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function confirmarCerrarTicket() {
            // Crear un formulario temporal para enviar la acción de cerrar
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'cerrar_ticket';
            input.value = '1';
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        // Cerrar modal al hacer clic fuera de él
        document.getElementById('modalCerrarTicket').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalCerrar();
            }
        });

        document.getElementById('modalReasignarTicket').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalReasignar();
            }
        });

        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalCerrar();
                cerrarModalReasignar();
            }
        });

        // Funciones para el modal de reasignación
        function mostrarModalReasignar() {
            const modal = document.getElementById('modalReasignarTicket');
            
            // Mostrar el modal
            modal.style.display = 'flex';
            modal.style.opacity = '0';
            modal.style.transform = 'scale(0.8)';
            
            // Animación de entrada suave
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.transform = 'scale(1)';
            }, 50);
        }

        function cerrarModalReasignar() {
            const modal = document.getElementById('modalReasignarTicket');
            modal.style.opacity = '0';
            modal.style.transform = 'scale(0.8)';
            
            setTimeout(() => {
                modal.style.display = 'none';
                // Limpiar el formulario
                document.getElementById('nuevo_usuario_id').value = '';
            }, 300);
        }

        function confirmarReasignarTicket() {
            const selectUsuario = document.getElementById('nuevo_usuario_id');
            const usuarioSeleccionado = selectUsuario.value;
            
            if (!usuarioSeleccionado) {
                alert('Por favor, selecciona un usuario para reasignar el ticket.');
                selectUsuario.focus();
                return;
            }
            
            const nombreUsuario = selectUsuario.options[selectUsuario.selectedIndex].text;
            
            if (confirm(`¿Estás seguro de que quieres reasignar este ticket a "${nombreUsuario}"?`)) {
                // Crear un formulario temporal para enviar la acción de reasignar
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const inputReasignar = document.createElement('input');
                inputReasignar.type = 'hidden';
                inputReasignar.name = 'reasignar_ticket';
                inputReasignar.value = '1';
                
                const inputUsuario = document.createElement('input');
                inputUsuario.type = 'hidden';
                inputUsuario.name = 'nuevo_usuario_id';
                inputUsuario.value = usuarioSeleccionado;
                
                form.appendChild(inputReasignar);
                form.appendChild(inputUsuario);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Función para centrar el modal en la vista del usuario
        function centrarModalEnVista() {
            const modal = document.getElementById('modalCerrarTicket');
            if (modal && modal.style.display === 'flex') {
                const viewportHeight = window.innerHeight;
                const modalHeight = modal.offsetHeight;
                
                // Si el modal es más alto que la vista, ajustar la posición
                if (modalHeight > viewportHeight - 40) {
                    modal.style.alignItems = 'flex-start';
                    modal.style.paddingTop = '20px';
                } else {
                    modal.style.alignItems = 'center';
                    modal.style.paddingTop = '0';
                }
            }
        }

        // Ajustar el modal cuando cambie el tamaño de la ventana
        window.addEventListener('resize', centrarModalEnVista);

        // Asegurar que los modales estén correctamente configurados al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const modalCerrar = document.getElementById('modalCerrarTicket');
            const modalReasignar = document.getElementById('modalReasignarTicket');
            
            if (modalCerrar) {
                // Asegurar que el modal de cerrar esté oculto inicialmente
                modalCerrar.style.display = 'none';
                modalCerrar.style.opacity = '0';
                modalCerrar.style.transform = 'scale(0.8)';
            }
            
            if (modalReasignar) {
                // Asegurar que el modal de reasignar esté oculto inicialmente
                modalReasignar.style.display = 'none';
                modalReasignar.style.opacity = '0';
                modalReasignar.style.transform = 'scale(0.8)';
            }
        });
    </script>

    <!-- Estilos CSS para el Modal Moderno -->
    <style>
        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: flex-start;
            z-index: 10000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            overflow-x: hidden;
            padding: 50px 20px 20px 20px;
            box-sizing: border-box;
        }

        /* Modal Container */
        .modal-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            max-width: 450px;
            width: calc(100% - 40px);
            max-height: 90vh;
            overflow: hidden;
            transform: scale(0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            margin: 0 auto;
            position: relative;
            flex-shrink: 0;
            box-sizing: border-box;
        }

        /* Modal Header */
        .modal-header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 25px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            border: none;
            border-radius: 20px 20px 0 0;
        }

        .modal-header * {
            color: white !important;
            background: none !important;
        }

        .modal-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2) !important;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            backdrop-filter: blur(10px);
        }

        .modal-title {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
            flex: 1;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            background: none;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white !important;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            transform: scale(1.1);
        }

        /* Modal Body */
        .modal-body {
            padding: 30px;
            text-align: center;
        }

        .modal-message {
            font-size: 1.1rem;
            color: #333;
            margin: 0 0 20px 0;
            line-height: 1.6;
        }

        .modal-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffeaa7;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #856404;
            font-size: 0.95rem;
            margin-top: 20px;
        }

        .modal-warning i {
            color: #f39c12;
            font-size: 1.1rem;
        }

        /* Modal Footer */
        .modal-footer {
            padding: 20px 30px 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-modal {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 140px;
            justify-content: center;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }

        .btn-confirm {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        }

        /* Animaciones */
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(-50px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes modalSlideOut {
            from {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
            to {
                opacity: 0;
                transform: scale(0.8) translateY(-50px);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modal-overlay {
                padding: 20px 15px;
                align-items: flex-start;
                padding-top: 30px;
            }
            
            .modal-container {
                width: calc(100% - 30px);
                max-width: none;
                margin: 0;
                max-height: calc(100vh - 60px);
                overflow-y: auto;
            }
            
            .modal-header {
                padding: 20px;
            }
            
            .modal-body {
                padding: 25px 20px;
            }
            
            .modal-footer {
                padding: 15px 20px 25px;
                flex-direction: column;
            }
            
            .btn-modal {
                width: 100%;
            }
        }

        /* Para pantallas muy pequeñas */
        @media (max-width: 480px) {
            .modal-overlay {
                padding: 10px;
                padding-top: 20px;
            }
            
            .modal-container {
                width: calc(100% - 20px);
                max-width: none;
                border-radius: 15px;
            }
            
            .modal-header {
                padding: 15px;
            }
            
            .modal-body {
                padding: 20px 15px;
            }
            
            .modal-footer {
                padding: 10px 15px 20px;
            }
        }

        /* Efectos de hover mejorados */
        .btn-modal:active {
            transform: translateY(0) scale(0.98);
        }

        /* Mejoras de accesibilidad */
        .modal-overlay:focus-within .modal-container {
            outline: 2px solid #007bff;
            outline-offset: 2px;
        }

        /* Animación de pulso para el icono */
        .modal-icon i {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Estilos para el botón de cerrar en el header */
        .header-right .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        .header-right .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
            background: linear-gradient(135deg, #c82333, #bd2130);
        }

        .header-right .btn-danger:active {
            transform: translateY(0);
        }

        /* Asegurar centrado perfecto del modal */
        .modal-overlay {
            display: none;
            flex-direction: column;
            align-items: center;
        }

        .modal-overlay[style*="display: flex"] {
            justify-content: flex-start;
            padding-top: 50px;
        }

        /* Estilos para el modal de reasignación */
        #modalReasignarTicket .modal-header {
            background: linear-gradient(135deg, #ffc107, #e0a800);
        }

        #modalReasignarTicket .modal-icon {
            background: rgba(255, 255, 255, 0.2) !important;
        }

        #modalReasignarTicket .btn-confirm {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }

        #modalReasignarTicket .btn-confirm:hover {
            background: linear-gradient(135deg, #e0a800, #d39e00);
        }

        /* Estilos para el formulario de reasignación */
        .form-group {
            margin: 20px 0;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: #ffc107;
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.1);
        }

        /* Estilos para el botón de reasignar en el header */
        .header-right .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }

        .header-right .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
            background: linear-gradient(135deg, #e0a800, #d39e00);
        }

        .header-right .btn-warning:active {
            transform: translateY(0);
        }

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

        /* Cerrar modal al hacer clic fuera de él */
        #modalReasignarTicket .modal-overlay {
            cursor: pointer;
        }

        #modalReasignarTicket .modal-container {
            cursor: default;
        }
    </style>
</body>
</html>
