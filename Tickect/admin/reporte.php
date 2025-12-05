<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION["usuario_id"]) || $_SESSION["rol"] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Obtener estadísticas por día
try {
    // Debug: Verificar conexión
    $test = $pdo->query("SELECT COUNT(*) as total FROM tickets");
    $test_result = $test->fetch();
    echo "<!-- Debug: Total tickets en BD: " . $test_result['total'] . " -->";
    
    // Tickets por día (últimos 30 días o todos si hay pocos)
    $stmt = $pdo->query("
        SELECT DATE(fecha_creacion) as fecha, COUNT(*) as cantidad 
        FROM tickets 
        GROUP BY DATE(fecha_creacion) 
        ORDER BY fecha DESC
        LIMIT 30
    ");
    $tickets_por_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tickets por categoría
    $stmt = $pdo->query("
        SELECT c.nombre as categoria, COUNT(t.id) as cantidad 
        FROM categorias c 
        LEFT JOIN tickets t ON c.id = t.categoria_id 
        GROUP BY c.id, c.nombre 
        ORDER BY cantidad DESC
    ");
    $tickets_por_categoria = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tickets por departamento
    $stmt = $pdo->query("
        SELECT d.nombre as departamento, COUNT(t.id) as cantidad 
        FROM departamentos d 
        LEFT JOIN tickets t ON d.id = t.departamento_id 
        GROUP BY d.id, d.nombre 
        ORDER BY cantidad DESC
    ");
    $tickets_por_departamento = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas generales
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets");
    $total_tickets = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE estado = 'pendiente'");
    $tickets_pendientes = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE estado = 'en_proceso'");
    $tickets_en_proceso = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE estado = 'cerrado'");
    $tickets_cerrados = $stmt->fetch()['total'];
    
    // Tickets por estado (en lugar de prioridad)
    $stmt = $pdo->query("
        SELECT estado, COUNT(*) as cantidad 
        FROM tickets 
        GROUP BY estado 
        ORDER BY cantidad DESC
    ");
    $tickets_por_prioridad = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tickets por mes (todos los meses con datos)
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, COUNT(*) as cantidad 
        FROM tickets 
        GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m') 
        ORDER BY mes DESC
        LIMIT 12
    ");
    $tickets_por_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tiempo promedio de resolución
    $stmt = $pdo->query("
        SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_cierre)) as tiempo_promedio 
        FROM tickets 
        WHERE estado = 'cerrado' AND fecha_cierre IS NOT NULL
    ");
    $tiempo_promedio = $stmt->fetch()['tiempo_promedio'] ?? 0;
    
    // Tickets más recientes
    $stmt = $pdo->query("
        SELECT t.id, SUBSTRING(t.descripcion, 1, 50) as asunto, t.estado
        FROM tickets t 
        ORDER BY t.fecha_creacion DESC 
        LIMIT 5
    ");
    $tickets_mas_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    echo "<!-- ERROR: " . $e->getMessage() . " -->";
    $tickets_por_dia = [];
    $tickets_por_categoria = [];
    $tickets_por_departamento = [];
    $tickets_por_prioridad = [];
    $tickets_por_mes = [];
    $tickets_mas_activos = [];
    $total_tickets = 0;
    $tickets_pendientes = 0;
    $tickets_en_proceso = 0;
    $tickets_cerrados = 0;
    $tiempo_promedio = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_admin_dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #e8eaf6 0%, #c5cae9 100%) !important;
            min-height: 100vh;
        }
        
        .page-container {
            background: transparent !important;
        }
        
        .header-section {
            background: linear-gradient(135deg, #b0bec5 0%, #90a4ae 100%) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%) !important;
            border: 1px solid #cfd8dc;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .chart-card, .table-card {
            background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%) !important;
            border: 1px solid #cfd8dc;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .titulo {
            color: #263238 !important;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.3);
        }
        
        .subtitulo {
            color: #37474f !important;
        }
    </style>
</head>
<body>
    
<div class="page-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-text">
                <h1 class="titulo">📊 Reportes del Sistema</h1>
                <p class="subtitulo">Estadísticas y análisis de tickets</p>
            </div>
            <div class="header-actions">
                <div class="export-buttons">
                    <a href="exportar_reporte.php?formato=pdf&tipo=general" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </a>
                    <a href="exportar_reporte.php?formato=excel&tipo=general" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </a>
                </div>
                <a href="../dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon tickets-active">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $total_tickets; ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon tickets-active">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $tickets_pendientes; ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon users">
                <i class="fas fa-cogs"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $tickets_en_proceso; ?></div>
                <div class="stat-label">En Proceso</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon tickets-closed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $tickets_cerrados; ?></div>
                <div class="stat-label">Cerrados</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon users">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo round($tiempo_promedio, 1); ?>h</div>
                <div class="stat-label">Tiempo Promedio</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon departments">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo ($tickets_pendientes + $tickets_en_proceso); ?></div>
                <div class="stat-label">Tickets Activos</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-grid">
        <!-- Tickets por Día -->
        <div class="chart-card">
            <h3><i class="fas fa-calendar-day"></i> Tickets por Día (Últimos 30 días)</h3>
            <div class="chart-container">
                <canvas id="ticketsPorDia"></canvas>
            </div>
        </div>

        <!-- Tickets por Categoría -->
        <div class="chart-card">
            <h3><i class="fas fa-tags"></i> Tickets por Categoría</h3>
            <div class="chart-container">
                <canvas id="ticketsPorCategoria"></canvas>
            </div>
        </div>

        <!-- Tickets por Departamento -->
        <div class="chart-card">
            <h3><i class="fas fa-building"></i> Tickets por Departamento</h3>
            <div class="chart-container">
                <canvas id="ticketsPorDepartamento"></canvas>
            </div>
        </div>

        <!-- Tickets por Estado -->
        <div class="chart-card">
            <h3><i class="fas fa-info-circle"></i> Distribución por Estado</h3>
            <div class="chart-container">
                <canvas id="ticketsPorPrioridad"></canvas>
            </div>
        </div>

        <!-- Tickets por Mes -->
        <div class="chart-card">
            <h3><i class="fas fa-calendar-alt"></i> Tickets por Mes (Histórico)</h3>
            <div class="chart-container">
                <canvas id="ticketsPorMes"></canvas>
            </div>
        </div>
    </div>

    <!-- Data Tables -->
    <div class="tables-grid">
        <!-- Tabla de Tickets por Día -->
        <div class="table-card">
            <h3><i class="fas fa-table"></i> Detalle por Día</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tickets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets_por_dia as $dia): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($dia['fecha'])); ?></td>
                            <td><?php echo $dia['cantidad']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabla de Tickets por Categoría -->
        <div class="table-card">
            <h3><i class="fas fa-table"></i> Detalle por Categoría</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Tickets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets_por_categoria as $categoria): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($categoria['categoria']); ?></td>
                            <td><?php echo $categoria['cantidad']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabla de Tickets por Estado -->
        <div class="table-card">
            <h3><i class="fas fa-table"></i> Detalle por Estado</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Tickets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets_por_prioridad as $estado_item): ?>
                        <tr>
                            <td>
                                <span class="priority-badge priority-<?php echo strtolower($estado_item['estado']); ?>">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $estado_item['estado']))); ?>
                                </span>
                            </td>
                            <td><?php echo $estado_item['cantidad']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabla de Tickets Recientes -->
        <div class="table-card">
            <h3><i class="fas fa-table"></i> Tickets Recientes</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets_mas_activos as $ticket): ?>
                        <tr>
                            <td>#<?php echo $ticket['id']; ?></td>
                            <td><?php echo htmlspecialchars($ticket['asunto']); ?></td>
                            <td>
                                <span class="priority-badge priority-<?php echo strtolower($ticket['estado']); ?>">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $ticket['estado']))); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Datos para los gráficos
