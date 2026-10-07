<?php
// Endpoint retirado: la cotización vive en localStorage y se envía a apis/procesar_cotizacion.php.
http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => false, 'error' => 'Este servicio ya no está disponible.']);
