<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('ventas');

if (!admin_table_exists($conn, 'admin_pedidos')) {
    admin_redirect('ventas.php');
}

$pageTitle = 'Nuevo pedido';
$activeId = 'ventas';
$error = '';

// Detectar columna precio en producto
$hasPrecio = false;
$cProbe = @$conn->query("SHOW COLUMNS FROM producto LIKE 'precio'");
if ($cProbe && $cProbe->num_rows) {
    $hasPrecio = true;
}

$productos = [];
$q = $hasPrecio
    ? 'SELECT id, nombre, COALESCE(precio,0) AS precio, stock FROM producto WHERE COALESCE(activo,1)=1 ORDER BY nombre LIMIT 800'
    : 'SELECT id, nombre, 0 AS precio, stock FROM producto ORDER BY nombre LIMIT 800';
$r = @$conn->query($q);
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $productos[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_contacto'] ?? '');
    $email = trim($_POST['email_contacto'] ?? '');
    $tel = trim($_POST['telefono'] ?? '');
    $pid = (int) ($_POST['producto_id'] ?? 0);
    $cant = max(1, (int) ($_POST['cantidad'] ?? 1));
    $estado = trim($_POST['estado'] ?? 'pendiente');
    $metodo = trim($_POST['metodo_pago'] ?? 'pendiente');

    if ($nombre === '' || $email === '' || $pid <= 0) {
        $error = 'Nombre, email y producto son obligatorios.';
    } else {
        $st = $conn->prepare('SELECT id, nombre, COALESCE(precio,0) as precio FROM producto WHERE id = ?');
        if (!$hasPrecio) {
            $st = $conn->prepare('SELECT id, nombre, 0 as precio FROM producto WHERE id = ?');
        }
        $st->bind_param('i', $pid);
        $st->execute();
        $pr = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$pr) {
            $error = 'Producto no válido.';
        } else {
            $precio = (float) $pr['precio'];
            $sub = round($precio * $cant, 2);

            // IVA desde config
            $ivaPct = 16.0;
            $cfg = $conn->query("SELECT valor FROM admin_config WHERE clave='impuesto_iva_pct' LIMIT 1");
            if ($cfg && $c = $cfg->fetch_assoc()) {
                $ivaPct = (float) $c['valor'];
            }
            $impuestos = round($sub * ($ivaPct / 100), 2);
            $envio = 0.0;
            $total = round($sub + $impuestos + $envio, 2);

            $conn->begin_transaction();
            try {
                $ins = $conn->prepare(
                    'INSERT INTO admin_pedidos (numero_pedido, cliente_id, nombre_contacto, email_contacto, telefono_contacto, estado, subtotal, impuestos, costo_envio, total, metodo_pago)
                     VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $num = 'TMP';
                $subFloat = $sub;
                $impFloat = $impuestos;
                $envFloat = $envio;
                $totFloat = $total;
                $ins->bind_param(
                    'sssssdddds',
                    $num,
                    $nombre,
                    $email,
                    $tel,
                    $estado,
                    $subFloat,
                    $impFloat,
                    $envFloat,
                    $totFloat,
                    $metodo
                );
                $ins->execute();
                $newId = (int) $conn->insert_id;
                $ins->close();

                $numero = 'ORD-' . str_pad((string) $newId, 6, '0', STR_PAD_LEFT);
                $u = $conn->prepare('UPDATE admin_pedidos SET numero_pedido = ? WHERE id = ?');
                $u->bind_param('si', $numero, $newId);
                $u->execute();
                $u->close();

                $nomProd = $pr['nombre'];
                $det = $conn->prepare(
                    'INSERT INTO admin_detalle_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal_linea) VALUES (?,?,?,?,?,?)'
                );
                $det->bind_param(
                    'iisidd',
                    $newId,
                    $pid,
                    $nomProd,
                    $cant,
                    $precio,
                    $sub
                );
                $det->execute();
                $det->close();

                if (admin_table_exists($conn, 'admin_envios')) {
                    $inE = $conn->prepare('INSERT INTO admin_envios (pedido_id, estado) VALUES (?, ?)');
                    $inE->bind_param('is', $newId, $estado);
                    $inE->execute();
                    $inE->close();
                }

                $conn->commit();
                admin_audit($conn, 'crear', 'pedido', $newId, $numero);
                admin_redirect('venta.php?id=' . $newId);
            } catch (Throwable $e) {
                $conn->rollback();
                $error = 'No se pudo crear el pedido: ' . $e->getMessage();
            }
        }
    }
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Nuevo pedido</h1>
    <p><a href="ventas.php">← Regresar al historial</a></p>
</div>
<?php if ($error): ?>
    <div class="alert err"><?= admin_h($error) ?></div><?php endif; ?>

<form method="post">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; margin-bottom:20px;">
        <div class="card" style="margin-bottom:0;">
            <h2>
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    style="vertical-align:-3px; margin-right:6px;">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                Cliente
            </h2>
            <div class="form-row"><label>Nombre</label><input name="nombre_contacto" required
                    value="<?= admin_h($_POST['nombre_contacto'] ?? '') ?>"></div>
            <div class="form-row"><label>Email</label><input type="email" name="email_contacto" required
                    value="<?= admin_h($_POST['email_contacto'] ?? '') ?>"></div>
            <div class="form-row"><label>Teléfono</label><input name="telefono"
                    value="<?= admin_h($_POST['telefono'] ?? '') ?>"></div>
        </div>
        <div class="card" style="margin-bottom:0;">
            <h2>
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    style="vertical-align:-3px; margin-right:6px;">
                    <path
                        d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" />
                </svg>
                Producto
            </h2>
            <div class="form-row"><label>Producto</label>
                <select name="producto_id" required>
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= admin_h($p['nombre']) ?>
                            ($<?= number_format((float) $p['precio'], 2) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row"><label>Cantidad</label><input type="number" name="cantidad" min="1"
                    value="<?= (int) ($_POST['cantidad'] ?? 1) ?>"></div>
            <div class="form-row"><label>Estado inicial</label>
                <select name="estado">
                    <?php foreach (['pendiente', 'en_preparacion'] as $es): ?>
                        <option value="<?= $es ?>"><?= $es ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row"><label>Método de pago</label><input name="metodo_pago"
                    value="<?= admin_h($_POST['metodo_pago'] ?? 'transferencia') ?>"></div>
        </div>
    </div>
    <div class="card">
        <p class="muted" style="margin-bottom:12px;">Se calcula IVA según configuración. Ajusta costo de envío después
            si necesitas añadir múltiples líneas.</p>
        <button type="submit" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Crear pedido
        </button>
    </div>
</form>
<?php require __DIR__ . '/includes/layout_end.php'; ?>