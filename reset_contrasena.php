<?php
session_start();
require_once __DIR__ . '/apis/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$token = trim($_GET['token'] ?? '');
$error = '';
$done  = false;

// Validate token
$user = null;
if ($token !== '') {
    $st = $conn->prepare(
        "SELECT id, nombre, email FROM usuarios WHERE reset_token = ? AND reset_expira > NOW() LIMIT 1"
    );
    $st->bind_param('s', $token);
    $st->execute();
    $user = $st->get_result()->fetch_assoc();
    $st->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = trim($_POST['token'] ?? '');
    $pass      = $_POST['password']  ?? '';
    $confirm   = $_POST['confirm']   ?? '';

    // Re-validate token from POST
    $st2 = $conn->prepare(
        "SELECT id, nombre, email FROM usuarios WHERE reset_token = ? AND reset_expira > NOW() LIMIT 1"
    );
    $st2->bind_param('s', $postToken);
    $st2->execute();
    $user = $st2->get_result()->fetch_assoc();
    $st2->close();

    if (!$user) {
        $error = 'El enlace expiró o ya fue utilizado. Solicita uno nuevo.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $up   = $conn->prepare(
            "UPDATE usuarios SET contrasena_hash = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?"
        );
        $up->bind_param('si', $hash, $user['id']);
        $up->execute();
        $up->close();
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nueva contraseña — OfiEquipo</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;font-family:'Inter',sans-serif;}
body{display:flex;}
.panel-brand{width:44%;min-height:100vh;background:linear-gradient(155deg,#0f172a 0%,#1e3a8a 60%,#1d4ed8 100%);display:flex;flex-direction:column;position:relative;overflow:hidden;flex-shrink:0;}
@keyframes drift1{0%{transform:translate(0,0)}50%{transform:translate(14px,-18px)}100%{transform:translate(0,0)}}
@keyframes drift2{0%{transform:translate(0,0)}40%{transform:translate(-16px,20px)}100%{transform:translate(0,0)}}
.circle{position:absolute;border-radius:50%;pointer-events:none;}
.c1{width:500px;height:500px;background:rgba(255,255,255,0.05);top:-160px;right:-180px;animation:drift1 18s ease-in-out infinite;}
.c2{width:300px;height:300px;background:rgba(255,255,255,0.05);bottom:-80px;left:-60px;animation:drift2 13s ease-in-out infinite;}
.c3{width:110px;height:110px;background:rgba(255,255,255,0.07);top:12%;left:8%;animation:drift1 8s ease-in-out infinite reverse;}
.brand-back{position:absolute;top:32px;left:48px;display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,0.5);text-decoration:none;font-size:13px;font-weight:500;transition:color .2s;z-index:2;}
.brand-back:hover{color:rgba(255,255,255,0.9);}
.brand-content{flex:1;display:flex;flex-direction:column;justify-content:center;padding:100px 52px 64px;position:relative;z-index:1;}
.brand-icon{width:54px;height:54px;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:22px;}
.brand-name{font-size:26px;font-weight:700;color:white;letter-spacing:.04em;line-height:1;}
.brand-name span{display:block;font-size:11px;font-weight:400;color:rgba(255,255,255,0.5);letter-spacing:.2em;margin-top:5px;}
.brand-divider{width:36px;height:2px;background:rgba(255,255,255,0.28);border-radius:2px;margin:26px 0;}
.brand-tagline{font-size:16px;font-weight:300;color:rgba(255,255,255,0.8);line-height:1.6;max-width:270px;}
.panel-form{flex:1;min-height:100vh;background:white;display:flex;align-items:center;justify-content:center;padding:48px 40px;overflow-y:auto;}
.form-card{width:100%;max-width:400px;border:1.5px solid #2563eb;border-radius:16px;padding:40px 36px;box-shadow:0 8px 32px rgba(37,99,235,0.08);}
.form-head{margin-bottom:28px;}
.form-head h2{font-size:22px;font-weight:700;color:#0f172a;letter-spacing:-.4px;}
.form-head p{margin-top:6px;font-size:14px;color:#475569;line-height:1.5;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:13px;font-weight:600;color:#0f172a;margin-bottom:6px;}
.form-group input{width:100%;padding:11px 13px;border:1.5px solid #2563eb;border-radius:9px;font-size:14px;font-family:inherit;color:#0f172a;background:#f8fafc;outline:none;transition:border-color .2s,box-shadow .2s;}
.form-group input:focus{border-color:#2563eb;background:white;box-shadow:0 0 0 3px rgba(37,99,235,0.1);}
.form-group input::placeholder{color:#94a3b8;}
.pass-hint{font-size:12px;color:#94a3b8;margin-top:-8px;margin-bottom:16px;}
.btn-submit{width:100%;padding:12px;background:linear-gradient(135deg,#1e3a8a,#2563eb);color:white;border:none;border-radius:10px;font-size:15px;font-weight:600;font-family:inherit;cursor:pointer;box-shadow:0 4px 14px rgba(37,99,235,0.3);transition:transform .15s,box-shadow .15s;margin-top:6px;}
.btn-submit:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,0.42);}
.alert{padding:11px 14px;border-radius:9px;font-size:13.5px;font-weight:500;margin-bottom:18px;}
.alert-error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.form-switch{text-align:center;font-size:14px;color:#475569;margin-top:20px;}
.form-switch a{color:#2563eb;font-weight:600;text-decoration:none;}
@media(max-width:800px){body{flex-direction:column;height:auto;}.panel-brand{width:100%;min-height:auto;}.brand-back{top:20px;left:20px;}.brand-content{flex-direction:row;align-items:center;padding:56px 20px 20px;gap:14px;}.brand-tagline,.brand-divider{display:none;}.brand-icon{width:42px;height:42px;border-radius:11px;margin-bottom:0;}.brand-name{font-size:18px;}.panel-form{min-height:auto;padding:32px 24px 48px;}}
</style>
</head>
<body>
<aside class="panel-brand">
    <div class="circle c1"></div><div class="circle c2"></div><div class="circle c3"></div>
    <a href="login.php" class="brand-back">
        <svg width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
        Volver al login
    </a>
    <div class="brand-content">
        <div class="brand-icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="white"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
        </div>
        <h2 class="brand-name">OFIEQUIPO<span>DE TAMPICO</span></h2>
        <div class="brand-divider"></div>
        <p class="brand-tagline">Crea una nueva contraseña segura para tu cuenta.</p>
    </div>
</aside>
<main class="panel-form">
    <div class="form-card">
        <?php if ($done): ?>
            <div class="form-head">
                <h2>¡Contraseña actualizada!</h2>
                <p>Tu contraseña fue cambiada exitosamente. Ya puedes iniciar sesión.</p>
            </div>
            <a href="login.php" style="display:block;text-align:center;padding:12px;background:linear-gradient(135deg,#1e3a8a,#2563eb);color:white;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;">Iniciar sesión</a>
        <?php elseif (!$user && !$_POST): ?>
            <div class="form-head">
                <h2>Enlace inválido</h2>
                <p>Este enlace expiró o ya fue utilizado. Solicita uno nuevo.</p>
            </div>
            <a href="olvide_contrasena.php" style="display:block;text-align:center;padding:12px;background:linear-gradient(135deg,#1e3a8a,#2563eb);color:white;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;">Solicitar nuevo enlace</a>
        <?php else: ?>
            <div class="form-head">
                <h2>Nueva contraseña</h2>
                <p>Elige una contraseña segura de al menos 6 caracteres.</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" minlength="6" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm">Confirmar contraseña</label>
                    <input type="password" id="confirm" name="confirm" placeholder="Repite la contraseña" required autocomplete="new-password">
                </div>
                <p class="pass-hint">Mínimo 6 caracteres.</p>
                <button type="submit" class="btn-submit">Guardar nueva contraseña</button>
            </form>
        <?php endif; ?>
        <p class="form-switch"><a href="login.php">Volver al inicio de sesión</a></p>
    </div>
</main>
</body>
</html>
