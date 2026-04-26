<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$ACCIONES_VALIDAS = ['add', 'remove', 'update', 'clear'];
if ($action !== '' && !in_array($action, $ACCIONES_VALIDAS, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
    exit;
}

define('MAX_ITEMS_CARRITO', 99);

switch ($action) {

    case 'add':
        $id     = (int)($_POST['id'] ?? 0);
        $nombre = mb_substr(trim($_POST['nombre'] ?? ''), 0, 255);
        $imagen = trim($_POST['imagen'] ?? '');
        // Rechazar protocolos peligrosos en la URL de imagen
        if (preg_match('/^\s*javascript:/i', $imagen)) {
            $imagen = '';
        }
        if ($id > 0 && $nombre !== '') {
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] === $id) {
                    $item['cantidad'] = min($item['cantidad'] + 1, MAX_ITEMS_CARRITO);
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
        if ($id > 0) {
            $_SESSION['cart'] = array_values(
                array_filter($_SESSION['cart'], fn($i) => $i['id'] !== $id)
            );
        }
        break;

    case 'update':
        $id       = (int)($_POST['id'] ?? 0);
        $cantidad = max(1, min((int)($_POST['cantidad'] ?? 1), MAX_ITEMS_CARRITO));
        if ($id > 0) {
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] === $id) {
                    $item['cantidad'] = $cantidad;
                    break;
                }
            }
            unset($item);
        }
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        break;
}

$count = array_sum(array_column($_SESSION['cart'], 'cantidad'));
$cart  = $_SESSION['cart'];
session_write_close();

echo json_encode([
    'success' => true,
    'cart'    => $cart,
    'count'   => $count,
]);
