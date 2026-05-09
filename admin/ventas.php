<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('ventas');

$pageTitle = 'Ventas';
$activeId  = 'ventas';

$desde   = $_GET['desde'] ?? date('Y-m-01');
$hasta   = $_GET['hasta'] ?? date('Y-m-d');
$estado  = trim($_GET['estado'] ?? '');
$cliente = trim($_GET['cliente'] ?? '');
$prod    = isset($_GET['producto_id']) ? (int) $_GET['producto_id'] : 0;
$export  = $_GET['export'] ?? '';

if (!admin_table_exists($conn, 'admin_pedidos')) {
    require __DIR__ . '/includes/layout.php';
    echo '<div class="alert err">Ejecuta <code>db/Ofi_com.sql</code> para habilitar ventas.</div>';
    require __DIR__ . '/includes/layout_end.php';
    exit;
}

$where = ['1=1'];
$params = [];
$types  = '';

if ($desde !== '') {
    $where[] = 'DATE(p.creado_en) >= ?';
    $params[] = $desde;
    $types   .= 's';
}
if ($hasta !== '') {
    $where[] = 'DATE(p.creado_en) <= ?';
    $params[] = $hasta;
    $types   .= 's';
}
if ($estado !== '') {
    $where[] = 'p.estado = ?';
    $params[] = $estado;
    $types   .= 's';
}
if ($cliente !== '') {
    $where[] = '(p.email_contacto LIKE ? OR p.nombre_contacto LIKE ?)';
    $like = '%' . $cliente . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if ($prod > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM admin_detalle_pedido d WHERE d.pedido_id = p.id AND d.producto_id = ?)';
    $params[] = $prod;
    $types   .= 'i';
}

$sqlWhere = implode(' AND ', $where);

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ventas_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['numero_pedido', 'fecha', 'estado', 'cliente', 'email', 'total', 'metodo_pago']);

    $q = "SELECT p.numero_pedido, p.creado_en, p.estado, p.nombre_contacto, p.email_contacto, p.total, p.metodo_pago
          FROM admin_pedidos p WHERE $sqlWhere ORDER BY p.creado_en DESC";
    $stmt = $conn->prepare($q);
    if ($params && $stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
    } elseif ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query($q);
    }
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            fputcsv($out, [
                $row['numero_pedido'],
                $row['creado_en'],
                $row['estado'],
                $row['nombre_contacto'],
                $row['email_contacto'],
                $row['total'],
                $row['metodo_pago'],
            ]);
        }
    }
    fclose($out);
    admin_audit($conn, 'export_csv', 'ventas', null, 'Filtros: ' . $sqlWhere);
    exit;
}

$pedidos = [];
$q = "SELECT p.* FROM admin_pedidos p WHERE $sqlWhere ORDER BY p.creado_en DESC LIMIT 200";
$stmt = $conn->prepare($q);
if ($params && $stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} elseif ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($q);
}
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pedidos[] = $row;
    }
}

$productosFilt = [];
if (admin_table_exists($conn, 'producto')) {
    $pr = $conn->query('SELECT id, nombre FROM producto ORDER BY nombre LIMIT 500');
    if ($pr) {
        while ($row = $pr->fetch_assoc()) {
            $productosFilt[] = $row;
        }
    }
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Historial de ventas</h1>
    <p>Listado de pedidos con filtros y exportación CSV.</p>
</div>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
    <a class="btn btn-primary" href="ventas_nuevo.php">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Nuevo pedido
    </a>
    <a class="btn btn-ghost" href="ventas.php?<?= admin_h(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
        Descargar CSV
    </a>
</div>

<form class="filters card" method="get" style="padding:18px 24px;">
    <div class="form-row" style="display:inline-block; width:auto; min-width:140px;">
        <label>Desde</label>
        <input type="date" name="desde" value="<?= admin_h($desde) ?>">
    </div>
    <div class="form-row" style="display:inline-block; width:auto; min-width:140px;">
        <label>Hasta</label>
        <input type="date" name="hasta" value="<?= admin_h($hasta) ?>">
    </div>
    <div class="form-row" style="display:inline-block; width:auto; min-width:160px;">
        <label>Estado</label>
        <select name="estado">
            <option value="">Todos</option>
            <?php foreach (['pendiente', 'en_preparacion', 'enviado', 'entregado', 'cancelado'] as $es): ?>
                <option value="<?= $es ?>" <?= $estado === $es ? 'selected' : '' ?>><?= $es ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-row" style="display:inline-block; width:auto; min-width:200px;">
        <label>Cliente</label>
        <input type="text" name="cliente" value="<?= admin_h($cliente) ?>" placeholder="Buscar...">
    </div>
    <div class="form-row" style="display:inline-block; width:auto; min-width:200px;">
        <label>Producto</label>
        <select name="producto_id">
            <option value="0">—</option>
            <?php foreach ($productosFilt as $pf): ?>
                <option value="<?= (int) $pf['id'] ?>" <?= $prod === (int) $pf['id'] ? 'selected' : '' ?>><?= admin_h($pf['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-row" style="margin-bottom:0;">
        <label>&nbsp;</label>
        <button type="submit" class="btn btn-primary btn-sm">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            Filtrar
        </button>
    </div>
</form>

<div class="card">
    <table class="data">
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Cliente</th>
                <th>Total</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pedidos)): ?>
                <tr><td colspan="6" class="muted" style="text-align:center; padding:30px;">No hay pedidos con estos filtros.</td></tr>
            <?php endif; ?>
            <?php foreach ($pedidos as $p): ?>
                <tr>
                    <td style="font-weight:600;"><?= admin_h($p['numero_pedido']) ?></td>
                    <td class="muted"><?= admin_h($p['creado_en']) ?></td>
                    <td><?= admin_estado_badge($p['estado']) ?></td>
                    <td>
                        <?= admin_h($p['nombre_contacto']) ?>
                        <br><span class="muted"><?= admin_h($p['email_contacto']) ?></span>
                    </td>
                    <td style="font-weight:600;">$<?= number_format((float) $p['total'], 2) ?></td>
                    <td><a class="btn btn-ghost btn-sm" href="venta.php?id=<?= (int) $p['id'] ?>">Ver</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
