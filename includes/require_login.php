<?php
if (empty($_SESSION['user_id'])) {
    $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
    header('Location: login.php?redirect=' . $redirect . '&msg=login_required');
    exit;
}
