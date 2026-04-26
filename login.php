<?php
require_once __DIR__ . '/includes/security.php';
security_session_configure();
session_start();
security_headers();
require_once __DIR__ . '/apis/db.php';

$error = '';
$success = '';

$redirectAfterLogin = trim($_GET['redirect'] ?? '');
if (!empty($_SESSION['user_id'])) {
    safe_redirect($redirectAfterLogin ?: 'index.php');
}

if (isset($_GET['registro']) && $_GET['registro'] === '1') {
    $success = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!rate_limit_ok($conn, 'login_publico')) {
        $error = 'Demasiados intentos fallidos. Espera 5 minutos antes de intentarlo de nuevo.';
    } else {

    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    $emailInput = trim($email);
    $emailNorm  = strtolower($emailInput);

    if (empty($emailInput) || empty($password)) {
        rate_limit_record($conn, 'login_publico', false);
        $error = 'Por favor completa todos los campos.';
    } else {
        // 1) Intentar login de panel admin (admin_usuarios)
        $stmtAdmin = $conn->prepare(
            "SELECT u.id, u.email, u.password_hash, u.nombre, u.activo, r.slug AS rol_slug, r.nombre AS rol_nombre
             FROM admin_usuarios u
             INNER JOIN admin_roles r ON r.id = u.rol_id
             WHERE u.email = ?
             LIMIT 1"
        );

        $authenticated = false;

        if ($stmtAdmin) {
            $stmtAdmin->bind_param("s", $emailNorm);
            $stmtAdmin->execute();
            $adminUser = $stmtAdmin->get_result()->fetch_assoc();
            $stmtAdmin->close();

            if ($adminUser && (int) $adminUser['activo'] === 1 && password_verify($password, $adminUser['password_hash'])) {
                rate_limit_clear($conn, 'login_publico');
                session_regenerate_id(true);
                $_SESSION['admin_user_id']   = (int) $adminUser['id'];
                $_SESSION['admin_email']     = $adminUser['email'];
                $_SESSION['admin_nombre']    = $adminUser['nombre'];
                $_SESSION['admin_rol_slug']  = $adminUser['rol_slug'];
                $_SESSION['admin_rol_nombre'] = $adminUser['rol_nombre'];
                $dest = $adminUser['rol_slug'] === 'repartidor'
                    ? 'admin/repartidor.php'
                    : 'admin/index.php';
                header('Location: ' . $dest);
                exit;
            }
        }

        // 2) Intentar login de usuario tienda (usuarios)
        $stmtUser = $conn->prepare(
            "SELECT u.id, u.email, u.nombre, u.contrasena_hash, u.email_verificado, r.nombre AS rol_nombre
             FROM usuarios u
             LEFT JOIN roles r ON r.id = u.rol_id
             WHERE u.email = ?
             LIMIT 1"
        );

        if ($stmtUser) {
            $stmtUser->bind_param("s", $emailNorm);
            $stmtUser->execute();
            $user = $stmtUser->get_result()->fetch_assoc();
            $stmtUser->close();

            if ($user) {
                if (!(int)$user['email_verificado']) {
                    $error = '__no_verificado__:' . $emailNorm;
                } elseif (password_verify($password, $user['contrasena_hash'])) {
                    rate_limit_clear($conn, 'login_publico');
                    session_regenerate_id(true);
                    $_SESSION['user_id']     = (int) $user['id'];
                    $_SESSION['user_email']  = $emailNorm;
                    $_SESSION['user_role']   = $user['rol_nombre'] ?? 'cliente';
                    $_SESSION['user_nombre'] = $user['nombre'] ?: explode('@', $emailNorm)[0];
                    $authenticated = true;
                }
            }
        }

        if ($authenticated) {
            $dest = trim($_POST['redirect'] ?? $_GET['redirect'] ?? '');
            safe_redirect($dest ?: 'index.php');
        }

        if (!$error) {
            rate_limit_record($conn, 'login_publico', false);
            $error = 'Correo o contraseña incorrectos.';
        }
    } // end else (empty check)
    } // end else (rate_limit_ok)
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Ofiequipo de Tampico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue-mid:  #1e3a8a;
            --blue:      #2563eb;
            --text-dark: #0f172a;
            --text-gray: #475569;
            --text-light:#94a3b8;
            --border:    #e2e8f0;
            --bg-light:  #f8fafc;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body { display: flex; }

        /* ── LEFT PANEL ─────────────────────────── */
        .panel-brand {
            width: 44%;
            min-height: 100vh;
            background: linear-gradient(155deg, #0f172a 0%, #1e3a8a 60%, #1d4ed8 100%);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }

        /* ── Animated circles ─────────────────────── */
        @keyframes drift1 {
            0%   { transform: translate(0,0) scale(1); }
            25%  { transform: translate(22px,-28px) scale(1.04); }
            50%  { transform: translate(-12px,18px) scale(0.96); }
            75%  { transform: translate(16px,6px) scale(1.02); }
            100% { transform: translate(0,0) scale(1); }
        }
        @keyframes drift2 {
            0%   { transform: translate(0,0) scale(1); }
            30%  { transform: translate(-20px,24px) scale(1.06); }
            65%  { transform: translate(14px,-12px) scale(0.95); }
            100% { transform: translate(0,0) scale(1); }
        }
        @keyframes drift3 {
            0%   { transform: translate(0,0) scale(1); }
            40%  { transform: translate(28px,-18px) scale(1.1); }
            80%  { transform: translate(-8px,22px) scale(0.92); }
            100% { transform: translate(0,0) scale(1); }
        }
        @keyframes drift4 {
            0%   { transform: translate(0,0) scale(1); }
            50%  { transform: translate(-24px,-16px) scale(1.08); }
            100% { transform: translate(0,0) scale(1); }
        }

        .circle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .circle-1 {
            width: 520px; height: 520px;
            background: rgba(255,255,255,0.05);
            top: -160px; right: -180px;
            animation: drift1 20s ease-in-out infinite;
        }
        .circle-2 {
            width: 320px; height: 320px;
            background: rgba(255,255,255,0.05);
            bottom: -100px; left: -80px;
            animation: drift2 15s ease-in-out infinite;
        }
        .circle-3 {
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.04);
            top: 42%; left: 18%;
            animation: drift3 11s ease-in-out infinite;
        }
        .circle-4 {
            width: 110px; height: 110px;
            background: rgba(255,255,255,0.07);
            top: 12%; left: 8%;
            animation: drift4 8s ease-in-out infinite;
        }
        .circle-5 {
            width: 64px; height: 64px;
            background: rgba(37,99,235,0.35);
            bottom: 28%; right: 14%;
            animation: drift1 7s ease-in-out infinite reverse;
        }
        .circle-6 {
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.12);
            top: 30%; right: 12%;
            animation: drift2 5s ease-in-out infinite reverse;
        }

        .brand-back {
            position: absolute;
            top: 32px; left: 48px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.2s;
            z-index: 2;
        }
        .brand-back:hover { color: rgba(255,255,255,0.9); }
        .brand-back svg { width: 16px; height: 16px; }

        .brand-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 100px 52px 64px;
            position: relative;
            z-index: 1;
        }

        .brand-icon {
            width: 54px; height: 54px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
        }
        .brand-icon svg { width: 26px; height: 26px; fill: white; }

        .brand-name {
            font-size: 26px;
            font-weight: 700;
            color: white;
            letter-spacing: 0.04em;
            line-height: 1;
        }
        .brand-name span {
            display: block;
            font-size: 11px;
            font-weight: 400;
            color: rgba(255,255,255,0.5);
            letter-spacing: 0.2em;
            margin-top: 5px;
        }

        .brand-divider {
            width: 36px; height: 2px;
            background: rgba(255,255,255,0.28);
            border-radius: 2px;
            margin: 26px 0;
        }

        .brand-tagline {
            font-size: 17px;
            font-weight: 300;
            color: rgba(255,255,255,0.82);
            line-height: 1.55;
            max-width: 270px;
            margin-bottom: 40px;
        }

        .brand-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 13px;
        }
        .brand-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            color: rgba(255,255,255,0.65);
        }
        .brand-features li svg {
            width: 16px; height: 16px;
            fill: #60a5fa;
            flex-shrink: 0;
        }

        /* ── RIGHT PANEL ─────────────────────────── */
        .panel-form {
            flex: 1;
            min-height: 100vh;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            overflow-y: auto;
        }

        .form-card {
            width: 100%;
            max-width: 400px;
            border: 1.5px solid #2563eb;
            border-radius: 16px;
            padding: 40px 36px;
            box-shadow: 0 8px 32px rgba(37, 99, 235, 0.08);
        }

        .form-head { margin-bottom: 32px; }
        .form-head h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: -0.4px;
        }
        .form-head p {
            margin-top: 5px;
            font-size: 14px;
            color: var(--text-gray);
        }

        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 11px 13px;
            border: 1.5px solid #2563eb;
            border-radius: 9px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text-dark);
            background: var(--bg-light);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .form-group input:focus {
            border-color: var(--blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .form-group input::placeholder { color: var(--text-light); }

        .form-forgot {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 22px;
        }
        .form-forgot a {
            font-size: 12.5px;
            color: var(--blue);
            text-decoration: none;
            font-weight: 500;
        }
        .form-forgot a:hover { text-decoration: underline; }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--blue-mid) 0%, var(--blue) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(37,99,235,0.3);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37,99,235,0.42);
        }
        .btn-submit:active { transform: none; }

        .form-sep {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 24px 0;
        }
        .form-sep hr {
            flex: 1;
            border: none;
            border-top: 1px solid var(--border);
        }
        .form-sep span {
            font-size: 12px;
            color: var(--text-light);
            white-space: nowrap;
        }

        .form-switch {
            text-align: center;
            font-size: 14px;
            color: var(--text-gray);
        }
        .form-switch a {
            color: var(--blue);
            font-weight: 600;
            text-decoration: none;
        }
        .form-switch a:hover { text-decoration: underline; }

        .alert {
            padding: 11px 14px;
            border-radius: 9px;
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 18px;
        }
        .alert-error  { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .alert-success{ background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }

        /* ── RESPONSIVE ──────────────────────────── */
        @media (max-width: 800px) {
            body { flex-direction: column; height: auto; }

            .panel-brand {
                width: 100%;
                min-height: auto;
                padding: 0;
            }
            .brand-back { top: 20px; left: 20px; }
            .brand-content {
                flex-direction: row;
                align-items: center;
                padding: 20px 20px 20px 20px;
                padding-top: 56px;
                gap: 14px;
            }
            .brand-tagline, .brand-features, .brand-divider { display: none; }
            .brand-icon { width: 42px; height: 42px; border-radius: 11px; margin-bottom: 0; flex-shrink: 0; }
            .brand-icon svg { width: 20px; height: 20px; }
            .brand-name { font-size: 18px; }

            .panel-form { min-height: auto; padding: 36px 24px 48px; }
        }
    </style>
</head>
<body>

    <aside class="panel-brand">
        <!-- Animated circles -->
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
        <div class="circle circle-4"></div>
        <div class="circle circle-5"></div>
        <div class="circle circle-6"></div>

        <a href="index.php" class="brand-back">
            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Volver al inicio
        </a>
        <div class="brand-content">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
            </div>
            <h2 class="brand-name">OFIEQUIPO<span>DE TAMPICO</span></h2>
            <div class="brand-divider"></div>
            <p class="brand-tagline">Tu proveedor de mobiliario de oficina de confianza.</p>
            <ul class="brand-features">
                <li>
                    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                    Gestiona tus cotizaciones fácilmente
                </li>
                <li>
                    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                    Historial de pedidos y cotizaciones
                </li>
                <li>
                    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                    Atención personalizada garantizada
                </li>
            </ul>
        </div>
    </aside>

    <main class="panel-form">
        <div class="form-card">
            <div class="form-head">
                <h2>Bienvenido de vuelta</h2>
                <p>Ingresa tus datos para acceder a tu cuenta.</p>
            </div>

            <?php
            $getMsg      = $_GET['msg'] ?? '';
            $getEmail    = htmlspecialchars($_GET['email'] ?? '');
            $noVerificado = str_starts_with($error, '__no_verificado__:');
            $emailNoVer   = $noVerificado ? htmlspecialchars(explode(':', $error, 2)[1]) : '';
            if ($noVerificado) $error = '';
            ?>
            <?php if ($getMsg === 'login_required'): ?>
                <div class="alert alert-error" style="display:flex;align-items:center;gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4a3 3 0 1 1 0 6 3 3 0 0 1 0-6zm0 13c-2.5 0-4.71-1.28-6-3.22C6.03 13.36 9.13 12 12 12s5.97 1.36 6 2.78C16.71 16.72 14.5 18 12 18z"/></svg>
                    Inicia sesión para continuar con tu compra.
                </div>
            <?php elseif ($getMsg === 'verificar_correo'): ?>
                <div class="alert alert-success" style="line-height:1.5;">
                    <strong>¡Cuenta creada!</strong> Te enviamos un correo a <strong><?= $getEmail ?></strong>. Verifica tu correo para poder iniciar sesión.
                    <form method="POST" action="apis/reenviar_verificacion.php" style="margin-top:8px;">
                        <input type="hidden" name="email" value="<?= $getEmail ?>">
                        <button type="submit" style="background:none;border:none;color:#15803d;font-weight:600;cursor:pointer;text-decoration:underline;padding:0;font-size:13px;">Reenviar correo de verificación</button>
                    </form>
                </div>
            <?php elseif ($getMsg === 'reenvio_ok'): ?>
                <div class="alert alert-success">Correo de verificación reenviado a <strong><?= $getEmail ?></strong>. Revisa tu bandeja de entrada.</div>
            <?php elseif ($noVerificado): ?>
                <div class="alert alert-error" style="line-height:1.5;">
                    <strong>Correo no verificado.</strong> Revisa tu bandeja de entrada y haz clic en el enlace que te enviamos.
                    <form method="POST" action="apis/reenviar_verificacion.php" style="margin-top:8px;">
                        <input type="hidden" name="email" value="<?= $emailNoVer ?>">
                        <button type="submit" style="background:none;border:none;color:#dc2626;font-weight:600;cursor:pointer;text-decoration:underline;padding:0;font-size:13px;">Reenviar correo de verificación</button>
                    </form>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="?redirect=<?= htmlspecialchars($redirectAfterLogin) ?>">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email"
                           placeholder="tucorreo@ejemplo.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password"
                           placeholder="••••••••"
                           required autocomplete="current-password">
                </div>
                <div class="form-forgot">
                    <a href="olvide_contrasena.php">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="btn-submit">Iniciar Sesión</button>
            </form>

            <div class="form-sep">
                <hr><span>¿No tienes cuenta?</span><hr>
            </div>

            <p class="form-switch">
                <a href="register.php">Crear una cuenta nueva</a>
            </p>
        </div>
    </main>

</body>
</html>
