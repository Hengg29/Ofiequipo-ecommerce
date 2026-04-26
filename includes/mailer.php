<?php
require_once __DIR__ . '/../vendor/autoload.php';

$_envMailer = __DIR__ . '/../.env';
if (file_exists($_envMailer)) {
    foreach (file($_envMailer, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_ln) {
        if (str_starts_with(trim($_ln), '#') || !str_contains($_ln, '=')) continue;
        [$_k, $_v] = explode('=', $_ln, 2);
        if (!array_key_exists(trim($_k), $_ENV)) $_ENV[trim($_k)] = trim($_v);
    }
}

function _mailBase(string $titulo, string $cuerpo): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 16px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.10);">
        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);padding:28px 40px;text-align:center;">
            <img src="https://ofiequipo.com.mx/oe/icono_logo.png" alt="OfiEquipo" width="180" style="max-width:180px;height:auto;display:inline-block;">
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="background:white;padding:40px 40px 32px;">
            <h2 style="margin:0 0 20px;font-size:22px;color:#0f172a;font-weight:700;">{$titulo}</h2>
            {$cuerpo}
          </td>
        </tr>
        <!-- Footer -->
        <tr>
          <td style="background:#f8fafc;padding:20px 40px;text-align:center;border-top:1px solid #e2e8f0;">
            <p style="margin:0;font-size:12px;color:#94a3b8;">OfiEquipo de Tampico &mdash; Este correo fue generado automáticamente, no respondas a este mensaje.</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

function _mailBtn(string $url, string $texto): string {
    return "<div style='text-align:center;margin:28px 0;'>"
         . "<a href='{$url}' style='display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#1e3a8a,#2563eb);"
         . "color:white;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;box-shadow:0 4px 14px rgba(37,99,235,0.35);'>"
         . "{$texto}</a></div>";
}

function sendMail(string $to, string $toName, string $subject, string $html): bool {
    try {
        $sg    = new \SendGrid($_ENV['SENDGRID_API_KEY'] ?? '');
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom($_ENV['SENDGRID_FROM_EMAIL'] ?? '', $_ENV['SENDGRID_FROM_NAME'] ?? 'OfiEquipo');
        $email->setSubject($subject);
        $email->addTo($to, $toName ?: $to);
        $plain = strip_tags(preg_replace('/<(style|head)[^>]*>.*?<\/\1>/si', '', $html));
        $plain = preg_replace("/\n{3,}/", "\n\n", trim($plain));
        $email->addContent('text/plain', $plain);
        $email->addContent('text/html', $html);
        set_error_handler(static fn() => true, E_DEPRECATED);
        $resp = $sg->send($email);
        restore_error_handler();
        return $resp->statusCode() >= 200 && $resp->statusCode() < 300;
    } catch (Throwable $e) {
        restore_error_handler();
        error_log('SendGrid error: ' . $e->getMessage());
        return false;
    }
}

function sendVerificacionEmail(string $to, string $nombre, string $token): bool {
    $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
    $link   = $appUrl . '/verificar_correo.php?token=' . urlencode($token);
    $cuerpo = "
        <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 16px;'>Hola <strong style='color:#0f172a;'>{$nombre}</strong>,</p>
        <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;'>
            Gracias por crear tu cuenta en OfiEquipo. Solo falta un paso: verifica tu correo electrónico haciendo clic en el botón de abajo.
        </p>"
        . _mailBtn($link, 'Verificar mi correo')
        . "<p style='color:#94a3b8;font-size:13px;text-align:center;margin:0;'>
            Este enlace expira en <strong>24 horas</strong>. Si no creaste esta cuenta, ignora este mensaje.
        </p>
        <div style='margin-top:24px;padding:16px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;'>
            <p style='margin:0;font-size:12px;color:#94a3b8;'>Si el botón no funciona, copia este enlace:<br>
            <span style='color:#2563eb;word-break:break-all;'>{$link}</span></p>
        </div>";
    return sendMail($to, $nombre, 'Verifica tu correo — OfiEquipo', _mailBase('Verifica tu correo electrónico', $cuerpo));
}

