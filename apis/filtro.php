<?php
header('Content-Type: application/json; charset=utf-8');
require_once "db.php";

$result = $conn->query("SELECT id, nombre FROM categoria WHERE parent_id IS NULL ORDER BY nombre ASC");

if ($result === false) {
    error_log('[filtro] Query falló: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron cargar las categorías.']);
    exit;
}

$categorias = [];
while ($row = $result->fetch_assoc()) {
    $categorias[] = [
        'id'     => (int)$row['id'],
        'nombre' => $row['nombre'],
    ];
}

echo json_encode($categorias);
