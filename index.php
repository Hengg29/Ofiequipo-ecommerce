<?php
session_start();
require_once __DIR__ . '/apis/db.php';

// Función helper para normalizar y obtener URLs de imágenes
function getImageUrl($imagePath)
{
    if (empty($imagePath)) {
        return 'https://via.placeholder.com/800x600?text=Sin+imagen';
    }

    // Limpiar espacios en blanco al inicio y final
    $imagePath = trim($imagePath);

    // Si está vacío después de trim, usar placeholder
    if (empty($imagePath)) {
        return 'https://via.placeholder.com/800x600?text=Sin+imagen';
    }

    // Si es una URL externa (http:// o https://), usar image.php como proxy para evitar CORS
    if (preg_match('/^https?:\/\//i', $imagePath)) {
        // Usar rawurlencode para preservar caracteres especiales ya codificados en la URL
        // rawurlencode es más apropiado para URLs completas
        return 'image.php?u=' . rawurlencode($imagePath);
    }

    // Verificar también con filter_var como respaldo
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
        return 'image.php?u=' . rawurlencode($imagePath);
    }

    // Normalizar rutas locales para servir a través de image.php
    // Normalizar barras (Windows puede usar \)
    $imagePath = str_replace('\\', '/', $imagePath);
    $imgTrim = ltrim($imagePath, '/');

    // Si ya tiene prefijo Uploads/uploads, usar tal cual
    if (stripos($imgTrim, 'uploads/') === 0 || stripos($imgTrim, 'Uploads/') === 0) {
        // Usar image.php para servir la imagen local
        $parts = explode('/', $imgTrim);
        $encodedParts = array_map('rawurlencode', $parts);
        $encodedPath = implode('/', $encodedParts);
        return 'image.php?path=' . $encodedPath;
    } else {
        // Agregar prefijo Uploads/ si no lo tiene
        $fullPath = 'Uploads/' . $imgTrim;
        $parts = explode('/', $fullPath);
        $encodedParts = array_map('rawurlencode', $parts);
        $encodedPath = implode('/', $encodedParts);
        return 'image.php?path=' . $encodedPath;
    }
}

// Obtener categorías: conservar las anteriores y agregar las nuevas
// Categorías anteriores: Islas de Trabajo, Libreros, Mesas, Mesas de Juntas, Recepción, Sillería
// Categorías nuevas: Almacenaje, Línea Italia, Escritorios, Metálico
$mainCategoryNames = [
    'Islas de Trabajo',
    'Libreros',
    'Mesas',
    'Mesas de Juntas',
    'Recepción',
    'Sillería',
    'Almacenaje',
    'Línea Italia',
    'Escritorios',
    'Metálico',
    'Líneas'
];
$cats = [];
foreach ($mainCategoryNames as $catName) {
    $stmt = $conn->prepare("SELECT id, nombre FROM categoria WHERE nombre = ? AND parent_id IS NULL");
    $stmt->bind_param("s", $catName);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $cats[] = $row;
    }
    $stmt->close();
}

// Ordenar las categorías según el orden especificado
usort($cats, function ($a, $b) use ($mainCategoryNames) {
    $posA = array_search($a['nombre'], $mainCategoryNames);
    $posB = array_search($b['nombre'], $mainCategoryNames);
    return ($posA === false ? 999 : $posA) - ($posB === false ? 999 : $posB);
});

// Para cada categoría obtener productos de todas sus subcategorías
$catProducts = [];
if (!empty($cats)) {
    foreach ($cats as $c) {
        $products = [];
        // Obtener todas las subcategorías de esta categoría principal
        $subCatStmt = $conn->prepare("SELECT id FROM categoria WHERE parent_id = ?");
        $subCatStmt->bind_param("i", $c['id']);
        $subCatStmt->execute();
        $subCatResult = $subCatStmt->get_result();
        $subCatIds = [];
        while ($subCat = $subCatResult->fetch_assoc()) {
            $subCatIds[] = $subCat['id'];
        }
        $subCatStmt->close();

        // Si tiene subcategorías, obtener productos de todas ellas
        if (!empty($subCatIds)) {
            $placeholders = str_repeat('?,', count($subCatIds) - 1) . '?';
            $stmt = $conn->prepare("SELECT id, nombre, descripcion, imagen, stock, destacado FROM producto WHERE categoria_id IN ($placeholders) ORDER BY id DESC LIMIT 6");
            $stmt->bind_param(str_repeat('i', count($subCatIds)), ...$subCatIds);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res)
                while ($p = $res->fetch_assoc())
                    $products[] = $p;
            $stmt->close();
        } else {
            // Si no tiene subcategorías, obtener productos directamente de la categoría principal
            $stmt = $conn->prepare("SELECT id, nombre, descripcion, imagen, stock, destacado FROM producto WHERE categoria_id = ? ORDER BY id DESC LIMIT 6");
            $stmt->bind_param("i", $c['id']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res)
                while ($p = $res->fetch_assoc())
                    $products[] = $p;
            $stmt->close();
        }
        $catProducts[$c['id']] = $products;
    }
}

// Lista completa de productos para el <select>
$allProducts = [];
$resAll = $conn->query("SELECT id, nombre FROM producto ORDER BY nombre");
if ($resAll)
    while ($p = $resAll->fetch_assoc())
        $allProducts[] = $p;

