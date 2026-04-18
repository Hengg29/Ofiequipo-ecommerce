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
$activeId  = 'clientes';

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

$nPed      = count($pedidos);
$ultimoPed = $nPed ? $pedidos[0] : null;
$freq      = $ultimoPed
    ? ('Último pedido: ' . $ultimoPed['creado_en'] . ' · Total pedidos: ' . $nPed)
    : 'Sin compras vinculados';

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1><?= admin_h($c['nombre'] . ' ' . $c['apellido']) ?></h1>
    <p><a href="clientes.php" style="color: var(--accent);">← Lista</a></p>
</div>

<div class="card">
    <h2>Datos de contacto</h2>
    <p>Email: <?= admin_h($c['email']) ?><br>
    Tel: <?= admin_h($c['telefono']) ?><br>
    Alta: <?= admin_h($c['creado_en']) ?></p>
    <p class="muted"><?= admin_h($freq) ?></p>
    <?php if ($c['notas']): ?><p>Notas: <?= nl2br(admin_h($c['notas'])) ?></p><?php endif; ?>
</div>

<div class="card">
    <h2>Historial de pedidos</h2>
    <?php if (empty($pedidos)): ?>
        <p class="muted">Este cliente aún no tiene <code>cliente_id</code> en pedidos. Vincula pedidos manualmente en BD o desde el checkout futuro.</p>
    <?php else: ?>
        <table class="data">
            <tr><th>Pedido</th><th>Fecha</th><th>Estado</th><th>Total</th></tr>
            <?php foreach ($pedidos as $p): ?>
                <tr>
                    <td><a href="venta.php?id=<?= (int) $p['id'] ?>"><?= admin_h($p['numero_pedido']) ?></a></td>
                    <td><?= admin_h($p['creado_en']) ?></td>
                    <td><?= admin_h($p['estado']) ?></td>
                    <td>$<?= number_format((float) $p['total'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
