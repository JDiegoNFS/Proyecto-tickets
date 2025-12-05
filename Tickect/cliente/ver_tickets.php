<?php
// cliente/ver_tickets.php - Versión corregida
require_once '../includes/auth.php';
verificarRol('cliente');
require_once '../includes/db.php';
require_once '../includes/funciones_mensajes.php';
require_once '../includes/funciones_jerarquias.php';

$usuario_id = $_SESSION['usuario_id'];

// Manejar mensajes de confirmación
$mensaje = "";
if (isset($_GET['mensaje'])) {
    switch ($_GET['mensaje']) {
        case 'ticket_creado':
            $ticket_id = isset($_GET['ticket_id']) ? $_GET['ticket_id'] : '';
            $mensaje = "✅ ¡Ticket creado exitosamente! ID: #$ticket_id";
            break;
        case 'ticket_editado':
            $mensaje = "✅ ¡Ticket actualizado correctamente!";
            break;
        case 'ticket_cerrado':
            $mensaje = "✅ ¡Ticket cerrado exitosamente!";
            break;
    }
}

// Parámetros de paginación y filtros
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$estado_filter = isset($_GET['estado']) ? $_GET['estado'] : '';
$tickets_per_page = 15;

// Obtener información del usuario
$usuario = obtenerJerarquiaUsuario($usuario_id);

if (!$usuario) {
    $error = "No se pudo obtener la información del usuario.";
    $tickets = [];
    $total_tickets = 0;
    $total_pages = 0;
    $mis_tickets_pendientes = 0;
    $mis_tickets_asignados = 0;
    $mis_tickets_cerrados = 0;
} else {
    // Verificar que el usuario tenga una jerarquía asignada
    if (empty($usuario['jerarquia'])) {
        $error = "No tienes una jerarquía asignada. Contacta al administrador.";
        $tickets = [];
        $total_tickets = 0;
        $total_pages = 0;
        $mis_tickets_pendientes = 0;
        $mis_tickets_asignados = 0;
        $mis_tickets_cerrados = 0;
    } else {
        $error = "";

        // Obtener total general SIN filtros (para mostrar siempre)
        $tickets_sin_filtro = obtenerTicketsVisibles($usuario_id, []);
        $total_tickets_general = $tickets_sin_filtro['total'];
        
        // Usar las funciones de jerarquías para obtener tickets visibles CON filtros
        $filtros = [];
        if ($estado_filter) {
            $filtros['estado'] = $estado_filter;
        }

        $tickets_visibles = obtenerTicketsVisibles($usuario_id, $filtros);
        $total_tickets = $tickets_visibles['total'];
        $total_pages = ceil($total_tickets / $tickets_per_page);

        // Aplicar paginación a los tickets visibles
        $offset = ($page - 1) * $tickets_per_page;
        $tickets = array_slice($tickets_visibles['tickets'], $offset, $tickets_per_page);

        // Obtener estadísticas usando las funciones de jerarquías
        $estadisticas = obtenerEstadisticasTickets($usuario_id);
        $mis_tickets_pendientes = $estadisticas['pendientes'];
        $mis_tickets_asignados = $estadisticas['en_proceso'];
        $mis_tickets_cerrados = $estadisticas['cerrados'];
    }
}

