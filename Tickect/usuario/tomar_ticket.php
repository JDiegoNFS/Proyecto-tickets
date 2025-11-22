<?php
require_once '../includes/auth.php';
verificarRol('usuario');
require_once '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ticket_id'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $cliente_id = $_SESSION['usuario_id'];

    try {
        // Iniciar transacción
        $pdo->beginTransaction();

        // Verificar que el ticket esté disponible
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND estado = 'pendiente'");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            throw new Exception("El ticket no está disponible o ya fue tomado.");
        }

        // Asignar ticket al Usuario y cambiar estado
        $stmtUpdate = $pdo->prepare("
            UPDATE tickets
            SET estado = 'en_proceso', usuario_id = ?, fecha_inicio = NOW()
            WHERE id = ?
        ");
        $stmtUpdate->execute([$cliente_id, $ticket_id]);

        // Registrar en historial
        $accion = "Ticket tomado por el Usuario";
        $stmtHist = $pdo->prepare("
            INSERT INTO historial_tickets (ticket_id, usuario_id, rol, accion)
            VALUES (?, ?, 'usuario', ?)
        ");
        $stmtHist->execute([$ticket_id, $cliente_id, $accion]);

        // Confirmar cambios
        $pdo->commit();

        header("Location: ver_tickets.php?msg=tomado");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error al tomar el ticket: " . $e->getMessage();
    }
} else {
    echo "Acceso no válido.";
}
