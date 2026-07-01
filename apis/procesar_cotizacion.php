<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/require_login.php';
require_once __DIR__ . '/../includes/mailer.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../cotizacion.php');
    exit;
}

// Carrito no vacío
$cart      = $_SESSION['cart'] ?? [];
$cartCount = array_sum(array_column($cart, 'cantidad'));
if (empty($cart)) {
    header('Location: ../carrito.php');
    exit;
}

// ── Sanitizar inputs ────────────────────────────────────────────
$nombre       = trim($_POST['nombre']       ?? '');
$empresa      = trim($_POST['empresa']      ?? '');
$email        = trim($_POST['email']        ?? '');
$telefono     = trim($_POST['telefono']     ?? '');
$mensaje      = trim($_POST['mensaje']      ?? '');
$dir_calle    = trim($_POST['dir_calle']    ?? '');
$dir_colonia  = trim($_POST['dir_colonia']  ?? '');
$dir_municipio= trim($_POST['dir_municipio']?? '');
$dir_cp       = trim($_POST['dir_cp']       ?? '');
$dir_refs     = trim($_POST['dir_refs']     ?? '');

// ── Validación servidor ─────────────────────────────────────────
$errors = [];
if (strlen($nombre) < 2)
    $errors['nombre']        = 'El nombre debe tener al menos 2 caracteres.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors['email']         = 'Ingresa un correo electrónico válido.';
if (strlen(preg_replace('/\D/', '', $telefono)) < 10)
    $errors['telefono']      = 'El teléfono debe tener al menos 10 dígitos.';
if (strlen($dir_calle) < 3)
    $errors['dir_calle']     = 'Ingresa la calle y número.';
if (empty($dir_municipio))
    $errors['dir_municipio'] = 'Selecciona el municipio.';

if (!empty($errors)) {
    $_SESSION['cot_errors'] = $errors;
    $_SESSION['cot_data']   = compact('nombre', 'empresa', 'email', 'telefono', 'mensaje',
                                       'dir_calle', 'dir_colonia', 'dir_municipio', 'dir_cp', 'dir_refs');
    header('Location: ../cotizacion.php');
    exit;
}

// ── Guardar en BD ───────────────────────────────────────────────
$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO cotizaciones (folio, nombre, empresa, email, telefono, mensaje,
                                   dir_calle, dir_colonia, dir_municipio, dir_cp, dir_refs)
         VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('ssssssssss', $nombre, $empresa, $email, $telefono, $mensaje,
                                    $dir_calle, $dir_colonia, $dir_municipio, $dir_cp, $dir_refs);
    $stmt->execute();
    $cid   = $conn->insert_id;
    $folio = 'COT-' . date('Y') . '-' . sprintf('%05d', $cid);
    $stmt->close();

    $conn->query(
        "UPDATE cotizaciones SET folio = '" . $conn->real_escape_string($folio) . "' WHERE id = $cid"
    );

    $is = $conn->prepare(
        "INSERT INTO cotizacion_items (cotizacion_id, producto_id, nombre_producto, cantidad, precio, imagen)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    foreach ($cart as $item) {
        $pid    = (int)($item['id']       ?? 0);
        $name   =       $item['nombre']   ?? '';
        $qty    = (int)($item['cantidad'] ?? 1);
        $precio = (float)($item['precio'] ?? 0);
        $img    =       $item['imagen']   ?? '';
        $is->bind_param('iisids', $cid, $pid, $name, $qty, $precio, $img);
        $is->execute();
    }
    $is->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('[cotizacion] ' . $e->getMessage());
    $_SESSION['cot_errors'] = ['_general' => 'Error al guardar la cotización. Intenta de nuevo más tarde.'];
    $_SESSION['cot_data']   = compact('nombre', 'empresa', 'email', 'telefono', 'mensaje');
    header('Location: ../cotizacion.php');
    exit;
}

