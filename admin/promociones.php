<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('promociones');

$pageTitle = 'Promociones y cupones';
$activeId  = 'promociones';
$msg         = '';

if (!admin_table_exists($conn, 'admin_cupones')) {
    require __DIR__ . '/includes/layout.php';
    echo '<div class="alert err">Ejecuta db/Ofi_com.sql.</div>';
    require __DIR__ . '/includes/layout_end.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nuevo_cupon'])) {
        $cod = strtoupper(trim($_POST['codigo'] ?? ''));
        $pct = $_POST['descuento_pct'] !== '' ? (float) $_POST['descuento_pct'] : 0.0;
        $mnt = $_POST['descuento_monto'] !== '' ? (float) $_POST['descuento_monto'] : 0.0;
        $fi  = trim($_POST['fecha_inicio'] ?? '') ?: date('Y-m-d');
        $ff  = trim($_POST['fecha_fin'] ?? '') ?: date('Y-m-d', strtotime('+1 year'));
        if ($cod !== '') {
            $st = $conn->prepare(
                'INSERT INTO admin_cupones (codigo, descuento_pct, descuento_monto, fecha_inicio, fecha_fin) VALUES (?,?,?,?,?)'
            );
            $st->bind_param('sddss', $cod, $pct, $mnt, $fi, $ff);
            $st->execute();
            $st->close();
            admin_audit($conn, 'crear', 'cupon', (int) $conn->insert_id, $cod);
            $msg = 'Cupón creado.';
        }
    }
    if (isset($_POST['nueva_promo'])) {
        $nom = trim($_POST['nombre'] ?? '');
        $alc = trim($_POST['alcance'] ?? 'global');
        $pid = (int) ($_POST['producto_id'] ?? 0);
        $cid = (int) ($_POST['categoria_id'] ?? 0);
        $pct = $_POST['descuento_pct'] !== '' ? (float) $_POST['descuento_pct'] : null;
        $mnt = $_POST['descuento_monto'] !== '' ? (float) $_POST['descuento_monto'] : null;
        $fi  = trim($_POST['fecha_inicio'] ?? '') ?: date('Y-m-d');
        $ff  = trim($_POST['fecha_fin'] ?? '') ?: date('Y-m-d', strtotime('+1 year'));
        if ($nom !== '' && in_array($alc, ['global', 'producto', 'categoria'], true)) {
            $pct = $pct ?? 0;
            $mnt = $mnt ?? 0;
            $st = $conn->prepare(
                'INSERT INTO admin_promociones (nombre, alcance, producto_id, categoria_id, descuento_pct, descuento_monto, fecha_inicio, fecha_fin)
                 VALUES (?,?,NULLIF(?,0),NULLIF(?,0),?,?,?,?)'
            );
            $st->bind_param('ssiiddss', $nom, $alc, $pid, $cid, $pct, $mnt, $fi, $ff);
            if ($st->execute()) {
                admin_audit($conn, 'crear', 'promocion', (int) $conn->insert_id, $nom);
                $msg = 'Promoción creada.';
            }
            $st->close();
        }
    }
}

$cupones = [];
$r = $conn->query('SELECT * FROM admin_cupones ORDER BY id DESC LIMIT 80');
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $cupones[] = $row;
    }
}
$promos = [];
$r = $conn->query('SELECT * FROM admin_promociones ORDER BY id DESC LIMIT 80');
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $promos[] = $row;
    }
}

$prods = [];
$r = @$conn->query('SELECT id, nombre FROM producto ORDER BY nombre LIMIT 400');
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $prods[] = $row;
    }
}
$cats = [];
$r = @$conn->query('SELECT id, nombre FROM categoria ORDER BY nombre LIMIT 400');
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $cats[] = $row;
    }
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Promociones y cupones</h1>
    <p>Descuentos por código, por producto / categoría o globales con fechas.</p>
</div>
<?php if ($msg): ?><div class="alert ok"><?= admin_h($msg) ?></div><?php endif; ?>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap:20px; align-items:start;">
    <form method="post" class="card">
        <h2>Nuevo cupón</h2>
        <input type="hidden" name="nuevo_cupon" value="1">
        <div class="form-row"><label>Código</label><input name="codigo" required placeholder="VERANO2026"></div>
        <div class="form-row"><label>% descuento</label><input type="number" step="0.01" name="descuento_pct" placeholder="opcional"></div>
        <div class="form-row"><label>Monto fijo</label><input type="number" step="0.01" name="descuento_monto" placeholder="opcional"></div>
        <div class="form-row"><label>Inicio</label><input type="date" name="fecha_inicio"></div>
        <div class="form-row"><label>Fin</label><input type="date" name="fecha_fin"></div>
        <button type="submit" class="btn btn-primary">Crear cupón</button>
    </form>
    <form method="post" class="card">
        <h2>Nueva promoción temporal</h2>
        <input type="hidden" name="nueva_promo" value="1">
        <div class="form-row"><label>Nombre interno</label><input name="nombre" required></div>
        <div class="form-row"><label>Alcance</label>
            <select name="alcance">
                <option value="global">Global</option>
                <option value="producto">Producto</option>
                <option value="categoria">Categoría</option>
            </select>
        </div>
        <div class="form-row"><label>Producto (si aplica)</label>
            <select name="producto_id"><option value="0">—</option><?php foreach ($prods as $p): ?><option value="<?= (int)$p['id'] ?>"><?= admin_h($p['nombre']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="form-row"><label>Categoría (si aplica)</label>
            <select name="categoria_id"><option value="0">—</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= admin_h($c['nombre']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="form-row"><label>% descuento</label><input type="number" step="0.01" name="descuento_pct"></div>
        <div class="form-row"><label>Monto</label><input type="number" step="0.01" name="descuento_monto"></div>
        <div class="form-row"><label>Inicio</label><input type="date" name="fecha_inicio"></div>
        <div class="form-row"><label>Fin</label><input type="date" name="fecha_fin"></div>
        <button type="submit" class="btn btn-primary">Crear promoción</button>
    </form>
</div>

<div class="card">
    <h2>Cupones</h2>
    <table class="data">
        <tr><th>Código</th><th>%</th><th>Monto</th><th>Vigencia</th><th>Usos</th><th>Activo</th></tr>
        <?php foreach ($cupones as $c): ?>
            <tr>
                <td><?= admin_h($c['codigo']) ?></td>
                <td><?= admin_h((string) $c['descuento_pct']) ?></td>
                <td><?= admin_h((string) $c['descuento_monto']) ?></td>
                <td class="muted"><?= admin_h($c['fecha_inicio'] ?? '') ?> — <?= admin_h($c['fecha_fin'] ?? '') ?></td>
                <td><?= (int) $c['usos_actuales'] ?> / <?= $c['max_usos'] !== null ? (int) $c['max_usos'] : '∞' ?></td>
                <td><?= (int) $c['activo'] ? 'Sí' : 'No' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<div class="card">
    <h2>Promociones</h2>
    <table class="data">
        <tr><th>Nombre</th><th>Alcance</th><th>% / monto</th><th>Vigencia</th></tr>
        <?php foreach ($promos as $p): ?>
            <tr>
                <td><?= admin_h($p['nombre']) ?></td>
                <td><?= admin_h($p['alcance']) ?></td>
                <td><?= admin_h((string) $p['descuento_pct']) ?> / <?= admin_h((string) $p['descuento_monto']) ?></td>
                <td class="muted"><?= admin_h($p['fecha_inicio'] ?? '') ?> — <?= admin_h($p['fecha_fin'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
