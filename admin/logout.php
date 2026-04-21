<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_audit($conn, 'logout', 'sesion', isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null, 'Cierre de sesión');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
admin_redirect('login.php');
