<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/mailer.php';
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

    // Sincronizar con tablas del panel admin
    $payerName  = trim(($capture['payer']['name']['given_name'] ?? '') . ' ' . ($capture['payer']['name']['surname'] ?? ''));
    $payerEmail = $capture['payer']['email_address'] ?? '';
    $numeroPedido = 'PP-' . $pedidoId . '-' . date('Ymd');

    // Buscar o crear admin_cliente por email
    $adminClienteId = null;
    if ($payerEmail) {
        $sac = $conn->prepare("SELECT id FROM admin_clientes WHERE email = ? LIMIT 1");
        $sac->bind_param('s', $payerEmail); $sac->execute();
        $acRow = $sac->get_result()->fetch_assoc(); $sac->close();
        if ($acRow) {
            $adminClienteId = (int)$acRow['id'];
        } else {
            $nameParts = explode(' ', $payerName, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName  = $nameParts[1] ?? '';
            $iac = $conn->prepare("INSERT INTO admin_clientes (nombre, apellido, email) VALUES (?, ?, ?)");
            $iac->bind_param('sss', $firstName, $lastName, $payerEmail); $iac->execute();
            $adminClienteId = (int)$conn->insert_id; $iac->close();
        }
    }

    // Insertar en admin_pedidos
    $iap = $conn->prepare(
        "INSERT INTO admin_pedidos (numero_pedido, cliente_id, nombre_contacto, email_contacto, estado, subtotal, total, metodo_pago)
         VALUES (?, ?, ?, ?, 'pendiente', ?, ?, 'paypal')"
    );
    $iap->bind_param('sissdd', $numeroPedido, $adminClienteId, $payerName, $payerEmail, $monto, $monto);
    $iap->execute();
    $adminPedidoId = (int)$conn->insert_id; $iap->close();

    // Crear registro de envío pendiente
    $iae = $conn->prepare("INSERT INTO admin_envios (pedido_id, estado) VALUES (?, 'pendiente')");
    $iae->bind_param('i', $adminPedidoId); $iae->execute(); $iae->close();

    // Insertar detalle en admin_detalle_pedido
    foreach ($cart as $item) {
        $stmtNom = $conn->prepare("SELECT nombre, precio FROM producto WHERE id = ?");
        $stmtNom->bind_param('i', $item['id']); $stmtNom->execute();
        $prodRow = $stmtNom->get_result()->fetch_assoc(); $stmtNom->close();
        $nombreProd = $prodRow['nombre'] ?? ('Producto #' . $item['id']);
        $precioProd = (float)($prodRow['precio'] ?? 0);
        $cantProd   = (int)$item['cantidad'];
        $subtotalLinea = $precioProd * $cantProd;
        $iad = $conn->prepare(
            "INSERT INTO admin_detalle_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal_linea)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $iad->bind_param('iisidd', $adminPedidoId, $item['id'], $nombreProd, $cantProd, $precioProd, $subtotalLinea);
        $iad->execute(); $iad->close();
    }

    // Enviar email de confirmación al usuario registrado (no al email de PayPal sandbox)
    $userEmail  = $_SESSION['user_email'] ?? $payerEmail;
    $userNombre = $_SESSION['user_nombre'] ?? $payerName;
    $itemsEmail = [];
    foreach ($cart as $item) {
        $itemsEmail[] = [
            'nombre'   => $item['nombre'] ?? ('Producto #' . $item['id']),
            'cantidad' => $item['cantidad'] ?? 1,
            'precio'   => $item['precio'] ?? 0,
        ];
    }
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
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

    // Enviar correo después de responder al navegador
    $mailOk = sendConfirmacionPedido($userEmail, $userNombre, [
        'pedido_id' => $pedidoId,
        'monto'     => number_format($monto, 2),
        'moneda'    => $pmt['amount']['currency_code'] ?? 'MXN',
        'metodo'    => 'PayPal',
        'items'     => $itemsEmail,
    ]);
    file_put_contents(__DIR__ . '/../mail_debug.log',
        date('Y-m-d H:i:s') . ' [pedido #' . $pedidoId . '] to=' . $userEmail . ' result=' . ($mailOk ? 'OK' : 'FALLO') . "\n",
        FILE_APPEND);
} else {
    echo json_encode(['error' => 'El pago no fue completado.', 'status' => $capture['status'] ?? 'UNKNOWN']);
}
