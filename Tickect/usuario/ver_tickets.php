<?php
require_once '../includes/auth.php';
// Permitir tanto usuarios como clientes con jerarquías
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['usuario', 'cliente'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../includes/db.php';
require_once '../includes/funciones_mensajes.php';
require_once '../includes/funciones_jerarquias.php';

$usuario_id = $_SESSION['usuario_id'];

// Parámetros de paginación y filtros
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$estado_filter = isset($_GET['estado']) ? $_GET['estado'] : '';
$tickets_per_page = 15;

// Obtener información del usuario y su jerarquía
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
    // Verificar que el usuario tenga una jerarquía asignada (solo para clientes)
    if ($usuario['rol'] === 'cliente' && empty($usuario['jerarquia'])) {
        $error = "No tienes una jerarquía asignada. Contacta al administrador.";
        $tickets = [];
        $total_tickets = 0;
        $total_pages = 0;
        $mis_tickets_pendientes = 0;
        $mis_tickets_asignados = 0;
        $mis_tickets_cerrados = 0;
    } else {
        $error = "";

        // Usar las funciones de jerarquías para obtener tickets visibles
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tickets - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_usuario_ver_tickets.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/themes.css">
    <link rel="stylesheet" href="../css/global-theme-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="theme-color" content="#4a90e2">
    <meta name="color-scheme" content="light dark">
</head>
<body>
    <div class="page-container">
        <div class="header-section">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="page-title">
                        <i class="fas fa-ticket-alt"></i>
                        Mis Tickets (<?php echo $total_tickets; ?>)
                    </h1>
                    <p class="page-subtitle">Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></strong> - Gestiona los tickets de tu departamento</p>
                </div>
                <div class="header-right">
                    <a href="../logout.php" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar sesión
                    </a>
                </div>
            </div>
        </div>


        <!-- Stats Dashboard -->
        <div class="stats-dashboard">
            <a href="?" class="stat-card stat-todos stat-filter <?php echo $estado_filter === '' ? 'active' : ''; ?>" data-filter="all">
                <div class="stat-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $mis_tickets_pendientes + $mis_tickets_asignados + $mis_tickets_cerrados; ?></div>
                    <div class="stat-label">Todos</div>
                </div>
            </a>
            
            <a href="?estado=pendiente" class="stat-card stat-pendiente stat-filter <?php echo $estado_filter === 'pendiente' ? 'active' : ''; ?>" data-filter="pendiente">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $mis_tickets_pendientes; ?></div>
                    <div class="stat-label">Mis Pendientes</div>
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

        <!-- Tickets Pendientes del Departamento -->
        <div class="tickets-container">
            
            <?php if (!empty($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            

            
            <?php if (count($tickets) === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No hay tickets pendientes</h3>
                    <p>No hay tickets pendientes disponibles en tu departamento por el momento.</p>
                </div>
            <?php else: ?>
                <!-- Tabla con estilos elegantes y efectos -->
                <div class="table-container">
                    <table class="tickets-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Asunto</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Creado Por</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $t): ?>
                                <tr class="ticket-row ticket-<?php echo $t['estado']; ?>" 
                                    style="border-bottom: 1px solid #e9ecef; background: white; transition: all 0.3s ease; transform: translateY(0); height: 80px;" 
                                    onmouseover="this.style.backgroundColor='#f8f9fa'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" 
                                    onmouseout="this.style.backgroundColor='white'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                    <td style="padding: 15px 12px; vertical-align: middle; width: 80px;">
                                        <span style="font-weight: 700; color: #007bff; font-size: 14px; text-shadow: 0 1px 2px rgba(0,123,255,0.1);">#<?php echo $t['id']; ?></span>
                                    </td>
                                    <td style="padding: 15px 12px; vertical-align: middle; width: 200px;">
                                        <div style="display: flex; align-items: center; height: 100%; width: 100%;">
                                            <i class="fas fa-tag" style="color: #6c757d; margin-right: 8px; transition: color 0.2s ease; flex-shrink: 0;"></i>
                                            <span style="color: #495057; font-size: 14px; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0;"><?php echo htmlspecialchars($t['categoria_nombre']); ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 15px 12px; vertical-align: middle; width: 250px;">
                                        <div style="display: flex; align-items: center; height: 100%; width: 100%;">
                                            <span style="color: #6c757d; font-size: 13px; font-style: italic; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0;"><?php 
                                                require_once '../includes/funciones_mensajes.php';
                                                echo mostrarDescripcionSimple($t['descripcion']); 
                                            ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 15px 12px; vertical-align: middle;  width: 120px;">
                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                                            <?php 
                                            $estado = $t['estado'];
                                            $color_fondo = '';
                                            $color_texto = '';
                                            $icono = '';
                                            $texto = '';
                                            $sombra = '';
                                            
                                            switch($estado) {
                                                case 'pendiente':
                                                    $color_fondo = '#fff3cd';
                                                    $color_texto = '#856404';
                                                    $icono = 'fas fa-clock';
                                                    $texto = 'Pendiente';
                                                    $sombra = '0 2px 8px rgba(133, 100, 4, 0.2)';
                                                    break;
                                                case 'en_proceso':
                                                    $color_fondo = '#cce7ff';
                                                    $color_texto = '#004085';
                                                    $icono = 'fas fa-cogs';
                                                    $texto = 'En Proceso';
                                                    $sombra = '0 2px 8px rgba(0, 64, 133, 0.2)';
                                                    break;
                                                case 'cerrado':
                                                    $color_fondo = '#d4edda';
                                                    $color_texto = '#155724';
                                                    $icono = 'fas fa-check-circle';
                                                    $texto = 'Cerrado';
                                                    $sombra = '0 2px 8px rgba(21, 87, 36, 0.2)';
                                                    break;
                                                default:
                                                    $color_fondo = '#e2e3e5';
                                                    $color_texto = '#383d41';
                                                    $icono = 'fas fa-question';
                                                    $texto = ucfirst($estado);
                                                    $sombra = '0 2px 8px rgba(56, 61, 65, 0.2)';
                                            }
                                            ?>
                                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; background: <?php echo $color_fondo; ?>; color: <?php echo $color_texto; ?>; box-shadow: <?php echo $sombra; ?>; transition: all 0.3s ease; transform: scale(1);" 
                                                  onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)'" 
                                                  onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='<?php echo $sombra; ?>'">
                                                <i class="<?php echo $icono; ?>" style="font-size: 10px;"></i> <?php echo $texto; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td style="padding: 15px 12px; vertical-align: middle;  width: 130px;">
                                        <div style="display: flex; align-items: center; height: 100%; width: 100%;">
                                            <?php if ($t['creador_nombre']): ?>
                                                <span class="usuario-creador">
                                                    <i class="fas fa-user-plus"></i>
                                                    <?php echo htmlspecialchars($t['creador_nombre']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="sin-creador">
                                                    <i class="fas fa-question-circle"></i>
                                                    Sin creador
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 15px 12px; vertical-align: middle;  width: 120px;">
                                        <div style="display: flex; flex-direction: column; justify-content: center; height: 100%;">
                                            <div style="font-weight: 600; color: #495057; font-size: 12px; margin-bottom: 2px;"><?php echo date('d/m/Y', strtotime($t['fecha_creacion'])); ?></div>
                                            <div style="color: #6c757d; font-size: 11px;"><?php echo date('H:i', strtotime($t['fecha_creacion'])); ?></div>
                                        </div>
                                    </td>
                                    <td style="padding: 15px 12px; vertical-align: middle; width: 120px;">
                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; gap: 8px;">
                                            <?php if ($t['estado'] === 'pendiente'): ?>
                                                <!-- Solo visualizar para tickets pendientes -->
                                                <a href="visualizar_ticket.php?ticket_id=<?php echo $t['id']; ?>" 
                                                   style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: linear-gradient(135deg, #17a2b8, #138496); color: white; border: none; border-radius: 8px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(23,162,184,0.3); transform: scale(1); cursor: pointer;"
                                                   title="Visualizar ticket"
                                                   onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 12px rgba(23,162,184,0.4)'"
                                                   onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 8px rgba(23,162,184,0.3)'">
                                                    <i class="fas fa-eye" style="font-size: 14px;"></i>
                                                </a>
                                            <?php elseif ($t['estado'] === 'en_proceso' || $t['estado'] === 'cerrado'): ?>
                                                <!-- Botones para tickets tomados -->
                                                <a href="responder_ticket.php?ticket_id=<?php echo $t['id']; ?>" 
                                                   style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: linear-gradient(135deg, #007bff, #0056b3); color: white; border: none; border-radius: 8px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,123,255,0.3); transform: scale(1); cursor: pointer;"
                                                   title="Responder ticket"
                                                   onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 12px rgba(0,123,255,0.4)'"
                                                   onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 8px rgba(0,123,255,0.3)'">
                                                    <i class="fas fa-reply" style="font-size: 14px;"></i>
                                                </a>
                                                
                                                <a href="ver_historial.php?ticket_id=<?php echo $t['id']; ?>" 
                                                   style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: linear-gradient(135deg, #6c757d, #495057); color: white; border: none; border-radius: 8px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(108,117,125,0.3); transform: scale(1); cursor: pointer;"
                                                   title="Ver historial"
                                                   onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 12px rgba(108,117,125,0.4)'"
                                                   onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 8px rgba(108,117,125,0.3)'">
                                                    <i class="fas fa-history" style="font-size: 14px;"></i>
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
    
    <!-- CSS mínimo para asegurar funcionalidad -->
    <style>
        /* Asegurar que la tabla sea visible */
        table {
            display: table !important;
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
        }
        
        /* Forzar que las celdas mantengan su ancho fijo */
        table td, table th {
            overflow: hidden !important;
            word-wrap: break-word !important;
        }
        
        /* Estilos para imágenes procesadas */
        .imagen-simple {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px;
            margin: 4px 0;
            background: #f8f9fa;
        }
        
        .imagen-titulo {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
        }
        
        .imagen-error {
            color: #dc3545;
            font-size: 12px;
            text-align: center;
            padding: 8px;
        }
        
        .imagen-simple img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 4px;
        }
    </style>
<!-- Sistema de Temas -->
<script src="../js/theme-manager.js"></script>

<script>
// Configuración específica para página de tickets de usuario
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

</body>
<script src="/js/script_usuario_ver_tickets.js"></script>
<script src="/js/script_carga_pantalla.js"></script>
</html>
