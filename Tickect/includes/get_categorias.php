<?php
require_once 'db.php';

header('Content-Type: application/json');

if (isset($_GET['departamento_id'])) {
    $departamento_id = intval($_GET['departamento_id']);
    
    try {
        $stmt = $pdo->prepare("SELECT id, nombre FROM categorias WHERE departamento_id = ? ORDER BY nombre");
        $stmt->execute([$departamento_id]);
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($categorias);
    } catch (Exception $e) {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>