// ── Construir tabla de productos para emails ────────────────────
$itemsHtml  = '';
$totalCat   = 0;
foreach ($cart as $item) {
    $n       = htmlspecialchars($item['nombre'] ?? '');
    $q       = (int)($item['cantidad'] ?? 1);
    $precio  = (float)($item['precio'] ?? 0);
    $sub     = $precio * $q;
    $totalCat += $sub;
    $precioStr = $precio > 0 ? '$' . number_format($precio, 2) : '—';
    $subStr    = $precio > 0 ? '$' . number_format($sub,    2) : '—';
    $itemsHtml .= "
        <tr>
          <td style='padding:11px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;line-height:1.4;'>{$n}</td>
          <td style='padding:11px 0;border-bottom:1px solid #f1f5f9;color:#475569;font-size:14px;text-align:center;width:50px;font-weight:600;'>{$q}</td>
          <td style='padding:11px 0;border-bottom:1px solid #f1f5f9;color:#475569;font-size:14px;text-align:right;width:90px;'>{$precioStr}</td>
          <td style='padding:11px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;text-align:right;width:90px;font-weight:600;'>{$subStr}</td>
        </tr>";
}
$totalStr = $totalCat > 0 ? '$' . number_format($totalCat, 2) : '—';
$itemsHtml .= "
    <tr>
      <td colspan='3' style='padding:12px 0 0;font-size:13px;color:#64748b;text-align:right;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;'>Total de referencia</td>
      <td style='padding:12px 0 0;font-size:15px;color:#1e3a8a;text-align:right;font-weight:800;'>{$totalStr}</td>
    </tr>";

$tableHtml = "
    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;border-collapse:collapse;'>
        <thead>
            <tr>
                <th style='font-size:11px;color:#94a3b8;font-weight:700;text-align:left;padding-bottom:8px;border-bottom:2px solid #e2e8f0;letter-spacing:0.05em;text-transform:uppercase;'>Producto</th>
                <th style='font-size:11px;color:#94a3b8;font-weight:700;text-align:center;padding-bottom:8px;border-bottom:2px solid #e2e8f0;width:50px;letter-spacing:0.05em;text-transform:uppercase;'>Cant.</th>
                <th style='font-size:11px;color:#94a3b8;font-weight:700;text-align:right;padding-bottom:8px;border-bottom:2px solid #e2e8f0;width:90px;letter-spacing:0.05em;text-transform:uppercase;'>P. Unit.</th>
                <th style='font-size:11px;color:#94a3b8;font-weight:700;text-align:right;padding-bottom:8px;border-bottom:2px solid #e2e8f0;width:90px;letter-spacing:0.05em;text-transform:uppercase;'>Subtotal</th>
            </tr>
        </thead>
        <tbody>{$itemsHtml}</tbody>
    </table>";

// ── Dirección formateada ─────────────────────────────────────────
$dirPartes = array_filter([$dir_calle, $dir_colonia ? 'Col. ' . $dir_colonia : '', $dir_municipio, $dir_cp ? 'C.P. ' . $dir_cp : '']);
$dirLinea  = implode(', ', $dirPartes);
$dirHtml   = "<div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 18px;margin-bottom:24px;'>"
           . "<div style='font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;'>Dirección de entrega</div>"
           . "<div style='font-size:14px;color:#0f172a;line-height:1.6;'>" . htmlspecialchars($dirLinea) . "</div>"
           . ($dir_refs ? "<div style='font-size:12px;color:#64748b;margin-top:4px;'>Ref: " . htmlspecialchars($dir_refs) . "</div>" : '')
           . "</div>";

// ── Email al cliente ────────────────────────────────────────────
$clientCuerpo = "
    <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 16px;'>
        Hola <strong style='color:#0f172a;'>" . htmlspecialchars($nombre) . "</strong>,
    </p>
    <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;'>
        Hemos recibido tu solicitud de cotización. Te contactaremos a la brevedad para confirmarte disponibilidad y precios.
    </p>
    <div style='background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:18px 22px;margin-bottom:24px;'>
        <div style='font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;'>Número de folio</div>
        <div style='font-size:26px;font-weight:800;color:#1e3a8a;letter-spacing:-0.5px;'>" . htmlspecialchars($folio) . "</div>
        <div style='font-size:12px;color:#64748b;margin-top:4px;'>Guarda este número para dar seguimiento a tu solicitud.</div>
    </div>
    {$dirHtml}
    {$tableHtml}
    <p style='color:#475569;font-size:14px;line-height:1.6;margin:0 0 8px;'>
        Si tienes preguntas o quieres dar seguimiento, contáctanos por:
    </p>
    <ul style='color:#475569;font-size:14px;line-height:1.8;margin:0 0 24px;padding-left:20px;'>
        <li>WhatsApp: <a href='https://wa.me/528331881814' style='color:#1e3a8a;font-weight:600;'>833 188 1814</a></li>
        <li>Teléfono: <strong>(833) 213-3837 | (833) 217-2047</strong></li>
        <li>Responde a este correo con tu folio</li>
    </ul>";

