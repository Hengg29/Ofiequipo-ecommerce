<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
ob_clean();

$clientId = $_ENV['PAYPAL_CLIENT_ID'] ?? '';
$secret   = $_ENV['PAYPAL_SECRET']    ?? '';
$mode     = $_ENV['PAYPAL_MODE']      ?? 'sandbox';
$base     = $mode === 'live'
    ? 'https://api-m.paypal.com'
    : 'https://api-m.sandbox.paypal.com';

$orderID = trim($_POST['orderID'] ?? '');
if (!$orderID) {
    echo json_encode(['error' => 'orderID requerido.']);
    exit;
}

function ppReq2(string $url, string $method, $body, array $headers): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true) ?? [];
}

// 1. Access token
$token = ppReq2(
    "$base/v1/oauth2/token", 'POST',
    'grant_type=client_credentials',
    ['Authorization: Basic ' . base64_encode("$clientId:$secret"),
     'Content-Type: application/x-www-form-urlencoded']
);
$accessToken = $token['access_token'] ?? null;
if (!$accessToken) {
    echo json_encode(['error' => 'Error de autenticación PayPal.']);
    exit;
}

// 2. Capturar pago
$capture = ppReq2(
    "$base/v2/checkout/orders/$orderID/capture", 'POST',
    '{}',
    ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json']
);

if (($capture['status'] ?? '') === 'COMPLETED') {
    $pu  = $capture['purchase_units'][0] ?? [];
    $pmt = $pu['payments']['captures'][0] ?? [];
    $monto = (float)($pmt['amount']['value'] ?? 0);

    // Obtener o crear registro en clientes vinculado al usuario
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $clienteId = null;
    if ($userId > 0) {
        $sc = $conn->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
        $sc->bind_param('i', $userId); $sc->execute();
        $row = $sc->get_result()->fetch_assoc(); $sc->close();
        if ($row) {
            $clienteId = (int)$row['id'];
        } else {
            $ic = $conn->prepare("INSERT INTO clientes (usuario_id, metodo_pago) VALUES (?, 'paypal')");
            $ic->bind_param('i', $userId); $ic->execute();
            $clienteId = (int)$conn->insert_id; $ic->close();
        }
    }

    // Crear pedido
    $factura = !empty($_SESSION['checkout_datos']['factura']) ? 1 : 0;
    $ip = $conn->prepare("INSERT INTO pedidos (cliente_id, monto_total, requiere_factura, estado) VALUES (?, ?, ?, 'pendiente')");
    $ip->bind_param('idi', $clienteId, $monto, $factura); $ip->execute();
    $pedidoId = (int)$conn->insert_id; $ip->close();

    // Guardar detalle de productos
    $cart = $_SESSION['cart'] ?? [];
    foreach ($cart as $item) {
        $stmtPr = $conn->prepare("SELECT precio FROM producto WHERE id = ?");
        $stmtPr->bind_param('i', $item['id']); $stmtPr->execute();
        $pr = $stmtPr->get_result()->fetch_assoc(); $stmtPr->close();
        $precio = (float)($pr['precio'] ?? 0);
        $qty    = (int)$item['cantidad'];
        // detalle_pedidos referencia a productos (tabla variantes), buscar ahí
        $stmtVar = $conn->prepare("SELECT id FROM productos WHERE producto_base_id = ? LIMIT 1");
        $stmtVar->bind_param('i', $item['id']); $stmtVar->execute();
        $var = $stmtVar->get_result()->fetch_assoc(); $stmtVar->close();
        $productoId = $var['id'] ?? null;
        if ($productoId) {
            $id2 = $conn->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)");
            $id2->bind_param('iiid', $pedidoId, $productoId, $qty, $precio); $id2->execute(); $id2->close();
        }
    }

    // Registrar pago
    $ipg = $conn->prepare("INSERT INTO pagos (pedido_id, monto, metodo_pago) VALUES (?, ?, 'paypal')");
    $ipg->bind_param('id', $pedidoId, $monto); $ipg->execute(); $ipg->close();

    // Guardar en sesión para confirmación
    $_SESSION['paypal_confirmacion'] = [
        'pedido_id'   => $pedidoId,
        'order_id'    => $capture['id'],
        'payer_email' => $capture['payer']['email_address'] ?? '',
        'payer_name'  => ($capture['payer']['name']['given_name'] ?? '') . ' ' . ($capture['payer']['name']['surname'] ?? ''),
        'monto'       => number_format($monto, 2),
        'moneda'      => $pmt['amount']['currency_code'] ?? 'MXN',
        'status'      => 'COMPLETED',
    ];
    $_SESSION['cart'] = [];
    echo json_encode(['success' => true, 'redirect' => 'confirmacion.php']);
} else {
    echo json_encode(['error' => 'El pago no fue completado.', 'status' => $capture['status'] ?? 'UNKNOWN']);
}
