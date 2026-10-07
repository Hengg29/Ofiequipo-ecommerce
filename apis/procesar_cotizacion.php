<?php
// Recibe la solicitud de cotización del modal "Mi cotización" (JSON) y la guarda
// ligada al usuario. Nunca recibe ni devuelve precios: los fija el admin después.
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/mailer.php';

function responder(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['success' => false, 'error' => 'Método no permitido.']);
}

// Sesión obligatoria: la cotización debe quedar en la cuenta del cliente
if (empty($_SESSION['user_id'])) {
    responder(401, ['success' => false, 'login' => true, 'error' => 'Inicia sesión para enviar tu cotización.']);
}

// Anti-CSRF: solo JSON (un formulario de otro sitio no puede enviarlo sin preflight CORS)
// y, si el navegador manda Origin, debe ser este mismo host.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
    || ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST))) {
    responder(403, ['success' => false, 'error' => 'Solicitud no permitida.']);
}

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) {
    responder(400, ['success' => false, 'error' => 'Solicitud inválida.']);
}

// ── Datos del formulario ────────────────────────────────────────
$nombre   = mb_substr(trim((string)($in['nombre']   ?? '')), 0, 150);
$empresa  = mb_substr(trim((string)($in['empresa']  ?? '')), 0, 150);
$email    = mb_substr(trim((string)($in['email']    ?? '')), 0, 150);
$telefono = mb_substr(trim((string)($in['telefono'] ?? '')), 0, 25);
$mensaje  = mb_substr(trim((string)($in['mensaje']  ?? '')), 0, 2000);

$errors = [];
if (mb_strlen($nombre) < 2)                     $errors['nombre']   = 'Ingresa tu nombre completo.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email']    = 'Ingresa un correo electrónico válido.';
if (strlen(preg_replace('/\D/', '', $telefono)) < 10) $errors['telefono'] = 'El teléfono debe tener al menos 10 dígitos.';

// ── Productos: solo id + cantidad; nombre e imagen salen de la BD ──
$cantidades = [];
foreach (array_slice((array)($in['items'] ?? []), 0, 100) as $it) {
    $pid = (int)($it['id'] ?? 0);
    $qty = max(1, min(999, (int)($it['cantidad'] ?? 1)));
    if ($pid > 0) $cantidades[$pid] = ($cantidades[$pid] ?? 0) + $qty;
}

$productos = [];
if ($cantidades) {
    $ids  = array_keys($cantidades);
    $ph   = implode(',', array_fill(0, count($ids), '?'));
    $st   = $conn->prepare("SELECT id, nombre, imagen FROM producto WHERE id IN ($ph) AND activo = 1");
    $st->bind_param(str_repeat('i', count($ids)), ...$ids);
    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $productos[] = [
            'id'       => (int)$row['id'],
            'nombre'   => $row['nombre'],
            'imagen'   => $row['imagen'] ?? '',
            'cantidad' => min(999, $cantidades[(int)$row['id']]),
        ];
    }
    $st->close();
}
if (!$productos) $errors['items'] = 'Tu cotización no tiene productos.';

if ($errors) {
    responder(422, ['success' => false, 'errors' => $errors, 'error' => reset($errors)]);
}

// ── Guardar en BD ───────────────────────────────────────────────
$userId = (int)$_SESSION['user_id'];
$conn->begin_transaction();
try {
    $st = $conn->prepare(
        "INSERT INTO cotizaciones (folio, usuario_id, nombre, empresa, email, telefono, mensaje)
         VALUES ('', ?, ?, ?, ?, ?, ?)"
    );
    $st->bind_param('isssss', $userId, $nombre, $empresa, $email, $telefono, $mensaje);
    $st->execute();
    $cid   = (int)$conn->insert_id;
    $folio = 'COT-' . date('Y') . '-' . sprintf('%05d', $cid);
    $st->close();

    $up = $conn->prepare("UPDATE cotizaciones SET folio = ? WHERE id = ?");
    $up->bind_param('si', $folio, $cid);
    $up->execute();
    $up->close();

    // precio queda en 0: el admin lo captura al negociar
    $is = $conn->prepare(
        "INSERT INTO cotizacion_items (cotizacion_id, producto_id, nombre_producto, cantidad, precio, imagen)
         VALUES (?, ?, ?, ?, 0, ?)"
    );
    foreach ($productos as $p) {
        $is->bind_param('iisis', $cid, $p['id'], $p['nombre'], $p['cantidad'], $p['imagen']);
        $is->execute();
    }
    $is->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('[cotizacion] ' . $e->getMessage());
    responder(500, ['success' => false, 'error' => 'No se pudo guardar la cotización. Intenta de nuevo más tarde.']);
}