function sendResetPassword(string $to, string $nombre, string $token): bool {
    $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
    $link   = $appUrl . '/reset_contrasena.php?token=' . urlencode($token);
    $cuerpo = "
        <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 16px;'>Hola <strong style='color:#0f172a;'>{$nombre}</strong>,</p>
        <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;'>
            Recibimos una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el botón para crear una nueva contraseña.
        </p>"
        . _mailBtn($link, 'Restablecer contraseña')
        . "<p style='color:#94a3b8;font-size:13px;text-align:center;margin:0;'>
            Este enlace expira en <strong>1 hora</strong>. Si no solicitaste este cambio, puedes ignorar este mensaje &mdash; tu contraseña no cambió.
        </p>
        <div style='margin-top:24px;padding:16px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;'>
            <p style='margin:0;font-size:12px;color:#94a3b8;'>Si el botón no funciona, copia este enlace:<br>
            <span style='color:#2563eb;word-break:break-all;'>{$link}</span></p>
        </div>";
    return sendMail($to, $nombre, 'Restablecer contraseña — OfiEquipo', _mailBase('Solicitud de nueva contraseña', $cuerpo));
}

function sendConfirmacionPedido(string $to, string $nombre, array $pedido): bool {
    $pid      = htmlspecialchars($pedido['pedido_id'] ?? '');
    $monto    = htmlspecialchars($pedido['monto'] ?? '0.00');
    $moneda   = htmlspecialchars($pedido['moneda'] ?? 'MXN');
    $metodo   = htmlspecialchars($pedido['metodo'] ?? 'PayPal');
    $appUrl   = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');

    $itemsHtml = '';
    foreach ($pedido['items'] ?? [] as $it) {
        $n   = htmlspecialchars($it['nombre'] ?? '');
        $q   = (int)($it['cantidad'] ?? 1);
        $p   = number_format((float)($it['precio'] ?? 0), 2);
        $img = $it['imagen'] ?? '';
        $imgCell = $img
            ? "<img src='{$img}' alt='" . htmlspecialchars($it['nombre'] ?? '') . "' width='56' height='56' style='width:56px;height:56px;object-fit:cover;border-radius:8px;display:block;border:1px solid #e2e8f0;'>"
            : "<div style='width:56px;height:56px;background:#f1f5f9;border-radius:8px;border:1px solid #e2e8f0;'></div>";
        $itemsHtml .= "<tr>
            <td style='padding:12px 0;border-bottom:1px solid #f1f5f9;vertical-align:middle;'>
                <div style='display:flex;align-items:center;gap:12px;'>
                    {$imgCell}
                    <span style='color:#0f172a;font-size:14px;font-weight:500;'>{$n}</span>
                </div>
            </td>
            <td style='padding:12px 0;border-bottom:1px solid #f1f5f9;color:#475569;font-size:14px;text-align:center;vertical-align:middle;'>{$q}</td>
            <td style='padding:12px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;text-align:right;vertical-align:middle;font-weight:600;'>\${$p} {$moneda}</td>
        </tr>";
    }

    $cuerpo = "
        <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 16px;'>Hola <strong style='color:#0f172a;'>{$nombre}</strong>,</p>
        <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;'>
            Tu pago fue procesado exitosamente. Aquí está el resumen de tu pedido:
        </p>
        <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;'>
            <span style='font-size:22px;'>&#10003;</span>
            <div>
                <div style='color:#15803d;font-weight:700;font-size:15px;'>Pago confirmado</div>
                <div style='color:#16a34a;font-size:13px;'>Pedido #{$pid} &mdash; {$metodo}</div>
            </div>
        </div>
        " . ($itemsHtml ? "
        <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:20px;'>
            <thead>
                <tr>
                    <th style='font-size:12px;color:#94a3b8;font-weight:600;text-align:left;padding-bottom:8px;border-bottom:2px solid #e2e8f0;'>PRODUCTO</th>
                    <th style='font-size:12px;color:#94a3b8;font-weight:600;text-align:center;padding-bottom:8px;border-bottom:2px solid #e2e8f0;width:60px;'>CANT.</th>
                    <th style='font-size:12px;color:#94a3b8;font-weight:600;text-align:right;padding-bottom:8px;border-bottom:2px solid #e2e8f0;width:110px;'>PRECIO UNIT.</th>
                </tr>
            </thead>
            <tbody>{$itemsHtml}</tbody>
        </table>" : '') . "
        <div style='text-align:right;padding:12px 0;border-top:2px solid #0f172a;'>
            <span style='font-size:18px;font-weight:700;color:#0f172a;'>Total: \${$monto} {$moneda}</span>
        </div>
        <p style='color:#475569;font-size:14px;margin:24px 0 0;'>
            Pronto nos pondremos en contacto contigo para coordinar la entrega. Si tienes alguna pregunta, responde a este correo.
        </p>"
        . _mailBtn($appUrl . '/mis_pedidos.php', 'Ver mis pedidos');

    return sendMail($to, $nombre, "Confirmación de pedido #{$pid} — OfiEquipo", _mailBase('¡Pedido confirmado!', $cuerpo));
}
