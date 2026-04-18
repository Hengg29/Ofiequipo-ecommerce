<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('configuracion');

$pageTitle = 'Configuración';
$activeId  = 'configuracion';
$msg         = '';

if (($_SESSION['admin_rol_slug'] ?? '') !== 'administrador') {
    http_response_code(403);
    echo 'Solo administradores.';
    exit;
}

if (!admin_table_exists($conn, 'admin_config')) {
    require __DIR__ . '/includes/layout.php';
    echo '<div class="alert err">Ejecuta db/Ofi_com.sql.</div>';
    require __DIR__ . '/includes/layout_end.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $conn->prepare(
        'INSERT INTO admin_config (clave, valor, grupo) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor), grupo = VALUES(grupo)'
    );
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'cfg_') !== 0) {
            continue;
        }
        $clave = substr($k, 4);
        $valor = is_string($v) ? trim($v) : '';
        $grupo = 'general';
        if (strpos($clave, 'tienda_') === 0) {
            $grupo = 'tienda';
        }
        if (strpos($clave, 'envio_') === 0) {
            $grupo = 'envio';
        }
        if (strpos($clave, 'impuesto') === 0) {
            $grupo = 'impuestos';
        }
        if (strpos($clave, 'pago_') === 0) {
            $grupo = 'pagos';
        }
        if (strpos($clave, 'notif_') === 0) {
            $grupo = 'notificaciones';
        }
        $st->bind_param('sss', $clave, $valor, $grupo);
        $st->execute();
    }
    $st->close();
    admin_audit($conn, 'config', 'admin_config', null, 'Actualización masiva');
    $msg = 'Configuración guardada.';
}

$rows = [];
$r = $conn->query('SELECT clave, valor, grupo FROM admin_config ORDER BY grupo, clave');
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $rows[] = $row;
    }
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Configuración del sistema</h1>
    <p>Tienda, envío, impuestos, métodos de pago y notificaciones (flags 0/1).</p>
</div>
<?php if ($msg): ?><div class="alert ok"><?= admin_h($msg) ?></div><?php endif; ?>

<form method="post" class="card">
    <table class="data">
        <thead>
            <tr><th>Clave</th><th>Grupo</th><th>Valor</th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= admin_h($row['clave']) ?></td>
                    <td class="muted"><?= admin_h($row['grupo']) ?></td>
                    <td><input name="cfg_<?= admin_h($row['clave']) ?>" value="<?= admin_h($row['valor']) ?>" style="width:100%; max-width:480px;"></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type="submit" class="btn btn-primary" style="margin-top:16px;">Guardar</button>
</form>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
