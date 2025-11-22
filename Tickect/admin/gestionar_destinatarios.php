<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/escalamiento.php';

if (!isset($_SESSION["usuario_id"]) || $_SESSION["rol"] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$mensaje = '';
$tipo_mensaje = '';
$editar_id = null;
$destinatario_editar = null;

// Inicializar variables del formulario
$tipo_solicitud = '';
$email_destinatario = '';
$nombre_destinatario = '';
$cargo_destinatario = '';

// Parámetros de paginación y búsqueda
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tipo_filter = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$destinatarios_per_page = 20;

// Manejar edición
if (isset($_GET['editar'])) {
    $editar_id = (int)$_GET['editar'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM escalamiento_destinatarios WHERE id = ?");
        $stmt->execute([$editar_id]);
        $destinatario_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $mensaje = 'Error al cargar el destinatario';
        $tipo_mensaje = 'error';
    }
}

// Manejar eliminación
if (isset($_GET['eliminar'])) {
    $eliminar_id = (int)$_GET['eliminar'];
    try {
        $stmt = $pdo->prepare("DELETE FROM escalamiento_destinatarios WHERE id = ?");
        $stmt->execute([$eliminar_id]);
        $mensaje = 'Destinatario eliminado exitosamente';
        $tipo_mensaje = 'success';
    } catch (Exception $e) {
        $mensaje = 'Error al eliminar el destinatario: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Manejar activación/desactivación
if (isset($_GET['toggle_activo'])) {
    $toggle_id = (int)$_GET['toggle_activo'];
    try {
        $stmt = $pdo->prepare("UPDATE escalamiento_destinatarios SET activo = NOT activo WHERE id = ?");
        $stmt->execute([$toggle_id]);
        $mensaje = 'Estado del destinatario actualizado';
        $tipo_mensaje = 'success';
    } catch (Exception $e) {
        $mensaje = 'Error al actualizar el destinatario: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_solicitud = trim($_POST['tipo_solicitud'] ?? '');
    $email_destinatario = trim($_POST['email_destinatario'] ?? '');
    $nombre_destinatario = trim($_POST['nombre_destinatario'] ?? '');
    $cargo_destinatario = trim($_POST['cargo_destinatario'] ?? '');
    $editar_id = isset($_POST['editar_id']) ? (int)$_POST['editar_id'] : null;
    
    if (empty($tipo_solicitud) || empty($email_destinatario) || empty($nombre_destinatario) || empty($cargo_destinatario)) {
        $mensaje = 'Todos los campos son obligatorios';
        $tipo_mensaje = 'error';
    } elseif (!filter_var($email_destinatario, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El email no tiene un formato válido';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Verificar si ya existe (excluyendo el que se está editando)
            $sql = "SELECT id FROM escalamiento_destinatarios WHERE tipo_solicitud = ? AND email_destinatario = ?";
            $params = [$tipo_solicitud, $email_destinatario];
            
            if ($editar_id) {
                $sql .= " AND id != ?";
                $params[] = $editar_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $mensaje = 'Ya existe un destinatario con ese email para ese tipo de solicitud';
                $tipo_mensaje = 'error';
            } else {
                if ($editar_id) {
                    // Actualizar destinatario
                    $stmt = $pdo->prepare("UPDATE escalamiento_destinatarios SET tipo_solicitud = ?, email_destinatario = ?, nombre_destinatario = ?, cargo_destinatario = ? WHERE id = ?");
                    $stmt->execute([$tipo_solicitud, $email_destinatario, $nombre_destinatario, $cargo_destinatario, $editar_id]);
                    $mensaje = 'Destinatario actualizado exitosamente';
                } else {
                    // Crear destinatario
                    $stmt = $pdo->prepare("INSERT INTO escalamiento_destinatarios (tipo_solicitud, email_destinatario, nombre_destinatario, cargo_destinatario) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$tipo_solicitud, $email_destinatario, $nombre_destinatario, $cargo_destinatario]);
                    $mensaje = 'Destinatario creado exitosamente';
                }
                $tipo_mensaje = 'success';
                
                // Limpiar formulario
                $tipo_solicitud = '';
                $email_destinatario = '';
                $nombre_destinatario = '';
                $cargo_destinatario = '';
                $editar_id = null;
                $destinatario_editar = null;
            }
        } catch (Exception $e) {
            $mensaje = 'Error al procesar el destinatario: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener tipos de solicitud
$tipos_solicitud = obtenerTiposSolicitud();

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(nombre_destinatario LIKE ? OR email_destinatario LIKE ? OR cargo_destinatario LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($tipo_filter) {
    $where_conditions[] = "tipo_solicitud = ?";
    $params[] = $tipo_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Obtener total de destinatarios para paginación
try {
    $count_sql = "SELECT COUNT(*) as total FROM escalamiento_destinatarios $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_destinatarios = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_destinatarios / $destinatarios_per_page);
} catch (Exception $e) {
    $total_destinatarios = 0;
    $total_pages = 0;
}

// Obtener destinatarios con paginación
try {
    $offset = ($page - 1) * $destinatarios_per_page;
    $sql = "
        SELECT * FROM escalamiento_destinatarios 
        $where_clause
        ORDER BY tipo_solicitud, nombre_destinatario 
        LIMIT $destinatarios_per_page OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $destinatarios = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Destinatarios - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_admin_dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    
<div class="page-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-text">
                <h1 class="titulo">📧 Gestión de Destinatarios</h1>
                <p class="subtitulo">Configurar destinatarios para escalamiento de tickets</p>
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
                <i class="fas fa-<?php echo $editar_id ? 'edit' : 'user-plus'; ?>"></i> 
                <?php echo $editar_id ? 'Editar Destinatario' : 'Agregar Nuevo Destinatario'; ?>
            </h3>
            <form method="POST" class="admin-form">
                <?php if ($editar_id): ?>
                <input type="hidden" name="editar_id" value="<?php echo $editar_id; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="tipo_solicitud">
                        <i class="fas fa-tag"></i> Tipo de Solicitud <span class="required">*</span>
                    </label>
                    <select id="tipo_solicitud" name="tipo_solicitud" required>
                        <option value="">-- Selecciona un tipo --</option>
                        <?php foreach ($tipos_solicitud as $key => $nombre): ?>
                        <option value="<?php echo $key; ?>" <?php echo (($destinatario_editar['tipo_solicitud'] ?? $tipo_solicitud) ?? '') === $key ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nombre); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="email_destinatario">
                        <i class="fas fa-envelope"></i> Email del Destinatario <span class="required">*</span>
                    </label>
                    <input type="email" id="email_destinatario" name="email_destinatario" 
                           value="<?php echo htmlspecialchars(($destinatario_editar['email_destinatario'] ?? $email_destinatario) ?? ''); ?>" 
                           placeholder="ejemplo@empresa.com" required>
                </div>

                <div class="form-group">
                    <label for="nombre_destinatario">
                        <i class="fas fa-user"></i> Nombre del Destinatario <span class="required">*</span>
                    </label>
                    <input type="text" id="nombre_destinatario" name="nombre_destinatario" 
                           value="<?php echo htmlspecialchars(($destinatario_editar['nombre_destinatario'] ?? $nombre_destinatario) ?? ''); ?>" 
                           placeholder="Ej: Juan Pérez" required>
                </div>

                <div class="form-group">
                    <label for="cargo_destinatario">
                        <i class="fas fa-briefcase"></i> Cargo del Destinatario <span class="required">*</span>
                    </label>
                    <input type="text" id="cargo_destinatario" name="cargo_destinatario" 
                           value="<?php echo htmlspecialchars(($destinatario_editar['cargo_destinatario'] ?? $cargo_destinatario) ?? ''); ?>" 
                           placeholder="Ej: Director Regional" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editar_id ? 'Actualizar' : 'Crear'; ?> Destinatario
                    </button>
                    <?php if ($editar_id): ?>
                    <a href="gestionar_destinatarios.php" class="btn btn-secondary">
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

    <!-- Destinatarios List -->
    <div class="users-section">
        <div class="users-card">
            <div class="departments-header">
                <h3><i class="fas fa-list"></i> Destinatarios Configurados (<?php echo $total_destinatarios; ?>)</h3>
                <div class="search-controls">
                    <form method="GET" class="search-form">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar destinatarios...">
                        </div>
                        <select name="tipo" class="filter-select">
                            <option value="">Todos los tipos</option>
                            <?php foreach ($tipos_solicitud as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>" <?php echo $tipo_filter === $key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nombre); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <?php if ($search || $tipo_filter): ?>
                        <a href="?" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="users-grid">
                <?php if (empty($destinatarios)): ?>
                <div class="empty-state">
                    <i class="fas fa-envelope"></i>
                    <p>No hay destinatarios configurados</p>
                </div>
                <?php else: ?>
                    <?php foreach ($destinatarios as $destinatario): ?>
                    <div class="user-item">
                        <div class="user-icon user-<?php echo $destinatario['activo'] ? 'active' : 'inactive'; ?>">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="user-content">
                            <h4><?php echo htmlspecialchars($destinatario['nombre_destinatario']); ?></h4>
                            <p class="user-name"><?php echo htmlspecialchars($destinatario['cargo_destinatario']); ?></p>
                            <p>
                                <span class="user-role role-<?php echo $destinatario['activo'] ? 'active' : 'inactive'; ?>">
                                    <?php echo htmlspecialchars($tipos_solicitud[$destinatario['tipo_solicitud']] ?? $destinatario['tipo_solicitud']); ?>
                                </span>
                                <br><small title="<?php echo htmlspecialchars($destinatario['email_destinatario']); ?>">📧 <?php echo htmlspecialchars($destinatario['email_destinatario']); ?></small>
                                <br><small class="hierarchy-badge"><?php echo $destinatario['activo'] ? '✅ Activo' : '❌ Inactivo'; ?></small>
                            </p>
                        </div>
                        <div class="user-actions">
                            <div class="action-buttons">
                                <a href="?editar=<?php echo $destinatario['id']; ?>" class="btn-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?toggle_activo=<?php echo $destinatario['id']; ?>" class="btn-toggle" title="<?php echo $destinatario['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                    <i class="fas fa-<?php echo $destinatario['activo'] ? 'pause' : 'play'; ?>"></i>
                                </a>
                                <a href="?eliminar=<?php echo $destinatario['id']; ?>" class="btn-delete" title="Eliminar" 
                                   onclick="return confirm('¿Estás seguro de que quieres eliminar este destinatario?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                            <span class="user-id">ID: <?php echo $destinatario['id']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando <?php echo (($page - 1) * $destinatarios_per_page) + 1; ?>-<?php echo min($page * $destinatarios_per_page, $total_destinatarios); ?> de <?php echo $total_destinatarios; ?> destinatarios
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

<style>
/* Estilos adicionales para destinatarios */
.user-active {
    background: linear-gradient(135deg, #28a745, #1e7e34);
}

.user-inactive {
    background: linear-gradient(135deg, #6c757d, #5a6268);
}

.role-active {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
}

.role-inactive {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
}

.btn-toggle {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #212529;
}

.btn-toggle:hover {
    background: linear-gradient(135deg, #e0a800, #ffc107);
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}
</style>

</body>
</html>
