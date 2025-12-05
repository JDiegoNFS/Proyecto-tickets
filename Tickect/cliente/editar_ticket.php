<?php
require_once '../includes/auth.php';
verificarRol('cliente');
require_once '../includes/db.php';

$usuario_id = $_SESSION['usuario_id'];
$ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

// Obtener información del ticket
try {
    $stmt = $pdo->prepare("
        SELECT t.*, c.nombre as categoria_nombre, d.nombre as departamento_nombre
        FROM tickets t
        LEFT JOIN categorias c ON t.categoria_id = c.id
        LEFT JOIN departamentos d ON t.departamento_id = d.id
        WHERE t.id = ? AND t.cliente_id = ? AND t.estado = 'pendiente'
    ");
    $stmt->execute([$ticket_id, $usuario_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        die("Ticket no encontrado o no se puede editar.");
    }
} catch (Exception $e) {
    die("Error al obtener el ticket: " . $e->getMessage());
}

// Obtener categorías y departamentos para el formulario
try {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE departamento_id = ? ORDER BY nombre");
    $stmt->execute([$ticket['departamento_id']]);
    $categorias = $stmt->fetchAll();
    
    // Solo obtener departamentos de tipo 'usuario' para editar tickets
    $stmt = $pdo->prepare("SELECT * FROM departamentos WHERE tipo = 'usuario' ORDER BY nombre");
    $stmt->execute();
    $departamentos = $stmt->fetchAll();
} catch (Exception $e) {
    $categorias = [];
    $departamentos = [];
}

$error = '';
$success = '';

// Procesar formulario de edición
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $categoria_id = intval($_POST['categoria_id']);
    $departamento_id = intval($_POST['departamento_id']);
    $descripcion = trim(strip_tags($_POST['descripcion']));
    
    if (!empty($categoria_id) && !empty($departamento_id) && !empty($descripcion)) {
        try {
            $pdo->beginTransaction();
            
            // Actualizar el ticket
            $stmt = $pdo->prepare("
                UPDATE tickets 
                SET categoria_id = ?, departamento_id = ?, descripcion = ?
                WHERE id = ? AND cliente_id = ? AND estado = 'pendiente'
            ");
            $stmt->execute([$categoria_id, $departamento_id, $descripcion, $ticket_id, $usuario_id]);
            
            // Registrar en historial
            $accion = "Ticket editado por el cliente";
            $stmtHist = $pdo->prepare("
                INSERT INTO historial_tickets (ticket_id, usuario_id, rol, accion)
                VALUES (?, ?, 'cliente', ?)
            ");
            $stmtHist->execute([$ticket_id, $usuario_id, $accion]);
            
            $pdo->commit();
            $success = "✅ Ticket actualizado correctamente.";
            
            // Recargar datos del ticket
            $stmt = $pdo->prepare("
                SELECT t.*, c.nombre as categoria_nombre, d.nombre as departamento_nombre
                FROM tickets t
                LEFT JOIN categorias c ON t.categoria_id = c.id
                LEFT JOIN departamentos d ON t.departamento_id = d.id
                WHERE t.id = ? AND t.cliente_id = ? AND t.estado = 'pendiente'
            ");
            $stmt->execute([$ticket_id, $usuario_id]);
            $ticket = $stmt->fetch();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "❌ Error al actualizar el ticket: " . $e->getMessage();
        }
    } else {
        $error = "⚠️ Todos los campos son obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ticket - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_usuario_crear_ticket.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="page-container">
        <div class="header-section">
            <div class="breadcrumb">
                <a href="ver_tickets.php" class="breadcrumb-link">
                    <i class="fas fa-arrow-left"></i> Volver a Mis Tickets
                </a>
            </div>
            <h1 class="page-title">
                <i class="fas fa-edit"></i>
                Editar Ticket #<?php echo $ticket['id']; ?>
            </h1>
        </div>

        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="ticket-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="departamento_id" class="form-label">
                        <i class="fas fa-building"></i>
                        Departamento
                    </label>
                    <select name="departamento_id" id="departamento_id" class="form-select" required>
                        <option value="">Seleccionar departamento</option>
                        <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?php echo $departamento['id']; ?>" 
                                    <?php echo $departamento['id'] == $ticket['departamento_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($departamento['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="categoria_id" class="form-label">
                        <i class="fas fa-tag"></i>
                        Categoría
                    </label>
                    <select name="categoria_id" id="categoria_id" class="form-select" required>
                        <option value="">Seleccionar categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" 
                                    <?php echo $categoria['id'] == $ticket['categoria_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="descripcion" class="form-label">
                        <i class="fas fa-comment-alt"></i>
                        Descripción del problema
                    </label>
                    <textarea name="descripcion" id="descripcion" class="form-textarea" rows="6" 
                              placeholder="Describe detalladamente el problema o solicitud..." required><?php echo htmlspecialchars(strip_tags($ticket['descripcion'])); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                    <a href="ver_tickets.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Filtrar categorías por departamento
        document.getElementById('departamento_id').addEventListener('change', function() {
            const departamentoId = this.value;
            const categoriaSelect = document.getElementById('categoria_id');
            
            // Limpiar opciones actuales
            categoriaSelect.innerHTML = '<option value="">Seleccionar categoría</option>';
            
            if (departamentoId) {
                // Hacer petición AJAX para obtener categorías
                fetch(`../includes/get_categorias.php?departamento_id=${departamentoId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(categoria => {
                            const option = document.createElement('option');
                            option.value = categoria.id;
                            option.textContent = categoria.nombre;
                            categoriaSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error al cargar categorías:', error);
                    });
            }
        });
    </script>
</body>
</html>
