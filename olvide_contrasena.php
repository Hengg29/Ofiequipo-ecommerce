<?php
session_start();
require_once __DIR__ . '/apis/db.php';
require_once __DIR__ . '/apis/mailer.php';

// Crear tabla de tokens si no existe
$conn->query("
    CREATE TABLE IF NOT EXISTS password_resets (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id BIGINT NOT NULL,
        token      VARCHAR(64) NOT NULL UNIQUE,
        expira_en  DATETIME NOT NULL,
        usado      TINYINT(1) NOT NULL DEFAULT 0,
        creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pr_token (token),
        INDEX idx_pr_usuario (usuario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$msg   = '';
$error = '';
$sent  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo electrónico válido.';
    } else {
        // Solo clientes (tabla usuarios), no admins
        $st = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $st->bind_param('s', $email);
        $st->execute();
        $user = $st->get_result()->fetch_assoc();
        $st->close();

        // Misma respuesta exista o no el correo (evita enumeración)
        if ($user) {
            // Invalidar tokens previos no usados
            $conn->prepare("UPDATE password_resets SET usado = 1 WHERE usuario_id = ? AND usado = 0")
                ->execute() || true;
            $delSt = $conn->prepare("UPDATE password_resets SET usado = 1 WHERE usuario_id = ? AND usado = 0");
            $delSt->bind_param('i', $user['id']);
            $delSt->execute();
            $delSt->close();

            $token    = bin2hex(random_bytes(32));
            $expira   = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $ins = $conn->prepare("INSERT INTO password_resets (usuario_id, token, expira_en) VALUES (?, ?, ?)");
            $ins->bind_param('iss', $user['id'], $token, $expira);
            $ins->execute();
            $ins->close();

            $enlace  = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $enlace .= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/restablecer_contrasena.php?token=' . $token;

            $asunto  = 'Restablecer contraseña — Ofiequipo de Tampico';
            $cuerpo  = "Hola,\n\n";
            $cuerpo .= "Recibimos una solicitud para restablecer la contraseña de tu cuenta en Ofiequipo de Tampico.\n\n";
            $cuerpo .= "Haz clic en el siguiente enlace para crear una nueva contraseña:\n";
            $cuerpo .= $enlace . "\n\n";
            $cuerpo .= "Este enlace expira en 1 hora.\n\n";
            $cuerpo .= "Si no solicitaste este cambio, ignora este correo — tu contraseña no cambiará.\n\n";
            $cuerpo .= "Equipo Ofiequipo de Tampico\n(833) 213-3837 | (833) 217-2047";
            smtp_send($email, $asunto, $cuerpo);
        }

        $sent = true;
        $msg  = 'Si ese correo está registrado, recibirás un enlace para restablecer tu contraseña en los próximos minutos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¿Olvidaste tu contraseña? — Ofiequipo de Tampico</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --blue-mid: #1e3a8a;
            --blue:     #2563eb;
            --text-dark:#0f172a;
            --text-gray:#475569;
            --text-light:#94a3b8;
            --border:   #e2e8f0;
            --bg-light: #f8fafc;
        }
        html, body { height: 100%; font-family: 'Inter', -apple-system, sans-serif; }
        body { display: flex; }

        /* Panel izquierdo — igual que login */
        .panel-brand {
            width: 44%; min-height: 100vh;
            background: linear-gradient(155deg, #0f172a 0%, #1e3a8a 60%, #1d4ed8 100%);
            display: flex; flex-direction: column;
            position: relative; overflow: hidden; flex-shrink: 0;
        }
        @keyframes drift1 { 0%{transform:translate(0,0) scale(1)} 25%{transform:translate(22px,-28px) scale(1.04)} 50%{transform:translate(-12px,18px) scale(0.96)} 75%{transform:translate(16px,6px) scale(1.02)} 100%{transform:translate(0,0) scale(1)} }
        @keyframes drift2 { 0%{transform:translate(0,0) scale(1)} 30%{transform:translate(-20px,24px) scale(1.06)} 65%{transform:translate(14px,-12px) scale(0.95)} 100%{transform:translate(0,0) scale(1)} }
        .circle { position:absolute; border-radius:50%; pointer-events:none; }
        .circle-1 { width:520px; height:520px; background:rgba(255,255,255,.05); top:-160px; right:-180px; animation:drift1 20s ease-in-out infinite; }
        .circle-2 { width:320px; height:320px; background:rgba(255,255,255,.05); bottom:-100px; left:-80px; animation:drift2 15s ease-in-out infinite; }
        .circle-3 { width:200px; height:200px; background:rgba(255,255,255,.04); top:42%; left:18%; animation:drift1 11s ease-in-out infinite; }
        .circle-4 { width:110px; height:110px; background:rgba(255,255,255,.07); top:12%; left:8%; animation:drift2 8s ease-in-out infinite; }

        .brand-back {
            position:absolute; top:32px; left:48px;
            display:inline-flex; align-items:center; gap:8px;
            color:rgba(255,255,255,.5); text-decoration:none;
            font-size:13px; font-weight:500; transition:color .2s; z-index:2;
        }
        .brand-back:hover { color:rgba(255,255,255,.9); }
        .brand-back svg { width:16px; height:16px; }

        .brand-content {
            flex:1; display:flex; flex-direction:column; justify-content:center;
            padding:100px 52px 64px; position:relative; z-index:1;
        }
        .brand-icon {
            width:54px; height:54px;
            background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
            border-radius:14px; display:flex; align-items:center; justify-content:center;
            margin-bottom:22px;
        }
        .brand-icon svg { width:26px; height:26px; fill:white; }
        .brand-name { font-size:26px; font-weight:700; color:white; letter-spacing:.04em; line-height:1; }
        .brand-name span { display:block; font-size:11px; font-weight:400; color:rgba(255,255,255,.5); letter-spacing:.2em; margin-top:5px; }
        .brand-divider { width:36px; height:2px; background:rgba(255,255,255,.28); border-radius:2px; margin:26px 0; }
        .brand-tagline { font-size:17px; font-weight:300; color:rgba(255,255,255,.82); line-height:1.55; max-width:270px; margin-bottom:40px; }
        .brand-step { display:flex; align-items:flex-start; gap:12px; margin-bottom:20px; }
        .brand-step-num {
            width:28px; height:28px; border-radius:50%;
            background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.2);
            display:flex; align-items:center; justify-content:center;
            font-size:12px; font-weight:700; color:white; flex-shrink:0; margin-top:1px;
        }
        .brand-step-text { font-size:13.5px; color:rgba(255,255,255,.65); line-height:1.5; }
        .brand-step-text strong { color:rgba(255,255,255,.9); display:block; font-weight:600; }

        /* Panel derecho */
        .panel-form {
            flex:1; min-height:100vh; background:white;
            display:flex; align-items:center; justify-content:center;
            padding:48px 40px; overflow-y:auto;
        }
        .form-card {
            width:100%; max-width:400px;
            border:1.5px solid #2563eb; border-radius:16px;
            padding:40px 36px;
            box-shadow:0 8px 32px rgba(37,99,235,.08);
        }
        .form-head { margin-bottom:28px; }
        .form-head h2 { font-size:24px; font-weight:700; color:var(--text-dark); letter-spacing:-.4px; }
        .form-head p { margin-top:6px; font-size:14px; color:var(--text-gray); line-height:1.5; }

        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-size:13px; font-weight:600; color:var(--text-dark); margin-bottom:6px; }
        .form-group input {
            width:100%; padding:11px 13px;
            border:1.5px solid #2563eb; border-radius:9px;
            font-size:14px; font-family:inherit; color:var(--text-dark);
            background:var(--bg-light); outline:none;
            transition:border-color .2s, box-shadow .2s, background .2s;
        }
        .form-group input:focus { border-color:var(--blue); background:white; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
        .form-group input::placeholder { color:var(--text-light); }

        .btn-submit {
            width:100%; padding:12px;
            background:linear-gradient(135deg, var(--blue-mid) 0%, var(--blue) 100%);
            color:white; border:none; border-radius:10px;
            font-size:15px; font-weight:600; font-family:inherit; cursor:pointer;
            box-shadow:0 4px 14px rgba(37,99,235,.3);
            transition:transform .15s, box-shadow .15s;
        }
        .btn-submit:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(37,99,235,.42); }

        .form-back { text-align:center; margin-top:20px; font-size:13.5px; color:var(--text-gray); }
        .form-back a { color:var(--blue); font-weight:600; text-decoration:none; }
        .form-back a:hover { text-decoration:underline; }

        .alert { padding:14px 16px; border-radius:10px; font-size:13.5px; font-weight:500; margin-bottom:22px; line-height:1.5; }
        .alert-error   { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .alert-success { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; display:flex; align-items:flex-start; gap:10px; }
        .alert-success svg { width:18px; height:18px; fill:#16a34a; flex-shrink:0; margin-top:1px; }

        .sent-icon {
            width:64px; height:64px; border-radius:50%;
            background:#eff6ff; display:flex; align-items:center; justify-content:center;
            margin:0 auto 20px;
        }
        .sent-icon svg { width:32px; height:32px; fill:#2563eb; }

        @media (max-width:800px) {
            body { flex-direction:column; height:auto; }
            .panel-brand { width:100%; min-height:auto; }
            .brand-back { top:20px; left:20px; }
            .brand-content { flex-direction:row; align-items:center; padding:20px; padding-top:56px; gap:14px; }
            .brand-tagline, .brand-step, .brand-divider { display:none; }
            .brand-icon { width:42px; height:42px; border-radius:11px; margin-bottom:0; flex-shrink:0; }
            .brand-icon svg { width:20px; height:20px; }
            .brand-name { font-size:18px; }
            .panel-form { min-height:auto; padding:36px 24px 48px; }
        }
    </style>
</head>
<body>

<aside class="panel-brand">
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>
    <div class="circle circle-3"></div>
    <div class="circle circle-4"></div>
    <a href="login.php" class="brand-back">
        <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
        Volver al inicio de sesión
    </a>
    <div class="brand-content">
        <div class="brand-icon">
            <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
        </div>
        <h2 class="brand-name">OFIEQUIPO<span>DE TAMPICO</span></h2>
        <div class="brand-divider"></div>
        <p class="brand-tagline">Recupera el acceso a tu cuenta en 3 pasos.</p>
        <div class="brand-step">
            <div class="brand-step-num">1</div>
            <div class="brand-step-text"><strong>Ingresa tu correo</strong>Te enviaremos un enlace seguro.</div>
        </div>
        <div class="brand-step">
            <div class="brand-step-num">2</div>
            <div class="brand-step-text"><strong>Revisa tu bandeja</strong>El correo llega en pocos minutos.</div>
        </div>
        <div class="brand-step">
            <div class="brand-step-num">3</div>
            <div class="brand-step-text"><strong>Crea tu nueva contraseña</strong>El enlace es válido por 1 hora.</div>
        </div>
    </div>
</aside>

<main class="panel-form">
    <div class="form-card">
        <?php if ($sent): ?>
            <div class="sent-icon">
                <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </div>
            <div class="form-head" style="text-align:center;">
                <h2>Revisa tu correo</h2>
                <p><?= htmlspecialchars($msg) ?></p>
            </div>
            <p class="form-back" style="margin-top:28px;">
                ¿No llegó? Revisa la carpeta de spam.<br><br>
                <a href="login.php">← Volver al inicio de sesión</a>
            </p>
        <?php else: ?>
            <div class="form-head">
                <h2>¿Olvidaste tu contraseña?</h2>
                <p>Ingresa tu correo y te enviaremos un enlace para crear una nueva.</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="olvide_contrasena.php">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email"
                           placeholder="tucorreo@ejemplo.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required autocomplete="email">
                </div>
                <button type="submit" class="btn-submit">Enviar enlace de recuperación</button>
            </form>

            <p class="form-back">
                <a href="login.php">← Volver al inicio de sesión</a>
            </p>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
