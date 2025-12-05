<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$rol = $_SESSION["rol"];
$usuario_id = $_SESSION["usuario_id"];

switch ($rol) {
    case 'usuario':
        header("Location: usuario/ver_tickets.php");
        exit;
    case 'cliente':
        header("Location: cliente/responder_ticket.php");
        exit;
}

// Obtener estadísticas avanzadas
try {
    // Contar tickets por estado
    $stmt = $pdo->query("SELECT estado, COUNT(*) as count FROM tickets GROUP BY estado");
    $tickets_por_estado = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Contar usuarios por rol
    $stmt = $pdo->query("SELECT rol, COUNT(*) as count FROM usuarios GROUP BY rol");
    $usuarios_por_rol = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Contar departamentos por tipo
    $stmt = $pdo->query("SELECT tipo, COUNT(*) as count FROM departamentos GROUP BY tipo");
    $departamentos_por_tipo = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Tickets por departamento
    $stmt = $pdo->query("
        SELECT d.nombre, COUNT(t.id) as count 
        FROM departamentos d 
        LEFT JOIN tickets t ON d.id = t.departamento_id 
        GROUP BY d.id, d.nombre 
        ORDER BY count DESC 
        LIMIT 10
    ");
    $tickets_por_departamento = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tickets por mes (últimos 6 meses)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
            COUNT(*) as count
        FROM tickets 
        WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
        ORDER BY mes ASC
    ");
    $tickets_por_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tickets creados hoy
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tickets WHERE DATE(fecha_creacion) = CURDATE()");
    $tickets_hoy = $stmt->fetch()['count'];
    
    // Tickets creados esta semana
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tickets WHERE YEARWEEK(fecha_creacion) = YEARWEEK(NOW())");
    $tickets_semana = $stmt->fetch()['count'];
    
    // Tiempo promedio de resolución (en días)
    $stmt = $pdo->query("
        SELECT AVG(DATEDIFF(fecha_cierre, fecha_creacion)) as promedio 
        FROM tickets 
        WHERE fecha_cierre IS NOT NULL
    ");
    $tiempo_promedio = round($stmt->fetch()['promedio'] ?? 0, 1);
    
    // Usuarios más activos (que más tickets han creado)
    $stmt = $pdo->query("
        SELECT u.nombre, u.usuario, COUNT(t.id) as tickets_creados
        FROM usuarios u
        LEFT JOIN tickets t ON u.id = t.cliente_id
        WHERE u.rol = 'cliente'
        GROUP BY u.id
        ORDER BY tickets_creados DESC
        LIMIT 5
    ");
    $usuarios_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular totales
    $total_usuarios = array_sum($usuarios_por_rol);
    $total_departamentos = array_sum($departamentos_por_tipo);
    $tickets_activos = ($tickets_por_estado['pendiente'] ?? 0) + ($tickets_por_estado['en_proceso'] ?? 0);
    $tickets_cerrados = $tickets_por_estado['cerrado'] ?? 0;
    $total_tickets = $tickets_activos + $tickets_cerrados;
    
} catch (Exception $e) {
    // Valores por defecto en caso de error
    $tickets_por_estado = [];
    $usuarios_por_rol = [];
    $departamentos_por_tipo = [];
    $tickets_por_departamento = [];
    $tickets_por_mes = [];
    $usuarios_activos = [];
    $tickets_activos = 0;
    $total_usuarios = 0;
    $total_departamentos = 0;
    $total_tickets = 0;
    $tickets_cerrados = 0;
    $tickets_hoy = 0;
    $tickets_semana = 0;
    $tiempo_promedio = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador</title>
    <link rel="stylesheet" href="css/style_admin_dashboard.css">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="css/global-theme-styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    
<div class="page-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-text">
                <h1 class="titulo">Panel de Administración</h1>
                <p class="subtitulo">Sesión iniciada como <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador'); ?></strong> (<?php echo ucfirst($_SESSION['rol']); ?>)</p>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="btn btn-danger btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon tickets-active">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $tickets_activos; ?></div>
                <div class="stat-label">Tickets Activos</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon users">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $total_usuarios; ?></div>
                <div class="stat-label">Usuarios</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon departments">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $total_departamentos; ?></div>
                <div class="stat-label">Departamentos</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon tickets-closed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $tickets_cerrados; ?></div>
                <div class="stat-label">Tickets Cerrados</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $tickets_hoy; ?></div>
                <div class="stat-label">Tickets Hoy</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $tickets_semana; ?></div>
                <div class="stat-label">Esta Semana</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $tiempo_promedio; ?></div>
                <div class="stat-label">Días Promedio</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #1abc9c, #16a085);">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $total_tickets; ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
        <div class="quick-buttons">
            <a href="admin/crear_usuario.php" class="quick-btn">
                <i class="fas fa-user-plus"></i>
                <span>Nuevo Usuario</span>
            </a>
            <a href="admin/crear_departamento.php" class="quick-btn">
                <i class="fas fa-building"></i>
                <span>Nuevo Departamento</span>
            </a>
            <a href="admin/crear_categoria.php" class="quick-btn">
                <i class="fas fa-tags"></i>
                <span>Nuevo Asunto</span>
            </a>
            <a href="admin/reporte.php" class="quick-btn">
                <i class="fas fa-chart-line"></i>
                <span>Ver Reportes</span>
            </a>
            <a href="admin/reportes_avanzados.php" class="quick-btn">
                <i class="fas fa-file-export"></i>
                <span>Exportar Reportes</span>
            </a>
            <a href="admin/notificaciones.php" class="quick-btn">
                <i class="fas fa-bell"></i>
                <span>Notificaciones</span>
            </a>
            <a href="admin/gestionar_destinatarios.php" class="quick-btn">
                <i class="fas fa-envelope"></i>
                <span>Destinatarios</span>
            </a>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <h3><i class="fas fa-chart-bar"></i> Estadísticas Visuales</h3>
        
        <div class="charts-grid">
            <!-- Gráfico de Tickets por Estado -->
            <div class="chart-card">
                <div class="chart-header">
                    <h4><i class="fas fa-pie-chart"></i> Tickets por Estado</h4>
                </div>
                <div class="chart-container">
                    <canvas id="ticketsEstadoChart"></canvas>
                </div>
            </div>

            <!-- Gráfico de Tickets por Mes -->
            <div class="chart-card">
                <div class="chart-header">
                    <h4><i class="fas fa-line-chart"></i> Tendencia Mensual</h4>
                </div>
                <div class="chart-container">
                    <canvas id="ticketsMesChart"></canvas>
                </div>
            </div>

            <!-- Gráfico de Tickets por Departamento -->
            <div class="chart-card">
                <div class="chart-header">
                    <h4><i class="fas fa-building"></i> Tickets por Departamento</h4>
                </div>
                <div class="chart-container">
                    <canvas id="ticketsDepartamentoChart"></canvas>
                </div>
            </div>

            <!-- Usuarios Más Activos -->
            <div class="chart-card">
                <div class="chart-header">
                    <h4><i class="fas fa-users"></i> Usuarios Más Activos</h4>
                </div>
                <div class="users-list">
                    <?php foreach ($usuarios_activos as $index => $usuario): ?>
                    <div class="user-item">
                        <div class="user-rank">#<?php echo $index + 1; ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($usuario['nombre'] ?? $usuario['usuario']); ?></div>
                            <div class="user-tickets"><?php echo $usuario['tickets_creados']; ?> tickets</div>
                        </div>
                        <div class="user-progress">
                            <div class="progress-bar" style="width: <?php echo min(100, ($usuario['tickets_creados'] / max(1, $usuarios_activos[0]['tickets_creados'])) * 100); ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h3>Gestión de Usuarios</h3>
            <p>Crear y administrar usuarios del sistema</p>
            <a href="admin/crear_usuario.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Registrar Usuario
            </a>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">
                <i class="fas fa-building"></i>
            </div>
            <h3>Departamentos</h3>
            <p>Gestionar departamentos y áreas de trabajo</p>
            <a href="admin/crear_departamento.php" class="btn btn-primary">
                <i class="fas fa-building"></i> Registrar Departamento
            </a>
        </div>

                                <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                            <h3>Categorías</h3>
                            <p>Administrar categorías por departamento</p>
                            <a href="admin/crear_categoria.php" class="btn btn-primary">
                                <i class="fas fa-tags"></i> Crear Categoría
                            </a>
                        </div>

        <div class="dashboard-card">
            <div class="card-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3>Reportes</h3>
            <p>Ver estadísticas y reportes del sistema</p>
            <a href="admin/reporte.php" class="btn btn-primary" style="margin-bottom: 10px;">
                <i class="fas fa-chart-line"></i> Ver Reportes
            </a>
            <a href="admin/reportes_avanzados.php" class="btn btn-success">
                <i class="fas fa-file-export"></i> Exportar con Filtros
            </a>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h3>Destinatarios de Escalamiento</h3>
            <p>Gestionar destinatarios para escalamiento de tickets</p>
            <a href="admin/gestionar_destinatarios.php" class="btn btn-primary">
                <i class="fas fa-envelope"></i> Gestionar Destinatarios
            </a>
        </div>
    </div>


</div>

<!-- Sistema de Temas -->
<script src="js/theme-manager.js"></script>

<script>
// Datos para los gráficos
const ticketsEstadoData = <?php echo json_encode($tickets_por_estado); ?>;
const ticketsMesData = <?php echo json_encode($tickets_por_mes); ?>;
const ticketsDepartamentoData = <?php echo json_encode($tickets_por_departamento); ?>;

// Configuración de colores para temas
function getThemeColors() {
    const theme = document.documentElement.getAttribute('data-theme') || 'light';
    
    const colors = {
        light: {
            primary: '#4a90e2',
            success: '#28a745',
            warning: '#ffc107',
            danger: '#dc3545',
            info: '#17a2b8',
            secondary: '#6c757d'
        },
        dark: {
            primary: '#5dade2',
            success: '#2ecc71',
            warning: '#f39c12',
            danger: '#e74c3c',
            info: '#3498db',
            secondary: '#95a5a6'
        },
        executive: {
            primary: '#9b59b6',
            success: '#2ecc71',
            warning: '#f39c12',
            danger: '#e74c3c',
            info: '#3498db',
            secondary: '#7f8c8d'
        }
    };
    
    return colors[theme] || colors.light;
}

// Inicializar gráficos
function initCharts() {
    const themeColors = getThemeColors();
    
    // Gráfico de Tickets por Estado
    const ctxEstado = document.getElementById('ticketsEstadoChart').getContext('2d');
    new Chart(ctxEstado, {
        type: 'doughnut',
        data: {
            labels: ['Pendientes', 'En Proceso', 'Cerrados'],
            datasets: [{
                data: [
                    ticketsEstadoData.pendiente || 0,
                    ticketsEstadoData.en_proceso || 0,
                    ticketsEstadoData.cerrado || 0
                ],
                backgroundColor: [
                    themeColors.warning,
                    themeColors.info,
                    themeColors.success
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de Tendencia Mensual
    const ctxMes = document.getElementById('ticketsMesChart').getContext('2d');
    new Chart(ctxMes, {
        type: 'line',
        data: {
            labels: ticketsMesData.map(item => {
                const [year, month] = item.mes.split('-');
                return new Date(year, month - 1).toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
            }),
            datasets: [{
                label: 'Tickets Creados',
                data: ticketsMesData.map(item => item.count),
                borderColor: themeColors.primary,
                backgroundColor: themeColors.primary + '20',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico de Tickets por Departamento
    const ctxDepartamento = document.getElementById('ticketsDepartamentoChart').getContext('2d');
    new Chart(ctxDepartamento, {
        type: 'bar',
        data: {
            labels: ticketsDepartamentoData.map(item => item.nombre),
            datasets: [{
                label: 'Tickets',
                data: ticketsDepartamentoData.map(item => item.count),
                backgroundColor: themeColors.primary,
                borderColor: themeColors.primary,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Escuchar cambios de tema para actualizar gráficos
document.addEventListener('themeChanged', function(e) {
    console.log(`Tema cambiado de ${e.detail.oldTheme} a ${e.detail.newTheme}`);
    
    // Reinicializar gráficos con nuevos colores
    setTimeout(() => {
        // Destruir gráficos existentes
        Chart.helpers.each(Chart.instances, function(instance) {
            instance.destroy();
        });
        // Recrear con nuevos colores
        initCharts();
    }, 100);
});

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar gráficos
    setTimeout(initCharts, 500);
    
    // Actualizar título de página
    const updatePageTitle = () => {
        const currentTheme = themeManager.getCurrentTheme();
        const themeName = themeManager.getAvailableThemes()[currentTheme].name;
        document.title = `Panel Administrador - Tema ${themeName}`;
    };
    
    setTimeout(updatePageTitle, 100);
    document.addEventListener('themeChanged', updatePageTitle);
});
</script>

</body>
</html>
