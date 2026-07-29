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

$perPage = 50;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$total = 0;
$countSql = 'SELECT COUNT(*) AS n FROM producto p LEFT JOIN categoria c ON c.id = p.categoria_id' . $where;
$stCount  = $conn->prepare($countSql);
if ($stCount === false) {
    error_log('[Admin/productos] prepare count falló: ' . $conn->error);
} else {
    if ($types !== '') {
        $stCount->bind_param($types, ...$params);
    }
    $stCount->execute();
    $total = (int) ($stCount->get_result()->fetch_assoc()['n'] ?? 0);
    $stCount->close();
}
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$sql = 'SELECT p.*, c.nombre AS cat_nombre FROM producto p LEFT JOIN categoria c ON c.id = p.categoria_id'
     . $where . ' ORDER BY p.id DESC LIMIT ? OFFSET ?';

$rows = [];
$st   = $conn->prepare($sql);
if ($st === false) {
    error_log('[Admin/productos] prepare falló: ' . $conn->error);
} else {
    $st->bind_param($types . 'ii', ...[...$params, $perPage, $offset]);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $st->close();
}

require __DIR__ . '/includes/layout.php';
?>
<style>
.search-box-productos {
    position: relative;
    min-width: 300px;
}
.search-box-productos input[type=text] {
    width: 100%;
    padding: 10px 14px 10px 38px;
    border-radius: var(--radius-sm, 8px);
    border: 1px solid var(--border, #d1d5db);
    background: var(--surface, #fff);
    color: var(--text, #111827);
    font-family: inherit;
    font-size: 13.5px;
    transition: border-color .2s, box-shadow .2s;
}
.search-box-productos input[type=text]:focus {
    outline: none;
    border-color: var(--primary, #1D3D8E);
    box-shadow: 0 0 0 3px rgba(29,61,142,.1);
}
.search-box-productos .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted, #9ca3af);
    pointer-events: none;
    font-size: 14px;
}
.data th.col-center, .data td.col-center { text-align: center; }
</style>

<div class="page-head">
    <h1>Gestión de productos</h1>
    <p>Alta, edición, precio, stock, imagen y categoría.</p>
</div>
<?php if ($msg): ?><div class="alert ok"><?= admin_h($msg) ?></div><?php endif; ?>

<div style="margin-bottom:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;">
    <a class="btn btn-primary" href="producto_edit.php">+ Nuevo producto</a>

    <form method="get" style="display:flex;gap:8px;align-items:center;">
        <div class="search-box-productos">
            <span class="search-icon">🔎</span>
            <input type="text" name="q" value="<?= admin_h($term) ?>"
                   placeholder="Buscar por nombre, categoría o precio...">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
        <?php if ($term !== ''): ?>
            <a class="btn btn-ghost btn-sm" href="productos.php">Limpiar</a>
        <?php endif; ?>
    </form>
</div>
<p style="margin:-8px 0 16px;color:var(--muted,#6b7280);font-size:13px;">
    <?= $total ?> producto(s)<?= $term !== '' ? ' para "' . admin_h($term) . '"' . ((is_numeric($term) && $hasPrecio) ? ' (incluye precios exactos o cercanos)' : '') : '' ?>
    · página <?= $page ?> de <?= $totalPages ?>
</p>

<div class="card">
    <table class="data">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th class="col-center">Stock</th>
                <th>Destacado</th>
                <?php if ($hasActivo): ?><th>Activo</th><?php endif; ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $p): ?>
                <?php $stock = (int) ($p['stock'] ?? 0); ?>
                <tr>
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= admin_h($p['nombre']) ?></td>
                    <td><?= admin_h($p['cat_nombre'] ?? '') ?></td>
                    <td><?= $hasPrecio ? '$' . number_format((float) ($p['precio'] ?? 0), 2) : '—' ?></td>
                    <td class="col-center"><?= $stock ? '<span style="color:#16a34a;font-weight:700;font-size:16px;">✓</span>' : '<span style="color:#dc2626;font-weight:700;font-size:16px;">✗</span>' ?></td>
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

<?php if ($totalPages > 1): ?>
<?php
$pageUrl = function (int $p) use ($term): string {
    $qs = ['page' => $p];
    if ($term !== '') $qs['q'] = $term;
    return 'productos.php?' . http_build_query($qs);
};
?>
<div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;justify-content:center;margin-top:18px;">
    <?php if ($page > 1): ?>
        <a class="btn btn-ghost btn-sm" href="<?= admin_h($pageUrl($page - 1)) ?>">← Anterior</a>
    <?php endif; ?>

    <?php
    $start = max(1, $page - 3);
    $end   = min($totalPages, $page + 3);
    if ($start > 1) echo '<a class="btn btn-ghost btn-sm" href="' . admin_h($pageUrl(1)) . '">1</a>';
    if ($start > 2) echo '<span style="padding:0 4px;color:var(--muted,#6b7280);">…</span>';
    for ($i = $start; $i <= $end; $i++):
    ?>
        <a class="btn <?= $i === $page ? 'btn-primary' : 'btn-ghost' ?> btn-sm" href="<?= admin_h($pageUrl($i)) ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php
    if ($end < $totalPages - 1) echo '<span style="padding:0 4px;color:var(--muted,#6b7280);">…</span>';
    if ($end < $totalPages) echo '<a class="btn btn-ghost btn-sm" href="' . admin_h($pageUrl($totalPages)) . '">' . $totalPages . '</a>';
    ?>

    <?php if ($page < $totalPages): ?>
        <a class="btn btn-ghost btn-sm" href="<?= admin_h($pageUrl($page + 1)) ?>">Siguiente →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_end.php'; ?>
