<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'add':
        $id     = (int)($_POST['id']     ?? 0);
        $nombre = trim($_POST['nombre']  ?? '');
        $imagen = trim($_POST['imagen']  ?? '');
        if ($id > 0 && $nombre !== '') {
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] === $id) {
                    $item['cantidad']++;
                    $found = true;
                    break;
                }
            }
            unset($item);
            if (!$found) {
                $_SESSION['cart'][] = [
                    'id'       => $id,
                    'nombre'   => $nombre,
                    'imagen'   => $imagen,
                    'cantidad' => 1,
                ];
            }
        }
        break;

    case 'remove':
        $id = (int)($_POST['id'] ?? 0);
        $_SESSION['cart'] = array_values(
            array_filter($_SESSION['cart'], fn($i) => $i['id'] !== $id)
        );
        break;

    case 'update':
        $id       = (int)($_POST['id']       ?? 0);
        $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $id) {
                $item['cantidad'] = $cantidad;
                break;
            }
        }
        unset($item);
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        break;
}

$count = array_sum(array_column($_SESSION['cart'], 'cantidad'));
$cart  = $_SESSION['cart'];
session_write_close(); // liberar lock de sesión inmediatamente

echo json_encode([
    'success' => true,
    'cart'    => $cart,
    'count'   => $count,
]);
