<?php
require_once __DIR__ . '/apis/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    if (empty($nombre) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Por favor completa todos los campos.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        // TODO: implementar registro real con tabla de usuarios
        $error = 'El sistema de registro estará disponible muy pronto.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta — Ofiequipo de Tampico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue-mid:  #1e3a8a;
            --blue:      #2563eb;
            --text-dark: #0f172a;
            --text-gray: #475569;
            --text-light:#94a3b8;
            --border:    #e2e8f0;
            --bg-light:  #f8fafc;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body { display: flex; }

        /* ── LEFT PANEL ─────────────────────────── */
        .panel-brand {
            width: 44%;
            min-height: 100vh;
            background: linear-gradient(155deg, #0f172a 0%, #1e3a8a 60%, #1d4ed8 100%);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }

        .panel-brand::before {
            content: '';
            position: absolute;
            width: 520px; height: 520px;
            border-radius: 50%;
            background: rgba(255,255,255,0.045);
            top: -160px; right: -180px;
            pointer-events: none;
        }
        .panel-brand::after {
            content: '';
            position: absolute;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: rgba(255,255,255,0.045);
            bottom: -100px; left: -80px;
            pointer-events: none;
        }

        .brand-back {
            position: absolute;
            top: 32px; left: 48px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.2s;
            z-index: 2;
        }
        .brand-back:hover { color: rgba(255,255,255,0.9); }
        .brand-back svg { width: 16px; height: 16px; }

        .brand-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 100px 52px 64px;
            position: relative;
            z-index: 1;
        }

        .brand-icon {
            width: 54px; height: 54px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
        }
        .brand-icon svg { width: 26px; height: 26px; fill: white; }

        .brand-name {
            font-size: 26px;
            font-weight: 700;
            color: white;
            letter-spacing: 0.04em;
            line-height: 1;
        }
        .brand-name span {
            display: block;
            font-size: 11px;
            font-weight: 400;
            color: rgba(255,255,255,0.5);
            letter-spacing: 0.2em;
            margin-top: 5px;
        }

        .brand-divider {
            width: 36px; height: 2px;
            background: rgba(255,255,255,0.28);
            border-radius: 2px;
            margin: 26px 0;
        }

        .brand-tagline {
            font-size: 17px;
            font-weight: 300;
            color: rgba(255,255,255,0.82);
            line-height: 1.55;
            max-width: 270px;
            margin-bottom: 40px;
        }

        .brand-steps {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .brand-steps li {
            display: flex;
            align-items: flex-start;
            gap: 13px;
        }
        .step-num {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: rgba(255,255,255,0.13);
            border: 1px solid rgba(255,255,255,0.22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .step-text {
            font-size: 13px;
            color: rgba(255,255,255,0.65);
            line-height: 1.5;
        }
        .step-text strong {
            display: block;
            color: rgba(255,255,255,0.88);
            font-weight: 600;
            margin-bottom: 1px;
        }

        /* ── RIGHT PANEL ─────────────────────────── */
        .panel-form {
            flex: 1;
            min-height: 100vh;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            overflow-y: auto;
        }

        .form-card {
            width: 100%;
            max-width: 420px;
            border: 1.5px solid #2563eb;
            border-radius: 16px;
            padding: 40px 36px;
            box-shadow: 0 8px 32px rgba(37, 99, 235, 0.08);
        }

        .form-head { margin-bottom: 30px; }
        .form-head h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: -0.4px;
        }
        .form-head p {
            margin-top: 5px;
            font-size: 14px;
            color: var(--text-gray);
        }

        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 11px 13px;
            border: 1.5px solid #2563eb;
            border-radius: 9px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text-dark);
            background: var(--bg-light);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .form-group input:focus {
            border-color: var(--blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .form-group input::placeholder { color: var(--text-light); }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .pass-hint {
            font-size: 12px;
            color: var(--text-light);
            margin-top: -8px;
            margin-bottom: 16px;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--blue-mid) 0%, var(--blue) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(37,99,235,0.3);
            transition: transform 0.15s, box-shadow 0.15s;
            margin-top: 6px;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37,99,235,0.42);
        }
        .btn-submit:active { transform: none; }

        .form-terms {
            font-size: 12px;
            color: var(--text-light);
            text-align: center;
            margin-top: 12px;
            line-height: 1.5;
        }

        .form-sep {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 22px 0;
        }
        .form-sep hr {
            flex: 1;
            border: none;
            border-top: 1px solid var(--border);
        }
        .form-sep span {
            font-size: 12px;
            color: var(--text-light);
            white-space: nowrap;
        }

        .form-switch {
            text-align: center;
            font-size: 14px;
            color: var(--text-gray);
        }
        .form-switch a {
            color: var(--blue);
            font-weight: 600;
            text-decoration: none;
        }
        .form-switch a:hover { text-decoration: underline; }

        .alert {
            padding: 11px 14px;
            border-radius: 9px;
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 18px;
        }
        .alert-error  { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .alert-success{ background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }

        /* ── RESPONSIVE ──────────────────────────── */
        @media (max-width: 800px) {
            body { flex-direction: column; height: auto; }

            .panel-brand {
                width: 100%;
                min-height: auto;
            }
            .brand-back { top: 20px; left: 20px; }
            .brand-content {
                flex-direction: row;
                align-items: center;
                padding: 20px 20px 20px 20px;
                padding-top: 56px;
                gap: 14px;
            }
            .brand-tagline, .brand-steps, .brand-divider { display: none; }
            .brand-icon { width: 42px; height: 42px; border-radius: 11px; margin-bottom: 0; flex-shrink: 0; }
            .brand-icon svg { width: 20px; height: 20px; }
            .brand-name { font-size: 18px; }

            .panel-form { min-height: auto; padding: 32px 24px 48px; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
        }
    </style>
</head>
<body>

    <aside class="panel-brand">
        <a href="index.php" class="brand-back">
            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Volver al inicio
        </a>
        <div class="brand-content">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
            </div>
            <h2 class="brand-name">OFIEQUIPO<span>DE TAMPICO</span></h2>
            <div class="brand-divider"></div>
            <p class="brand-tagline">Únete y gestiona tus cotizaciones de forma fácil.</p>
            <ul class="brand-steps">
                <li>
                    <div class="step-num">1</div>
                    <div class="step-text">
                        <strong>Crea tu cuenta</strong>
                        Regístrate en menos de un minuto
                    </div>
                </li>
                <li>
                    <div class="step-num">2</div>
                    <div class="step-text">
                        <strong>Explora el catálogo</strong>
                        Descubre todo nuestro mobiliario
                    </div>
                </li>
                <li>
                    <div class="step-num">3</div>
                    <div class="step-text">
                        <strong>Solicita cotizaciones</strong>
                        Recibe atención personalizada
                    </div>
                </li>
            </ul>
        </div>
    </aside>

    <main class="panel-form">
        <div class="form-card">
            <div class="form-head">
                <h2>Crear una cuenta</h2>
                <p>Completa el formulario para comenzar.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre"
                           placeholder="Tu nombre completo"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                           required autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email"
                           placeholder="tucorreo@ejemplo.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required autocomplete="email">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password"
                               placeholder="Mínimo 6 caracteres"
                               minlength="6" required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="confirm">Confirmar</label>
                        <input type="password" id="confirm" name="confirm"
                               placeholder="Repite la contraseña"
                               required autocomplete="new-password">
                    </div>
                </div>
                <p class="pass-hint">Mínimo 6 caracteres.</p>

                <button type="submit" class="btn-submit">Crear Cuenta</button>
                <p class="form-terms">Al registrarte aceptas nuestros términos de uso y política de privacidad.</p>
            </form>

            <div class="form-sep">
                <hr><span>¿Ya tienes cuenta?</span><hr>
            </div>

            <p class="form-switch">
                <a href="login.php">Iniciar sesión</a>
            </p>
        </div>
    </main>

</body>
</html>
