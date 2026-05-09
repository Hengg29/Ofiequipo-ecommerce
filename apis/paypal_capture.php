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
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp  = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($errno || $resp === false) {
        error_log('[PayPal Capture] cURL error ' . $errno . ' en ' . $url);
        return [];
    }
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
    $pu    = $capture['purchase_units'][0] ?? [];
    $pmt   = $pu['payments']['captures'][0] ?? [];
    $monto = (float)($pmt['amount']['value'] ?? 0);
    $cart  = $_SESSION['cart'] ?? [];

    $payerName  = trim(($capture['payer']['name']['given_name'] ?? '') . ' ' . ($capture['payer']['name']['surname'] ?? ''));
    $payerEmail = $capture['payer']['email_address'] ?? '';

    $pedidoId      = 0;
    $adminPedidoId = 0;

    try {
        $conn->begin_transaction();

        // Obtener o crear registro en clientes vinculado al usuario
        $userId    = (int)($_SESSION['user_id'] ?? 0);
        $clienteId = null;
        if ($userId > 0) {
            $sc = $conn->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
            if (!$sc) throw new RuntimeException('prepare clientes: ' . $conn->error);
            $sc->bind_param('i', $userId); $sc->execute();
            $row = $sc->get_result()->fetch_assoc(); $sc->close();
            if ($row) {
                $clienteId = (int)$row['id'];
            } else {
                $ic = $conn->prepare("INSERT INTO clientes (usuario_id, metodo_pago) VALUES (?, 'paypal')");
                if (!$ic) throw new RuntimeException('prepare insert clientes: ' . $conn->error);
                $ic->bind_param('i', $userId); $ic->execute();
                $clienteId = (int)$conn->insert_id; $ic->close();
            }
        }

        // Crear pedido
        $factura = !empty($_SESSION['checkout_datos']['factura']) ? 1 : 0;
        $ip = $conn->prepare("INSERT INTO pedidos (cliente_id, monto_total, requiere_factura, estado) VALUES (?, ?, ?, 'pendiente')");
        if (!$ip) throw new RuntimeException('prepare pedidos: ' . $conn->error);
        $ip->bind_param('idi', $clienteId, $monto, $factura); $ip->execute();
        $pedidoId = (int)$conn->insert_id; $ip->close();

        // Guardar detalle de productos
        foreach ($cart as $item) {
            $stmtPr = $conn->prepare("SELECT precio FROM producto WHERE id = ?");
            if (!$stmtPr) throw new RuntimeException('prepare precio: ' . $conn->error);
            $stmtPr->bind_param('i', $item['id']); $stmtPr->execute();
            $pr     = $stmtPr->get_result()->fetch_assoc(); $stmtPr->close();
            $precio = (float)($pr['precio'] ?? 0);
            $qty    = (int)$item['cantidad'];
            $stmtVar = $conn->prepare("SELECT id FROM productos WHERE producto_base_id = ? LIMIT 1");
            if (!$stmtVar) throw new RuntimeException('prepare variante: ' . $conn->error);
            $stmtVar->bind_param('i', $item['id']); $stmtVar->execute();
            $var        = $stmtVar->get_result()->fetch_assoc(); $stmtVar->close();
            $productoId = $var['id'] ?? null;
            if ($productoId) {
                $id2 = $conn->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)");
                if (!$id2) throw new RuntimeException('prepare detalle_pedidos: ' . $conn->error);
                $id2->bind_param('iiid', $pedidoId, $productoId, $qty, $precio); $id2->execute(); $id2->close();
            }
        }

        // Registrar pago
        $ipg = $conn->prepare("INSERT INTO pagos (pedido_id, monto, metodo_pago) VALUES (?, ?, 'paypal')");
        if (!$ipg) throw new RuntimeException('prepare pagos: ' . $conn->error);
        $ipg->bind_param('id', $pedidoId, $monto); $ipg->execute(); $ipg->close();

        // Buscar o crear admin_cliente por email
        $adminClienteId = null;
        if ($payerEmail) {
            $sac = $conn->prepare("SELECT id FROM admin_clientes WHERE email = ? LIMIT 1");
            if (!$sac) throw new RuntimeException('prepare admin_clientes select: ' . $conn->error);
            $sac->bind_param('s', $payerEmail); $sac->execute();
            $acRow = $sac->get_result()->fetch_assoc(); $sac->close();
            if ($acRow) {
                $adminClienteId = (int)$acRow['id'];
            } else {
                $nameParts = explode(' ', $payerName, 2);
                $firstName = $nameParts[0] ?? '';
                $lastName  = $nameParts[1] ?? '';
                $iac = $conn->prepare("INSERT INTO admin_clientes (nombre, apellido, email) VALUES (?, ?, ?)");
                if (!$iac) throw new RuntimeException('prepare admin_clientes insert: ' . $conn->error);
                $iac->bind_param('sss', $firstName, $lastName, $payerEmail); $iac->execute();
                $adminClienteId = (int)$conn->insert_id; $iac->close();
            }
        }

        // Insertar en admin_pedidos
        $numeroPedido = 'PP-' . $pedidoId . '-' . date('Ymd');
        $iap = $conn->prepare(
            "INSERT INTO admin_pedidos (numero_pedido, cliente_id, nombre_contacto, email_contacto, estado, subtotal, total, metodo_pago)
             VALUES (?, ?, ?, ?, 'pendiente', ?, ?, 'paypal')"
        );
        if (!$iap) throw new RuntimeException('prepare admin_pedidos: ' . $conn->error);
        $iap->bind_param('sissdd', $numeroPedido, $adminClienteId, $payerName, $payerEmail, $monto, $monto);
        $iap->execute();
        $adminPedidoId = (int)$conn->insert_id; $iap->close();

        // Vincular pedido con admin_pedido para sincronizar estado
        $conn->query("UPDATE pedidos SET admin_pedido_id = $adminPedidoId WHERE id = $pedidoId");

        // Crear registro de envío pendiente
        $iae = $conn->prepare("INSERT INTO admin_envios (pedido_id, estado) VALUES (?, 'pendiente')");
        if (!$iae) throw new RuntimeException('prepare admin_envios: ' . $conn->error);
        $iae->bind_param('i', $adminPedidoId); $iae->execute(); $iae->close();

        // Insertar detalle en admin_detalle_pedido
        foreach ($cart as $item) {
            $stmtNom = $conn->prepare("SELECT nombre, precio FROM producto WHERE id = ?");
            if (!$stmtNom) throw new RuntimeException('prepare admin_detalle: ' . $conn->error);
            $stmtNom->bind_param('i', $item['id']); $stmtNom->execute();
            $prodRow       = $stmtNom->get_result()->fetch_assoc(); $stmtNom->close();
            $nombreProd    = $prodRow['nombre'] ?? ('Producto #' . $item['id']);
            $precioProd    = (float)($prodRow['precio'] ?? 0);
            $cantProd      = (int)$item['cantidad'];
            $subtotalLinea = $precioProd * $cantProd;
            $iad = $conn->prepare(
                "INSERT INTO admin_detalle_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal_linea)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            if (!$iad) throw new RuntimeException('prepare admin_detalle_pedido: ' . $conn->error);
            $iad->bind_param('iisidd', $adminPedidoId, $item['id'], $nombreProd, $cantProd, $precioProd, $subtotalLinea);
            $iad->execute(); $iad->close();
        }

        $conn->commit();

    } catch (RuntimeException $e) {
        $conn->rollback();
        error_log('[PayPal Capture] Rollback pedido #' . $pedidoId . ': ' . $e->getMessage());
        echo json_encode(['error' => 'Error al registrar el pedido. Contacta a soporte con tu ID de PayPal: ' . ($capture['id'] ?? '')]);
        exit;
    }

    // Enviar email de confirmación al usuario registrado (no al email de PayPal sandbox)
    $userEmail  = $_SESSION['user_email'] ?? $payerEmail;
    $userNombre = $_SESSION['user_nombre'] ?? $payerName;
    $appUrlBase = rtrim($_ENV['APP_URL'] ?? 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
    $itemsEmail = [];
    foreach ($cart as $item) {
        $stmtEm = $conn->prepare("SELECT nombre, precio, imagen FROM producto WHERE id = ? LIMIT 1");
        $stmtEm->bind_param('i', $item['id']); $stmtEm->execute();
        $prodEm = $stmtEm->get_result()->fetch_assoc(); $stmtEm->close();
        $imgRaw = $prodEm['imagen'] ?? '';
        if (!empty($imgRaw) && preg_match('/^https?:\/\//i', $imgRaw)) {
            $imgUrl = $imgRaw;
        } elseif (!empty($imgRaw)) {
            $imgUrl = $appUrlBase . '/' . ltrim(str_replace('\\', '/', $imgRaw), '/');
        } else {
            $imgUrl = '';
        }
        $itemsEmail[] = [
            'nombre'   => $prodEm['nombre'] ?? ($item['nombre'] ?? ('Producto #' . $item['id'])),
            'cantidad' => $item['cantidad'] ?? 1,
            'precio'   => (float)($prodEm['precio'] ?? 0),
            'imagen'   => $imgUrl,
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
