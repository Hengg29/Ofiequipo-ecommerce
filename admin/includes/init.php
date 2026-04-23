<?php
/**
 * Bootstrap del panel administrativo
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/apis/db.php';

/** @var mysqli $conn */
$conn->set_charset('utf8mb4');

function admin_h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function admin_redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Matriz de permisos por slug de rol (debe coincidir con admin_roles.slug)
 */
function admin_role_modules(): array
{
    return [
        'administrador' => null, // null = todos los módulos
        'vendedor' => [
            'dashboard', 'ventas', 'clientes', 'analisis', 'reportes', 'promociones',
        ],
        'almacen' => [
            'dashboard', 'envios', 'inventario', 'productos', 'reportes',
        ],
        'repartidor' => [
            'repartidor',
        ],
    ];
}

function admin_can(string $module): bool
{
    $slug = $_SESSION['admin_rol_slug'] ?? '';
    if ($slug === 'administrador') {
        return true;
    }
    $map = admin_role_modules();
    if (!isset($map[$slug])) {
        return false;
    }
    $allowed = $map[$slug];
    if ($allowed === null) {
        return true;
    }
    return in_array($module, $allowed, true);
}

function admin_require_module(string $module): void
{
    if (!admin_can($module)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Sin permiso</title></head><body>';
        echo '<p>No tienes permiso para acceder a este módulo.</p><p><a href="index.php">Volver al panel</a></p>';
        echo '</body></html>';
        exit;
    }
}

function admin_require_login(): void
{
    if (empty($_SESSION['admin_user_id'])) {
        admin_redirect('login.php');
    }
}

/**
 * Registro de auditoría (falla en silencio si la tabla no existe)
 */
function admin_audit(mysqli $conn, string $accion, string $entidad, ?int $entidadId, ?string $detalle = null): void
{
    $uid = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = @$conn->prepare(
        'INSERT INTO admin_auditoria (usuario_id, accion, entidad, entidad_id, detalle, ip) VALUES (?,?,?,?,?,?)'
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('ississ', $uid, $accion, $entidad, $entidadId, $detalle, $ip);
    $stmt->execute();
    $stmt->close();
}

function admin_table_exists(mysqli $conn, string $table): bool
{
    $t = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '$t'");

    return $r && $r->num_rows > 0;
}

function admin_menu_items(): array
{
    return [
        ['id' => 'dashboard', 'module' => 'dashboard', 'label' => 'Dashboard', 'href' => 'index.php'],
        ['id' => 'ventas', 'module' => 'ventas', 'label' => 'Ventas', 'href' => 'ventas.php'],
        ['id' => 'envios', 'module' => 'envios', 'label' => 'Envíos', 'href' => 'envios.php'],
        ['id' => 'analisis', 'module' => 'analisis', 'label' => 'Análisis', 'href' => 'analisis.php'],
        ['id' => 'clientes', 'module' => 'clientes', 'label' => 'Clientes', 'href' => 'clientes.php'],
        ['id' => 'usuarios', 'module' => 'usuarios', 'label' => 'Usuarios', 'href' => 'usuarios.php'],
        ['id' => 'productos', 'module' => 'productos', 'label' => 'Productos', 'href' => 'productos.php'],
        ['id' => 'inventario', 'module' => 'inventario', 'label' => 'Inventario', 'href' => 'inventario.php'],
        ['id' => 'promociones', 'module' => 'promociones', 'label' => 'Promociones', 'href' => 'promociones.php'],
        ['id' => 'reportes', 'module' => 'reportes', 'label' => 'Reportes', 'href' => 'reportes.php'],
        ['id' => 'configuracion', 'module' => 'configuracion', 'label' => 'Configuración', 'href' => 'configuracion.php'],
        ['id' => 'auditoria', 'module' => 'auditoria', 'label' => 'Auditoría', 'href' => 'auditoria.php'],
    ];
}

function admin_active_id(string $script): string
{
    $base = basename($script);
    foreach (admin_menu_items() as $item) {
        if (basename($item['href']) === $base) {
            return $item['id'];
        }
    }
    if (strpos($base, 'venta.php') === 0) {
        return 'ventas';
    }
    if (strpos($base, 'cliente.php') === 0) {
        return 'clientes';
    }
    if (strpos($base, 'producto_edit.php') === 0) {
        return 'productos';
    }
    return 'dashboard';
}
