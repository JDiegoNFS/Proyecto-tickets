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
$categoria_editar = null;
$nombre = '';
$descripcion = '';
$color = '#4a90e2';
$departamento_id = null;

// Parámetros de paginación y búsqueda
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$categories_per_page = 15;

// Manejar edición
if (isset($_GET['editar'])) {
    $editar_id = (int)$_GET['editar'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
        $stmt->execute([$editar_id]);
        $categoria_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $mensaje = 'Error al cargar la categoría';
        $tipo_mensaje = 'error';
    }
}

// Manejar eliminación
if (isset($_GET['eliminar'])) {
    $eliminar_id = (int)$_GET['eliminar'];
    try {
        // Verificar si hay tickets asociados
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE categoria_id = ?");
        $stmt->execute([$eliminar_id]);
        $tickets_count = $stmt->fetch()['count'];
        
        if ($tickets_count > 0) {
            $mensaje = 'No se puede eliminar la categoría porque tiene tickets asociados';
            $tipo_mensaje = 'error';
        } else {
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$eliminar_id]);
            $mensaje = 'Categoría eliminada exitosamente';
            $tipo_mensaje = 'success';
        }
    } catch (Exception $e) {
        $mensaje = 'Error al eliminar la categoría: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $color = trim($_POST['color'] ?? '#4a90e2');
    $departamento_id = $_POST['departamento_id'] ?? null;
    $editar_id = isset($_POST['editar_id']) ? (int)$_POST['editar_id'] : null;
    
    if (empty($nombre)) {
        $mensaje = 'El nombre de la categoría es obligatorio';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Verificar si ya existe (excluyendo el que se está editando)
            $sql = "SELECT id FROM categorias WHERE nombre = ?";
            $params = [$nombre];
            
            if ($editar_id) {
                $sql .= " AND id != ?";
                $params[] = $editar_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $mensaje = 'Ya existe una categoría con ese nombre';
                $tipo_mensaje = 'error';
            } else {
                if ($editar_id) {
                    // Actualizar categoría
                    $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, descripcion = ?, color = ?, departamento_id = ? WHERE id = ?");
                    $stmt->execute([$nombre, $descripcion, $color, $departamento_id ?: null, $editar_id]);
                    $mensaje = 'Asunto actualizado exitosamente';
                } else {
                    // Crear categoría
                    $stmt = $pdo->prepare("INSERT INTO categorias (nombre, descripcion, color, departamento_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nombre, $descripcion, $color, $departamento_id ?: null]);
                    $mensaje = 'Asunto creado exitosamente';
                }
                $tipo_mensaje = 'success';
                
                // Limpiar formulario
                $nombre = '';
                $descripcion = '';
                $color = '#4a90e2';
                $editar_id = null;
                $categoria_editar = null;
            }
        } catch (Exception $e) {
            $mensaje = 'Error al procesar la categoría: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener departamentos (solo los de tipo 'usuario' para responder tickets)
try {
    $stmtDeptos = $pdo->query("SELECT id, nombre FROM departamentos WHERE tipo = 'usuario' ORDER BY nombre ASC");
    $departamentos = $stmtDeptos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $departamentos = [];
}

// Obtener lista de categorías con departamentos
// Construir consulta con filtros
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(c.nombre LIKE ? OR c.descripcion LIKE ? OR d.nombre LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($dept_filter) {
    $where_conditions[] = "c.departamento_id = ?";
    $params[] = $dept_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Obtener total de categorías para paginación
try {
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM categorias c 
        LEFT JOIN departamentos d ON c.departamento_id = d.id 
        $where_clause
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_categories = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_categories / $categories_per_page);
} catch (Exception $e) {
    $total_categories = 0;
    $total_pages = 0;
}

// Obtener categorías con paginación
try {
    $offset = ($page - 1) * $categories_per_page;
    $sql = "
        SELECT c.*, d.nombre as departamento_nombre 
        FROM categorias c 
        LEFT JOIN departamentos d ON c.departamento_id = d.id 
        $where_clause
        ORDER BY d.nombre, c.nombre 
        LIMIT $categories_per_page OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categorias = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Categoría - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_admin_dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    
<div class="page-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-text">
                <h1 class="titulo">📋 Gestión de Asuntos</h1>
                <p class="subtitulo">Crear y administrar asuntos de tickets por departamento</p>
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
                <?php echo $editar_id ? 'Editar Asunto' : 'Crear Nuevo Asunto'; ?>
            </h3>
            <form method="POST" class="admin-form">
                <?php if ($editar_id): ?>
                <input type="hidden" name="editar_id" value="<?php echo $editar_id; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="departamento_id">
                        <i class="fas fa-building"></i> Departamento (Para responder tickets)
                    </label>
                    <select id="departamento_id" name="departamento_id" required>
                        <option value="">-- Selecciona un Departamento --</option>
                        <?php foreach ($departamentos as $depto): ?>
                        <option value="<?php echo htmlspecialchars($depto['id']); ?>" 
                                <?php echo (($categoria_editar['departamento_id'] ?? $departamento_id) ?? '') == $depto['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($depto['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="nombre">
                        <i class="fas fa-tag"></i> Nombre del Asunto
                    </label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?php echo htmlspecialchars(($categoria_editar['nombre'] ?? $nombre) ?? ''); ?>" 
                           placeholder="Ej: Problema de conexión, Solicitud de software, Consulta técnica" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">
                        <i class="fas fa-align-left"></i> Descripción
                    </label>
                    <textarea id="descripcion" name="descripcion" rows="4" 
                              placeholder="Describe el tipo de tickets que pertenecen a esta categoría"><?php echo htmlspecialchars(($categoria_editar['descripcion'] ?? $descripcion) ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="color">
                        <i class="fas fa-palette"></i> Color de la Categoría
                    </label>
                    <div class="color-input-group">
                        <input type="color" id="color" name="color" 
                               value="<?php echo htmlspecialchars(($categoria_editar['color'] ?? $color) ?? '#4a90e2'); ?>" 
                               class="color-picker">
                        <input type="text" id="color-text" 
                               value="<?php echo htmlspecialchars(($categoria_editar['color'] ?? $color) ?? '#4a90e2'); ?>" 
                               class="color-text" readonly>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editar_id ? 'Actualizar' : 'Crear'; ?> Asunto
                    </button>
                    <?php if ($editar_id): ?>
                    <a href="crear_categoria.php" class="btn btn-secondary">
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

    <!-- Categories List -->
    <div class="categories-section">
        <div class="categories-card">
            <div class="departments-header">
                <h3><i class="fas fa-list"></i> Asuntos Existentes (<?php echo $total_categories; ?>)</h3>
                <div class="search-controls">
                    <form method="GET" class="search-form">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar asuntos...">
                        </div>
                        <select name="dept" class="filter-select">
                            <option value="">Todos los departamentos (Usuario)</option>
                            <?php foreach ($departamentos as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $dept_filter == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <?php if ($search || $dept_filter): ?>
                        <a href="?" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="categories-grid">
                <?php if (empty($categorias)): ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <p>No hay asuntos creados aún</p>
                </div>
                <?php else: ?>
                    <?php foreach ($categorias as $cat): ?>
                    <div class="category-item">
                        <div class="category-icon" style="background-color: <?php echo htmlspecialchars($cat['color']); ?>">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="category-content">
                            <h4 title="<?php echo htmlspecialchars($cat['nombre']); ?>"><?php echo htmlspecialchars($cat['nombre']); ?></h4>
                            <p class="category-description" title="<?php echo htmlspecialchars($cat['descripcion'] ?: 'Sin descripción'); ?>">
                                <?php 
                                $descripcion = $cat['descripcion'] ?: 'Sin descripción';
                                echo htmlspecialchars($descripcion);
                                ?>
                            </p>
                            <?php if ($cat['departamento_nombre']): ?>
                            <p class="category-department" title="<?php echo htmlspecialchars($cat['departamento_nombre']); ?>">
                                <strong>Depto:</strong> <?php echo htmlspecialchars($cat['departamento_nombre']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="category-actions">
                            <div class="action-buttons">
                                <a href="?editar=<?php echo $cat['id']; ?>" class="btn-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?eliminar=<?php echo $cat['id']; ?>" class="btn-delete" title="Eliminar" 
                                   onclick="return confirm('¿Estás seguro de que quieres eliminar esta categoría?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                            <span class="category-color" style="background-color: <?php echo htmlspecialchars($cat['color']); ?>"></span>
                            <span class="category-id">ID: <?php echo $cat['id']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando <?php echo (($page - 1) * $categories_per_page) + 1; ?>-<?php echo min($page * $categories_per_page, $total_categories); ?> de <?php echo $total_categories; ?> asuntos
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
// Sincronizar color picker con texto
document.getElementById('color').addEventListener('input', function() {
    document.getElementById('color-text').value = this.value;
});

document.getElementById('color-text').addEventListener('input', function() {
    document.getElementById('color').value = this.value;
});

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
