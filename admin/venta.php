<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('ventas');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0 || !admin_table_exists($conn, 'admin_pedidos')) {
    admin_redirect('ventas.php');
}

$pageTitle = 'Detalle de pedido';
$activeId  = 'ventas';
$msg       = '';

$stmt = $conn->prepare('SELECT * FROM admin_pedidos WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$ped = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$ped) {
    admin_redirect('ventas.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo = trim($_POST['estado'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    $ok    = in_array(
        $nuevo,
        ['pendiente', 'en_preparacion', 'enviado', 'entregado', 'cancelado'],
        true
    );
    if ($ok) {
        $st = $conn->prepare('UPDATE admin_pedidos SET estado = ?, notas = ? WHERE id = ?');
        $st->bind_param('ssi', $nuevo, $notas, $id);
        $st->execute();
        $st->close();
        admin_audit($conn, 'actualizar', 'pedido', $id, "estado=$nuevo");
        $ped['estado'] = $nuevo;
        $ped['notas']  = $notas;

        // Sincronizar fila de envío
        if (admin_table_exists($conn, 'admin_envios')) {
            $chk = $conn->prepare('SELECT id FROM admin_envios WHERE pedido_id = ?');
            $chk->bind_param('i', $id);
            $chk->execute();
            $ex = $chk->get_result()->fetch_assoc();
            $chk->close();
            $es = $nuevo;
            if ($ex) {
                $up = $conn->prepare('UPDATE admin_envios SET estado = ? WHERE pedido_id = ?');
                $up->bind_param('si', $es, $id);
                $up->execute();
                $up->close();
            } else {
                $ins = $conn->prepare('INSERT INTO admin_envios (pedido_id, estado) VALUES (?, ?)');
                $ins->bind_param('is', $id, $es);
                $ins->execute();
                $ins->close();
            }
        }
        $msg = 'Pedido actualizado.';
    }
}

$detalle = [];
$st = $conn->prepare('SELECT * FROM admin_detalle_pedido WHERE pedido_id = ? ORDER BY id');
$st->bind_param('i', $id);
$st->execute();
$r = $st->get_result();
while ($row = $r->fetch_assoc()) {
    $detalle[] = $row;
}
$st->close();

$envio = null;
if (admin_table_exists($conn, 'admin_envios')) {
    $st = $conn->prepare('SELECT * FROM admin_envios WHERE pedido_id = ?');
    $st->bind_param('i', $id);
    $st->execute();
    $envio = $st->get_result()->fetch_assoc();
    $st->close();
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Pedido <?= admin_h($ped['numero_pedido']) ?></h1>
    <p><a href="ventas.php" style="color: var(--accent);">← Volver al historial</a></p>
</div>

<?php if ($msg): ?><div class="alert ok"><?= admin_h($msg) ?></div><?php endif; ?>

<div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); align-items: start;">
    <div class="card">
        <h2>Cliente</h2>
        <p><strong><?= admin_h($ped['nombre_contacto']) ?></strong><br>
        <?= admin_h($ped['email_contacto']) ?><br>
        <?= admin_h($ped['telefono_contacto']) ?></p>
        <p class="muted">Creado: <?= admin_h($ped['creado_en']) ?></p>
    </div>
    <div class="card">
        <h2>Totales</h2>
        <table class="data">
            <tr><td>Subtotal</td><td>$<?= number_format((float) $ped['subtotal'], 2) ?></td></tr>
            <tr><td>Impuestos</td><td>$<?= number_format((float) $ped['impuestos'], 2) ?></td></tr>
            <tr><td>Envío</td><td>$<?= number_format((float) $ped['costo_envio'], 2) ?></td></tr>
            <tr><td><strong>Total</strong></td><td><strong>$<?= number_format((float) $ped['total'], 2) ?></strong></td></tr>
        </table>
        <p class="muted">Pago: <?= admin_h($ped['metodo_pago']) ?></p>
    </div>
</div>

<div class="card">
    <h2>Líneas del pedido</h2>
    <table class="data">
        <thead>
            <tr><th>Producto</th><th>Cant.</th><th>P. unit.</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            <?php foreach ($detalle as $d): ?>
                <tr>
                    <td><?= admin_h($d['nombre_producto']) ?> <?php if ($d['producto_id']): ?><span class="muted">#<?= (int) $d['producto_id'] ?></span><?php endif; ?></td>
                    <td><?= (int) $d['cantidad'] ?></td>
                    <td>$<?= number_format((float) $d['precio_unitario'], 2) ?></td>
                    <td>$<?= number_format((float) $d['subtotal_linea'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Estado del pedido</h2>
    <form method="post">
        <div class="form-row">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <?php foreach (['pendiente', 'en_preparacion', 'enviado', 'entregado', 'cancelado'] as $es): ?>
                    <option value="<?= $es ?>" <?= $ped['estado'] === $es ? 'selected' : '' ?>><?= $es ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="notas">Notas internas</label>
            <textarea name="notas" id="notas" rows="3" style="max-width:100%;"><?= admin_h($ped['notas'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
    </form>
</div>

<?php if ($envio): ?>
<div class="card">
    <h2>Envío vinculado</h2>
    <p>Estado envío: <span class="badge <?= admin_h($envio['estado']) ?>"><?= admin_h($envio['estado']) ?></span><br>
    Guía: <?= admin_h($envio['guia_rastreo'] ?: '—') ?> · Transportista: <?= admin_h($envio['transportista'] ?: '—') ?></p>
    <a class="btn btn-ghost btn-sm" href="envios.php?pedido=<?= (int) $id ?>">Editar en módulo envíos</a>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_end.php'; ?>
