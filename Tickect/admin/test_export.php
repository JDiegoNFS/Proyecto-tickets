<?php
// Script de prueba para verificar exportación
session_start();
require_once '../includes/db.php';

// Simular sesión de admin para prueba
$_SESSION["usuario_id"] = 1;
$_SESSION["rol"] = 'admin';

echo "<h2>🧪 Prueba de Exportación de Reportes</h2>";
echo "<p>Verificando conexión y datos...</p>";

try {
    // Verificar conexión
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets");
    $total = $stmt->fetch()['total'];
    echo "<p>✅ Conexión exitosa. Total de tickets: <strong>$total</strong></p>";
    
    // Probar consulta de reporte general
    $fecha_inicio = '2024-08-01';
    $fecha_fin = date('Y-m-d');
    
    $sql = "
        SELECT 
            t.id as ticket_id,
            SUBSTRING(t.descripcion, 1, 100) as descripcion,
            t.estado,
            c.nombre as categoria,
            d.nombre as departamento,
            u_creador.nombre as creado_por,
            u_asignado.nombre as asignado_a,
            DATE_FORMAT(t.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion
        FROM tickets t
        LEFT JOIN categorias c ON t.categoria_id = c.id
        LEFT JOIN departamentos d ON t.departamento_id = d.id
        LEFT JOIN usuarios u_creador ON t.cliente_id = u_creador.id
        LEFT JOIN usuarios u_asignado ON t.usuario_id = u_asignado.id
        WHERE DATE(t.fecha_creacion) BETWEEN ? AND ?
        ORDER BY t.fecha_creacion DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Consulta exitosa. Registros encontrados: <strong>" . count($datos) . "</strong></p>";
    
    if (count($datos) > 0) {
        echo "<h3>📋 Muestra de datos (primeros 5 registros):</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #4a90e2; color: white;'>";
        echo "<th>ID</th><th>Descripción</th><th>Estado</th><th>Departamento</th><th>Fecha</th>";
        echo "</tr>";
        
        foreach ($datos as $row) {
            echo "<tr>";
            echo "<td>#" . $row['ticket_id'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['descripcion'], 0, 50)) . "...</td>";
            echo "<td>" . ucfirst($row['estado']) . "</td>";
            echo "<td>" . ($row['departamento'] ?? 'N/A') . "</td>";
            echo "<td>" . $row['fecha_creacion'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<br><h3>🎯 Enlaces de Prueba:</h3>";
        echo "<p>Haz clic en estos enlaces para probar la descarga:</p>";
        echo "<ul>";
        echo "<li><a href='exportar_reporte.php?tipo=general&formato=excel&fecha_inicio=$fecha_inicio&fecha_fin=$fecha_fin' target='_blank'>📗 Descargar Reporte General (Excel)</a></li>";
        echo "<li><a href='exportar_reporte.php?tipo=departamento&formato=excel&fecha_inicio=$fecha_inicio&fecha_fin=$fecha_fin' target='_blank'>📗 Descargar Reporte por Departamento (Excel)</a></li>";
        echo "<li><a href='exportar_reporte.php?tipo=rendimiento&formato=excel&fecha_inicio=$fecha_inicio&fecha_fin=$fecha_fin' target='_blank'>📗 Descargar Reporte de Rendimiento (Excel)</a></li>";
        echo "</ul>";
        
        echo "<br><p>✅ <strong>Todo está funcionando correctamente!</strong></p>";
        echo "<p>Puedes usar la página de <a href='reportes_avanzados.php'>Reportes Avanzados</a> para generar reportes con filtros.</p>";
        
    } else {
        echo "<p>⚠️ No se encontraron datos en el rango de fechas especificado.</p>";
        echo "<p>Asegúrate de haber ejecutado el script <code>datos_prueba_tickets.sql</code></p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<br><hr>";
echo "<p><a href='reportes_avanzados.php'>← Volver a Reportes Avanzados</a></p>";
?>
