<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Debug: Log de la petición
error_log("AJAX Request: " . print_r($_POST, true));

// Usar rutas absolutas para evitar problemas de rutas relativas
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/includes/db.php';
require_once $root_path . '/includes/escalamiento.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$tipo_solicitud = $_POST['tipo_solicitud'] ?? '';

if (empty($tipo_solicitud)) {
    echo json_encode(['success' => false, 'error' => 'Tipo de solicitud requerido']);
    exit();
}

try {
    $destinatarios = obtenerDestinatariosEscalamiento($tipo_solicitud);
    
    error_log("Destinatarios encontrados: " . count($destinatarios));
    
    echo json_encode([
        'success' => true,
        'destinatarios' => $destinatarios
    ]);
    
} catch (Exception $e) {
    error_log("Error obteniendo destinatarios: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
