<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
ob_clean();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado.']);
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    echo json_encode(['error' => 'Carrito vacío.']);
    exit;
}

// Calcular total desde la BD (nunca confiar en el cliente)
$total = 0;
foreach ($cart as $item) {
    $sp = $conn->prepare("SELECT precio FROM producto WHERE id = ? AND activo = 1");
    $sp->bind_param('i', $item['id']);
    $sp->execute();
    $pr = $sp->get_result()->fetch_assoc();
    $sp->close();
    $total += (float)($pr['precio'] ?? 0) * (int)$item['cantidad'];
}

if ($total <= 0) {
    echo json_encode(['error' => 'Total inválido. Verifica los precios del carrito.']);
    exit;
}

try {
    $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY'] ?? '');
    $intent = $stripe->paymentIntents->create([
        'amount'   => (int) round($total * 100), // centavos
        'currency' => 'mxn',
        'payment_method_types' => ['card'],
        'metadata' => ['user_id' => $_SESSION['user_id']],
    ]);
    echo json_encode(['client_secret' => $intent->client_secret, 'amount' => $total]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Error Stripe: ' . $e->getMessage()]);
}
