<?php
// Pago de cotizaciones: el monto SIEMPRE sale del total que fijó el admin en la BD,
// nunca del navegador. Lo usan pago.php y los endpoints de Stripe/PayPal.

// Cotización lista para pagar por este usuario (o null si no aplica).
function cot_pagable(mysqli $conn, int $cotId, int $userId): ?array {
    $st = $conn->prepare(
        "SELECT * FROM cotizaciones
          WHERE id = ? AND usuario_id = ? AND status = 'cotizada'
            AND total > 0 AND pago_enviado_en IS NOT NULL AND pagada_en IS NULL
          LIMIT 1"
    );
    $st->bind_param('ii', $cotId, $userId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    return $row ?: null;
}

function cot_items(mysqli $conn, int $cotId): array {
    $st = $conn->prepare("SELECT * FROM cotizacion_items WHERE cotizacion_id = ? ORDER BY id");
    $st->bind_param('i', $cotId);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $rows;
}

function cot_columna_existe(mysqli $conn, string $tabla, string $columna): bool {
    $st = $conn->prepare(
        "SELECT 1 FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1"
    );
    $st->bind_param('ss', $tabla, $columna);
    $st->execute();
    $ok = (bool)$st->get_result()->fetch_row();
    $st->close();
    return $ok;
}

/**
 * Registra el pago de una cotización y crea el pedido (mismas tablas que el checkout anterior:
 * pedidos, detalle_pedidos, pagos, admin_pedidos, admin_detalle_pedido, admin_envios).
 * Es idempotente por $referencia: si la misma transacción llega dos veces, devuelve el mismo pedido.
 *
 * @return array{pedido_id:int, cot:array, items:array}
 */
function cot_registrar_pago(mysqli $conn, int $cotId, int $userId, float $monto, string $metodo, string $referencia): array {
    $conn->begin_transaction();
    try {
        // Bloquear la fila para evitar dos pagos simultáneos
        $st = $conn->prepare("SELECT * FROM cotizaciones WHERE id = ? AND usuario_id = ? FOR UPDATE");
        $st->bind_param('ii', $cotId, $userId);
        $st->execute();
        $cot = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$cot) throw new RuntimeException('Cotización no encontrada.');

        if ($cot['pagada_en'] !== null) {
            if ($cot['pago_referencia'] === $referencia) {
                $conn->commit();
                return ['pedido_id' => (int)$cot['pedido_id'], 'cot' => $cot, 'items' => cot_items($conn, $cotId)];
            }
            throw new RuntimeException('La cotización ya estaba pagada.');
        }
        if ($cot['status'] !== 'cotizada' || (float)$cot['total'] <= 0) {
            throw new RuntimeException('La cotización no está lista para pago.');
        }
        if (abs($monto - (float)$cot['total']) > 0.009) {
            throw new RuntimeException("Monto cobrado ($monto) distinto al total de la cotización ({$cot['total']}).");
        }

        $items = cot_items($conn, $cotId);

        // Cliente de la tienda
        $st = $conn->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
        $st->bind_param('i', $userId);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if ($row) {
            $clienteId = (int)$row['id'];
        } else {
            $st = $conn->prepare("INSERT INTO clientes (usuario_id, metodo_pago) VALUES (?, ?)");
            $st->bind_param('is', $userId, $metodo);
            $st->execute();
            $clienteId = (int)$conn->insert_id;
            $st->close();
        }

        // Pedido
        $st = $conn->prepare("INSERT INTO pedidos (cliente_id, monto_total, requiere_factura, estado) VALUES (?, ?, 0, 'pendiente')");
        $st->bind_param('id', $clienteId, $monto);
        $st->execute();
        $pedidoId = (int)$conn->insert_id;
        $st->close();

        // Detalle (detalle_pedidos apunta a la tabla de variantes `productos`)
        $stVar = $conn->prepare("SELECT id FROM productos WHERE producto_base_id = ? LIMIT 1");
        $stDet = $conn->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)");
        foreach ($items as $it) {
            $pid = (int)$it['producto_id'];
            $stVar->bind_param('i', $pid);
            $stVar->execute();
            $var = $stVar->get_result()->fetch_assoc();
            if (!$var) continue;
            $varId = (int)$var['id'];
            $qty   = (int)$it['cantidad'];
            $precio = (float)$it['precio'];
            $stDet->bind_param('iiid', $pedidoId, $varId, $qty, $precio);
            $stDet->execute();
        }
        $stVar->close();
        $stDet->close();

        // Pago
        $st = $conn->prepare("INSERT INTO pagos (pedido_id, monto, metodo_pago) VALUES (?, ?, ?)");
        $st->bind_param('ids', $pedidoId, $monto, $metodo);
        $st->execute();
        $st->close();

        // Cliente en el panel admin (por correo)
        $st = $conn->prepare("SELECT id FROM admin_clientes WHERE email = ? LIMIT 1");
        $st->bind_param('s', $cot['email']);
        $st->execute();
        $ac = $st->get_result()->fetch_assoc();
        $st->close();
        if ($ac) {
            $adminClienteId = (int)$ac['id'];
        } else {
            [$nom, $ape] = array_pad(explode(' ', trim($cot['nombre']), 2), 2, '');
            $st = $conn->prepare("INSERT INTO admin_clientes (nombre, apellido, email, telefono) VALUES (?, ?, ?, ?)");
            $st->bind_param('ssss', $nom, $ape, $cot['email'], $cot['telefono']);
            $st->execute();
            $adminClienteId = (int)$conn->insert_id;
            $st->close();
        }

        // Pedido en el panel admin (número = folio de la cotización)
        $subtotal = (float)$cot['subtotal'];
        $iva      = (float)$cot['iva'];
        $envio    = $cot['envio_tipo'] === 'con_costo' ? (float)$cot['envio_costo'] : 0.0;
        $notas    = 'Pago de cotización ' . $cot['folio'] . ' · ref. ' . $referencia;
        $st = $conn->prepare(
            "INSERT INTO admin_pedidos (numero_pedido, cliente_id, nombre_contacto, email_contacto, telefono_contacto,
                                        estado, subtotal, impuestos, costo_envio, total, metodo_pago, notas)
             VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?, ?, ?, ?, ?)"
        );
        $st->bind_param('sisssddddss', $cot['folio'], $adminClienteId, $cot['nombre'], $cot['email'], $cot['telefono'],
                        $subtotal, $iva, $envio, $monto, $metodo, $notas);
        $st->execute();
        $adminPedidoId = (int)$conn->insert_id;
        $st->close();

        if (cot_columna_existe($conn, 'pedidos', 'admin_pedido_id')) {
            $st = $conn->prepare("UPDATE pedidos SET admin_pedido_id = ? WHERE id = ?");
            $st->bind_param('ii', $adminPedidoId, $pedidoId);
            $st->execute();
            $st->close();
        }

        $st = $conn->prepare("INSERT INTO admin_envios (pedido_id, estado) VALUES (?, 'pendiente')");
        $st->bind_param('i', $adminPedidoId);
        $st->execute();
        $st->close();

        $st = $conn->prepare(
            "INSERT INTO admin_detalle_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal_linea)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        foreach ($items as $it) {
            $pid    = $it['producto_id'] ? (int)$it['producto_id'] : null;
            $qty    = (int)$it['cantidad'];
            $precio = (float)$it['precio'];
            $linea  = round($precio * $qty, 2);
            $st->bind_param('iisidd', $adminPedidoId, $pid, $it['nombre_producto'], $qty, $precio, $linea);
            $st->execute();
        }
        $st->close();

        // Marcar cotización como pagada
        $st = $conn->prepare(
            "UPDATE cotizaciones
                SET status = 'pagada', pagada_en = NOW(), pago_metodo = ?, pago_referencia = ?, pedido_id = ?
              WHERE id = ?"
        );
        $st->bind_param('ssii', $metodo, $referencia, $pedidoId, $cotId);
        $st->execute();
        $st->close();

        $conn->commit();
        return ['pedido_id' => $pedidoId, 'cot' => $cot, 'items' => $items];
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $_) {}
        throw $e;
    }
}

