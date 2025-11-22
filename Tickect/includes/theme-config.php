<?php
/**
 * CONFIGURACIÓN GLOBAL DE TEMAS
 * Incluir este archivo en todas las páginas para habilitar el sistema de temas
 */

function includeThemeAssets() {
    echo '
    <!-- Sistema de Temas -->
    <link rel="stylesheet" href="' . getThemeBasePath() . 'css/themes.css">
    <meta name="theme-color" content="#4a90e2">
    <meta name="color-scheme" content="light dark">
    ';
}

function includeThemeScript() {
    echo '
    <!-- Sistema de Temas JavaScript -->
    <script src="' . getThemeBasePath() . 'js/theme-manager.js"></script>
    ';
}

function getThemeBasePath() {
    // Detectar la profundidad del directorio actual para ajustar la ruta
    $currentPath = $_SERVER['REQUEST_URI'];
    $depth = substr_count($currentPath, '/') - substr_count($_SERVER['DOCUMENT_ROOT'], '/');
    
    // Ajustar según la estructura del proyecto
    if (strpos($currentPath, '/usuario/') !== false || 
        strpos($currentPath, '/cliente/') !== false || 
        strpos($currentPath, '/admin/') !== false) {
        return '../';
    }
    
    return '';
}

function addThemeBodyClass() {
    echo ' data-theme-enabled="true"';
}

// Función para páginas específicas que necesiten configuración especial
function getThemePageConfig($pageName) {
    $configs = [
        'dashboard' => [
            'title_suffix' => ' - Panel Admin',
            'primary_color' => '#4a90e2',
            'features' => ['auto-theme', 'notifications', 'shortcuts']
        ],
        'login' => [
            'title_suffix' => ' - Iniciar Sesión',
            'primary_color' => '#4a90e2',
            'features' => ['basic-themes']
        ],
        'tickets' => [
            'title_suffix' => ' - Tickets',
            'primary_color' => '#4a90e2',
            'features' => ['auto-theme', 'notifications']
        ]
    ];
    
    return $configs[$pageName] ?? $configs['dashboard'];
}
?>