const ticketsPorDia = <?php echo json_encode($tickets_por_dia); ?>;
const ticketsPorCategoria = <?php echo json_encode($tickets_por_categoria); ?>;
const ticketsPorDepartamento = <?php echo json_encode($tickets_por_departamento); ?>;
const ticketsPorPrioridad = <?php echo json_encode($tickets_por_prioridad); ?>;
const ticketsPorMes = <?php echo json_encode($tickets_por_mes); ?>;

// Gráfico de Tickets por Día
const ctxDia = document.getElementById('ticketsPorDia').getContext('2d');
new Chart(ctxDia, {
    type: 'line',
    data: {
        labels: ticketsPorDia.map(item => {
            const fecha = new Date(item.fecha);
            return fecha.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
        }).reverse(),
        datasets: [{
            label: 'Tickets',
            data: ticketsPorDia.map(item => item.cantidad).reverse(),
            borderColor: '#a8b2bfff',
            backgroundColor: 'rgba(74, 144, 226, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Gráfico de Tickets por Categoría
const ctxCategoria = document.getElementById('ticketsPorCategoria').getContext('2d');
new Chart(ctxCategoria, {
    type: 'doughnut',
    data: {
        labels: ticketsPorCategoria.map(item => item.categoria),
        datasets: [{
            data: ticketsPorCategoria.map(item => item.cantidad),
            backgroundColor: [
                '#89acd3ff',
                '#f39c12',
                '#e74c3c',
                '#27ae60',
                '#9b59b6',
                '#1abc9c',
                '#34495e'
            ]
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

// Gráfico de Tickets por Departamento
const ctxDepartamento = document.getElementById('ticketsPorDepartamento').getContext('2d');
new Chart(ctxDepartamento, {
    type: 'bar',
    data: {
        labels: ticketsPorDepartamento.map(item => item.departamento),
        datasets: [{
            label: 'Tickets',
            data: ticketsPorDepartamento.map(item => item.cantidad),
            backgroundColor: '#87a3c4ff',
            borderColor: '#8198aeff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Gráfico de Tickets por Estado
const ctxPrioridad = document.getElementById('ticketsPorPrioridad').getContext('2d');
new Chart(ctxPrioridad, {
    type: 'pie',
    data: {
        labels: ticketsPorPrioridad.map(item => item.estado.replace('_', ' ')),
        datasets: [{
            data: ticketsPorPrioridad.map(item => item.cantidad),
            backgroundColor: [
                '#f39c12', // Pendiente
                '#3498db', // Abierto
                '#e74c3c', // En proceso
                '#27ae60'  // Cerrado
            ]
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

// Gráfico de Tickets por Mes
const ctxMes = document.getElementById('ticketsPorMes').getContext('2d');
new Chart(ctxMes, {
    type: 'bar',
    data: {
        labels: ticketsPorMes.map(item => {
            const fecha = new Date(item.mes + '-01');
            return fecha.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
        }).reverse(),
        datasets: [{
            label: 'Tickets',
            data: ticketsPorMes.map(item => item.cantidad).reverse(),
            backgroundColor: '#9b59b6',
            borderColor: '#8e44ad',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

</body>
</html>
