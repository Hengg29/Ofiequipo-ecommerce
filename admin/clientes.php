<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('clientes');

$pageTitle = 'Clientes';
$activeId  = 'clientes';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo'])) {
    $nom = mb_substr(trim($_POST['nombre']   ?? ''), 0, 120);
    $ape = mb_substr(trim($_POST['apellido'] ?? ''), 0, 120);
    $em  = mb_substr(trim($_POST['email']    ?? ''), 0, 190);
    $tel = mb_substr(trim($_POST['telefono'] ?? ''), 0, 40);
    if ($nom !== '' && $em !== '') {
        if (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
            $msg = 'El correo electrónico no tiene un formato válido.';
        } else {
            $st = $conn->prepare('INSERT INTO admin_clientes (nombre, apellido, email, telefono) VALUES (?,?,?,?)');
            if ($st) {
                $st->bind_param('ssss', $nom, $ape, $em, $tel);
                if ($st->execute()) {
                    admin_audit($conn, 'crear', 'cliente', (int) $conn->insert_id, $em);
                    $msg = 'Cliente registrado.';
                } else {
                    error_log('[Admin/clientes] execute insert: ' . $st->error);
                    $msg = 'No se pudo registrar el cliente. El correo puede estar duplicado.';
                }
                $st->close();
            } else {
                error_log('[Admin/clientes] prepare insert: ' . $conn->error);
                $msg = 'Error interno al registrar el cliente.';
            }
        }
    } else {
        $msg = 'Nombre y correo son obligatorios.';
    }
}

$rows = [];
if (admin_table_exists($conn, 'admin_clientes')) {
    // Whitelist explícita para ORDER BY
    $order = $_GET['sort'] ?? 'reciente';
    $ob    = $order === 'nombre' ? 'c.nombre ASC' : 'c.creado_en DESC';
    $sql = "SELECT c.*,
            (SELECT COUNT(*) FROM admin_pedidos p WHERE p.cliente_id = c.id) AS n_pedidos,
            (SELECT COALESCE(SUM(p.total),0) FROM admin_pedidos p WHERE p.cliente_id = c.id AND p.estado <> 'cancelado') AS valor
            FROM admin_clientes c
            ORDER BY $ob
            LIMIT 400";
    $r = $conn->query($sql);
    if ($r === false) {
        error_log('[Admin/clientes] query falló: ' . $conn->error);
    } else {
        while ($row = $r->fetch_assoc()) {
            $rows[] = $row;
        }
    }
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Gestión de clientes</h1>
    <p>Contacto, historial de compras y valor acumulado por cliente.</p>
</div>
<?php if ($msg): ?><div class="alert ok"><?= admin_h($msg) ?></div><?php endif; ?>

<div style="display:grid; grid-template-columns: 320px 1fr; gap:24px; align-items:start;">
    <form method="post" class="card">
        <h2>Nuevo cliente</h2>
        <input type="hidden" name="nuevo" value="1">
        <div class="form-row"><label>Nombre</label><input name="nombre" required></div>
        <div class="form-row"><label>Apellido</label><input name="apellido"></div>
        <div class="form-row"><label>Email</label><input type="email" name="email" required></div>
        <div class="form-row"><label>Teléfono</label><input name="telefono"></div>
        <button type="submit" class="btn btn-primary" style="width:100%;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            Guardar
        </button>
    </form>
    <div>
        <div style="margin-bottom:14px; display:flex; gap:8px;">
            <a class="btn btn-sm <?= ($_GET['sort'] ?? '') === 'nombre' ? 'btn-primary' : 'btn-ghost' ?>" href="clientes.php?sort=nombre">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M3 12h12M3 18h6"/></svg>
                A-Z
            </a>
            <a class="btn btn-sm <?= ($_GET['sort'] ?? 'reciente') === 'reciente' ? 'btn-primary' : 'btn-ghost' ?>" href="clientes.php?sort=reciente">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                Más recientes
            </a>
        </div>
        <div class="card">
            <table class="data">
                <thead>
                    <tr><th>Cliente</th><th>Contacto</th><th>Pedidos</th><th>Valor</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $c): ?>
                        <tr>
                            <td style="font-weight:600;"><?= admin_h($c['nombre'] . ' ' . $c['apellido']) ?></td>
                            <td>
                                <?= admin_h($c['email']) ?>
                                <br><span class="muted"><?= admin_h($c['telefono']) ?></span>
                            </td>
                            <td><?= (int) $c['n_pedidos'] ?></td>
                            <td style="font-weight:600;">$<?= number_format((float) $c['valor'], 2) ?></td>
                            <td><a class="btn btn-ghost btn-sm" href="cliente.php?id=<?= (int) $c['id'] ?>">Ver</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="5" class="muted" style="text-align:center; padding:30px;">No hay clientes registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
