<?php
/**
 * Script para servir imágenes con headers correctos
 * Uso: image.php?path=Uploads/Slider/image.png
 * También soporta: image.php?u=https://example.com/image.jpg (para compatibilidad)
 */

// Desactivar cualquier output buffering existente
while (ob_get_level()) {
    ob_end_clean();
}

// Iniciar nuevo output buffer para capturar cualquier salida accidental
ob_start();

// Desactivar errores que puedan generar HTML
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// Soporte para parámetro 'u' (URL externa) - compatibilidad con código antiguo
if (isset($_GET['u']) && !empty($_GET['u'])) {
    // PHP decodifica automáticamente los parámetros GET, así que $_GET['u'] ya está decodificado
    // Si la URL fue codificada con rawurlencode(), PHP la decodifica automáticamente
    $externalUrl = $_GET['u'];
    
    // Verificar si la URL es válida
    // Si no es válida, puede ser porque PHP la decodificó incorrectamente o hay caracteres especiales
    if (!filter_var($externalUrl, FILTER_VALIDATE_URL)) {
        // Intentar recodificar espacios y caracteres problemáticos
        // Solo codificar espacios literales, no tocar lo que ya está codificado
        if (strpos($externalUrl, ' ') !== false) {
            $externalUrl = str_replace(' ', '%20', $externalUrl);
        }
        
        // Si aún no es válida después de codificar espacios, intentar una vez más
        if (!filter_var($externalUrl, FILTER_VALIDATE_URL)) {
            // La URL puede tener caracteres especiales que necesitan ser preservados
            // Intentar usar la URL tal cual, puede que el servidor externo la acepte
        }
    }
    
    // Normalizar espacios: codificar espacios reales como %20 (pero no tocar URLs ya codificadas)
    // Solo si hay espacios literales en la URL (no codificados)
    if (strpos($externalUrl, ' ') !== false) {
        $externalUrl = str_replace(' ', '%20', $externalUrl);
    }
    
    // Intentar usar la URL incluso si filter_var falla, puede tener caracteres UTF-8 válidos
    // Verificar que al menos tenga el formato básico de URL
    if (preg_match('/^https?:\/\//i', $externalUrl)) {
        // Crear contexto para file_get_contents con User-Agent para evitar bloqueos
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
                    'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
                    'Referer: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
                ],
                'timeout' => 10,
                'follow_location' => true,
                'max_redirects' => 3
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        // Obtener la imagen desde la URL externa
        $imageData = @file_get_contents($externalUrl, false, $context);
        
        if ($imageData !== false && strlen($imageData) > 0) {
            // Detectar el tipo MIME basándose en la extensión de la URL
            $extension = strtolower(pathinfo(parse_url($externalUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'bmp' => 'image/bmp',
                'ico' => 'image/x-icon'
            ];
            $mimeType = $mimeTypes[$extension] ?? 'image/jpeg';
            
            // Limpiar completamente cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Establecer headers ANTES de enviar cualquier dato
            header('Content-Type: ' . $mimeType, true);
            header('Content-Length: ' . strlen($imageData), true);
            header('Cache-Control: public, max-age=86400', true); // Cache por 1 día
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT', true);
            header('Access-Control-Allow-Origin: *', true); // Permitir acceso desde cualquier origen
            
            // Enviar la imagen directamente
            echo $imageData;
            exit;
        } else {
            // Si no se pudo obtener, puede ser un error de acceso o URL inválida
            // Intentar obtener información del error para debugging
            $error = error_get_last();
        }
    }
    
    // Si no se pudo obtener la imagen externa, devolver placeholder SVG
    while (ob_get_level()) {
        ob_end_clean();
    }
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300"><rect width="300" height="300" fill="#f1f5f9"/><rect x="90" y="80" width="120" height="90" rx="8" fill="#cbd5e1"/><circle cx="115" cy="105" r="12" fill="#94a3b8"/><polyline points="90,170 130,130 160,155 190,120 210,170" fill="none" stroke="#94a3b8" stroke-width="6" stroke-linejoin="round"/><text x="150" y="210" font-family="sans-serif" font-size="13" fill="#94a3b8" text-anchor="middle">Sin imagen</text></svg>';
    header('Content-Type: image/svg+xml', true);
    header('Cache-Control: no-cache', true);
    echo $svg;
    exit;
}

// Obtener la ruta de la imagen desde el parámetro 'path'
$imagePath = isset($_GET['path']) ? $_GET['path'] : '';

if (empty($imagePath)) {
    http_response_code(400);
    die('Ruta de imagen no especificada');
}

// Decodificar la URL usando rawurldecode para ser consistente con rawurlencode usado en getImageUrl()
// rawurldecode preserva el "+" como carácter literal, no lo convierte en espacio
$imagePath = rawurldecode($imagePath);
// Decodificar múltiples veces en caso de doble codificación
while ($imagePath !== rawurldecode($imagePath)) {
    $imagePath = rawurldecode($imagePath);
}

// Nota: rawurldecode() ya maneja correctamente:
// - %20 → espacio (correcto)
// - %2B → + (correcto, preserva el + en nombres de archivo)
// - No convierte + en espacio (a diferencia de urldecode)

// Normalizar barras (Windows usa \ pero necesitamos /)
$imagePath = str_replace('\\', '/', $imagePath);

// Limpiar la ruta para prevenir directory traversal
$imagePath = str_replace(['../', '..\\'], '', $imagePath);
// Solo eliminar espacios y barras al inicio, preservar espacios en nombres de archivos
$imagePath = ltrim($imagePath, '/ ');

// Normalizar la ruta - intentar con diferentes variaciones de mayúsculas/minúsculas
$baseDir = __DIR__;
$possiblePaths = [];

// Preservar espacios en nombres de archivos y carpetas, solo trim al inicio/final de la ruta completa
$imagePath = trim($imagePath);

// Intentar primero la ruta tal cual desde el webroot, luego con prefijo Uploads/
// Esto cubre rutas como src/img/..., Uploads/..., uploads/..., etc.
if (stripos($imagePath, 'uploads/') === 0) {
    $possiblePaths[] = $baseDir . '/' . $imagePath;
    $parts = explode('/', $imagePath, 2);
    if (isset($parts[1])) {
        $possiblePaths[] = $baseDir . '/Uploads/' . $parts[1];
        $possiblePaths[] = $baseDir . '/uploads/' . $parts[1];
    }
} else {
    // Ruta webroot-relativa (ej: src/img/...) — probar directa primero
    $possiblePaths[] = $baseDir . '/' . $imagePath;
    // Luego con prefijo Uploads/ por compatibilidad con rutas sin directorio
    $possiblePaths[] = $baseDir . '/Uploads/' . $imagePath;
    $possiblePaths[] = $baseDir . '/uploads/' . $imagePath;
}

// Buscar el archivo en las rutas posibles
$fullPath = null;
foreach ($possiblePaths as $path) {
    // Normalizar la ruta del sistema operativo
    $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    // Asegurar que los espacios se manejen correctamente
    $normalizedPath = trim($normalizedPath);
    if (file_exists($normalizedPath) && is_file($normalizedPath)) {
        $fullPath = $normalizedPath;
        break;
    }
}

// Función helper para búsqueda case-insensitive con manejo de espacios
function findFileCaseInsensitive($dir, $filename) {
    if (!is_dir($dir)) {
        return false;
    }
    
    // Normalizar el nombre del archivo buscado (manejar espacios)
    $searchFilename = trim($filename);
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
        
        // Comparar nombres normalizando espacios (case-insensitive)
        $normalizedFile = trim($file);
        if (strcasecmp($normalizedFile, $searchFilename) === 0 && is_file($fullPath)) {
            return $fullPath;
        }
        
        // Buscar recursivamente en subdirectorios
        if (is_dir($fullPath)) {
            $result = findFileCaseInsensitive($fullPath, $filename);
            if ($result) {
                return $result;
            }
        }
    }
    
    return false;
}

