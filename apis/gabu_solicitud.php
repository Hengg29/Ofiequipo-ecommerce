<?php
// Recibe las respuestas del asistente virtual Gabu y las manda por correo a la admin
// en formato de solicitud de cotización. No requiere sesión.
session_start();
session_write_close();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/mailer.php';

function responder(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['success' => false, 'error' => 'Método no permitido.']);
}

// Anti-CSRF: solo JSON y, si el navegador manda Origin, debe ser este mismo host
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
    || ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST))) {
    responder(403, ['success' => false, 'error' => 'Solicitud no permitida.']);
}

// Evita que se use para inundar de correos a la admin: máx. 5 solicitudes por hora por IP
if (!rate_limit_ok($conn, 'gabu', 5, 3600)) {
    responder(429, ['success' => false, 'error' => 'Ya recibimos varias solicitudes desde tu conexión. Intenta más tarde o escríbenos por WhatsApp.']);
}

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) {
    responder(400, ['success' => false, 'error' => 'Solicitud inválida.']);
}

$nombre      = mb_substr(trim((string)($in['nombre']   ?? '')), 0, 150);
$telefono    = mb_substr(trim((string)($in['telefono'] ?? '')), 0, 25);
$servicio    = mb_substr(trim((string)($in['servicio'] ?? '')), 0, 1000);
$presupuesto = preg_replace('/\D/', '', (string)($in['presupuesto'] ?? ''));
$pagina      = mb_substr(trim((string)($in['pagina'] ?? '')), 0, 200);

$errors = [];
if (mb_strlen($nombre) < 2)                               $errors['nombre']      = 'Escribe tu nombre.';
if (strlen(preg_replace('/\D/', '', $telefono)) < 10)     $errors['telefono']    = 'El teléfono debe tener al menos 10 dígitos.';
if (mb_strlen($servicio) < 3)                             $errors['servicio']    = 'Cuéntame un poco más de lo que necesitas.';
if ($presupuesto === '' || strlen($presupuesto) > 10 || (int)$presupuesto <= 0)
                                                          $errors['presupuesto'] = 'Escribe tu presupuesto solo con números.';
if ($errors) {
    responder(422, ['success' => false, 'errors' => $errors, 'error' => reset($errors)]);
}

rate_limit_record($conn, 'gabu', false); // cuenta para el límite por hora

$folio    = 'GABU-' . date('ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
$fecha    = date('d/m/Y H:i');
$telDig   = preg_replace('/\D/', '', $telefono);
$telWa    = '52' . substr($telDig, -10);
$presFmt  = '$' . number_format((int)$presupuesto) . ' MXN';
$cuenta   = !empty($_SESSION['user_id'])
    ? 'Con sesión: ' . ($_SESSION['user_email'] ?? '')
    : 'Sin sesión (se le pidió crear cuenta o iniciar sesión)';

// ── Correo a la admin ───────────────────────────────────────────
$fila = fn(string $label, string $valorHtml) =>
    "<tr>
        <td style='padding:11px 0;border-bottom:1px solid #f1f5f9;font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.05em;width:150px;vertical-align:top;'>{$label}</td>
        <td style='padding:11px 0;border-bottom:1px solid #f1f5f9;font-size:15px;color:#0f172a;'>{$valorHtml}</td>
    </tr>";

$cuerpo = "
    <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 20px;'>
        Gabu, el asistente virtual del sitio, recibió una nueva solicitud de cotización:
    </p>
    <div style='background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:16px 20px;margin-bottom:20px;'>
        <div style='font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.05em;'>Folio</div>
        <div style='font-size:20px;font-weight:800;color:#1e3a8a;'>{$folio}</div>
        <div style='font-size:12px;color:#64748b;margin-top:2px;'>{$fecha}</div>
    </div>
    <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse:collapse;margin-bottom:24px;'>"
        . $fila('Cliente', '<strong>' . htmlspecialchars($nombre) . '</strong>')
        . $fila('Teléfono', "<a href='tel:" . htmlspecialchars($telDig) . "' style='color:#2563eb;font-weight:600;text-decoration:none;'>" . htmlspecialchars($telefono) . '</a>')
        . $fila('Qué desea realizar', nl2br(htmlspecialchars($servicio)))
        . $fila('Presupuesto', '<strong>' . htmlspecialchars($presFmt) . '</strong>')
        . $fila('Cuenta', htmlspecialchars($cuenta))
        . ($pagina !== '' ? $fila('Página', htmlspecialchars($pagina)) : '')
    . "</table>
    " . _mailBtn('https://wa.me/' . $telWa . '?text=' . rawurlencode("Hola {$nombre}, le contactamos de OfiEquipo de Tampico por su solicitud de cotización."), 'Contactar por WhatsApp');

$staffEmail = $_ENV['STAFF_EMAIL'] ?? 'soniaanaya@ofiequipo.com.mx';
$ok = sendMail(
    $staffEmail,
    'Equipo OfiEquipo',
    "Solicitud vía asistente virtual {$folio} — {$nombre}",
    _mailBase('Solicitud vía asistente virtual', $cuerpo)
);

if (!$ok) {
    error_log("[Gabu] No se pudo enviar el correo de {$folio}");
    responder(502, ['success' => false, 'error' => 'No pudimos enviar tu solicitud en este momento. Intenta de nuevo o escríbenos por WhatsApp.']);
}

responder(200, ['success' => true]);
