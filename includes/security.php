<?php
declare(strict_types=1);

// ─── SESIÓN SEGURA ────────────────────────────────────────────────────────────
// Llamar ANTES de session_start()
function security_session_configure(): void
{
    if (session_status() !== PHP_SESSION_NONE) return;

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,          // Cookie de sesión (se borra al cerrar el navegador)
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,    // Solo HTTPS en producción
        'httponly' => true,        // Inaccesible para JavaScript
        'samesite' => 'Lax',      // Protege contra CSRF manteniendo navegación normal
    ]);

    ini_set('session.use_strict_mode',   '1');  // Rechazar IDs de sesión no generados por el servidor
    ini_set('session.gc_maxlifetime',    '7200'); // 2 horas de inactividad máxima
    ini_set('session.use_only_cookies',  '1');  // Nunca IDs en URLs
    ini_set('session.cookie_httponly',   '1');
    ini_set('session.cookie_samesite',   'Lax');
}

// ─── HEADERS DE SEGURIDAD HTTP ────────────────────────────────────────────────
function security_headers(): void
{
    if (headers_sent()) return;

    // Evitar que la página se incruste en iframes de otros dominios
    header('X-Frame-Options: SAMEORIGIN');

    // Evitar que el navegador "adivine" el tipo MIME
    header('X-Content-Type-Options: nosniff');

    // Filtro XSS del navegador
    header('X-XSS-Protection: 1; mode=block');

    // Controlar qué información de referencia se envía
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Desactivar APIs del navegador que no se usan
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // Content Security Policy — permite inline scripts/styles (necesario para el proyecto actual)
    // y solo carga recursos de fuentes conocidas
    header(
        "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' " .
            "https://www.paypal.com https://www.sandbox.paypal.com " .
            "https://js.stripe.com https://cdn.jsdelivr.net " .
            "https://fonts.googleapis.com; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
        "font-src 'self' https://fonts.gstatic.com; " .
        "img-src 'self' data: https:; " .
        "frame-src https://www.paypal.com https://www.sandbox.paypal.com; " .
        "connect-src 'self' https://www.paypal.com https://www.sandbox.paypal.com; " .
        "object-src 'none';"
    );
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Genera el campo oculto para pegar dentro de <form>
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
         . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

// Verifica el token en requests POST. Termina con 403 si es inválido.
function csrf_verify(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';

    if (!$stored || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error de seguridad</title>'
           . '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f3f4f6;margin:0}'
           . '.box{background:#fff;padding:40px;border-radius:12px;text-align:center;max-width:380px;border:1px solid #e5e7eb}'
           . 'h2{color:#dc2626;margin-bottom:10px}p{color:#6b7280;margin-bottom:20px}'
           . 'a{color:#1D3D8E;font-weight:600}</style></head>'
           . '<body><div class="box"><h2>Token de seguridad inválido</h2>'
           . '<p>La página expiró o fue recargada. Por favor regresa e intenta de nuevo.</p>'
           . '<a href="javascript:history.back()">← Volver</a></div></body></html>';
        exit;
    }
}

// ─── REDIRECCIÓN SEGURA (previene Open Redirect) ──────────────────────────────
function safe_redirect(string $url, string $fallback = 'index.php'): void
{
    // Rechazar URLs absolutas, protocol-relative y rutas con backslash
    if (
        preg_match('~^(https?:)?//~i', $url) ||
        str_starts_with(ltrim($url), '\\') ||
        str_contains($url, ':')
    ) {
        $url = $fallback;
    }

    // Solo permitir caracteres seguros en URL
    $url = preg_replace('/[^\w\-\.\/\?=&#%+_]/', '', $url);

    if (empty(trim($url, '/ '))) $url = $fallback;

    header('Location: ' . $url);
    exit;
}

// ─── RATE LIMITING ───────────────────────────────────────────────────────────
// Devuelve true si el intento está permitido, false si se superó el límite
function rate_limit_ok(mysqli $conn, string $context, int $max = 5, int $windowSec = 300): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Limpiar intentos antiguos (silencioso si la tabla no existe)
    @$conn->query(
        "DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL {$windowSec} SECOND)"
    );

    $st = @$conn->prepare(
        "SELECT COUNT(*) AS c FROM login_attempts
         WHERE ip = ? AND context = ? AND success = 0
           AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
    );
    if (!$st) return true; // Si la tabla no existe, no bloquear

    $st->bind_param('ssi', $ip, $context, $windowSec);
    $st->execute();
    $c = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();

    return $c < $max;
}

function rate_limit_record(mysqli $conn, string $context, bool $success): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $st = @$conn->prepare(
        "INSERT INTO login_attempts (ip, context, success) VALUES (?, ?, ?)"
    );
    if (!$st) return;
    $s = $success ? 1 : 0;
    $st->bind_param('ssi', $ip, $context, $s);
    $st->execute();
    $st->close();
}

function rate_limit_clear(mysqli $conn, string $context): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $st = @$conn->prepare(
        "DELETE FROM login_attempts WHERE ip = ? AND context = ?"
    );
    if (!$st) return;
    $st->bind_param('ss', $ip, $context);
    $st->execute();
    $st->close();
}
