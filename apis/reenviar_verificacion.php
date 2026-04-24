<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/mailer.php';

$base  = rtrim($_ENV['APP_URL'] ?? 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
$email = strtolower(trim($_POST['email'] ?? $_GET['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $base . '/login.php?msg=reenvio_error');
    exit;
}

$st = $conn->prepare(
    "SELECT id, nombre, email FROM usuarios WHERE email = ? AND email_verificado = 0 LIMIT 1"
);
$st->bind_param('s', $email);
$st->execute();
$user = $st->get_result()->fetch_assoc();
$st->close();

$nombre = '';
$token  = '';
if ($user) {
    $token  = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $up = $conn->prepare("UPDATE usuarios SET verificacion_token = ?, token_expira = ? WHERE id = ?");
    $up->bind_param('ssi', $token, $expira, $user['id']);
    $up->execute();
    $up->close();
    $nombre = $user['nombre'] ?: explode('@', $user['email'])[0];
}

// Redirigir primero, enviar correo después
header('Location: ' . $base . '/login.php?msg=reenvio_ok&email=' . urlencode($email));
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

if ($user) {
    $result = sendVerificacionEmail($user['email'], $nombre, $token);
    file_put_contents(__DIR__ . '/../mail_debug.log',
        date('Y-m-d H:i:s') . ' [reenviar] to=' . $email . ' result=' . ($result ? 'OK' : 'FALLO') . "\n",
        FILE_APPEND);
}
exit;
