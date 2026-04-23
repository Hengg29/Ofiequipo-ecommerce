<?php
session_start();
require_once __DIR__ . '/apis/db.php';
require_once __DIR__ . '/apis/mailer.php';
require_once __DIR__ . '/includes/require_login.php';

$userId    = (int)$_SESSION['user_id'];
$userEmail = (string)($_SESSION['user_email'] ?? '');

$cartCount     = array_sum(array_column($_SESSION['cart'] ?? [], 'cantidad'));
$totalProducts = 0;
$tp = $conn->query("SELECT COUNT(*) AS cnt FROM producto");
if ($tp) $totalProducts = $tp->fetch_assoc()['cnt'] ?? 0;

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_contrasena'])) {
    $actual    = $_POST['contrasena_actual']   ?? '';
    $nueva     = $_POST['contrasena_nueva']    ?? '';
    $confirmar = $_POST['contrasena_confirmar'] ?? '';

    if ($actual === '' || $nueva === '' || $confirmar === '') {
        $error = 'Completa todos los campos.';
    } elseif (strlen($nueva) < 8) {
        $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } elseif ($nueva !== $confirmar) {
        $error = 'La nueva contraseña y su confirmación no coinciden.';
    } else {
        $st = $conn->prepare("SELECT contrasena_hash FROM usuarios WHERE id = ?");
        $st->bind_param('i', $userId);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$row || !password_verify($actual, $row['contrasena_hash'])) {
            $error = 'La contraseña actual es incorrecta.';
        } else {
            $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE usuarios SET contrasena_hash = ? WHERE id = ?");
            $up->bind_param('si', $nuevoHash, $userId);
            $up->execute();
            $up->close();

            // Enviar correo de confirmación
            $asunto  = 'Contraseña actualizada — Ofiequipo de Tampico';
            $cuerpo  = "Hola,\n\nTu contraseña en Ofiequipo de Tampico fue actualizada exitosamente.\n\n";
            $cuerpo .= "Si no realizaste este cambio, contáctanos de inmediato respondiendo este correo.\n\n";
            $cuerpo .= "Equipo Ofiequipo de Tampico\n(833) 213-3837 | (833) 217-2047";
            smtp_send($userEmail, $asunto, $cuerpo);

            $success = 'Contraseña actualizada correctamente. Te enviamos un correo de confirmación.';
        }
    }
}

// Obtener fecha de registro
$stReg = $conn->prepare("SELECT creado_en FROM usuarios WHERE id = ?");
$stReg->bind_param('i', $userId);
$stReg->execute();
$regRow = $stReg->get_result()->fetch_assoc();
$stReg->close();
$fechaRegistro = $regRow['creado_en'] ?? null;

