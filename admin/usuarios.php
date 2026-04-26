<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('usuarios');

if (($_SESSION['admin_rol_slug'] ?? '') !== 'administrador') {
    http_response_code(403);
    echo 'Solo administradores.';
    exit;
}

$pageTitle = 'Usuarios del panel';
$activeId  = 'usuarios';
$msg       = '';
$error     = '';

if (!admin_table_exists($conn, 'admin_usuarios')) {
    require __DIR__ . '/includes/layout.php';
    echo '<div class="alert err">Tablas admin no instaladas.</div>';
    require __DIR__ . '/includes/layout_end.php';
    exit;
}

$roles = [];
$r = $conn->query('SELECT id, nombre, slug FROM admin_roles ORDER BY id');
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $roles[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = $_POST['accion'] ?? '';
    if ($act === 'crear') {
        $email = trim($_POST['email'] ?? '');
        $nom   = trim($_POST['nombre'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $rid   = (int) ($_POST['rol_id'] ?? 0);
        if ($email === '' || $nom === '' || strlen($pass) < 6) {
            $error = 'Email, nombre y contraseña (mín. 6 caracteres).';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $st = $conn->prepare('INSERT INTO admin_usuarios (email, password_hash, nombre, rol_id) VALUES (?,?,?,?)');
            $st->bind_param('sssi', $email, $hash, $nom, $rid);
            if ($st->execute()) {
                admin_audit($conn, 'crear', 'admin_usuario', (int) $conn->insert_id, $email);
                $msg = 'Usuario creado.';
            } else {
                $error = 'No se pudo crear (email duplicado?).';
            }
            $st->close();
        }
    }
    if ($act === 'editar') {
        $uid  = (int) ($_POST['user_id'] ?? 0);
        $nom  = trim($_POST['nombre'] ?? '');
        $rid  = (int) ($_POST['rol_id'] ?? 0);
        $actv = isset($_POST['activo']) ? 1 : 0;
        $st = $conn->prepare('UPDATE admin_usuarios SET nombre = ?, rol_id = ?, activo = ? WHERE id = ?');
        $st->bind_param('siii', $nom, $rid, $actv, $uid);
        $st->execute();
        $st->close();
        if (!empty($_POST['password'])) {
            $pw = $_POST['password'];
            if (strlen($pw) >= 6) {
                $h = password_hash($pw, PASSWORD_DEFAULT);
                $u = $conn->prepare('UPDATE admin_usuarios SET password_hash = ? WHERE id = ?');
                $u->bind_param('si', $h, $uid);
                $u->execute();
                $u->close();
            }
        }
        admin_audit($conn, 'editar', 'admin_usuario', $uid, $nom);
        $msg = 'Usuario actualizado.';
    }
    if ($act === 'eliminar') {
        $uid = (int) ($_POST['user_id'] ?? 0);
        if ($uid > 0 && $uid !== (int) ($_SESSION['admin_user_id'] ?? 0)) {
            $st = $conn->prepare('DELETE FROM admin_usuarios WHERE id = ?');
            $st->bind_param('i', $uid);
            $st->execute();
            $st->close();
            admin_audit($conn, 'eliminar', 'admin_usuario', $uid, '');
            $msg = 'Usuario eliminado.';
        } else {
            $error = 'No puedes eliminarte a ti mismo.';
        }
    }
}

$users = [];
$r = $conn->query(
    'SELECT u.*, r.nombre AS rol_nombre, r.slug AS rol_slug FROM admin_usuarios u JOIN admin_roles r ON r.id = u.rol_id ORDER BY u.id'
);
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $users[] = $row;
    }
}

require __DIR__ . '/includes/layout.php';
?>
<div class="page-head">
    <h1>Usuarios y roles</h1>
    <p>Administrador (acceso total), vendedor (ventas, clientes, análisis, promociones) y almacén (envíos, inventario, productos).</p>
</div>
<?php if ($msg): ?><div class="alert ok"><?= admin_h($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert err"><?= admin_h($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Nuevo usuario</h2>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="crear">
        <div class="form-row"><label>Email</label><input type="email" name="email" required></div>
        <div class="form-row"><label>Nombre</label><input name="nombre" required></div>
        <div class="form-row"><label>Contraseña</label><input type="password" name="password" required minlength="6"></div>
        <div class="form-row"><label>Rol</label>
            <select name="rol_id">
                <?php foreach ($roles as $ro): ?>
                    <option value="<?= (int) $ro['id'] ?>"><?= admin_h($ro['nombre']) ?> (<?= admin_h($ro['slug']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Crear</button>
    </form>
</div>

<div class="card">
    <h2>Listado</h2>
    <table class="data">
        <thead>
            <tr><th>ID</th><th>Email</th><th>Editar</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= admin_h($u['email']) ?></td>
                    <td colspan="2" style="vertical-align:top;">
                        <form method="post" style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="editar">
                            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                            <div class="form-row" style="margin:0;">
                                <label>Nombre</label>
                                <input type="text" name="nombre" value="<?= admin_h($u['nombre']) ?>" style="width:140px;">
                            </div>
                            <div class="form-row" style="margin:0;">
                                <label>Rol</label>
                                <select name="rol_id">
                                    <?php foreach ($roles as $ro): ?>
                                        <option value="<?= (int) $ro['id'] ?>" <?= (int) $u['rol_id'] === (int) $ro['id'] ? 'selected' : '' ?>><?= admin_h($ro['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row" style="margin:0;">
                                <label><input type="checkbox" name="activo" value="1" <?= (int) $u['activo'] ? 'checked' : '' ?>> Activo</label>
                            </div>
                            <div class="form-row" style="margin:0;">
                                <label>Nueva clave</label>
                                <input type="password" name="password" placeholder="opcional" style="width:130px;" autocomplete="new-password">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                        </form>
                        <?php if ((int) $u['id'] !== (int) ($_SESSION['admin_user_id'] ?? 0)): ?>
                            <form method="post" style="margin-top:8px;" onsubmit="return confirm('¿Eliminar usuario?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
