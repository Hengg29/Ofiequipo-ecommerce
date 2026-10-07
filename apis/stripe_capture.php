<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/cotizacion_pago.php';
ob_clean();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado.']);
    exit;
}

$paymentIntentId = trim($_POST['payment_intent_id'] ?? '');
if (!$paymentIntentId) {
    echo json_encode(['error' => 'payment_intent_id requerido.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$cotId  = (int)($_SESSION['pago_cot_id'] ?? 0);

try {
    $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY'] ?? '');
    $intent = $stripe->paymentIntents->retrieve($paymentIntentId);
} catch (Throwable $e) {
    error_log('[Stripe Capture] ' . $e->getMessage());
    echo json_encode(['error' => 'No se pudo verificar el pago con Stripe.']);
    exit;
}

if ($intent->status !== 'succeeded') {
    echo json_encode(['error' => 'El pago no fue completado. Estado: ' . $intent->status]);
    exit;
}

// El cobro debe pertenecer a esta cotización y a este usuario
if ((int)($intent->metadata['cotizacion_id'] ?? 0) !== $cotId || (int)($intent->metadata['user_id'] ?? 0) !== $userId) {
    error_log("[Stripe Capture] Intent $paymentIntentId no coincide con cotización $cotId / usuario $userId");
    echo json_encode(['error' => 'El pago no corresponde a esta cotización. Referencia: ' . $paymentIntentId]);
    exit;
}

$monto = $intent->amount / 100;
try {
    $res = cot_registrar_pago($conn, $cotId, $userId, $monto, 'stripe', $paymentIntentId);
} catch (Throwable $e) {
    error_log('[Stripe Capture] ' . $e->getMessage());
    echo json_encode(['error' => 'El pago se recibió pero no se pudo registrar el pedido. Contáctanos con la referencia: ' . $paymentIntentId]);
    exit;
}

cot_post_pago($res, $monto, 'Stripe', $paymentIntentId);
echo json_encode(['success' => true, 'redirect' => 'confirmacion.php']);
session_write_close();
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

cot_enviar_confirmacion($res, $monto, 'Stripe');
