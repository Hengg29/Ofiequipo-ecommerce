<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('productos');

$pageTitle = 'Productos';
$activeId  = 'productos';
$msg         = '';

$hasPrecio = false;
$hasActivo = false;
if (admin_table_exists($conn, 'producto')) {
    $hasPrecio = (bool) $conn->query("SHOW COLUMNS FROM producto LIKE 'precio'")->num_rows;
    $hasActivo = (bool) $conn->query("SHOW COLUMNS FROM producto LIKE 'activo'")->num_rows;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_activo']) && $hasActivo) {
    $pid = (int) ($_POST['producto_id'] ?? 0);
    $nv  = (int) ($_POST['nuevo_activo'] ?? 0);
    if ($pid > 0) {
        $st = $conn->prepare('UPDATE producto SET activo = ? WHERE id = ?');
        if ($st) {
            $st->bind_param('ii', $nv, $pid);
            $st->execute();
            $st->close();
            admin_audit($conn, 'toggle_activo', 'producto', $pid, (string) $nv);
            $msg = 'Estado actualizado.';
        } else {
            error_log('[Admin/productos] prepare toggle_activo: ' . $conn->error);
            $msg = 'Error al actualizar el estado.';
        }
    }
}

$term = trim($_GET['q'] ?? '');

$where  = '';
$types  = '';
$params = [];

if ($term !== '') {
    $conds    = ['p.nombre LIKE ?', 'c.nombre LIKE ?'];
    $like     = '%' . $term . '%';
    $types   .= 'ss';
    $params[] = $like;
    $params[] = $like;

    if ($hasPrecio && is_numeric($term)) {
        $termNum   = (float) $term;
        $tolerance = max(50.0, $termNum * 0.1); // exacto o "cercano" (±10%, mínimo ±$50)
        $conds[]   = 'ABS(p.precio - ?) <= ?';
        $types    .= 'dd';
        $params[]  = $termNum;
        $params[]  = $tolerance;
    }

    $where = ' WHERE (' . implode(' OR ', $conds) . ')';
}

$sql = 'SELECT p.*, c.nombre AS cat_nombre FROM producto p LEFT JOIN categoria c ON c.id = p.categoria_id'
     . $where . ' ORDER BY p.id DESC LIMIT 500';

$rows = [];
$st   = $conn->prepare($sql);
if ($st === false) {
    error_log('[Admin/productos] prepare falló: ' . $conn->error);
} else {
    if ($types !== '') {
        $st->bind_param($types, ...$params);
    }
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $st->close();
}

$umbralBajo = 5;

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Gestión de productos</h1>
    <p>Alta, edición, precio, stock, imagen y categoría. Alertas si stock ≤ <?= (int) $umbralBajo ?>.</p>
</div>
<?php if ($msg): ?><div class="alert ok"><?= admin_h($msg) ?></div><?php endif; ?>

<div style="margin-bottom:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;">
    <a class="btn btn-primary" href="producto_edit.php">+ Nuevo producto</a>

    <form method="get" style="display:flex;gap:8px;align-items:center;">
        <input type="text" name="q" value="<?= admin_h($term) ?>"
               placeholder="Buscar por nombre, categoría o precio..."
               style="min-width:280px;">
        <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
        <?php if ($term !== ''): ?>
            <a class="btn btn-ghost btn-sm" href="productos.php">Limpiar</a>
        <?php endif; ?>
    </form>
</div>
<?php if ($term !== ''): ?>
    <p style="margin:-8px 0 16px;color:var(--muted,#6b7280);font-size:13px;">
        <?= count($rows) ?> resultado(s) para "<?= admin_h($term) ?>"<?= (is_numeric($term) && $hasPrecio) ? ' (incluye precios exactos o cercanos)' : '' ?>.
    </p>
<?php endif; ?>

<div class="card">
    <table class="data">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Destacado</th>
                <?php if ($hasActivo): ?><th>Activo</th><?php endif; ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $p): ?>
                <?php
                $stock = (int) ($p['stock'] ?? 0);
                $bajo  = $stock <= $umbralBajo;
                ?>
                <tr style="<?= $bajo ? 'background:rgba(245,158,11,.08);' : '' ?>">
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= admin_h($p['nombre']) ?><?= $bajo ? ' <span class="badge pendiente">bajo</span>' : '' ?></td>
                    <td><?= admin_h($p['cat_nombre'] ?? '') ?></td>
                    <td><?= $hasPrecio ? '$' . number_format((float) ($p['precio'] ?? 0), 2) : '—' ?></td>
                    <td><?= $stock ?></td>
                    <td><?= !empty($p['destacado']) ? 'Sí' : 'No' ?></td>
                    <?php if ($hasActivo): ?>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="toggle_activo" value="1">
                                <input type="hidden" name="producto_id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="nuevo_activo" value="<?= !empty($p['activo']) ? 0 : 1 ?>">
                                <button type="submit" class="btn btn-ghost btn-sm"><?= !empty($p['activo']) ? 'Sí · desactivar' : 'No · activar' ?></button>
                            </form>
                        </td>
                    <?php endif; ?>
                    <td><a class="btn btn-primary btn-sm" href="producto_edit.php?id=<?= (int) $p['id'] ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
