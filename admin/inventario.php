<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('inventario');

$pageTitle = 'Inventario';
$activeId  = 'inventario';
$msg         = '';

if (!admin_table_exists($conn, 'admin_inventario_mov')) {
    require __DIR__ . '/includes/layout.php';
    echo '<div class="alert err">Ejecuta db/Ofi_com.sql (tabla admin_inventario_mov).</div>';
    require __DIR__ . '/includes/layout_end.php';
    exit;
}

$uid = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mov'])) {
    $pid = (int) ($_POST['producto_id'] ?? 0);
    $tipo = trim($_POST['tipo'] ?? '');
    $cant = (int) ($_POST['cantidad'] ?? 0);
    $nota = trim($_POST['nota'] ?? '');
    $okTipo = in_array($tipo, ['entrada', 'salida', 'ajuste', 'venta'], true);
    if ($pid > 0 && $okTipo && $cant > 0) {
        $conn->begin_transaction();
        try {
            $st = $conn->prepare('SELECT stock FROM producto WHERE id = ? FOR UPDATE');
            $st->bind_param('i', $pid);
            $st->execute();
            $pr = $st->get_result()->fetch_assoc();
            $st->close();
            if (!$pr) {
                throw new RuntimeException('Producto inexistente');
            }
            $stock = (int) $pr['stock'];
            $nuevo = $stock;
            $absmov = $cant;
            if ($tipo === 'entrada') {
                $nuevo = $stock + $cant;
            } elseif ($tipo === 'salida' || $tipo === 'venta') {
                $nuevo = max(0, $stock - $cant);
            } elseif ($tipo === 'ajuste') {
                $nuevo = max(0, $cant);
            }
            $up = $conn->prepare('UPDATE producto SET stock = ? WHERE id = ?');
            $up->bind_param('ii', $nuevo, $pid);
            $up->execute();
            $up->close();

            $ref = '';
            $ins = $conn->prepare(
                'INSERT INTO admin_inventario_mov (producto_id, tipo, cantidad, stock_despues, referencia, nota, usuario_id)
                 VALUES (?,?,?,?,?,?,?)'
            );
            $ins->bind_param('isiissi', $pid, $tipo, $absmov, $nuevo, $ref, $nota, $uid);
            $ins->execute();
            $ins->close();
            $conn->commit();
            admin_audit($conn, 'inventario_mov', 'producto', $pid, $tipo);
            $msg = 'Movimiento registrado.';
        } catch (Throwable $e) {
            $conn->rollback();
            $msg = 'Error: ' . $e->getMessage();
        }
    }
}

$prods = [];
$r = $conn->query('SELECT id, nombre, stock FROM producto ORDER BY nombre LIMIT 600');
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $prods[] = $row;
    }
}

$movs = [];
$r = $conn->query(
    'SELECT m.*, p.nombre AS prod_nombre FROM admin_inventario_mov m
     LEFT JOIN producto p ON p.id = m.producto_id
     ORDER BY m.creado_en DESC LIMIT 150'
);
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $movs[] = $row;
    }
}

$agotados = [];
$r = $conn->query('SELECT id, nombre, stock FROM producto WHERE stock <= 0 ORDER BY nombre LIMIT 80');
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $agotados[] = $row;
    }
}
$casi = [];
$r = $conn->query('SELECT id, nombre, stock FROM producto WHERE stock > 0 AND stock <= 5 ORDER BY stock, nombre LIMIT 80');
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $casi[] = $row;
    }
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Inventario y almacén</h1>
    <p>Movimientos de entrada, salida, ajuste o venta interna. Stock actual y alertas.</p>
</div>
<?php if ($msg): ?><div class="alert <?= strpos($msg, 'Error') === 0 ? 'err' : 'ok' ?>"><?= admin_h($msg) ?></div><?php endif; ?>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap:20px; align-items:start;">
    <form method="post" class="card">
        <h2>Registrar movimiento</h2>
        <input type="hidden" name="mov" value="1">
        <div class="form-row"><label>Producto</label>
            <select name="producto_id" required>
                <option value="">—</option>
                <?php foreach ($prods as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= admin_h($p['nombre']) ?> (<?= (int) $p['stock'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label>Tipo</label>
            <select name="tipo">
                <option value="entrada">Entrada</option>
                <option value="salida">Salida</option>
                <option value="ajuste">Ajuste (stock fijo)</option>
                <option value="venta">Salida por venta</option>
            </select>
        </div>
        <div class="form-row"><label>Cantidad</label><input type="number" name="cantidad" min="1" value="1" required></div>
        <div class="form-row"><label>Nota</label><input name="nota" placeholder="opcional"></div>
        <button type="submit" class="btn btn-primary">Registrar</button>
        <p class="muted" style="margin-top:12px;"><strong>Ajuste:</strong> la cantidad indicada será el nuevo stock total.</p>
    </form>
    <div class="card">
        <h2>Agotados o stock crítico</h2>
        <h3 style="font-size:0.85rem; color:var(--danger);">Sin stock</h3>
        <ul class="muted" style="margin:0 0 16px; padding-left:18px;">
            <?php foreach ($agotados as $a): ?>
                <li><?= admin_h($a['nombre']) ?> (<?= (int) $a['stock'] ?>)</li>
            <?php endforeach; ?>
            <?php if (empty($agotados)): ?><li>Ninguno</li><?php endif; ?>
        </ul>
        <h3 style="font-size:0.85rem; color:var(--warning);">1–5 unidades</h3>
        <ul class="muted" style="margin:0; padding-left:18px;">
            <?php foreach ($casi as $a): ?>
                <li><?= admin_h($a['nombre']) ?> — <?= (int) $a['stock'] ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="card">
    <h2>Últimos movimientos</h2>
    <table class="data">
        <thead>
            <tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cant.</th><th>Stock después</th><th>Nota</th></tr>
        </thead>
        <tbody>
            <?php foreach ($movs as $m): ?>
                <tr>
                    <td class="muted"><?= admin_h($m['creado_en']) ?></td>
                    <td><?= admin_h($m['prod_nombre']) ?></td>
                    <td><?= admin_h($m['tipo']) ?></td>
                    <td><?= (int) $m['cantidad'] ?></td>
                    <td><?= $m['stock_despues'] !== null ? (int) $m['stock_despues'] : '—' ?></td>
                    <td><?= admin_h($m['nota']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
