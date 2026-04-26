<?php
// Carga variables del .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}

$host   = $_ENV['DB_HOST'] ?? 'localhost';
$user   = $_ENV['DB_USER'] ?? 'root';
$pass   = $_ENV['DB_PASS'] ?? 'Csnu88334';
$dbname = $_ENV['DB_NAME'] ?? 'ofiequipo2';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    error_log('[DB] Fallo de conexión: ' . $conn->connect_error);
    http_response_code(503);
    $isJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
           || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'json');
    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Servicio temporalmente no disponible.']);
    } else {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body>'
           . '<p style="font-family:sans-serif;padding:40px">Servicio temporalmente no disponible. Intenta más tarde.</p>'
           . '</body></html>';
    }
    exit;
}

$conn->set_charset('utf8mb4');
?>