// Responder primero; los correos se mandan después
$itemsResp = array_map(fn($p) => ['id' => $p['id'], 'nombre' => $p['nombre'], 'cantidad' => $p['cantidad']], $productos);
echo json_encode(['success' => true, 'folio' => $folio, 'items' => $itemsResp]);
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

// ── Tabla de productos para los correos (sin precios) ───────────
$filas = '';
foreach ($productos as $p) {
    $filas .= "<tr>
        <td style='padding:11px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;line-height:1.4;'>" . htmlspecialchars($p['nombre']) . "</td>
        <td style='padding:11px 0;border-bottom:1px solid #f1f5f9;color:#475569;font-size:14px;text-align:center;width:70px;font-weight:600;'>{$p['cantidad']}</td>
    </tr>";
}
$tablaHtml = "<table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;border-collapse:collapse;'>
    <thead><tr>
        <th style='font-size:11px;color:#94a3b8;font-weight:700;text-align:left;padding-bottom:8px;border-bottom:2px solid #e2e8f0;letter-spacing:0.05em;text-transform:uppercase;'>Producto</th>
        <th style='font-size:11px;color:#94a3b8;font-weight:700;text-align:center;padding-bottom:8px;border-bottom:2px solid #e2e8f0;width:70px;letter-spacing:0.05em;text-transform:uppercase;'>Cant.</th>
    </tr></thead>
    <tbody>{$filas}</tbody>
</table>";

$folioH = htmlspecialchars($folio);
$waNum  = WHATSAPP_NUMERO;
$waTxt  = '+' . substr($waNum, 0, 2) . ' ' . substr($waNum, 2);

// ── Correo al cliente ───────────────────────────────────────────
$clienteCuerpo = "
    <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 16px;'>
        Hola <strong style='color:#0f172a;'>" . htmlspecialchars($nombre) . "</strong>,
    </p>
    <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;'>
        Recibimos tu solicitud de cotización. Te contactaremos por WhatsApp para confirmar disponibilidad y condiciones.
        Cuando tu cotización esté lista te enviaremos un enlace para pagar en línea.
    </p>
    <div style='background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:18px 22px;margin-bottom:24px;'>
        <div style='font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;'>Número de folio</div>
        <div style='font-size:26px;font-weight:800;color:#1e3a8a;letter-spacing:-0.5px;'>{$folioH}</div>
        <div style='font-size:12px;color:#64748b;margin-top:4px;'>Puedes ver el estado en “Mis pedidos” dentro de tu cuenta.</div>
    </div>
    {$tablaHtml}
    <ul style='color:#475569;font-size:14px;line-height:1.8;margin:0 0 24px;padding-left:20px;'>
        <li>WhatsApp: <a href='https://wa.me/{$waNum}' style='color:#1e3a8a;font-weight:600;'>{$waTxt}</a></li>
        <li>Teléfono: <strong>(833) 213-3837 | (833) 217-2047</strong></li>
    </ul>";

sendMail($email, $nombre, "Solicitud de cotización {$folio} recibida — OfiEquipo",
    _mailBase('¡Solicitud recibida!', $clienteCuerpo));

// ── Correo al equipo ────────────────────────────────────────────
$fila = fn(string $label, string $valor) =>
    "<div style='margin-bottom:10px;'><span style='font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;'>{$label}</span><br>"
    . "<strong style='color:#0f172a;font-size:15px;'>" . htmlspecialchars($valor) . "</strong></div>";

$staffCuerpo = "
    <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 20px;'>Nueva solicitud de cotización desde el sitio web:</p>
    <div style='background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px;'>
        <div style='margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #e2e8f0;'>
            <span style='font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;'>Folio</span><br>
            <strong style='color:#1e3a8a;font-size:22px;font-weight:800;'>{$folioH}</strong>
        </div>"
        . $fila('Nombre', $nombre)
        . ($empresa ? $fila('Empresa', $empresa) : '')
        . $fila('Correo', $email)
        . $fila('Teléfono', $telefono)
        . ($mensaje ? "<div style='margin-top:14px;padding-top:14px;border-top:1px solid #e2e8f0;'><span style='font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;'>Mensaje</span><p style='color:#0f172a;font-size:14px;margin:6px 0 0;line-height:1.6;'>" . nl2br(htmlspecialchars($mensaje)) . "</p></div>" : '')
    . "</div>
    <p style='color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:12px;'>Productos solicitados</p>
    {$tablaHtml}
    " . _mailBtn('https://wa.me/52' . substr(preg_replace('/\D/', '', $telefono), -10), 'Responder por WhatsApp');

$staffEmail = $_ENV['STAFF_EMAIL'] ?? 'soniaanaya@ofiequipo.com.mx';
sendMail($staffEmail, 'Equipo OfiEquipo',
    "Nueva cotización {$folio} — {$nombre}" . ($empresa ? " ({$empresa})" : ''),
    _mailBase("Nueva solicitud: {$folio}", $staffCuerpo));
