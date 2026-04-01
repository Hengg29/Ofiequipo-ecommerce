<?php
require_once "db.php";
header('Content-Type: application/json; charset=utf-8');

// Obtener los datos del formulario
$producto = trim($_POST['producto'] ?? '');
$nombre   = trim($_POST['nombre'] ?? '');
$empresa  = trim($_POST['empresa'] ?? '');
$email    = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$cantidad = (int)($_POST['cantidad'] ?? 1);
$mensaje  = trim($_POST['mensaje'] ?? '');

// Preparar la consulta para guardar en la base de datos
$sql  = "INSERT INTO cotizaciones (producto, nombre, empresa, email, telefono, cantidad, mensaje)
         VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

// Comprobar si la preparación de la consulta es exitosa
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "icon" => "error",
        "text" => "Error en la base de datos (prepare): " . $conn->error
    ]);
    exit;
}

// Vincular los parámetros y ejecutarlos
if (!$stmt->bind_param("sssssis", $producto, $nombre, $empresa, $email, $telefono, $cantidad, $mensaje)) {
    http_response_code(500);
    echo json_encode([
        "icon" => "error",
        "text" => "Error al bind_param: " . $stmt->error
    ]);
    exit;
}

// Ejecutar la consulta
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        "icon" => "error",
        "text" => "Error al ejecutar la consulta: " . $stmt->error
    ]);
    exit;
}

// Responder con éxito
echo json_encode([
    "icon" => "success",
    "text" => "Cotización guardada correctamente en la base de datos."
]);
?>
