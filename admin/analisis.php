<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('analisis');

$pageTitle = 'Análisis de ventas';
$activeId  = 'analisis';

$top = [];
$slow = [];
$cats = [];
$byDay = [];
$monthCompare = [];

if (admin_table_exists($conn, 'admin_detalle_pedido')) {
    $q = "SELECT d.nombre_producto, SUM(d.cantidad) AS u
          FROM admin_detalle_pedido d
          INNER JOIN admin_pedidos p ON p.id = d.pedido_id
          WHERE p.estado <> 'cancelado'
          GROUP BY d.nombre_producto
          ORDER BY u DESC
          LIMIT 8";
    $r = $conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $top[] = $row;
        }
    }

    $q = "SELECT d.nombre_producto, SUM(d.cantidad) AS u
          FROM admin_detalle_pedido d
          INNER JOIN admin_pedidos p ON p.id = d.pedido_id
          INNER JOIN producto pr ON pr.id = d.producto_id
          WHERE p.estado <> 'cancelado' AND d.producto_id IS NOT NULL
          GROUP BY d.producto_id, d.nombre_producto
          HAVING u <= 3
          ORDER BY u ASC
          LIMIT 8";
    $r = @$conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $slow[] = $row;
        }
    }

    $q = "SELECT c.nombre AS cat, SUM(d.cantidad) AS u
          FROM admin_detalle_pedido d
          INNER JOIN admin_pedidos p ON p.id = d.pedido_id
          LEFT JOIN producto pr ON pr.id = d.producto_id
          LEFT JOIN categoria c ON c.id = pr.categoria_id
          WHERE p.estado <> 'cancelado'
          GROUP BY c.nombre
          ORDER BY u DESC
          LIMIT 10";
    $r = @$conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $cats[] = $row;
        }
    }

    $q = "SELECT DAYOFWEEK(p.creado_en) AS dw, COUNT(*) AS c
          FROM admin_pedidos p WHERE p.estado <> 'cancelado' AND p.creado_en >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
          GROUP BY dw ORDER BY dw";
    $r = $conn->query($q);
    $labelsDow = ['', 'Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $byDay[] = ['label' => $labelsDow[(int) $row['dw']] ?? $row['dw'], 'c' => (int) $row['c']];
        }
    }

    $cur = (float) ($conn->query("SELECT COALESCE(SUM(total),0) FROM admin_pedidos WHERE estado<>'cancelado' AND YEAR(creado_en)=YEAR(CURDATE()) AND MONTH(creado_en)=MONTH(CURDATE())")->fetch_row()[0] ?? 0);
    $prev = (float) ($conn->query(
        "SELECT COALESCE(SUM(total),0) FROM admin_pedidos WHERE estado<>'cancelado'
         AND YEAR(creado_en)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
         AND MONTH(creado_en)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))"
    )->fetch_row()[0] ?? 0);
    $monthCompare = ['actual' => $cur, 'anterior' => $prev];
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Análisis de ventas</h1>
    <p>Popularidad, categorías, día de la semana y comparativa mensual.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); align-items:start; gap:20px;">
    <div class="card">
        <h2>Productos más populares</h2>
        <?php if (empty($top)): ?>
            <p class="muted">Sin datos de ventas aún.</p>
        <?php else: ?>
            <table class="data">
                <?php foreach ($top as $t): ?>
                    <tr><td><?= admin_h($t['nombre_producto']) ?></td><td><?= (int) $t['u'] ?> u.</td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2>Menor rotación (muestra)</h2>
        <p class="muted" style="font-size:0.8rem;">Productos con pocas unidades vendidas en el histórico (HAVING u ≤ 3).</p>
        <?php if (empty($slow)): ?>
            <p class="muted">Sin datos o todos con buena rotación.</p>
        <?php else: ?>
            <table class="data">
                <?php foreach ($slow as $t): ?>
                    <tr><td><?= admin_h($t['nombre_producto']) ?></td><td><?= (int) $t['u'] ?> u.</td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>Categorías más vendidas (unidades)</h2>
    <?php if (empty($cats)): ?>
        <p class="muted">Sin datos vinculados a categoría.</p>
    <?php else: ?>
        <table class="data">
            <?php foreach ($cats as $c): ?>
                <tr><td><?= admin_h($c['cat'] ?? 'Sin categoría') ?></td><td><?= (int) $c['u'] ?> u.</td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
    <div class="card">
        <h2>Pedidos por día de la semana (90 días)</h2>
        <div class="chart-wrap">
            <canvas id="chartDow"></canvas>
        </div>
    </div>
    <div class="card">
        <h2>Comparativa mensual (bruto)</h2>
        <p style="font-size:1.5rem; font-weight:700;">Mes actual: $<?= number_format($monthCompare['actual'], 2) ?></p>
        <p class="muted">Mes anterior: $<?= number_format($monthCompare['anterior'], 2) ?></p>
        <?php
        $prev = $monthCompare['anterior'];
        $chg = $prev > 0 ? (($monthCompare['actual'] - $prev) / $prev) * 100 : 0;
        ?>
        <p style="color: var(--accent2);">Variación vs mes anterior: <?= number_format($chg, 1) ?> %</p>
    </div>
</div>

<script>
const byDay = <?= json_encode($byDay, JSON_UNESCAPED_UNICODE) ?>;
const labels = byDay.map(x => x.label);
const data = byDay.map(x => x.c);
const ctx = document.getElementById('chartDow');
if (ctx && labels.length) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{ label: 'Pedidos', data, backgroundColor: 'rgba(59,130,246,0.6)' }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#2d3a4f' } },
                x: { grid: { display: false } }
            }
        }
    });
}
</script>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
