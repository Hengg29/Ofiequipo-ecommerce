<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('dashboard');

$pageTitle = 'Dashboard';
$activeId  = 'dashboard';
$script    = __FILE__;

$ventasTot   = 0;
$ingresos    = 0;
$pedHoy      = 0;
$pedSemana   = 0;
$pedMes      = 0;
$nuevosMes   = 0;
$pend        = 0;
$prep        = 0;
$env         = 0;
$ent         = 0;
$cancel      = 0;
$topProducts = [];
$newClientsW = 0;

if (admin_table_exists($conn, 'admin_pedidos')) {
    $r = $conn->query("SELECT COALESCE(SUM(total),0) AS t FROM admin_pedidos WHERE estado <> 'cancelado'");
    if ($r && $row = $r->fetch_assoc()) {
        $ventasTot = (float) $row['t'];
        $ingresos  = $ventasTot;
    }

    $r = $conn->query("SELECT COUNT(*) AS c FROM admin_pedidos WHERE DATE(creado_en) = CURDATE()");
    if ($r && $row = $r->fetch_assoc()) {
        $pedHoy = (int) $row['c'];
    }
    $r = $conn->query("SELECT COUNT(*) AS c FROM admin_pedidos WHERE creado_en >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    if ($r && $row = $r->fetch_assoc()) {
        $pedSemana = (int) $row['c'];
    }
    $r = $conn->query("SELECT COUNT(*) AS c FROM admin_pedidos WHERE YEAR(creado_en)=YEAR(CURDATE()) AND MONTH(creado_en)=MONTH(CURDATE())");
    if ($r && $row = $r->fetch_assoc()) {
        $pedMes = (int) $row['c'];
    }

    $r = $conn->query(
        "SELECT estado, COUNT(*) AS c FROM admin_pedidos GROUP BY estado"
    );
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            switch ($row['estado']) {
                case 'pendiente':
                    $pend = (int) $row['c'];
                    break;
                case 'en_preparacion':
                    $prep = (int) $row['c'];
                    break;
                case 'enviado':
                    $env = (int) $row['c'];
                    break;
                case 'entregado':
                    $ent = (int) $row['c'];
                    break;
                case 'cancelado':
                    $cancel = (int) $row['c'];
                    break;
            }
        }
    }
}

if (admin_table_exists($conn, 'admin_clientes')) {
    $r = $conn->query(
        "SELECT COUNT(*) AS c FROM admin_clientes WHERE YEAR(creado_en)=YEAR(CURDATE()) AND MONTH(creado_en)=MONTH(CURDATE())"
    );
    if ($r && $row = $r->fetch_assoc()) {
        $nuevosMes = (int) $row['c'];
    }
    $r = $conn->query(
        "SELECT COUNT(*) AS c FROM admin_clientes WHERE creado_en >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
    );
    if ($r && $row = $r->fetch_assoc()) {
        $newClientsW = (int) $row['c'];
    }
}

if (admin_table_exists($conn, 'admin_detalle_pedido')) {
    $sql = "SELECT d.producto_id, d.nombre_producto, SUM(d.cantidad) AS u
            FROM admin_detalle_pedido d
            INNER JOIN admin_pedidos p ON p.id = d.pedido_id
            WHERE p.estado <> 'cancelado'
            GROUP BY d.producto_id, d.nombre_producto
            ORDER BY u DESC
            LIMIT 5";
    $r = $conn->query($sql);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $topProducts[] = $row;
        }
    }
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Dashboard</h1>
    <p>Resumen de ventas, pedidos y actividad reciente.</p>
</div>

<?php if (!admin_table_exists($conn, 'admin_pedidos')): ?>
    <div class="alert err">Las tablas del panel no están instaladas. Ejecuta el script <code>db/Ofi_com.sql</code> en MySQL.</div>
<?php endif; ?>

<div class="grid kpi">
    <div class="kpi-box">
        <div class="lbl">Ventas totales</div>
        <div class="val">$<?= number_format($ventasTot, 2) ?></div>
        <div class="sub">Ingresos acumulados (pedidos no cancelados)</div>
    </div>
    <div class="kpi-box">
        <div class="lbl">Pedidos hoy</div>
        <div class="val"><?= $pedHoy ?></div>
        <div class="sub">Semana: <?= $pedSemana ?> · Mes: <?= $pedMes ?></div>
    </div>
    <div class="kpi-box">
        <div class="lbl">Ingresos</div>
        <div class="val">$<?= number_format($ingresos, 2) ?></div>
        <div class="sub">Mismo total que ventas (vista monetaria)</div>
    </div>
    <div class="kpi-box">
        <div class="lbl">Clientes nuevos</div>
        <div class="val"><?= $nuevosMes ?></div>
        <div class="sub">Este mes · última semana: <?= $newClientsW ?></div>
    </div>
</div>

<div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); align-items: start;">
    <div class="card">
        <h2>Pedidos por estado</h2>
        <table class="data">
            <tr><th>Estado</th><th>Cantidad</th></tr>
            <tr><td><span class="badge pendiente">Pendiente</span></td><td><?= $pend ?></td></tr>
            <tr><td><span class="badge en_preparacion">En preparación</span></td><td><?= $prep ?></td></tr>
            <tr><td><span class="badge enviado">Enviado</span></td><td><?= $env ?></td></tr>
            <tr><td><span class="badge entregado">Entregado</span></td><td><?= $ent ?></td></tr>
            <tr><td><span class="badge cancelado">Cancelado</span></td><td><?= $cancel ?></td></tr>
        </table>
        <p class="muted" style="margin-top:12px;"><strong>Pendientes</strong> incluye pedidos recién creados; <strong>enviado / entregado</strong> reflejan la logística.</p>
    </div>
    <div class="card">
        <h2>Productos más vendidos</h2>
        <?php if (empty($topProducts)): ?>
            <p class="muted">Aún no hay ventas registradas en pedidos.</p>
        <?php else: ?>
            <table class="data">
                <tr><th>Producto</th><th>Unidades</th></tr>
                <?php foreach ($topProducts as $tp): ?>
                    <tr>
                        <td><?= admin_h($tp['nombre_producto']) ?></td>
                        <td><?= (int) $tp['u'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>Accesos rápidos</h2>
    <p style="margin:0 0 12px; color: var(--muted); font-size: 0.9rem;">Historial de ventas, envíos y reportes exportables.</p>
    <div style="display:flex; flex-wrap:wrap; gap:10px;">
        <?php if (admin_can('ventas')): ?><a class="btn btn-primary btn-sm" href="ventas.php">Ver ventas</a><?php endif; ?>
        <?php if (admin_can('envios')): ?><a class="btn btn-ghost btn-sm" href="envios.php">Envíos</a><?php endif; ?>
        <?php if (admin_can('analisis')): ?><a class="btn btn-ghost btn-sm" href="analisis.php">Análisis</a><?php endif; ?>
        <?php if (admin_can('reportes')): ?><a class="btn btn-ghost btn-sm" href="reportes.php">Reportes</a><?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
