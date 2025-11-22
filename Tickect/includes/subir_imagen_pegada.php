<?php
// Archivo para manejar la subida de imágenes pegadas
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Verificar que se recibió una imagen
if (!isset($_POST['imagen']) || empty($_POST['imagen'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió ninguna imagen']);
    exit;
}

$imagenData = $_POST['imagen'];

// Verificar que es una imagen válida (base64)
if (strpos($imagenData, 'data:image/') !== 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de imagen inválido']);
    exit;
}

// Extraer el tipo de imagen y los datos
$matches = [];
if (!preg_match('/^data:image\/(\w+);base64,/', $imagenData, $matches)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de imagen no válido']);
    exit;
}

$tipoImagen = $matches[1];
$datosImagen = substr($imagenData, strpos($imagenData, ',') + 1);
$datosImagen = base64_decode($datosImagen);

// Verificar tipos de imagen permitidos
$tiposPermitidos = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
if (!in_array(strtolower($tipoImagen), $tiposPermitidos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de imagen no permitido']);
    exit;
}

// Verificar tamaño (máximo 5MB)
if (strlen($datosImagen) > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Imagen demasiado grande (máximo 5MB)']);
    exit;
}

// Crear directorio si no existe
$directorio = '../uploads/pasted_images/';
if (!is_dir($directorio)) {
    mkdir($directorio, 0777, true);
}

// Generar nombre único para el archivo
$nombreArchivo = uniqid() . '_' . time() . '.' . $tipoImagen;
$rutaCompleta = $directorio . $nombreArchivo;

// Guardar la imagen
if (file_put_contents($rutaCompleta, $datosImagen)) {
    // Retornar la ruta relativa para usar en la base de datos
    $rutaRelativa = 'uploads/pasted_images/' . $nombreArchivo;
    
    echo json_encode([
        'success' => true,
        'ruta' => $rutaRelativa,
        'nombre' => $nombreArchivo,
        'tamaño' => strlen($datosImagen)
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar la imagen']);
}
?>