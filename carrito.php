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

// Si viene con ?quote=id, agregar ese producto al carrito
if (!empty($_GET['quote'])) {
    $qId = (int)$_GET['quote'];
    $qStmt = $conn->prepare("SELECT id, nombre, imagen FROM producto WHERE id = ?");
    $qStmt->bind_param('i', $qId);
    $qStmt->execute();
    $qProd = $qStmt->get_result()->fetch_assoc();
    $qStmt->close();
    if ($qProd && !isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if ($qProd) {
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $qId) { $found = true; break; }
        }
        unset($item);
        if (!$found) {
            $_SESSION['cart'][] = ['id'=>$qId,'nombre'=>$qProd['nombre'],'imagen'=>getImageUrl($qProd['imagen']??''),'cantidad'=>1];
        }
    }
}

$cart      = $_SESSION['cart'] ?? [];
$cartCount = array_sum(array_column($cart, 'cantidad'));
$isEmpty   = empty($cart);

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
    <title>Mi Carrito — Ofiequipo de Tampico</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="shortcut icon" type="image/png" href="icono_logo.png">
    <link rel="apple-touch-icon" href="icono_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ── Navbar (same as producto.php / catalogo.php) ─────── */
        .promo-banner p { color: white !important; margin: 0; }
        .promo-banner .phone-numbers { color: white !important; font-weight: 600; }
        .promo-banner * { color: white !important; }

        .navbar-category-dropdown { position: relative; display: inline-block; }
        .navbar-category-toggle {
            background: transparent; color: var(--text-dark);
            border: none; font-size: 16px; font-weight: 500;
            cursor: pointer; display: flex; align-items: center; gap: 6px;
            text-decoration: none;
        }
        .navbar-category-toggle:hover { color: var(--primary-blue); }
        .navbar-category-toggle .icon { transition: transform 0.3s ease; width: 12px; height: 12px; opacity: 0.7; }
        .navbar-category-toggle.active .icon { transform: rotate(180deg); }

        .navbar-category-dropdown-menu {
            position: absolute; top: 100%; left: 0;
            background: white; border: 1px solid #e5e7eb;
            border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            z-index: 1000; display: none; min-width: 250px;
            max-height: 500px; overflow-y: auto;
            margin-top: 8px; padding: 8px 0;
        }
        .navbar-category-dropdown-menu.active { display: block; animation: dropIn 0.2s ease; }
        @keyframes dropIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }

        .navbar-category-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 20px; color: var(--text-gray); text-decoration: none;
            border-bottom: 1px solid #f3f4f6; transition: all 0.2s ease;
            font-size: 14px; margin: 0 8px; border-radius: 6px;
        }
        .navbar-category-item:last-child { border-bottom: none; }
        .navbar-category-item:hover { background: #eff6ff; color: var(--primary-blue); }
        .navbar-category-main {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 20px; color: var(--text-dark);
            font-size: 13px; font-weight: 600; cursor: pointer;
            border-bottom: 1px solid #f3f4f6; margin: 0 8px; border-radius: 6px;
            transition: background 0.15s;
        }
        .navbar-category-main:hover { background: #f8fafc; }
        .navbar-category-main .icon { width: 12px; height: 12px; opacity: 0.5; transition: transform 0.2s; }
        .navbar-category-main.active .icon { transform: rotate(90deg); }
        .navbar-subcategory-menu { display: none; }
        .navbar-subcategory-menu.active { display: block; background: #f8fafc; }
        .navbar-subcategory-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 16px 10px 28px; color: var(--text-gray);
            text-decoration: none; font-size: 13px;
            border-bottom: 1px solid #f0f0f0; transition: all 0.15s;
        }
        .navbar-subcategory-item:hover { background: #eff6ff; color: var(--primary-blue); }
        .navbar-category-count {
            background: #f1f5f9; color: #64748b;
            font-size: 11px; font-weight: 600;
            padding: 2px 7px; border-radius: 10px;
        }

        .menu-toggle { display: none; }
        .nav { display: flex; gap: 40px; align-items: center; }
        @media (max-width: 1024px) {
            .menu-toggle {
                display: flex; flex-direction: column; gap: 4px;
                width: 40px; height: 40px; padding: 8px;
                background: transparent; border: none; cursor: pointer;
            }
            .menu-toggle span { width: 100%; height: 3px; background: var(--text-dark,#1a1a2e); border-radius: 2px; transition: all 0.3s; }
            .menu-toggle.active span:nth-child(1) { transform: rotate(45deg) translate(5px,5px); }
            .menu-toggle.active span:nth-child(2) { opacity: 0; }
            .menu-toggle.active span:nth-child(3) { transform: rotate(-45deg) translate(7px,-6px); }
            .nav {
                display: none; position: absolute; top: 100%; left: 0; right: 0;
                background: white; flex-direction: column; gap: 0; padding: 16px;
                box-shadow: 0 8px 16px rgba(0,0,0,0.1);
                max-height: 70vh; overflow-y: auto;
                border-radius: 0 0 12px 12px; z-index: 1000;
            }
            .nav.active { display: flex !important; }
            .nav a { padding: 12px 16px; color: var(--text-dark); text-decoration: none; border-bottom: 1px solid #f0f0f0; }
            .nav a:hover { background: #f8f9fa; color: var(--primary-blue); }
            .header-actions { display: none; }
            .nav .navbar-category-dropdown { position: static; display: block; width: 100%; }
            .nav .navbar-category-dropdown-menu { position: static; display: none; box-shadow: none; border: none; background: #f8f9fa; margin: 0; padding: 0; width: 100%; animation: none; }
            .nav .navbar-category-dropdown-menu.active { display: block; }
            .nav .navbar-category-item, .nav .navbar-category-main { margin: 0; border-radius: 0; }
            .nav .navbar-subcategory-item { margin: 0; border-radius: 0; }
        }

        /* ── Logo ────────────────────────────────────────────── */
        .logo { text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px; transition: opacity 0.3s; }
        .logo:hover { opacity: 0.8; }
        .logo h1 { margin: 0; color: inherit; }

        /* ── Page-level variables ─────────────────────────────── */
        :root {
            --cart-border: #e2e8f0;
            --cart-bg:     #f8fafc;
            --cart-blue:   #2563eb;
            --cart-navy:   #1e3a8a;
            --cart-dark:   #0f172a;
            --cart-gray:   #475569;
            --cart-light:  #94a3b8;
        }

        /* ── Page wrapper ────────────────────────────────────── */
        .cart-page {
            max-width: 1160px;
            margin: 0 auto;
            padding: 40px 32px 80px;
        }

        /* ── Breadcrumb ──────────────────────────────────────── */
        .breadcrumb {
            display: flex; align-items: center; gap: 6px;
            font-size: 13px; color: #64748b;
            margin-bottom: 32px;
        }
        .breadcrumb a { color: var(--primary-blue,#1e3a8a); text-decoration: none; font-weight: 500; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb-sep { color: #cbd5e1; }

        /* ── Page header block ───────────────────────────────── */
        .cart-page-header {
            margin-bottom: 32px;
        }
        .cart-page-header h1 {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.6px;
            color: var(--cart-dark);
            margin-bottom: 6px;
        }
        .cart-page-header p {
            font-size: 15px;
            color: var(--cart-gray);
        }

        /* ── Empty state ─────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 80px 32px;
            background: white;
            border-radius: 18px;
            border: 1px solid var(--cart-border);
            box-shadow: 0 2px 16px rgba(0,0,0,0.04);
        }
        .empty-state-icon {
            width: 72px; height: 72px;
            background: var(--cart-bg);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        }
        .empty-state-icon svg { width: 36px; height: 36px; fill: var(--cart-light); }
        .empty-state h3 { font-size: 20px; font-weight: 700; margin-bottom: 8px; color: var(--cart-dark); }
        .empty-state p { font-size: 14px; color: var(--cart-gray); margin-bottom: 28px; }
        .btn-go-catalog {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 28px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white; border: none; border-radius: 11px;
            font-size: 14px; font-weight: 600; font-family: inherit;
            text-decoration: none; cursor: pointer;
            box-shadow: 0 4px 14px rgba(37,99,235,0.3);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-go-catalog:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,0.42); color: white; }

        /* ── Main layout ─────────────────────────────────────── */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 28px;
            align-items: start;
        }

        /* ── Items card ──────────────────────────────────────── */
        .cart-items-card {
            background: white;
            border-radius: 18px;
            border: 1px solid var(--cart-border);
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(0,0,0,0.04);
        }
        .cart-items-head {
            padding: 18px 28px;
            border-bottom: 1px solid var(--cart-border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .cart-items-head-label {
            font-size: 14px; font-weight: 700; color: var(--cart-dark);
            display: flex; align-items: center; gap: 8px;
        }
        .cart-items-head-label .count-pill {
            background: #eff6ff; color: var(--cart-navy);
            font-size: 12px; font-weight: 700;
            padding: 3px 10px; border-radius: 20px;
        }
        .cart-items-head-back {
            font-size: 13px; color: var(--cart-blue);
            text-decoration: none; font-weight: 500;
            display: flex; align-items: center; gap: 4px;
        }
        .cart-items-head-back:hover { text-decoration: underline; }

        /* ── Single item ─────────────────────────────────────── */
        .cart-item {
            display: grid;
            grid-template-columns: 88px 1fr auto;
            gap: 20px;
            align-items: center;
            padding: 22px 28px;
            border-bottom: 1px solid var(--cart-border);
            transition: background 0.15s;
            overflow: hidden;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-item:hover { background: #fafbfc; }

        /* Remove animation */
        @keyframes itemSlideOut {
            0%   { opacity: 1; transform: translateX(0); max-height: 160px; padding-top: 22px; padding-bottom: 22px; }
            45%  { opacity: 0; transform: translateX(60px); max-height: 160px; padding-top: 22px; padding-bottom: 22px; }
            100% { opacity: 0; transform: translateX(60px); max-height: 0; padding-top: 0; padding-bottom: 0; border-width: 0; }
        }
        .cart-item.removing {
            animation: itemSlideOut 0.45s cubic-bezier(0.4,0,0.2,1) forwards;
            pointer-events: none;
        }

        .cart-item-img {
            width: 88px; height: 88px;
            border-radius: 12px;
            border: 1px solid var(--cart-border);
            object-fit: contain;
            padding: 8px;
            background: white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }

        .cart-item-info { min-width: 0; }
        .cart-item-name {
            font-size: 15px; font-weight: 600;
            color: var(--cart-dark); margin-bottom: 8px; line-height: 1.3;
        }
        .cart-item-name a { color: inherit; text-decoration: none; }
        .cart-item-name a:hover { color: var(--cart-blue); }
        .cart-item-category {
            font-size: 12px; color: var(--cart-light);
            margin-bottom: 12px; font-weight: 500;
        }

        .cart-item-controls { display: flex; align-items: center; gap: 8px; }
        .qty-btn {
            width: 30px; height: 30px;
            border: 1.5px solid var(--cart-border);
            border-radius: 7px; background: white;
            font-size: 17px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--cart-dark); transition: border-color 0.15s, background 0.15s;
        }
        .qty-btn:hover { border-color: var(--cart-blue); background: #eff6ff; }
        .qty-val {
            min-width: 32px; text-align: center;
            font-size: 15px; font-weight: 700; color: var(--cart-dark);
        }
        .btn-remove {
            background: none; border: none;
            color: var(--cart-light); cursor: pointer;
            font-family: inherit; font-size: 12px; font-weight: 600;
            padding: 5px 10px; border-radius: 6px;
            transition: color 0.15s, background 0.15s; margin-left: 6px;
            display: flex; align-items: center; gap: 4px;
        }
        .btn-remove:hover { color: #dc2626; background: #fef2f2; }
        .btn-remove svg { width: 13px; height: 13px; fill: currentColor; }

        /* ── Summary sidebar ─────────────────────────────────── */
        .cart-summary {
            background: white;
            border-radius: 18px;
            border: 1px solid var(--cart-border);
            box-shadow: 0 2px 16px rgba(0,0,0,0.04);
            position: sticky;
            top: 100px;
            overflow: hidden;
        }
        .summary-head {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            padding: 20px 24px;
            color: white;
        }
        .summary-head-title {
            font-size: 15px; font-weight: 700; margin-bottom: 4px;
        }
        .summary-head-sub {
            font-size: 12px; opacity: 0.8;
        }
        .summary-body { padding: 20px 24px; }

        .summary-row {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 14px; margin-bottom: 12px; color: var(--cart-gray);
        }
        .summary-row strong { color: var(--cart-dark); font-weight: 700; }
        .summary-divider { border: none; border-top: 1px solid var(--cart-border); margin: 16px 0; }
        .summary-total-row {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 16px; font-weight: 800; color: var(--cart-dark);
            margin-bottom: 22px;
        }
        .summary-total-row span:last-child {
            font-size: 18px; color: var(--cart-navy);
        }

        .btn-quote-all {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; font-family: inherit; cursor: pointer;
            box-shadow: 0 4px 14px rgba(37,99,235,0.3);
            transition: transform 0.15s, box-shadow 0.15s;
            margin-bottom: 10px;
            text-decoration: none;
        }
        .btn-quote-all:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,0.42); color: white; }
        .btn-quote-all svg { width: 18px; height: 18px; fill: white; flex-shrink: 0; }

        .btn-clear-cart {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 11px;
            background: white; color: #dc2626;
            border: 1.5px solid #fca5a5; border-radius: 10px;
            font-size: 13px; font-weight: 600; font-family: inherit; cursor: pointer;
            transition: background 0.15s;
        }
        .btn-clear-cart:hover { background: #fef2f2; }
        .btn-clear-cart svg { width: 14px; height: 14px; fill: currentColor; }

        .summary-note {
            font-size: 12px; color: var(--cart-light);
            text-align: center; margin-top: 16px; line-height: 1.6;
            padding: 12px; background: var(--cart-bg);
            border-radius: 8px; border: 1px solid var(--cart-border);
        }

        /* ── Quote modal ─────────────────────────────────────── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
            z-index: 1200; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white; border-radius: 18px;
            max-width: 520px; width: 100%;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
            animation: modalIn 0.25s ease;
        }
        @keyframes modalIn { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        .modal-header {
            padding: 22px 28px 18px;
            border-bottom: 1px solid var(--cart-border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-header h3 { font-size: 18px; font-weight: 800; color: var(--cart-dark); }
        .modal-close-btn {
            background: #f1f5f9; border: none; width: 32px; height: 32px;
            border-radius: 8px; font-size: 18px; color: var(--cart-gray);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.15s;
        }
        .modal-close-btn:hover { background: #e2e8f0; color: var(--cart-dark); }
        .modal-body { padding: 24px 28px 28px; }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block; font-size: 13px; font-weight: 600;
            color: var(--cart-dark); margin-bottom: 6px;
        }
        .form-group input, .form-group textarea {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid #2563eb; border-radius: 9px;
            font-size: 14px; font-family: inherit; color: var(--cart-dark);
            background: var(--cart-bg); outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--cart-blue);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
            background: white;
        }
        .form-group textarea { resize: vertical; min-height: 90px; }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 900px) {
            .cart-layout { grid-template-columns: 1fr; }
            .cart-summary { position: static; }
        }
        @media (max-width: 640px) {
            .cart-page { padding: 24px 16px 60px; }
            .cart-item { grid-template-columns: 72px 1fr; padding: 16px 20px; }
            .cart-item-img { width: 72px; height: 72px; }
        }
    </style>
</head>
<body>

    <!-- PROMO BANNER -->
    <div class="promo-banner">
        <p>Armado gratis | Entrega a domicilio (envío gratuito en zona metropolitana al sur de Tamaulipas) | Garantía
            segura por 1 año | Contacto: <span class="phone-numbers">(833) 213-3837 | (833) 217-2047</span></p>
    </div>

    <!-- HEADER (same as catalogo / producto) -->
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
                            foreach ($mainCategories as $mainCatName) {
                                $mst = $conn->prepare("SELECT id, nombre FROM categoria WHERE nombre = ? AND parent_id IS NULL");
                                $mst->bind_param("s", $mainCatName);
                                $mst->execute();
                                $row = $mst->get_result()->fetch_assoc();
                                $mst->close();
                                if ($row) $mainCatsData[] = $row;
                            }
                            if (empty($mainCatsData)) {
                                $mainCatsData = [
                                    ['id'=>1,'nombre'=>'Sillería'],['id'=>9,'nombre'=>'Almacenaje'],
                                    ['id'=>13,'nombre'=>'Línea Italia'],['id'=>19,'nombre'=>'Escritorios'],
                                    ['id'=>28,'nombre'=>'Metálico'],['id'=>39,'nombre'=>'Líneas']
                                ];
                            }
                            foreach ($mainCatsData as $mainCat):
                                $sst = $conn->prepare("SELECT id, nombre FROM categoria WHERE parent_id = ? ORDER BY nombre");
                                $sst->bind_param("i", $mainCat['id']);
                                $sst->execute();
                                $subCats = $sst->get_result()->fetch_all(MYSQLI_ASSOC);
                                $sst->close();
                                if (empty($subCats)) {
                                    switch ($mainCat['id']) {
                                        case 1:  $subCats=[['id'=>2,'nombre'=>'Visita'],['id'=>3,'nombre'=>'Operativa'],['id'=>4,'nombre'=>'Ejecutiva'],['id'=>5,'nombre'=>'Sofás'],['id'=>6,'nombre'=>'Visitantes'],['id'=>7,'nombre'=>'Bancas de espera'],['id'=>8,'nombre'=>'Escolar']]; break;
                                        case 9:  $subCats=[['id'=>10,'nombre'=>'Archiveros'],['id'=>11,'nombre'=>'Gabinetes'],['id'=>12,'nombre'=>'Credenzas']]; break;
                                        case 13: $subCats=[['id'=>14,'nombre'=>'Anzio'],['id'=>15,'nombre'=>'iwork & privatt'],['id'=>16,'nombre'=>'Italia Solución general']]; break;
                                        case 19: $subCats=[['id'=>23,'nombre'=>'Básicos'],['id'=>24,'nombre'=>'Operativos en L'],['id'=>25,'nombre'=>'Semi-Ejecutivo'],['id'=>26,'nombre'=>'Ejecutivos']]; break;
                                        case 28: $subCats=[['id'=>29,'nombre'=>'Archiveros'],['id'=>30,'nombre'=>'Anaqueles'],['id'=>31,'nombre'=>'Escritorios'],['id'=>32,'nombre'=>'Gabinetes'],['id'=>33,'nombre'=>'Góndolas'],['id'=>34,'nombre'=>'Lockers'],['id'=>35,'nombre'=>'Restauranteras'],['id'=>36,'nombre'=>'Mesas'],['id'=>37,'nombre'=>'Escolar'],['id'=>38,'nombre'=>'Línea Económica']]; break;
                                        case 39: $subCats=[['id'=>40,'nombre'=>'Euro'],['id'=>41,'nombre'=>'Delta'],['id'=>42,'nombre'=>'Tempo'],['id'=>43,'nombre'=>'Línea Alva'],['id'=>44,'nombre'=>'Línea Beta'],['id'=>45,'nombre'=>'Línea Ceres'],['id'=>46,'nombre'=>'Línea Fiore'],['id'=>47,'nombre'=>'Línea Worvik'],['id'=>48,'nombre'=>'Línea Yenko']]; break;
                                    }
                                }
                                if (!empty($subCats)):
                            ?>
                                <div class="navbar-category-group">
                                    <div class="navbar-category-main">
                                        <?= htmlspecialchars($mainCat['nombre']) ?>
                                        <svg class="icon submenu-icon" viewBox="0 0 24 24" fill="none">
                                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                    <div class="navbar-subcategory-menu">
                                        <?php foreach ($subCats as $subCat):
                                            $cst = $conn->prepare("SELECT COUNT(*) AS cnt FROM producto WHERE id_categoria = ?");
                                            $cst->bind_param("i", $subCat['id']);
                                            $cst->execute();
                                            $catCount = $cst->get_result()->fetch_assoc()['cnt'] ?? 0;
                                            $cst->close();
                                        ?>
                                            <a href="catalogo.php?categoria=<?= (int)$subCat['id'] ?>" class="navbar-subcategory-item">
                                                <?= htmlspecialchars($subCat['nombre']) ?>
                                                <span class="navbar-category-count"><?= $catCount ?></span>
                                            </a>
                                        <?php endforeach ?>
                                    </div>
                                </div>
                            <?php endif; endforeach; ?>
                            <?php
                            $otherCategories = ['Libreros','Mesas','Mesas de Juntas','Islas de Trabajo','Recepción'];
                            foreach ($otherCategories as $otherCatName):
                                $ost = $conn->prepare("SELECT id FROM categoria WHERE nombre = ? AND parent_id IS NULL");
                                $ost->bind_param("s", $otherCatName);
                                $ost->execute();
                                $orow = $ost->get_result()->fetch_assoc();
                                $ost->close();
                                if ($orow):
                                    $oCatId = $orow['id'];
                                    $oct = $conn->prepare("SELECT COUNT(*) AS cnt FROM producto WHERE id_categoria = ?");
                                    $oct->bind_param("i", $oCatId);
                                    $oct->execute();
                                    $catCount = $oct->get_result()->fetch_assoc()['cnt'] ?? 0;
                                    $oct->close();
                                    if ($catCount > 0):
                            ?>
                                        <a href="catalogo.php?categoria=<?= (int)$oCatId ?>" class="navbar-category-item">
                                            <?= htmlspecialchars($otherCatName) ?>
                                            <span class="navbar-category-count"><?= $catCount ?></span>
                                        </a>
                            <?php   endif; endif; endforeach; ?>
                        </div>
                    </div>
                    <a href="catalogo.php" class="nav-link">Catálogo</a>
                    <a href="index.php#contacto" class="nav-link">Contacto</a>
                </nav>
                <div class="header-actions">
                    <a href="tel:8331881814" class="btn btn-secondary btn-small">Llamar</a>
                    <a href="https://wa.me/528331881814" class="btn btn-secondary btn-small">WhatsApp</a>
                    <a href="carrito.php" class="btn btn-primary btn-small" style="display:inline-flex;align-items:center;gap:6px;">
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

    <!-- PAGE -->
    <main class="cart-page">

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">Inicio</a>
            <span class="breadcrumb-sep">›</span>
            <a href="catalogo.php">Catálogo</a>
            <span class="breadcrumb-sep">›</span>
            <span style="color:#0f172a;font-weight:500;">Mi Carrito</span>
        </nav>

        <!-- Title -->
        <div class="cart-page-header">
            <h1>Mi Carrito</h1>
            <p>
                <?php if ($isEmpty): ?>
                    No tienes productos en tu carrito.
                <?php else: ?>
                    <?= $cartCount ?> <?= $cartCount === 1 ? 'unidad' : 'unidades' ?> en
                    <?= count($cart) ?> <?= count($cart) === 1 ? 'producto' : 'productos' ?>
                <?php endif; ?>
            </p>
        </div>

        <?php if ($isEmpty): ?>
            <!-- Empty state -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM5.82 5H21l-1.68 8.39c-.16.79-.84 1.36-1.64 1.36H8.08c-.8 0-1.49-.57-1.64-1.36L5 5H3V3H5.82z"/></svg>
                </div>
                <h3>Tu carrito está vacío</h3>
                <p>Explora nuestro catálogo y agrega los productos que necesitas.</p>
                <a href="catalogo.php" class="btn-go-catalog">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M19 11H7.83l4.88-4.88c.39-.39.39-1.03 0-1.42-.39-.39-1.02-.39-1.41 0l-6.59 6.59c-.39.39-.39 1.02 0 1.41l6.59 6.59c.39.39 1.02.39 1.41 0 .39-.39.39-1.02 0-1.41L7.83 13H19c.55 0 1-.45 1-1s-.45-1-1-1z"/></svg>
                    Ver catálogo
                </a>
            </div>

        <?php else: ?>
            <div class="cart-layout">

                <!-- Items -->
                <div class="cart-items-card">
                    <div class="cart-items-head">
                        <div class="cart-items-head-label">
                            Productos
                            <span class="count-pill"><?= count($cart) ?></span>
                        </div>
                        <a href="catalogo.php" class="cart-items-head-back">
                            ← Seguir comprando
                        </a>
                    </div>

                    <?php foreach ($cart as $item): ?>
                    <div class="cart-item" id="item-<?= (int)$item['id'] ?>">
                        <img class="cart-item-img"
                             src="<?= htmlspecialchars($item['imagen']) ?>"
                             alt="<?= htmlspecialchars($item['nombre']) ?>">
                        <div class="cart-item-info">
                            <div class="cart-item-name">
                                <a href="producto.php?id=<?= (int)$item['id'] ?>">
                                    <?= htmlspecialchars($item['nombre']) ?>
                                </a>
                            </div>
                            <div class="cart-item-category">Mobiliario de oficina</div>
                            <div class="cart-item-controls">
                                <button class="qty-btn" onclick="updateQty(<?= (int)$item['id'] ?>, -1)">−</button>
                                <span class="qty-val" id="qty-<?= (int)$item['id'] ?>"><?= (int)$item['cantidad'] ?></span>
                                <button class="qty-btn" onclick="updateQty(<?= (int)$item['id'] ?>, 1)">+</button>
                                <button class="btn-remove" onclick="removeItem(<?= (int)$item['id'] ?>)">
                                    <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Summary sidebar -->
                <aside class="cart-summary">
                    <div class="summary-head">
                        <div class="summary-head-title">Resumen del pedido</div>
                        <div class="summary-head-sub">Te contactaremos para confirmar disponibilidad</div>
                    </div>
                    <div class="summary-body">
                        <div class="summary-row">
                            <span>Tipos de producto</span>
                            <strong id="summaryCount"><?= count($cart) ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Unidades totales</span>
                            <strong id="summaryUnits"><?= $cartCount ?></strong>
                        </div>

                        <hr class="summary-divider">

                        <div class="summary-total-row">
                            <span>Total artículos</span>
                            <span id="summaryTotal"><?= $cartCount ?></span>
                        </div>

                        <a href="datos.php" class="btn-quote-all">
                            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"/></svg>
                            Proceder al pago
                        </a>

                        <button class="btn-clear-cart" onclick="clearCart()">
                            <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                            Vaciar carrito
                        </button>

                        <p class="summary-note">
                            Nos pondremos en contacto para coordinar disponibilidad, entrega y cualquier detalle adicional.
                        </p>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </main>

    <!-- Quote Modal -->
    <div class="modal-overlay" id="quoteModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Solicitar Cotización</h3>
                <button class="modal-close-btn" onclick="closeQuoteModal()">✕</button>
            </div>
            <div class="modal-body">
                <form action="https://formsubmit.co/soniaanaya@ofiequipo.com.mx" method="POST" target="_blank">
                    <input type="hidden" name="_subject" value="Nueva Cotización desde Carrito — Ofiequipo">
                    <input type="hidden" name="_captcha" value="false">
                    <input type="hidden" name="_template" value="table">
                    <input type="hidden" name="Productos" id="hiddenProducts">

                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre" placeholder="Tu nombre completo" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" placeholder="tucorreo@ejemplo.com" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="tel" name="telefono" placeholder="Tu número de teléfono">
                    </div>
                    <div class="form-group">
                        <label>Productos a cotizar</label>
                        <textarea name="productos_lista" id="productosList" readonly></textarea>
                    </div>
                    <div class="form-group">
                        <label>Comentarios adicionales</label>
                        <textarea name="comentarios" placeholder="Preguntas, especificaciones, cantidades adicionales..."></textarea>
                    </div>

                    <button type="submit" class="btn-quote-all" style="margin-top:8px">
                        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        Enviar Solicitud
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateQty(id, delta) {
            const qtyEl  = document.getElementById('qty-' + id);
            const newQty = Math.max(1, parseInt(qtyEl.textContent) + delta);
            qtyEl.textContent = newQty;
            fetch('apis/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update&id=${id}&cantidad=${newQty}`
            }).then(r => r.json()).then(data => refreshSummary(data));
        }

        function removeItem(id) {
            const row = document.getElementById('item-' + id);
            row.classList.add('removing');

            const req = fetch('apis/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=remove&id=${id}`
            }).then(r => r.json());

            setTimeout(() => {
                row.remove();
                req.then(data => {
                    refreshSummary(data);
                    if (data.count === 0) location.reload();
                });
            }, 460);
        }

        function clearCart() {
            if (!confirm('¿Vaciar el carrito?')) return;
            fetch('apis/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=clear'
            }).then(() => location.reload());
        }

        function refreshSummary(data) {
            const count = data.cart ? data.cart.length : 0;
            const units = data.count || 0;
            document.getElementById('summaryCount').textContent = count;
            document.getElementById('summaryUnits').textContent = units;
            document.getElementById('summaryTotal').textContent = units;
        }

        function openQuoteModal() {
            let list = '';
            document.querySelectorAll('.cart-item:not(.removing)').forEach(item => {
                const name = item.querySelector('.cart-item-name').textContent.trim();
                const qty  = item.querySelector('.qty-val').textContent.trim();
                list += `${name} (x${qty})\n`;
            });
            document.getElementById('productosList').value = list;
            document.getElementById('hiddenProducts').value = list;
            document.getElementById('quoteModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeQuoteModal() {
            document.getElementById('quoteModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        document.getElementById('quoteModal').addEventListener('click', function(e) {
            if (e.target === this) closeQuoteModal();
        });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeQuoteModal(); });

        // ── Navbar JS ─────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            const toggle   = document.getElementById('navbarCategoryToggle');
            const dropdown = document.getElementById('navbarCategoryDropdown');
            if (toggle && dropdown) {
                toggle.addEventListener('click', function (e) {
                    e.preventDefault(); e.stopPropagation();
                    const active = dropdown.classList.toggle('active');
                    toggle.classList.toggle('active', active);
                });
                document.addEventListener('click', function (e) {
                    if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                        toggle.classList.remove('active');
                    }
                });
                dropdown.addEventListener('click', function (e) {
                    if (e.target.closest('.navbar-category-item') || e.target.closest('.navbar-subcategory-item')) {
                        dropdown.classList.remove('active');
                        toggle.classList.remove('active');
                    }
                });
            }

            document.querySelectorAll('.navbar-category-group').forEach(function (group) {
                const main    = group.querySelector('.navbar-category-main');
                const submenu = group.querySelector('.navbar-subcategory-menu');
                if (main && submenu) {
                    main.addEventListener('click', function (e) {
                        e.preventDefault(); e.stopPropagation();
                        document.querySelectorAll('.navbar-subcategory-menu.active').forEach(function (m) {
                            if (m !== submenu) { m.classList.remove('active'); m.previousElementSibling && m.previousElementSibling.classList.remove('active'); }
                        });
                        submenu.classList.toggle('active');
                        main.classList.toggle('active');
                    });
                }
            });

            const menuToggle = document.querySelector('.menu-toggle');
            const nav        = document.querySelector('.nav');
            if (menuToggle && nav) {
                menuToggle.addEventListener('click', function () {
                    menuToggle.classList.toggle('active');
                    nav.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>
