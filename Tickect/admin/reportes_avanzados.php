<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION["usuario_id"]) || $_SESSION["rol"] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Obtener departamentos para filtros
$stmt = $pdo->query("SELECT id, nombre FROM departamentos ORDER BY nombre");
$departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener usuarios para filtros
$stmt = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol IN ('usuario', 'admin') ORDER BY nombre");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Avanzados - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_admin_dashboard.css">
    <link rel="stylesheet" href="../css/themes.css">
    <link rel="stylesheet" href="../css/global-theme-styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            color: #2c3e50;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .report-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e0e0e0;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .report-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }
        
        .report-card h3 i {
            color: #4a90e2;
        }
        
        .report-card p {
            color: #5a6c7d;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .report-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-download {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-excel {
            background: #217346;
            color: white;
        }
        
        .btn-excel:hover {
            background: #1a5c37;
        }
        
        .btn-csv {
            background: #0078d4;
            color: white;
        }
        
        .btn-csv:hover {
            background: #005a9e;
        }
        

        
        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-filter {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #4a90e2;
            color: white;
        }
        
        .btn-primary:hover {
            background: #357abd;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title h1 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .page-title p {
            color: #5a6c7d;
            font-size: 14px;
        }
        
        .back-button {
            padding: 10px 20px;
            background: #4a90e2;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background: #357abd;
        }
    </style>
