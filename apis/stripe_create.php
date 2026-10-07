<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/cotizacion_pago.php';
ob_clean();

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
$total = (float)$cot['total'];

try {
    $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY'] ?? '');
    $intent = $stripe->paymentIntents->create([
        'amount'      => (int) round($total * 100), // centavos
        'currency'    => 'mxn',
        'payment_method_types' => ['card'],
        'description' => 'Cotización ' . $cot['folio'],
        'metadata'    => [
            'user_id'       => $_SESSION['user_id'],
            'cotizacion_id' => $cot['id'],
            'folio'         => $cot['folio'],
        ],
    ]);
    echo json_encode(['client_secret' => $intent->client_secret]);
} catch (Throwable $e) {
    error_log('[Stripe Create] ' . $e->getMessage());
    echo json_encode(['error' => 'No se pudo iniciar el pago con tarjeta. Intenta de nuevo.']);
}
