<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once dirname(__DIR__) . '/includes/init.php';
admin_require_login();
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido.']); exit;
}

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['error' => 'No se recibió ningún archivo.']); exit;
}
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Error al subir: código ' . $file['error']]); exit;
}

$info = @getimagesize($file['tmp_name']);
if ($info === false) {
    echo json_encode(['error' => 'El archivo no es una imagen válida.']); exit;
}

$allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
if (!in_array($info[2], $allowed, true)) {
    echo json_encode(['error' => 'Solo JPG, PNG, GIF o WEBP.']); exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['error' => 'La imagen no puede superar 5 MB.']); exit;
}

$exts  = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
$ext   = $exts[$info[2]];

// Usar nombre original limpio si es posible, sino generar uno
$original = pathinfo($file['name'], PATHINFO_FILENAME);
$original = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $original);
$original = trim($original, '_') ?: 'img';
$fname    = $original . '_' . bin2hex(random_bytes(3)) . '.' . $ext;

$destDir = dirname(__DIR__, 2) . '/src/img/';
if (!is_dir($destDir)) mkdir($destDir, 0755, true);

if (!move_uploaded_file($file['tmp_name'], $destDir . $fname)) {
    echo json_encode(['error' => 'No se pudo guardar. Verifica permisos de src/img/.']); exit;
}

echo json_encode(['ok' => true, 'filename' => $fname]);
