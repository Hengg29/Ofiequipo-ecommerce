<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('cotizaciones');

$pageTitle = 'Cotizaciones';
$activeId  = 'cotizaciones';

// ── Filtros ──────────────────────────────────────────────────
$filtroStatus = $_GET['status'] ?? '';
$filtroSearch = trim($_GET['q'] ?? '');
$validStatuses = ['pendiente', 'en_proceso', 'cotizada', 'rechazada'];

$where  = [];
$params = [];
$types  = '';

if ($filtroStatus && in_array($filtroStatus, $validStatuses, true)) {
    $where[]  = 'c.status = ?';
    $params[] = $filtroStatus;
    $types   .= 's';
}
if ($filtroSearch !== '') {
    $where[]  = '(c.folio LIKE ? OR c.nombre LIKE ? OR c.email LIKE ? OR c.empresa LIKE ?)';
    $like      = '%' . $filtroSearch . '%';
    $params[]  = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types    .= 'ssss';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT c.*, COUNT(i.id) AS n_items, SUM(i.cantidad) AS n_unidades
        FROM cotizaciones c
        LEFT JOIN cotizacion_items i ON i.cotizacion_id = c.id
        $whereSQL
        GROUP BY c.id
        ORDER BY c.fecha DESC
        LIMIT 500";

$rows = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $stmt->close();
}

// Conteo por estado para badges del sidebar
$countByStatus = [];
$cr = $conn->query("SELECT status, COUNT(*) AS cnt FROM cotizaciones GROUP BY status");
if ($cr) while ($r = $cr->fetch_assoc()) $countByStatus[$r['status']] = (int)$r['cnt'];
$pendientes = $countByStatus['pendiente'] ?? 0;

// ── Labels y colores de estado ───────────────────────────────
function cot_badge(string $status): string {
    $map = [
        'pendiente'  => ['label' => 'Pendiente',   'class' => 'pendiente'],
        'en_proceso' => ['label' => 'En proceso',  'class' => 'en_proceso'],
        'cotizada'   => ['label' => 'Cotizada',    'class' => 'completado'],
        'rechazada'  => ['label' => 'Rechazada',   'class' => 'cancelado'],
    ];
    $d = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'pendiente'];
    return '<span class="badge ' . $d['class'] . '">' . admin_h($d['label']) . '</span>';
}

require_once __DIR__ . '/includes/layout.php';
?>

<div class="page-head" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1>Cotizaciones</h1>
        <p><?= count($rows) ?> resultado<?= count($rows) !== 1 ? 's' : '' ?>
            <?php if ($filtroSearch): ?> para "<strong><?= admin_h($filtroSearch) ?></strong>"<?php endif; ?>
        </p>
    </div>
    <?php if ($pendientes > 0): ?>
    <div style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.3);border-radius:10px;padding:10px 18px;display:flex;align-items:center;gap:10px;">
        <svg width="18" height="18" fill="none" stroke="#b45309" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span style="font-size:13px;font-weight:600;color:#b45309;"><?= $pendientes ?> cotización<?= $pendientes !== 1 ? 'es' : '' ?> pendiente<?= $pendientes !== 1 ? 's' : '' ?> sin responder</span>
    </div>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card" style="padding:16px 20px;margin-bottom:20px;">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <div>
            <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;">Buscar</label>
            <input type="text" name="q" value="<?= admin_h($filtroSearch) ?>"
                placeholder="Folio, nombre, correo, empresa..."
                style="padding:9px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;color:var(--text);background:var(--surface);min-width:280px;outline:none;"
                onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div>
            <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;">Estado</label>
            <select name="status" style="padding:9px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;color:var(--text);background:var(--surface);outline:none;"
                onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
                <option value="">Todos los estados</option>
                <option value="pendiente"  <?= $filtroStatus === 'pendiente'  ? 'selected' : '' ?>>Pendiente</option>
                <option value="en_proceso" <?= $filtroStatus === 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                <option value="cotizada"   <?= $filtroStatus === 'cotizada'   ? 'selected' : '' ?>>Cotizada</option>
                <option value="rechazada"  <?= $filtroStatus === 'rechazada'  ? 'selected' : '' ?>>Rechazada</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
        <?php if ($filtroSearch || $filtroStatus): ?>
        <a href="cotizaciones.php" class="btn btn-ghost btn-sm">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabs de estado rápido -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
    <?php
    $tabDefs = [
        ''           => 'Todas',
        'pendiente'  => 'Pendientes',
        'en_proceso' => 'En proceso',
        'cotizada'   => 'Cotizadas',
        'rechazada'  => 'Rechazadas',
    ];
    foreach ($tabDefs as $val => $lbl):
        $cnt    = $val === '' ? array_sum($countByStatus) : ($countByStatus[$val] ?? 0);
        $active = $filtroStatus === $val;
    ?>
    <a href="cotizaciones.php<?= $val ? '?status=' . $val : '' ?>"
       style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;border:1px solid <?= $active ? 'var(--primary)' : 'var(--border)' ?>;background:<?= $active ? 'var(--primary)' : 'white' ?>;color:<?= $active ? 'white' : 'var(--text-secondary)' ?>;transition:all .15s;">
        <?= $lbl ?>
        <span style="background:<?= $active ? 'rgba(255,255,255,.25)' : 'var(--neutral)' ?>;color:<?= $active ? 'white' : 'var(--muted)' ?>;font-size:11px;font-weight:700;padding:1px 7px;border-radius:20px;"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Tabla -->
<div class="card" style="padding:0;overflow:hidden;">
    <?php if (empty($rows)): ?>
    <div style="padding:60px 32px;text-align:center;">
        <svg width="48" height="48" fill="none" stroke="#cbd5e1" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px;display:block;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
        <p style="font-size:15px;font-weight:600;color:#475569;margin-bottom:6px;">Sin cotizaciones</p>
        <p style="font-size:13px;color:#94a3b8;">No hay cotizaciones que coincidan con los filtros.</p>
    </div>
    <?php else: ?>
    <table class="data">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Cliente</th>
                <th>Contacto</th>
                <th style="text-align:center;">Productos</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th style="text-align:right;">Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td>
                    <span style="font-family:monospace;font-weight:700;font-size:13px;color:var(--primary);">
                        <?= admin_h($row['folio']) ?>
                    </span>
                </td>
                <td>
                    <div style="font-weight:600;color:var(--text);font-size:13px;"><?= admin_h($row['nombre']) ?></div>
                    <?php if ($row['empresa']): ?>
                    <div style="font-size:11px;color:var(--muted);margin-top:2px;"><?= admin_h($row['empresa']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="font-size:12px;color:var(--text-secondary);"><?= admin_h($row['email']) ?></div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px;"><?= admin_h($row['telefono']) ?></div>
                </td>
                <td style="text-align:center;">
                    <span style="font-weight:700;font-size:14px;color:var(--text);"><?= (int)$row['n_items'] ?></span>
                    <span style="font-size:11px;color:var(--muted);display:block;"><?= (int)$row['n_unidades'] ?> un.</span>
                </td>
                <td><?= cot_badge($row['status']) ?></td>
                <td style="font-size:12px;color:var(--muted);white-space:nowrap;">
                    <?= date('d/m/Y', strtotime($row['fecha'])) ?>
                    <span style="display:block;"><?= date('H:i', strtotime($row['fecha'])) ?></span>
                </td>
                <td style="text-align:right;">
                    <a href="cotizacion_ver.php?id=<?= (int)$row['id'] ?>" class="btn btn-primary btn-sm">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Ver / Responder
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
