<?php
session_start();

foreach (['user_id', 'user_email', 'user_role', 'user_nombre'] as $k) {
    unset($_SESSION[$k]);
}

header('Location: index.php');
exit;
