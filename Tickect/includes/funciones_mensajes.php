<?php
/**
 * Funciones para manejar el contenido de los mensajes
 */

function procesarMensaje($mensaje) {
    // Si el mensaje está vacío, devolver mensaje por defecto
    if (empty($mensaje)) {
        return '<em>Sin mensaje</em>';
    }
    
    // Limpiar el mensaje de caracteres problemáticos
    $mensaje = htmlspecialchars_decode($mensaje);
    
    // Si contiene imágenes base64, procesarlas
    if (strpos($mensaje, 'data:image/') !== false) {
        $mensaje = procesarImagenesBase64($mensaje);
    }
    
    return $mensaje;
}

function procesarImagenesBase64($mensaje) {
    // Patrón para encontrar imágenes base64
    $patron = '/<img[^>]+src="data:image\/[^"]+"[^>]*>/i';
    
    // Reemplazar imágenes base64 con una versión optimizada
    $mensaje = preg_replace_callback($patron, function($matches) {
        $imgTag = $matches[0];
        
        // Extraer el tipo de imagen
        if (preg_match('/data:image\/([^;]+)/', $imgTag, $tipoMatches)) {
            $tipoImagen = $tipoMatches[1];
        } else {
            $tipoImagen = 'png';
        }
        
        // Validar que el data URL esté completo
        if (preg_match('/data:image\/[^;]+;base64,([A-Za-z0-9+\/]+=*)/', $imgTag, $dataMatches)) {
            $base64Data = $dataMatches[1];
            // Verificar que el base64 sea válido
            if (base64_decode($base64Data, true) !== false) {
                // Crear una imagen simple y redimensionada
                return '<div class="imagen-simple">
                            <div class="imagen-titulo">
                                <i class="fas fa-image"></i> Imagen (' . strtoupper($tipoImagen) . ')
                            </div>
                            ' . $imgTag . '
                        </div>';
            }
        }
        
        // Si la imagen no es válida, mostrar solo un placeholder
        return '<div class="imagen-simple">
                    <div class="imagen-titulo">
                        <i class="fas fa-image"></i> Imagen (' . strtoupper($tipoImagen) . ') - Error
                    </div>
                    <div class="imagen-error">
                        <i class="fas fa-exclamation-triangle"></i> Imagen no válida
                    </div>
                </div>';
    }, $mensaje);
    
    return $mensaje;
}

function truncarMensaje($mensaje, $longitud = 500) {
    if (strlen($mensaje) <= $longitud) {
        return $mensaje;
    }
    
    // Si contiene imágenes base64, truncar más agresivamente
    if (strpos($mensaje, 'data:image/') !== false) {
        $longitud = 200;
    }
    
    return substr($mensaje, 0, $longitud) . '...';
}

function limpiarMensajeParaVista($mensaje) {
    // Procesar el mensaje
    $mensaje = procesarMensaje($mensaje);
    
    // Si es muy largo, truncarlo
    if (strlen($mensaje) > 1000) {
        $mensaje = truncarMensaje($mensaje, 800);
        $mensaje .= '<br><small><em>Mensaje truncado. Ver completo en la conversación.</em></small>';
    }
    
    return $mensaje;
}

function mostrarDescripcionCompleta($mensaje) {
    // Para la descripción completa, mostrar todo el contenido
    if (empty($mensaje)) {
        return '<em>Sin descripción</em>';
    }
    
    // Limpiar el mensaje pero mantener las imágenes
    $mensaje = htmlspecialchars_decode($mensaje);
    
    // Si contiene imágenes base64, procesarlas para mostrar
    if (strpos($mensaje, 'data:image/') !== false) {
        $mensaje = procesarImagenesBase64($mensaje);
    }
    
    return $mensaje;
}

function mostrarDescripcionSimple($mensaje) {
    // Para mostrar en listas, versión simplificada
    if (empty($mensaje)) {
        return 'Sin descripción';
    }
    
    // Si contiene imágenes base64, reemplazarlas con texto simple
    if (strpos($mensaje, 'data:image/') !== false) {
        $mensaje = preg_replace('/<img[^>]+src="data:image\/[^"]+"[^>]*>/i', '[Imagen]', $mensaje);
    }
    
    // Limpiar HTML y truncar
    $mensaje = strip_tags($mensaje);
    $mensaje = htmlspecialchars($mensaje);
    
    // Si es muy largo, truncar
    if (strlen($mensaje) > 80) {
        $mensaje = mb_strimwidth($mensaje, 0, 80, "...");
    }
    
    return $mensaje;
}
?>
