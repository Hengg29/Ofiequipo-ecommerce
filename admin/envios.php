<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('envios');

$pageTitle = 'Envíos';
$activeId  = 'envios';
$msg         = '';

if (!admin_table_exists($conn, 'admin_envios') || !admin_table_exists($conn, 'admin_pedidos')) {
    require __DIR__ . '/includes/layout.php';
    echo '<div class="alert err">Instala el esquema admin (<code>db/Ofi_com.sql</code>).</div>';
    require __DIR__ . '/includes/layout_end.php';
    exit;
}

$filter = trim($_GET['f'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid   = (int) ($_POST['pedido_id'] ?? 0);
    $guia  = trim($_POST['guia_rastreo'] ?? '');
    $trans = trim($_POST['transportista'] ?? '');
    $nest  = trim($_POST['estado'] ?? '');
    $ok    = in_array(
        $nest,
        ['pendiente', 'en_preparacion', 'enviado', 'entregado', 'cancelado'],
        true
    );
    if ($pid > 0 && $ok) {
        $chk = $conn->prepare('SELECT id FROM admin_envios WHERE pedido_id = ?');
        $chk->bind_param('i', $pid);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($row) {
            $up = $conn->prepare(
                'UPDATE admin_envios SET estado = ?, guia_rastreo = ?, transportista = ? WHERE pedido_id = ?'
            );
            $up->bind_param('sssi', $nest, $guia, $trans, $pid);
            $up->execute();
            $up->close();
        } else {
            $ins = $conn->prepare(
                'INSERT INTO admin_envios (pedido_id, estado, guia_rastreo, transportista) VALUES (?,?,?,?)'
            );
            $ins->bind_param('isss', $pid, $nest, $guia, $trans);
            $ins->execute();
            $ins->close();
        }
        $upP = $conn->prepare('UPDATE admin_pedidos SET estado = ? WHERE id = ?');
        $upP->bind_param('si', $nest, $pid);
        $upP->execute();
        $upP->close();
        admin_audit($conn, 'actualizar', 'envio', $pid, "guía=$guia");
        $msg = 'Envío actualizado.';
    }
}

$sql = "SELECT e.*, p.numero_pedido, p.nombre_contacto, p.email_contacto, p.total, p.creado_en
        FROM admin_envios e
        INNER JOIN admin_pedidos p ON p.id = e.pedido_id
        WHERE 1=1";
$params = [];
$types  = '';

if ($filter === 'prep') {
    $sql .= ' AND e.estado IN (\'pendiente\',\'en_preparacion\')';
} elseif ($filter === 'enviado') {
    $sql .= ' AND e.estado = \'enviado\'';
} elseif ($filter === 'entregado') {
    $sql .= ' AND e.estado = \'entregado\'';
} elseif ($filter === 'cancelado') {
    $sql .= ' AND e.estado = \'cancelado\'';
}

$sql .= ' ORDER BY e.actualizado_en DESC LIMIT 300';

$res  = $conn->query($sql);
$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

$highlight = isset($_GET['pedido']) ? (int) $_GET['pedido'] : 0;

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Envíos y rastreo</h1>
    <p>Estados: en preparación, enviado, entregado o cancelado. Guía y transportista.</p>
</div>
<?php if ($msg): ?><div class="alert ok"><?= admin_h($msg) ?></div><?php endif; ?>

<div class="filters card" style="padding:16px; margin-bottom:20px;">
    <a class="btn btn-sm <?= $filter === '' ? 'btn-primary' : 'btn-ghost' ?>" href="envios.php">Todos</a>
    <a class="btn btn-sm <?= $filter === 'prep' ? 'btn-primary' : 'btn-ghost' ?>" href="envios.php?f=prep">Preparación</a>
    <a class="btn btn-sm <?= $filter === 'enviado' ? 'btn-primary' : 'btn-ghost' ?>" href="envios.php?f=enviado">Enviados</a>
    <a class="btn btn-sm <?= $filter === 'entregado' ? 'btn-primary' : 'btn-ghost' ?>" href="envios.php?f=entregado">Entregados</a>
    <a class="btn btn-sm <?= $filter === 'cancelado' ? 'btn-primary' : 'btn-ghost' ?>" href="envios.php?f=cancelado">Cancelados</a>
</div>

<div class="card">
    <table class="data">
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Cliente</th>
                <th>Estado envío</th>
                <th>Guía</th>
                <th>Transportista</th>
                <th>Actualización</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr id="row-<?= (int) $r['pedido_id'] ?>" style="<?= $highlight === (int) $r['pedido_id'] ? 'outline:1px solid var(--accent);' : '' ?>">
                    <td><?= admin_h($r['numero_pedido']) ?></td>
                    <td><?= admin_h($r['nombre_contacto']) ?><br><span class="muted"><?= admin_h($r['email_contacto']) ?></span></td>
                    <td><span class="badge <?= admin_h(str_replace(' ', '_', $r['estado'])) ?>"><?= admin_h($r['estado']) ?></span></td>
                    <td><?= admin_h($r['guia_rastreo']) ?></td>
                    <td><?= admin_h($r['transportista']) ?></td>
                    <td class="muted"><?= admin_h($r['actualizado_en']) ?></td>
                    <td>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('form-<?= (int) $r['pedido_id'] ?>').style.display=document.getElementById('form-<?= (int) $r['pedido_id'] ?>').style.display==='block'?'none':'block'">Editar</button>
                    </td>
                </tr>
                <tr>
                    <td colspan="7" style="padding:0; border:none;">
                        <form method="post" id="form-<?= (int) $r['pedido_id'] ?>" style="display:none; padding:16px; background:#243044;">
                            <input type="hidden" name="pedido_id" value="<?= (int) $r['pedido_id'] ?>">
                            <div class="form-row" style="display:inline-block; width:160px;">
                                <label>Estado</label>
                                <select name="estado">
                                    <?php foreach (['pendiente', 'en_preparacion', 'enviado', 'entregado', 'cancelado'] as $es): ?>
                                        <option value="<?= $es ?>" <?= $r['estado'] === $es ? 'selected' : '' ?>><?= $es ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row" style="display:inline-block; width:200px;">
                                <label>Guía / rastreo</label>
                                <input name="guia_rastreo" value="<?= admin_h($r['guia_rastreo']) ?>">
                            </div>
                            <div class="form-row" style="display:inline-block; width:200px;">
                                <label>Transportista</label>
                                <input name="transportista" value="<?= admin_h($r['transportista']) ?>">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="muted">No hay registros de envío. Los pedidos nuevos crean fila de envío automáticamente al crear el pedido.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
