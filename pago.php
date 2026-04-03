<?php
session_start();
require_once __DIR__ . '/apis/db.php';

function getImageUrl($imagePath) {
    if (empty($imagePath)) return 'https://via.placeholder.com/800x600?text=Sin+imagen';
    $imagePath = trim($imagePath);
    if (empty($imagePath)) return 'https://via.placeholder.com/800x600?text=Sin+imagen';
    if (preg_match('/^https?:\/\//i', $imagePath)) return 'image.php?u=' . rawurlencode($imagePath);
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) return 'image.php?u=' . rawurlencode($imagePath);
    $imagePath = str_replace('\\', '/', $imagePath);
    $imgTrim   = ltrim($imagePath, '/');
    if (stripos($imgTrim, 'uploads/') === 0)
        return 'image.php?path=' . implode('/', array_map('rawurlencode', explode('/', $imgTrim)));
    return 'image.php?path=' . implode('/', array_map('rawurlencode', explode('/', 'Uploads/' . $imgTrim)));
}

$cart      = $_SESSION['cart'] ?? [];
$cartCount = array_sum(array_column($cart, 'cantidad'));
if (empty($cart)) { header('Location: carrito.php'); exit; }

// Header vars
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
    <title>Pagar — Ofiequipo de Tampico</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="shortcut icon" type="image/png" href="icono_logo.png">
    <link rel="apple-touch-icon" href="icono_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ── Navbar (same pattern) ───────────────────────────── */
        .promo-banner p { color: white !important; margin: 0; }
        .promo-banner .phone-numbers { color: white !important; font-weight: 600; }
        .promo-banner * { color: white !important; }

        .navbar-category-dropdown { position: relative; display: inline-block; }
        .navbar-category-toggle { background: transparent; color: var(--text-dark); border: none; font-size: 16px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px; text-decoration: none; }
        .navbar-category-toggle:hover { color: var(--primary-blue); }
        .navbar-category-toggle .icon { transition: transform 0.3s ease; width: 12px; height: 12px; opacity: 0.7; }
        .navbar-category-toggle.active .icon { transform: rotate(180deg); }
        .navbar-category-dropdown-menu { position: absolute; top: 100%; left: 0; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.15); z-index: 1000; display: none; min-width: 250px; max-height: 500px; overflow-y: auto; margin-top: 8px; padding: 8px 0; }
        .navbar-category-dropdown-menu.active { display: block; animation: dropIn 0.2s ease; }
        @keyframes dropIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
        .navbar-category-item { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; color: var(--text-gray); text-decoration: none; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; font-size: 14px; margin: 0 8px; border-radius: 6px; }
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
            .nav .navbar-category-dropdown { position: static; display: block; width: 100%; }
            .nav .navbar-category-dropdown-menu { position: static; display: none; box-shadow: none; border: none; background: #f8f9fa; margin: 0; padding: 0; width: 100%; animation: none; }
            .nav .navbar-category-dropdown-menu.active { display: block; }
            .nav .navbar-category-item,.nav .navbar-category-main { margin: 0; border-radius: 0; }
            .nav .navbar-subcategory-item { margin: 0; border-radius: 0; }
        }
        .logo { text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px; transition: opacity 0.3s; }
        .logo:hover { opacity: 0.8; }
        .logo h1 { margin: 0; color: inherit; }

        /* ── Page ────────────────────────────────────────────── */
        .pay-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 32px 80px;
        }

        .breadcrumb {
            display: flex; align-items: center; gap: 6px;
            font-size: 13px; color: #64748b; margin-bottom: 32px;
        }
        .breadcrumb a { color: var(--primary-blue,#1e3a8a); text-decoration: none; font-weight: 500; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb-sep { color: #cbd5e1; }

        .pay-page-header { margin-bottom: 32px; }
        .pay-page-header h1 { font-size: 30px; font-weight: 800; letter-spacing: -0.6px; color: #0f172a; margin-bottom: 6px; }
        .pay-page-header p { font-size: 15px; color: #475569; }

        /* Progress steps */
        .pay-steps {
            display: flex; align-items: center; gap: 0;
            margin-bottom: 40px;
        }
        .step {
            display: flex; align-items: center; gap: 10px;
            font-size: 13px; font-weight: 600; color: #94a3b8;
        }
        .step.active { color: #1e3a8a; }
        .step.done { color: #16a34a; }
        .step-num {
            width: 28px; height: 28px; border-radius: 50%;
            background: #f1f5f9; color: #94a3b8;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; flex-shrink: 0;
        }
        .step.active .step-num { background: #1e3a8a; color: white; }
        .step.done .step-num { background: #16a34a; color: white; }
        .step-line {
            flex: 1; height: 2px; background: #e2e8f0; margin: 0 12px; max-width: 60px;
        }
        .step-line.done { background: #16a34a; }

        /* Two-col layout */
        .pay-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 32px;
            align-items: start;
        }

        /* ── Payment card ─────────────────────────────────────── */
        .pay-card {
            background: white;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 16px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .pay-card-head {
            padding: 20px 28px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 15px; font-weight: 700; color: #0f172a;
        }
        .pay-card-body { padding: 24px 28px; }

        /* Method tabs */
        .method-tabs {
            display: flex; gap: 10px;
            margin-bottom: 28px;
        }
        .method-tab {
            flex: 1; padding: 14px 12px;
            border: 2px solid #e2e8f0; border-radius: 12px;
            background: white; cursor: pointer;
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        .method-tab:hover { border-color: #93c5fd; background: #f8fafc; }
        .method-tab.active {
            border-color: #1e3a8a;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(30,58,138,0.08);
        }
        .method-tab-icon { width: 36px; height: 24px; display: flex; align-items: center; justify-content: center; }
        .method-tab-label { font-size: 12px; font-weight: 600; color: #475569; }
        .method-tab.active .method-tab-label { color: #1e3a8a; }

        /* ── Credit/Debit card form ───────────────────────────── */
        .card-panel { display: none; }
        .card-panel.active { display: block; }

        /* Visual card */
        .card-visual-wrap {
            perspective: 1000px;
            margin-bottom: 28px;
        }
        .card-visual {
            width: 100%;
            max-width: 360px;
            height: 200px;
            margin: 0 auto;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.4,0,0.2,1);
        }
        .card-visual.flipped { transform: rotateY(180deg); }
        .card-face {
            position: absolute; inset: 0;
            border-radius: 16px;
            padding: 22px 26px;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2), 0 4px 12px rgba(0,0,0,0.1);
        }
        .card-front {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);
            color: white;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .card-back {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            transform: rotateY(180deg);
        }

        /* Front elements */
        .card-chip {
            width: 36px; height: 28px;
            background: linear-gradient(135deg, #d4af37 0%, #f5d06a 50%, #d4af37 100%);
            border-radius: 5px;
            position: relative;
            overflow: hidden;
        }
        .card-chip::before {
            content: '';
            position: absolute; top: 50%; left: 0; right: 0;
            height: 1px; background: rgba(0,0,0,0.2);
            transform: translateY(-50%);
        }
        .card-chip::after {
            content: '';
            position: absolute; left: 50%; top: 0; bottom: 0;
            width: 1px; background: rgba(0,0,0,0.2);
            transform: translateX(-50%);
        }
        .card-logo-row {
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-number-display {
            font-size: 18px; letter-spacing: 3px; font-weight: 600;
            font-family: 'Courier New', monospace;
        }
        .card-info-row {
            display: flex; justify-content: space-between; align-items: flex-end;
        }
        .card-label { font-size: 9px; opacity: 0.7; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 3px; }
        .card-value { font-size: 14px; font-weight: 600; letter-spacing: 0.04em; }

        /* Network logos */
        .visa-logo { font-size: 22px; font-weight: 800; font-style: italic; letter-spacing: -1px; color: white; }
        .mc-logo { display: flex; }
        .mc-logo span { width: 22px; height: 22px; border-radius: 50%; }
        .mc-logo span:first-child { background: #eb001b; }
        .mc-logo span:last-child { background: #f79e1b; margin-left: -8px; opacity: 0.9; }

        /* Back of card */
        .card-stripe { height: 40px; background: rgba(0,0,0,0.5); margin: 0 -26px 20px; }
        .card-cvv-row { display: flex; justify-content: flex-end; gap: 12px; align-items: center; }
        .card-cvv-box {
            background: white; color: #1e3a8a;
            padding: 6px 16px; border-radius: 5px;
            font-size: 14px; font-weight: 700; letter-spacing: 3px;
            min-width: 70px; text-align: center;
        }
        .card-cvv-label { font-size: 11px; opacity: 0.7; }

        /* Card form fields */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block; font-size: 12px; font-weight: 700;
            letter-spacing: 0.04em; text-transform: uppercase;
            color: #64748b; margin-bottom: 6px;
        }
        .form-group input {
            width: 100%; padding: 12px 14px;
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 15px; font-family: inherit; color: #0f172a;
            background: #f8fafc; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .form-group input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
            background: white;
        }
        .form-group input::placeholder { color: #94a3b8; }

        /* ── PayPal panel ─────────────────────────────────────── */
        .paypal-panel { display: none; text-align: center; padding: 8px 0; }
        .paypal-panel.active { display: block; }
        .paypal-logo-wrap {
            margin: 0 auto 24px;
            display: inline-flex; align-items: center; justify-content: center;
            gap: 2px;
        }
        .paypal-logo-wrap svg { height: 40px; }
        .paypal-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 20px 0; color: #94a3b8; font-size: 13px;
        }
        .paypal-divider::before, .paypal-divider::after {
            content: ''; flex: 1; height: 1px; background: #e2e8f0;
        }
        .btn-paypal {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 14px;
            background: #ffc439;
            border: none; border-radius: 12px;
            font-size: 16px; font-weight: 700; font-family: inherit; cursor: pointer;
            box-shadow: 0 4px 14px rgba(255,196,57,0.4);
            transition: transform 0.15s, box-shadow 0.15s;
            margin-bottom: 14px;
            color: #003087;
        }
        .btn-paypal:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(255,196,57,0.55); }
        .btn-paypal svg { width: 20px; height: 20px; }
        .paypal-note {
            font-size: 13px; color: #64748b; line-height: 1.6;
            background: #f8fafc; border-radius: 10px; padding: 14px 16px;
            border: 1px solid #e2e8f0; text-align: left;
        }
        .paypal-note strong { color: #0f172a; }

        /* ── Pay button (at bottom of form) ──────────────────── */
        .btn-pay {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 16px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white; border: none; border-radius: 13px;
            font-size: 16px; font-weight: 700; font-family: inherit; cursor: pointer;
            box-shadow: 0 4px 18px rgba(37,99,235,0.35);
            transition: transform 0.15s, box-shadow 0.15s;
            margin-top: 24px;
            letter-spacing: 0.01em;
        }
        .btn-pay:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(37,99,235,0.45); }
        .btn-pay svg { width: 18px; height: 18px; fill: white; }

        .pay-secure-note {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            font-size: 12px; color: #94a3b8; margin-top: 12px;
        }
        .pay-secure-note svg { width: 13px; height: 13px; fill: #94a3b8; }

        /* ── Order summary sidebar ────────────────────────────── */
        .order-summary {
            background: white;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 16px rgba(0,0,0,0.04);
            position: sticky; top: 100px;
            overflow: hidden;
        }
        .order-summary-head {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            padding: 18px 22px; color: white;
        }
        .order-summary-head h3 { font-size: 15px; font-weight: 700; margin-bottom: 3px; }
        .order-summary-head p { font-size: 12px; opacity: 0.7; }
        .order-summary-body { padding: 16px; }

        .order-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 0; border-bottom: 1px solid #f1f5f9;
        }
        .order-item:last-child { border-bottom: none; }
        .order-item-img {
            width: 52px; height: 52px; border-radius: 8px;
            border: 1px solid #e2e8f0; object-fit: contain;
            padding: 4px; background: white; flex-shrink: 0;
        }
        .order-item-name {
            font-size: 13px; font-weight: 600; color: #0f172a;
            line-height: 1.3; flex: 1;
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
        }
        .order-item-qty {
            font-size: 12px; color: #64748b; font-weight: 500; flex-shrink: 0;
        }

        .order-divider { border: none; border-top: 1px solid #e2e8f0; margin: 12px 0; }

        .order-row {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 13px; color: #475569; margin-bottom: 8px;
        }
        .order-row strong { color: #0f172a; font-weight: 700; }
        .order-total {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 15px; font-weight: 800; color: #0f172a;
            margin-top: 10px; padding-top: 12px; border-top: 2px solid #e2e8f0;
        }
        .order-total span:last-child { color: #1e3a8a; }

        .order-badge {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; color: #16a34a; font-weight: 600;
            background: #dcfce7; border-radius: 8px;
            padding: 8px 12px; margin-top: 14px;
        }
        .order-badge svg { width: 14px; height: 14px; fill: #16a34a; flex-shrink: 0; }

        /* Accepted cards row */
        .accepted-cards {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
            margin-top: 16px; padding-top: 14px; border-top: 1px solid #f1f5f9;
        }
        .accepted-cards-label { font-size: 11px; color: #94a3b8; font-weight: 600; width: 100%; }
        .card-badge {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 6px; padding: 4px 10px;
            font-size: 11px; font-weight: 700; color: #475569;
            letter-spacing: 0.03em;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .pay-layout { grid-template-columns: 1fr; }
            .order-summary { position: static; }
        }
        @media (max-width: 640px) {
            .pay-page { padding: 24px 16px 60px; }
            .method-tabs { flex-direction: column; }
            .form-row { grid-template-columns: 1fr; }
            .pay-steps { display: none; }
        }
    </style>
</head>
<body>

    <!-- PROMO BANNER -->
    <div class="promo-banner">
        <p>Armado gratis | Entrega a domicilio (envío gratuito en zona metropolitana al sur de Tamaulipas) | Garantía
            segura por 1 año | Contacto: <span class="phone-numbers">(833) 213-3837 | (833) 217-2047</span></p>
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
                                if(empty($sub)){switch($mc['id']){case 1:$sub=[['id'=>2,'nombre'=>'Visita'],['id'=>3,'nombre'=>'Operativa'],['id'=>4,'nombre'=>'Ejecutiva'],['id'=>5,'nombre'=>'Sofás']];break;case 19:$sub=[['id'=>23,'nombre'=>'Básicos'],['id'=>24,'nombre'=>'Operativos en L'],['id'=>25,'nombre'=>'Semi-Ejecutivo'],['id'=>26,'nombre'=>'Ejecutivos']];break;}}
                                if(!empty($sub)):
                            ?>
                                <div class="navbar-category-group">
                                    <div class="navbar-category-main"><?= htmlspecialchars($mc['nombre']) ?>
                                        <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </div>
                                    <div class="navbar-subcategory-menu">
                                        <?php foreach($sub as $sc): $ct=$conn->prepare("SELECT COUNT(*) AS c FROM producto WHERE id_categoria=?"); $ct->bind_param("i",$sc['id']); $ct->execute(); $n=$ct->get_result()->fetch_assoc()['c']??0; $ct->close(); ?>
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

    <main class="pay-page">

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">Inicio</a>
            <span class="breadcrumb-sep">›</span>
            <a href="catalogo.php">Catálogo</a>
            <span class="breadcrumb-sep">›</span>
            <a href="carrito.php">Mi Carrito</a>
            <span class="breadcrumb-sep">›</span>
            <a href="datos.php">Datos</a>
            <span class="breadcrumb-sep">›</span>
            <span style="color:#0f172a;font-weight:500;">Pago</span>
        </nav>

        <!-- Progress (4 steps) -->
        <div class="pay-steps">
            <div class="step done">
                <div class="step-num">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="white"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </div>
                <span>Carrito</span>
            </div>
            <div class="step-line done"></div>
            <div class="step done">
                <div class="step-num">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="white"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </div>
                <span>Datos</span>
            </div>
            <div class="step-line done"></div>
            <div class="step active">
                <div class="step-num">3</div>
                <span>Pago</span>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-num">4</div>
                <span>Confirmación</span>
            </div>
        </div>

        <!-- Title -->
        <div class="pay-page-header">
            <h1>Método de pago</h1>
            <p>Elige cómo quieres pagar tu pedido de <?= count($cart) ?> <?= count($cart) === 1 ? 'producto' : 'productos' ?>.</p>
        </div>

        <div class="pay-layout">

            <!-- LEFT: Payment form -->
            <div class="pay-card">
                <div class="pay-card-head">Selecciona tu método de pago</div>
                <div class="pay-card-body">

                    <!-- Method tabs -->
                    <div class="method-tabs">
                        <!-- PayPal -->
                        <button class="method-tab" id="tab-paypal" onclick="switchTab('paypal')">
                            <div class="method-tab-icon">
                                <svg viewBox="0 0 24 24" height="22" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.291-.077.444-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.1zm14.146-14.42a3.35 3.35 0 0 0-.607-.541c-.013.076-.026.175-.041.254-.93 4.778-4.005 7.201-9.138 7.201h-2.19a.563.563 0 0 0-.556.479l-1.187 7.527h-.506l-.24 1.516a.56.56 0 0 0 .554.647h3.882c.46 0 .85-.334.922-.788l.038-.196.731-4.628.047-.256a.932.932 0 0 1 .92-.788h.58c3.76 0 6.705-1.528 7.565-5.946.36-1.847.174-3.388-.77-4.48z" fill="#009cde"/>
                                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.291-.077.444-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.1z" fill="#012169" opacity=".5"/>
                                </svg>
                            </div>
                            <span class="method-tab-label">PayPal</span>
                        </button>

                        <!-- Credit card -->
                        <button class="method-tab active" id="tab-credit" onclick="switchTab('credit')">
                            <div class="method-tab-icon">
                                <svg width="36" height="24" viewBox="0 0 36 24" fill="none">
                                    <rect width="36" height="24" rx="4" fill="#1e3a8a"/>
                                    <rect y="6" width="36" height="6" fill="#2563eb"/>
                                    <rect x="4" y="15" width="10" height="4" rx="1" fill="white" opacity="0.9"/>
                                </svg>
                            </div>
                            <span class="method-tab-label">Crédito</span>
                        </button>

                        <!-- Debit card -->
                        <button class="method-tab" id="tab-debit" onclick="switchTab('debit')">
                            <div class="method-tab-icon">
                                <svg width="36" height="24" viewBox="0 0 36 24" fill="none">
                                    <rect width="36" height="24" rx="4" fill="#16a34a"/>
                                    <rect y="6" width="36" height="6" fill="#15803d"/>
                                    <rect x="4" y="15" width="10" height="4" rx="1" fill="white" opacity="0.9"/>
                                </svg>
                            </div>
                            <span class="method-tab-label">Débito</span>
                        </button>
                    </div>

                    <!-- ── PAYPAL PANEL ── -->
                    <div class="card-panel paypal-panel" id="panel-paypal">
                        <div class="paypal-logo-wrap">
                            <span style="font-size:36px;font-weight:800;font-style:italic;font-family:'Inter',sans-serif;color:#003087;letter-spacing:-1px;">Pay</span><span style="font-size:36px;font-weight:800;font-style:italic;font-family:'Inter',sans-serif;color:#009cde;letter-spacing:-1px;">Pal</span>
                        </div>

                        <p style="font-size:14px;color:#475569;margin-bottom:20px;line-height:1.6;">
                            Paga de forma rápida y segura con tu cuenta PayPal.<br>
                            Serás redirigido a PayPal para completar el pago.
                        </p>

                        <button class="btn-paypal">
                            <svg viewBox="0 0 24 24" fill="#003087" width="20" height="20"><path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.291-.077.444-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.1zm14.146-14.42a3.35 3.35 0 0 0-.607-.541c-.013.076-.026.175-.041.254-.93 4.778-4.005 7.201-9.138 7.201h-2.19a.563.563 0 0 0-.556.479l-1.187 7.527h-.506l-.24 1.516a.56.56 0 0 0 .554.647h3.882c.46 0 .85-.334.922-.788l.038-.196.731-4.628.047-.256a.932.932 0 0 1 .92-.788h.58c3.76 0 6.705-1.528 7.565-5.946.36-1.847.174-3.388-.77-4.48z"/></svg>
                            Pagar con PayPal
                        </button>

                        <div class="paypal-divider">o paga con tarjeta a través de PayPal</div>

                        <div class="paypal-note">
                            <strong>¿Sin cuenta PayPal?</strong> No te preocupes — puedes pagar con tarjeta de crédito o débito directamente desde PayPal sin necesidad de registrarte. Selecciona "Tarjeta de crédito o débito" en la ventana de PayPal.
                        </div>
                    </div>

                    <!-- ── CREDIT CARD PANEL ── -->
                    <div class="card-panel active" id="panel-credit">
                        <!-- Visual card -->
                        <div class="card-visual-wrap">
                            <div class="card-visual" id="cardVisual">
                                <div class="card-face card-front">
                                    <div class="card-logo-row">
                                        <div class="card-chip"></div>
                                        <div id="creditNetworkLogo" class="visa-logo">VISA</div>
                                    </div>
                                    <div class="card-number-display" id="creditNumberDisplay">•••• •••• •••• ••••</div>
                                    <div class="card-info-row">
                                        <div>
                                            <div class="card-label">Titular</div>
                                            <div class="card-value" id="creditNameDisplay">NOMBRE APELLIDO</div>
                                        </div>
                                        <div style="text-align:right">
                                            <div class="card-label">Vence</div>
                                            <div class="card-value" id="creditExpDisplay">MM/AA</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-face card-back">
                                    <div class="card-stripe"></div>
                                    <div class="card-cvv-row">
                                        <div class="card-cvv-label">CVV</div>
                                        <div class="card-cvv-box" id="creditCvvDisplay">•••</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form -->
                        <div class="form-group">
                            <label>Número de tarjeta</label>
                            <input type="text" id="creditNumber" placeholder="1234 5678 9012 3456" maxlength="19"
                                   oninput="formatCardNumber(this,'credit')" onpaste="return false">
                        </div>
                        <div class="form-group">
                            <label>Nombre del titular</label>
                            <input type="text" id="creditName" placeholder="Como aparece en la tarjeta"
                                   oninput="updateCardName(this,'credit')" style="text-transform:uppercase">
                        </div>
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:0">
                                <label>Fecha de vencimiento</label>
                                <input type="text" id="creditExp" placeholder="MM/AA" maxlength="5"
                                       oninput="formatExp(this,'credit')">
                            </div>
                            <div class="form-group" style="margin-bottom:0">
                                <label>CVV</label>
                                <input type="text" id="creditCvv" placeholder="•••" maxlength="4"
                                       oninput="updateCvv(this,'credit')"
                                       onfocus="flipCard('credit',true)"
                                       onblur="flipCard('credit',false)">
                            </div>
                        </div>

                        <button class="btn-pay" onclick="handlePay()">
                            <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                            Pagar ahora
                        </button>
                        <p class="pay-secure-note">
                            <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2z"/></svg>
                            Pago seguro con encriptación SSL de 256 bits
                        </p>
                    </div>

                    <!-- ── DEBIT CARD PANEL ── -->
                    <div class="card-panel" id="panel-debit">
                        <!-- Visual card (green) -->
                        <div class="card-visual-wrap">
                            <div class="card-visual" id="debitCardVisual">
                                <div class="card-face card-front" style="background:linear-gradient(135deg,#15803d 0%,#16a34a 60%,#22c55e 100%)">
                                    <div class="card-logo-row">
                                        <div class="card-chip"></div>
                                        <div id="debitNetworkLogo" class="visa-logo">VISA</div>
                                    </div>
                                    <div class="card-number-display" id="debitNumberDisplay">•••• •••• •••• ••••</div>
                                    <div class="card-info-row">
                                        <div>
                                            <div class="card-label">Titular</div>
                                            <div class="card-value" id="debitNameDisplay">NOMBRE APELLIDO</div>
                                        </div>
                                        <div style="text-align:right">
                                            <div class="card-label">Vence</div>
                                            <div class="card-value" id="debitExpDisplay">MM/AA</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-face card-back" style="background:linear-gradient(135deg,#14532d 0%,#15803d 100%)">
                                    <div class="card-stripe"></div>
                                    <div class="card-cvv-row">
                                        <div class="card-cvv-label">NIP / CVV</div>
                                        <div class="card-cvv-box" id="debitCvvDisplay">•••</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Número de tarjeta</label>
                            <input type="text" id="debitNumber" placeholder="1234 5678 9012 3456" maxlength="19"
                                   oninput="formatCardNumber(this,'debit')" onpaste="return false">
                        </div>
                        <div class="form-group">
                            <label>Nombre del titular</label>
                            <input type="text" id="debitName" placeholder="Como aparece en la tarjeta"
                                   oninput="updateCardName(this,'debit')" style="text-transform:uppercase">
                        </div>
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:0">
                                <label>Fecha de vencimiento</label>
                                <input type="text" id="debitExp" placeholder="MM/AA" maxlength="5"
                                       oninput="formatExp(this,'debit')">
                            </div>
                            <div class="form-group" style="margin-bottom:0">
                                <label>NIP</label>
                                <input type="password" id="debitCvv" placeholder="••••" maxlength="4"
                                       oninput="updateCvv(this,'debit')"
                                       onfocus="flipCard('debit',true)"
                                       onblur="flipCard('debit',false)">
                            </div>
                        </div>

                        <button class="btn-pay" onclick="handlePay()">
                            <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                            Pagar ahora
                        </button>
                        <p class="pay-secure-note">
                            <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2z"/></svg>
                            Pago seguro con encriptación SSL de 256 bits
                        </p>
                    </div>

                </div><!-- /pay-card-body -->
            </div><!-- /pay-card -->

            <!-- RIGHT: Order summary -->
            <aside class="order-summary">
                <div class="order-summary-head">
                    <h3>Resumen del pedido</h3>
                    <p><?= count($cart) ?> <?= count($cart) === 1 ? 'producto' : 'productos' ?> · <?= $cartCount ?> unidades</p>
                </div>
                <div class="order-summary-body">
                    <?php foreach ($cart as $item): ?>
                    <div class="order-item">
                        <img class="order-item-img"
                             src="<?= htmlspecialchars($item['imagen']) ?>"
                             alt="<?= htmlspecialchars($item['nombre']) ?>">
                        <span class="order-item-name"><?= htmlspecialchars($item['nombre']) ?></span>
                        <span class="order-item-qty">×<?= (int)$item['cantidad'] ?></span>
                    </div>
                    <?php endforeach; ?>

                    <hr class="order-divider">

                    <div class="order-row">
                        <span>Productos</span>
                        <strong><?= count($cart) ?></strong>
                    </div>
                    <div class="order-row">
                        <span>Unidades</span>
                        <strong><?= $cartCount ?></strong>
                    </div>
                    <div class="order-row">
                        <span>Envío</span>
                        <strong style="color:#16a34a;">Gratis</strong>
                    </div>
                    <div class="order-row">
                        <span>Precio</span>
                        <strong>A cotizar</strong>
                    </div>

                    <div class="order-total">
                        <span>Total</span>
                        <span>Por confirmar</span>
                    </div>

                    <div class="order-badge">
                        <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                        Armado e instalación gratis
                    </div>

                    <div class="accepted-cards">
                        <span class="accepted-cards-label">Métodos aceptados</span>
                        <span class="card-badge">VISA</span>
                        <span class="card-badge">Mastercard</span>
                        <span class="card-badge">AMEX</span>
                        <span class="card-badge">PayPal</span>
                    </div>
                </div>
            </aside>

        </div><!-- /pay-layout -->
    </main>

    <script>
    let activeTab = 'credit';

    function switchTab(tab) {
        // Update tabs
        document.querySelectorAll('.method-tab').forEach(t => t.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');

        // Update panels
        document.querySelectorAll('.card-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('panel-' + tab).classList.add('active');

        activeTab = tab;
    }

    // ── Card number formatting ─────────────────────────────────
    function formatCardNumber(input, type) {
        let v = input.value.replace(/\D/g, '').slice(0, 16);
        let formatted = v.replace(/(.{4})/g, '$1 ').trim();
        input.value = formatted;

        // Detect network
        const logo = type === 'credit' ? 'creditNetworkLogo' : 'debitNetworkLogo';
        const el   = document.getElementById(logo);
        if (v.startsWith('4')) {
            el.className = 'visa-logo'; el.innerHTML = 'VISA';
        } else if (/^5[1-5]|^2[2-7]/.test(v)) {
            el.className = 'mc-logo'; el.innerHTML = '<span></span><span></span>';
        } else if (v.startsWith('3')) {
            el.className = 'visa-logo'; el.innerHTML = 'AMEX'; el.style.fontSize = '14px';
        } else {
            el.className = 'visa-logo'; el.innerHTML = 'VISA'; el.style.fontSize = '';
        }

        // Update display
        const display = v ? v.replace(/(.{4})/g, '$1 ').trim() : '•••• •••• •••• ••••';
        // Pad with bullets
        const raw  = v.padEnd(16, '•');
        const disp = raw.match(/.{1,4}/g).join(' ');
        document.getElementById(type + 'NumberDisplay').textContent = disp;
    }

    function updateCardName(input, type) {
        const val = input.value.toUpperCase() || 'NOMBRE APELLIDO';
        document.getElementById(type + 'NameDisplay').textContent = val;
    }

    function formatExp(input, type) {
        let v = input.value.replace(/\D/g, '').slice(0, 4);
        if (v.length >= 2) v = v.slice(0,2) + '/' + v.slice(2);
        input.value = v;
        document.getElementById(type + 'ExpDisplay').textContent = v || 'MM/AA';
    }

    function updateCvv(input, type) {
        const val = input.value.replace(/\D/g,'') || '•••';
        document.getElementById(type + 'CvvDisplay').textContent = val.replace(/./g,'•').padEnd(3,'•');
    }

    function flipCard(type, flip) {
        const el = document.getElementById(type === 'credit' ? 'cardVisual' : 'debitCardVisual');
        el.classList.toggle('flipped', flip);
    }

    // ── Navbar JS ─────────────────────────────────────────────
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

    // ── Pay button placeholder ─────────────────────────────────
    function handlePay() {
        alert('El sistema de pago se conectará próximamente. ¡Gracias por tu interés!');
    }
    </script>
</body>
</html>