// Contar pedidos del usuario
$stmtC = $conn->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
$stmtC->bind_param('i', $userId); $stmtC->execute();
$cliente   = $stmtC->get_result()->fetch_assoc(); $stmtC->close();
$numPedidos = 0;
if ($cliente) {
    $stmtN = $conn->prepare("SELECT COUNT(*) AS c FROM pedidos WHERE cliente_id = ?");
    $stmtN->bind_param('i', $cliente['id']); $stmtN->execute();
    $numPedidos = (int)($stmtN->get_result()->fetch_assoc()['c'] ?? 0); $stmtN->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil — Ofiequipo de Tampico</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .promo-banner p,.promo-banner .phone-numbers,.promo-banner * { color:white !important; margin:0; font-weight:600; }
        .navbar-category-dropdown { position:relative; display:inline-block; }
        .navbar-category-toggle { background:transparent; color:var(--text-dark); border:none; font-size:16px; font-weight:500; cursor:pointer; display:flex; align-items:center; gap:6px; text-decoration:none; }
        .navbar-category-toggle:hover { color:var(--primary-blue); }
        .navbar-category-toggle .icon { transition:transform 0.3s; width:12px; height:12px; opacity:0.7; }
        .navbar-category-toggle.active .icon { transform:rotate(180deg); }
        .navbar-category-dropdown-menu { position:absolute; top:100%; left:0; background:white; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.15); z-index:1000; display:none; min-width:250px; max-height:500px; overflow-y:auto; margin-top:8px; padding:8px 0; }
        .navbar-category-dropdown-menu.active { display:block; animation:dropIn 0.2s ease; }
        @keyframes dropIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
        .navbar-category-item { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; color:var(--text-gray); text-decoration:none; border-bottom:1px solid #f3f4f6; font-size:14px; margin:0 8px; border-radius:6px; transition:all 0.2s; }
        .navbar-category-item:last-child { border-bottom:none; }
        .navbar-category-item:hover { background:#eff6ff; color:var(--primary-blue); }
        .navbar-category-main { display:flex; align-items:center; justify-content:space-between; padding:12px 20px; color:var(--text-dark); font-size:13px; font-weight:600; cursor:pointer; border-bottom:1px solid #f3f4f6; margin:0 8px; border-radius:6px; transition:background 0.15s; }
        .navbar-category-main:hover { background:#f8fafc; }
        .navbar-category-main .icon { width:12px; height:12px; opacity:0.5; transition:transform 0.2s; }
        .navbar-category-main.active .icon { transform:rotate(90deg); }
        .navbar-subcategory-menu { display:none; }
        .navbar-subcategory-menu.active { display:block; background:#f8fafc; }
        .navbar-subcategory-item { display:flex; align-items:center; justify-content:space-between; padding:10px 16px 10px 28px; color:var(--text-gray); text-decoration:none; font-size:13px; border-bottom:1px solid #f0f0f0; transition:all 0.15s; }
        .navbar-subcategory-item:hover { background:#eff6ff; color:var(--primary-blue); }
        .navbar-category-count { background:#f1f5f9; color:#64748b; font-size:11px; font-weight:600; padding:2px 7px; border-radius:10px; }
        .menu-toggle { display:none; }
        .nav { display:flex; gap:40px; align-items:center; }
        .logo { text-decoration:none; color:inherit; display:flex; align-items:center; gap:12px; transition:opacity 0.3s; }
        .logo:hover { opacity:0.8; }
        .logo h1 { margin:0; color:inherit; }
        @media (max-width:1024px) {
            .menu-toggle { display:flex; flex-direction:column; gap:4px; width:40px; height:40px; padding:8px; background:transparent; border:none; cursor:pointer; }
            .menu-toggle span { width:100%; height:3px; background:var(--text-dark,#1a1a2e); border-radius:2px; transition:all 0.3s; }
            .nav { display:none; position:absolute; top:100%; left:0; right:0; background:white; flex-direction:column; gap:0; padding:16px; box-shadow:0 8px 16px rgba(0,0,0,0.1); max-height:70vh; overflow-y:auto; border-radius:0 0 12px 12px; z-index:1000; }
            .nav.active { display:flex !important; }
            .header-actions { display:none; }
        }

        /* Page layout */
        .perfil-page { max-width: 720px; margin: 0 auto; padding: 40px 32px 80px; }
        .breadcrumb { display:flex; align-items:center; gap:6px; font-size:13px; color:#64748b; margin-bottom:28px; }
        .breadcrumb a { color:#1e3a8a; text-decoration:none; font-weight:500; }
        .breadcrumb a:hover { text-decoration:underline; }
        .breadcrumb-sep { color:#cbd5e1; }

        /* Profile header card */
        .profile-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            border-radius: 20px;
            padding: 32px;
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 24px;
            color: white;
        }
        .profile-avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 800;
            flex-shrink: 0;
            border: 3px solid rgba(255,255,255,0.25);
        }
        .profile-hero-info { flex: 1; min-width: 0; }
        .profile-hero-email { font-size: 18px; font-weight: 700; word-break: break-all; }
        .profile-hero-since { font-size: 13px; opacity: 0.65; margin-top: 4px; }
        .profile-stat {
            text-align: center;
            background: rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 14px 20px;
            flex-shrink: 0;
        }
        .profile-stat-num { font-size: 24px; font-weight: 800; }
        .profile-stat-lbl { font-size: 11px; opacity: 0.65; margin-top: 2px; }

        /* Section card */
        .section-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .section-head {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; gap: 12px;
        }
        .section-head-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: #eff6ff;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .section-head-icon svg { width: 18px; height: 18px; fill: #1e3a8a; }
        .section-head-text h2 { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 2px; }
        .section-head-text p  { font-size: 12px; color: #64748b; margin: 0; }
        .section-body { padding: 24px; }

        /* Form */
        .fld { margin-bottom: 18px; }
        .fld:last-child { margin-bottom: 0; }
        .fld label {
            display: block; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em;
            color: #64748b; margin-bottom: 7px;
        }
        .fld-wrap { position: relative; }
        .fld input {
            width: 100%; padding: 12px 44px 12px 14px;
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 14px; font-family: inherit; color: #0f172a;
            background: #f8fafc; outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .fld input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
            background: white;
        }
        .fld-eye {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #94a3b8; padding: 4px; line-height: 0;
            transition: color .15s;
        }
        .fld-eye:hover { color: #1e3a8a; }
        .fld-hint { font-size: 11px; color: #94a3b8; margin-top: 5px; }

        /* Password strength bar */
        .pwd-strength { margin-top: 8px; }
        .pwd-strength-bar {
            height: 4px; border-radius: 2px; background: #e2e8f0;
            overflow: hidden; margin-bottom: 4px;
        }
        .pwd-strength-fill {
            height: 100%; border-radius: 2px; width: 0;
            transition: width .3s, background .3s;
        }
        .pwd-strength-text { font-size: 11px; color: #94a3b8; }

        /* Submit button */
        .btn-save {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 28px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white; border: none; border-radius: 12px;
            font-size: 14px; font-weight: 700; font-family: inherit;
            cursor: pointer; transition: transform .15s, box-shadow .15s;
            box-shadow: 0 4px 16px rgba(37,99,235,.3);
            margin-top: 8px;
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,.4); }
        .btn-save svg { width: 16px; height: 16px; fill: white; }

        /* Alerts */
        .alert-ok, .alert-err {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 14px 18px; border-radius: 12px;
            font-size: 13px; font-weight: 500; margin-bottom: 20px;
        }
        .alert-ok { background: #dcfce7; border: 1px solid #86efac; color: #15803d; }
        .alert-err { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; }
        .alert-ok svg, .alert-err svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }
        .alert-ok svg { fill: #16a34a; }
        .alert-err svg { fill: #dc2626; }

        /* Info row (account data) */
        .info-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 0; border-bottom: 1px solid #f1f5f9;
        }
        .info-row:last-child { border-bottom: none; padding-bottom: 0; }
        .info-lbl { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
        .info-val { font-size: 14px; font-weight: 600; color: #0f172a; }

        /* Quick link */
        .quick-link {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px; border: 1.5px solid #e2e8f0;
            border-radius: 10px; text-decoration: none;
            font-size: 13px; font-weight: 600; color: #475569;
            transition: border-color .2s, color .2s, background .2s;
        }
        .quick-link:hover { border-color: #1e3a8a; color: #1e3a8a; background: #eff6ff; }
        .quick-link svg { width: 16px; height: 16px; fill: currentColor; flex-shrink: 0; }

        @media (max-width: 640px) {
            .perfil-page { padding: 24px 16px 60px; }
            .profile-hero { flex-direction: column; text-align: center; }
            .profile-stat { align-self: stretch; }
        }
    </style>
</head>
<body>

<div class="promo-banner">
    <p>Armado gratis | Entrega a domicilio | Garantía segura por 1 año | Contacto: <span class="phone-numbers">(833) 213-3837 | (833) 217-2047</span></p>
</div>

<header class="header" id="header">
    <div class="container">
        <div class="header-content">
            <a href="index.php" class="logo">
                <img src="icono_logo.png" alt="OFIEQUIPO Logo" class="logo-icon">
                <h1>OFIEQUIPO<span>DE TAMPICO</span></h1>
            </a>
            <nav class="nav">
                <a href="index.php" class="nav-link">Inicio</a>
                <a href="catalogo.php" class="nav-link">Catálogo</a>
                <a href="index.php#contacto" class="nav-link">Contacto</a>
            </nav>
            <div class="header-actions">
                <a href="tel:8331881814" class="btn btn-secondary btn-small">Llamar</a>
                <a href="https://wa.me/528331881814" class="btn btn-secondary btn-small">WhatsApp</a>
                <?php require_once __DIR__ . '/includes/user_avatar.php'; ?>
            </div>
            <button class="menu-toggle" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>

<main class="perfil-page">

    <nav class="breadcrumb">
        <a href="index.php">Inicio</a>
        <span class="breadcrumb-sep">›</span>
        <a href="mis_pedidos.php">Mis Pedidos</a>
        <span class="breadcrumb-sep">›</span>
        <span style="color:#0f172a; font-weight:500;">Mi Perfil</span>
    </nav>

    <?php if ($success): ?>
    <div class="alert-ok">
        <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert-err">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Hero / resumen de cuenta -->
    <div class="profile-hero">
        <?php
        $initials = strtoupper(mb_substr($userEmail, 0, 1));
        ?>
        <div class="profile-avatar"><?= $initials ?></div>
        <div class="profile-hero-info">
            <div class="profile-hero-email"><?= htmlspecialchars($userEmail) ?></div>
            <?php if ($fechaRegistro): ?>
            <div class="profile-hero-since">
                Miembro desde <?= date('d \d\e F \d\e Y', strtotime($fechaRegistro)) ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="profile-stat">
            <div class="profile-stat-num"><?= $numPedidos ?></div>
            <div class="profile-stat-lbl">Pedido<?= $numPedidos !== 1 ? 's' : '' ?></div>
        </div>
    </div>

    <!-- Información de la cuenta -->
    <div class="section-card">
        <div class="section-head">
            <div class="section-head-icon">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
            <div class="section-head-text">
                <h2>Información de la cuenta</h2>
                <p>Datos de tu cuenta registrada en Ofiequipo</p>
            </div>
        </div>
        <div class="section-body">
            <div class="info-row">
                <span class="info-lbl">Correo electrónico</span>
                <span class="info-val"><?= htmlspecialchars($userEmail) ?></span>
            </div>
            <?php if ($fechaRegistro): ?>
            <div class="info-row">
                <span class="info-lbl">Fecha de registro</span>
                <span class="info-val"><?= date('d/m/Y', strtotime($fechaRegistro)) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-lbl">Pedidos realizados</span>
                <span class="info-val"><?= $numPedidos ?></span>
            </div>
        </div>
    </div>

    <!-- Cambiar contraseña -->
    <div class="section-card">
        <div class="section-head">
            <div class="section-head-icon">
                <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
            </div>
            <div class="section-head-text">
                <h2>Cambiar contraseña</h2>
                <p>Recibirás un correo de confirmación en <?= htmlspecialchars($userEmail) ?></p>
            </div>
        </div>
        <div class="section-body">
            <form method="POST" action="perfil.php" id="formPwd" autocomplete="off">
                <input type="hidden" name="cambiar_contrasena" value="1">

                <div class="fld">
                    <label>Contraseña actual</label>
                    <div class="fld-wrap">
                        <input type="password" name="contrasena_actual" id="pwdActual"
                               placeholder="Tu contraseña actual" autocomplete="current-password" required>
                        <button type="button" class="fld-eye" onclick="togglePwd('pwdActual', this)" aria-label="Mostrar">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" id="eyeActual">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="fld">
                    <label>Nueva contraseña</label>
                    <div class="fld-wrap">
                        <input type="password" name="contrasena_nueva" id="pwdNueva"
                               placeholder="Mínimo 8 caracteres" autocomplete="new-password"
                               required oninput="checkStrength(this.value)">
                        <button type="button" class="fld-eye" onclick="togglePwd('pwdNueva', this)" aria-label="Mostrar">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <div class="pwd-strength" id="pwdStrengthWrap" style="display:none;">
                        <div class="pwd-strength-bar"><div class="pwd-strength-fill" id="pwdFill"></div></div>
                        <div class="pwd-strength-text" id="pwdText"></div>
                    </div>
                    <div class="fld-hint">Usa letras, números y símbolos para mayor seguridad.</div>
                </div>

                <div class="fld">
                    <label>Confirmar nueva contraseña</label>
                    <div class="fld-wrap">
                        <input type="password" name="contrasena_confirmar" id="pwdConfirmar"
                               placeholder="Repite la nueva contraseña" autocomplete="new-password"
                               required oninput="checkMatch()">
                        <button type="button" class="fld-eye" onclick="togglePwd('pwdConfirmar', this)" aria-label="Mostrar">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <div class="fld-hint" id="matchHint"></div>
                </div>

                <button type="submit" class="btn-save">
                    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    Guardar nueva contraseña
                </button>
            </form>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:4px;">
        <a href="mis_pedidos.php" class="quick-link">
            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
            Ver mis pedidos
        </a>
        <a href="catalogo.php" class="quick-link">
            <svg viewBox="0 0 24 24"><path d="M20 4H4v2l8 5 8-5V4zM4 20h16V8l-8 5-8-5v12z"/></svg>
            Ir al catálogo
        </a>
        <a href="logout.php" class="quick-link" style="color:#dc2626; border-color:#fca5a5;"
           onmouseover="this.style.background='#fef2f2';this.style.borderColor='#f87171';"
           onmouseout="this.style.background='';this.style.borderColor='#fca5a5';">
            <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
            Cerrar sesión
        </a>
    </div>

</main>

<script>
function togglePwd(inputId, btn) {
    const inp = document.getElementById(inputId);
    const isText = inp.type === 'text';
    inp.type = isText ? 'password' : 'text';
    btn.querySelector('svg').innerHTML = isText
        ? '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'
        : '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
}

function checkStrength(val) {
    const wrap = document.getElementById('pwdStrengthWrap');
    const fill = document.getElementById('pwdFill');
    const text = document.getElementById('pwdText');
    if (!val) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'block';

    let score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct: '20%', color: '#ef4444', label: 'Muy débil' },
        { pct: '40%', color: '#f97316', label: 'Débil' },
        { pct: '60%', color: '#eab308', label: 'Regular' },
        { pct: '80%', color: '#22c55e', label: 'Fuerte' },
        { pct: '100%', color: '#16a34a', label: 'Muy fuerte' },
    ];
    const lvl = levels[Math.min(score, 4)];
    fill.style.width = lvl.pct;
    fill.style.background = lvl.color;
    text.textContent = lvl.label;
    text.style.color = lvl.color;
}

function checkMatch() {
    const n = document.getElementById('pwdNueva').value;
    const c = document.getElementById('pwdConfirmar').value;
    const hint = document.getElementById('matchHint');
    if (!c) { hint.textContent = ''; return; }
    if (n === c) {
        hint.textContent = '✓ Las contraseñas coinciden';
        hint.style.color = '#16a34a';
    } else {
        hint.textContent = '✗ Las contraseñas no coinciden';
        hint.style.color = '#dc2626';
    }
}

// Navbar mobile toggle
document.addEventListener('DOMContentLoaded', function () {
    const mt = document.querySelector('.menu-toggle');
    const nv = document.querySelector('.nav');
    if (mt && nv) mt.addEventListener('click', () => { mt.classList.toggle('active'); nv.classList.toggle('active'); });
});
</script>
</body>
</html>
