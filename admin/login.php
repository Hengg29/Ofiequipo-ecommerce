<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (!empty($_SESSION['admin_user_id'])) {
    admin_redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $error = 'Introduce correo y contraseña.';
    } else {
        $stmt = $conn->prepare(
            'SELECT u.id, u.password_hash, u.nombre, u.activo, r.slug AS rol_slug, r.nombre AS rol_nombre
             FROM admin_usuarios u
             INNER JOIN admin_roles r ON r.id = u.rol_id
             WHERE u.email = ? LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $u = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$u || !(int) $u['activo']) {
                $error = 'Credenciales incorrectas o cuenta desactivada.';
            } elseif (!password_verify($pass, $u['password_hash'])) {
                $error = 'Credenciales incorrectas o cuenta desactivada.';
            } else {
                $_SESSION['admin_user_id']    = (int) $u['id'];
                $_SESSION['admin_email']       = $email;
                $_SESSION['admin_nombre']      = $u['nombre'];
                $_SESSION['admin_rol_slug']    = $u['rol_slug'];
                $_SESSION['admin_rol_nombre']   = $u['rol_nombre'];
                admin_audit($conn, 'login', 'sesion', (int) $u['id'], 'Inicio de sesión panel');
                admin_redirect('index.php');
            }
        } else {
            $error = 'No se encontraron las tablas del panel. Ejecuta db/Ofi_com.sql en MySQL.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso administrador</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'DM Sans', system-ui, sans-serif; background: linear-gradient(160deg, #0f1419 0%, #1a2332 50%, #0f172a 100%); color: #e8edf4; }
        .box { width: 100%; max-width: 400px; padding: 32px; background: #1a2332; border: 1px solid #2d3a4f; border-radius: 16px; }
        h1 { margin: 0 0 8px; font-size: 1.35rem; }
        p.sub { margin: 0 0 24px; color: #8b9cb3; font-size: 0.9rem; }
        label { display: block; font-size: 0.8rem; color: #8b9cb3; margin-bottom: 6px; }
        input { width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #2d3a4f; background: #0f1419; color: #e8edf4; margin-bottom: 16px; }
        button { width: 100%; padding: 12px; border: none; border-radius: 8px; background: #3b82f6; color: white; font-weight: 600; cursor: pointer; font-size: 1rem; }
        button:hover { filter: brightness(1.05); }
        .err { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.35); color: #fca5a5; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
        .hint { margin-top: 20px; font-size: 0.75rem; color: #64748b; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Panel Ofiequipo</h1>
        <p class="sub">Inicia sesión con tu cuenta de administrador.</p>
        <?php if ($error !== ''): ?><div class="err"><?= admin_h($error) ?></div><?php endif; ?>
        <form method="post" autocomplete="on">
            <label for="email">Correo</label>
            <input type="email" name="email" id="email" required value="<?= admin_h($_POST['email'] ?? '') ?>">
            <label for="password">Contraseña</label>
            <input type="password" name="password" id="password" required>
            <button type="submit">Entrar</button>
        </form>
        <p class="hint">Primera instalación: ejecuta <code>db/Ofi_com.sql</code>. Usuario por defecto en el script SQL.</p>
    </div>
</body>
</html>