sendMail(
    $email,
    $nombre,
    "Solicitud de cotización {$folio} recibida — OfiEquipo",
    _mailBase('¡Solicitud recibida!', $clientCuerpo)
);

// ── Email al equipo Ofiequipo ───────────────────────────────────
$empresaRow = $empresa
    ? "<div style='margin-bottom:10px;'><span style='font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;'>Empresa</span><br><strong style='color:#0f172a;font-size:15px;'>" . htmlspecialchars($empresa) . "</strong></div>"
    : '';
$mensajeRow = $mensaje
    ? "<div style='margin-top:14px;padding-top:14px;border-top:1px solid #e2e8f0;'><span style='font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;'>Comentarios</span><br><p style='color:#0f172a;font-size:14px;margin:6px 0 0;line-height:1.6;'>" . nl2br(htmlspecialchars($mensaje)) . "</p></div>"
    : '';

$staffCuerpo = "
    <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 20px;'>Nueva solicitud de cotización recibida desde el sitio web:</p>

    <div style='background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px;'>
        <div style='margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #e2e8f0;'>
            <span style='font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;'>Folio</span><br>
            <strong style='color:#1e3a8a;font-size:22px;font-weight:800;'>" . htmlspecialchars($folio) . "</strong>
        </div>
        <div style='display:grid;grid-template-columns:1fr 1fr;gap:12px;'>
            <div>
                <span style='font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;'>Nombre</span><br>
                <strong style='color:#0f172a;font-size:15px;'>" . htmlspecialchars($nombre) . "</strong>
            </div>
            <div>
                <span style='font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;'>Teléfono</span><br>
                <a href='tel:" . htmlspecialchars($telefono) . "' style='color:#2563eb;font-size:15px;font-weight:600;text-decoration:none;'>" . htmlspecialchars($telefono) . "</a>
            </div>
            <div>
                <span style='font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;'>Correo</span><br>
                <a href='mailto:" . htmlspecialchars($email) . "' style='color:#2563eb;font-size:15px;text-decoration:none;'>" . htmlspecialchars($email) . "</a>
            </div>
            {$empresaRow}
        </div>
        {$mensajeRow}
    </div>

    <p style='color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:12px;'>Productos solicitados</p>
    {$tableHtml}

    " . _mailBtn('https://wa.me/52' . preg_replace('/\D/', '', $telefono), 'Responder por WhatsApp') . "
    <p style='color:#94a3b8;font-size:12px;text-align:center;margin-top:8px;'>
        También puedes responder directamente a este correo o llamar al cliente.
    </p>";

$staffEmail = $_ENV['STAFF_EMAIL'] ?? 'soniaanaya@ofiequipo.com.mx';
sendMail(
    $staffEmail,
    'Equipo OfiEquipo',
    "Nueva cotización {$folio} — " . $nombre . ($empresa ? " ({$empresa})" : ''),
    _mailBase("Nueva solicitud: {$folio}", $staffCuerpo)
);

// ── Guardar datos en sesión para la página de confirmación ──────
$_SESSION['last_cotizacion'] = [
    'folio'   => $folio,
    'nombre'  => $nombre,
    'empresa' => $empresa,
    'email'   => $email,
    'items'   => $cart,
];

// ── Vaciar carrito ──────────────────────────────────────────────
$_SESSION['cart'] = [];

header('Location: ../confirmacion_cotizacion.php');
exit;
