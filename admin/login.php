<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (!empty($_SESSION['admin_user_id'])) {
    admin_redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

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
                $_SESSION['admin_user_id'] = (int) $u['id'];
                $_SESSION['admin_email'] = $email;
                $_SESSION['admin_nombre'] = $u['nombre'];
                $_SESSION['admin_rol_slug'] = $u['rol_slug'];
                $_SESSION['admin_rol_nombre'] = $u['rol_nombre'];
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', system-ui, sans-serif;
            background: #F5F7FA;
            color: #1A1D26;
        }

        .box {
            width: 100%;
            max-width: 420px;
            padding: 40px;
            background: #FFFFFF;
            border: 1px solid #E3E7EF;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, .06);
        }

        .brand {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            font-size: 13px;
            color: #1D3D8E;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        h1 {
            margin: 0 0 8px;
            font-family: 'Manrope', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1A1D26;
        }

        p.sub {
            margin: 0 0 28px;
            color: #8792AB;
            font-size: 0.9rem;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #4A5068;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #E3E7EF;
            background: #FFFFFF;
            color: #1A1D26;
            margin-bottom: 16px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color .2s, box-shadow .2s;
        }

        input:focus {
            outline: none;
            border-color: #1D3D8E;
            box-shadow: 0 0 0 3px rgba(29, 61, 142, .1);
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #1D3D8E;
            color: white;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            font-family: inherit;
            transition: background .15s, box-shadow .15s;
        }

        button:hover {
            background: #2B4FAF;
            box-shadow: 0 4px 12px rgba(29, 61, 142, .25);
        }

        .err {
            background: rgba(239, 68, 68, .08);
            border: 1px solid rgba(239, 68, 68, .2);
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.85rem;
        }

        .hint {
            margin-top: 20px;
            font-size: 0.75rem;
            color: #8792AB;
        }

        .hint code {
            background: #F0F2F7;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.72rem;
        }
    </style>
</head>

<body>
    <div class="box">
        <h1>Panel Ofiequipo</h1>
        <p class="sub">Inicia sesión con tu cuenta de administrador.</p>
        <?php if ($error !== ''): ?>
            <div class="err"><?= admin_h($error) ?></div><?php endif; ?>
        <form method="post" autocomplete="on">
            <label for="email">Correo</label>
            <input type="email" name="email" id="email" required value="<?= admin_h($_POST['email'] ?? '') ?>">
            <label for="password">Contraseña</label>
            <input type="password" name="password" id="password" required>
            <button type="submit">Entrar</button>
        </form>
        <p class="hint">Primera instalación: ejecuta <code>db/Ofi_com.sql</code>. Usuario por defecto en el script SQL.
        </p>
    </div>
</body>

</html>