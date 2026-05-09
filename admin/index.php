<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('dashboard');

$pageTitle = 'Panel de Control';
$activeId = 'dashboard';
$script = __FILE__;

$ventasTot = 0;
$ingresos = 0;
$pedHoy = 0;
$pedSemana = 0;
$pedMes = 0;
$nuevosMes = 0;
$pend = 0;
$prep = 0;
$env = 0;
$ent = 0;
$cancel = 0;
$topProducts = [];
$newClientsW = 0;

if (admin_table_exists($conn, 'admin_pedidos')) {
    $r = $conn->query("SELECT COALESCE(SUM(total),0) AS t FROM admin_pedidos WHERE estado <> 'cancelado'");
    if ($r && $row = $r->fetch_assoc()) {
        $ventasTot = (float) $row['t'];
        $ingresos = $ventasTot;
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

/* Recent orders for table */
$recentOrders = [];
if (admin_table_exists($conn, 'admin_pedidos')) {
    $r = $conn->query("SELECT id, numero_pedido, nombre_contacto, estado, total FROM admin_pedidos ORDER BY creado_en DESC LIMIT 5");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $recentOrders[] = $row;
        }
    }
}

require __DIR__ . '/includes/layout.php';
?>

<?php if (!admin_table_exists($conn, 'admin_pedidos')): ?>
    <div class="alert err">Las tablas del panel no están instaladas. Ejecuta el script <code>db/Ofi_com.sql</code> en MySQL.
    </div>
<?php endif; ?>

<!-- Dashboard Header -->
<div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div class="page-head" style="margin-bottom:0;">
        <h1>Panel de Control</h1>
        <p>Bienvenido de vuelta, <strong><?= admin_h($_SESSION['admin_nombre'] ?? 'Admin') ?></strong> · <?= date('d M Y') ?></p>
    </div>
    <?php if (admin_can('ventas')): ?>
    <a class="btn btn-primary" href="ventas_nuevo.php">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo Pedido
    </a>
    <?php endif; ?>
</div>

<!-- KPI Cards -->
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px;">
    <!-- Ventas totales -->
    <div style="background:linear-gradient(135deg,#1e3a8a,#2563eb); border-radius:16px; padding:22px; color:white; box-shadow:0 8px 24px rgba(37,99,235,.3);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;">
            <div style="background:rgba(255,255,255,0.18);border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                <svg width="20" height="20" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 100 7h5a3.5 3.5 0 110 7H6"/></svg>
            </div>
            <span style="font-size:10px;font-weight:700;letter-spacing:.08em;background:rgba(255,255,255,0.15);padding:3px 8px;border-radius:6px;text-transform:uppercase;">Total</span>
        </div>
        <div style="font-size:11px;font-weight:600;opacity:.7;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Ingresos</div>
        <div style="font-family:'Manrope',sans-serif;font-size:1.75rem;font-weight:800;line-height:1.1;">$<?= number_format($ventasTot, 2) ?></div>
        <div style="font-size:11px;opacity:.6;margin-top:6px;">Sin pedidos cancelados</div>
    </div>
    <!-- Pedidos hoy -->
    <div class="kpi-box">
        <div class="kpi-header">
            <div class="kpi-icon" style="background:rgba(249,115,22,.1);color:#ea580c;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
            </div>
            <span class="kpi-badge kpi-badge-live">Hoy</span>
        </div>
        <div class="lbl">Pedidos de Hoy</div>
        <div class="val"><?= $pedHoy ?></div>
        <div class="sub">Semana: <?= $pedSemana ?> · Mes: <?= $pedMes ?></div>
    </div>
    <!-- Pendientes -->
    <div class="kpi-box">
        <div class="kpi-header">
            <div class="kpi-icon" style="background:rgba(245,158,11,.1);color:#d97706;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </div>
            <?php if ($pend > 0): ?>
            <span style="background:#fef3c7;color:#b45309;font-size:10px;font-weight:700;padding:3px 8px;border-radius:6px;">Atención</span>
            <?php endif; ?>
        </div>
        <div class="lbl">Pendientes</div>
        <div class="val" style="color:<?= $pend > 0 ? '#b45309' : 'var(--text)' ?>;"><?= $pend ?></div>
        <div class="sub">En preparación: <?= $prep ?></div>
    </div>
    <!-- Clientes -->
    <div class="kpi-box">
        <div class="kpi-header">
            <div class="kpi-icon" style="background:rgba(34,197,94,.1);color:#16a34a;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
            </div>
            <span class="kpi-badge" style="background:rgba(34,197,94,.1);color:#16a34a;">Este mes</span>
        </div>
        <div class="lbl">Clientes Nuevos</div>
        <div class="val"><?= $nuevosMes ?></div>
        <div class="sub">Última semana: <?= $newClientsW ?></div>
    </div>
</div>

