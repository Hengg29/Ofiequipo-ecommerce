<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/cotizacion_pago.php';
ob_clean(); // carga .env y $conn

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado.']);
    exit;
}

// Solo se paga una cotización con total fijado por el admin (la carga pago.php)
$cot = cot_pagable($conn, (int)($_SESSION['pago_cot_id'] ?? 0), (int)$_SESSION['user_id']);
if (!$cot) {
    echo json_encode(['error' => 'Esta cotización no está disponible para pago.']);
    exit;
}

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

// 2. Total fijado por el admin
$totalStr = number_format((float)$cot['total'], 2, '.', '');

// 3. Crear orden en PayPal
$order = ppReq(
    "$base/v2/checkout/orders", 'POST',
    json_encode([
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'description' => 'Cotización ' . $cot['folio'] . ' — Ofiequipo de Tampico',
            'custom_id'   => (string)$cot['id'],
            'amount'      => [
                'currency_code' => 'MXN',
                'value'         => $totalStr,
            ],
        ]],
    ]),
    ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json']
);

if (!empty($order['id'])) {
    echo json_encode(['id' => $order['id']]);
} else {
    error_log('[PayPal Create] ' . json_encode($order));
    echo json_encode(['error' => 'No se pudo crear la orden en PayPal.']);
}
