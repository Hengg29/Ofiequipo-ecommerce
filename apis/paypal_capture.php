<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/cotizacion_pago.php';
ob_clean();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado.']);
    exit;
}
$userId = (int)$_SESSION['user_id'];
$cotId  = (int)($_SESSION['pago_cot_id'] ?? 0);

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

if (($capture['status'] ?? '') !== 'COMPLETED') {
    echo json_encode(['error' => 'El pago no fue completado.', 'status' => $capture['status'] ?? 'UNKNOWN']);
    exit;
}

$pmt   = $capture['purchase_units'][0]['payments']['captures'][0] ?? [];
$monto = (float)($pmt['amount']['value'] ?? 0);
$ref   = (string)($capture['id'] ?? $orderID);

// El cobro debe pertenecer a la cotización que se está pagando
$customId = $pmt['custom_id'] ?? ($capture['purchase_units'][0]['custom_id'] ?? 0);
if ((int)$customId !== $cotId || ($pmt['amount']['currency_code'] ?? '') !== 'MXN') {
    error_log("[PayPal Capture] Orden $ref no coincide con cotización $cotId");
    echo json_encode(['error' => 'El pago no corresponde a esta cotización. Referencia: ' . $ref]);
    exit;
}

try {
    $res = cot_registrar_pago($conn, $cotId, $userId, $monto, 'paypal', $ref);
} catch (Throwable $e) {
    error_log('[PayPal Capture] ' . $e->getMessage());
    echo json_encode(['error' => 'El pago se recibió pero no se pudo registrar el pedido. Contáctanos con la referencia: ' . $ref]);
    exit;
}

cot_post_pago($res, $monto, 'PayPal', $ref);
echo json_encode(['success' => true, 'redirect' => 'confirmacion.php']);
session_write_close();
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

cot_enviar_confirmacion($res, $monto, 'PayPal');
