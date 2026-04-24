<?php
session_start();
require_once __DIR__ . '/apis/db.php';

$status = 'invalid';
$token  = trim($_GET['token'] ?? '');

if ($token !== '') {
    $st = $conn->prepare(
        "SELECT id, nombre, email FROM usuarios
         WHERE verificacion_token = ? AND token_expira > NOW() AND email_verificado = 0 LIMIT 1"
    );
    $st->bind_param('s', $token);
    $st->execute();
    $user = $st->get_result()->fetch_assoc();
    $st->close();

    if ($user) {
        $up = $conn->prepare(
            "UPDATE usuarios SET email_verificado = 1, verificacion_token = NULL, token_expira = NULL WHERE id = ?"
        );
        $up->bind_param('i', $user['id']);
        $up->execute();
        $up->close();
        $status = 'ok';
    } else {
        // Check if already verified
        $st2 = $conn->prepare("SELECT id FROM usuarios WHERE verificacion_token = ? LIMIT 1");
        $st2->bind_param('s', $token);
        $st2->execute();
        $already = $st2->get_result()->fetch_assoc();
        $st2->close();
        $status = $already ? 'expired' : 'invalid';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verificación de correo — OfiEquipo</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 60%,#1d4ed8 100%);font-family:'Inter',sans-serif;padding:24px;}
.card{background:white;border-radius:20px;padding:48px 40px;max-width:420px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.25);}
.icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:32px;}
.icon-ok{background:#f0fdf4;}
.icon-err{background:#fef2f2;}
h2{font-size:22px;font-weight:700;color:#0f172a;margin-bottom:10px;}
p{font-size:15px;color:#475569;line-height:1.6;margin-bottom:24px;}
.btn{display:inline-block;padding:12px 28px;background:linear-gradient(135deg,#1e3a8a,#2563eb);color:white;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;box-shadow:0 4px 14px rgba(37,99,235,0.35);}
.btn-outline{display:inline-block;padding:12px 28px;border:1.5px solid #2563eb;color:#2563eb;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;margin-top:12px;}
</style>
</head>
<body>
<div class="card">
<?php if ($status === 'ok'): ?>
    <div class="icon icon-ok">✅</div>
    <h2>¡Correo verificado!</h2>
    <p>Tu cuenta ha sido verificada exitosamente. Ya puedes iniciar sesión y disfrutar de todos los beneficios.</p>
    <a href="index.php" class="btn">Ir al inicio</a>
<?php elseif ($status === 'expired'): ?>
    <div class="icon icon-err">⏰</div>
    <h2>Enlace expirado</h2>
    <p>Este enlace de verificación ha expirado. Inicia sesión y te enviaremos uno nuevo.</p>
    <a href="login.php" class="btn">Iniciar sesión</a>
<?php else: ?>
    <div class="icon icon-err">❌</div>
    <h2>Enlace inválido</h2>
    <p>El enlace de verificación no es válido o ya fue utilizado.</p>
    <a href="index.php" class="btn">Ir al inicio</a>
<?php endif; ?>
</div>
</body>
</html>
