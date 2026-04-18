<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('reportes');

$export = $_GET['export'] ?? '';

if ($export === 'inventario' && admin_table_exists($conn, 'producto')) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventario_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $hasPrecio = $conn->query("SHOW COLUMNS FROM producto LIKE 'precio'")->num_rows > 0;
    fputcsv($out, $hasPrecio ? ['id', 'nombre', 'categoria_id', 'stock', 'precio'] : ['id', 'nombre', 'categoria_id', 'stock']);
    $q = $hasPrecio
        ? 'SELECT id, nombre, categoria_id, stock, precio FROM producto ORDER BY id'
        : 'SELECT id, nombre, categoria_id, stock FROM producto ORDER BY id';
    $r = $conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            fputcsv($out, array_values($row));
        }
    }
    fclose($out);
    admin_audit($conn, 'export_csv', 'inventario', null, null);
    exit;
}

if ($export === 'ventas_cliente' && admin_table_exists($conn, 'admin_pedidos')) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ventas_por_cliente_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['email_contacto', 'nombre_contacto', 'pedidos', 'total_acumulado']);
    $sql = "SELECT email_contacto, nombre_contacto, COUNT(*) AS n, SUM(total) AS t
            FROM admin_pedidos WHERE estado <> 'cancelado'
            GROUP BY email_contacto, nombre_contacto ORDER BY t DESC LIMIT 500";
    $r = $conn->query($sql);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            fputcsv($out, [$row['email_contacto'], $row['nombre_contacto'], $row['n'], $row['t']]);
        }
    }
    fclose($out);
    admin_audit($conn, 'export_csv', 'ventas_cliente', null, null);
    exit;
}

if ($export === 'ventas_producto' && admin_table_exists($conn, 'admin_detalle_pedido')) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ventas_por_producto_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['producto', 'unidades', 'importe_estimado']);
    $sql = "SELECT d.nombre_producto, SUM(d.cantidad) AS u, SUM(d.subtotal_linea) AS imp
            FROM admin_detalle_pedido d
            INNER JOIN admin_pedidos p ON p.id = d.pedido_id AND p.estado <> 'cancelado'
            GROUP BY d.nombre_producto ORDER BY imp DESC";
    $r = @$conn->query($sql);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            fputcsv($out, [$row['nombre_producto'], $row['u'], $row['imp']]);
        }
    }
    fclose($out);
    admin_audit($conn, 'export_csv', 'ventas_producto', null, null);
    exit;
}

$pageTitle = 'Reportes';
$activeId  = 'reportes';

$totalVentas = 0;
$utilidad    = 0;
if (admin_table_exists($conn, 'admin_pedidos')) {
    $r = $conn->query("SELECT COALESCE(SUM(total),0) FROM admin_pedidos WHERE estado<>'cancelado'");
    if ($r) {
        $totalVentas = (float) $r->fetch_row()[0];
    }
    // utilidad estimada: total - 60% costo aproximado (placeholder configurable)
    $utilidad = $totalVentas * 0.4;
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Reportes descargables</h1>
    <p>Exportaciones CSV y resumen de utilidad estimada (margen aproximado del 40 % sobre ventas; ajusta lógica contable según tu negocio).</p>
</div>

<div class="card">
    <h2>Resumen</h2>
    <p><strong>Ventas acumuladas (no canceladas):</strong> $<?= number_format($totalVentas, 2) ?></p>
    <p><strong>Utilidad estimada (demo 40 %):</strong> $<?= number_format($utilidad, 2) ?></p>
</div>

<div class="card">
    <h2>Descargas</h2>
    <ul style="line-height:2; color:var(--muted);">
        <li><a href="ventas.php?export=csv" style="color:var(--accent);">Historial de ventas (respeta filtros actuales — abre primero ventas y filtra)</a></li>
        <li><a href="reportes.php?export=inventario" style="color:var(--accent);">Inventario actual</a></li>
        <li><a href="reportes.php?export=ventas_cliente" style="color:var(--accent);">Ventas por cliente (agrupado)</a></li>
        <li><a href="reportes.php?export=ventas_producto" style="color:var(--accent);">Ventas por producto</a></li>
    </ul>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
