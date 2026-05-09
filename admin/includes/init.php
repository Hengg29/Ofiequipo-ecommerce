<?php
/**
 * Bootstrap del panel administrativo
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/security.php';

if (session_status() === PHP_SESSION_NONE) {
    security_session_configure();
    session_start();
}

security_headers();

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
        $slug = $_SESSION['admin_rol_slug'] ?? '';
        $back = $slug === 'repartidor' ? 'repartidor.php' : 'index.php';
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Sin acceso</title>'
           . '<style>*{box-sizing:border-box;margin:0;padding:0}body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f3f4f6;font-family:Inter,system-ui,sans-serif}'
           . '.box{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:48px 40px;max-width:400px;width:100%;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.07)}'
           . '.icon{font-size:40px;margin-bottom:16px}.title{font-size:20px;font-weight:700;color:#111827;margin-bottom:8px}.sub{font-size:14px;color:#6b7280;margin-bottom:28px;line-height:1.5}'
           . '.btn{display:inline-block;padding:11px 24px;background:#1D3D8E;color:white;text-decoration:none;border-radius:9px;font-size:14px;font-weight:600}'
           . '.btn:hover{background:#2B4FAF}</style></head>'
           . '<body><div class="box">'
           . '<div class="icon">🔒</div>'
           . '<div class="title">Acceso restringido</div>'
           . '<p class="sub">No tienes permiso para ver este módulo.</p>'
           . '<a href="' . $back . '" class="btn">Volver al panel</a>'
           . '</div></body></html>';
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
    $stmt = $conn->prepare(
        'INSERT INTO admin_auditoria (usuario_id, accion, entidad, entidad_id, detalle, ip) VALUES (?,?,?,?,?,?)'
    );
    if (!$stmt) {
        error_log('[Audit] prepare falló: ' . $conn->error);
        return;
    }
    $stmt->bind_param('ississ', $uid, $accion, $entidad, $entidadId, $detalle, $ip);
    if (!$stmt->execute()) {
        error_log('[Audit] execute falló: ' . $stmt->error);
    }
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

function admin_estado_badge(string $estado): string
{
    $labels = [
        'pendiente'      => 'Pendiente',
        'en_preparacion' => 'En preparación',
        'en_proceso'     => 'En proceso',
        'enviado'        => 'Enviado',
        'en_camino'      => 'En camino',
        'entregado'      => 'Entregado',
        'completado'     => 'Completado',
        'cancelado'      => 'Cancelado',
    ];
    $label = $labels[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
    $class = preg_replace('/[^a-z0-9_]/', '', strtolower($estado));
    return '<span class="badge ' . $class . '">' . htmlspecialchars($label) . '</span>';
}