// Si no se encontró, intentar búsqueda case-insensitive (útil para Windows/Linux)
if (!$fullPath) {
    // Buscar recursivamente en el directorio Uploads
    $searchDir = $baseDir . DIRECTORY_SEPARATOR . 'Uploads';
    if (is_dir($searchDir)) {
        // Normalizar el nombre del archivo (manejar espacios)
        $filename = basename($imagePath);
        $filename = trim($filename);
        $searchPath = findFileCaseInsensitive($searchDir, $filename);
        if ($searchPath) {
            $fullPath = $searchPath;
        }
    }
}

// Si no se encontró, verificar si es un archivo en la raíz
if (!$fullPath) {
    $rootPath = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath);
    if (file_exists($rootPath) && is_file($rootPath)) {
        $fullPath = $rootPath;
    }
}

// Verificar que el archivo existe
if (!$fullPath) {
    // Intentar obtener la imagen desde el servidor de producción
    $prodUrl = 'https://ofiequipo.com.mx/oe/' . $imagePath;
    $context = stream_context_create([
        'http' => [
            'method'          => 'GET',
            'header'          => ['User-Agent: Mozilla/5.0'],
            'timeout'         => 8,
            'follow_location' => true,
            'max_redirects'   => 3,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $imageData = @file_get_contents($prodUrl, false, $context);
    if ($imageData !== false && strlen($imageData) > 0) {
        $ext      = strtolower(pathinfo(parse_url($prodUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        $mimeMap  = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp','svg'=>'image/svg+xml'];
        $mime     = $mimeMap[$ext] ?? 'image/jpeg';
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: ' . $mime, true);
        header('Content-Length: ' . strlen($imageData), true);
        header('Cache-Control: public, max-age=86400', true);
        echo $imageData;
        exit;
    }

    while (ob_get_level()) {
        ob_end_clean();
    }
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300"><rect width="300" height="300" fill="#f1f5f9"/><rect x="90" y="80" width="120" height="90" rx="8" fill="#cbd5e1"/><circle cx="115" cy="105" r="12" fill="#94a3b8"/><polyline points="90,170 130,130 160,155 190,120 210,170" fill="none" stroke="#94a3b8" stroke-width="6" stroke-linejoin="round"/><text x="150" y="210" font-family="sans-serif" font-size="13" fill="#94a3b8" text-anchor="middle">Sin imagen</text></svg>';
    header('Content-Type: image/svg+xml', true);
    header('Cache-Control: no-cache', true);
    echo $svg;
    exit;
}

// Obtener información del archivo
$fileInfo = pathinfo($fullPath);
$extension = strtolower($fileInfo['extension'] ?? '');

// Mapear extensiones a tipos MIME
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'bmp' => 'image/bmp',
    'ico' => 'image/x-icon'
];

$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

// Obtener tamaño del archivo antes de limpiar buffer
$fileSize = filesize($fullPath);
$lastModified = filemtime($fullPath);

// Limpiar completamente cualquier output buffer antes de enviar headers
while (ob_get_level()) {
    ob_end_clean();
}

// Establecer headers ANTES de leer el archivo
header('Content-Type: ' . $mimeType, true);
header('Content-Length: ' . $fileSize, true);
header('Cache-Control: public, max-age=31536000', true); // Cache por 1 año
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT', true);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT', true);
header('Access-Control-Allow-Origin: *', true); // Permitir acceso desde cualquier origen

// Leer y enviar el archivo directamente
readfile($fullPath);
exit;