// Datos para confirmacion.php + correo de confirmación (se llama tras responder al navegador).
function cot_post_pago(array $res, float $monto, string $metodoLabel, string $referencia): void {
    $cot = $res['cot'];
    $_SESSION['paypal_confirmacion'] = [
        'pedido_id'   => $res['pedido_id'],
        'order_id'    => $referencia,
        'payer_email' => $cot['email'],
        'payer_name'  => $cot['nombre'],
        'monto'       => number_format($monto, 2),
        'moneda'      => 'MXN',
        'metodo'      => strtolower($metodoLabel),
        'status'      => 'COMPLETED',
    ];
    unset($_SESSION['pago_cot_id']);
}

function cot_enviar_confirmacion(array $res, float $monto, string $metodoLabel): void {
    $items = array_map(fn($it) => [
        'nombre'   => $it['nombre_producto'],
        'cantidad' => (int)$it['cantidad'],
        'precio'   => (float)$it['precio'],
    ], $res['items']);
    sendConfirmacionPedido($res['cot']['email'], $res['cot']['nombre'], [
        'pedido_id' => $res['pedido_id'],
        'monto'     => number_format($monto, 2),
        'moneda'    => 'MXN',
        'metodo'    => $metodoLabel,
        'items'     => $items,
    ]);
}
