<?php
session_start();
require_once __DIR__ . '/apis/db.php';
require_once __DIR__ . '/includes/require_login.php';

function getImageUrl($p) {
    if (empty($p)) return 'image.php?path=placeholder';
    $p = trim($p);
    if (preg_match('/^https?:\/\//i', $p)) return 'image.php?u=' . rawurlencode($p);
    if (filter_var($p, FILTER_VALIDATE_URL))  return 'image.php?u=' . rawurlencode($p);
    $p = str_replace('\\', '/', $p);
    $t = ltrim($p, '/');
    return 'image.php?path=' . implode('/', array_map('rawurlencode', explode('/', $t)));
}

$cart      = $_SESSION['cart'] ?? [];
$cartCount = array_sum(array_column($cart, 'cantidad'));
if (empty($cart)) { header('Location: carrito.php'); exit; }
$totalRef  = array_sum(array_map(fn($i) => (float)($i['precio'] ?? 0) * (int)($i['cantidad'] ?? 1), $cart));

// Flash de errores / datos previos del formulario
$errors   = $_SESSION['cot_errors'] ?? [];
$old      = $_SESSION['cot_data']   ?? [];
unset($_SESSION['cot_errors'], $_SESSION['cot_data']);

// Navbar data
$search_query  = '';
$categoria_id  = 0;
$totalProducts = 0;
$tp = $conn->query("SELECT COUNT(*) AS cnt FROM producto");
if ($tp) $totalProducts = $tp->fetch_assoc()['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Cotización — Ofiequipo de Tampico</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="shortcut icon" type="image/png" href="icono_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* ── Navbar ──────────────────────────────────────────────── */
        .promo-banner p { color: white !important; margin: 0; }
        .promo-banner .phone-numbers { color: white !important; font-weight: 600; }
        .promo-banner * { color: white !important; }
        .navbar-category-dropdown { position: relative; display: inline-block; }
        .navbar-category-toggle { background: transparent; color: var(--text-dark); border: none; font-size: 16px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px; text-decoration: none; }
        .navbar-category-toggle:hover { color: var(--primary-blue); }
        .navbar-category-toggle .icon { transition: transform 0.3s; width: 12px; height: 12px; opacity: 0.7; }
        .navbar-category-toggle.active .icon { transform: rotate(180deg); }
        .navbar-category-dropdown-menu { position: absolute; top: 100%; left: 0; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.15); z-index: 1000; display: none; min-width: 250px; max-height: 500px; overflow-y: auto; margin-top: 8px; padding: 8px 0; }
        .navbar-category-dropdown-menu.active { display: block; animation: dropIn 0.2s ease; }
        @keyframes dropIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
        .navbar-category-item { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; color: var(--text-gray); text-decoration: none; border-bottom: 1px solid #f3f4f6; font-size: 14px; margin: 0 8px; border-radius: 6px; transition: all 0.2s; }
        .navbar-category-item:last-child { border-bottom: none; }
        .navbar-category-item:hover { background: #eff6ff; color: var(--primary-blue); }
        .navbar-category-main { display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; color: var(--text-dark); font-size: 13px; font-weight: 600; cursor: pointer; border-bottom: 1px solid #f3f4f6; margin: 0 8px; border-radius: 6px; transition: background 0.15s; }
        .navbar-category-main:hover { background: #f8fafc; }
        .navbar-category-main .icon { width: 12px; height: 12px; opacity: 0.5; transition: transform 0.2s; }
        .navbar-category-main.active .icon { transform: rotate(90deg); }
        .navbar-subcategory-menu { display: none; }
        .navbar-subcategory-menu.active { display: block; background: #f8fafc; }
        .navbar-subcategory-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 16px 10px 28px; color: var(--text-gray); text-decoration: none; font-size: 13px; border-bottom: 1px solid #f0f0f0; transition: all 0.15s; }
        .navbar-subcategory-item:hover { background: #eff6ff; color: var(--primary-blue); }
        .navbar-category-count { background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 10px; }
        .menu-toggle { display: none; }
        .nav { display: flex; gap: 40px; align-items: center; }
        @media (max-width: 1024px) {
            .menu-toggle { display: flex; flex-direction: column; gap: 4px; width: 40px; height: 40px; padding: 8px; background: transparent; border: none; cursor: pointer; }
            .menu-toggle span { width: 100%; height: 3px; background: var(--text-dark,#1a1a2e); border-radius: 2px; transition: all 0.3s; }
            .menu-toggle.active span:nth-child(1) { transform: rotate(45deg) translate(5px,5px); }
            .menu-toggle.active span:nth-child(2) { opacity: 0; }
            .menu-toggle.active span:nth-child(3) { transform: rotate(-45deg) translate(7px,-6px); }
            .nav { display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; flex-direction: column; gap: 0; padding: 16px; box-shadow: 0 8px 16px rgba(0,0,0,0.1); max-height: 70vh; overflow-y: auto; border-radius: 0 0 12px 12px; z-index: 1000; }
            .nav.active { display: flex !important; }
            .nav a { padding: 12px 16px; color: var(--text-dark); text-decoration: none; border-bottom: 1px solid #f0f0f0; }
            .nav a:hover { background: #f8f9fa; color: var(--primary-blue); }
            .header-actions { display: none; }
        }
        .logo { text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px; transition: opacity 0.3s; }
        .logo:hover { opacity: 0.8; }
        .logo h1 { margin: 0; color: inherit; }

        /* ── Page ────────────────────────────────────────────────── */
        .cot-page { max-width: 1100px; margin: 0 auto; padding: 40px 32px 80px; }

        .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #64748b; margin-bottom: 32px; }
        .breadcrumb a { color: var(--primary-blue,#1e3a8a); text-decoration: none; font-weight: 500; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb-sep { color: #cbd5e1; }

        /* Progress steps */
        .cot-steps { display: flex; align-items: center; margin-bottom: 40px; }
        .step { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: #94a3b8; white-space: nowrap; }
        .step.active { color: #1e3a8a; }
        .step.done   { color: #16a34a; }
        .step-num { width: 28px; height: 28px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .step.active .step-num { background: #1e3a8a; color: white; }
        .step.done   .step-num { background: #16a34a; color: white; }
        .step-line { flex: 1; height: 2px; background: #e2e8f0; margin: 0 12px; max-width: 60px; }
        .step-line.done { background: #16a34a; }

        /* Layout */
        .cot-layout { display: grid; grid-template-columns: 1fr 360px; gap: 32px; align-items: start; }

        /* Form card */
        .form-card { background: white; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 16px rgba(0,0,0,0.04); overflow: hidden; }
        .form-card-head { padding: 20px 28px; border-bottom: 1px solid #e2e8f0; font-size: 15px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        .form-card-head svg { width: 20px; height: 20px; fill: #1e3a8a; flex-shrink: 0; }
        .form-card-body { padding: 28px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 18px; position: relative; }
        .form-group:last-child { margin-bottom: 0; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }
        .form-group .optional { font-size: 10px; font-weight: 500; text-transform: none; letter-spacing: 0; color: #94a3b8; margin-left: 4px; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 12px 14px;
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 14px; font-family: inherit; color: #0f172a;
            background: #f8fafc; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            box-sizing: border-box;
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2394a3b8' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 36px; cursor: pointer; }
        .form-group select.is-invalid { border-color: #ef4444; background-color: #fff5f5; }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); background: white;
        }
        .form-group input.is-valid   { border-color: #16a34a; background: white; }
        .form-group input.is-invalid { border-color: #ef4444; background: #fff5f5; }
        .form-group input.is-valid:focus   { box-shadow: 0 0 0 3px rgba(22,163,74,0.12); }
        .form-group input.is-invalid:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.12); }
        .field-error { font-size: 12px; color: #dc2626; margin-top: 5px; display: none; }
        .field-error.show { display: block; }

        /* Location button */
        .btn-location {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px;
            background: white; color: #1e3a8a;
            border: 1.5px solid #1e3a8a; border-radius: 10px;
            font-size: 13px; font-weight: 600; font-family: inherit; cursor: pointer;
            transition: background 0.15s, box-shadow 0.15s;
            margin-bottom: 20px;
        }
        .btn-location:hover { background: #eff6ff; box-shadow: 0 2px 8px rgba(30,58,138,0.12); }
        .btn-location.loading { opacity: 0.6; pointer-events: none; }
        .btn-location svg { width: 16px; height: 16px; fill: #1e3a8a; flex-shrink: 0; }
        .location-note { font-size: 12px; color: #94a3b8; margin-bottom: 20px; margin-top: -12px; }
        .location-ok {
            display: none; align-items: center; gap: 6px;
            font-size: 12px; color: #16a34a; font-weight: 600;
            background: #dcfce7; border-radius: 8px; padding: 8px 12px;
            margin-bottom: 16px;
        }
        .location-ok.show { display: flex; }
        .location-ok svg { width: 14px; height: 14px; fill: #16a34a; }

        /* Global error alert */
        .alert-error {
            background: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 12px;
            padding: 14px 18px; margin-bottom: 24px;
            display: flex; align-items: flex-start; gap: 10px;
            font-size: 14px; color: #dc2626;
        }
        .alert-error svg { width: 18px; height: 18px; fill: #dc2626; flex-shrink: 0; margin-top: 1px; }

        /* Submit button */
        .btn-submit {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 16px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white; border: none; border-radius: 13px;
            font-size: 16px; font-weight: 700; font-family: inherit; cursor: pointer;
            box-shadow: 0 4px 18px rgba(37,99,235,0.35);
            transition: transform 0.15s, box-shadow 0.15s, opacity 0.2s;
            margin-top: 28px;
        }
        .btn-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(37,99,235,0.45); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-submit svg { width: 18px; height: 18px; fill: white; flex-shrink: 0; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .btn-submit.loading svg { animation: spin 0.8s linear infinite; }

        /* Sidebar — product summary */
        .cot-summary { background: white; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 16px rgba(0,0,0,0.04); position: sticky; top: 100px; overflow: hidden; }
        .cot-summary-head { background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); padding: 18px 22px; color: white; }
        .cot-summary-head h3 { font-size: 15px; font-weight: 700; margin: 0 0 3px; }
        .cot-summary-head p { font-size: 12px; opacity: 0.7; margin: 0; }
        .cot-summary-body { padding: 16px; }

        .cot-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .cot-item:last-child { border-bottom: none; }
        .cot-item-img { width: 52px; height: 52px; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: contain; padding: 4px; background: white; flex-shrink: 0; }
        .cot-item-info { flex: 1; min-width: 0; }
        .cot-item-name { font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .cot-item-qty { font-size: 12px; color: #64748b; margin-top: 2px; }
        .cot-divider { border: none; border-top: 1px solid #e2e8f0; margin: 12px 0; }

        .cot-stat { display: flex; justify-content: space-between; font-size: 13px; color: #475569; margin-bottom: 8px; }
        .cot-stat strong { color: #0f172a; }

        .cot-note {
            display: flex; align-items: flex-start; gap: 8px;
            font-size: 12px; color: #475569; line-height: 1.5;
            background: #f8fafc; border-radius: 10px; padding: 12px 14px;
            margin-top: 16px;
        }
        .cot-note svg { width: 15px; height: 15px; fill: #64748b; flex-shrink: 0; margin-top: 1px; }

        @media (max-width: 900px) { .cot-layout { grid-template-columns: 1fr; } .cot-summary { position: static; } }
        @media (max-width: 640px) { .cot-page { padding: 24px 16px 60px; } .form-row { grid-template-columns: 1fr; } .cot-steps { gap: 0; } .step span { display: none; } }
    </style>
</head>
<body>

    <!-- PROMO BANNER -->
    <div class="promo-banner">
        <p>Armado gratis | Entrega a domicilio (envío gratuito en zona metropolitana al sur de Tamaulipas) | Garantía segura por 1 año | Contacto: <span class="phone-numbers">(833) 213-3837 | (833) 217-2047</span></p>
    </div>

    <!-- HEADER -->
    <header class="header" id="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <img src="icono_logo.png" alt="OFIEQUIPO Logo" class="logo-icon">
                    <h1>OFIEQUIPO<span>DE TAMPICO</span></h1>
                </a>
                <nav class="nav">
                    <a href="index.php" class="nav-link">Inicio</a>
                    <div class="navbar-category-dropdown">
                        <a href="#" class="navbar-category-toggle" id="navbarCategoryToggle">
                            Productos
                            <svg class="icon" viewBox="0 0 24 24" fill="none">
                                <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <div class="navbar-category-dropdown-menu" id="navbarCategoryDropdown">
                            <a href="catalogo.php" class="navbar-category-item">
                                Todos los productos
                                <span class="navbar-category-count"><?= $totalProducts ?></span>
                            </a>
                            <?php
                            $mainCategories = ['Sillería','Almacenaje','Línea Italia','Escritorios','Metálico','Líneas'];
                            $mainCatsData   = [];
                            foreach ($mainCategories as $n) {
                                $s = $conn->prepare("SELECT id, nombre FROM categoria WHERE nombre = ? AND parent_id IS NULL");
                                $s->bind_param("s",$n); $s->execute();
                                $r = $s->get_result()->fetch_assoc(); $s->close();
                                if ($r) $mainCatsData[] = $r;
                            }
                            if (empty($mainCatsData)) $mainCatsData=[['id'=>1,'nombre'=>'Sillería'],['id'=>9,'nombre'=>'Almacenaje'],['id'=>13,'nombre'=>'Línea Italia'],['id'=>19,'nombre'=>'Escritorios'],['id'=>28,'nombre'=>'Metálico'],['id'=>39,'nombre'=>'Líneas']];
                            foreach ($mainCatsData as $mc):
                                $ss=$conn->prepare("SELECT id,nombre FROM categoria WHERE parent_id=? ORDER BY nombre");
                                $ss->bind_param("i",$mc['id']); $ss->execute();
                                $sub=$ss->get_result()->fetch_all(MYSQLI_ASSOC); $ss->close();
                                if(!empty($sub)):
                            ?>
                                <div class="navbar-category-group">
                                    <div class="navbar-category-main"><?= htmlspecialchars($mc['nombre']) ?>
                                        <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </div>
                                    <div class="navbar-subcategory-menu">
                                        <?php foreach($sub as $sc): $ct=$conn->prepare("SELECT COUNT(*) AS c FROM producto WHERE categoria_id=?"); $ct->bind_param("i",$sc['id']); $ct->execute(); $n=$ct->get_result()->fetch_assoc()['c']??0; $ct->close(); ?>
                                        <a href="catalogo.php?categoria=<?=(int)$sc['id']?>" class="navbar-subcategory-item"><?=htmlspecialchars($sc['nombre'])?><span class="navbar-category-count"><?=$n?></span></a>
                                        <?php endforeach ?>
                                    </div>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                    <a href="catalogo.php" class="nav-link">Catálogo</a>
                    <a href="index.php#contacto" class="nav-link">Contacto</a>
                </nav>
                <div class="header-actions">
                    <a href="tel:8331881814" class="btn btn-secondary btn-small">Llamar</a>
                    <a href="https://wa.me/528331881814" class="btn btn-secondary btn-small">WhatsApp</a>
                    <?php require_once __DIR__ . '/includes/user_avatar.php'; ?>
                    <a href="carrito.php" class="btn btn-secondary btn-small" style="display:inline-flex;align-items:center;gap:6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM5.82 5H21l-1.68 8.39c-.16.79-.84 1.36-1.64 1.36H8.08c-.8 0-1.49-.57-1.64-1.36L5 5H3V3H5.82z"/></svg>
                        Carrito
                        <?php if ($cartCount > 0): ?>
                        <span style="background:#ef4444;color:white;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;padding:0 3px;"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <button class="menu-toggle" aria-label="Toggle menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <main class="cot-page">

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">Inicio</a>
            <span class="breadcrumb-sep">›</span>
            <a href="catalogo.php">Catálogo</a>
            <span class="breadcrumb-sep">›</span>
            <a href="carrito.php">Mi Carrito</a>
            <span class="breadcrumb-sep">›</span>
            <span style="color:#0f172a;font-weight:500;">Cotización</span>
        </nav>

        <!-- Progress -->
        <div class="cot-steps">
            <div class="step done">
                <div class="step-num">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="white"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </div>
                <span>Carrito</span>
            </div>
            <div class="step-line done"></div>
            <div class="step active">
                <div class="step-num">2</div>
                <span>Cotización</span>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-num">3</div>
                <span>Confirmación</span>
            </div>
        </div>

        <div class="cot-layout">

            <!-- FORM -->
            <div>
                <?php if (!empty($errors['_general'])): ?>
                <div class="alert-error">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    <?= htmlspecialchars($errors['_general']) ?>
                </div>
                <?php endif; ?>

                <form id="cotForm" method="POST" action="apis/procesar_cotizacion.php" novalidate>

                    <div class="form-card">
                        <div class="form-card-head">
                            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            Datos de contacto
                        </div>
                        <div class="form-card-body">

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nombre">Nombre completo *</label>
                                    <input type="text" id="nombre" name="nombre"
                                           placeholder="Juan García López"
                                           value="<?= htmlspecialchars($old['nombre'] ?? '') ?>"
                                           autocomplete="name" required
                                           class="<?= isset($errors['nombre']) ? 'is-invalid' : '' ?>">
                                    <div class="field-error <?= isset($errors['nombre']) ? 'show' : '' ?>" id="err-nombre">
                                        <?= htmlspecialchars($errors['nombre'] ?? 'El nombre es requerido.') ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="empresa">
                                        Empresa / Institución
                                        <span class="optional">(opcional)</span>
                                    </label>
                                    <input type="text" id="empresa" name="empresa"
                                           placeholder="Mi Empresa S.A. de C.V."
                                           value="<?= htmlspecialchars($old['empresa'] ?? '') ?>"
                                           autocomplete="organization">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Correo electrónico *</label>
                                    <input type="email" id="email" name="email"
                                           placeholder="tucorreo@ejemplo.com"
                                           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                                           autocomplete="email" required
                                           class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>">
                                    <div class="field-error <?= isset($errors['email']) ? 'show' : '' ?>" id="err-email">
                                        <?= htmlspecialchars($errors['email'] ?? 'Ingresa un correo válido.') ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="telefono">Teléfono *</label>
                                    <input type="tel" id="telefono" name="telefono"
                                           placeholder="833 123 4567"
                                           value="<?= htmlspecialchars($old['telefono'] ?? '') ?>"
                                           autocomplete="tel" required
                                           class="<?= isset($errors['telefono']) ? 'is-invalid' : '' ?>">
                                    <div class="field-error <?= isset($errors['telefono']) ? 'show' : '' ?>" id="err-telefono">
                                        <?= htmlspecialchars($errors['telefono'] ?? 'Ingresa un teléfono de al menos 10 dígitos.') ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom:0;">
                                <label for="mensaje">
                                    Comentarios adicionales
                                    <span class="optional">(opcional)</span>
                                </label>
                                <textarea id="mensaje" name="mensaje"
                                          placeholder="Especificaciones de color, cantidades adicionales, preguntas sobre disponibilidad, plazos de entrega…"><?= htmlspecialchars($old['mensaje'] ?? '') ?></textarea>
                            </div>

                        </div>
                    </div>

                    <!-- Sección dirección de entrega -->
                    <div class="form-card" style="margin-top:20px;">
                        <div class="form-card-head">
                            <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:#1e3a8a;flex-shrink:0;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            Dirección de entrega
                        </div>
                        <div class="form-card-body">

                            <!-- Location button -->
                            <button type="button" class="btn-location" id="btnLocation" onclick="useMyLocation()">
                                <svg viewBox="0 0 24 24"><path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3c-.46-4.17-3.77-7.48-7.94-7.94V1h-2v2.06C6.83 3.52 3.52 6.83 3.06 11H1v2h2.06c.46 4.17 3.77 7.48 7.94 7.94V23h2v-2.06c4.17-.46 7.48-3.77 7.94-7.94H23v-2h-2.06z"/></svg>
                                Usar mi ubicación actual
                            </button>
                            <p class="location-note">Se usará tu GPS para llenar los campos automáticamente. Puedes editarlos después.</p>

                            <div class="location-ok" id="locationOk">
                                <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                ¡Ubicación detectada! Revisa y ajusta si es necesario.
                            </div>

                            <div class="form-group">
                                <label for="dir_calle">Calle y número *</label>
                                <input type="text" id="dir_calle" name="dir_calle"
                                       placeholder="Av. Hidalgo 123 Int. 4"
                                       value="<?= htmlspecialchars($old['dir_calle'] ?? '') ?>"
                                       autocomplete="street-address" required
                                       class="<?= isset($errors['dir_calle']) ? 'is-invalid' : '' ?>">
                                <div class="field-error <?= isset($errors['dir_calle']) ? 'show' : '' ?>" id="err-dir_calle">
                                    Ingresa la calle y número.
                                </div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                <div class="form-group">
                                    <label for="dir_colonia">Colonia</label>
                                    <input type="text" id="dir_colonia" name="dir_colonia"
                                           placeholder="Col. Centro"
                                           value="<?= htmlspecialchars($old['dir_colonia'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="dir_cp">Código postal</label>
                                    <input type="text" id="dir_cp" name="dir_cp"
                                           placeholder="89000"
                                           value="<?= htmlspecialchars($old['dir_cp'] ?? '') ?>"
                                           maxlength="5">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="dir_municipio">Municipio / Ciudad *</label>
                                <select id="dir_municipio" name="dir_municipio" required
                                        class="<?= isset($errors['dir_municipio']) ? 'is-invalid' : '' ?>">
                                    <option value="">— Selecciona el municipio —</option>
                                    <optgroup label="Zona metropolitana (envío gratis)">
                                        <option value="Tampico"       <?= ($old['dir_municipio'] ?? '') === 'Tampico'       ? 'selected':'' ?>>Tampico</option>
                                        <option value="Ciudad Madero" <?= ($old['dir_municipio'] ?? '') === 'Ciudad Madero' ? 'selected':'' ?>>Ciudad Madero</option>
                                        <option value="Altamira"      <?= ($old['dir_municipio'] ?? '') === 'Altamira'      ? 'selected':'' ?>>Altamira</option>
                                    </optgroup>
                                    <optgroup label="Zona extendida (gratis a partir de $5,000)">
                                        <option value="Pueblo Viejo"  <?= ($old['dir_municipio'] ?? '') === 'Pueblo Viejo'  ? 'selected':'' ?>>Pueblo Viejo</option>
                                        <option value="Pánuco"        <?= ($old['dir_municipio'] ?? '') === 'Pánuco'        ? 'selected':'' ?>>Pánuco</option>
                                    </optgroup>
                                    <optgroup label="Otro">
                                        <option value="Otro"          <?= ($old['dir_municipio'] ?? '') === 'Otro'          ? 'selected':'' ?>>Otro municipio</option>
                                    </optgroup>
                                </select>
                                <div class="field-error <?= isset($errors['dir_municipio']) ? 'show' : '' ?>" id="err-dir_municipio">
                                    Selecciona el municipio.
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom:0;">
                                <label for="dir_refs">
                                    Referencias adicionales
                                    <span class="optional">(opcional)</span>
                                </label>
                                <input type="text" id="dir_refs" name="dir_refs"
                                       placeholder="Entre calles, color de la fachada, bodega…"
                                       value="<?= htmlspecialchars($old['dir_refs'] ?? '') ?>">
                            </div>

                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="btnSubmit">
                        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        Enviar solicitud de cotización
                    </button>

                    <p style="text-align:center;font-size:12px;color:#94a3b8;margin-top:14px;">
                        Recibirás una confirmación por correo con el número de folio. Te contactaremos en 24–48 horas hábiles.
                    </p>

                </form>
            </div>

            <!-- SIDEBAR -->
            <aside class="cot-summary">
                <div class="cot-summary-head">
                    <h3>Productos a cotizar</h3>
                    <p><?= count($cart) ?> <?= count($cart) === 1 ? 'producto' : 'productos' ?> · <?= $cartCount ?> unidades</p>
                </div>
                <div class="cot-summary-body">

                    <?php foreach ($cart as $item):
                        $precio = (float)($item['precio'] ?? 0);
                        $sub    = $precio * (int)($item['cantidad'] ?? 1);
                    ?>
                    <div class="cot-item">
                        <img class="cot-item-img"
                             src="<?= htmlspecialchars(getImageUrl($item['imagen'] ?? '')) ?>"
                             alt="<?= htmlspecialchars($item['nombre']) ?>">
                        <div class="cot-item-info">
                            <div class="cot-item-name"><?= htmlspecialchars($item['nombre']) ?></div>
                            <div class="cot-item-qty">Cantidad: <?= (int)$item['cantidad'] ?></div>
                            <?php if ($precio > 0): ?>
                            <div style="font-size:12px;color:#1e3a8a;font-weight:700;margin-top:2px;">
                                <?php if ((int)$item['cantidad'] > 1): ?>
                                    $<?= number_format($precio, 2) ?> c/u &nbsp;·&nbsp; $<?= number_format($sub, 2) ?>
                                <?php else: ?>
                                    $<?= number_format($precio, 2) ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <hr class="cot-divider">

                    <div class="cot-stat">
                        <span>Tipos de producto</span>
                        <strong><?= count($cart) ?></strong>
                    </div>
                    <div class="cot-stat">
                        <span>Unidades totales</span>
                        <strong><?= $cartCount ?></strong>
                    </div>
                    <?php if ($totalRef > 0): ?>
                    <div class="cot-stat" style="margin-top:8px;padding-top:8px;border-top:1px solid #e2e8f0;">
                        <span style="font-weight:700;color:#0f172a;">Total de referencia</span>
                        <strong style="color:#1e3a8a;font-size:16px;">$<?= number_format($totalRef, 2) ?></strong>
                    </div>
                    <?php endif; ?>

                    <div class="cot-note">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        Los precios mostrados son de catálogo y pueden variar. Te confirmaremos disponibilidad y precio final por correo.
                    </div>

                </div>
            </aside>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // ── Geolocalización ──────────────────────────────────────────────
    function useMyLocation() {
        if (!navigator.geolocation) {
            alert('Tu navegador no soporta geolocalización.');
            return;
        }
        const btn = document.getElementById('btnLocation');
        btn.classList.add('loading');
        btn.innerHTML = `<svg viewBox="0 0 24 24" width="16" height="16" fill="#1e3a8a" style="animation:spin 1s linear infinite"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 14.68 20 13.39 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 9.32 4 11.1 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg> Obteniendo ubicación...`;

        navigator.geolocation.getCurrentPosition(
            function(pos) {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&accept-language=es`)
                    .then(r => r.json())
                    .then(data => {
                        const a = data.address || {};
                        const street = [a.road || a.pedestrian || '', a.house_number || ''].filter(Boolean).join(' ');
                        const colonia = a.suburb || a.neighbourhood || a.quarter || a.city_district || '';
                        const ciudad  = a.city || a.town || a.municipality || a.village || '';
                        const cp      = (a.postcode || '').replace(/[^0-9]/g,'').slice(0,5);

                        document.getElementById('dir_calle').value   = street;
                        document.getElementById('dir_colonia').value = colonia;
                        document.getElementById('dir_cp').value      = cp;

                        const norm = s => (s || '').normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase().trim();
                        const select = document.getElementById('dir_municipio');
                        let match  = Array.from(select.options).find(o => o.value && norm(o.value) === norm(ciudad));
                        if (!match && norm(ciudad) === 'madero') {
                            match = Array.from(select.options).find(o => o.value && norm(o.value) === 'ciudad madero');
                        }
                        if (match) {
                            select.value = match.value;
                        } else {
                            select.value = 'Otro';
                            if (ciudad) {
                                const refs = document.getElementById('dir_refs');
                                refs.value = refs.value ? refs.value : ciudad;
                            }
                        }

                        validateMunicipio();
                        if (!isMunicipioAllowed(select.value)) {
                            showSwalAlert('Solo se permite cotización en Tampico, Altamira o Ciudad Madero. Tu ubicación actual no está en la zona de servicio.');
                        }

                        document.getElementById('locationOk').classList.add('show');
                        resetLocationBtn();
                    })
                    .catch(() => {
                        showSwalAlert('No se pudo obtener la dirección. Intenta de nuevo.');
                        resetLocationBtn();
                    });
            },
            function(err) {
                const msgs = { 1:'Permiso denegado. Activa la ubicación en tu navegador.', 2:'Ubicación no disponible.', 3:'Tiempo de espera agotado.' };
                showSwalAlert(msgs[err.code] || 'Error al obtener ubicación.');
                resetLocationBtn();
            },
            { timeout: 10000, enableHighAccuracy: true }
        );
    }

    function resetLocationBtn() {
        const btn = document.getElementById('btnLocation');
        btn.classList.remove('loading');
        btn.innerHTML = `<svg viewBox="0 0 24 24" width="16" height="16" fill="#1e3a8a"><path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3c-.46-4.17-3.77-7.48-7.94-7.94V1h-2v2.06C6.83 3.52 3.52 6.83 3.06 11H1v2h2.06c.46 4.17 3.77 7.48 7.94 7.94V23h2v-2.06c4.17-.46 7.48-3.77 7.94-7.94H23v-2h-2.06z"/></svg> Usar mi ubicación actual`;
    }

    // ── Validación en tiempo real ───────────────────────────────────
    const rules = {
        nombre:   v => v.trim().length >= 2,
        email:    v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()),
        telefono: v => v.replace(/\D/g,'').length >= 10,
    };
    const msgs = {
        nombre:   'El nombre debe tener al menos 2 caracteres.',
        email:    'Ingresa un correo electrónico válido.',
        telefono: 'El teléfono debe tener al menos 10 dígitos.',
    };
    const allowedMunicipios = ['tampico', 'altamira', 'ciudad madero', 'madero'];

    function isMunicipioAllowed(value) {
        return allowedMunicipios.includes((value || '').trim().toLowerCase());
    }

    function showSwalAlert(message) {
        if (typeof Swal === 'undefined') {
            alert(message);
            return;
        }
        Swal.fire({
            icon: 'error',
            title: '¡Atención!',
            text: message,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#1e3a8a',
            background: '#ffffff',
            color: '#0f172a'
        });
    }

    function validateMunicipio() {
        const municipio = document.getElementById('dir_municipio');
        const municipioError = document.getElementById('err-dir_municipio');
        if (!municipio || !municipioError) return true;

        const ok = isMunicipioAllowed(municipio.value);
        municipio.classList.toggle('is-valid', ok);
        municipio.classList.toggle('is-invalid', !ok);

        if (ok) {
            municipioError.classList.remove('show');
            municipioError.textContent = 'Selecciona el municipio.';
        } else {
            municipioError.textContent = 'Solo se permite cotización en Tampico, Altamira o Ciudad Madero.';
            municipioError.classList.add('show');
        }
        return ok;
    }

    function validateField(id) {
        const input = document.getElementById(id);
        const err   = document.getElementById('err-' + id);
        if (!input || !err || !rules[id]) return true;
        const ok = rules[id](input.value);
        input.classList.toggle('is-valid', ok);
        input.classList.toggle('is-invalid', !ok);
        err.textContent = msgs[id];
        err.classList.toggle('show', !ok);
        return ok;
    }

    ['nombre','email','telefono'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('blur',  () => validateField(id));
        el.addEventListener('input', () => {
            if (el.classList.contains('is-invalid')) validateField(id);
        });
    });

    const municipioEl = document.getElementById('dir_municipio');
    if (municipioEl) {
        municipioEl.addEventListener('change', validateMunicipio);
    }

    // ── Envío del formulario ────────────────────────────────────────
    document.getElementById('cotForm').addEventListener('submit', function(e) {
        const fields = ['nombre','email','telefono'];
        let valid = true;
        fields.forEach(id => { if (!validateField(id)) valid = false; });

        const municipio = document.getElementById('dir_municipio');
        const municipioError = document.getElementById('err-dir_municipio');
        if (!validateMunicipio()) {
            valid = false;
            e.preventDefault();
            showSwalAlert('Solo se permite cotización en Tampico, Altamira o Ciudad Madero. Si tu ubicación es otra, por favor contáctanos.');
        }

        if (!valid) {
            const firstErr = document.querySelector('.is-invalid');
            if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.classList.add('loading');
        btn.innerHTML = `<svg viewBox="0 0 24 24" width="18" height="18" fill="white"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 14.68 20 13.39 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 9.32 4 11.1 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg> Enviando...`;
    });

    // ── Navbar JS ──────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        const toggle   = document.getElementById('navbarCategoryToggle');
        const dropdown = document.getElementById('navbarCategoryDropdown');
        if (toggle && dropdown) {
            toggle.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); const a = dropdown.classList.toggle('active'); toggle.classList.toggle('active', a); });
            document.addEventListener('click', e => { if (!toggle.contains(e.target) && !dropdown.contains(e.target)) { dropdown.classList.remove('active'); toggle.classList.remove('active'); } });
        }
        document.querySelectorAll('.navbar-category-group').forEach(g => {
            const m = g.querySelector('.navbar-category-main');
            const s = g.querySelector('.navbar-subcategory-menu');
            if (m && s) {
                m.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); document.querySelectorAll('.navbar-subcategory-menu.active').forEach(x => { if(x!==s){x.classList.remove('active');x.previousElementSibling&&x.previousElementSibling.classList.remove('active');}}); s.classList.toggle('active'); m.classList.toggle('active'); });
            }
        });
        const mt = document.querySelector('.menu-toggle');
        const nv = document.querySelector('.nav');
        if (mt && nv) mt.addEventListener('click', () => { mt.classList.toggle('active'); nv.classList.toggle('active'); });
    });
    </script>
</body>
</html>
