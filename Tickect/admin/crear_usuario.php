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
$usuario_editar = null;
$usuario = '';
$nombre = '';
$clave = '';
$rol = '';
$departamento_id = null;
$jerarquia = '';
$superior_id = null;

// Parámetros de paginación y búsqueda
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$users_per_page = 20;

// Manejar edición
if (isset($_GET['editar'])) {
    $editar_id = (int)$_GET['editar'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$editar_id]);
        $usuario_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $mensaje = 'Error al cargar el usuario';
        $tipo_mensaje = 'error';
    }
}

// Manejar eliminación
if (isset($_GET['eliminar'])) {
    $eliminar_id = (int)$_GET['eliminar'];
    try {
        // No permitir eliminar el usuario actual
        if ($eliminar_id == $_SESSION['usuario_id']) {
            $mensaje = 'No puedes eliminar tu propio usuario';
            $tipo_mensaje = 'error';
        } else {
            // Verificar si hay tickets asociados
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE usuario_id = ? OR cliente_id = ?");
            $stmt->execute([$eliminar_id, $eliminar_id]);
            $tickets_count = $stmt->fetch()['count'];
            
            if ($tickets_count > 0) {
                $mensaje = 'No se puede eliminar el usuario porque tiene tickets asociados';
                $tipo_mensaje = 'error';
            } else {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$eliminar_id]);
                $mensaje = 'Usuario eliminado exitosamente';
                $tipo_mensaje = 'success';
            }
        }
    } catch (Exception $e) {
        $mensaje = 'Error al eliminar el usuario: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $clave = trim($_POST['clave'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $departamento_id = $_POST['departamento_id'] ?? null;
    $jerarquia = $_POST['jerarquia'] ?? '';
    $superior_id = $_POST['superior_id'] ?? null;
    $editar_id = isset($_POST['editar_id']) ? (int)$_POST['editar_id'] : null;
    
    if (empty($usuario) || empty($rol)) {
        $mensaje = 'Usuario y rol son obligatorios';
        $tipo_mensaje = 'error';
    } elseif (!$editar_id && empty($clave)) {
        $mensaje = 'La contraseña es obligatoria para nuevos usuarios';
        $tipo_mensaje = 'error';
    } elseif (empty($departamento_id)) {
        $mensaje = 'El departamento es obligatorio';
        $tipo_mensaje = 'error';
    } elseif ($rol === 'cliente' && empty($jerarquia)) {
        $mensaje = 'La jerarquía es obligatoria para usuarios cliente';
        $tipo_mensaje = 'error';
    } elseif (!in_array($rol, ['admin', 'usuario', 'cliente'])) {
        $mensaje = 'Rol inválido';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Verificar si ya existe (excluyendo el que se está editando)
            $sql = "SELECT id FROM usuarios WHERE usuario = ?";
            $params = [$usuario];
            
            if ($editar_id) {
                $sql .= " AND id != ?";
                $params[] = $editar_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $mensaje = 'El nombre de usuario ya existe';
                $tipo_mensaje = 'error';
            } else {
                if ($editar_id) {
                    // Actualizar usuario
                    if (!empty($clave)) {
                        $stmt = $pdo->prepare("UPDATE usuarios SET usuario = ?, nombre = ?, clave = ?, rol = ?, departamento_id = ?, jerarquia = ?, superior_id = ? WHERE id = ?");
                        $stmt->execute([$usuario, $nombre ?: null, $clave, $rol, $departamento_id ?: null, $jerarquia ?: null, $superior_id ?: null, $editar_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE usuarios SET usuario = ?, nombre = ?, rol = ?, departamento_id = ?, jerarquia = ?, superior_id = ? WHERE id = ?");
                        $stmt->execute([$usuario, $nombre ?: null, $rol, $departamento_id ?: null, $jerarquia ?: null, $superior_id ?: null, $editar_id]);
                    }
                    $mensaje = 'Usuario actualizado exitosamente';
                } else {
                    // Crear usuario
                    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, nombre, clave, rol, departamento_id, jerarquia, superior_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$usuario, $nombre ?: null, $clave, $rol, $departamento_id ?: null, $jerarquia ?: null, $superior_id ?: null]);
                    $mensaje = 'Usuario creado exitosamente';
                }
                $tipo_mensaje = 'success';
                
                // Limpiar formulario
                $usuario = '';
                $nombre = '';
                $clave = '';
                $rol = '';
                $departamento_id = null;
                $jerarquia = '';
                $superior_id = null;
                $editar_id = null;
                $usuario_editar = null;
            }
        } catch (Exception $e) {
            $mensaje = 'Error al procesar el usuario: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener departamentos
try {
    $stmt = $pdo->query("SELECT id, nombre, tipo FROM departamentos ORDER BY nombre");
    $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $departamentos = [];
}

// Obtener usuarios superiores (para jerarquías) - ordenados por nivel jerárquico
try {
    $stmt = $pdo->query("
        SELECT u.id, u.usuario, u.jerarquia, j.nivel 
        FROM usuarios u 
        LEFT JOIN jerarquias j ON u.jerarquia = j.nombre 
        WHERE u.rol = 'cliente' AND u.jerarquia IS NOT NULL 
        ORDER BY j.nivel ASC, u.usuario
    ");
    $usuarios_superiores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usuarios_superiores = [];
}

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(u.usuario LIKE ? OR d.nombre LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    $where_conditions[] = "u.rol = ?";
    $params[] = $role_filter;
}

if ($dept_filter) {
    $where_conditions[] = "u.departamento_id = ?";
    $params[] = $dept_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Obtener total de usuarios para paginación
try {
    $count_sql = "SELECT COUNT(*) as total FROM usuarios u LEFT JOIN departamentos d ON u.departamento_id = d.id $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_users = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_users / $users_per_page);
} catch (Exception $e) {
    $total_users = 0;
    $total_pages = 0;
}

// Obtener usuarios con paginación
try {
    $offset = ($page - 1) * $users_per_page;
    $sql = "
        SELECT u.id, u.usuario, u.nombre, u.rol, u.jerarquia, d.nombre as departamento 
        FROM usuarios u 
        LEFT JOIN departamentos d ON u.departamento_id = d.id 
        $where_clause
        ORDER BY u.id DESC 
        LIMIT $users_per_page OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usuarios = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_admin_dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    
<div class="page-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-text">
                <h1 class="titulo">👥 Gestión de Usuarios</h1>
                <p class="subtitulo">Crear y administrar usuarios del sistema</p>
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
                <?php echo $editar_id ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?>
            </h3>
            <form method="POST" class="admin-form">
                <?php if ($editar_id): ?>
                <input type="hidden" name="editar_id" value="<?php echo $editar_id; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="usuario">
                        <i class="fas fa-user"></i> Nombre de Usuario <span class="required">*</span>
                    </label>
                    <input type="text" id="usuario" name="usuario" 
                           value="<?php echo htmlspecialchars(($usuario_editar['usuario'] ?? $usuario) ?? ''); ?>" 
                           placeholder="Ej: juan.perez, maria.garcia" required>
                </div>

                <div class="form-group">
                    <label for="nombre">
                        <i class="fas fa-id-card"></i> Nombre Completo
                    </label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?php echo htmlspecialchars(($usuario_editar['nombre'] ?? $nombre) ?? ''); ?>" 
                           placeholder="Ej: Juan Pérez, María García">
                </div>

                <div class="form-group">
                    <label for="clave">
                        <i class="fas fa-lock"></i> Contraseña <span class="required" id="clave-required" <?php echo $editar_id ? 'style="display: none;"' : ''; ?>>*</span>
                    </label>
                    <input type="password" id="clave" name="clave" 
                           placeholder="<?php echo $editar_id ? 'Dejar vacío para mantener la actual' : 'Mínimo 6 caracteres'; ?>" 
                           <?php echo !$editar_id ? 'required' : ''; ?>>
                </div>

                <div class="form-group">
                    <label for="rol">
                        <i class="fas fa-user-tag"></i> Rol del Usuario <span class="required">*</span>
                    </label>
                    <select id="rol" name="rol" required>
                        <option value="">-- Selecciona un rol --</option>
                        <option value="admin" <?php echo (($usuario_editar['rol'] ?? $rol) ?? '') === 'admin' ? 'selected' : ''; ?>>👑 Administrador</option>
                        <option value="usuario" <?php echo (($usuario_editar['rol'] ?? $rol) ?? '') === 'usuario' ? 'selected' : ''; ?>>👤 Usuario</option>
                        <option value="cliente" <?php echo (($usuario_editar['rol'] ?? $rol) ?? '') === 'cliente' ? 'selected' : ''; ?>>🏢 Cliente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="departamento_id">
                        <i class="fas fa-building"></i> Departamento <span class="required">*</span>
                    </label>
                    <select id="departamento_id" name="departamento_id" required>
                        <option value="">-- Selecciona un departamento --</option>
                        <?php foreach ($departamentos as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" 
                                data-tipo="<?php echo htmlspecialchars($dept['tipo'] ?? ''); ?>"
                                <?php echo (($usuario_editar['departamento_id'] ?? $departamento_id) ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['nombre']); ?>
                            <?php if (isset($dept['tipo'])): ?>
                                (<?php echo $dept['tipo'] === 'usuario' ? '👥 Usuario' : '🏢 Cliente'; ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="jerarquia-group" style="display: none;">
                    <label for="jerarquia">
                        <i class="fas fa-sitemap"></i> Jerarquía <span class="required">*</span>
                    </label>
                    <select id="jerarquia" name="jerarquia">
                        <option value="">-- Selecciona una jerarquía --</option>
                        <option value="jefe_tienda" <?php echo (($usuario_editar['jerarquia'] ?? $jerarquia) ?? '') === 'jefe_tienda' ? 'selected' : ''; ?>>👑 Jefe de Tienda (Nivel 1)</option>
                        <option value="asistente_tienda" <?php echo (($usuario_editar['jerarquia'] ?? $jerarquia) ?? '') === 'asistente_tienda' ? 'selected' : ''; ?>>📋 Asistente de Tienda (Nivel 2)</option>
                        <option value="sub_gerente_tienda" <?php echo (($usuario_editar['jerarquia'] ?? $jerarquia) ?? '') === 'sub_gerente_tienda' ? 'selected' : ''; ?>>👤 Sub Gerente de Tienda (Nivel 3)</option>
                        <option value="gerente_tienda" <?php echo (($usuario_editar['jerarquia'] ?? $jerarquia) ?? '') === 'gerente_tienda' ? 'selected' : ''; ?>>👔 Gerente de Tienda (Nivel 4)</option>
                    </select>
                </div>

                <div class="form-group" id="superior-group" style="display: none;">
                    <label for="superior_id">
                        <i class="fas fa-user-tie"></i> Superior Jerárquico (Opcional)
                    </label>
                    <select id="superior_id" name="superior_id">
                        <option value="">-- Sin superior --</option>
                        <?php foreach ($usuarios_superiores as $superior): ?>
                        <option value="<?php echo $superior['id']; ?>" 
                                <?php echo (($usuario_editar['superior_id'] ?? $superior_id) ?? '') == $superior['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($superior['usuario']); ?> (Nivel <?php echo $superior['nivel']; ?> - <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $superior['jerarquia']))); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editar_id ? 'Actualizar' : 'Crear'; ?> Usuario
                    </button>
                    <?php if ($editar_id): ?>
                    <a href="crear_usuario.php" class="btn btn-secondary">
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

    <!-- Users List -->
    <div class="users-section">
        <div class="users-card">
            <div class="departments-header">
                <h3><i class="fas fa-list"></i> Usuarios Existentes (<?php echo $total_users; ?>)</h3>
                <div class="search-controls">
                    <form method="GET" class="search-form">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar usuarios...">
                        </div>
                        <select name="role" class="filter-select">
                            <option value="">Todos los roles</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>👑 Administradores</option>
                            <option value="usuario" <?php echo $role_filter === 'usuario' ? 'selected' : ''; ?>>👤 Usuarios</option>
                            <option value="cliente" <?php echo $role_filter === 'cliente' ? 'selected' : ''; ?>>🏢 Clientes</option>
                        </select>
                        <select name="dept" class="filter-select">
                            <option value="">Todos los departamentos</option>
                            <?php foreach ($departamentos as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $dept_filter == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <?php if ($search || $role_filter || $dept_filter): ?>
                        <a href="?" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="users-grid">
                <?php if (empty($usuarios)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>No hay usuarios creados aún</p>
                </div>
                <?php else: ?>
                    <?php foreach ($usuarios as $user): ?>
                    <div class="user-item">
                        <div class="user-icon user-<?php echo $user['rol']; ?>">
                            <i class="fas fa-<?php echo $user['rol'] === 'admin' ? 'crown' : ($user['rol'] === 'usuario' ? 'user' : 'building'); ?>"></i>
                        </div>
                        <div class="user-content">
                            <h4><?php echo htmlspecialchars($user['usuario']); ?></h4>
                            <?php if ($user['nombre']): ?>
                            <p class="user-name"><?php echo htmlspecialchars($user['nombre']); ?></p>
                            <?php endif; ?>
                            <p>
                                <?php 
                                $role_text = $user['rol'] === 'admin' ? '👑 Administrador' : 
                                           ($user['rol'] === 'usuario' ? '👤 Usuario' : '🏢 Cliente');
                                $role_class = strlen($role_text) > 15 ? 'user-role user-role-long role-' . $user['rol'] : 'user-role role-' . $user['rol'];
                                ?>
                                <span class="<?php echo $role_class; ?>" 
                                      <?php if (strlen($role_text) > 15): ?>data-full-role="<?php echo htmlspecialchars($role_text); ?>"<?php endif; ?>>
                                    <?php echo htmlspecialchars($role_text); ?>
                                </span>
                                <?php if ($user['jerarquia']): ?>
                                <br><small class="hierarchy-badge">🏢 <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user['jerarquia']))); ?></small>
                                <?php endif; ?>
                                <?php if ($user['departamento']): ?>
                                <br><small title="<?php echo htmlspecialchars($user['departamento']); ?>">📁 <?php echo htmlspecialchars(strlen($user['departamento']) > 20 ? substr($user['departamento'], 0, 20) . '...' : $user['departamento']); ?></small>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="user-actions">
                            <div class="action-buttons">
                                <a href="?editar=<?php echo $user['id']; ?>" class="btn-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($user['id'] != $_SESSION['usuario_id']): ?>
                                <a href="?eliminar=<?php echo $user['id']; ?>" class="btn-delete" title="Eliminar" 
                                   onclick="return confirm('¿Estás seguro de que quieres eliminar este usuario?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php else: ?>
                                <span class="btn-current-user" title="Usuario actual">
                                    <i class="fas fa-user-check"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                            <span class="user-id">ID: <?php echo $user['id']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando <?php echo (($page - 1) * $users_per_page) + 1; ?>-<?php echo min($page * $users_per_page, $total_users); ?> de <?php echo $total_users; ?> usuarios
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
// Función para filtrar departamentos según el rol
function filtrarDepartamentos(rol) {
    const selectDepartamento = document.getElementById('departamento_id');
    const opciones = selectDepartamento.querySelectorAll('option');
    const valorActual = selectDepartamento.value;
    
    // Mostrar/ocultar opciones según el rol
    opciones.forEach(opcion => {
        if (opcion.value === '') {
            // Siempre mostrar la opción "Sin departamento"
            opcion.style.display = 'block';
        } else {
            const tipoDepartamento = opcion.getAttribute('data-tipo');
            
            if (rol === 'admin') {
                // Admin puede ver todos los departamentos
                opcion.style.display = 'block';
            } else if (rol === 'usuario') {
                // Usuario solo ve departamentos de tipo 'usuario'
                opcion.style.display = tipoDepartamento === 'usuario' ? 'block' : 'none';
            } else if (rol === 'cliente') {
                // Cliente solo ve departamentos de tipo 'cliente'
                opcion.style.display = tipoDepartamento === 'cliente' ? 'block' : 'none';
            } else {
                // Si no hay rol seleccionado, mostrar todos
                opcion.style.display = 'block';
            }
        }
    });
    
    // Si el departamento actual no es compatible con el nuevo rol, limpiarlo
    const opcionSeleccionada = selectDepartamento.querySelector('option:checked');
    if (opcionSeleccionada && opcionSeleccionada.style.display === 'none') {
        selectDepartamento.value = '';
    }
}

// Mostrar/ocultar campos de jerarquía según el rol
document.getElementById('rol').addEventListener('change', function() {
    const rol = this.value;
    const jerarquiaGroup = document.getElementById('jerarquia-group');
    const superiorGroup = document.getElementById('superior-group');
    
    // Filtrar departamentos según el rol
    filtrarDepartamentos(rol);
    
    if (rol === 'cliente') {
        jerarquiaGroup.style.display = 'block';
        superiorGroup.style.display = 'block';
        // Hacer jerarquía requerida para clientes
        document.getElementById('jerarquia').required = true;
    } else {
        jerarquiaGroup.style.display = 'none';
        superiorGroup.style.display = 'none';
        // Limpiar valores cuando se ocultan
        document.getElementById('jerarquia').value = '';
        document.getElementById('superior_id').value = '';
        // Quitar requerimiento cuando no es cliente
        document.getElementById('jerarquia').required = false;
    }
});

// Mostrar campos si ya está editando un cliente
document.addEventListener('DOMContentLoaded', function() {
    const rol = document.getElementById('rol').value;
    
    // Aplicar filtro inicial de departamentos
    filtrarDepartamentos(rol);
    
    if (rol === 'cliente') {
        document.getElementById('jerarquia-group').style.display = 'block';
        document.getElementById('superior-group').style.display = 'block';
        // Hacer jerarquía requerida para clientes
        document.getElementById('jerarquia').required = true;
    }
});
</script>

</body>
</html>