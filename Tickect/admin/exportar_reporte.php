<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION["usuario_id"]) || $_SESSION["rol"] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$formato = $_GET['formato'] ?? 'excel';
$tipo = $_GET['tipo'] ?? 'general';
$fecha_inicio = $_GET['fecha_inicio'] ?? '2024-01-01';
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$departamento = $_GET['departamento'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';
$usuario_filtro = $_GET['usuario'] ?? '';

// Función para generar Excel (HTML con formato Excel)
function generarExcel($datos, $columnas, $nombre_archivo, $titulo) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '.xls"');
    
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th { background-color: #4a90e2; color: white; padding: 10px; border: 1px solid #ddd; font-weight: bold; text-align: left; }';
    echo 'td { padding: 8px; border: 1px solid #ddd; }';
    echo 'tr:nth-child(even) { background-color: #f2f2f2; }';
    echo '.titulo { font-size: 18px; font-weight: bold; margin-bottom: 10px; padding: 10px; }';
    echo '.info { color: #666; margin-bottom: 20px; padding: 5px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="titulo">' . htmlspecialchars($titulo) . '</div>';
    echo '<div class="info">Generado: ' . date('d/m/Y H:i:s') . '</div>';
    echo '<div class="info">Período: ' . $_GET['fecha_inicio'] . ' al ' . $_GET['fecha_fin'] . '</div>';
    echo '<table>';
    
    // Encabezados
    echo '<tr>';
    foreach ($columnas as $columna) {
        echo '<th>' . htmlspecialchars($columna) . '</th>';
    }
    echo '</tr>';
    
    // Datos
    foreach ($datos as $fila) {
        echo '<tr>';
        foreach ($fila as $celda) {
            echo '<td>' . htmlspecialchars($celda ?? '') . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit;
}

try {
    // Construir condiciones WHERE según filtros
    $where_conditions = ["DATE(t.fecha_creacion) BETWEEN ? AND ?"];
    $params = [$fecha_inicio, $fecha_fin];
    
    if ($departamento) {
        $where_conditions[] = "t.departamento_id = ?";
        $params[] = $departamento;
    }
    
    if ($estado_filtro) {
        $where_conditions[] = "t.estado = ?";
        $params[] = $estado_filtro;
    }
    
    if ($usuario_filtro) {
        $where_conditions[] = "t.usuario_id = ?";
        $params[] = $usuario_filtro;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    switch ($tipo) {
        case 'general':
            // REPORTE GENERAL DE TICKETS
            $sql = "
                SELECT 
                    t.id as ticket_id,
                    SUBSTRING(t.descripcion, 1, 100) as descripcion,
                    t.estado,
                    c.nombre as categoria,
                    d.nombre as departamento,
                    u_creador.nombre as creado_por,
                    u_asignado.nombre as asignado_a,
                    DATE_FORMAT(t.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion,
                    DATE_FORMAT(t.fecha_inicio, '%d/%m/%Y %H:%i') as fecha_inicio,
                    DATE_FORMAT(t.fecha_cierre, '%d/%m/%Y %H:%i') as fecha_cierre,
                    CASE 
                        WHEN t.fecha_cierre IS NOT NULL THEN 
                            TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre)
                        ELSE 
                            TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW())
                    END as horas_transcurridas
                FROM tickets t
                LEFT JOIN categorias c ON t.categoria_id = c.id
                LEFT JOIN departamentos d ON t.departamento_id = d.id
                LEFT JOIN usuarios u_creador ON t.cliente_id = u_creador.id
                LEFT JOIN usuarios u_asignado ON t.usuario_id = u_asignado.id
                WHERE $where_clause
                ORDER BY t.fecha_creacion DESC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $columnas = [
                'ID Ticket',
                'Descripción',
                'Estado',
                'Categoría',
                'Departamento',
                'Creado Por',
                'Asignado A',
                'Fecha Creación',
                'Fecha Inicio',
                'Fecha Cierre',
                'Horas Transcurridas'
            ];
            
            $datos_formateados = [];
            foreach ($datos as $row) {
                $datos_formateados[] = [
                    '#' . $row['ticket_id'],
                    $row['descripcion'],
                    ucfirst(str_replace('_', ' ', $row['estado'])),
                    $row['categoria'] ?? 'Sin categoría',
                    $row['departamento'] ?? 'Sin departamento',
                    $row['creado_por'] ?? 'Desconocido',
                    $row['asignado_a'] ?? 'Sin asignar',
                    $row['fecha_creacion'],
                    $row['fecha_inicio'] ?? 'No iniciado',
                    $row['fecha_cierre'] ?? 'No cerrado',
                    $row['horas_transcurridas'] . 'h'
                ];
            }
            
            $nombre_archivo = 'reporte_general_' . date('Y-m-d_His');
            $titulo = 'Reporte General de Tickets';
            break;
            
        case 'departamento':
            // REPORTE POR DEPARTAMENTO
            $sql = "
                SELECT 
                    d.nombre as departamento,
                    COUNT(t.id) as total_tickets,
                    SUM(CASE WHEN t.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN t.estado = 'abierto' THEN 1 ELSE 0 END) as abiertos,
                    SUM(CASE WHEN t.estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) as cerrados,
                    ROUND(AVG(CASE WHEN t.estado = 'cerrado' AND t.fecha_cierre IS NOT NULL
                        THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) 
                        ELSE NULL END), 2) as tiempo_promedio_resolucion,
                    COUNT(DISTINCT t.usuario_id) as usuarios_activos
                FROM departamentos d
                LEFT JOIN tickets t ON d.id = t.departamento_id 
                    AND DATE(t.fecha_creacion) BETWEEN ? AND ?
                GROUP BY d.id
                HAVING total_tickets > 0
                ORDER BY total_tickets DESC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fecha_inicio, $fecha_fin]);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $columnas = [
                'Departamento',
                'Total Tickets',
                'Pendientes',
                'Abiertos',
                'En Proceso',
                'Cerrados',
                'Tiempo Promedio (horas)',
                'Usuarios Activos',
                'Tasa de Cierre (%)'
            ];
            
            $datos_formateados = [];
            foreach ($datos as $row) {
                $tasa_cierre = $row['total_tickets'] > 0 
                    ? round(($row['cerrados'] / $row['total_tickets']) * 100, 2) 
                    : 0;
                    
                $datos_formateados[] = [
                    $row['departamento'],
                    $row['total_tickets'],
                    $row['pendientes'],
                    $row['abiertos'],
                    $row['en_proceso'],
                    $row['cerrados'],
                    $row['tiempo_promedio_resolucion'] ?? 'N/A',
                    $row['usuarios_activos'],
                    $tasa_cierre . '%'
                ];
            }
            
            $nombre_archivo = 'reporte_departamento_' . date('Y-m-d_His');
            $titulo = 'Reporte por Departamento';
            break;
            
        case 'rendimiento':
            // REPORTE DE RENDIMIENTO POR USUARIO
            $sql = "
                SELECT 
                    u.nombre as usuario,
                    u.rol,
                    d.nombre as departamento,
                    COUNT(DISTINCT t.id) as tickets_asignados,
                    SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) as tickets_cerrados,
                    SUM(CASE WHEN t.estado = 'pendiente' THEN 1 ELSE 0 END) as tickets_pendientes,
                    SUM(CASE WHEN t.estado = 'en_proceso' THEN 1 ELSE 0 END) as tickets_en_proceso,
                    ROUND(AVG(CASE WHEN t.estado = 'cerrado' AND t.fecha_cierre IS NOT NULL
                        THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) 
                        ELSE NULL END), 2) as tiempo_promedio_resolucion
                FROM usuarios u
                LEFT JOIN departamentos d ON u.departamento_id = d.id
                LEFT JOIN tickets t ON u.id = t.usuario_id 
                    AND DATE(t.fecha_creacion) BETWEEN ? AND ?
                WHERE u.rol IN ('usuario', 'admin')
                GROUP BY u.id
                HAVING tickets_asignados > 0
                ORDER BY tickets_cerrados DESC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fecha_inicio, $fecha_fin]);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $columnas = [
                'Usuario',
                'Rol',
                'Departamento',
                'Tickets Asignados',
                'Tickets Cerrados',
                'Tickets Pendientes',
                'Tickets En Proceso',
                'Tiempo Promedio (horas)',
                'Tasa de Cierre (%)'
            ];
            
            $datos_formateados = [];
            foreach ($datos as $row) {
                $tasa_cierre = $row['tickets_asignados'] > 0 
                    ? round(($row['tickets_cerrados'] / $row['tickets_asignados']) * 100, 2) 
                    : 0;
                    
                $datos_formateados[] = [
                    $row['usuario'],
                    ucfirst($row['rol']),
                    $row['departamento'] ?? 'Sin departamento',
                    $row['tickets_asignados'],
                    $row['tickets_cerrados'],
                    $row['tickets_pendientes'],
                    $row['tickets_en_proceso'],
                    $row['tiempo_promedio_resolucion'] ?? 'N/A',
                    $tasa_cierre . '%'
                ];
            }
            
            $nombre_archivo = 'reporte_rendimiento_' . date('Y-m-d_His');
            $titulo = 'Reporte de Rendimiento por Usuario';
            break;
            
        default:
            die('Tipo de reporte no válido');
    }
    
    // Generar archivo según formato
    if (empty($datos_formateados)) {
        die('No hay datos para exportar con los filtros seleccionados');
    }
    
    generarExcel($datos_formateados, $columnas, $nombre_archivo, $titulo);
    
} catch (Exception $e) {
    die('Error al generar reporte: ' . $e->getMessage());
}
?>
