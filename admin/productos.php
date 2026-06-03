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
    csrf_verify();
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

$perPage    = 50;
$page       = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset     = ($page - 1) * $perPage;

$totalRows  = 0;
$cntRes = $conn->query('SELECT COUNT(*) AS cnt FROM producto');
if ($cntRes) {
    $totalRows = (int) $cntRes->fetch_assoc()['cnt'];
}
$totalPages = (int) ceil($totalRows / $perPage);

$st = $conn->prepare('SELECT p.*, c.nombre AS cat_nombre FROM producto p LEFT JOIN categoria c ON c.id = p.categoria_id ORDER BY p.id DESC LIMIT ? OFFSET ?');
$rows = [];
if ($st) {
    $st->bind_param('ii', $perPage, $offset);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $st->close();
} else {
    error_log('[Admin/productos] query falló: ' . $conn->error);
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Gestión de productos</h1>
    <p>Alta, edición, precio, stock, imagen y categoría.</p>
</div>
<?php if ($msg): ?><div class="alert ok"><?= admin_h($msg) ?></div><?php endif; ?>

<div style="margin-bottom:16px;">
    <a class="btn btn-primary" href="producto_edit.php">+ Nuevo producto</a>
</div>

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
                ?>
                <tr>
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= admin_h($p['nombre']) ?></td>
                    <td><?= admin_h($p['cat_nombre'] ?? '') ?></td>
                    <td><?= $hasPrecio ? '$' . number_format((float) ($p['precio'] ?? 0), 2) : '—' ?></td>
                    <td style="text-align:center;">
                        <?php if ($stock): ?>
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#dcfce7;color:#16a34a;font-size:15px;font-weight:700;" title="Con existencia">✓</span>
                        <?php else: ?>
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#fee2e2;color:#dc2626;font-size:15px;font-weight:700;" title="Sin existencia">✕</span>
                        <?php endif; ?>
                    </td>
                    <td><?= !empty($p['destacado']) ? 'Sí' : 'No' ?></td>
                    <?php if ($hasActivo): ?>
                        <td>
                            <form method="post" style="display:inline;">
                                <?= csrf_field() ?>
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

<?php if ($totalPages > 1): ?>
<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:16px;justify-content:center;">
    <?php
    $pageBtn = function(int $n, int $cur) {
        $cls  = $n === $cur ? 'btn-primary' : 'btn-ghost';
        $aria = $n === $cur ? ' aria-current="page"' : '';
        echo '<a href="?' . http_build_query(['page' => $n]) . '" class="btn btn-sm ' . $cls . '"' . $aria . '>' . $n . '</a>';
    };

    $window = 2;
    $shown  = [];

    $candidates = array_unique(array_filter([
        1, 2, 3,
        $page - 1, $page, $page + 1,
        $totalPages - 2, $totalPages - 1, $totalPages,
    ], fn($n) => $n >= 1 && $n <= $totalPages));
    sort($candidates);

    $prev = 0;
    foreach ($candidates as $n) {
        if ($prev && $n - $prev > 1) {
            echo '<span style="padding:0 4px;color:#888;">…</span>';
        }
        $pageBtn($n, $page);
        $prev = $n;
    }
    ?>

    <form method="get" style="display:inline-flex;align-items:center;gap:6px;margin-left:10px;"
          onsubmit="var v=parseInt(this.pg.value);if(v>=1&&v<=<?= $totalPages ?>){window.location='?page='+v;}return false;">
        <input type="number" name="pg" min="1" max="<?= $totalPages ?>"
               placeholder="Ir a…"
               style="width:72px;padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;text-align:center;">
        <button type="submit" class="btn btn-ghost btn-sm">Ir</button>
    </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_end.php'; ?>