</head>
<body>
    <div class="page-container" style="padding: 30px;">
        <!-- Header -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-chart-bar"></i> Reportes Avanzados</h1>
                <p>Genera y descarga reportes personalizados del sistema</p>
            </div>
            <a href="../dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <h2 style="margin-bottom: 20px; color: var(--text-primary);">
                <i class="fas fa-filter"></i> Filtros de Búsqueda
            </h2>
            
            <form id="filtrosForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="fecha_inicio">
                            <i class="fas fa-calendar-alt"></i> Fecha Inicio
                        </label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" 
                               value="2024-01-01">
                    </div>
                    
                    <div class="filter-group">
                        <label for="fecha_fin">
                            <i class="fas fa-calendar-alt"></i> Fecha Fin
                        </label>
                        <input type="date" id="fecha_fin" name="fecha_fin" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="departamento">
                            <i class="fas fa-building"></i> Departamento
                        </label>
                        <select id="departamento" name="departamento">
                            <option value="">Todos los departamentos</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="estado">
                            <i class="fas fa-info-circle"></i> Estado
                        </label>
                        <select id="estado" name="estado">
                            <option value="">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="abierto">Abierto</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="cerrado">Cerrado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="prioridad">
                            <i class="fas fa-exclamation-triangle"></i> Prioridad
                        </label>
                        <select id="prioridad" name="prioridad">
                            <option value="">Todas las prioridades</option>
                            <option value="alta">Alta</option>
                            <option value="media">Media</option>
                            <option value="baja">Baja</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="usuario">
                            <i class="fas fa-user"></i> Usuario Asignado
                        </label>
                        <select id="usuario" name="usuario">
                            <option value="">Todos los usuarios</option>
                            <?php foreach ($usuarios as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="button" class="btn-filter btn-primary" onclick="aplicarFiltros()">
                        <i class="fas fa-search"></i> Aplicar Filtros
                    </button>
                    <button type="button" class="btn-filter btn-secondary" onclick="limpiarFiltros()">
                        <i class="fas fa-times"></i> Limpiar Filtros
                    </button>
                </div>
            </form>
        </div>

        <!-- Tipos de Reportes -->
        <div class="reports-grid">
            <!-- Reporte General -->
            <div class="report-card">
                <h3>
                    <i class="fas fa-list"></i>
                    Reporte General
                </h3>
                <p>
                    Listado completo de tickets con todos los detalles: ID, asunto, estado, 
                    prioridad, departamento, usuarios involucrados y tiempos.
                </p>
                <div class="report-actions">
                    <a href="#" class="btn-download btn-excel" onclick="descargar('general', 'excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="#" class="btn-download btn-csv" onclick="descargar('general', 'csv')">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                </div>
            </div>

            <!-- Reporte Detallado -->
            <div class="report-card">
                <h3>
                    <i class="fas fa-clipboard-list"></i>
                    Reporte Detallado
                </h3>
                <p>
                    Incluye información adicional como número de respuestas, archivos adjuntos 
                    y participantes en cada ticket.
                </p>
                <div class="report-actions">
                    <a href="#" class="btn-download btn-excel" onclick="descargar('detallado', 'excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="#" class="btn-download btn-csv" onclick="descargar('detallado', 'csv')">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                </div>
            </div>

            <!-- Reporte de Rendimiento -->
            <div class="report-card">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    Rendimiento por Usuario
                </h3>
                <p>
                    Estadísticas de desempeño: tickets asignados, cerrados, tiempo promedio 
                    de resolución y tasa de cierre por usuario.
                </p>
                <div class="report-actions">
                    <a href="#" class="btn-download btn-excel" onclick="descargar('rendimiento', 'excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="#" class="btn-download btn-csv" onclick="descargar('rendimiento', 'csv')">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                </div>
            </div>

            <!-- Reporte por Departamento -->
            <div class="report-card">
                <h3>
                    <i class="fas fa-building"></i>
                    Análisis por Departamento
                </h3>
                <p>
                    Métricas por departamento: total de tickets, distribución por estado y 
                    prioridad, tiempos promedio y usuarios activos.
                </p>
                <div class="report-actions">
                    <a href="#" class="btn-download btn-excel" onclick="descargar('departamento', 'excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="#" class="btn-download btn-csv" onclick="descargar('departamento', 'csv')">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                </div>
            </div>

            <!-- Reporte SLA -->
            <div class="report-card">
                <h3>
                    <i class="fas fa-clock"></i>
                    Cumplimiento SLA
                </h3>
                <p>
                    Análisis de cumplimiento de tiempos de respuesta según prioridad. 
                    Identifica tickets vencidos y en riesgo.
                </p>
                <div class="report-actions">
                    <a href="#" class="btn-download btn-excel" onclick="descargar('sla', 'excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="#" class="btn-download btn-csv" onclick="descargar('sla', 'csv')">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                </div>
            </div>

            <!-- Reporte Ejecutivo -->
            <div class="report-card">
                <h3>
                    <i class="fas fa-briefcase"></i>
                    Resumen Ejecutivo
                </h3>
                <p>
                    Resumen de alto nivel con KPIs principales, tendencias y métricas clave 
                    para la toma de decisiones.
                </p>
                <div class="report-actions">
                    <a href="#" class="btn-download btn-excel" onclick="descargar('ejecutivo', 'excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="#" class="btn-download btn-csv" onclick="descargar('ejecutivo', 'csv')">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function obtenerParametros() {
            const params = new URLSearchParams();
            params.append('fecha_inicio', document.getElementById('fecha_inicio').value);
            params.append('fecha_fin', document.getElementById('fecha_fin').value);
            
            const departamento = document.getElementById('departamento').value;
            if (departamento) params.append('departamento', departamento);
            
            const estado = document.getElementById('estado').value;
            if (estado) params.append('estado', estado);
            
            const prioridad = document.getElementById('prioridad').value;
            if (prioridad) params.append('prioridad', prioridad);
            
            const usuario = document.getElementById('usuario').value;
            if (usuario) params.append('usuario', usuario);
            
            return params.toString();
        }

        function descargar(tipo, formato) {
            event.preventDefault();
            const params = obtenerParametros();
            const url = `exportar_reporte.php?tipo=${tipo}&formato=${formato}&${params}`;
            window.open(url, '_blank');
        }

        function aplicarFiltros() {
            // Los filtros se aplican automáticamente al descargar
            alert('Filtros aplicados. Ahora puedes descargar cualquier reporte con estos filtros.');
        }

        function limpiarFiltros() {
            document.getElementById('filtrosForm').reset();
            document.getElementById('fecha_inicio').value = '2024-01-01';
            document.getElementById('fecha_fin').value = '<?php echo date('Y-m-d'); ?>';
        }

        // Validar fechas
        document.getElementById('fecha_inicio').addEventListener('change', function() {
            const fechaInicio = new Date(this.value);
            const fechaFin = new Date(document.getElementById('fecha_fin').value);
            
            if (fechaInicio > fechaFin) {
                alert('La fecha de inicio no puede ser mayor que la fecha fin');
                this.value = document.getElementById('fecha_fin').value;
            }
        });

        document.getElementById('fecha_fin').addEventListener('change', function() {
            const fechaInicio = new Date(document.getElementById('fecha_inicio').value);
            const fechaFin = new Date(this.value);
            
            if (fechaFin < fechaInicio) {
                alert('La fecha fin no puede ser menor que la fecha de inicio');
                this.value = document.getElementById('fecha_inicio').value;
            }
        });
    </script>
</body>
</html>
