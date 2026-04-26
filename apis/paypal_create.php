<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
ob_clean(); // carga .env y $conn

$clientId = $_ENV['PAYPAL_CLIENT_ID'] ?? '';
$secret   = $_ENV['PAYPAL_SECRET']    ?? '';
$mode     = $_ENV['PAYPAL_MODE']      ?? 'sandbox';
$base     = $mode === 'live'
    ? 'https://api-m.paypal.com'
    : 'https://api-m.sandbox.paypal.com';

function ppReq(string $url, string $method, $body, array $headers): array {
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
        error_log('[PayPal] cURL error ' . $errno . ' en ' . $url);
        return [];
    }
    return json_decode($resp, true) ?? [];
}

// 1. Obtener access token
$token = ppReq(
    "$base/v1/oauth2/token", 'POST',
    'grant_type=client_credentials',
    ['Authorization: Basic ' . base64_encode("$clientId:$secret"),
     'Content-Type: application/x-www-form-urlencoded']
);
$accessToken = $token['access_token'] ?? null;
if (!$accessToken) {
    echo json_encode(['error' => 'No se pudo autenticar con PayPal. Revisa las credenciales.']);
    exit;
}

// 2. Calcular total desde BD (nunca confiar en el cliente)
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    echo json_encode(['error' => 'El carrito está vacío.']);
    exit;
}

$total = 0.0;
$items = [];
foreach ($cart as $item) {
    $stmt = $conn->prepare("SELECT nombre, precio FROM producto WHERE id = ? AND activo = 1");
    if (!$stmt) {
        error_log('[PayPal] prepare falló: ' . $conn->error);
        echo json_encode(['error' => 'Error interno al procesar el carrito.']);
        exit;
    }
    $stmt->bind_param('i', $item['id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || (float)$row['precio'] <= 0) continue;
    $unit = round((float)$row['precio'], 2);
    $qty  = max(1, (int)$item['cantidad']);
    $total += $unit * $qty;
    $items[] = [
        'name'        => mb_substr($row['nombre'], 0, 127),
        'quantity'    => (string)$qty,
        'unit_amount' => ['currency_code' => 'MXN', 'value' => number_format($unit, 2, '.', '')],
    ];
}

if ($total <= 0) {
    echo json_encode(['error' => 'Los productos aún no tienen precio registrado. Contacta a Ofiequipo para cotización.']);
    exit;
}

$totalStr = number_format($total, 2, '.', '');

// 3. Crear orden en PayPal
$order = ppReq(
    "$base/v2/checkout/orders", 'POST',
    json_encode([
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'description' => 'Pedido Ofiequipo de Tampico',
            'amount'      => [
                'currency_code' => 'MXN',
                'value'         => $totalStr,
                'breakdown'     => [
                    'item_total' => ['currency_code' => 'MXN', 'value' => $totalStr]
                ],
            ],
            'items' => $items,
        ]],
    ]),
    ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json']
);

if (!empty($order['id'])) {
    echo json_encode(['id' => $order['id']]);
} else {
    echo json_encode(['error' => 'No se pudo crear la orden en PayPal.', 'details' => $order]);
}
