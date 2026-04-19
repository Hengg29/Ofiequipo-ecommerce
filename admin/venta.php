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
$activeId = 'ventas';
$msg = '';

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
    $ok = in_array(
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
        $ped['notas'] = $notas;

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
    <p><a href="ventas.php">← Volver al historial</a></p>
</div>

<?php if ($msg): ?>
    <div class="alert ok"><?= admin_h($msg) ?></div><?php endif; ?>

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
        <div style="display:grid; gap:10px; font-size:13px;">
            <div>
                <span class="muted"
                    style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Nombre</span>
                <strong><?= admin_h($ped['nombre_contacto']) ?></strong>
            </div>
            <div>
                <span class="muted"
                    style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Email</span>
                <?= admin_h($ped['email_contacto']) ?>
            </div>
            <div>
                <span class="muted"
                    style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Teléfono</span>
                <?= admin_h($ped['telefono_contacto']) ?: '—' ?>
            </div>
            <div>
                <span class="muted"
                    style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Creado</span>
                <?= admin_h($ped['creado_en']) ?>
            </div>
        </div>
    </div>
    <div class="card" style="margin-bottom:0;">
        <h2>
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                style="vertical-align:-3px; margin-right:6px;">
                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 100 7h5a3.5 3.5 0 110 7H6" />
            </svg>
            Totales
        </h2>
        <table class="data">
            <tr>
                <td>Subtotal</td>
                <td style="text-align:right;">$<?= number_format((float) $ped['subtotal'], 2) ?></td>
            </tr>
            <tr>
                <td>Impuestos</td>
                <td style="text-align:right;">$<?= number_format((float) $ped['impuestos'], 2) ?></td>
            </tr>
            <tr>
                <td>Envío</td>
                <td style="text-align:right;">$<?= number_format((float) $ped['costo_envio'], 2) ?></td>
            </tr>
            <tr style="font-weight:700; font-size:15px;">
                <td>Total</td>
                <td style="text-align:right; color:var(--primary);">$<?= number_format((float) $ped['total'], 2) ?></td>
            </tr>
        </table>
        <p class="muted" style="margin-top:10px;">Pago: <?= admin_h($ped['metodo_pago']) ?></p>
    </div>
</div>

<div class="card">
    <h2>Líneas del pedido</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>P. unit.</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalle as $d): ?>
                <tr>
                    <td style="font-weight:600;">
                        <?= admin_h($d['nombre_producto']) ?>
                        <?php if ($d['producto_id']): ?>
                            <span class="muted">#<?= (int) $d['producto_id'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $d['cantidad'] ?></td>
                    <td>$<?= number_format((float) $d['precio_unitario'], 2) ?></td>
                    <td style="font-weight:600;">$<?= number_format((float) $d['subtotal_linea'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start;">
    <div class="card" style="margin-bottom:0;">
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
                <textarea name="notas" id="notas" rows="3"
                    style="max-width:100%;"><?= admin_h($ped['notas'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z" />
                    <path d="M17 21v-8H7v8M7 3v5h8" />
                </svg>
                Guardar cambios
            </button>
        </form>
    </div>

    <?php if ($envio): ?>
        <div class="card" style="margin-bottom:0;">
            <h2>
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    style="vertical-align:-3px; margin-right:6px;">
                    <rect x="1" y="3" width="15" height="13" rx="1" />
                    <path d="M16 8h4l3 3v5h-7V8z" />
                    <circle cx="5.5" cy="18.5" r="2.5" />
                    <circle cx="18.5" cy="18.5" r="2.5" />
                </svg>
                Envío vinculado
            </h2>
            <div style="display:grid; gap:10px; font-size:13px;">
                <div>
                    <span class="muted"
                        style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Estado
                        envío</span>
                    <span class="badge <?= admin_h($envio['estado']) ?>"><?= admin_h($envio['estado']) ?></span>
                </div>
                <div>
                    <span class="muted"
                        style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Guía</span>
                    <?= admin_h($envio['guia_rastreo'] ?: '—') ?>
                </div>
                <div>
                    <span class="muted"
                        style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Transportista</span>
                    <?= admin_h($envio['transportista'] ?: '—') ?>
                </div>
            </div>
            <a class="btn btn-ghost btn-sm" href="envios.php?pedido=<?= (int) $id ?>" style="margin-top:12px;">Editar
                envío</a>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout_end.php'; ?>