<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('auditoria');

if (($_SESSION['admin_rol_slug'] ?? '') !== 'administrador') {
    http_response_code(403);
    echo 'Solo administradores.';
    exit;
}

$pageTitle = 'Auditoría';
$activeId  = 'auditoria';

$rows = [];
if (admin_table_exists($conn, 'admin_auditoria')) {
    $q = "SELECT a.*, u.email AS usuario_email
          FROM admin_auditoria a
          LEFT JOIN admin_usuarios u ON u.id = a.usuario_id
          ORDER BY a.creado_en DESC LIMIT 400";
    $r = @$conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $rows[] = $row;
        }
    }
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Bitácora / auditoría</h1>
    <p>Acciones recientes en el panel (creación, edición, exportaciones, login).</p>
</div>

<div class="card">
    <table class="data">
        <thead>
            <tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>ID</th><th>Detalle</th><th>IP</th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $a): ?>
                <tr>
                    <td class="muted" style="white-space:nowrap;"><?= admin_h($a['creado_en']) ?></td>
                    <td><?= admin_h($a['usuario_email'] ?? '—') ?></td>
                    <td><?= admin_h($a['accion']) ?></td>
                    <td><?= admin_h($a['entidad']) ?></td>
                    <td><?= $a['entidad_id'] !== null ? (int) $a['entidad_id'] : '—' ?></td>
                    <td><?= admin_h(substr((string) $a['detalle'], 0, 120)) ?></td>
                    <td class="muted"><?= admin_h($a['ip']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="muted">Sin registros o tabla no instalada.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
