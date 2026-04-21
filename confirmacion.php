<?php
session_start();
require_once __DIR__ . '/apis/db.php';
require_once __DIR__ . '/includes/require_login.php';

$confirmacion = $_SESSION['paypal_confirmacion'] ?? null;
if (!$confirmacion) { header('Location: index.php'); exit; }

// Limpiar la sesión de confirmación para que no pueda recargarse
unset($_SESSION['paypal_confirmacion']);

$cartCount     = 0;
$totalProducts = 0;
$tp = $conn->query("SELECT COUNT(*) AS cnt FROM producto");
if ($tp) $totalProducts = $tp->fetch_assoc()['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Compra exitosa! — Ofiequipo de Tampico</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Navbar */
        .promo-banner p,.promo-banner .phone-numbers,.promo-banner * { color:white !important; margin:0; }
        .navbar-category-dropdown { position:relative; display:inline-block; }
        .navbar-category-toggle { background:transparent; color:var(--text-dark); border:none; font-size:16px; font-weight:500; cursor:pointer; display:flex; align-items:center; gap:6px; text-decoration:none; }
        .navbar-category-toggle:hover { color:var(--primary-blue); }
        .navbar-category-toggle .icon { transition:transform 0.3s; width:12px; height:12px; opacity:0.7; }
        .navbar-category-toggle.active .icon { transform:rotate(180deg); }
        .navbar-category-dropdown-menu { position:absolute; top:100%; left:0; background:white; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.15); z-index:1000; display:none; min-width:250px; max-height:500px; overflow-y:auto; margin-top:8px; padding:8px 0; }
        .navbar-category-dropdown-menu.active { display:block; }
        .navbar-category-item { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; color:var(--text-gray); text-decoration:none; border-bottom:1px solid #f3f4f6; font-size:14px; margin:0 8px; border-radius:6px; transition:all 0.2s; }
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

        /* ── Confetti canvas ── */
        #confetti-canvas {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none; z-index: 9999;
        }

        /* ── Page ── */
        .confirm-page {
            min-height: calc(100vh - 130px);
            display: flex; align-items: center; justify-content: center;
            padding: 60px 24px;
        }

        .confirm-card {
            background: white; border-radius: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
            max-width: 520px; width: 100%;
            padding: 52px 44px 44px;
            text-align: center;
            animation: cardIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
            animation-delay: 0.1s;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        /* ── Animated checkmark ── */
        .check-wrap {
            width: 90px; height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 28px;
            box-shadow: 0 8px 32px rgba(22,163,74,0.35);
            animation: checkPop 0.6s cubic-bezier(0.34,1.56,0.64,1) both;
            animation-delay: 0.4s;
            opacity: 0;
        }
        @keyframes checkPop {
            from { opacity: 0; transform: scale(0.3); }
            to   { opacity: 1; transform: scale(1); }
        }
        .check-svg { width: 46px; height: 46px; }
        .check-path {
            stroke: white; stroke-width: 3.5;
            stroke-linecap: round; stroke-linejoin: round;
            fill: none;
            stroke-dasharray: 60;
            stroke-dashoffset: 60;
            animation: drawCheck 0.5s ease forwards;
            animation-delay: 1s;
        }
        @keyframes drawCheck {
            to { stroke-dashoffset: 0; }
        }

        .confirm-title {
            font-size: 26px; font-weight: 800; color: #0f172a;
            margin-bottom: 10px;
            animation: fadeUp 0.5s ease both;
            animation-delay: 0.6s; opacity: 0;
        }
        .confirm-sub {
            font-size: 15px; color: #64748b; line-height: 1.6;
            margin-bottom: 32px;
            animation: fadeUp 0.5s ease both;
            animation-delay: 0.75s; opacity: 0;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Info box ── */
        .confirm-info {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 14px; padding: 20px 24px;
            margin-bottom: 28px; text-align: left;
            animation: fadeUp 0.5s ease both;
            animation-delay: 0.9s; opacity: 0;
        }
        .confirm-info-row {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 13px; padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .confirm-info-row:last-child { border-bottom: none; }
        .confirm-info-row span:first-child { color: #64748b; }
        .confirm-info-row span:last-child { font-weight: 700; color: #0f172a; }
        .confirm-total { font-size: 15px !important; }
        .confirm-total span:last-child { color: #16a34a !important; font-size: 18px !important; }

        /* ── Actions ── */
        .confirm-actions {
            display: flex; flex-direction: column; gap: 12px;
            animation: fadeUp 0.5s ease both;
            animation-delay: 1.05s; opacity: 0;
        }
        .btn-home {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px; border-radius: 13px;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: white; font-size: 15px; font-weight: 700;
            text-decoration: none; transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 18px rgba(37,99,235,0.35);
        }
        .btn-home:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(37,99,235,0.45); }
        .btn-orders {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 13px; border-radius: 13px;
            background: white; color: #1e3a8a;
            border: 1.5px solid #1e3a8a;
            font-size: 15px; font-weight: 600;
            text-decoration: none; transition: background 0.15s;
        }
        .btn-orders:hover { background: #eff6ff; }

        @media (max-width: 560px) {
            .confirm-card { padding: 36px 20px 28px; }
            .confirm-title { font-size: 22px; }
        }
    </style>
</head>
<body>

    <canvas id="confetti-canvas"></canvas>

    <div class="promo-banner">
        <p>Armado gratis | Entrega a domicilio (envío gratuito en zona metropolitana al sur de Tamaulipas) | Garantía segura por 1 año | Contacto: <span class="phone-numbers">(833) 213-3837 | (833) 217-2047</span></p>
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
                <button class="menu-toggle" aria-label="Toggle menu"><span></span><span></span><span></span></button>
            </div>
        </div>
    </header>

    <main class="confirm-page">
        <div class="confirm-card">

            <div class="check-wrap">
                <svg class="check-svg" viewBox="0 0 52 52">
                    <polyline class="check-path" points="14,27 22,36 38,18"/>
                </svg>
            </div>

            <h1 class="confirm-title">¡Pago exitoso!</h1>
            <p class="confirm-sub">
                Tu pedido fue recibido y está siendo procesado.<br>
                El equipo de Ofiequipo se pondrá en contacto contigo pronto.
            </p>

            <div class="confirm-info">
                <?php if (!empty($confirmacion['pedido_id'])): ?>
                <div class="confirm-info-row">
                    <span>Número de pedido</span>
                    <span>#<?= (int)$confirmacion['pedido_id'] ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($confirmacion['payer_name']) && trim($confirmacion['payer_name'])): ?>
                <div class="confirm-info-row">
                    <span>Pagado por</span>
                    <span><?= htmlspecialchars(trim($confirmacion['payer_name'])) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($confirmacion['payer_email'])): ?>
                <div class="confirm-info-row">
                    <span>Cuenta PayPal</span>
                    <span><?= htmlspecialchars($confirmacion['payer_email']) ?></span>
                </div>
                <?php endif; ?>
                <div class="confirm-info-row confirm-total">
                    <span>Total pagado</span>
                    <span>$<?= htmlspecialchars($confirmacion['monto']) ?> <?= htmlspecialchars($confirmacion['moneda'] ?? 'MXN') ?></span>
                </div>
            </div>

            <div class="confirm-actions">
                <a href="index.php" class="btn-home">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    Volver al inicio
                </a>
                <a href="mis_pedidos.php" class="btn-orders">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                    Ver mis pedidos
                </a>
            </div>

        </div>
    </main>

    <script>
    // ── Confetti ──────────────────────────────────────────────────
    (function() {
        const canvas = document.getElementById('confetti-canvas');
        const ctx    = canvas.getContext('2d');
        canvas.width  = window.innerWidth;
        canvas.height = window.innerHeight;
        window.addEventListener('resize', () => { canvas.width = window.innerWidth; canvas.height = window.innerHeight; });

        const colors  = ['#1e3a8a','#2563eb','#16a34a','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
        const pieces  = [];
        const TOTAL   = 140;

        for (let i = 0; i < TOTAL; i++) {
            pieces.push({
                x: Math.random() * canvas.width,
                y: Math.random() * -canvas.height,
                w: Math.random() * 10 + 5,
                h: Math.random() * 5 + 3,
                color: colors[Math.floor(Math.random() * colors.length)],
                rot: Math.random() * 360,
                rotSpeed: (Math.random() - 0.5) * 6,
                vx: (Math.random() - 0.5) * 3,
                vy: Math.random() * 3 + 2,
                alpha: 1,
            });
        }

        let frame = 0;
        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            pieces.forEach(p => {
                p.x  += p.vx;
                p.y  += p.vy;
                p.rot += p.rotSpeed;
                if (frame > 120) p.alpha = Math.max(0, p.alpha - 0.008);
                ctx.save();
                ctx.globalAlpha = p.alpha;
                ctx.translate(p.x + p.w / 2, p.y + p.h / 2);
                ctx.rotate(p.rot * Math.PI / 180);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
                if (p.y > canvas.height + 20) { p.y = -20; p.x = Math.random() * canvas.width; }
            });
            frame++;
            if (frame < 300) requestAnimationFrame(draw);
            else ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        // Iniciar con delay para que coincida con la animación del check
        setTimeout(draw, 800);
    })();

    // ── Navbar ────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        const mt = document.querySelector('.menu-toggle');
        const nv = document.querySelector('.nav');
        if (mt && nv) mt.addEventListener('click', () => { mt.classList.toggle('active'); nv.classList.toggle('active'); });
    });
    </script>

</body>
</html>