// Función para obtener icono del estado
function getEstadoIcon($estado) {
    switch ($estado) {
        case 'pendiente':
            return 'clock';
        case 'en_proceso':
            return 'cogs';
        case 'cerrado':
            return 'check-circle';
        default:
            return 'question';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tickets - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_cliente_ver_tickets.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/themes.css">
    <link rel="stylesheet" href="../css/global-theme-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="page-container">
        <div class="header-section">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="page-title">
                        <i class="fas fa-ticket-alt"></i>
                        Mis Tickets (<?php echo $total_tickets_general; ?>)
                    </h1>
                    <p class="page-subtitle">Gestiona y da seguimiento a tus solicitudes - <?php echo htmlspecialchars($usuario['nombre'] ?? $usuario['usuario']); ?></p>
                </div>
                <div class="header-right">
                    <a href="crear_ticket.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Nuevo Ticket
                    </a>
                    <a href="../logout.php" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar sesión
                    </a>
                </div>
            </div>
        </div>

        <!-- Mensaje de confirmación -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success" id="alert-message">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($mensaje); ?></span>
                <button type="button" class="alert-close" onclick="closeAlert()" title="Cerrar mensaje">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Dashboard de estadísticas -->
        <div class="stats-dashboard">
            <a href="?" class="stat-card stat-todos stat-filter <?php echo $estado_filter === '' ? 'active' : ''; ?>" data-filter="all">
                <div class="stat-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_tickets; ?></div>
                    <div class="stat-label">Todos</div>
                </div>
            </a>
            
            <a href="?estado=pendiente" class="stat-card stat-pendiente stat-filter <?php echo $estado_filter === 'pendiente' ? 'active' : ''; ?>" data-filter="pendiente">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $mis_tickets_pendientes; ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
            </a>
            
            <a href="?estado=en_proceso" class="stat-card stat-en-proceso stat-filter <?php echo $estado_filter === 'en_proceso' ? 'active' : ''; ?>" data-filter="en_proceso">
                <div class="stat-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $mis_tickets_asignados; ?></div>
                    <div class="stat-label">En Proceso</div>
                </div>
            </a>
            
            <a href="?estado=cerrado" class="stat-card stat-cerrado stat-filter <?php echo $estado_filter === 'cerrado' ? 'active' : ''; ?>" data-filter="cerrado">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $mis_tickets_cerrados; ?></div>
                    <div class="stat-label">Cerrados</div>
                </div>
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #f5c6cb;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>



        <div class="tickets-container">
            <?php if (count($tickets) === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>No tienes tickets registrados</h3>
                    <p>Comienza creando tu primera solicitud para obtener ayuda</p>
                    <a href="crear_ticket.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Crear mi primer ticket
                    </a>
                </div>
            <?php else: ?>
                
                <div class="table-container">
                    <table class="tickets-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Asunto</th>
                                <th>Departamento</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Usuario Creador</th>
                                <th>Usuario Asignado</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                                                <tr class="ticket-row ticket-<?php echo $ticket['estado']; ?>">
                                    <td class="ticket-id">
                                        <span class="ticket-number">#<?php echo $ticket['id']; ?></span>
                                    </td>
                                    <td class="ticket-asunto">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($ticket['categoria_nombre'] ?? 'Sin categoría'); ?>
                                    </td>
                                    <td class="ticket-departamento">
                                        <i class="fas fa-building"></i>
                                        <?php echo htmlspecialchars($ticket['departamento_nombre'] ?? 'Sin departamento'); ?>
                                    </td>
                                    <td class="ticket-descripcion" data-full="<?php 
                                        require_once '../includes/funciones_mensajes.php';
                                        echo htmlspecialchars(limpiarMensajeParaVista($ticket['descripcion'])); 
                                    ?>">
                                        <div class="descripcion-content">
                                            <?php echo mostrarDescripcionSimple($ticket['descripcion']); ?>
                                        </div>
                                        <div class="descripcion-tooltip">
                                            <?php 
                                            require_once '../includes/funciones_mensajes.php';
                                            echo limpiarMensajeParaVista($ticket['descripcion']); 
                                            ?>
                                        </div>
                                    </td>
                                    <td class="ticket-estado">
                                        <span class="estado-badge estado-<?php echo $ticket['estado']; ?>" 
                                              style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; <?php 
                                              switch($ticket['estado']) {
                                                  case 'pendiente':
                                                      echo 'background-color: #fff3cd; color: #856404;';
                                                      break;
                                                  case 'en_proceso':
                                                      echo 'background-color: #cce7ff; color: #004085;';
                                                      break;
                                                  case 'cerrado':
                                                      echo 'background-color: #d4edda; color: #155724;';
                                                      break;
                                                  default:
                                                      echo 'background-color: #f8f9fa; color: #6c757d;';
                                              }
                                              ?>">
                                            <i class="fas fa-<?php echo getEstadoIcon($ticket['estado']); ?>"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['estado'])); ?>
                                        </span>
                                    </td>
                                    <td class="ticket-creador">
                                        <?php if ($ticket['creador_nombre']): ?>
                                            <span class="usuario-creador">
                                                <i class="fas fa-user-plus"></i>
                                                <?php echo htmlspecialchars($ticket['creador_nombre']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="sin-creador">
                                                <i class="fas fa-question-circle"></i>
                                                Sin creador
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="ticket-usuario">
                                        <?php if ($ticket['asignado_nombre']): ?>
                                            <span class="usuario-asignado">
                                                <i class="fas fa-user-cog"></i>
                                                <?php echo htmlspecialchars($ticket['asignado_nombre']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="sin-asignar">
                                                <i class="fas fa-user-clock"></i>
                                                Sin asignar
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="ticket-fecha">
                                        <div class="fecha-info">
                                            <div class="fecha-dia"><?php echo date('d/m/Y', strtotime($ticket['fecha_creacion'])); ?></div>
                                            <div class="fecha-hora"><?php echo date('H:i', strtotime($ticket['fecha_creacion'])); ?></div>
                                        </div>
                                    </td>
                                    <td class="ticket-acciones">
                                        <div class="acciones-buttons">
                                            <?php if ($ticket['estado'] === 'pendiente'): ?>
                                                <a href="editar_ticket.php?ticket_id=<?php echo $ticket['id']; ?>" 
                                                   class="btn-action btn-editar" 
                                                   title="Editar ticket">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($ticket['estado'] === 'en_proceso' || $ticket['estado'] === 'cerrado'): ?>
                                                <a href="responder_ticket.php?ticket_id=<?php echo $ticket['id']; ?>" 
                                                   class="btn-action btn-responder" 
                                                   title="Responder ticket">
                                                    <i class="fas fa-reply"></i>
                                                </a>
                                                <a href="../usuario/ver_historial.php?ticket_id=<?php echo $ticket['id']; ?>" 
                                                   class="btn-action btn-historial" 
                                                   title="Ver historial">
                                                    <i class="fas fa-history"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Mostrando <?php echo (($page - 1) * $tickets_per_page) + 1; ?>-<?php echo min($page * $tickets_per_page, $total_tickets); ?> de <?php echo $total_tickets; ?> tickets
                    </div>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-btn">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="pagination-btn">1</a>
                        <?php if ($start_page > 2): ?>
                        <span class="pagination-dots">...</span>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                        <span class="pagination-dots">...</span>
                        <?php endif; ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="pagination-btn"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-btn">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sistema de Temas -->
    <script src="../js/theme-manager.js"></script>
    
    <script src="../js/script_cliente_ver_tickets.js"></script>
    <script src="../js/script_carga_pantalla.js"></script>
    
    <script>
        // Configuración específica para página de tickets de cliente
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar título con tema actual
            const updatePageTitle = () => {
                const currentTheme = themeManager.getCurrentTheme();
                const themeName = themeManager.getAvailableThemes()[currentTheme].name;
                document.title = `Mis Tickets - Tema ${themeName}`;
            };
            
            setTimeout(updatePageTitle, 100);
            document.addEventListener('themeChanged', updatePageTitle);
        });
    </script>
    
    <script>
        // Función para cerrar alerta manualmente
        function closeAlert() {
            const alert = document.getElementById('alert-message');
            if (alert) {
                alert.style.animation = 'slideOutUp 0.3s ease-out';
                
                setTimeout(function() {
                    alert.style.display = 'none';
                    // Limpiar la URL para remover los parámetros del mensaje
                    if (window.history.replaceState) {
                        const url = new URL(window.location);
                        url.searchParams.delete('mensaje');
                        url.searchParams.delete('ticket_id');
                        window.history.replaceState({}, document.title, url.pathname + url.search);
                    }
                }, 300); // Esperar a que termine la animación
            }
        }

        // Auto-ocultar mensajes de alerta después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            
            alerts.forEach(function(alert) {
                // Mostrar la alerta con animación
                alert.style.display = 'flex';
                
                // Ocultar después de 5 segundos
                setTimeout(function() {
                    if (alert.style.display !== 'none') { // Solo si no fue cerrado manualmente
                        alert.style.animation = 'slideOutUp 0.3s ease-out';
                        
                        setTimeout(function() {
                            alert.style.display = 'none';
                            // Limpiar la URL para remover los parámetros del mensaje
                            if (window.history.replaceState) {
                                const url = new URL(window.location);
                                url.searchParams.delete('mensaje');
                                url.searchParams.delete('ticket_id');
                                window.history.replaceState({}, document.title, url.pathname + url.search);
                            }
                        }, 300); // Esperar a que termine la animación
                    }
                }, 5000); // 5 segundos
            });
        });
    </script>
</body>
</html>


