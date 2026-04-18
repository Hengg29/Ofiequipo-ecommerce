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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $conn->prepare("
    SELECT p.*, c.nombre AS cat_nombre, c.parent_id,
           cp.nombre AS parent_nombre, cp.id AS parent_cat_id
    FROM producto p
    LEFT JOIN categoria c  ON p.categoria_id = c.id
    LEFT JOIN categoria cp ON c.parent_id    = cp.id
    WHERE p.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$producto) { header('Location: index.php'); exit; }

$imagenUrl = getImageUrl($producto['imagen'] ?? '');
$inCart    = !empty(array_filter($_SESSION['cart'] ?? [], fn($i) => $i['id'] === $id));

// Related products (same category)
$related = [];
if ($producto['categoria_id']) {
    $rs = $conn->prepare("SELECT id, nombre, descripcion, imagen FROM producto WHERE categoria_id = ? AND id != ? ORDER BY id DESC LIMIT 8");
    $rs->bind_param('ii', $producto['categoria_id'], $id);
    $rs->execute();
    $related = $rs->get_result()->fetch_all(MYSQLI_ASSOC);
    $rs->close();
}

// Header variables
$search_query  = '';
$categoria_id  = 0;
$totalProducts = 0;
$tp = $conn->query("SELECT COUNT(*) AS cnt FROM producto");
if ($tp) $totalProducts = $tp->fetch_assoc()['cnt'] ?? 0;
$cartCount = array_sum(array_column($_SESSION['cart'] ?? [], 'cantidad'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($producto['nombre']) ?> — Ofiequipo de Tampico</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="shortcut icon" type="image/png" href="icono_logo.png">
    <link rel="apple-touch-icon" href="icono_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* ── Navbar category dropdown (same as catalogo) ─────────── */
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
        .navbar-category-dropdown-menu.active { display: block; animation: slideDownMenu 0.2s ease; }
        @keyframes slideDownMenu { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }

        .navbar-category-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 20px; color: var(--text-gray); text-decoration: none;
            border-bottom: 1px solid #f3f4f6; transition: all 0.2s ease;
            font-size: 14px; margin: 0 8px; border-radius: 6px;
        }
        .navbar-category-item:last-child { border-bottom: none; }
        .navbar-category-item:hover, .navbar-category-item.active {
            background: #eff6ff; color: var(--primary-blue);
        }
        .navbar-category-main {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 20px; color: var(--text-dark);
            font-size: 13px; font-weight: 600; cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            margin: 0 8px; border-radius: 6px;
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
        .navbar-subcategory-item:hover, .navbar-subcategory-item.active {
            background: #eff6ff; color: var(--primary-blue);
        }
        .navbar-category-count {
            background: #f1f5f9; color: #64748b;
            font-size: 11px; font-weight: 600;
            padding: 2px 7px; border-radius: 10px;
        }
        .navbar-category-item.active .navbar-category-count,
        .navbar-subcategory-item.active .navbar-category-count {
            background: #dbeafe; color: var(--primary-blue);
        }

        /* ── Mobile nav ─────────────────────────────────────────── */
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
            .nav .navbar-category-dropdown-menu { position: static; display: none; box-shadow: none; border: none; background: #f8f9fa; margin: 0; padding: 0; width: 100%; }
            .nav .navbar-category-dropdown-menu.active { display: block; animation: none; }
            .nav .navbar-category-item, .nav .navbar-category-main { margin: 0; border-radius: 0; }
            .nav .navbar-subcategory-item { margin: 0; border-radius: 0; }
        }

        /* ── Product page styles ─────────────────────────────────── */
        .product-page {
            max-width: 1160px;
            margin: 0 auto;
            padding: 40px 32px 80px;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex; align-items: center; gap: 6px;
            font-size: 13px; color: #64748b;
            margin-bottom: 32px;
        }
        .breadcrumb a { color: var(--primary-blue,#1e3a8a); text-decoration: none; font-weight: 500; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb-sep { color: #cbd5e1; }

        /* Product hero grid */
        .product-hero {
            display: grid;
            grid-template-columns: 1fr 480px;
            gap: 48px;
            align-items: start;
            margin-bottom: 72px;
        }

        /* Image panel */
        .product-img-panel {
            position: sticky;
            top: 96px;
        }
        .product-img-main {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            overflow: hidden;
            aspect-ratio: 4/3;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        }
        .product-img-main img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.4s ease;
            cursor: zoom-in;
        }
        .product-img-main:hover img { transform: scale(1.04); }

        /* ── Image lightbox ──────────────────────────────────── */
        .img-lightbox {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.92);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(6px);
        }
        .img-lightbox.open { display: flex; }

        .img-lightbox-inner {
            position: relative;
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            user-select: none;
        }

        .img-lightbox img {
            max-width: 90vw;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 4px;
            transform-origin: center center;
            transition: transform 0.15s ease;
            cursor: grab;
            will-change: transform;
            pointer-events: none; /* handled by wrapper */
        }
        .img-lightbox img.grabbing { cursor: grabbing; }

        /* Controls bar */
        .lb-controls {
            position: fixed;
            bottom: 28px; left: 50%; transform: translateX(-50%);
            display: flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 8px 16px; border-radius: 40px;
            z-index: 10000;
        }
        .lb-btn {
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50%; color: white; font-size: 18px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: background 0.15s;
            flex-shrink: 0;
        }
        .lb-btn:hover { background: rgba(255,255,255,0.25); }
        .lb-btn svg { width: 18px; height: 18px; fill: white; }
        .lb-zoom-label {
            font-size: 13px; color: rgba(255,255,255,0.85);
            font-weight: 600; min-width: 46px; text-align: center;
            font-family: inherit;
        }

        /* Close button */
        .lb-close {
            position: fixed; top: 20px; right: 20px;
            width: 40px; height: 40px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50%; color: white; font-size: 22px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 10000;
            transition: background 0.15s;
        }
        .lb-close:hover { background: rgba(255,255,255,0.25); }

        /* Zoom hint */
        .lb-hint {
            position: fixed; top: 24px; left: 50%; transform: translateX(-50%);
            font-size: 12px; color: rgba(255,255,255,0.5);
            pointer-events: none; z-index: 10000; white-space: nowrap;
        }

        /* Info panel */
        .product-info-panel {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .product-cat-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            color: var(--primary-blue,#1e3a8a);
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
            margin-bottom: 16px;
            width: fit-content;
            letter-spacing: 0.02em;
        }

        .product-name-title {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.8px;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .product-stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 24px;
            width: fit-content;
        }
        .product-stock-badge.in-stock { background: #dcfce7; color: #16a34a; }
        .product-stock-badge.coming-soon { background: #f1f5f9; color: #64748b; }
        .product-stock-badge .dot { width: 7px; height: 7px; border-radius: 50%; }
        .product-stock-badge.in-stock .dot { background: #16a34a; }
        .product-stock-badge.coming-soon .dot { background: #94a3b8; }

        /* Price block */
        .product-price-block {
            display: flex;
            align-items: baseline;
            gap: 12px;
            margin-bottom: 20px;
        }
        .product-price {
            font-size: 34px;
            font-weight: 800;
            color: var(--primary-blue, #1e3a8a);
            letter-spacing: -1px;
            line-height: 1;
        }
        .product-price-note {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }
        .product-price-placeholder {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1.5px dashed #cbd5e1;
            border-radius: 10px;
            padding: 10px 18px;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .product-price-placeholder svg { width: 15px; height: 15px; fill: #cbd5e1; flex-shrink: 0; }

        .product-divider {
            border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;
        }

        .product-desc-label {
            font-size: 12px; font-weight: 700; letter-spacing: 0.08em;
            text-transform: uppercase; color: #94a3b8; margin-bottom: 10px;
        }
        .product-description {
            font-size: 15px;
            line-height: 1.7;
            color: #475569;
        }
        .product-description.empty {
            font-style: italic;
            color: #94a3b8;
        }

        /* CTA buttons */
        .product-cta {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 32px;
        }

        .btn-add-cart {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 28px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 18px rgba(37,99,235,0.35);
            transition: transform 0.15s, box-shadow 0.15s, background 0.2s;
            letter-spacing: 0.01em;
        }
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(37,99,235,0.45);
        }
        .btn-add-cart:active { transform: translateY(0); }
        .btn-add-cart.added {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
            box-shadow: 0 4px 18px rgba(22,163,74,0.35);
        }

        .btn-whatsapp {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 13px 28px;
            background: white;
            color: #1e3a8a;
            border: 2px solid #1e3a8a;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-whatsapp:hover { background: #eff6ff; }

        /* Specs card */
        .specs-card {
            margin-top: 28px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px 22px;
        }
        .specs-card-title {
            font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.08em;
            color: #94a3b8; margin-bottom: 14px;
        }
        .specs-row {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 13.5px; padding: 8px 0;
            border-bottom: 1px solid #e2e8f0; color: #475569;
        }
        .specs-row:last-child { border-bottom: none; }
        .specs-row strong { color: #0f172a; font-weight: 600; }

        /* Related section */
        .related-section { margin-top: 16px; }
        .related-title {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .related-subtitle {
            font-size: 15px;
            color: #64748b;
            margin-bottom: 32px;
        }

        /* Reuse product-card from style.css but with local overrides */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 28px;
        }
        .product-card .product-image { height: 240px; }
        .product-card .product-content { padding: 22px; }
        .product-card .product-name { font-size: 17px; margin-bottom: 10px; }
        .product-card .product-description { font-size: 14px; margin-bottom: 20px; }
        .product-footer { display: flex; gap: 10px; }
        .product-footer .btn { flex: 1; text-align: center; padding: 12px 16px; font-size: 13px; }

        /* WhatsApp FAB (same as catalogo) */
        .whatsapp-fab {
            position: fixed; right: 20px; bottom: 20px;
            width: 56px; height: 56px; background: #25D366;
            color: #fff; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            z-index: 9998; box-shadow: 0 4px 12px rgba(37,211,102,0.4);
            text-decoration: none; transition: all 0.3s ease;
        }
        .whatsapp-fab:hover { transform: scale(1.1); box-shadow: 0 6px 20px rgba(37,211,102,0.5); }
        .whatsapp-fab svg { width: 32px; height: 32px; display: block; }

        /* Toast notification */
        .cart-toast {
            position: fixed; bottom: 90px; right: 24px;
            background: #0f172a; color: white;
            padding: 12px 20px; border-radius: 12px;
            font-size: 14px; font-weight: 500;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            transform: translateY(20px); opacity: 0;
            transition: all 0.25s ease;
            z-index: 9999; pointer-events: none;
            display: flex; align-items: center; gap: 10px;
        }
        .cart-toast.show { transform: translateY(0); opacity: 1; }
        .cart-toast svg { width: 18px; height: 18px; fill: #4ade80; flex-shrink: 0; }

        /* Responsive */
        @media (max-width: 1024px) {
            .product-hero { grid-template-columns: 1fr; gap: 32px; }
            .product-img-panel { position: static; }
            .product-name-title { font-size: 26px; }
        }
        @media (max-width: 640px) {
            .product-page { padding: 24px 16px 60px; }
            .product-name-title { font-size: 22px; }
            .products-grid { grid-template-columns: 1fr 1fr; gap: 16px; }
            .product-card .product-image { height: 180px; }
        }
        @media (max-width: 480px) {
            .products-grid { grid-template-columns: 1fr; }
        }

        /* Logo link */
        .logo { text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px; transition: opacity 0.3s; }
        .logo:hover { opacity: 0.8; }
        .logo h1 { margin: 0; color: inherit; }
    </style>
</head>
<body>

    <!-- PROMO BANNER -->
    <div class="promo-banner">
        <p>Armado gratis | Entrega a domicilio (envío gratuito en zona metropolitana al sur de Tamaulipas) | Garantía
            segura por 1 año | Contacto: <span class="phone-numbers">(833) 213-3837 | (833) 217-2047</span></p>
    </div>

    <!-- HEADER (exact same as catalogo.php) -->
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
                            <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                                                $cst = $conn->prepare("SELECT COUNT(*) AS cnt FROM producto WHERE categoria_id = ?");
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
                                    $oct = $conn->prepare("SELECT COUNT(*) AS cnt FROM producto WHERE categoria_id = ?");
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
                    <button onclick="openCartDrawer()" class="btn btn-secondary btn-small" style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM5.82 5H21l-1.68 8.39c-.16.79-.84 1.36-1.64 1.36H8.08c-.8 0-1.49-.57-1.64-1.36L5 5H3V3H5.82z"/></svg>
                        Carrito
                        <span class="cart-badge-count" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>background:#ef4444;color:white;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;padding:0 3px;"><?= $cartCount ?></span>
                    </button>
                </div>
                <button class="menu-toggle" aria-label="Toggle menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- PAGE CONTENT -->
    <main class="product-page">

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">Inicio</a>
            <span class="breadcrumb-sep">›</span>
            <a href="catalogo.php">Catálogo</a>
            <?php if ($producto['cat_nombre']): ?>
                <span class="breadcrumb-sep">›</span>
                <a href="catalogo.php?categoria=<?= (int)$producto['categoria_id'] ?>">
                    <?= htmlspecialchars($producto['cat_nombre']) ?>
                </a>
            <?php endif; ?>
            <span class="breadcrumb-sep">›</span>
            <span style="color:#0f172a;font-weight:500;"><?= htmlspecialchars($producto['nombre']) ?></span>
        </nav>

        <!-- Product hero -->
        <div class="product-hero">

            <!-- Image -->
            <div class="product-img-panel">
                <div class="product-img-main" onclick="openLightbox()" title="Haz clic para ampliar">
                    <img src="<?= htmlspecialchars($imagenUrl) ?>"
                         alt="<?= htmlspecialchars($producto['nombre']) ?>"
                         id="mainProductImg">
                </div>
                <p style="text-align:center;font-size:12px;color:#94a3b8;margin-top:10px;display:flex;align-items:center;justify-content:center;gap:5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="#94a3b8"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    Haz clic para ampliar · Scroll para hacer zoom
                </p>
            </div>

            <!-- Info -->
            <div class="product-info-panel">

                <?php if ($producto['cat_nombre']): ?>
                <div class="product-cat-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <?= htmlspecialchars($producto['cat_nombre']) ?>
                </div>
                <?php endif; ?>

                <h1 class="product-name-title"><?= htmlspecialchars($producto['nombre']) ?></h1>

                <!-- Price — column not in DB yet, placeholder shown -->
                <?php if (!empty($producto['precio']) && (float)$producto['precio'] > 0): ?>
                <div class="product-price-block">
                    <span class="product-price">
                        $<?= number_format((float)$producto['precio'], 2, '.', ',') ?>
                    </span>
                    <span class="product-price-note">MXN + IVA</span>
                </div>
                <?php else: ?>
                <div class="product-price-placeholder">
                    <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                    Precio disponible próximamente — cotiza sin compromiso
                </div>
                <?php endif; ?>

                <!-- Stock badge — stock not in DB yet, show "Disponible" -->
                <div class="product-stock-badge in-stock">
                    <span class="dot"></span>
                    Disponible bajo pedido
                </div>

                <hr class="product-divider">

                <p class="product-desc-label">Descripción</p>
                <?php $desc = trim($producto['descripcion'] ?? ''); ?>
                <p class="product-description <?= $desc ? '' : 'empty' ?>">
                    <?= $desc ? nl2br(htmlspecialchars($desc)) : 'Sin descripción disponible por el momento.' ?>
                </p>

                <!-- Specs card -->
                <div class="specs-card">
                    <div class="specs-card-title">Detalles del producto</div>
                    <div class="specs-row">
                        <span>Categoría</span>
                        <strong><?= htmlspecialchars($producto['cat_nombre'] ?? '—') ?></strong>
                    </div>
                    <?php if ($producto['parent_nombre']): ?>
                    <div class="specs-row">
                        <span>Línea</span>
                        <strong><?= htmlspecialchars($producto['parent_nombre']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="specs-row">
                        <span>Stock</span>
                        <strong style="color:#16a34a;">Disponible (consultar)</strong>
                    </div>
                    <div class="specs-row">
                        <span>Garantía</span>
                        <strong>1 año</strong>
                    </div>
                </div>

                <!-- CTA buttons -->
                <div class="product-cta">
                    <button class="btn-add-cart" id="addCartBtn"
                        onclick="handleAddToCart()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM5.82 5H21l-1.68 8.39c-.16.79-.84 1.36-1.64 1.36H8.08c-.8 0-1.49-.57-1.64-1.36L5 5H3V3H5.82z"/></svg>
                        <?= $inCart ? 'Ya en el carrito — Agregar más' : 'Añadir al carrito' ?>
                    </button>
                    <a href="https://wa.me/528331881814?text=<?= urlencode('Hola, me interesa el producto: ' . $producto['nombre']) ?>"
                       target="_blank" class="btn-whatsapp">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.118 1.523 5.847L0 24l6.334-1.49A11.934 11.934 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.896 0-3.67-.502-5.199-1.378l-.372-.22-3.762.886.935-3.663-.242-.38A9.937 9.937 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                        Consultar por WhatsApp
                    </a>
                </div>

            </div>
        </div>

        <!-- Related products -->
        <?php if (!empty($related)): ?>
        <section class="related-section">
            <h2 class="related-title">Productos relacionados</h2>
            <p class="related-subtitle">
                Más productos de la categoría
                <strong><?= htmlspecialchars($producto['cat_nombre'] ?? '') ?></strong>
            </p>
            <div class="products-grid">
                <?php foreach ($related as $rel):
                    $relImg = getImageUrl($rel['imagen'] ?? '');
                    $relDesc = trim($rel['descripcion'] ?? '');
                ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($relImg) ?>"
                             alt="<?= htmlspecialchars($rel['nombre']) ?>">
                    </div>
                    <div class="product-content">
                        <h3 class="product-name"><?= htmlspecialchars($rel['nombre']) ?></h3>
                        <p class="product-description">
                            <?= $relDesc ? htmlspecialchars(mb_strimwidth($relDesc, 0, 100, '…')) : 'Producto de alta calidad para tu oficina.' ?>
                        </p>
                        <div class="product-footer">
                            <button class="btn btn-primary"
                                onclick="addToCart(<?= (int)$rel['id'] ?>,'<?= addslashes(htmlspecialchars($rel['nombre'])) ?>','<?= addslashes($relImg) ?>')">
                                Agregar
                            </button>
                            <a class="btn btn-secondary"
                               href="producto.php?id=<?= (int)$rel['id'] ?>">Ver detalles</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </main>

    <!-- WhatsApp FAB -->
    <a href="https://wa.me/528331881814" target="_blank" class="whatsapp-fab" aria-label="Contáctanos por WhatsApp">
        <svg viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.118 1.523 5.847L0 24l6.334-1.49A11.934 11.934 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.896 0-3.67-.502-5.199-1.378l-.372-.22-3.762.886.935-3.663-.242-.38A9.937 9.937 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
        </svg>
    </a>

    <!-- Toast notification -->
    <div class="cart-toast" id="cartToast">
        <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        <span>¡Producto agregado al carrito!</span>
    </div>

    <?php require_once __DIR__ . '/includes/cart_drawer.php'; ?>

    <!-- IMAGE LIGHTBOX -->
    <div class="img-lightbox" id="imgLightbox">
        <div class="img-lightbox-inner" id="lbInner">
            <img src="" alt="" id="lbImg">
        </div>
        <button class="lb-close" onclick="closeLightbox()" title="Cerrar (Esc)">✕</button>
        <div class="lb-hint">Scroll para zoom · Arrastra para mover · Doble clic para resetear</div>
        <div class="lb-controls">
            <button class="lb-btn" onclick="lbZoom(-0.25)" title="Alejar">
                <svg viewBox="0 0 24 24"><path d="M19 13H5v-2h14v2z"/></svg>
            </button>
            <span class="lb-zoom-label" id="lbZoomLabel">100%</span>
            <button class="lb-btn" onclick="lbZoom(0.25)" title="Acercar">
                <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            </button>
            <div style="width:1px;height:20px;background:rgba(255,255,255,0.2);margin:0 4px;"></div>
            <button class="lb-btn" onclick="lbReset()" title="Restablecer">
                <svg viewBox="0 0 24 24"><path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg>
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // ── Product page cart handler ──────────────────────────────
    const productId    = <?= (int)$id ?>;
    const productName  = <?= json_encode($producto['nombre']) ?>;
    const productImage = <?= json_encode($imagenUrl) ?>;

    function handleAddToCart() {
        addToCart(productId, productName, productImage);
    }

    // Called back by cart_drawer.php after addToCart resolves
    function onCartAdd(data) {
        const btn = document.getElementById('addCartBtn');
        btn.classList.add('added');
        btn.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            ¡Agregado al carrito!
        `;

        // Show toast
        const toast = document.getElementById('cartToast');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2800);

        // Open drawer after brief delay
        setTimeout(() => openCartDrawer(), 350);

        // Reset button after drawer is open
        setTimeout(() => {
            btn.classList.remove('added');
            btn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM5.82 5H21l-1.68 8.39c-.16.79-.84 1.36-1.64 1.36H8.08c-.8 0-1.49-.57-1.64-1.36L5 5H3V3H5.82z"/></svg>
                Añadir al carrito
            `;
        }, 3200);
    }

    // ── Navbar JS (same as catalogo.php) ──────────────────────
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

        // Subcategory toggles
        document.querySelectorAll('.navbar-category-group').forEach(function (group) {
            const main    = group.querySelector('.navbar-category-main');
            const submenu = group.querySelector('.navbar-subcategory-menu');
            if (main && submenu) {
                main.addEventListener('click', function (e) {
                    e.preventDefault(); e.stopPropagation();
                    // Close others
                    document.querySelectorAll('.navbar-subcategory-menu.active').forEach(function (m) {
                        if (m !== submenu) {
                            m.classList.remove('active');
                            m.previousElementSibling && m.previousElementSibling.classList.remove('active');
                        }
                    });
                    submenu.classList.toggle('active');
                    main.classList.toggle('active');
                });
            }
        });

        // Mobile menu toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const nav        = document.querySelector('.nav');
        if (menuToggle && nav) {
            menuToggle.addEventListener('click', function () {
                menuToggle.classList.toggle('active');
                nav.classList.toggle('active');
            });
        }
    });

    // ── Lightbox ──────────────────────────────────────────────
    let lbScale   = 1;
    let lbTransX  = 0;
    let lbTransY  = 0;
    let lbDragging = false;
    let lbDragStartX, lbDragStartY;
    const LB_MIN = 0.5;
    const LB_MAX = 5;

    function openLightbox() {
        const src = document.getElementById('mainProductImg').src;
        const alt = document.getElementById('mainProductImg').alt;
        const lb  = document.getElementById('imgLightbox');
        const img = document.getElementById('lbImg');
        img.src = src;
        img.alt = alt;
        lbReset(false);
        lb.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        document.getElementById('imgLightbox').classList.remove('open');
        document.body.style.overflow = '';
    }

    function lbApply(animate) {
        const img = document.getElementById('lbImg');
        img.style.transition = animate ? 'transform 0.2s ease' : 'none';
        img.style.transform  = `translate(${lbTransX}px, ${lbTransY}px) scale(${lbScale})`;
        document.getElementById('lbZoomLabel').textContent = Math.round(lbScale * 100) + '%';
        img.style.cursor = lbScale > 1 ? 'grab' : 'default';
    }

    function lbZoom(delta, cx, cy) {
        const inner = document.getElementById('lbInner');
        const rect  = inner.getBoundingClientRect();
        // focal point relative to image center
        const ox = (cx != null ? cx : rect.width  / 2) - rect.width  / 2 - lbTransX;
        const oy = (cy != null ? cy : rect.height / 2) - rect.height / 2 - lbTransY;
        const prevScale = lbScale;
        lbScale = Math.min(LB_MAX, Math.max(LB_MIN, lbScale + delta));
        const ratio = lbScale / prevScale - 1;
        lbTransX -= ox * ratio;
        lbTransY -= oy * ratio;
        lbApply(true);
    }

    function lbReset(animate = true) {
        lbScale  = 1;
        lbTransX = 0;
        lbTransY = 0;
        lbApply(animate);
    }

    // Mouse wheel zoom
    document.getElementById('lbInner').addEventListener('wheel', function (e) {
        e.preventDefault();
        const rect  = this.getBoundingClientRect();
        const delta = e.deltaY < 0 ? 0.2 : -0.2;
        lbZoom(delta, e.clientX - rect.left, e.clientY - rect.top);
    }, { passive: false });

    // Drag to pan
    const lbInner = document.getElementById('lbInner');
    lbInner.addEventListener('mousedown', function (e) {
        if (lbScale <= 1) return;
        lbDragging  = true;
        lbDragStartX = e.clientX - lbTransX;
        lbDragStartY = e.clientY - lbTransY;
        document.getElementById('lbImg').classList.add('grabbing');
        e.preventDefault();
    });
    window.addEventListener('mousemove', function (e) {
        if (!lbDragging) return;
        lbTransX = e.clientX - lbDragStartX;
        lbTransY = e.clientY - lbDragStartY;
        lbApply(false);
    });
    window.addEventListener('mouseup', function () {
        lbDragging = false;
        document.getElementById('lbImg').classList.remove('grabbing');
    });

    // Touch support (pinch-to-zoom + drag)
    let lbLastTouchDist = null;
    lbInner.addEventListener('touchstart', function (e) {
        if (e.touches.length === 2) {
            lbLastTouchDist = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
        } else if (e.touches.length === 1 && lbScale > 1) {
            lbDragging   = true;
            lbDragStartX = e.touches[0].clientX - lbTransX;
            lbDragStartY = e.touches[0].clientY - lbTransY;
        }
    }, { passive: true });
    lbInner.addEventListener('touchmove', function (e) {
        if (e.touches.length === 2 && lbLastTouchDist !== null) {
            e.preventDefault();
            const dist  = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
            const cx = (e.touches[0].clientX + e.touches[1].clientX) / 2;
            const cy = (e.touches[0].clientY + e.touches[1].clientY) / 2;
            const rect = lbInner.getBoundingClientRect();
            lbZoom((dist - lbLastTouchDist) * 0.01, cx - rect.left, cy - rect.top);
            lbLastTouchDist = dist;
        } else if (e.touches.length === 1 && lbDragging) {
            lbTransX = e.touches[0].clientX - lbDragStartX;
            lbTransY = e.touches[0].clientY - lbDragStartY;
            lbApply(false);
        }
    }, { passive: false });
    lbInner.addEventListener('touchend', function () {
        lbLastTouchDist = null;
        lbDragging      = false;
    });

    // Double-click to reset
    lbInner.addEventListener('dblclick', () => lbReset());

    // Click on overlay (not controls) to close
    document.getElementById('imgLightbox').addEventListener('click', function (e) {
        if (e.target === this || e.target === document.getElementById('lbInner'))
            closeLightbox();
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function (e) {
        if (!document.getElementById('imgLightbox').classList.contains('open')) return;
        if (e.key === 'Escape')     closeLightbox();
        if (e.key === '+' || e.key === '=') lbZoom(0.25);
        if (e.key === '-')          lbZoom(-0.25);
        if (e.key === '0')          lbReset();
    });
    </script>
</body>
</html>