// Variables para el menú de productos
$categoria_id = isset($_GET['categoria']) ? (int) $_GET['categoria'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Contar total de productos
$totalProducts = 0;
$countRes = $conn->query("SELECT COUNT(*) AS cnt FROM producto");
if ($countRes) {
    $totalProducts = $countRes->fetch_assoc()['cnt'] ?? 0;
}

// --- NUEVO: obtener productos destacados desde la BD ---
// Detectar si existe columna 'destacado' o el typo 'Destactado'
$destCol = null;
$cols = $conn->query("SHOW COLUMNS FROM producto");
if ($cols) {
    while ($col = $cols->fetch_assoc()) {
        if (strtolower($col['Field']) === 'destacado')
            $destCol = 'destacado';
        if ($col['Field'] === 'Destactado')
            $destCol = 'Destactado';
    }
}

// Obtener destacados (si hay columna) o fallback a últimos 8 productos
$featuredProducts = [];
if ($destCol) {
    $stmtF = $conn->prepare("SELECT id, nombre, descripcion, imagen, stock FROM producto WHERE `$destCol` = 1 ORDER BY id DESC LIMIT 8");
    if ($stmtF) {
        $stmtF->execute();
        $resF = $stmtF->get_result();
        if ($resF)
            while ($p = $resF->fetch_assoc())
                $featuredProducts[] = $p;
        $stmtF->close();
    }
} else {
    $resF = $conn->query("SELECT id, nombre, descripcion, imagen, stock FROM producto ORDER BY id DESC LIMIT 8");
    if ($resF)
        while ($p = $resF->fetch_assoc())
            $featuredProducts[] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ofiequipo de Tampico - Mobiliario de Oficina Premium</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="shortcut icon" type="image/png" href="icono_logo.png">
    <link rel="apple-touch-icon" href="icono_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <!-- Adding SweetAlert2 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* COMENTADO PARA USO FUTURO: Bat Animation Styles */
        /*
        .bat-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        }


        .bat {
            position: absolute;
            width: 60px;
            height: 40px;
            opacity: 0;
            animation: flyBat 3s ease-in-out forwards;
        }


        .bat svg {
            width: 100%;
            height: 100%;
            fill: #1a1a2e;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }


        .bat:nth-child(1) {
            top: 10%;
            animation-delay: 0s;
        }


        .bat:nth-child(2) {
            top: 30%;
            animation-delay: 0.3s;
        }


        .bat:nth-child(3) {
            top: 50%;
            animation-delay: 0.6s;
        }


        .bat:nth-child(4) {
            top: 20%;
            animation-delay: 0.9s;
        }


        .bat:nth-child(5) {
            top: 40%;
            animation-delay: 1.2s;
        }


        @keyframes flyBat {
            0% {
                left: -100px;
                opacity: 0;
                transform: translateY(0) rotate(0deg);
            }
            10% {
                opacity: 1;
            }
            50% {
                transform: translateY(-20px) rotate(5deg);
            }
            90% {
                opacity: 1;
            }
            100% {
                left: calc(100% + 100px);
                opacity: 0;
                transform: translateY(-5deg);
            }
        }


        @keyframes flapWings {
            0%, 100% {
                transform: scaleX(1);
            }
            50% {
                transform: scaleX(0.8);
            }
        }


        .bat svg {
            animation: flapWings 0.3s ease-in-out infinite;
        }


        /* Hide bat container after animation completes */
        .bat-container.hidden {
            display: none;
        }

        */

        /* Desktop navbar - optimized for all desktop and laptop screens */
        .nav {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .nav::-webkit-scrollbar {
            height: 4px;
        }

        .nav::-webkit-scrollbar-track {
            background: transparent;
        }

        .nav::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }

        .nav::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Add subtle gradient fade to indicate scrollable content */
        .nav::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 20px;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.8));
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .nav:hover::after {
            opacity: 1;
        }


        /* Estilos base para números de teléfono en promo-banner */
        .promo-banner .phone-numbers {
            color: #ffffff !important;
            font-weight: 600;
        }

        /* Desktop: show header actions */
        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-left: 20px;
        }

        /* Desktop: hide menu toggle */
        .menu-toggle {
            display: none;
        }

        /* Desktop nav styles */
        .nav {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        /* Desktop nav styles for large screens */
        @media (min-width: 1024px) {
            .menu-toggle {
                display: none;
            }

            .nav {
                display: flex !important;
                position: static;
                background: transparent;
                box-shadow: none;
                flex-direction: row;
                padding: 0;
                max-height: none;
                overflow: visible;
            }
        }

        /* Show hamburger menu on mobile and tablet */
        @media (max-width: 1024px) {
            .menu-toggle {
                display: flex;
            }
        }

        /* Large laptops and desktop (1440px and above) */
        @media (min-width: 1440px) {
            .header-actions {
                gap: 24px;
            }

            .header-actions .btn {
                padding: 10px 16px;
                font-size: 15px;
            }

            .header-actions .btn-small {
                padding: 8px 14px;
                font-size: 14px;
            }
        }

        /* Standard laptops (1280px - 1439px) */
        @media (max-width: 1439px) and (min-width: 1281px) {
            .header-actions {
                gap: 18px;
            }

            .header-actions .btn {
                padding: 8px 14px;
                font-size: 14px;
            }

            .header-actions .btn-small {
                padding: 6px 12px;
                font-size: 13px;
            }
        }

        /* WhatsApp FAB - properly sized and positioned */
        .whatsapp-fab {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 56px;
            height: 56px;
            background: #25D366;
            color: white;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9998;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .whatsapp-fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.5);
        }

        .whatsapp-fab svg {
            width: 32px;
            height: 32px;
            display: block;
        }

        /* Carousel Styles */
        .carousel-container {
            position: relative;
            width: 100%;
            height: 500px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        /* Responsive aspect ratio for promo image */
        @media (min-width: 1200px) {
            .carousel-container {
                height: 400px;
                /* Ajusta según tu imagen */
            }
        }

        @media (max-width: 1199px) and (min-width: 768px) {
            .carousel-container {
                height: 350px;
            }
        }

        .carousel-slides {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .carousel-slide.active {
            opacity: 1;
        }

        /* Fallback: Show first slide if no JavaScript */
        .carousel-slide:first-child {
            opacity: 1;
        }

        .carousel-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            border-radius: 12px;
        }

        /* Promo image specific styling - show complete image */
        .carousel-slide:nth-child(1) img {
            object-fit: cover;
            object-position: center;
            background: #ffffff;
            padding: 1rem;
        }

        /* Logo specific styling */
        .carousel-slide:nth-child(2) img {
            object-fit: cover;
            object-position: center;
            background: #f8fafc;
            padding: 1rem 2rem 3rem 2rem;
            object-position: center top;
        }

        .carousel-indicators {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 10;
            background: rgba(0, 0, 0, 0.3);
            padding: 8px 16px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .indicator {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .indicator:hover {
            background: rgba(255, 255, 255, 0.8);
            transform: scale(1.1);
        }

        .indicator.active {
            background: white;
            transform: scale(1.3);
            border-color: rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 100%;
            display: none;
            /* Hide navigation arrows */
            justify-content: space-between;
            padding: 0 20px;
            z-index: 10;
        }

        .carousel-prev,
        .carousel-next {
            display: none;
            /* Hide navigation buttons */
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .carousel-prev:hover,
        .carousel-next:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        /* Pause carousel on hover */
        .carousel-container:hover {
            animation-play-state: paused;
        }

        /* Product Detail Modal Styles */
        .product-detail-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .product-detail-image img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            object-position: center;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .product-detail-info h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1a1a2e;
        }

        .product-detail-description {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
            margin-bottom: 24px;
        }

        .product-detail-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .product-detail-actions .btn {
            flex: 1;
            min-width: 140px;
        }

        /* Responsive for product detail modal */
        @media (max-width: 768px) {
            .product-detail-content {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .product-detail-image img {
                height: 250px;
            }

            .product-detail-actions {
                flex-direction: column;
            }

            .product-detail-actions .btn {
                width: 100%;
            }
        }

        /* Responsive carousel heights */
        @media (max-width: 768px) {
            .carousel-container {
                height: 400px;
            }
        }

        @media (max-width: 480px) {
            .carousel-container {
                height: 350px;
            }
        }

        /* Laptop optimization (1024px - 1280px) - keep horizontal nav with adjusted spacing */
        @media (max-width: 1280px) and (min-width: 1025px) {
            .nav {
                gap: 8px;
                margin: 0 8px;
                overflow-x: auto;
                overflow-y: hidden;
            }


            .header-actions {
                gap: 16px;
                flex-wrap: nowrap;
            }

            .header-actions .btn {
                padding: 8px 12px;
                font-size: 13px;
                white-space: nowrap;
                min-width: auto;
            }

            .header-actions .btn-small {
                padding: 6px 10px;
                font-size: 12px;
            }

            .hero-cta {
                display: flex;
                flex-direction: row;
                gap: 16px;
                justify-content: center;
                align-items: center;
                flex-wrap: wrap;
                max-width: 100%;
                margin-top: 24px;
            }

            .hero-cta .btn {
                flex: 0 0 auto;
                min-width: 160px;
                max-width: 200px;
                padding: 12px 16px;
                font-size: 14px;
                white-space: nowrap;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
            }

            .collections-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
            }
        }

        /* iPad specific optimization - landscape */
        @media (max-width: 1024px) and (min-width: 769px) and (orientation: landscape) {
            .hero-cta {
                display: flex;
                flex-direction: row;
                gap: 20px;
                justify-content: center;
                align-items: center;
                flex-wrap: nowrap;
                max-width: 100%;
                margin-top: 24px;
            }

            .hero-cta .btn {
                flex: 0 0 auto;
                min-width: 180px;
                max-width: 220px;
                padding: 14px 20px;
                font-size: 15px;
                white-space: nowrap;
            }
        }

        /* iPad specific optimization - portrait */
        @media (max-width: 1024px) and (min-width: 769px) and (orientation: portrait) {
            .hero-cta {
                display: flex;
                flex-direction: column;
                gap: 16px;
                justify-content: center;
                align-items: center;
                max-width: 100%;
                margin-top: 24px;
            }

            .hero-cta .btn {
                width: 100%;
                max-width: 300px;
                padding: 14px 20px;
                font-size: 15px;
                white-space: nowrap;
                text-align: center;
            }
        }

        /* Small laptops and iPad (1024px and below) - still keep horizontal nav */
        @media (max-width: 1024px) and (min-width: 769px) {
            .container {
                padding-left: 20px;
                padding-right: 20px;
            }

            .nav {
                gap: 8px;
                margin: 0 6px;
                overflow-x: auto;
                overflow-y: hidden;
            }


            .header-actions {
                gap: 12px;
                flex-wrap: nowrap;
            }

            .header-actions .btn {
                padding: 6px 10px;
                font-size: 12px;
                white-space: nowrap;
                min-width: auto;
            }

            .header-actions .btn-small {
                padding: 5px 8px;
                font-size: 11px;
            }

            .hero {
                padding: 40px 0;
                flex-direction: column;
                text-align: center;
            }

            .hero-content {
                text-align: center;
                max-width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .hero-title {
                font-size: 32px;
                line-height: 1.2;
            }

            .hero-subtitle {
                font-size: 16px;
            }

            .hero-cta {
                display: flex;
                flex-direction: row;
                gap: 16px;
                justify-content: center;
                align-items: center;
                flex-wrap: wrap;
                max-width: 100%;
                margin-top: 24px;

            }

            .hero-cta .btn {
                flex: 0 0 auto;
                min-width: 160px;
                max-width: 200px;
                padding: 12px 16px;
                font-size: 14px;
                white-space: nowrap;
            }

            .hero-image {
                margin-top: 32px;
                max-width: 100%;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }

            .product-image img {
                height: 200px;
            }

            .collections-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }

            .benefits-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
        }

        /* Tablets and mobile (768px and below) - switch to hamburger menu */
        @media (max-width: 768px) {
            .promo-banner .phone-numbers {
                color: #ffffff !important;
                font-weight: 600;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            }

            .container {
                padding-left: 20px;
                padding-right: 20px;
            }

            .hero {
                padding: 40px 0;
                flex-direction: column;
                text-align: center;
            }

            .hero-content {
                text-align: center;
                max-width: 100%;
                margin-bottom: 24px;
            }

            .hero-title {
                font-size: 32px;
                line-height: 1.2;
                margin-bottom: 16px;
            }

            .hero-subtitle {
                font-size: 16px;
                margin-bottom: 24px;
            }

            .hero-cta {
                display: flex;
                flex-direction: column;
                gap: 16px;
                justify-content: center;
                align-items: center;
                max-width: 100%;
                margin-top: 24px;
            }

            .hero-cta .btn {
                width: 100%;
                max-width: 300px;
                padding: 14px 20px;
                font-size: 15px;
                white-space: nowrap;
                text-align: center;
            }

            .hero-image {
                margin-top: 32px;
                max-width: 100%;
                border-radius: 12px;
                overflow: hidden;
            }

            .hero-image img {
                width: 100%;
                height: auto;
                object-fit: cover;
                object-position: center;
            }

            /* Carousel responsive styles for mobile */
            .carousel-nav {
                padding: 0 10px;
            }

            .carousel-prev,
            .carousel-next {
                width: 35px;
                height: 35px;
                font-size: 1.2rem;
            }

            /* Ensure slides fill container on small screens */
            .carousel-slides,
            .carousel-slide {
                height: 100%;
            }

            .carousel-slide img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: center;
                padding: 0;
                /* override any desktop padding */
                background: transparent;
                /* avoid framed look on mobile */
                border-radius: 12px;
            }

            /* Remove special paddings/backgrounds for the first two slides on mobile */
            .carousel-slide:nth-child(1) img,
            .carousel-slide:nth-child(2) img {
                padding: 0 !important;
                background: transparent !important;
            }

            /* Mobile: hide desktop header actions */
            .header-actions {
                display: none;
            }

            /* Mobile: show menu toggle button */
            .menu-toggle {
                display: flex;
                flex-direction: column;
                gap: 4px;
                width: 40px;
                height: 40px;
                padding: 8px;
                background: transparent;
                border: none;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .menu-toggle:hover {
                background: rgba(0, 0, 0, 0.05);
                border-radius: 6px;
            }

            .menu-toggle span {
                width: 100%;
                height: 3px;
                background: var(--text-dark, #1a1a2e);
                border-radius: 2px;
                transition: all 0.3s ease;
            }

            .menu-toggle.active span:nth-child(1) {
                transform: rotate(45deg) translate(5px, 5px);
            }

            .menu-toggle.active span:nth-child(2) {
                opacity: 0;
            }

            .menu-toggle.active span:nth-child(3) {
                transform: rotate(-45deg) translate(7px, -6px);
            }

            /* Mobile: nav becomes vertical dropdown */
            .nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                gap: 0;
                padding: 16px;
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
                max-height: 70vh;
                overflow-y: auto;
                overflow-x: hidden;
                margin: 0;
                border-radius: 0 0 12px 12px;
                z-index: 1000;
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
            }

            /* Smooth scrollbar styling for mobile nav */
            .nav::-webkit-scrollbar {
                width: 6px;
            }

            .nav::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }

            .nav::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            .nav::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            .nav.active {
                display: flex !important;
            }

            .nav a {
                padding: 12px 16px;
                color: var(--text-dark);
                text-decoration: none;
                border-bottom: 1px solid #f0f0f0;
                transition: all 0.3s ease;
                display: block;
                width: 100%;
            }

            .nav a:hover {
                background: #f8f9fa;
                color: var(--primary-blue);
            }

            .nav a:last-child {
                border-bottom: none;
            }

            .nav .navbar-category-dropdown {
                position: static;
                display: block;
                width: 100%;
            }

            .nav .navbar-category-dropdown-menu {
                position: static;
                display: none;
                width: 100%;
                box-shadow: none;
                border: none;
                background: #f8f9fa;
                margin: 0;
                padding: 0;
                max-height: 60vh;
                overflow-y: auto;
                overflow-x: hidden;
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
            }

            /* Smooth scrollbar styling for category dropdown menu */
            .nav .navbar-category-dropdown-menu::-webkit-scrollbar {
                width: 6px;
            }

            .nav .navbar-category-dropdown-menu::-webkit-scrollbar-track {
                background: #e5e7eb;
                border-radius: 10px;
            }

            .nav .navbar-category-dropdown-menu::-webkit-scrollbar-thumb {
                background: #94a3b8;
                border-radius: 10px;
            }

            .nav .navbar-category-dropdown-menu::-webkit-scrollbar-thumb:hover {
                background: #64748b;
            }

            .nav .navbar-category-dropdown-menu.active {
                display: block;
            }

            .nav .navbar-category-item,
            .nav .navbar-category-main {
                padding: 12px 16px;
                border-bottom: 1px solid #e5e7eb;
                margin: 0;
                border-radius: 0;
            }

            .nav .navbar-subcategory-item {
                padding: 10px 16px;
                margin: 0;
                border-radius: 0;
                border-bottom: 1px solid #e5e7eb;
            }

            .nav .navbar-subcategory-menu {
                margin-left: 0;
                border-left: none;
                border-top: 1px solid #e5e7eb;
                background: #f8f9fa;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }

            .product-image img {
                height: 200px;
            }

            .collections-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }

            .whatsapp-fab {
                width: 52px;
                height: 52px;
                right: 16px;
                bottom: 16px;
            }

            .whatsapp-fab svg {
                width: 28px;
                height: 28px;
            }

            .bat-container {
                display: none;
            }

            .benefits-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }
        }

        /* Mobile phones (640px and below) */
        @media (max-width: 640px) {
            .promo-banner {
                font-size: 12px;
                padding: 8px 12px;
            }

            .promo-banner .phone-numbers {
                color: #ffffff !important;
                font-weight: 600;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            }

            .hero {
                padding: 24px 0;
            }

            .hero-title {
                font-size: 24px;
            }

            .hero-eyebrow,
            .hero-subtitle {
                font-size: 14px;
            }

            .hero-cta {
                flex-direction: column;
                gap: 12px;
                width: 100%;
                justify-content: center;
                align-items: center;
            }

            .hero-cta .btn {
                width: 100%;
                max-width: 280px;
                padding: 12px 16px;
                font-size: 14px;
                justify-content: center;
                text-align: center;
            }

            .section-title {
                font-size: 28px;
            }

            .section-subtitle {
                font-size: 14px;
            }

            .products-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .collections-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .product-image img {
                height: 180px;
            }

            .product-footer {
                flex-direction: column;
                gap: 8px;
            }

            .product-footer .btn {
                width: 100%;
                padding: 12px;
                font-size: 14px;
                justify-content: center;
            }

            /* Improve product cards on mobile */
            .product-card {
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
            }

            .product-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            }

            .category-card {
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
            }

            .category-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            }

            /* Modal optimized for mobile screens */
            .modal {
                height: 100vh;
                overflow: hidden;
            }

            .modal .modal-content {
                width: 100%;
                max-width: 100%;
                height: 100vh;
                max-height: 100vh;
                margin: 0;
                border-radius: 0;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
            }

            .modal.active {
                align-items: stretch;
                padding: 0;
            }

            .modal .modal-body {
                padding: 16px;
                flex: 1;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
            }

            .modal-header {
                padding: 16px;
            }

            .modal-header h2 {
                font-size: 20px;
            }

            .form-group input,
            .form-group textarea,
            .form-group select {
                width: 100%;
                font-size: 16px;
                padding: 12px;
            }

            .form-actions {
                display: flex;
                gap: 8px;
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
            }

            .header .logo h1 {
                font-size: 18px;
            }

            .cta-section {
                padding: 40px 0;
            }

            .cta-content h2 {
                font-size: 24px;
            }

            .cta-buttons {
                flex-direction: column;
                gap: 12px;
            }

            .cta-buttons .btn {
                width: 100%;
            }
        }

        /* Very small phones (480px and below) */
        @media (max-width: 480px) {
            .container {
                padding-left: 16px;
                padding-right: 16px;
            }

            .menu-toggle {
                width: 36px;
                height: 36px;
            }

            .whatsapp-fab {
                width: 48px;
                height: 48px;
            }

            .whatsapp-fab svg {
                width: 24px;
                height: 24px;
            }

            .hero-title {
                font-size: 22px;
            }

            .section-title {
                font-size: 24px;
            }

            /* Optimize scroll for very small screens */
            .nav {
                max-height: 65vh;
            }

            .nav .navbar-category-dropdown-menu {
                max-height: 50vh;
            }
        }

        /* Why Us Section Styles */
        .why-us {
            padding: 80px 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .why-us-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
            margin-top: 48px;
        }

        .why-us-card {
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .why-us-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .why-us-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .why-us-icon svg {
            width: 40px;
            height: 40px;
        }

        .why-us-card h3 {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 16px;
        }

        .why-us-card p {
            font-size: 16px;
            line-height: 1.6;
            color: #64748b;
        }

        /* Google Reviews Section Styles */
        .google-reviews {
            padding: 80px 0;
            background: white;
        }

        .google-reviews-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .google-rating-title {
            font-size: 48px;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0 0 16px 0;
            letter-spacing: -0.02em;
        }

        .google-rating-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .google-stars {
            display: flex;
            gap: 2px;
        }

        .google-stars .star {
            color: #fbbf24;
            font-size: 24px;
        }

        .google-stars .star.half {
            background: linear-gradient(90deg, #fbbf24 50%, #e5e7eb 50%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .google-review-count {
            font-size: 16px;
            color: #6b7280;
            margin: 0;
        }

        .google-logo {
            display: flex;
            align-items: center;
        }

        .reviews-carousel-container {
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }

        .reviews-carousel {
            overflow: hidden;
            width: 100%;
        }

        .reviews-track {
            display: flex;
            transition: transform 0.5s ease-in-out;
            gap: 20px;
            padding: 10px 0;
        }

        .review-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            min-width: 320px;
            flex-shrink: 0;
        }

        .review-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .reviewer-info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #10b981;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            flex-shrink: 0;
        }

        .reviewer-details {
            flex: 1;
        }

        .reviewer-name-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .reviewer-details h4 {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0;
        }

        .google-icon {
            display: flex;
            align-items: center;
        }

        .review-date {
            font-size: 14px;
            color: #6b7280;
            margin: 0;
        }

        .review-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .review-stars {
            display: flex;
            gap: 1px;
        }

        .review-stars .star {
            color: #fbbf24;
            font-size: 16px;
        }

        .verified-badge {
            display: flex;
            align-items: center;
        }

        .review-content p {
            font-size: 15px;
            line-height: 1.5;
            color: #374151;
            margin: 0;
        }

        .reviews-nav {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .reviews-next {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            pointer-events: all;
        }

        .reviews-next:hover {
            background: #e5e7eb;
            color: #374151;
        }

        .reviews-cta {
            text-align: center;
            margin-top: 48px;
        }

        .reviews-cta .btn {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
        }

        /* Responsive styles for new sections */
        @media (max-width: 768px) {
            .why-us {
                padding: 60px 0;
            }

            .why-us-grid {
                grid-template-columns: 1fr;
                gap: 24px;
                margin-top: 32px;
            }

            .why-us-card {
                padding: 24px;
            }

            .why-us-icon {
                width: 64px;
                height: 64px;
                margin-bottom: 20px;
            }

            .why-us-icon svg {
                width: 32px;
                height: 32px;
            }

            .why-us-card h3 {
                font-size: 20px;
            }

            .google-reviews {
                padding: 60px 0;
            }

            .google-reviews-header {
                margin-bottom: 32px;
            }

            .google-rating-title {
                font-size: 36px;
                margin-bottom: 12px;
            }

            .google-rating-info {
                flex-direction: row;
                gap: 8px;
                flex-wrap: wrap;
            }

            .reviews-carousel-container {
                margin-top: 32px;
                padding: 0 10px;
            }

            .review-card {
                padding: 18px;
                min-width: calc(100vw - 80px);
                max-width: 400px;
            }

            .reviews-nav {
                right: 5px;
            }

            .reviews-next {
                width: 36px;
                height: 36px;
            }

            .reviewer-details h4 {
                font-size: 15px;
            }

            .review-content p {
                font-size: 14px;
            }

            .google-stars .star {
                font-size: 20px;
            }

            .reviews-cta {
                margin-top: 32px;
            }
        }

        @media (max-width: 480px) {
            .why-us-card {
                padding: 20px;
            }

            .why-us-card h3 {
                font-size: 18px;
            }

            .google-reviews {
                padding: 40px 0;
            }

            .google-reviews-header {
                margin-bottom: 24px;
            }

            .google-rating-title {
                font-size: 28px;
                margin-bottom: 10px;
            }

            .google-rating-info {
                gap: 6px;
            }

            .google-stars .star {
                font-size: 18px;
            }

            .google-review-count {
                font-size: 14px;
            }

            .review-card {
                padding: 16px;
                min-width: calc(100vw - 60px);
            }

            .reviewer-avatar {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }

            .reviewer-avatar svg {
                width: 18px;
                height: 18px;
            }

            .reviewer-details h4 {
                font-size: 14px;
            }

            .review-date {
                font-size: 12px;
            }

            .review-stars .star {
                font-size: 14px;
            }

            .review-content p {
                font-size: 13px;
                line-height: 1.6;
            }

            .reviews-nav {
                right: 2px;
            }

            .reviews-next {
                width: 32px;
                height: 32px;
            }

            .reviews-next svg {
                width: 16px;
                height: 16px;
            }

            .reviews-cta {
                margin-top: 24px;
            }

            .reviews-cta .btn {
                padding: 10px 20px;
                font-size: 14px;
            }
        }

        @media (max-width: 360px) {
            .google-reviews {
                padding: 30px 0;
            }

            .google-reviews-header {
                margin-bottom: 20px;
            }

            .google-rating-title {
                font-size: 24px;
                margin-bottom: 8px;
            }

            .google-rating-info {
                gap: 4px;
            }

            .google-stars .star {
                font-size: 16px;
            }

            .google-review-count {
                font-size: 12px;
            }

            .review-card {
                min-width: calc(100vw - 50px);
                padding: 14px;
            }

            .reviewer-details h4 {
                font-size: 13px;
            }

            .review-content p {
                font-size: 12px;
            }
        }

        /* Logo as link styles */
        .logo {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: opacity 0.3s ease;
        }

        .logo:hover {
            opacity: 0.8;
        }

        .logo h1 {
            margin: 0;
            color: inherit;
        }

        /* Social Media Icons Styles */
        .social-links {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .social-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: #666;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .social-link svg {
            width: 20px;
            height: 20px;
            transition: all 0.3s ease;
        }

        .social-link.facebook {
            background: #f0f2f5;
            color: #1877f2;
        }

        .social-link.facebook:hover {
            background: #1877f2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(24, 119, 242, 0.3);
        }

        .social-link.instagram {
            background: #fdf2f8;
            color: #e1306c;
        }

        .social-link.instagram:hover {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(225, 48, 108, 0.3);
        }

        .social-link:hover svg {
            transform: scale(1.1);
        }

        /* Responsive social links */
        @media (max-width: 768px) {
            .social-links {
                flex-direction: column;
                gap: 12px;
            }

            .social-link {
                justify-content: center;
                padding: 12px 16px;
                font-size: 16px;
            }

            .social-link svg {
                width: 24px;
                height: 24px;
            }
        }

        /* Improve touch targets for mobile accessibility */
        @media (hover: none) and (pointer: coarse) {
            .btn {
                min-height: 44px;
                padding: 12px 20px;
            }


            .modal-close {
                min-width: 44px;
                min-height: 44px;
            }

            .social-link {
                min-height: 44px;
                min-width: 44px;
                justify-content: center;
            }
        }

        /* Navbar Category Dropdown - Estilo como en la imagen */
        .navbar-category-dropdown {
            position: relative;
            display: inline-block;
        }

        .navbar-category-toggle {
            background: transparent;
            color: var(--text-dark);
            border: none;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            position: relative;
        }

        .navbar-category-toggle:hover {
            color: var(--primary-blue);
        }

        .navbar-category-toggle .icon {
            transition: transform 0.3s ease;
            width: 12px;
            height: 12px;
            opacity: 0.7;
        }

        .navbar-category-toggle.active .icon {
            transform: rotate(180deg);
        }

        .navbar-category-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
            min-width: 250px;
            max-height: 500px;
            overflow-y: auto;
            overflow-x: hidden;
            margin-top: 8px;
            padding: 8px 0;
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        /* Smooth scrollbar styling for desktop category dropdown */
        .navbar-category-dropdown-menu::-webkit-scrollbar {
            width: 8px;
        }

        .navbar-category-dropdown-menu::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .navbar-category-dropdown-menu::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .navbar-category-dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .navbar-category-dropdown-menu.active {
            display: block;
            animation: slideDown 0.2s ease;
        }

        .navbar-category-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            color: var(--text-gray);
            text-decoration: none;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 400;
            position: relative;
            margin: 0 8px;
            border-radius: 6px;
        }

        .navbar-category-item:last-child {
            border-bottom: none;
        }

        .navbar-category-item:hover {
            background: #f8fafc;
            color: var(--primary-blue);
            padding-left: 24px;
        }

        .navbar-category-item.active {
            background: #f0f4ff;
            color: var(--primary-blue);
            font-weight: 500;
        }

        .navbar-category-item.active:hover {
            background: #e6f0ff;
        }

        .navbar-category-item .navbar-category-count {
            float: right;
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
            margin-left: 8px;
        }

        .navbar-category-item.active .navbar-category-count {
            background: rgba(30, 64, 175, 0.2);
            color: var(--primary-blue);
        }

        /* Estilos para menú jerárquico */
        .navbar-category-group {
            position: relative;
        }

        .navbar-category-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            color: var(--text-gray);
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
            margin: 0 8px;
            border-radius: 6px;
        }

        .navbar-category-main:hover {
            background: #f8fafc;
            color: var(--primary-blue);
            padding-left: 24px;
        }

        .navbar-category-main .submenu-icon {
            transition: transform 0.3s ease;
            width: 12px;
            height: 12px;
            opacity: 0.7;
        }

        .navbar-category-main.active .submenu-icon {
            transform: rotate(90deg);
        }

        .navbar-subcategory-menu {
            display: none;
            background: #f8fafc;
            border-left: 3px solid var(--primary-blue);
            margin-left: 20px;
            border-radius: 0 6px 6px 0;
            max-height: 400px;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        /* Smooth scrollbar styling for subcategory menu */
        .navbar-subcategory-menu::-webkit-scrollbar {
            width: 6px;
        }

        .navbar-subcategory-menu::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 10px;
        }

        .navbar-subcategory-menu::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }

        .navbar-subcategory-menu::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        .navbar-subcategory-menu.active {
            display: block;
            animation: slideDown 0.2s ease;
        }

        .navbar-subcategory-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            color: var(--text-gray);
            text-decoration: none;
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.2s ease;
            font-size: 13px;
            font-weight: 400;
            position: relative;
            margin: 0 8px;
            border-radius: 6px;
        }

        .navbar-subcategory-item:last-child {
            border-bottom: none;
        }

        .navbar-subcategory-item:hover {
            background: white;
            color: var(--primary-blue);
            padding-left: 28px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-subcategory-item.active {
            background: white;
            color: var(--primary-blue);
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-subcategory-item.active:hover {
            background: #f0f4ff;
        }

        .navbar-subcategory-item .navbar-category-count {
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 500;
            margin-left: 8px;
        }

        .navbar-subcategory-item.active .navbar-category-count {
            background: rgba(30, 64, 175, 0.2);
            color: var(--primary-blue);
        }

        .promo-banner p {
            color: white !important;
            margin: 0;
        }

        .promo-banner .phone-numbers {
            color: white !important;
            font-weight: 600;
        }

        .promo-banner * {
            color: white !important;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== Auth Modal Styles ===== */
        .auth-modal-content {
            max-width: 460px;
            width: 90%;
            overflow: hidden;
        }

        .auth-modal-content::before {
            content: '';
            display: block;
            height: 4px;
            background: linear-gradient(90deg, #1e3a8a 0%, #3b82f6 50%, #1d4ed8 100%);
        }

        .auth-screen {
            position: relative;
        }

        #authWelcome {
            padding: 44px 40px 32px;
            text-align: center;
        }

        #authWelcome .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
        }

        .auth-brand {
            margin-bottom: 20px;
        }

        .auth-brand-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.25);
        }

        .auth-brand-icon svg {
            width: 26px;
            height: 26px;
            fill: white;
        }

        .auth-brand h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-blue, #1e3a8a);
            letter-spacing: 0.05em;
        }

        .auth-brand h2 span {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-gray, #475569);
            letter-spacing: 0.15em;
            margin-top: 2px;
        }

        .auth-divider {
            width: 48px;
            height: 3px;
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            margin: 0 auto 22px;
            border-radius: 2px;
        }

        .auth-question {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-dark, #0f172a);
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .auth-subtitle {
            font-size: 14px;
            color: var(--text-gray, #475569);
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .auth-welcome-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .auth-welcome-actions .btn {
            width: 100%;
            padding: 14px;
            font-size: 15px;
        }

        .auth-skip {
            font-size: 13px;
            color: var(--text-light, #94a3b8);
            margin-top: 4px;
        }

        .auth-skip a {
            color: var(--text-light, #94a3b8);
            text-decoration: underline;
            transition: color 0.2s;
        }

        .auth-skip a:hover {
            color: var(--primary-blue, #1e3a8a);
        }

        .auth-switch {
            text-align: center;
            font-size: 13px;
            color: var(--text-gray, #475569);
            margin-top: 20px;
        }

        .auth-switch a {
            color: var(--primary-blue, #1e3a8a);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-switch a:hover {
            text-decoration: underline;
        }

        .auth-welcome-actions .btn-primary {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .auth-welcome-actions .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
            transform: translateY(-1px);
        }

        .auth-welcome-actions .btn-secondary {
            border: 2px solid #e2e8f0;
            background: white;
            color: var(--primary-blue, #1e3a8a);
            font-weight: 600;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .auth-welcome-actions .btn-secondary:hover {
            border-color: #3b82f6;
            background: #f0f7ff;
        }

        @media (max-width: 768px) {
            #authWelcome {
                padding: 40px 24px 28px;
            }
        }
        /* ===== Fin Auth Modal ===== */
    </style>
</head>


<body>
    <!-- COMENTADO PARA USO FUTURO: Adding bat animation container -->
    <!--
    <div class="bat-container" id="batContainer">
        <div class="bat">
            <svg viewBox="0 0 64 48" xmlns="http://www.w3.org/2000/svg">
                <path d="M32 8c-4 0-8 2-10 6-2-4-6-6-10-6-6 0-10 4-10 10 0 8 8 14 20 22 12-8 20-14 20-22 0-6-4-10-10-10zm0 0c4 0 8 2 10 6 2-4 6-6 10-6 6 0 10 4 10 10 0 8-8 14-20 22-12-8-20-14-20-22 0-6 4-10 10-10z"/>
                <ellipse cx="32" cy="28" rx="3" ry="4"/>
            </svg>
        </div>
        <div class="bat">
            <svg viewBox="0 0 64 48" xmlns="http://www.w3.org/2000/svg">
                <path d="M32 8c-4 0-8 2-10 6-2-4-6-6-10-6-6 0-10 4-10 10 0 8 8 14 20 22 12-8 20-14 20-22 0-6-4-10-10-10zm0 0c4 0 8 2 10 6 2-4 6-6 10-6 6 0 10 4 10 10 0 8-8 14-20 22-12-8-20-14-20-22 0-6 4-10 10-10z"/>
                <ellipse cx="32" cy="28" rx="3" ry="4"/>
            </svg>
        </div>
        <div class="bat">
            <svg viewBox="0 0 64 48" xmlns="http://www.w3.org/2000/svg">
                <path d="M32 8c-4 0-8 2-10 6-2-4-6-6-10-6-6 0-10 4-10 10 0 8 8 14 20 22 12-8 20-14 20-22 0-6-4-10-10-10zm0 0c4 0 8 2 10 6 2-4 6-6 10-6 6 0 10 4 10 10 0 8-8 14-20 22-12-8-20-14-20-22 0-6 4-10 10-10z"/>
                <ellipse cx="32" cy="28" rx="3" ry="4"/>
            </svg>
        </div>
        <div class="bat">
            <svg viewBox="0 0 64 48" xmlns="http://www.w3.org/2000/svg">
                <path d="M32 8c-4 0-8 2-10 6-2-4-6-6-10-6-6 0-10 4-10 10 0 8 8 14 20 22 12-8 20-14 20-22 0-6-4-10-10-10zm0 0c4 0 8 2 10 6 2-4 6-6 10-6 6 0 10 4 10 10 0 8-8 14-20 22-12-8-20-14-20-22 0-6 4-10 10-10z"/>
                <ellipse cx="32" cy="28" rx="3" ry="4"/>
            </svg>
        </div>
        <div class="bat">
            <svg viewBox="0 0 64 48" xmlns="http://www.w3.org/2000/svg">
                <path d="M32 8c-4 0-8 2-10 6-2-4-6-6-10-6-6 0-10 4-10 10 0 8 8 14 20 22 12-8 20-14 20-22 0-6-4-10-10-10zm0 0c4 0 8 2 10 6 2-4 6-6 10-6 6 0 10 4 10 10 0 8-8 14-20 22-12-8-20-14-20-22 0-6 4-10 10-10z"/>
                <ellipse cx="32" cy="28" rx="3" ry="4"/>
            </svg>
        </div>
    </div>
    -->


    <!-- Promotional Banner -->
    <div class="promo-banner">
        <p>
            Armado gratis |
            Entrega a domicilio (envío gratuito en zona metropolitana al sur de Tamaulipas)|
            Garantía segura por 1 año |
            Contacto: <span class="phone-numbers">(833) 213-3837 | (833) 217-2047</span>
        </p>
    </div>


    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <img src="icono_logo.png" alt="OFIEQUIPO Logo" class="logo-icon">
                    <h1>OFIEQUIPO<span>DE TAMPICO</span></h1>
                </a>
                <nav class="nav" id="mainNav">
                    <a href="#inicio" class="nav-link">Inicio</a>
                    <div class="navbar-category-dropdown">
                        <a href="#" class="navbar-category-toggle" id="navbarCategoryToggle">
                            Productos
                            <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>
                        <div class="navbar-category-dropdown-menu" id="navbarCategoryDropdown">
                            <a href="catalogo.php<?= !empty($search_query) ? '?search=' . urlencode($search_query) : '' ?>"
                                class="navbar-category-item <?= $categoria_id === 0 ? 'active' : '' ?>">
                                Todos los productos
                                <span class="navbar-category-count"><?= $totalProducts ?></span>
                            </a>

                            <?php
                            // Obtener categorías principales específicas con sus subcategorías
                            // Obtener categorías principales específicas con sus subcategorías
                            $mainCategories = ['Sillería', 'Almacenaje', 'Línea Italia', 'Escritorios', 'Metálico', 'Líneas'];
                            $mainCatsData = [];

                            foreach ($mainCategories as $mainCatName) {
                                // Buscar por nombre exacto primero
                                $stmt = $conn->prepare("SELECT id, nombre FROM categoria WHERE nombre = ? AND parent_id IS NULL");
                                $stmt->bind_param("s", $mainCatName);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $row = $result->fetch_assoc();
                                $stmt->close();

                                if ($row) {
                                    $mainCatsData[] = $row;
                                }
                            }

                            // Si no se encontraron las categorías, crear datos de respaldo
                            if (empty($mainCatsData)) {
                                $mainCatsData = [
                                    ['id' => 1, 'nombre' => 'Sillería'],
                                    ['id' => 9, 'nombre' => 'Almacenaje'],
                                    ['id' => 13, 'nombre' => 'Línea Italia'],
                                    ['id' => 19, 'nombre' => 'Escritorios'],
                                    ['id' => 28, 'nombre' => 'Metálico'],
                                    ['id' => 39, 'nombre' => 'Líneas']
                                ];
                            }

                            foreach ($mainCatsData as $mainCat):
                                // Obtener subcategorías
                                $stmt = $conn->prepare("SELECT id, nombre FROM categoria WHERE parent_id = ? ORDER BY nombre");
                                $stmt->bind_param("i", $mainCat['id']);
                                $stmt->execute();
                                $subCats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                $stmt->close();

                                // Si no hay subcategorías en la BD, usar las definidas en SQL_PRO.sql
                                if (empty($subCats)) {
                                    switch ($mainCat['id']) {
                                        case 1: // Sillería
                                            $subCats = [
                                                ['id' => 2, 'nombre' => 'Visita'],
                                                ['id' => 3, 'nombre' => 'Operativa'],
                                                ['id' => 4, 'nombre' => 'Ejecutiva'],
                                                ['id' => 5, 'nombre' => 'Sofás'],
                                                ['id' => 6, 'nombre' => 'Visitantes'],
                                                ['id' => 7, 'nombre' => 'Bancas de espera'],
                                                ['id' => 8, 'nombre' => 'Escolar']
                                            ];
                                            break;
                                        case 8: // Almacenaje
                                            $subCats = [
                                                ['id' => 9, 'nombre' => 'Archiveros'],
                                                ['id' => 10, 'nombre' => 'Gabinetes'],
                                                ['id' => 11, 'nombre' => 'Credenzas']
                                            ];
                                            break;
                                        case 13: // Línea Italia
                                            $subCats = [
                                                ['id' => 14, 'nombre' => 'Anzio'],
                                                ['id' => 15, 'nombre' => 'iwork & privatt'],
                                                ['id' => 16, 'nombre' => 'Italia Solución general']
                                            ];
                                            break;
                                        case 19: // Escritorios
                                            $subCats = [
                                                ['id' => 23, 'nombre' => 'Básicos'],
                                                ['id' => 24, 'nombre' => 'Operativos en L'],
                                                ['id' => 25, 'nombre' => 'Semi-Ejecutivo'],
                                                ['id' => 26, 'nombre' => 'Ejecutivos']
                                            ];
                                            break;
                                        case 28: // Metálico
                                            $subCats = [
                                                ['id' => 29, 'nombre' => 'Archiveros'],
                                                ['id' => 30, 'nombre' => 'Anaqueles'],
                                                ['id' => 31, 'nombre' => 'Escritorios'],
                                                ['id' => 32, 'nombre' => 'Gabinetes'],
                                                ['id' => 33, 'nombre' => 'Góndolas'],
                                                ['id' => 34, 'nombre' => 'Lockers'],
                                                ['id' => 35, 'nombre' => 'Restauranteras'],
                                                ['id' => 36, 'nombre' => 'Mesas'],
                                                ['id' => 37, 'nombre' => 'Escolar'],
                                                ['id' => 38, 'nombre' => 'Línea Económica']
                                            ];
                                            break;
                                        case 39: // Líneas
                                            $subCats = [
                                                ['id' => 40, 'nombre' => 'Euro'],
                                                ['id' => 41, 'nombre' => 'Delta'],
                                                ['id' => 42, 'nombre' => 'Tempo'],
                                                ['id' => 43, 'nombre' => 'Línea Alva'],
                                                ['id' => 44, 'nombre' => 'Línea Beta'],
                                                ['id' => 45, 'nombre' => 'Línea Ceres'],
                                                ['id' => 46, 'nombre' => 'Línea Fiore'],
                                                ['id' => 47, 'nombre' => 'Línea Worvik'],
                                                ['id' => 48, 'nombre' => 'Línea Yenko']
                                            ];
                                            break;
                                    }
                                }

                                // Solo mostrar el grupo si tiene subcategorías
                                if (!empty($subCats)):
                                    ?>
                                    <div class="navbar-category-group">
                                        <div class="navbar-category-main">
                                            <?= htmlspecialchars($mainCat['nombre']) ?>
                                            <svg class="icon submenu-icon" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                        <div class="navbar-subcategory-menu">
                                            <?php foreach ($subCats as $subCat):
                                                // Contar productos por subcategoría
                                                $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM producto WHERE categoria_id = ?");
                                                $countStmt->bind_param("i", $subCat['id']);
                                                $countStmt->execute();
                                                $catCount = $countStmt->get_result()->fetch_assoc()['cnt'] ?? 0;
                                                $countStmt->close();
                                                ?>
                                                <a href="catalogo.php?categoria=<?= (int) $subCat['id'] ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>"
                                                    class="navbar-subcategory-item <?= $categoria_id === (int) $subCat['id'] ? 'active' : '' ?>">
                                                    <?= htmlspecialchars($subCat['nombre']) ?>
                                                    <span class="navbar-category-count"><?= $catCount ?></span>
                                                </a>
                                            <?php endforeach ?>
                                        </div>
                                    </div>
                                    <?php
                                endif; // Cerrar el if de subcategorías
                            endforeach ?>

                            <!-- Otras categorías (sin subcategorías) -->
                            <?php
                            // Obtener categorías que no son principales ni subcategorías
                            $otherCategories = ['Libreros', 'Mesas', 'Mesas de Juntas', 'Islas de Trabajo', 'Recepción'];

                            foreach ($otherCategories as $otherCatName):
                                $stmt = $conn->prepare("SELECT id FROM categoria WHERE nombre = ? AND parent_id IS NULL");
                                $stmt->bind_param("s", $otherCatName);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($row = $result->fetch_assoc()):
                                    $otherCatId = $row['id'];
                                    $stmt->close();

                                    // Contar productos por categoría
                                    $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM producto WHERE categoria_id = ?");
                                    $countStmt->bind_param("i", $otherCatId);
                                    $countStmt->execute();
                                    $catCount = $countStmt->get_result()->fetch_assoc()['cnt'] ?? 0;
                                    $countStmt->close();

                                    if ($catCount > 0): // Solo mostrar si tiene productos
                                        ?>
                                        <a href="catalogo.php?categoria=<?= (int) $otherCatId ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>"
                                            class="navbar-category-item <?= $categoria_id === (int) $otherCatId ? 'active' : '' ?>">
                                            <?= htmlspecialchars($otherCatName) ?>
                                            <span class="navbar-category-count"><?= $catCount ?></span>
                                        </a>
                                        <?php
                                    endif;
                                endif;
                            endforeach ?>
                        </div>
                    </div>
                    <a href="catalogo.php" class="nav-link">Catálogo</a>
                    <a href="#contacto" class="nav-link">Contacto</a>
                </nav>
                <!-- Agregando botones de acción en el header -->
                <div class="header-actions">
                    <a href="tel:8331881814" class="btn btn-secondary btn-small">Llamar</a>
                    <a href="https://wa.me/528331881814" class="btn btn-secondary btn-small">WhatsApp</a>
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <?php
                        $hdrName = trim((string) ($_SESSION['user_nombre'] ?? ''));
                        $hdrLabel = $hdrName !== '' ? $hdrName : (string) ($_SESSION['user_email'] ?? 'Cuenta');
                        ?>
                        <span class="btn btn-secondary btn-small" style="cursor:default;pointer-events:none;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars((string) ($_SESSION['user_email'] ?? ''), ENT_QUOTES) ?>"><?= htmlspecialchars($hdrLabel, ENT_QUOTES) ?></span>
                        <a href="logout.php" class="btn btn-secondary btn-small">Salir</a>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-small" onclick="openAuthModal()">Iniciar Sesión</button>
                    <?php endif; ?>
                    <button class="btn btn-primary btn-small" onclick="openQuoteModal()">Cotizar</button>
                    <?php $cartCount = array_sum(array_column($_SESSION['cart'] ?? [], 'cantidad')); ?>
                    <button onclick="openCartDrawer()" class="btn btn-secondary btn-small" style="display:inline-flex;align-items:center;gap:6px;position:relative;cursor:pointer;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM5.82 5H21l-1.68 8.39c-.16.79-.84 1.36-1.64 1.36H8.08c-.8 0-1.49-.57-1.64-1.36L5 5H3V3H5.82z"/></svg>
                        Carrito
                        <span class="cart-badge-count" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>background:#ef4444;color:white;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;padding:0 3px;"><?= $cartCount ?></span>
                    </button>
                </div>
                <button class="menu-toggle" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>


    <!-- Hero Section -->
    <section class="hero" id="inicio">
        <div class="hero-content">
            <p class="hero-eyebrow fade-in">Mobiliario Corporativo Premium</p>
            <h2 class="hero-title fade-in">Espacios de Trabajo que Inspiran Excelencia</h2>
            <p class="hero-subtitle fade-in-delay">Soluciones integrales de mobiliario diseñadas para potenciar la
                productividad y el bienestar en entornos corporativos modernos.</p>
            <div class="hero-cta fade-in-delay-2">
                <a href="catalogo.php" class="btn btn-primary">Explorar Catálogo</a>
                <button class="btn btn-secondary" onclick="openQuoteModal()">Solicitar Cotización</button>
            </div>
        </div>
        <div class="hero-image">
            <div class="carousel-container">
                <div class="carousel-slides">
                    <div class="carousel-slide active">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/Enscape_2022-10-31-18-19-56.png')) ?>"
                            alt="Ofiequipo de Tampico - Mobiliario de Oficina">
                    </div>

                    <div class="carousel-slide">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/OFICINA RENDER 2.png')) ?>"
                            alt="Render Enscape">
                    </div>

                    <div class="carousel-slide">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/RENDER HO.png')) ?>"
                            alt="Render Enscape">
                    </div>

                    <div class="carousel-slide">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/RENDER MESA DE JUNTAS 2.png')) ?>"
                            alt="Render Enscape">
                    </div>

                    <div class="carousel-slide">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/Enscape_2022-10-31-18-19-56.png')) ?>"
                            alt="Render Enscape">
                    </div>

                    <div class="carousel-slide">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/OFICINA RENDER 2.png')) ?>"
                            alt="Render Enscape">
                    </div>

                    <div class="carousel-slide">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/RENDER HO.png')) ?>"
                            alt="Render Enscape">
                    </div>

                    <div class="carousel-slide">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/RENDER MESA DE JUNTAS 2.png')) ?>"
                            alt="Render Enscape">
                    </div>

                    <div class="carousel-slide">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/Enscape_2022-10-31-18-19-56.png')) ?>"
                            alt="Render Enscape">
                    </div>

                    <div class="carousel-slide">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/RENDER HO.png')) ?>"
                            alt="Render Enscape">
                    </div>

                    <div class="carousel-slide">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/RENDER MESA DE JUNTAS 2.png')) ?>"
                            alt="Render Enscape">
                    </div>

                    <div class="carousel-slide">
                        <img src="<?= htmlspecialchars(getImageUrl('Uploads/Slider/OFICINA RENDER 2.png')) ?>"
                            alt="Render Enscape">
                    </div>
                </div>
                <div class="carousel-indicators">
                    <span class="indicator active" data-slide="0"></span>
                    <span class="indicator" data-slide="1"></span>
                    <span class="indicator" data-slide="2"></span>
                    <span class="indicator" data-slide="3"></span>
                    <span class="indicator" data-slide="4"></span>
                    <span class="indicator" data-slide="5"></span>
                    <span class="indicator" data-slide="6"></span>
                    <span class="indicator" data-slide="7"></span>
                    <span class="indicator" data-slide="8"></span>
                    <span class="indicator" data-slide="9"></span>
                    <span class="indicator" data-slide="10"></span>
                    <span class="indicator" data-slide="11"></span>
                </div>
                <div class="carousel-nav">
                    <button class="carousel-prev">‹</button>
                    <button class="carousel-next">›</button>
                </div>
            </div>
        </div>
    </section>


    <!-- Categories Section -->
    <section class="categories" id="productos">
        <div class="container">
            <h2 class="section-title">Nuestras Categorías</h2>
            <p class="section-subtitle">Soluciones especializadas para cada área de su organización</p>

            <div class="collections-grid"
                style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;margin:24px 0;">
                <?php foreach ($cats as $c):
                    $products = $catProducts[$c['id']] ?? [];
                    $hero = $products[0] ?? null;
                    $imgField = $hero['imagen'] ?? '';

                    // Buscar productos específicos según la categoría
                    // Metálico: usar siempre la imagen del gabinete GC80
                    if ($c['nombre'] === 'Metálico') {
                        // Usar imagen específica del gabinete GC80
                        $imgField = 'Uploads/METALICO2022/GABINETES/GC80.jpg';
                    } elseif ($c['nombre'] === 'Libreros') {
                        // Buscar producto LA16 para Libreros
                        $specificStmt = $conn->prepare("SELECT imagen FROM producto WHERE (nombre LIKE '%LA16%' OR nombre LIKE '%LA 16%') AND imagen IS NOT NULL AND imagen != '' LIMIT 1");
                        $specificStmt->execute();
                        $specificResult = $specificStmt->get_result();
                        if ($specificRow = $specificResult->fetch_assoc()) {
                            $imgField = $specificRow['imagen'];
                        }
                        $specificStmt->close();
                    } elseif ($c['nombre'] === 'Escritorios') {
                        // Buscar productos de Escritorios y sus subcategorías con imagen
                        $subCatStmt = $conn->prepare("SELECT id FROM categoria WHERE parent_id = ?");
                        $subCatStmt->bind_param("i", $c['id']);
                        $subCatStmt->execute();
                        $subCatResult = $subCatStmt->get_result();
                        $subCatIds = [$c['id']]; // Incluir la categoría principal también
                        while ($subCat = $subCatResult->fetch_assoc()) {
                            $subCatIds[] = $subCat['id'];
                        }
                        $subCatStmt->close();

                        if (!empty($subCatIds)) {
                            $placeholders = str_repeat('?,', count($subCatIds) - 1) . '?';
                            $escritorioStmt = $conn->prepare("SELECT imagen FROM producto WHERE categoria_id IN ($placeholders) AND imagen IS NOT NULL AND imagen != '' ORDER BY id DESC LIMIT 1");
                            $escritorioStmt->bind_param(str_repeat('i', count($subCatIds)), ...$subCatIds);
                            $escritorioStmt->execute();
                            $escritorioResult = $escritorioStmt->get_result();
                            if ($escritorioRow = $escritorioResult->fetch_assoc()) {
                                $imgField = $escritorioRow['imagen'];
                            }
                            $escritorioStmt->close();
                        }
                    } elseif ($c['nombre'] === 'Mesas') {
                        // Buscar producto MMT
                        $specificStmt = $conn->prepare("SELECT imagen FROM producto WHERE nombre LIKE '%MMT%' AND imagen IS NOT NULL AND imagen != '' LIMIT 1");
                        $specificStmt->execute();
                        $specificResult = $specificStmt->get_result();
                        if ($specificRow = $specificResult->fetch_assoc()) {
                            $imgField = $specificRow['imagen'];
                        }
                        $specificStmt->close();
                    } elseif ($c['nombre'] === 'Sillería') {
                        // Buscar producto OHV-115
                        $specificStmt = $conn->prepare("SELECT imagen FROM producto WHERE nombre LIKE '%OHV-115%' AND imagen IS NOT NULL AND imagen != '' LIMIT 1");
                        $specificStmt->execute();
                        $specificResult = $specificStmt->get_result();
                        if ($specificRow = $specificResult->fetch_assoc()) {
                            $imgField = $specificRow['imagen'];
                        }
                        $specificStmt->close();

                        // Si no se encontró OHV-115, buscar imagen de sillas (no sofás) como fallback
                        if (empty($imgField)) {
                            // Buscar en subcategorías que contengan "silla" o "operativa" (evitar sofás)
                            $sillaStmt = $conn->prepare("SELECT id FROM categoria WHERE parent_id = ? AND (nombre LIKE '%Operativa%' OR nombre LIKE '%Visita%' OR nombre LIKE '%Escolar%') ORDER BY nombre");
                            $sillaStmt->bind_param("i", $c['id']);
                            $sillaStmt->execute();
                            $sillaResult = $sillaStmt->get_result();

                            while ($sillaRow = $sillaResult->fetch_assoc()) {
                                // Buscar producto de sillas con mejor imagen
                                $sillaProductStmt = $conn->prepare("SELECT imagen, nombre FROM producto WHERE categoria_id = ? AND imagen IS NOT NULL AND imagen != '' AND nombre NOT LIKE '%sofá%' AND nombre NOT LIKE '%sofa%' ORDER BY id DESC LIMIT 5");
                                $sillaProductStmt->bind_param("i", $sillaRow['id']);
                                $sillaProductStmt->execute();
                                $sillaProductResult = $sillaProductStmt->get_result();

                                // Buscar la mejor imagen de silla (no sofá)
                                while ($sillaProductRow = $sillaProductResult->fetch_assoc()) {
                                    $testImg = $sillaProductRow['imagen'];
                                    $productName = strtolower($sillaProductRow['nombre']);

                                    // Evitar sofás y buscar sillas específicamente
                                    if (
                                        !empty($testImg) && $testImg !== 'uploads/' &&
                                        !strpos($productName, 'sofá') && !strpos($productName, 'sofa') &&
                                        !strpos($productName, 'diván') && !strpos($productName, 'divan')
                                    ) {

                                        if (!filter_var($testImg, FILTER_VALIDATE_URL)) {
                                            // Si la imagen ya empieza con Uploads/ o uploads/, usarla tal cual
                                            $testImgTrimmed = ltrim($testImg, '/');
                                            if (stripos($testImgTrimmed, 'uploads/') === 0 || stripos($testImgTrimmed, 'Uploads/') === 0) {
                                                $testImg = $testImgTrimmed;
                                            } else {
                                                $testImg = 'uploads/' . $testImgTrimmed;
                                            }
                                        }

                                        $imgField = $sillaProductRow['imagen'];
                                        break 2; // Salir de ambos bucles
                                    }
                                }
                                $sillaProductStmt->close();
                            }
                            $sillaStmt->close();
                        }
                    }

                    // Usar la función helper para normalizar la URL de la imagen
                    $img = getImageUrl($imgField);

                    // Usar categoria_parent para mostrar todos los productos de las subcategorías
                    $linkUrl = 'catalogo.php?categoria_parent=' . (int) $c['id'];

                    // En index, todas las categorías llevan al catálogo; las subcategorías se verán allá
                    $isExpandable = false;
                    ?>
                    <article class="category-card"
                        style="background:#fff;border:1px solid #eef0f3;border-radius:10px;overflow:hidden;">
                        <a href="<?= htmlspecialchars($linkUrl) ?>" data-category-id="<?= (int) $c['id'] ?>"
                            data-category-name="<?= htmlspecialchars($c['nombre']) ?>"
                            style="display:block;text-decoration:none;color:inherit;">
                            <div class="category-image" style="height:220px;overflow:hidden;">
                                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($c['nombre']) ?>"
                                    style="width:100%;height:100%;object-fit:cover;object-position:center;">
                            </div>
                            <div class="category-content" style="padding:18px;">
                                <h3><?= htmlspecialchars($c['nombre']) ?></h3>
                                <div style="margin-top:12px;color:#0b5ed7;font-weight:600;">Explorar colección →</div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Why Us Section -->
    <section class="why-us">
        <div class="container">
            <h2 class="section-title">¿POR QUÉ NOSOTROS?</h2>
            <p class="section-subtitle">Comprometidos con la excelencia en cada proyecto</p>

            <div class="why-us-grid">
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <span style="font-size: 3rem;">🔧</span>
                    </div>
                    <h3>Armado Gratis</h3>
                    <p>Te facilitamos el proceso de armado para que disfrutes de tus productos sin el riesgo de
                        dañarlos.</p>
                </div>

                <div class="why-us-card">
                    <div class="why-us-icon">
                        <span style="font-size: 3rem;">🚚</span>
                    </div>
                    <h3>Entrega a Domicilio</h3>
                    <p>Para nuestros clientes de la zona metropolitana al sur de Tamaulipas ofrecemos envío gratuito.
                        Cotiza para envíos dentro de la república mexicana.</p>
                </div>

                <div class="why-us-card">
                    <div class="why-us-icon">
                        <span style="font-size: 3rem;">✅</span>
                    </div>
                    <h3>Garantía Segura</h3>
                    <p>Te damos un año para asegurar tu comodidad, si tu equipo presenta fallas puedes acercarte a
                        nuestra tienda autorizada.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Google Reviews Section -->
    <section class="google-reviews">
        <div class="container">
            <div class="google-reviews-header">
                <h2 class="google-rating-title">Reseñas</h2>
                <div class="google-rating-info">
                    <div class="google-stars">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star half">★</span>
                    </div>
                    <p class="google-review-count">A base de 18 reseñas</p>
                    <div class="google-logo">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path fill="#4285F4"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05"
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#EA4335"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="reviews-carousel-container">
                <div class="reviews-carousel">
                    <div class="reviews-track">
                        <div class="review-card">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <span>R</span>
                                </div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name-row">
                                        <h4>Roberto Zepeda Soto</h4>
                                        <div class="google-icon">
                                            <svg viewBox="0 0 24 24" width="16" height="16">
                                                <path fill="#4285F4"
                                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                                <path fill="#34A853"
                                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                                <path fill="#FBBC05"
                                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                                <path fill="#EA4335"
                                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="review-date">2021-10-15</p>
                                </div>
                            </div>
                            <div class="review-rating">
                                <div class="review-stars">
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                </div>
                                <div class="verified-badge">
                                    <svg viewBox="0 0 24 24" width="16" height="16">
                                        <path fill="#4285F4"
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="review-content">
                                <p>"Buena experiencia encontré lo que buscaba, felicidades por su excelente variedad.
                                    Muy buena atención."</p>
                            </div>
                        </div>

                        <div class="review-card">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <span>E</span>
                                </div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name-row">
                                        <h4>Endy Efren Orozco Cruz</h4>
                                        <div class="google-icon">
                                            <svg viewBox="0 0 24 24" width="16" height="16">
                                                <path fill="#4285F4"
                                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                                <path fill="#34A853"
                                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                                <path fill="#FBBC05"
                                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                                <path fill="#EA4335"
                                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="review-date">2021-09-28</p>
                                </div>
                            </div>
                            <div class="review-rating">
                                <div class="review-stars">
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                </div>
                                <div class="verified-badge">
                                    <svg viewBox="0 0 24 24" width="16" height="16">
                                        <path fill="#4285F4"
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="review-content">
                                <p>"Excelente variedad de mobiliario para oficina y excelente atención de los
                                    vendedores."</p>
                            </div>
                        </div>

                        <div class="review-card">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <span>C</span>
                                </div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name-row">
                                        <h4>Conchita Bautista</h4>
                                        <div class="google-icon">
                                            <svg viewBox="0 0 24 24" width="16" height="16">
                                                <path fill="#4285F4"
                                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                                <path fill="#34A853"
                                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                                <path fill="#FBBC05"
                                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                                <path fill="#EA4335"
                                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="review-date">2021-08-12</p>
                                </div>
                            </div>
                            <div class="review-rating">
                                <div class="review-stars">
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                </div>
                                <div class="verified-badge">
                                    <svg viewBox="0 0 24 24" width="16" height="16">
                                        <path fill="#4285F4"
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="review-content">
                                <p>"Mucha variedad de sillas y excelentes precios"</p>
                            </div>
                        </div>

                        <div class="review-card">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <span>M</span>
                                </div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name-row">
                                        <h4>María González</h4>
                                        <div class="google-icon">
                                            <svg viewBox="0 0 24 24" width="16" height="16">
                                                <path fill="#4285F4"
                                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                                <path fill="#34A853"
                                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                                <path fill="#FBBC05"
                                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                                <path fill="#EA4335"
                                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="review-date">2021-07-25</p>
                                </div>
                            </div>
                            <div class="review-rating">
                                <div class="review-stars">
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                </div>
                                <div class="verified-badge">
                                    <svg viewBox="0 0 24 24" width="16" height="16">
                                        <path fill="#4285F4"
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="review-content">
                                <p>"El servicio de entrega a domicilio es excelente. Llegaron exactamente a la hora
                                    acordada y el armado fue impecable."</p>
                            </div>
                        </div>

                        <div class="review-card">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <span>J</span>
                                </div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name-row">
                                        <h4>José Luis Martínez</h4>
                                        <div class="google-icon">
                                            <svg viewBox="0 0 24 24" width="16" height="16">
                                                <path fill="#4285F4"
                                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                                <path fill="#34A853"
                                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                                <path fill="#FBBC05"
                                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                                <path fill="#EA4335"
                                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="review-date">2021-06-18</p>
                                </div>
                            </div>
                            <div class="review-rating">
                                <div class="review-stars">
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                </div>
                                <div class="verified-badge">
                                    <svg viewBox="0 0 24 24" width="16" height="16">
                                        <path fill="#4285F4"
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="review-content">
                                <p>"Muy buena atención al cliente y productos de calidad. La garantía que ofrecen me da
                                    mucha confianza."</p>
                            </div>
                        </div>

                        <div class="review-card">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <span>A</span>
                                </div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name-row">
                                        <h4>Ana Patricia López</h4>
                                        <div class="google-icon">
                                            <svg viewBox="0 0 24 24" width="16" height="16">
                                                <path fill="#4285F4"
                                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                                <path fill="#34A853"
                                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                                <path fill="#FBBC05"
                                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                                <path fill="#EA4335"
                                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="review-date">2021-05-30</p>
                                </div>
                            </div>
                            <div class="review-rating">
                                <div class="review-stars">
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                </div>
                                <div class="verified-badge">
                                    <svg viewBox="0 0 24 24" width="16" height="16">
                                        <path fill="#4285F4"
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="review-content">
                                <p>"El servicio de entrega a domicilio es excelente. Llegaron exactamente a la hora
                                    acordada y el armado fue impecable."</p>
                            </div>
                        </div>

                        <div class="review-card">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <span>L</span>
                                </div>
                                <div class="reviewer-details">
                                    <div class="reviewer-name-row">
                                        <h4>Luis Carlos Ramírez</h4>
                                        <div class="google-icon">
                                            <svg viewBox="0 0 24 24" width="16" height="16">
                                                <path fill="#4285F4"
                                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                                <path fill="#34A853"
                                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                                <path fill="#FBBC05"
                                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                                <path fill="#EA4335"
                                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="review-date">2021-04-22</p>
                                </div>
                            </div>
                            <div class="review-rating">
                                <div class="review-stars">
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                    <span class="star">★</span>
                                </div>
                                <div class="verified-badge">
                                    <svg viewBox="0 0 24 24" width="16" height="16">
                                        <path fill="#4285F4"
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="review-content">
                                <p>"Increíble calidad en sus productos. El equipo de armado fue muy profesional y el
                                    resultado superó mis expectativas."</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reviews-nav">
                    <button class="reviews-next" aria-label="Siguiente reseña">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path fill="currentColor" d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </section>


    <!-- Benefits Section -->
    <section class="benefits">
        <div class="container">
            <h2 class="section-title">Nuestra Experiencia</h2>
            <p class="section-subtitle">Comprometidos con la excelencia en cada proyecto</p>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <span style="font-size: 3rem;">🏢</span>
                    </div>
                    <h3>PROYECTOS EJECUTIVOS</h3>
                    <p>Con más de 200 proyectos para empresas y oficinas terminados al año aseguramos la confiabilidad
                        de nuestros productos.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <span style="font-size: 3rem;">⭐</span>
                    </div>
                    <h3>45 AÑOS DE EXPERIENCIA</h3>
                    <p>Nuestra vasta experiencia nos permite entender tus necesidades para ofrecerte los mejores
                        productos y servicio.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <span style="font-size: 3rem;">👥</span>
                    </div>
                    <h3>+ 150K CLIENTES SATISFECHOS</h3>
                    <p>Nuestros más de 2,000 Clientes satisfechos anualmente nos respaldan.</p>
                </div>
            </div>
        </div>
    </section>


    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Transforme su Espacio de Trabajo</h2>
                <p>Nuestro equipo de especialistas está listo para desarrollar una solución personalizada que se adapte
                    a las necesidades específicas de su organización.</p>
                <div class="cta-buttons">
                    <a href="tel:8331881814" class="btn btn-primary">Contactar Ahora</a>
                    <a href="https://wa.me/528331881814" class="btn btn-secondary">WhatsApp Empresarial</a>
                </div>
            </div>
        </div>
    </section>


    <!-- Modal de detalles del producto -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="productModalTitle">Detalles del Producto</h2>
                <button class="modal-close" onclick="closeProductModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="product-detail-content">
                    <div class="product-detail-image">
                        <img id="productModalImage" src="https://via.placeholder.com/400x300?text=Sin+imagen" alt=""
                            style="width: 100%; height: 300px; object-fit: contain; border-radius: 8px;">
                    </div>
                    <div class="product-detail-info">
                        <h3 id="productModalName"></h3>
                        <p id="productModalDescription" class="product-detail-description"></p>
                        <div class="product-detail-actions">
                            <button class="btn btn-primary" onclick="openQuoteModalFromProduct()">Solicitar
                                Cotización</button>
                            <button class="btn btn-secondary" onclick="closeProductModal()">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Bienvenida / Auth -->
    <div class="modal" id="authModal">
        <div class="modal-content auth-modal-content">

            <!-- Pantalla de bienvenida -->
            <div id="authWelcome" class="auth-screen">
                <button class="modal-close" onclick="closeAuthModal()">&times;</button>
                <div class="auth-brand">
                    <div class="auth-brand-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/>
                        </svg>
                    </div>
                    <h2>OFIEQUIPO<span>DE TAMPICO</span></h2>
                </div>
                <div class="auth-divider"></div>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <?php
                    $amName = trim((string) ($_SESSION['user_nombre'] ?? ''));
                    $amHi = $amName !== '' ? $amName : (string) ($_SESSION['user_email'] ?? 'Cuenta');
                    ?>
                    <h3 class="auth-question">Hola, <?= htmlspecialchars($amHi, ENT_QUOTES) ?></h3>
                    <p class="auth-subtitle">Tu sesión en la tienda está activa.</p>
                    <div class="auth-welcome-actions">
                        <button type="button" class="btn btn-primary" onclick="window.location.href='catalogo.php'">Ver catálogo</button>
                        <a href="logout.php" class="btn btn-secondary" style="text-align:center;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Cerrar sesión</a>
                    </div>
                <?php else: ?>
                    <h3 class="auth-question">¿Ya tienes cuenta con nosotros?</h3>
                    <p class="auth-subtitle">Inicia sesión para gestionar tus cotizaciones o crea una cuenta nueva.</p>
                    <div class="auth-welcome-actions">
                        <button type="button" class="btn btn-primary" onclick="window.location.href='login.php'">Iniciar Sesión</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='register.php'">Crear Cuenta</button>
                    </div>
                <?php endif; ?>
                <p class="auth-skip"><a href="#" onclick="closeAuthModal(); return false;">Continuar sin cuenta</a></p>
            </div>

        </div>
    </div>


    <!-- Agregando modal de cotización -->
    <div class="modal" id="quoteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Solicitar Cotización</h2>
                <button class="modal-close" onclick="closeQuoteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="quoteForm" action="https://formsubmit.co/soniaanaya@ofiequipo.com.mx" method="POST"
                    target="hidden_iframe">
                    <input type="hidden" name="_subject" value="Nueva Solicitud de Cotización - Ofiequipo">
                    <input type="hidden" name="_captcha" value="false">
                    <input type="hidden" name="_template" value="table">
                    <!-- opcional: redirigir al mismo sitio (no necesario con iframe) -->
                    <!-- <input type="hidden" name="_next" value="http://localhost/"> -->


                    <div class="form-group">
                        <label for="productSelect">Producto de Interés</label>
                        <select id="productSelect" name="producto" required>
                            <option value="">Seleccione un producto</option>
                            <?php if (empty($allProducts)): ?>
                                <option value="" disabled>Sin producto que mostrar</option>
                            <?php else: ?>
                                <?php foreach ($allProducts as $p): ?>
                                    <option value="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>">
                                        <?= htmlspecialchars($p['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="name">Nombre Completo</label>
                        <input type="text" id="name" name="nombre" required>
                    </div>


                    <div class="form-group">
                        <label for="company">Empresa</label>
                        <input type="text" id="company" name="empresa">
                    </div>


                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" id="email" name="email" required>
                    </div>


                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input type="tel" id="phone" name="telefono" required>
                    </div>


                    <div class="form-group">
                        <label for="quantity">Cantidad</label>
                        <input type="number" id="quantity" name="cantidad" min="1" value="1">
                    </div>


                    <div class="form-group">
                        <label for="message">Mensaje Adicional</label>
                        <textarea id="message" name="mensaje"
                            placeholder="Cuéntenos más sobre sus necesidades..."></textarea>
                    </div>


                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeQuoteModal()">Cancelar</button>
                        <button type="submit" id="cotizar" class="btn btn-primary">Enviar Cotización</button>
                    </div>
                </form>

                <!-- Iframe oculto para enviar el form sin navegar fuera -->
                <iframe name="hidden_iframe" id="hidden_iframe" style="display:none;" aria-hidden="true"></iframe>
            </div>
        </div>
    </div>


    <!-- Footer -->
    <footer class="footer" id="contacto">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>OFIEQUIPO<span>DE TAMPICO</span></h3>
                    <p>Soluciones integrales en mobiliario corporativo desde 1977. Comprometidos con la excelencia y la
                        satisfacción de nuestros clientes.</p>
                </div>
                <div class="footer-column">
                    <h4>Categorías</h4>
                    <ul>
                        <li><a href="#sillas">Sillas Ejecutivas</a></li>
                        <li><a href="#escritorios">Escritorios</a></li>
                        <li><a href="#mesas">Mesas de Juntas</a></li>
                        <li><a href="#archiveros">Sistemas de Archivo</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Información de Contacto</h4>
                    <ul>
                        <li>Av. Miguel Hidalgo & Encino 3306</li>
                        <li>Aguila, 89230 Tampico, Tamps.</li>
                        <li>Tel: +52 833-213-3837</li>
                        <li>Tel: +52 833-217-2047</li>
                        <li>info@ofiequipo.com.mx</li>
                        <li>Lun-Vie: 9:00-20:00 hrs</li>
                        <li>Sábados: 9:00-14:00 hrs</li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Redes Sociales</h4>
                    <div class="social-links">
                        <a href="https://www.facebook.com/p/Ofiequipo-De-Tampico-Sa-De-Cv-100063668015117/?locale=es_LA"
                            aria-label="Facebook" class="social-link facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                            Facebook
                        </a>
                        <a href="https://www.instagram.com/ofiequipodetampico/" aria-label="Instagram"
                            class="social-link instagram">
                            <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4z" />
                            </svg>
                            Instagram
                        </a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Ofiequipo de Tampico. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>


    <!-- Adding SweetAlert2 script -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.addEventListener('load', function () {
            // COMENTADO PARA USO FUTURO: Animación de murciélagos
            /*
            const batContainer = document.getElementById('batContainer');
           
            // Hide bats after animation completes (3s animation + 1.5s delay for last bat)
            setTimeout(function() {
                batContainer.classList.add('hidden');
            }, 5000);
            */
        });


        function openQuoteModal(productName = '') {
            const modal = document.getElementById('quoteModal');
            const productSelect = document.getElementById('productSelect');


            if (productName) {
                productSelect.value = productName;
            } else {
                // opcional: dejar en blanco para que el usuario seleccione
                productSelect.value = '';
            }


            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }


        function closeQuoteModal() {
            const modal = document.getElementById('quoteModal');
            modal.classList.add('closing');
            setTimeout(function () {
                modal.classList.remove('active', 'closing');
                document.body.style.overflow = 'auto';
            }, 260);
        }

        // Product Detail Modal Functions
        function openProductModal(name, description, image) {
            const modal = document.getElementById('productModal');
            const title = document.getElementById('productModalTitle');
            const nameEl = document.getElementById('productModalName');
            const descEl = document.getElementById('productModalDescription');
            const imgEl = document.getElementById('productModalImage');

            // Set content
            title.textContent = 'Detalles del Producto';
            nameEl.textContent = name;
            descEl.innerHTML = description.replace(/\n/g, '<br>');
            imgEl.src = image;
            imgEl.alt = name;

            // Show modal
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeProductModal() {
            const modal = document.getElementById('productModal');
            modal.classList.add('closing');
            setTimeout(function () {
                modal.classList.remove('active', 'closing');
                document.body.style.overflow = 'auto';
            }, 260);
        }

        function openQuoteModalFromProduct() {
            closeProductModal();
            // Get the product name from the modal and open quote modal
            const productName = document.getElementById('productModalName').textContent;
            openQuoteModal(productName);
        }


        // Cerrar modal al hacer click fuera del contenido
        document.getElementById('quoteModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeQuoteModal();
            }
        });

        // Cerrar modal de detalles al hacer click fuera del contenido
        document.getElementById('productModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeProductModal();
            }
        });

        // ===== Auth Modal =====
        function openAuthModal() {
            const modal = document.getElementById('authModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeAuthModal() {
            const modal = document.getElementById('authModal');
            modal.classList.add('closing');
            setTimeout(function () {
                modal.classList.remove('active', 'closing');
                document.body.style.overflow = '';
                sessionStorage.setItem('authModalSeen', '1');
            }, 260);
        }

        // Cerrar auth modal al hacer click fuera del contenido
        document.getElementById('authModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeAuthModal();
            }
        });

        // Mostrar auth modal al cargar la página (solo una vez por sesión)
        if (!sessionStorage.getItem('authModalSeen')) {
            setTimeout(openAuthModal, 800);
        }
        // ===== Fin Auth Modal =====


        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAuthModal();
                closeQuoteModal();
                closeProductModal();
            }
        });


        // --- AJAX submit para cotización ---
        (function () {
            const form = document.getElementById('quoteForm');
            const submitBtn = document.getElementById('cotizar');
            const iframe = document.getElementById('hidden_iframe');

            // Marcar que se envió para distinguir otros loads del iframe
            let submitting = false;

            form.addEventListener('submit', function (e) {
                // Si el action usa formsubmit.co usamos el iframe oculto
                if (form.action && form.action.includes('formsubmit.co')) {
                    // simple validación nativa
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        e.preventDefault();
                        return;
                    }
                    submitting = true;
                    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Enviando...'; }
                    // permitir que el navegador haga el POST hacia el iframe
                    return;
                }
                // si no es formsubmit, tu lógica AJAX previa puede ejecutarse
                e.preventDefault();
            });

            // Cuando el iframe carga, si estábamos enviando mostramos el mensaje y limpiamos
            iframe.addEventListener('load', function () {
                if (!submitting) return;
                submitting = false;

                // reactivar botón y texto
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Enviar Cotización';
                }

                // mostrar SweetAlert de éxito
                Swal.fire({
                    icon: 'success',
                    title: 'Cotización enviada',
                    text: 'Gracias. Hemos recibido su solicitud.',
                    confirmButtonColor: '#1e40af'
                }).then(() => {
                    // reset form y cerrar modal
                    form.reset();
                    const productSelect = document.getElementById('productSelect');
                    if (productSelect) productSelect.value = '';
                    closeQuoteModal();

                    // limpiar iframe content (evitar que contenga la página de formsubmit)
                    try {
                        iframe.contentWindow.document.open();
                        iframe.contentWindow.document.write('');
                        iframe.contentWindow.document.close();
                    } catch (err) {
                        // cross-origin: no se puede limpiar, no es crítico
                    }
                });
            });
        })();


        // Animaciones de entrada
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };


        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);


        document.querySelectorAll('.category-card, .product-card, .benefit-card, .why-us-card, .review-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });


        // Header scroll effect
        const header = document.querySelector('.header');
        let lastScroll = 0;

        if (header) {
            window.addEventListener('scroll', () => {
                const currentScroll = window.pageYOffset;

                if (currentScroll > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }


                lastScroll = currentScroll;
            });
        }


        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const targetId = this.getAttribute('href');

                // Skip if targetId is just "#" without an actual ID (category toggle)
                if (targetId === '#') {
                    e.preventDefault();
                    return;
                }

                const target = document.querySelector(targetId);

                if (target) {
                    e.preventDefault();

                    const header = document.querySelector('.header');
                    if (header) {
                        const headerHeight = header.offsetHeight;
                        const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;

                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });

                        // Close mobile menu after scrolling to section
                        const nav = document.querySelector('.nav');
                        const menuToggle = document.querySelector('.menu-toggle');
                        if (nav && menuToggle && window.innerWidth <= 1024) {
                            setTimeout(() => {
                                nav.classList.remove('active');
                                menuToggle.classList.remove('active');
                                document.body.style.overflow = 'auto';
                            }, 100);
                        }
                    }
                }
            });
        });


        // Mobile menu toggle functionality
        console.log('Initializing mobile menu...');
        const menuToggle = document.querySelector('.menu-toggle');
        const nav = document.querySelector('.nav');

        console.log('Menu toggle element:', menuToggle);
        console.log('Nav element:', nav);

        if (menuToggle && nav) {
            console.log('Adding event listeners...');
            menuToggle.addEventListener('click', function (e) {
                console.log('Menu toggle clicked!');
                e.preventDefault();
                e.stopPropagation();

                nav.classList.toggle('active');
                menuToggle.classList.toggle('active');

                console.log('Nav classes:', nav.className);
                console.log('Menu toggle classes:', menuToggle.className);

                // Prevent body scroll when menu is open
                if (nav.classList.contains('active')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = 'auto';
                }
            });

            // Close menu when clicking outside (but not on category dropdown)
            document.addEventListener('click', function (e) {
                // Don't close if clicking on the category dropdown or toggle
                if (e.target.closest('#navbarCategoryToggle') ||
                    e.target.closest('#navbarCategoryDropdown') ||
                    e.target.closest('.navbar-category-dropdown')) {
                    return;
                }

                if (!menuToggle.contains(e.target) && !nav.contains(e.target)) {
                    nav.classList.remove('active');
                    menuToggle.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            });

            // Close menu when clicking on direct nav links only (not category dropdown items)
            document.querySelectorAll('.nav > a.nav-link').forEach(link => {
                link.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    // Close for Catálogo link (others like #inicio, #contacto are handled by anchor scroll)
                    if (href && href.includes('catalogo.php') && window.innerWidth <= 1024) {
                        nav.classList.remove('active');
                        menuToggle.classList.remove('active');
                        document.body.style.overflow = 'auto';
                    }
                });
            });
        }

        // Function to close category dropdown
        function closeCategoryDropdown() {
            const navbarCategoryDropdown = document.getElementById('navbarCategoryDropdown');
            const navbarCategoryToggle = document.getElementById('navbarCategoryToggle');
            if (navbarCategoryDropdown && navbarCategoryToggle) {
                navbarCategoryDropdown.classList.remove('active');
                navbarCategoryToggle.classList.remove('active');
            }
        }

        // Navbar category dropdown functionality
        document.addEventListener('DOMContentLoaded', function () {
            const navbarCategoryToggle = document.getElementById('navbarCategoryToggle');
            const navbarCategoryDropdown = document.getElementById('navbarCategoryDropdown');

            if (navbarCategoryToggle && navbarCategoryDropdown) {
                // Toggle principal del dropdown
                navbarCategoryToggle.addEventListener('click', function (e) {
                    console.log('Category toggle clicked!');
                    e.preventDefault();
                    e.stopPropagation();
                    const isActive = navbarCategoryDropdown.classList.contains('active');

                    console.log('Dropdown is currently active:', isActive);

                    if (isActive) {
                        navbarCategoryDropdown.classList.remove('active');
                        navbarCategoryToggle.classList.remove('active');
                        console.log('Dropdown closed');
                    } else {
                        navbarCategoryDropdown.classList.add('active');
                        navbarCategoryToggle.classList.add('active');
                        console.log('Dropdown opened');
                    }

                    // Don't close mobile menu when toggling categories
                    console.log('Category dropdown toggled, mobile menu stays open');
                });

                // Cerrar dropdown al hacer clic fuera (pero no en el menú móvil)
                document.addEventListener('click', function (e) {
                    // Don't close if clicking on mobile menu elements
                    if (e.target.closest('.menu-toggle') || e.target.closest('.nav')) {
                        return;
                    }

                    if (!navbarCategoryToggle.contains(e.target) && !navbarCategoryDropdown.contains(e.target)) {
                        navbarCategoryDropdown.classList.remove('active');
                        navbarCategoryToggle.classList.remove('active');
                    }
                });

                // Cerrar dropdown al hacer clic en un item de categoría (pero mantener el menú móvil abierto)
                navbarCategoryDropdown.addEventListener('click', function (e) {
                    // Only close dropdown if clicking on actual category items, not the dropdown container
                    if (e.target.closest('.navbar-category-item') || e.target.closest('.navbar-subcategory-item')) {
                        navbarCategoryDropdown.classList.remove('active');
                        navbarCategoryToggle.classList.remove('active');
                        console.log('Category item clicked, dropdown closed, mobile menu stays open');

                        // El menú móvil se mantiene abierto para permitir más navegación
                    }
                });
            }

            // Funcionalidad de toggle para subcategorías
            const categoryGroups = document.querySelectorAll('.navbar-category-group');

            // Remove existing event listeners to prevent duplicates
            // Cloning and replacing nodes is a common way to achieve this
            categoryGroups.forEach(function (group) {
                const mainCategory = group.querySelector('.navbar-category-main');
                if (mainCategory) {
                    const newMainCategory = mainCategory.cloneNode(true);
                    mainCategory.parentNode.replaceChild(newMainCategory, mainCategory);
                }
            });

            // Re-query after cloning to get fresh elements
            const freshCategoryGroups = document.querySelectorAll('.navbar-category-group');

            freshCategoryGroups.forEach(function (group) {
                const mainCategory = group.querySelector('.navbar-category-main');
                const submenu = group.querySelector('.navbar-subcategory-menu');

                if (mainCategory && submenu) {
                    mainCategory.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const isActive = submenu.classList.contains('active');

                        // Cerrar todos los otros submenús
                        freshCategoryGroups.forEach(function (otherGroup) {
                            const otherSubmenu = otherGroup.querySelector('.navbar-subcategory-menu');
                            const otherMain = otherGroup.querySelector('.navbar-category-main');
                            if (otherSubmenu && otherMain && otherGroup !== group) {
                                otherSubmenu.classList.remove('active');
                                otherMain.classList.remove('active');
                            }
                        });

                        // Toggle del submenú actual
                        if (isActive) {
                            submenu.classList.remove('active');
                            mainCategory.classList.remove('active');
                        } else {
                            submenu.classList.add('active');
                            mainCategory.classList.add('active');
                        }
                    });
                }
            });
        });

        // Smooth horizontal scroll for desktop navigation
        if (window.innerWidth > 768) {
            const nav = document.querySelector('.nav');
            if (nav) {
                // Add smooth scrolling behavior
                nav.style.scrollBehavior = 'smooth';

                // Add keyboard navigation support
                nav.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                        e.preventDefault();
                        const scrollAmount = 100;
                        if (e.key === 'ArrowLeft') {
                            nav.scrollLeft -= scrollAmount;
                        } else {
                            nav.scrollLeft += scrollAmount;
                        }
                    }
                });
            }
        }

        // Carousel functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const indicators = document.querySelectorAll('.indicator');
        const totalSlides = slides.length;

        // Debug: Check if carousel elements exist
        console.log('Carousel slides found:', slides.length);
        console.log('Indicators found:', indicators.length);

        function showSlide(index) {
            // Remove active class from all slides and indicators
            slides.forEach(slide => slide.classList.remove('active'));
            indicators.forEach(indicator => indicator.classList.remove('active'));

            // Add active class to current slide and indicator (only if they exist)
            if (slides[index]) {
                slides[index].classList.add('active');
            }
            if (indicators[index]) {
                indicators[index].classList.add('active');
            }
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
            console.log('Current slide:', currentSlide + 1, 'of', totalSlides);
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            showSlide(currentSlide);
            console.log('Current slide:', currentSlide + 1, 'of', totalSlides);
        }

        // Initialize carousel
        let carouselInterval;

        if (totalSlides > 0) {
            showSlide(0);

            // Auto-adjust container height based on promo image
            const promoImg = document.querySelector('.carousel-slide:nth-child(1) img');
            if (promoImg) {
                promoImg.onload = function () {
                    const aspectRatio = this.naturalHeight / this.naturalWidth;
                    const container = document.querySelector('.carousel-container');
                    if (container) {
                        // Calculate height based on current width and image aspect ratio
                        const currentWidth = container.offsetWidth;
                        const idealHeight = currentWidth * aspectRatio;

                        // Only adjust if the difference is significant
                        if (Math.abs(idealHeight - container.offsetHeight) > 50) {
                            container.style.height = idealHeight + 'px';
                        }
                    }
                };

                // If image is already loaded
                if (promoImg.complete) {
                    promoImg.onload();
                }

                // Adjust on window resize
                window.addEventListener('resize', () => {
                    if (promoImg.complete) {
                        const aspectRatio = promoImg.naturalHeight / promoImg.naturalWidth;
                        const container = document.querySelector('.carousel-container');
                        if (container) {
                            const currentWidth = container.offsetWidth;
                            const idealHeight = currentWidth * aspectRatio;
                            container.style.height = idealHeight + 'px';
                        }
                    }
                });
            }

            // Auto-play carousel with continuous flow
            // All slides change every 3 seconds
            carouselInterval = setInterval(() => {
                nextSlide();
            }, 3000);

            // Pause on hover
            const carouselContainer = document.querySelector('.carousel-container');
            if (carouselContainer) {
                carouselContainer.addEventListener('mouseenter', () => {
                    clearInterval(carouselInterval);
                });

                carouselContainer.addEventListener('mouseleave', () => {
                    carouselInterval = setInterval(nextSlide, 3000);
                });
            }
        }

        // Navigation buttons
        const prevBtn = document.querySelector('.carousel-prev');
        const nextBtn = document.querySelector('.carousel-next');

        if (prevBtn) {
            prevBtn.addEventListener('click', prevSlide);
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', nextSlide);
        }

        // Indicator clicks
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
            });
        });

        // Touch/swipe support for mobile
        let startX = 0;
        let endX = 0;
        const carouselContainer = document.querySelector('.carousel-container');

        if (carouselContainer) {
            carouselContainer.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
            });

            carouselContainer.addEventListener('touchend', (e) => {
                endX = e.changedTouches[0].clientX;
                handleSwipe();
            });

            function handleSwipe() {
                const threshold = 50;
                const diff = startX - endX;

                if (Math.abs(diff) > threshold) {
                    if (diff > 0) {
                        nextSlide();
                    } else {
                        prevSlide();
                    }
                }
            }
        }

        // Close menu when clicking outside (duplicate handler - considering category dropdown)
        document.addEventListener('click', (e) => {
            // Don't close if clicking on the category dropdown or toggle
            if (e.target.closest('#navbarCategoryToggle') ||
                e.target.closest('#navbarCategoryDropdown') ||
                e.target.closest('.navbar-category-dropdown')) {
                return;
            }

            if (!nav.contains(e.target) && !menuToggle.contains(e.target)) {
                nav.classList.remove('active');
                menuToggle.classList.remove('active');
                // Restore body scroll if menu was closed
                if (!nav.classList.contains('active')) {
                    document.body.style.overflow = 'auto';
                }
            }
        });

        // Close menu when clicking on a link (excepto en categorías)
        document.querySelectorAll('.nav > a.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                // No cerrar el menú si es el toggle de categorías
                if (link.id === 'navbarCategoryToggle') {
                    return;
                }

                if (window.innerWidth <= 768) {
                    // Close mobile menu
                    nav.classList.remove('active');
                    menuToggle.classList.remove('active');
                    document.body.style.overflow = 'auto';

                    // For internal anchors, let the global anchor handler perform the smooth scroll.
                    // Avoid duplicating preventDefault/scroll logic here to prevent conflicts.
                    const href = link.getAttribute('href');
                    if (href && href.startsWith('#')) {
                        return;
                    }
                }
            });
        });


        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function () {
                this.style.transform = 'translateY(-2px)';
            });


            card.addEventListener('mouseleave', function () {
                this.style.transform = 'translateY(0)';
            });
        });


        document.querySelectorAll('.category-card').forEach(card => {
            card.addEventListener('mousemove', function (e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;


                const centerX = rect.width / 2;
                const centerY = rect.height / 2;


                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;


                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-4px)`;
            });


            card.addEventListener('mouseleave', function () {
                this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)';
            });
        });

        // Reviews Carousel Functionality
        let currentReview = 0;
        const reviewCards = document.querySelectorAll('.review-card');
        const reviewTrack = document.querySelector('.reviews-track');
        const reviewNext = document.querySelector('.reviews-next');
        const totalReviews = reviewCards.length;

        function updateReviewCarousel() {
            if (reviewTrack && totalReviews > 0 && reviewCards[currentReview]) {
                // Usar la posición real de la tarjeta actual (offsetLeft) para scroll preciso
                const currentCard = reviewCards[currentReview];
                const scrollPosition = currentCard.offsetLeft;

                reviewTrack.style.transform = `translateX(-${scrollPosition}px)`;
            }
        }

        function nextReview() {
            if (totalReviews > 0) {
                currentReview = (currentReview + 1) % totalReviews;
                updateReviewCarousel();
            }
        }

        // Recalcular en resize para manejar cambios de orientación o tamaño de ventana
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                updateReviewCarousel();
            }, 250);
        });

        // Recalcular cuando la página cargue completamente (imágenes, etc.)
        window.addEventListener('load', () => {
            setTimeout(() => {
                updateReviewCarousel();
            }, 100);
        });

        // Initialize reviews carousel
        if (totalReviews > 0) {
            // Esperar a que el DOM se renderice completamente antes de calcular posiciones
            setTimeout(() => {
                updateReviewCarousel();
            }, 100);

            // Auto-play reviews carousel
            let reviewsInterval = setInterval(nextReview, 5000);

            // Pause on hover
            const reviewsContainer = document.querySelector('.reviews-carousel-container');
            if (reviewsContainer) {
                reviewsContainer.addEventListener('mouseenter', () => {
                    clearInterval(reviewsInterval);
                });

                reviewsContainer.addEventListener('mouseleave', () => {
                    reviewsInterval = setInterval(nextReview, 5000);
                });
            }

            // Navigation button
            if (reviewNext) {
                reviewNext.addEventListener('click', nextReview);
            }

            // Touch/swipe support for mobile
            let startX = 0;
            let endX = 0;

            if (reviewsContainer) {
                reviewsContainer.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                });

                reviewsContainer.addEventListener('touchend', (e) => {
                    endX = e.changedTouches[0].clientX;
                    handleReviewSwipe();
                });

                function handleReviewSwipe() {
                    const threshold = 50;
                    const diff = startX - endX;

                    if (Math.abs(diff) > threshold) {
                        if (diff > 0) {
                            nextReview();
                        }
                    }
                }
            }
        }

    </script>

    <!-- colocar antes del cierre de body -->
    <a class="whatsapp-fab" href="https://wa.me/528331881814?text=Hola%20quiero%20una%20cotizaci%C3%B3n" target="_blank"
        rel="noopener noreferrer" aria-label="Contactar por WhatsApp">
        <!-- WhatsApp SVG icon -->
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path
                d="M20.52 3.48A11.88 11.88 0 0 0 12 .5C5.73.5.99 5.24.99 11.5c0 2.03.53 4.02 1.54 5.78L.5 23.5l6.45-1.68A11.92 11.92 0 0 0 12 23.5c6.27 0 11.01-4.74 11.01-11 0-2.95-1.15-5.73-3.49-7.02z"
                fill="#25D366" />
            <path
                d="M17.3 14.3c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.48-1.76-1.66-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2 0-.38-.02-.53-.02-.15-.67-1.62-.92-2.22-.24-.6-.48-.52-.67-.53-.17-.01-.37-.01-.57-.01-.2 0-.53.08-.81.38-.28.3-1.06 1.04-1.06 2.53 0 1.48 1.09 2.92 1.24 3.12.15.2 2.14 3.42 5.19 4.8 3.05 1.38 3.05.92 3.6.86.55-.07 1.76-.72 2.01-1.41.25-.69.25-1.28.18-1.41-.07-.14-.27-.2-.57-.35z"
                fill="#fff" />
        </svg>
    </a>

    <!-- ============================================
         EFECTO AÑO NUEVO - Fuegos Artificiales
         Cambiar el año y mensaje aquí para futuros años
         COMENTADO - Se usará hasta el siguiente año
         ============================================ -->
    <!-- 
    <div id="newyear-overlay">
        <div id="fireworks-container"></div>
        <div id="newyear-content">
            <div id="newyear-year">2026</div>
            <div id="newyear-text">HAPPY NEW YEAR</div>
        </div>
    </div>
    -->

    <!-- ESTILOS AÑO NUEVO COMENTADOS - Se usará hasta el siguiente año
    <style>
        /* Estilos para efecto año nuevo */
        #newyear-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: transparent;
            pointer-events: none;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
        }

        #newyear-overlay.active {
            opacity: 1;
            visibility: visible;
            animation: overlayFadeIn 0.5s ease-in forwards, overlayFadeOut 0.5s ease-out 9.5s forwards;
        }

        @keyframes overlayFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes overlayFadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        #fireworks-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        #newyear-content {
            position: relative;
            z-index: 1;
            text-align: center;
            animation: contentFadeIn 0.8s ease-out forwards;
        }

        @keyframes contentFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        #newyear-year {
            font-size: 3rem;
            font-weight: 300;
            color: transparent;
            -webkit-text-stroke: 2px rgb(255, 218, 7);
            text-stroke: 2px rgb(255, 218, 7);
            font-family: 'Arial', sans-serif;
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
            line-height: 1;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5), 0 0 40px rgba(255, 255, 255, 0.3);
            padding: 0 1rem;
        }

        #newyear-text {
            font-size: 2.5rem;
            font-weight: 300;
            color: rgb(255, 218, 7);
            font-family: 'Arial', sans-serif;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5), 0 0 20px rgba(255, 255, 255, 0.3), 2px 2px 4px rgba(0, 0, 0, 0.5);
            padding: 0 1rem;
        }

        .firework-burst {
            position: absolute;
            pointer-events: none;
        }

        .firework-line {
            position: absolute;
            background: currentColor;
            transform-origin: center;
        }

        .firework-dot {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        /* Tablets */
        @media (max-width: 1024px) {
            #newyear-year {
                font-size: 2.5rem;
                -webkit-text-stroke: 1.5px rgb(255, 218, 7);
                text-stroke: 1.5px rgb(255, 218, 7);
            }
            #newyear-text {
                font-size: 2rem;
                letter-spacing: 0.25em;
            }
            .firework-line {
                width: 1.5px;
            }
            .firework-dot {
                width: 6px;
                height: 6px;
            }
        }

        /* Tablets pequeñas y móviles grandes */
        @media (max-width: 768px) {
            #newyear-year {
                font-size: 2rem;
                -webkit-text-stroke: 1.5px rgb(255, 218, 7);
                text-stroke: 1.5px rgb(255, 218, 7);
                margin-bottom: 0.75rem;
            }
            #newyear-text {
                font-size: 1.2rem;
                letter-spacing: 0.2em;
            }
            .firework-line {
                width: 1.5px;
            }
            .firework-dot {
                width: 5px;
                height: 5px;
            }
        }

        /* Móviles */
        @media (max-width: 480px) {
            #newyear-year {
                font-size: 1.5rem;
                -webkit-text-stroke: 2px rgb(255, 218, 7);
                text-stroke: 2px rgb(255, 218, 7);
                margin-bottom: 0.5rem;
                letter-spacing: 0.05em;
            }
            #newyear-text {
                font-size: 1.5rem;
                letter-spacing: 0.15em;
            }
            #newyear-content {
                padding: 0 0.5rem;
            }
            .firework-line {
                width: 1px;
            }
            .firework-dot {
                width: 4px;
                height: 4px;
            }
        }

        /* Móviles muy pequeños */
        @media (max-width: 360px) {
            #newyear-year {
                font-size: 1.2rem;
                -webkit-text-stroke: 1px rgb(255, 218, 7);
                text-stroke: 1px rgb(255, 218, 7);
            }
            #newyear-text {
                font-size: 0.6rem;
                letter-spacing: 0.1em;
            }
        }
    </style>
    -->

    <!-- SCRIPT AÑO NUEVO COMENTADO - Se usará hasta el siguiente año
    <script>
        // ============================================
        // EFECTO AÑO NUEVO - Script de fuegos artificiales
        // Cambiar el año y mensaje aquí para futuros años
        // ============================================
        
        (function() {
            const container = document.getElementById('fireworks-container');
            const overlay = document.getElementById('newyear-overlay');
            
            // Colores dorados para los fuegos artificiales
            const goldColors = ['#ffd700', '#ffed4e', '#ffc125', '#daa520', '#b8860b', '#f4a460', '#ffeb3b', '#ffc107'];
            
            // Posiciones predefinidas para los fuegos artificiales (todos dorados)
            const fireworkPositions = [
                { x: 15, y: 10, color: '#ffd700', type: 'large' },      // Top left
                { x: 85, y: 12, color: '#ffed4e', type: 'large' },      // Top right
                { x: 20, y: 15, color: '#ffc125', type: 'large' },      // Top left
                { x: 12, y: 50, color: '#daa520', type: 'small' },      // Mid left
                { x: 25, y: 55, color: '#b8860b', type: 'medium' },     // Mid left
                { x: 75, y: 60, color: '#f4a460', type: 'medium' },      // Mid right
                { x: 70, y: 65, color: '#ffd700', type: 'small' },       // Mid right
                { x: 88, y: 15, color: '#ffed4e', type: 'medium' },      // Top right
                { x: 90, y: 85, color: '#ffc125', type: 'medium' },      // Bottom right
                { x: 5, y: 20, color: '#daa520', type: 'medium' },      // Left side
                { x: 95, y: 25, color: '#b8860b', type: 'large' },      // Right side
                { x: 10, y: 70, color: '#f4a460', type: 'small' },      // Bottom left
                { x: 50, y: 8, color: '#ffd700', type: 'large' },       // Top center
                { x: 30, y: 30, color: '#ffed4e', type: 'medium' },      // Center left
                { x: 65, y: 40, color: '#ffc125', type: 'small' },      // Center right
                { x: 8, y: 40, color: '#daa520', type: 'small' },       // Left side
                { x: 92, y: 50, color: '#b8860b', type: 'medium' },      // Right side
                { x: 45, y: 75, color: '#f4a460', type: 'large' },      // Bottom center
                { x: 55, y: 25, color: '#ffd700', type: 'medium' },      // Top center right
                { x: 35, y: 80, color: '#ffed4e', type: 'small' },      // Bottom left
                { x: 80, y: 75, color: '#ffc125', type: 'large' },      // Bottom right
                { x: 60, y: 15, color: '#daa520', type: 'medium' },      // Top right center
                { x: 40, y: 45, color: '#b8860b', type: 'small' },      // Center
                { x: 18, y: 35, color: '#f4a460', type: 'medium' },      // Left center
                { x: 82, y: 35, color: '#ffd700', type: 'small' },      // Right center
            ];
            
            function createFireworkBurst(config) {
                const burst = document.createElement('div');
                burst.className = 'firework-burst';
                burst.style.left = config.x + '%';
                burst.style.top = config.y + '%';
                burst.style.color = config.color;
                container.appendChild(burst);
                
                // Ajustar tamaño según el ancho de pantalla
                const isMobile = window.innerWidth <= 480;
                const isTablet = window.innerWidth <= 768;
                
                const lineCount = config.type === 'large' ? 12 : config.type === 'medium' ? 8 : 6;
                let lineLength = config.type === 'large' ? 40 : config.type === 'medium' ? 25 : 15;
                
                // Reducir tamaño en dispositivos móviles
                if (isMobile) {
                    lineLength = config.type === 'large' ? 25 : config.type === 'medium' ? 18 : 12;
                } else if (isTablet) {
                    lineLength = config.type === 'large' ? 32 : config.type === 'medium' ? 20 : 14;
                }
                
                // Crear líneas radiales
                for (let i = 0; i < lineCount; i++) {
                    const angle = (Math.PI * 2 * i) / lineCount;
                    const line = document.createElement('div');
                    line.className = 'firework-line';
                    line.style.width = '2px';
                    line.style.height = lineLength + 'px';
                    line.style.left = '50%';
                    line.style.top = '50%';
                    line.style.transform = `translate(-50%, -50%) rotate(${angle * 180 / Math.PI}deg)`;
                    line.style.opacity = '0.8';
                    burst.appendChild(line);
                }
                
                // Crear puntos pequeños (para algunos fuegos)
                if (config.type === 'small' || Math.random() > 0.5) {
                    const dotCount = config.type === 'small' ? 8 : 4;
                    for (let i = 0; i < dotCount; i++) {
                        const angle = (Math.PI * 2 * i) / dotCount;
                        const distance = lineLength * 0.7;
                        const dot = document.createElement('div');
                        dot.className = 'firework-dot';
                        dot.style.left = `calc(50% + ${Math.cos(angle) * distance}px)`;
                        dot.style.top = `calc(50% + ${Math.sin(angle) * distance}px)`;
                        dot.style.transform = 'translate(-50%, -50%)';
                        burst.appendChild(dot);
                    }
                }
                
                // Animación de aparición
                burst.style.opacity = '0';
                burst.style.transform = 'scale(0)';
                setTimeout(() => {
                    burst.style.transition = 'opacity 0.2s ease-out, transform 0.3s ease-out';
                    burst.style.opacity = '1';
                    burst.style.transform = 'scale(1)';
                }, 100);
                
                // Remover después de la animación
                setTimeout(() => {
                    burst.style.transition = 'opacity 0.2s ease-out';
                    burst.style.opacity = '0';
                    setTimeout(() => burst.remove(), 500);
                }, 9500);
            }
            
            // Función para iniciar la animación
            function startAnimation() {
                if (!overlay || !container) return;
                
                // Activar el overlay
                overlay.classList.add('active');
                
                // Crear todos los fuegos artificiales iniciales
                fireworkPositions.forEach((pos, index) => {
                    setTimeout(() => {
                        createFireworkBurst(pos);
                    }, index * 150);
                });
                
                // Crear fuegos artificiales adicionales durante la animación (todos dorados)
                const additionalFireworks = setInterval(() => {
                    const randomPos = {
                        x: Math.random() * 100,
                        y: Math.random() * 100,
                        color: goldColors[Math.floor(Math.random() * goldColors.length)],
                        type: ['small', 'medium', 'large'][Math.floor(Math.random() * 3)]
                    };
                    createFireworkBurst(randomPos);
                }, 600);
                
                // Ocultar overlay después de 10 segundos
                setTimeout(() => {
                    clearInterval(additionalFireworks);
                    overlay.classList.remove('active');
                    setTimeout(() => {
                        overlay.style.display = 'none';
                    }, 500);
                }, 10000);
            }
            
            // Iniciar cuando la página carga (múltiples eventos para compatibilidad)
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startAnimation);
            } else {
                // Si el DOM ya está cargado, iniciar inmediatamente
                setTimeout(startAnimation, 100);
            }
            
            // También escuchar el evento load por si acaso
            window.addEventListener('load', () => {
                if (!overlay.classList.contains('active')) {
                    startAnimation();
                }
            });
        })();
    </script>
    -->
    <!-- INICIO ANIMACION CORAZONES (COMENTADO)
    <style>
        .heart-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        }

        .heart {
            position: absolute;
            bottom: -50px;
            color: #ff4d6d;
            font-size: 24px;
            animation: floatUp 4s linear forwards;
            opacity: 0;
        }

        @keyframes floatUp {
            0% {
                transform: translateY(0) scale(0.5) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            100% {
                transform: translateY(-110vh) scale(1.2) rotate(360deg);
                opacity: 0;
            }
        }
    </style>

    <div id="heart-container" class="heart-container"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('heart-container');
            const duration = 10000; // 10 segundos
            const endTime = Date.now() + duration;

            // Función para crear un corazón
            function createHeart() {
                if (Date.now() > endTime) return;

                const heart = document.createElement('div');
                heart.classList.add('heart');
                // Usamos SVG para garantizar que se vea plano y tome el color en iOS (evita emoji)
                heart.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width:1em;height:1em;fill:currentColor;"><path d="M47.6 300.4L228.3 469.1c7.5 7 17.4 10.9 27.7 10.9s20.2-3.9 27.7-10.9L464.4 300.4c30.4-28.3 47.6-68 47.6-109.5v-5.8c0-69.9-50.5-129.5-119.4-141C347 36.5 300.6 51.4 268 84l-12 12-12-12c-32.6-32.6-79-47.5-124.6-39.9C50.5 55.6 0 115.2 0 185.1v5.8c0 41.5 17.2 81.2 47.6 109.5z"/></svg>';

                // Posición horizontal aleatoria
                heart.style.left = Math.random() * 100 + 'vw';

                // Tamaño aleatorio
                const size = Math.random() * 20 + 10 + 'px';
                heart.style.fontSize = size;

                // Color aleatorio (tonos de rojo/rosa)
                const colors = ['#ff4d6d', '#ff758f', '#ff8fa3', '#ffb3c1', '#c9184a', '#a8002a'];
                heart.style.color = colors[Math.floor(Math.random() * colors.length)];

                // Duración de animación aleatoria
                const animDuration = Math.random() * 3 + 3 + 's';
                heart.style.animationDuration = animDuration;

                container.appendChild(heart);

                // Eliminar el elemento después de que termine la animación
                setTimeout(() => {
                    heart.remove();
                }, 6000);
            }

            // Crear corazones continuamente
            const interval = setInterval(createHeart, 100);

            // Detener la creación de corazones a los 10 segundos
            setTimeout(() => {
                clearInterval(interval);
                // Opcional: eliminar el contenedor después de que todo termine
                setTimeout(() => {
                    container.remove();
                }, 7000); // Esperar a que terminen las últimas animaciones
            }, duration);
        });
    </script>
    FIN ANIMACION CORAZONES -->

    <!-- INICIO ANIMACION FLORES AMARILLAS -->
    <style>
        .flower-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        }

        .flower {
            position: absolute;
            bottom: -50px;
            color: #FFD700;
            font-size: 24px;
            animation: floatFlower 5s linear forwards;
            opacity: 0;
        }

        @keyframes floatFlower {
            0% {
                transform: translateY(0) scale(0.5) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            100% {
                transform: translateY(-110vh) scale(1.3) rotate(720deg);
                opacity: 0;
            }
        }
    </style>

    <div id="flower-container" class="flower-container"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('flower-container');
            const duration = 10000; // 10 segundos
            const endTime = Date.now() + duration;

            // Función para crear una flor
            function createFlower() {
                if (Date.now() > endTime) return;

                const flower = document.createElement('div');
                flower.classList.add('flower');
                // SVG de Flor refinada (8 pétalos, centro naranja)
                flower.innerHTML = `<svg viewBox="0 0 100 100" style="width:1.5em;height:1.5em;">
                    <g transform="translate(50,50)">
                        <g fill="currentColor">
                            <ellipse cx="0" cy="-30" rx="13" ry="20" transform="rotate(0)" />
                            <ellipse cx="0" cy="-30" rx="13" ry="20" transform="rotate(45)" />
                            <ellipse cx="0" cy="-30" rx="13" ry="20" transform="rotate(90)" />
                            <ellipse cx="0" cy="-30" rx="13" ry="20" transform="rotate(135)" />
                            <ellipse cx="0" cy="-30" rx="13" ry="20" transform="rotate(180)" />
                            <ellipse cx="0" cy="-30" rx="13" ry="20" transform="rotate(225)" />
                            <ellipse cx="0" cy="-30" rx="13" ry="20" transform="rotate(270)" />
                            <ellipse cx="0" cy="-30" rx="13" ry="20" transform="rotate(315)" />
                        </g>
                        <circle cx="0" cy="0" r="14" fill="#E67E22" />
                        <path d="M0 -8 L2 -2 L8 0 L2 2 L0 8 L-2 2 L-8 0 L-2 -2 Z" fill="white" />
                    </g>
                </svg>`;

                // Posición horizontal aleatoria
                flower.style.left = Math.random() * 100 + 'vw';

                // Tamaño aleatorio
                const size = Math.random() * 20 + 20 + 'px';
                flower.style.fontSize = size;

                // Color de los pétalos (tonos de amarillo intenso como en la imagen)
                const colors = ['#FFC107', '#FFD54F', '#FFEB3B', '#FBC02D', '#F9A825'];
                flower.style.color = colors[Math.floor(Math.random() * colors.length)];

                // Duración de animación aleatoria
                const animDuration = Math.random() * 4 + 4 + 's';
                flower.style.animationDuration = animDuration;

                container.appendChild(flower);

                // Eliminar el elemento después de que termine la animación
                setTimeout(() => {
                    flower.remove();
                }, 8000);
            }

            // Crear flores continuamente
            const interval = setInterval(createFlower, 150);

            // Detener la creación de flores a los 10 segundos
            setTimeout(() => {
                clearInterval(interval);
                // Opcional: eliminar el contenedor después de que todo termine
                setTimeout(() => {
                    if (container) container.remove();
                }, 9000);
            }, duration);
        });
    </script>
    <!-- FIN ANIMACION FLORES AMARILLAS -->

<?php require_once __DIR__ . '/includes/cart_drawer.php'; ?>
</body>

</html>