<!-- Pipeline de estados -->
<?php $totalPedidos = $pend + $prep + $env + $ent + $cancel; ?>
<?php if ($totalPedidos > 0): ?>
<div class="card" style="margin-bottom:24px;">
    <div class="section-header" style="margin-bottom:18px;">
        <h2>Pipeline de pedidos</h2>
        <span class="muted"><?= $totalPedidos ?> pedidos en total</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;">
        <?php
        $pipeline = [
            ['label'=>'Pendientes',      'val'=>$pend,   'color'=>'#f59e0b','bg'=>'rgba(245,158,11,.1)', 'icon'=>'⏳'],
            ['label'=>'En preparación',  'val'=>$prep,   'color'=>'#f97316','bg'=>'rgba(249,115,22,.1)', 'icon'=>'📦'],
            ['label'=>'Enviados',        'val'=>$env,    'color'=>'#8b5cf6','bg'=>'rgba(139,92,246,.1)', 'icon'=>'🚚'],
            ['label'=>'Entregados',      'val'=>$ent,    'color'=>'#22c55e','bg'=>'rgba(34,197,94,.1)',  'icon'=>'✅'],
            ['label'=>'Cancelados',      'val'=>$cancel, 'color'=>'#ef4444','bg'=>'rgba(239,68,68,.1)',  'icon'=>'❌'],
        ];
        foreach ($pipeline as $p):
            $pct = $totalPedidos > 0 ? round(($p['val'] / $totalPedidos) * 100) : 0;
        ?>
        <div style="text-align:center;">
            <div style="font-size:22px;margin-bottom:8px;"><?= $p['icon'] ?></div>
            <div style="font-family:'Manrope',sans-serif;font-size:1.6rem;font-weight:800;color:<?= $p['color'] ?>;"><?= $p['val'] ?></div>
            <div style="font-size:11px;color:var(--muted);margin:4px 0 8px;font-weight:500;"><?= $p['label'] ?></div>
            <div style="height:6px;background:var(--neutral);border-radius:99px;overflow:hidden;">
                <div style="height:100%;width:<?= $pct ?>%;background:<?= $p['color'] ?>;border-radius:99px;transition:width .6s;"></div>
            </div>
            <div style="font-size:10px;color:var(--muted);margin-top:4px;"><?= $pct ?>%</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Content Grid: Orders + Products -->
<div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:20px; align-items:start; margin-bottom:24px;">
    <!-- Orders by Status -->
    <div class="card" style="margin-bottom:0;">
        <div class="section-header">
            <h2>Pedidos por estado</h2>
            <?php if (admin_can('ventas')): ?>
                <a href="ventas.php">Ver todos los pedidos →</a>
            <?php endif; ?>
        </div>
        <table class="data">
            <thead>
                <tr>
                    <th>N° Pedido</th>
                    <th>Cliente</th>
                    <th>Estado</th>
                    <th>Monto</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:30px;" class="muted">
                            <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24"
                                style="opacity:.3; margin-bottom:8px; display:block; margin-left:auto; margin-right:auto;">
                                <rect x="2" y="3" width="20" height="18" rx="2" />
                                <path d="M8 7h8M8 11h5" />
                            </svg>
                            No se encontraron pedidos activos para hoy
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td style="font-weight:600;"><?= admin_h($o['numero_pedido']) ?></td>
                            <td><?= admin_h($o['nombre_contacto']) ?></td>
                            <td><?= admin_estado_badge($o['estado']) ?></td>
                            <td>$<?= number_format((float) $o['total'], 2) ?></td>
                            <td><a class="btn btn-ghost btn-sm" href="venta.php?id=<?= (int) $o['id'] ?>">Ver</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Most Sold Products -->
    <div class="card" style="margin-bottom:0;">
        <h2>Productos más vendidos</h2>
        <?php if (empty($topProducts)): ?>
            <p class="muted" style="padding:12px 0;">Aún no hay ventas registradas.</p>
        <?php else: ?>
            <?php
            $productIcons = [
                '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4M4 7l8 4M4 7v10l8 4m0-10v10"/></svg>',
                '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a4 4 0 10-8 0v2"/></svg>',
            ];
            foreach ($topProducts as $i => $tp): ?>
                <div class="product-list-item">
                    <div class="product-list-icon"
                        style="<?= $i === 0 ? 'background:rgba(29,61,142,.08);color:var(--primary);' : '' ?>">
                        <?= $productIcons[$i % 2] ?>
                    </div>
                    <div class="product-list-info">
                        <div class="product-list-name"><?= admin_h($tp['nombre_producto']) ?></div>
                        <div class="product-list-sub">Catálogo de productos</div>
                    </div>
                    <div class="product-list-sales"><?= (int) $tp['u'] ?> ventas</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Access -->
<div class="card">
    <h2>Acceso Rápido</h2>
    <div class="quick-grid">
        <?php if (admin_can('inventario')): ?>
            <a class="quick-card quick-card-primary" href="inventario.php">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path
                        d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" />
                    <path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12" />
                </svg>
                Nuevo Stock
            </a>
        <?php endif; ?>
        <?php if (admin_can('clientes')): ?>
            <a class="quick-card quick-card-light" href="clientes.php">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
                    <circle cx="8.5" cy="7" r="4" />
                    <path d="M20 8v6M23 11h-6" />
                </svg>
                Agregar Cliente
            </a>
        <?php endif; ?>
        <?php if (admin_can('reportes')): ?>
            <a class="quick-card quick-card-light" href="reportes.php">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M18 20V10M12 20V4M6 20v-6" />
                </svg>
                Reportes
            </a>
        <?php endif; ?>
        <?php if (admin_can('configuracion')): ?>
            <a class="quick-card quick-card-light" href="configuracion.php">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <path d="M22 6l-10 7L2 6" />
                </svg>
                Soporte
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/layout_end.php'; ?>