<?php
/**
 * Cliente SMTP mínimo sin dependencias externas.
 * Lee credenciales de las variables de entorno del .env.
 */

function smtp_send(string $to, string $subject, string $body): bool {
    $host = $_ENV['SMTP_HOST'] ?? '';
    $port = (int)($_ENV['SMTP_PORT'] ?? 587);
    $user = $_ENV['SMTP_USER'] ?? '';
    $pass = $_ENV['SMTP_PASS'] ?? '';
    $from = $_ENV['SMTP_FROM'] ?? $user;
    $name = $_ENV['SMTP_FROM_NAME'] ?? 'Ofiequipo de Tampico';

    if ($host === '' || $user === '' || $pass === '') {
        error_log('[mailer] SMTP no configurado. Revisa SMTP_HOST, SMTP_USER y SMTP_PASS en .env');
        return false;
    }

    // Conexión: puerto 465 → SSL, resto → TLS con STARTTLS
    $ssl = ($port === 465);
    $addr = ($ssl ? 'ssl://' : '') . $host;

    $errno = 0; $errstr = '';
    $sock = @fsockopen($addr, $port, $errno, $errstr, 10);
    if (!$sock) {
        error_log("[mailer] No se pudo conectar a $host:$port — $errstr ($errno)");
        return false;
    }

    $read = fn() => fgets($sock, 512);
    $send = function(string $cmd) use ($sock, &$read): string {
        fwrite($sock, $cmd . "\r\n");
        return $read();
    };

    $read(); // Saludo del servidor

    if (!$ssl) {
        // EHLO → STARTTLS → upgrade a TLS
        $send("EHLO " . gethostname());
        $send("STARTTLS");
        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    }

    $ehlo = $send("EHLO " . gethostname());
    // Leer líneas multi-línea del EHLO
    while (substr($ehlo, 3, 1) === '-') { $ehlo = $read(); }

    // AUTH LOGIN
    $r = $send("AUTH LOGIN");
    if ((int)$r !== 334) { fclose($sock); error_log("[mailer] AUTH LOGIN falló: $r"); return false; }
    $send(base64_encode($user));
    $r = $read();
    if ((int)$r !== 334) { fclose($sock); error_log("[mailer] Usuario rechazado: $r"); return false; }
    $r = $send(base64_encode($pass));
    if ((int)$r !== 235) { fclose($sock); error_log("[mailer] Contraseña incorrecta: $r"); return false; }

    // Envío
    $send("MAIL FROM:<$user>");
    $send("RCPT TO:<$to>");
    $send("DATA");

    $subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromEncoded    = '=?UTF-8?B?' . base64_encode($name) . '?=';
    $msgId          = '<' . time() . '.' . bin2hex(random_bytes(6)) . '@' . $host . '>';
    $date           = date('r');

    $msg  = "Date: $date\r\n";
    $msg .= "From: $fromEncoded <$user>\r\n";
    $msg .= "To: <$to>\r\n";
    $msg .= "Message-ID: $msgId\r\n";
    $msg .= "Subject: $subjectEncoded\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n";
    $msg .= "\r\n";
    $msg .= chunk_split(base64_encode($body));
    $msg .= "\r\n.";

    $r = $send($msg);
    $send("QUIT");
    fclose($sock);

    $ok = (int)$r === 250;
    if (!$ok) error_log("[mailer] El servidor rechazó el mensaje: $r");
    return $ok;
}
