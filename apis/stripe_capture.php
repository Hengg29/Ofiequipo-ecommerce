<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/mailer.php';
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

try {
    $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY'] ?? '');
    $intent = $stripe->paymentIntents->retrieve($paymentIntentId);

    if ($intent->status !== 'succeeded') {
        echo json_encode(['error' => 'El pago no fue completado. Estado: ' . $intent->status]);
        exit;
    }

    $monto  = $intent->amount / 100;
    $cart   = $_SESSION['cart'] ?? [];
    $userId = (int)$_SESSION['user_id'];
    $cd     = $_SESSION['checkout_datos'] ?? [];

    $contactNombre = trim(($cd['nombre'] ?? '') . ' ' . ($cd['apellido'] ?? ''));
    $contactEmail  = $cd['email'] ?? ($_SESSION['user_email'] ?? '');
    $contactTel    = $cd['telefono'] ?? '';

    $conn->begin_transaction();
    try {
        // Cliente
        $sc = $conn->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
        $sc->bind_param('i', $userId); $sc->execute();
        $row = $sc->get_result()->fetch_assoc(); $sc->close();
        if ($row) {
            $clienteId = (int)$row['id'];
        } else {
            $ic = $conn->prepare("INSERT INTO clientes (usuario_id, metodo_pago) VALUES (?, 'stripe')");
            $ic->bind_param('i', $userId); $ic->execute();
            $clienteId = (int)$conn->insert_id; $ic->close();
        }

        // Pedido
        $factura = !empty($cd['factura']) ? 1 : 0;
        $ip = $conn->prepare("INSERT INTO pedidos (cliente_id, monto_total, requiere_factura, estado) VALUES (?, ?, ?, 'pendiente')");
        $ip->bind_param('idi', $clienteId, $monto, $factura); $ip->execute();
        $pedidoId = (int)$conn->insert_id; $ip->close();

        // Detalle
        foreach ($cart as $item) {
            $stmtPr = $conn->prepare("SELECT precio FROM producto WHERE id = ?");
            $stmtPr->bind_param('i', $item['id']); $stmtPr->execute();
            $pr = $stmtPr->get_result()->fetch_assoc(); $stmtPr->close();
            $precio = (float)($pr['precio'] ?? 0);
            $qty    = (int)$item['cantidad'];
            $stmtVar = $conn->prepare("SELECT id FROM productos WHERE producto_base_id = ? LIMIT 1");
            $stmtVar->bind_param('i', $item['id']); $stmtVar->execute();
            $var = $stmtVar->get_result()->fetch_assoc(); $stmtVar->close();
            if ($var['id'] ?? null) {
                $id2 = $conn->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)");
                $id2->bind_param('iiid', $pedidoId, $var['id'], $qty, $precio); $id2->execute(); $id2->close();
            }
        }

        // Pago
        $ipg = $conn->prepare("INSERT INTO pagos (pedido_id, monto, metodo_pago) VALUES (?, ?, 'stripe')");
        $ipg->bind_param('id', $pedidoId, $monto); $ipg->execute(); $ipg->close();

        // Admin cliente
        $adminClienteId = null;
        $lookupEmail = $contactEmail;
        if ($lookupEmail) {
            $sac = $conn->prepare("SELECT id FROM admin_clientes WHERE email = ? LIMIT 1");
            $sac->bind_param('s', $lookupEmail); $sac->execute();
            $acRow = $sac->get_result()->fetch_assoc(); $sac->close();
            if ($acRow) {
                $adminClienteId = (int)$acRow['id'];
            } else {
                $fn = $cd['nombre'] ?? ''; $ln = $cd['apellido'] ?? '';
                $iac = $conn->prepare("INSERT INTO admin_clientes (nombre, apellido, email, telefono) VALUES (?, ?, ?, ?)");
                $iac->bind_param('ssss', $fn, $ln, $lookupEmail, $contactTel); $iac->execute();
                $adminClienteId = (int)$conn->insert_id; $iac->close();
            }
        }

        // Admin pedido
        $numeroPedido = 'STR-' . $pedidoId . '-' . date('Ymd');
        $emailContacto = $contactEmail;
        $iap = $conn->prepare(
            "INSERT INTO admin_pedidos (numero_pedido, cliente_id, nombre_contacto, email_contacto, telefono_contacto, estado, subtotal, total, metodo_pago)
             VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?, 'stripe')"
        );
        $iap->bind_param('sisssdd', $numeroPedido, $adminClienteId, $contactNombre, $emailContacto, $contactTel, $monto, $monto);
        $iap->execute();
        $adminPedidoId = (int)$conn->insert_id; $iap->close();

        $conn->query("UPDATE pedidos SET admin_pedido_id = $adminPedidoId WHERE id = $pedidoId");

        $iae = $conn->prepare("INSERT INTO admin_envios (pedido_id, estado) VALUES (?, 'pendiente')");
        $iae->bind_param('i', $adminPedidoId); $iae->execute(); $iae->close();

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

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('[Stripe Capture] ' . $e->getMessage());
        echo json_encode(['error' => 'Error al registrar pedido. ID Stripe: ' . $paymentIntentId]);
        exit;
    }

    // Sesión para confirmación
    $_SESSION['paypal_confirmacion'] = [
        'pedido_id'   => $pedidoId,
        'order_id'    => $paymentIntentId,
        'payer_email' => $contactEmail,
        'payer_name'  => $contactNombre,
        'monto'       => number_format($monto, 2),
        'moneda'      => 'MXN',
        'metodo'      => 'stripe',
        'status'      => 'COMPLETED',
    ];
    $_SESSION['cart'] = [];
    echo json_encode(['success' => true, 'redirect' => 'confirmacion.php']);
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

    // Correo de confirmación
    $userEmail  = $contactEmail ?: ($_SESSION['user_email'] ?? '');
    $userNombre = $contactNombre ?: ($_SESSION['user_nombre'] ?? '');
    $itemsEmail = [];
    foreach ($cart as $item) {
        $itemsEmail[] = [
            'nombre'   => $item['nombre'] ?? ('Producto #' . $item['id']),
            'cantidad' => $item['cantidad'] ?? 1,
            'precio'   => $item['precio'] ?? 0,
        ];
    }
    sendConfirmacionPedido($userEmail, $userNombre, [
        'pedido_id' => $pedidoId,
        'monto'     => number_format($monto, 2),
        'moneda'    => 'MXN',
        'metodo'    => 'Stripe',
        'items'     => $itemsEmail,
    ]);

} catch (Throwable $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
