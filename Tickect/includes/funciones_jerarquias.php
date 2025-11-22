<?php
/**
 * Funciones para manejar jerarquías de usuarios
 * Sistema de Tickets con Jerarquías Organizacionales
 */

require_once 'db.php';

/**
 * Obtiene la jerarquía de un usuario
 */
function obtenerJerarquiaUsuario($usuario_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.*, d.tipo as departamento_tipo, j.nivel as jerarquia_nivel
        FROM usuarios u
        LEFT JOIN departamentos d ON u.departamento_id = d.id
        LEFT JOIN jerarquias j ON u.jerarquia = j.nombre
        WHERE u.id = ?
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetch();
}

/**
 * Obtiene usuarios visibles para un usuario según su jerarquía
 */
function obtenerUsuariosVisibles($usuario_id) {
    global $pdo;
    
    $usuario = obtenerJerarquiaUsuario($usuario_id);
    
    if (!$usuario || $usuario['rol'] !== 'cliente') {
        return [$usuario_id]; // Solo su propio ID
    }
    
    $departamento_id = $usuario['departamento_id'];
    $jerarquia = $usuario['jerarquia'];
    
    // Definir jerarquías visibles según el nivel
    $jerarquias_visibles = [];
    
    switch ($jerarquia) {
        case 'jefe_tienda':
            // Jefe (nivel 1) solo puede ver sus propios tickets
            return [$usuario_id];
            
        case 'asistente_tienda':
            // Asistente (nivel 2) puede ver jefes y asistentes del departamento
            $jerarquias_visibles = ['jefe_tienda', 'asistente_tienda'];
            break;
            
        case 'sub_gerente_tienda':
            // Sub gerente (nivel 3) puede ver todos los tickets de su departamento
            $jerarquias_visibles = ['jefe_tienda', 'asistente_tienda', 'sub_gerente_tienda', 'gerente_tienda'];
            break;
            
        case 'gerente_tienda':
            // Gerente (nivel 4) puede ver todos los tickets de su departamento
            $jerarquias_visibles = ['jefe_tienda', 'asistente_tienda', 'sub_gerente_tienda', 'gerente_tienda'];
            break;
            
        default:
            return [$usuario_id];
    }
    
    // Construir consulta para obtener usuarios visibles
    $placeholders = str_repeat('?,', count($jerarquias_visibles) - 1) . '?';
    
    $stmt = $pdo->prepare("
        SELECT id FROM usuarios 
        WHERE departamento_id = ? 
        AND rol = 'cliente' 
        AND jerarquia IN ($placeholders)
    ");
    
    $params = array_merge([$departamento_id], $jerarquias_visibles);
    $stmt->execute($params);
    
    $usuarios_visibles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Asegurar que el usuario actual esté incluido
    if (!in_array($usuario_id, $usuarios_visibles)) {
        $usuarios_visibles[] = $usuario_id;
    }
    
    return $usuarios_visibles;
}

/**
 * Obtiene departamentos disponibles para crear tickets
 * Solo muestra departamentos de tipo 'usuario'
 */
function obtenerDepartamentosParaTickets() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id, nombre 
        FROM departamentos 
        WHERE tipo = 'usuario'
        ORDER BY nombre
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Verifica si un usuario puede ver un ticket específico
 */
function puedeVerTicket($usuario_id, $ticket_id) {
    global $pdo;
    
    $usuario = obtenerJerarquiaUsuario($usuario_id);
    
    if (!$usuario) {
        return false;
    }
    
    // Si es admin o usuario, puede ver todos los tickets
    if ($usuario['rol'] === 'admin' || $usuario['rol'] === 'usuario') {
        return true;
    }
    
    // Si es cliente, verificar jerarquía
    if ($usuario['rol'] === 'cliente') {
        $usuarios_visibles = obtenerUsuariosVisibles($usuario_id);
        
        // Obtener información del ticket
        $stmt = $pdo->prepare("
            SELECT usuario_id, cliente_id, departamento_id 
            FROM tickets 
            WHERE id = ?
        ");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();
        
        if (!$ticket) {
            return false;
        }
        
        // Verificar si el creador del ticket está en los usuarios visibles
        return in_array($ticket['cliente_id'], $usuarios_visibles);
    }
    
    return false;
}

/**
 * Obtiene tickets visibles para un usuario según su jerarquía
 */
function obtenerTicketsVisibles($usuario_id, $filtros = []) {
    global $pdo;
    
    $usuario = obtenerJerarquiaUsuario($usuario_id);
    
    if (!$usuario) {
        return [];
    }
    
    $where_conditions = [];
    $params = [];
    
    // Si es admin, puede ver todos los tickets
    if ($usuario['rol'] === 'admin') {
        $where_conditions[] = "1=1";
    }
    // Si es usuario, puede ver tickets de su departamento
    elseif ($usuario['rol'] === 'usuario') {
        $where_conditions[] = "t.departamento_id = ?";
        $params[] = $usuario['departamento_id'];
    }
    // Si es cliente, aplicar lógica de jerarquía
    elseif ($usuario['rol'] === 'cliente') {
        $usuarios_visibles = obtenerUsuariosVisibles($usuario_id);
        $placeholders = str_repeat('?,', count($usuarios_visibles) - 1) . '?';
        // Solo ver tickets creados por usuarios visibles (incluyendo el propio usuario)
        $where_conditions[] = "t.cliente_id IN ($placeholders)";
        $params = array_merge($params, $usuarios_visibles);
    }
    
    // Aplicar filtros adicionales
    if (isset($filtros['estado']) && $filtros['estado']) {
        $where_conditions[] = "t.estado = ?";
        $params[] = $filtros['estado'];
    }
    
    if (isset($filtros['departamento_id']) && $filtros['departamento_id']) {
        $where_conditions[] = "t.departamento_id = ?";
        $params[] = $filtros['departamento_id'];
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "
        SELECT t.id, t.descripcion, t.estado, t.fecha_creacion, t.fecha_cierre,
               c.nombre AS categoria_nombre,
               d.nombre AS departamento_nombre,
               u.usuario AS asignado_nombre,
               u2.usuario AS creador_nombre
        FROM tickets t
        LEFT JOIN categorias c ON t.categoria_id = c.id
        LEFT JOIN departamentos d ON t.departamento_id = d.id
        LEFT JOIN usuarios u ON t.usuario_id = u.id
        LEFT JOIN usuarios u2 ON t.cliente_id = u2.id
        $where_clause
        ORDER BY t.fecha_creacion DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
    
    return [
        'total' => count($tickets),
        'tickets' => $tickets
    ];
}

/**
 * Obtiene estadísticas de tickets para un usuario según su jerarquía
 */
function obtenerEstadisticasTickets($usuario_id) {
    global $pdo;
    
    $usuario = obtenerJerarquiaUsuario($usuario_id);
    
    if (!$usuario) {
        return [
            'total' => 0,
            'pendientes' => 0,
            'en_proceso' => 0,
            'cerrados' => 0
        ];
    }
    
    $where_conditions = [];
    $params = [];
    
    // Aplicar lógica de jerarquía
    if ($usuario['rol'] === 'admin') {
        $where_conditions[] = "1=1";
    } elseif ($usuario['rol'] === 'usuario') {
        $where_conditions[] = "departamento_id = ?";
        $params[] = $usuario['departamento_id'];
    } elseif ($usuario['rol'] === 'cliente') {
        $usuarios_visibles = obtenerUsuariosVisibles($usuario_id);
        $placeholders = str_repeat('?,', count($usuarios_visibles) - 1) . '?';
        $where_conditions[] = "(cliente_id IN ($placeholders) OR usuario_id IN ($placeholders) OR (usuario_id IS NULL AND cliente_id IN ($placeholders)))";
        $params = array_merge($params, $usuarios_visibles, $usuarios_visibles, $usuarios_visibles);
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stats = [];
    
    // Total de tickets
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets $where_clause");
    $stmt->execute($params);
    $stats['total'] = $stmt->fetchColumn();
    
    // Tickets pendientes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets $where_clause AND estado = 'pendiente'");
    $stmt->execute($params);
    $stats['pendientes'] = $stmt->fetchColumn();
    
    // Tickets en proceso
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets $where_clause AND estado = 'en_proceso'");
    $stmt->execute($params);
    $stats['en_proceso'] = $stmt->fetchColumn();
    
    // Tickets cerrados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets $where_clause AND estado = 'cerrado'");
    $stmt->execute($params);
    $stats['cerrados'] = $stmt->fetchColumn();
    
    return $stats;
}

/**
 * Obtiene la cadena de jerarquía de un usuario
 */
function obtenerCadenaJerarquia($usuario_id) {
    global $pdo;
    
    $cadena = [];
    $usuario_actual = $usuario_id;
    
    while ($usuario_actual) {
        $stmt = $pdo->prepare("
            SELECT u.id, u.usuario, u.jerarquia, u.superior_id, j.nivel
            FROM usuarios u
            LEFT JOIN jerarquias j ON u.jerarquia = j.nombre
            WHERE u.id = ?
        ");
        $stmt->execute([$usuario_actual]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) break;
        
        $cadena[] = [
            'id' => $usuario['id'],
            'usuario' => $usuario['usuario'],
            'jerarquia' => $usuario['jerarquia'],
            'nivel' => $usuario['nivel']
        ];
        
        $usuario_actual = $usuario['superior_id'];
    }
    
    return $cadena;
}

/**
 * Verifica si un usuario es superior jerárquico de otro
 */
function esSuperiorJerarquico($superior_id, $subordinado_id) {
    $cadena = obtenerCadenaJerarquia($subordinado_id);
    
    foreach ($cadena as $usuario) {
        if ($usuario['id'] == $superior_id) {
            return true;
        }
    }
    
    return false;
}
?>
