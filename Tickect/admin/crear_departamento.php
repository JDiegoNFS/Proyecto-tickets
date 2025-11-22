<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION["usuario_id"]) || $_SESSION["rol"] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$mensaje = '';
$tipo_mensaje = '';
$editar_id = null;
$departamento_editar = null;
$nombre = '';
$tipo = '';

// Parámetros de paginación y búsqueda
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$departments_per_page = 15;

// Manejar edición
if (isset($_GET['editar'])) {
    $editar_id = (int)$_GET['editar'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM departamentos WHERE id = ?");
        $stmt->execute([$editar_id]);
        $departamento_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $mensaje = 'Error al cargar el departamento';
        $tipo_mensaje = 'error';
    }
}

// Manejar eliminación
if (isset($_GET['eliminar'])) {
    $eliminar_id = (int)$_GET['eliminar'];
    try {
        // Verificar si hay tickets asociados
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE departamento_id = ?");
        $stmt->execute([$eliminar_id]);
        $tickets_count = $stmt->fetch()['count'];
        
        if ($tickets_count > 0) {
            $mensaje = 'No se puede eliminar el departamento porque tiene tickets asociados';
            $tipo_mensaje = 'error';
        } else {
            $stmt = $pdo->prepare("DELETE FROM departamentos WHERE id = ?");
            $stmt->execute([$eliminar_id]);
            $mensaje = 'Departamento eliminado exitosamente';
            $tipo_mensaje = 'success';
        }
    } catch (Exception $e) {
        $mensaje = 'Error al eliminar el departamento: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $editar_id = isset($_POST['editar_id']) ? (int)$_POST['editar_id'] : null;
    
    if (empty($nombre)) {
        $mensaje = 'El nombre del departamento es obligatorio';
        $tipo_mensaje = 'error';
    } elseif (empty($tipo)) {
        $mensaje = 'El tipo de departamento es obligatorio';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Verificar si ya existe (excluyendo el que se está editando)
            $sql = "SELECT id FROM departamentos WHERE nombre = ?";
            $params = [$nombre];
            
            if ($editar_id) {
                $sql .= " AND id != ?";
                $params[] = $editar_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $mensaje = 'Ya existe un departamento con ese nombre';
                $tipo_mensaje = 'error';
            } else {
                if ($editar_id) {
                    // Actualizar departamento
                    $stmt = $pdo->prepare("UPDATE departamentos SET nombre = ?, tipo = ? WHERE id = ?");
                    $stmt->execute([$nombre, $tipo, $editar_id]);
                    $mensaje = 'Departamento actualizado exitosamente';
                } else {
                    // Crear departamento
                    $stmt = $pdo->prepare("INSERT INTO departamentos (nombre, tipo) VALUES (?, ?)");
                    $stmt->execute([$nombre, $tipo]);
                    $mensaje = 'Departamento creado exitosamente';
                }
                $tipo_mensaje = 'success';
                
                // Limpiar formulario
                $nombre = '';
                $tipo = '';
                $editar_id = null;
                $departamento_editar = null;
            }
        } catch (Exception $e) {
            $mensaje = 'Error al procesar el departamento: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "nombre LIKE ?";
    $params[] = "%$search%";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Obtener total de departamentos para paginación
try {
    $count_sql = "SELECT COUNT(*) as total FROM departamentos $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_departments = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_departments / $departments_per_page);
} catch (Exception $e) {
    $total_departments = 0;
    $total_pages = 0;
}

// Obtener departamentos con paginación
try {
    $offset = ($page - 1) * $departments_per_page;
    $sql = "SELECT * FROM departamentos $where_clause ORDER BY nombre LIMIT $departments_per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $departamentos = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Departamento - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_admin_dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    
<div class="page-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-text">
                <h1 class="titulo">🏢 Gestión de Departamentos</h1>
                <p class="subtitulo">Crear y administrar departamentos del sistema</p>
            </div>
            <div class="header-actions">
                <a href="../dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Message Alert -->
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>

    <!-- Form Section -->
    <div class="form-section">
        <div class="form-card">
            <h3>
                <i class="fas fa-<?php echo $editar_id ? 'edit' : 'plus-circle'; ?>"></i> 
                <?php echo $editar_id ? 'Editar Departamento' : 'Crear Nuevo Departamento'; ?>
            </h3>
            <form method="POST" class="admin-form">
                <?php if ($editar_id): ?>
                <input type="hidden" name="editar_id" value="<?php echo $editar_id; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="nombre">
                        <i class="fas fa-building"></i> Nombre del Departamento
                    </label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?php echo htmlspecialchars(($departamento_editar['nombre'] ?? $nombre) ?? ''); ?>" 
                           placeholder="Ej: Soporte Técnico, Ventas, Recursos Humanos" required>
                </div>

                <div class="form-group">
                    <label for="tipo">
                        <i class="fas fa-tags"></i> Tipo de Departamento
                    </label>
                    <select id="tipo" name="tipo" required>
                        <option value="">-- Seleccionar tipo --</option>
                        <option value="usuario" <?php echo (($departamento_editar['tipo'] ?? $tipo) ?? '') === 'usuario' ? 'selected' : ''; ?>>
                            👥 Usuario (Para responder tickets)
                        </option>
                        <option value="cliente" <?php echo (($departamento_editar['tipo'] ?? $tipo) ?? '') === 'cliente' ? 'selected' : ''; ?>>
                            🏢 Cliente (Para crear tickets)
                        </option>
                    </select>
                </div>



                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editar_id ? 'Actualizar' : 'Crear'; ?> Departamento
                    </button>
                    <?php if ($editar_id): ?>
                    <a href="crear_departamento.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <?php else: ?>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Limpiar
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Departments List -->
    <div class="departments-section">
        <div class="departments-card">
            <div class="departments-header">
                <h3><i class="fas fa-list"></i> Departamentos Existentes (<?php echo $total_departments; ?>)</h3>
                <div class="search-controls">
                    <form method="GET" class="search-form">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar departamentos...">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <?php if ($search): ?>
                        <a href="?" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="departments-grid">
                <?php if (empty($departamentos)): ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <p>No hay departamentos creados aún</p>
                </div>
                <?php else: ?>
                    <?php foreach ($departamentos as $dept): ?>
                    <div class="department-item">
                        <div class="department-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="department-content">
                            <h4 title="<?php echo htmlspecialchars($dept['nombre']); ?>"><?php echo htmlspecialchars($dept['nombre']); ?></h4>
                            <p class="department-description">
                                <?php if (isset($dept['tipo'])): ?>
                                    <span class="department-type <?php echo $dept['tipo']; ?>">
                                        <?php if ($dept['tipo'] === 'usuario'): ?>
                                            👥 Usuario
                                        <?php elseif ($dept['tipo'] === 'cliente'): ?>
                                            🏢 Cliente
                                        <?php else: ?>
                                            📋 <?php echo ucfirst($dept['tipo']); ?>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    Departamento del sistema
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="department-actions">
                            <div class="action-buttons">
                                <a href="?editar=<?php echo $dept['id']; ?>" class="btn-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?eliminar=<?php echo $dept['id']; ?>" class="btn-delete" title="Eliminar" 
                                   onclick="return confirm('¿Estás seguro de que quieres eliminar este departamento?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                            <span class="department-id">ID: <?php echo $dept['id']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando <?php echo (($page - 1) * $departments_per_page) + 1; ?>-<?php echo min($page * $departments_per_page, $total_departments); ?> de <?php echo $total_departments; ?> departamentos
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
        </div>
    </div>
</div>

<script>
// Auto-submit del formulario de búsqueda
document.querySelector('.search-form input[name="search"]').addEventListener('input', function() {
    const searchTerm = this.value;
    if (searchTerm.length >= 3 || searchTerm.length === 0) {
        // Auto-submit después de 500ms de inactividad
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.form.submit();
        }, 500);
    }
});
</script>

</body>
</html>
