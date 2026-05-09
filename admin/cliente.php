<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('clientes');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0 || !admin_table_exists($conn, 'admin_clientes')) {
    admin_redirect('clientes.php');
}

$st = $conn->prepare('SELECT * FROM admin_clientes WHERE id = ?');
$st->bind_param('i', $id);
$st->execute();
$c = $st->get_result()->fetch_assoc();
$st->close();
if (!$c) {
    admin_redirect('clientes.php');
}

$pageTitle = 'Cliente';
$activeId = 'clientes';

$pedidos = [];
$st = $conn->prepare(
    'SELECT id, numero_pedido, creado_en, estado, total FROM admin_pedidos WHERE cliente_id = ? ORDER BY creado_en DESC'
);
$st->bind_param('i', $id);
$st->execute();
$r = $st->get_result();
while ($row = $r->fetch_assoc()) {
    $pedidos[] = $row;
}
$st->close();

$nPed = count($pedidos);
$ultimoPed = $nPed ? $pedidos[0] : null;
$freq = $ultimoPed
    ? ('Último pedido: ' . $ultimoPed['creado_en'] . ' · Total pedidos: ' . $nPed)
    : 'Sin compras vinculadas';

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1><?= admin_h($c['nombre'] . ' ' . $c['apellido']) ?></h1>
    <p><a href="clientes.php">← Regresar a lista de clientes</a></p>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; margin-bottom:20px;">
    <div class="card" style="margin-bottom:0;">
        <h2>Datos de contacto</h2>
        <div style="display:grid; gap:10px; font-size:13px;">
            <div>
                <span class="muted"
                    style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Email</span>
                <strong><?= admin_h($c['email']) ?></strong>
            </div>
            <div>
                <span class="muted"
                    style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Teléfono</span>
                <strong><?= admin_h($c['telefono']) ?: '—' ?></strong>
            </div>
            <div>
                <span class="muted"
                    style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Fecha
                    de alta</span>
                <strong><?= admin_h($c['creado_en']) ?></strong>
            </div>
        </div>
    </div>
    <div class="card" style="margin-bottom:0;">
        <h2>Resumen de actividad</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div>
                <span class="muted"
                    style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Pedidos</span>
                <div style="font-family:'Manrope',sans-serif; font-weight:800; font-size:1.5rem;"><?= $nPed ?></div>
            </div>
            <div>
                <span class="muted"
                    style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Estado</span>
                <div style="font-size:13px; color:var(--text-secondary);"><?= admin_h($freq) ?></div>
            </div>
        </div>
        <?php if ($c['notas']): ?>
            <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border-light);">
                <span class="muted"
                    style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px;">Notas</span>
                <p style="font-size:13px; color:var(--text-secondary);"><?= nl2br(admin_h($c['notas'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>Historial de pedidos</h2>
    <?php if (empty($pedidos)): ?>
        <p class="muted" style="padding:12px 0;">Este cliente aún no tiene pedidos vinculados.</p>
    <?php else: ?>
        <table class="data">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td><a href="venta.php?id=<?= (int) $p['id'] ?>"
                                style="color:var(--primary); font-weight:600; text-decoration:none;"><?= admin_h($p['numero_pedido']) ?></a>
                        </td>
                        <td class="muted"><?= admin_h($p['creado_en']) ?></td>
                        <td><?= admin_estado_badge($p['estado']) ?></td>
                        <td style="font-weight:600;">$<?= number_format((float) $p['total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>