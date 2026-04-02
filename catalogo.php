<?php
session_start();
require_once "apis/db.php";

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

$categoria_id = isset($_GET['categoria']) ? (int) $_GET['categoria'] : 0;
$categoria_parent_id = isset($_GET['categoria_parent']) ? (int) $_GET['categoria_parent'] : 0;

// Obtener categorías para la navegación / footer
$cats = [];
$catRes = $conn->query("SELECT id, nombre FROM categoria WHERE parent_id IS NULL ORDER BY nombre");
if ($catRes) {
    while ($r = $catRes->fetch_assoc())
        $cats[] = $r;
}

// --- PAGINACIÓN: configuración ---
$perPage = 21;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Preparar subcategorías si llega categoria_parent (se mostrarán arriba del listado)
$subcategoryCards = [];
$parentCategoryName = '';
if ($categoria_parent_id > 0) {
    // Obtener nombre de la categoría padre
    $pstmt = $conn->prepare("SELECT nombre FROM categoria WHERE id = ? AND parent_id IS NULL");
    $pstmt->bind_param("i", $categoria_parent_id);
    $pstmt->execute();
    $pRes = $pstmt->get_result();
    if ($pRow = $pRes->fetch_assoc()) {
        $parentCategoryName = $pRow['nombre'];
    }
    $pstmt->close();

    // Subcategorías
    // Mapeo de subcategorías a productos específicos para sus imágenes
    $subcategoryImageMap = [
        2 => 'OHV-115',       // Visita
        3 => 'OHE-55',        // Operativa
        4 => 'OHE-305',       // Ejecutiva
        5 => 'Lyon',          // Sofás
        10 => 'AP 3',         // Archiveros (Almacenaje)
        11 => 'GC 100',       // Gabinetes (Almacenaje)
        23 => 'ES 140',       // Básicos (Escritorios)
        24 => 'EURO 160 Alt', // Operativos en L (Escritorios)
        26 => 'CEE 161 Alt 2', // Ejecutivos (Escritorios)
        27 => 'Eco Chair'     // Linea Eco
    ];

    $sstmt = $conn->prepare("SELECT id, nombre FROM categoria WHERE parent_id = ? ORDER BY nombre");
    $sstmt->bind_param("i", $categoria_parent_id);
    $sstmt->execute();
    $sres = $sstmt->get_result();
    while ($srow = $sres->fetch_assoc()) {
        $subId = (int) $srow['id'];
        $subName = $srow['nombre'];
        // Imagen representativa
        $img = '';

        // Primero intentar buscar un producto específico si está en el mapeo
        if (isset($subcategoryImageMap[$subId])) {
            $specificProductName = $subcategoryImageMap[$subId];
            // Usar LIKE para buscar productos que contengan el nombre (permite variaciones como "AP 3" o "AP-3")
            $specificStmt = $conn->prepare("SELECT imagen FROM producto WHERE id_categoria = ? AND nombre LIKE ? AND imagen IS NOT NULL AND imagen != '' LIMIT 1");
            $searchPattern = '%' . $specificProductName . '%';
            $specificStmt->bind_param("is", $subId, $searchPattern);
            $specificStmt->execute();
            $specificRes = $specificStmt->get_result();
            if ($specificRow = $specificRes->fetch_assoc()) {
                $imgCandidate = trim((string) $specificRow['imagen']);
                if ($imgCandidate !== '') {
                    $img = getImageUrl($imgCandidate);
                }
            }
            $specificStmt->close();
        }

        // Si no se encontró producto específico, usar el comportamiento por defecto
        if ($img === '') {
            $imgStmt = $conn->prepare("SELECT imagen FROM producto WHERE id_categoria = ? AND imagen IS NOT NULL AND imagen != '' ORDER BY id DESC LIMIT 1");
            $imgStmt->bind_param("i", $subId);
            $imgStmt->execute();
            $imgRes = $imgStmt->get_result();
            if ($imgRow = $imgRes->fetch_assoc()) {
                $imgCandidate = trim((string) $imgRow['imagen']);
                if ($imgCandidate !== '') {
                    $img = getImageUrl($imgCandidate);
                }
            }
            $imgStmt->close();
        }

        if ($img === '') {
            $img = 'https://via.placeholder.com/800x600?text=Sin+imagen';
        }
        $subcategoryCards[] = ['id' => $subId, 'nombre' => $subName, 'imagen' => $img];
    }
    $sstmt->close();
}

// Contar total de productos según filtro (categoría o categoria_parent)
if ($categoria_parent_id > 0) {
    // Obtener todas las subcategorías de la categoría principal
    $subCatStmt = $conn->prepare("SELECT id FROM categoria WHERE parent_id = ?");
    $subCatStmt->bind_param("i", $categoria_parent_id);
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

        // Contar productos
        $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM producto WHERE id_categoria IN ($placeholders)");
        $countStmt->bind_param(str_repeat('i', count($subCatIds)), ...$subCatIds);
        $countStmt->execute();
        $cntRes = $countStmt->get_result()->fetch_assoc();
        $totalProducts = (int) ($cntRes['cnt'] ?? 0);
        $countStmt->close();

        // Obtener productos paginados
        $stmt = $conn->prepare("SELECT * FROM producto WHERE id_categoria IN ($placeholders) ORDER BY nombre LIMIT ?, ?");
        $params = array_merge($subCatIds, [$offset, $perPage]);
        $stmt->bind_param(str_repeat('i', count($subCatIds)) . 'ii', ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $productos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    } else {
        // Si no tiene subcategorías, obtener productos directamente de la categoría principal
        $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM producto WHERE id_categoria = ?");
        $countStmt->bind_param("i", $categoria_parent_id);
        $countStmt->execute();
        $cntRes = $countStmt->get_result()->fetch_assoc();
        $totalProducts = (int) ($cntRes['cnt'] ?? 0);
        $countStmt->close();

        $stmt = $conn->prepare("SELECT * FROM producto WHERE id_categoria = ? ORDER BY nombre LIMIT ?, ?");
        $stmt->bind_param("iii", $categoria_parent_id, $offset, $perPage);
        $stmt->execute();
        $res = $stmt->get_result();
        $productos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
} elseif ($categoria_id > 0) {
    // Solo por categoría
    $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM producto WHERE id_categoria = ?");
    $countStmt->bind_param("i", $categoria_id);
    $countStmt->execute();
    $cntRes = $countStmt->get_result()->fetch_assoc();
    $totalProducts = (int) ($cntRes['cnt'] ?? 0);
    $countStmt->close();

    $stmt = $conn->prepare("SELECT * FROM producto WHERE id_categoria = ? ORDER BY nombre LIMIT ?, ?");
    $stmt->bind_param("iii", $categoria_id, $offset, $perPage);
    $stmt->execute();
    $res = $stmt->get_result();
    $productos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    // Todos los productos
    $cntRes = $conn->query("SELECT COUNT(*) AS cnt FROM producto")->fetch_assoc();
    $totalProducts = (int) ($cntRes['cnt'] ?? 0);

    $stmt = $conn->prepare("SELECT * FROM producto ORDER BY nombre LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $perPage);
    $stmt->execute();
    $res = $stmt->get_result();
    $productos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
}

$totalPages = $totalProducts > 0 ? (int) ceil($totalProducts / $perPage) : 1;

// Obtener lista completa para el <select> (sin paginar)
$allProducts = [];
$resAll = $conn->query("SELECT id, nombre FROM producto ORDER BY nombre");
if ($resAll) {
    while ($r = $resAll->fetch_assoc())
        $allProducts[] = $r;
}

// función auxiliar para construir URL de página manteniendo GET actuales
function pageUrl($p)
{
    $qp = $_GET;
    $qp['page'] = $p;
    return htmlspecialchars(basename($_SERVER['PHP_SELF']) . '?' . http_build_query($qp));
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - Ofiequipo de Tampico</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="shortcut icon" type="image/png" href="icono_logo.png">
    <link rel="apple-touch-icon" href="icono_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* Estilos específicos del catálogo que complementan el CSS externo */

        /* Page Header específico para catálogo */
        .page-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            color: white;
            padding: 80px 0 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
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

        .page-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zM36 0v4h-2V0h-4v2h4v4h2V2h4V0h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.4;
        }

        .page-header-content {
            position: relative;
            z-index: 1;
        }

        .page-header h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -1.5px;
        }

        .page-header p {
            font-size: 20px;
            opacity: 0.95;
            font-weight: 400;
        }


        .search-results-info {
            text-align: center;
            padding: 16px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }

        .search-results-info strong {
            color: var(--primary-blue);
        }

        /* Category Header Title - Estilo ZAMOFI */
        .category-header {
            margin-bottom: 32px;
            text-align: center;
        }

        .category-header h2 {
            display: block;
            width: 100%;
            background: url('Titulos.jpeg') center/cover no-repeat;
            color: white;
            padding: 20px 60px;
            border-radius: 0;
            font-size: 42px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            margin: 0 0 16px 0;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-align: center;
        }

        .category-header p {
            color: #6b7280;
            font-size: 16px;
            margin: 0;
        }

        /* Category Navigation */
        .category-nav {
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 165px;
            z-index: 90;
        }

        /* Category Toggle Section */
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
            margin-top: 8px;
            padding: 8px 0;
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

        .navbar-category-count {
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

        .category-toggle-section {
            display: none;
        }

        .category-toggle-header {
            text-align: center;
            margin-bottom: 20px;
            display: none;
        }

        .category-toggle-header h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0 0 8px 0;
        }

        .category-toggle-header p {
            font-size: 16px;
            color: var(--text-gray);
            margin: 0;
        }

        /* Category Toggle */
        .category-toggle {
            display: flex;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            color: white;
            border: none;
            padding: 16px 24px;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 0 auto;
            max-width: 400px;
            width: calc(100% - 40px);
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(30, 64, 175, 0.3);
            position: relative;
            overflow: hidden;
        }

        .category-toggle::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .category-toggle:hover::before {
            left: 100%;
        }

        .category-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(30, 64, 175, 0.4);
        }

        .category-toggle:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(30, 64, 175, 0.3);
        }

        .category-toggle .icon {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .category-toggle.active .icon {
            transform: rotate(180deg);
        }

        .category-toggle-text {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .category-toggle-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }

        .category-dropdown {
            display: none;
            background: white;
            border: 1px solid rgba(30, 64, 175, 0.1);
            border-radius: 16px;
            margin: 16px auto 20px;
            max-width: 500px;
            width: calc(100% - 40px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(20px);
            position: relative;
        }

        .category-dropdown::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--primary-blue-dark));
        }

        .category-dropdown.active {
            display: block;
            animation: slideDown 0.3s ease;
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

        .category-dropdown-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            color: var(--text-gray);
            text-decoration: none;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            position: relative;
        }

        .category-dropdown-item:last-child {
            border-bottom: none;
        }

        .category-dropdown-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
            transition: width 0.3s ease;
        }

        .category-dropdown-item:hover::before {
            width: 4px;
        }

        .category-dropdown-item:hover {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.05), rgba(30, 64, 175, 0.02));
            color: var(--primary-blue);
            padding-left: 28px;
            transform: translateX(4px);
        }

        .category-dropdown-item.active {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
            color: white;
            box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.1);
        }

        .category-dropdown-item.active::before {
            width: 4px;
            background: rgba(255, 255, 255, 0.3);
        }

        .category-dropdown-item.active:hover {
            background: linear-gradient(135deg, var(--primary-blue-dark), var(--primary-blue));
            padding-left: 20px;
            transform: translateX(0);
        }

        .category-item-name {
            flex: 1;
        }

        .category-item-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            opacity: 0.7;
        }

        .category-dropdown-item.active .category-item-count {
            background: rgba(255, 255, 255, 0.3);
            opacity: 1;
        }

        .category-nav-content {
            display: none;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--text-light) var(--bg-gray);
            padding: 0;
        }

        /* Show both options on large screens */
        @media (min-width: 1024px) {
            .category-nav-content {
                display: flex;
            }

            .category-toggle {
                display: flex;
                margin: 20px auto;
                max-width: 800px;
            }

            .category-dropdown {
                max-width: 800px;
            }

            /* Hide hamburger menu on large screens */
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

        .category-nav-content::-webkit-scrollbar {
            height: 6px;
        }

        .category-nav-content::-webkit-scrollbar-track {
            background: var(--bg-gray);
        }

        .category-nav-content::-webkit-scrollbar-thumb {
            background: var(--text-light);
            border-radius: 3px;
        }

        .category-link {
            padding: 16px 24px;
            color: var(--text-gray);
            text-decoration: none;
            white-space: nowrap;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .category-link:hover,
        .category-link.active {
            color: var(--primary-blue);
            border-bottom-color: var(--primary-blue-light);
            background-color: var(--bg-light);
        }

        /* Ajustes para el grid de productos */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
        }

        /* Product Card - usando las clases del CSS externo pero con ajustes */
        .product-card .product-image {
            height: 280px;
        }

        .product-card .product-content {
            padding: 28px;
        }

        .product-card .product-name {
            font-size: 20px;
            margin-bottom: 14px;
        }

        .product-card .product-description {
            font-size: 15px;
            margin-bottom: 28px;
        }

        .product-footer {
            display: flex;
            gap: 12px;
        }

        .product-footer .btn {
            flex: 1;
            padding: 14px 24px;
            font-size: 14px;
            text-align: center;
        }

        /* Comprehensive responsive improvements for catalog */

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

        /* Tablets */
        @media (max-width: 1024px) {
            .container {
                padding-left: 20px;
                padding-right: 20px;
            }

            .page-header {
                padding: 60px 0 40px;
            }

            .page-header h1 {
                font-size: 36px;
            }

            .page-header p {
                font-size: 16px;
            }


            .search-btn {
                padding: 10px 16px;
                font-size: 14px;
            }

            .category-toggle {
                font-size: 14px;
                padding: 12px 18px;
                margin: 12px auto;
                width: calc(100% - 24px);
                max-width: 350px;
            }

            .category-dropdown {
                width: calc(100% - 24px);
                margin: 12px auto 16px;
                max-width: 450px;
            }

            .category-dropdown-item {
                padding: 14px 16px;
            }

            .category-nav {
                position: sticky;
                top: 145px;
            }

            .category-nav-content {
                gap: 4px;
                padding: 8px 12px;
            }

            .category-link {
                padding: 12px 16px;
                font-size: 14px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }

            .product-card .product-image {
                height: 220px;
            }

            .product-content {
                padding: 20px;
            }

            .product-name {
                font-size: 18px;
            }

            .product-description {
                font-size: 14px;
            }

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
                margin: 0;
                border-radius: 0 0 12px 12px;
                z-index: 1000;
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

            .menu-toggle.active span:nth-child(1) {
                transform: rotate(45deg) translate(5px, 5px);
            }

            .menu-toggle.active span:nth-child(2) {
                opacity: 0;
            }

            .menu-toggle.active span:nth-child(3) {
                transform: rotate(-45deg) translate(7px, -6px);
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
        }

        /* Estilos base para números de teléfono en promo-banner */
        .promo-banner .phone-numbers {
            color: #ffffff !important;
            font-weight: 600;
        }

        /* Mobile phones */
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

            .container {
                padding-left: 16px;
                padding-right: 16px;
            }

            .page-header {
                padding: 40px 0 30px;
            }

            .page-header h1 {
                font-size: 28px;
            }

            .page-header p {
                font-size: 14px;
            }


            .category-toggle {
                font-size: 16px;
                padding: 14px 16px;
                margin: 8px auto;
                width: calc(100% - 16px);
                max-width: 320px;
            }

            .category-dropdown {
                width: calc(100% - 16px);
                margin: 8px auto 12px;
                max-width: 380px;
            }

            .category-dropdown-item {
                padding: 12px 14px;
                font-size: 15px;
            }

            .category-toggle-count {
                font-size: 10px;
                padding: 3px 6px;
            }

            .category-item-count {
                font-size: 10px;
                padding: 2px 6px;
            }

            .category-nav {
                top: 125px;
            }

            .category-link {
                font-size: 13px;
                padding: 10px 12px;
            }

            .products-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .product-card {
                width: 100%;
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
            }

            .product-image {
                width: 100%;
                height: 200px;
                overflow: hidden;
                border-radius: 8px;
            }

            .product-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: center;
                object-position: center;
            }

            .product-name {
                font-size: 16px;
            }

            .product-description {
                font-size: 14px;
            }

            .product-content {
                padding: 16px;
                flex: 1;
                display: flex;
                flex-direction: column;
            }

            .product-footer {
                flex-direction: column;
                gap: 8px;
                margin-top: auto;
            }

            .product-footer .btn {
                width: 100%;
            }

            .modal .modal-content {
                width: calc(100% - 24px);
                margin: 12px auto;
                max-height: 90vh;
                border-radius: 12px;
            }

            .modal.active {
                align-items: flex-start;
                padding-top: 12px;
            }

            .modal .modal-body {
                padding: 16px;
                max-height: calc(90vh - 80px);
                overflow-y: auto;
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

            .pagination {
                gap: 4px;
                font-size: 14px;
            }

            .pagination a,
            .pagination span {
                padding: 8px 10px;
                font-size: 14px;
            }

            .category-header h2 {
                font-size: 24px;
            }

            .category-header p {
                font-size: 14px;
            }

            .header .logo h1 {
                font-size: 18px;
            }
        }

        /* Very small phones */
        @media (max-width: 480px) {
            .container {
                padding-left: 16px;
                padding-right: 16px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .category-nav {
                top: 105px;
            }

            .product-footer .btn {
                padding: 12px;
                font-size: 14px;
            }

            .product-image {
                height: 220px;
                border-radius: 12px;
            }

            .product-card {
                border-radius: 12px;
                overflow: hidden;
            }

            main.container {
                padding: 40px 16px;
            }

            .menu-toggle {
                width: 36px;
                height: 36px;
            }
        }

        /* WhatsApp FAB responsive improvements */
        .whatsapp-fab {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 56px;
            height: 56px;
            background: #25D366;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9998;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
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

        @media (max-width: 640px) {
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
        }

        @media (max-width: 480px) {
            .whatsapp-fab {
                width: 48px;
                height: 48px;
            }

            .whatsapp-fab svg {
                width: 24px;
                height: 24px;
            }
        }

        /* Responsive styles for category dropdown */
        @media (max-width: 1024px) {
            .navbar-category-dropdown-menu {
                min-width: 220px;
                max-height: 400px;
            }

            .navbar-category-item,
            .navbar-category-main {
                padding: 12px 16px;
                font-size: 13px;
            }

            .navbar-subcategory-item {
                padding: 10px 16px;
                font-size: 12px;
            }
        }

        @media (max-width: 768px) {
            .navbar-category-dropdown {
                position: static;
            }

            .navbar-category-dropdown-menu {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                height: 100vh;
                max-height: 100vh;
                border-radius: 0;
                margin: 0;
                padding: 0;
                background: white;
                z-index: 1001;
                overflow-y: auto;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .navbar-category-dropdown-menu.active {
                transform: translateX(0);
            }

            .mobile-dropdown-header {
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                padding: 20px;
                border-bottom: 1px solid #e5e7eb;
                background: #f8fafc;
            }

            .mobile-dropdown-header h3 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                color: var(--text-dark);
            }

            .mobile-dropdown-close {
                background: none;
                border: none;
                font-size: 24px;
                color: var(--text-gray);
                cursor: pointer;
                padding: 8px;
                border-radius: 4px;
                transition: all 0.2s ease;
            }

            .mobile-dropdown-close:hover {
                background: #e5e7eb;
                color: var(--text-dark);
            }

            .navbar-category-item,
            .navbar-category-main {
                padding: 16px 20px;
                font-size: 16px;
                border-bottom: 1px solid #f0f0f0;
                margin: 0;
                border-radius: 0;
            }

            .navbar-subcategory-item {
                padding: 14px 20px;
                font-size: 15px;
                margin: 0;
                border-radius: 0;
            }

            .navbar-subcategory-menu {
                margin-left: 0;
                border-left: none;
                border-top: 1px solid #e5e7eb;
                background: #f8fafc;
            }

            .navbar-category-count {
                font-size: 12px;
                padding: 4px 8px;
            }
        }

        @media (max-width: 480px) {
            .navbar-category-dropdown-menu {
                padding: 16px;
            }

            .navbar-category-item,
            .navbar-category-main {
                padding: 14px 16px;
                font-size: 15px;
            }

            .navbar-subcategory-item {
                padding: 12px 16px;
                font-size: 14px;
            }
        }

        /* Improve touch targets for mobile */
        @media (hover: none) and (pointer: coarse) {
            .btn {
                min-height: 44px;
                padding: 12px 20px;
            }

            .category-link {
                min-height: 44px;
                display: flex;
                align-items: center;
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

        /* Product Detail Modal Styles */
        .product-detail-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .product-detail-image {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            cursor: pointer;
        }

        .product-detail-image img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .product-detail-image:hover img {
            transform: scale(1.05);
        }

        /* Zoom hint */
        .zoom-hint {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .product-detail-image:hover .zoom-hint {
            opacity: 1;
        }

        /* Fullscreen zoom overlay */
        .zoom-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10001;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: zoom-out;
        }

        .zoom-overlay.active {
            display: flex;
        }

        .zoom-overlay img {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            transform-origin: center;
            transition: transform 0.3s ease;
            cursor: grab;
        }

        .zoom-overlay img:active {
            cursor: grabbing;
        }

        .zoom-controls {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            padding: 12px 20px;
            border-radius: 30px;
            display: flex;
            gap: 15px;
            align-items: center;
            z-index: 10002;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .zoom-overlay.active .zoom-controls {
            opacity: 1;
        }

        .zoom-controls button {
            background: transparent;
            border: 2px solid white;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .zoom-controls button:hover {
            background: white;
            color: black;
        }

        .zoom-controls .zoom-level {
            color: white;
            font-size: 14px;
            min-width: 60px;
            text-align: center;
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
    </style>
</head>

<body>
    <div class="promo-banner">
        <p>EArmado gratis | Entrega a domicilio (envío gratuito en zona metropolitana al sur de Tamaulipas)| Garantía
            segura por 1 año | Contacto: <span class="phone-numbers">(833) 213-3837 | (833) 217-2047</span></p>
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
                                                $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM producto WHERE id_categoria = ?");
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
                            endforeach
                            ?>

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
                                    $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM producto WHERE id_categoria = ?");
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
                            endforeach
                            ?>
                        </div>
                    </div>
                    <a href="catalogo.php" class="nav-link">Catálogo</a>
                    <a href="index.php#contacto" class="nav-link">Contacto</a>
                </nav>
                <div class="header-actions">
                    <a href="tel:8331881814" class="btn btn-secondary btn-small">Llamar</a>
                    <a href="https://wa.me/528331881814" class="btn btn-secondary btn-small">WhatsApp</a>
                    <button class="btn btn-primary btn-small" onclick="openQuoteModal()">Cotizar</button>
                    <?php $cartCount = array_sum(array_column($_SESSION['cart'] ?? [], 'cantidad')); ?>
                    <button onclick="openCartDrawer()" class="btn btn-secondary btn-small" style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
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

    <section class="page-header">
        <div class="page-header-content">
            <h1>Catálogo de Productos</h1>
            <p>Descubre nuestra amplia selección de mobiliario profesional para tu oficina</p>
        </div>
    </section>




    <main class="container" style="padding: 64px 32px;">
        <?php if ($categoria_parent_id > 0 && !empty($subcategoryCards)): ?>
            <section class="category-section" style="padding-top:0;">
                <div class="category-header">
                    <h2>Subcategorías<?= $parentCategoryName ? ' de ' . htmlspecialchars($parentCategoryName) : '' ?></h2>
                    <p>Selecciona una subcategoría para ver sus productos</p>
                </div>
                <div class="collections-grid"
                    style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin:24px 0;">
                    <?php foreach ($subcategoryCards as $sc): ?>
                        <article class="category-card"
                            style="background:#fff;border:1px solid #eef0f3;border-radius:10px;overflow:hidden;">
                            <a href="catalogo.php?categoria=<?= (int) $sc['id'] ?>"
                                style="display:block;text-decoration:none;color:inherit;">
                                <div class="category-image" style="height:220px;overflow:hidden;">
                                    <img src="<?= htmlspecialchars($sc['imagen']) ?>"
                                        alt="<?= htmlspecialchars($sc['nombre']) ?>"
                                        style="width:100%;height:100%;object-fit:cover;object-position:center;">
                                </div>
                                <div class="category-content" style="padding:18px;">
                                    <h3><?= htmlspecialchars($sc['nombre']) ?></h3>
                                    <div style="margin-top:12px;color:#0b5ed7;font-weight:600;">Ver productos →</div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php if (empty($productos)): ?>
            <section class="category-section">
                <div class="category-header">
                    <h2>No hay productos disponibles</h2>
                    <p>Actualmente no hay productos en esta categoría</p>
                </div>
            </section>
        <?php else: ?>
            <section class="category-section">
                <div class="category-header">
                    <h2>
                        <?php
                        if ($categoria_id > 0) {
                            $categoryName = '';

                            // Buscar primero en categorías principales
                            foreach ($cats as $c) {
                                if ($c['id'] == $categoria_id) {
                                    $categoryName = $c['nombre'];
                                    break;
                                }
                            }

                            // Si no se encontró en categorías principales, buscar en subcategorías
                            if (empty($categoryName)) {
                                $subCatStmt = $conn->prepare("SELECT nombre FROM categoria WHERE id = ?");
                                $subCatStmt->bind_param("i", $categoria_id);
                                $subCatStmt->execute();
                                $subCatResult = $subCatStmt->get_result();
                                if ($subCatRow = $subCatResult->fetch_assoc()) {
                                    $categoryName = $subCatRow['nombre'];
                                }
                                $subCatStmt->close();
                            }

                            echo htmlspecialchars($categoryName ?: 'Categoría');
                        } else {
                            echo "Todos los Productos";
                        }
                        ?>
                    </h2>
                    <p>Mostrando <?= count($productos) ?> de <?= $totalProducts ?> productos</p>
                </div>

                <div class="products-grid">
                    <?php foreach ($productos as $producto): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php
                                // Usar la función helper para normalizar la URL de la imagen
                                $imagenUrl = getImageUrl($producto['imagen'] ?? '');
                                ?>
                                <img src="<?= htmlspecialchars($imagenUrl) ?>"
                                    alt="<?= htmlspecialchars($producto['nombre']) ?>">
                            </div>
                            <div class="product-content">
                                <h3 class="product-name"><?= htmlspecialchars($producto['nombre']) ?></h3>
                                <p class="product-description">
                                    <?= htmlspecialchars($producto['descripcion'] ?? 'Producto de alta calidad para tu oficina') ?>
                                </p>
                                <div class="product-footer">
                                    <button class="btn btn-primary"
                                        onclick="openQuoteModal('<?= htmlspecialchars($producto['nombre'], ENT_QUOTES) ?>')">
                                        Cotizar
                                    </button>
                                    <a class="btn btn-secondary"
                                        href="producto.php?id=<?= (int)$producto['id'] ?>">Ver detalles</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>


                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="Paginación">
                        <style>
                            .pagination {
                                display: flex;
                                gap: 8px;
                                justify-content: center;
                                align-items: center;
                                margin: 28px 0;
                                flex-wrap: wrap;
                            }

                            .pagination a,
                            .pagination span {
                                padding: 8px 12px;
                                border-radius: 6px;
                                text-decoration: none;
                                color: var(--text-gray);
                                border: 1px solid #e6e6e6;
                                background: white;
                            }

                            .pagination a:hover {
                                background: #f5f7ff;
                                color: var(--primary-blue);
                                border-color: #dbe4ff;
                            }

                            .pagination .active {
                                background: var(--primary-blue);
                                color: white;
                                border-color: var(--primary-blue-dark);
                            }

                            .pagination .disabled {
                                opacity: 0.5;
                                pointer-events: none;
                            }
                        </style>


                        <?php if ($page > 1): ?>
                            <a href="<?= pageUrl($page - 1) ?>">&laquo; Anterior</a>
                        <?php else: ?>
                            <span class="disabled">&laquo; Anterior</span>
                        <?php endif; ?>


                        <?php
                        $start = max(1, $page - 3);
                        $end = min($totalPages, $page + 3);
                        if ($start > 1) {
                            echo '<a href="' . pageUrl(1) . '">1</a>';
                            if ($start > 2)
                                echo '<span>…</span>';
                        }
                        for ($p = $start; $p <= $end; $p++):
                            ?>
                            <?php if ($p == $page): ?>
                                <span class="active"><?= $p ?></span>
                            <?php else: ?>
                                <a href="<?= pageUrl($p) ?>"><?= $p ?></a>
                            <?php endif; ?>
                        <?php endfor;
                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1)
                                echo '<span>…</span>';
                            echo '<a href="' . pageUrl($totalPages) . '">' . $totalPages . '</a>';
                        }
                        ?>


                        <?php if ($page < $totalPages): ?>
                            <a href="<?= pageUrl($page + 1) ?>">Siguiente &raquo;</a>
                        <?php else: ?>
                            <span class="disabled">Siguiente &raquo;</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

            </section>
        <?php endif; ?>
    </main>


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

                    <div class="form-group">
                        <label for="productSelect">Producto de Interés</label>
                        <select id="productSelect" name="producto" required>
                            <option value="">Seleccione un producto</option>
                            <?php if (empty($allProducts)): ?>
                                <option value="" disabled>Sin producto que mostrar</option>
                            <?php else: ?>
                                <?php foreach ($allProducts as $p): ?>
                                    <option value="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>">
                                        <?= htmlspecialchars($p['nombre']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group"><label for="name">Nombre Completo</label><input type="text" id="name"
                            name="nombre" required></div>
                    <div class="form-group"><label for="company">Empresa</label><input type="text" id="company"
                            name="empresa"></div>
                    <div class="form-group"><label for="email">Correo Electrónico</label><input type="email" id="email"
                            name="email" required></div>
                    <div class="form-group"><label for="phone">Teléfono</label><input type="tel" id="phone"
                            name="telefono" required></div>
                    <div class="form-group"><label for="quantity">Cantidad</label><input type="number" id="quantity"
                            name="cantidad" min="1" value="1"></div>
                    <div class="form-group"><label for="message">Mensaje Adicional</label><textarea id="message"
                            name="mensaje" rows="3"></textarea></div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeQuoteModal()">Cancelar</button>
                        <button type="submit" id="cotizar" class="btn btn-primary">Enviar Cotización</button>
                    </div>
                </form>

                <iframe name="hidden_iframe" id="hidden_iframe" style="display:none;" aria-hidden="true"></iframe>
            </div>
        </div>
    </div>

    <!-- Modal de detalles del producto -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="productModalTitle">Detalles del Producto</h2>
                <button class="modal-close" onclick="closeProductModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="product-detail-content">
                    <div class="product-detail-image" onclick="openImageZoom()">
                        <img id="productModalImage" src="" alt=""
                            style="width: 100%; height: 300px; object-fit: contain; border-radius: 8px;">
                        <div class="zoom-hint">🔍 Click para hacer zoom</div>
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

    <!-- Zoom Overlay -->
    <div class="zoom-overlay" id="zoomOverlay">
        <img id="zoomedImage" src="" alt="">
        <div class="zoom-controls">
            <button onclick="zoomOut()" title="Zoom Out">−</button>
            <span class="zoom-level" id="zoomLevel">100%</span>
            <button onclick="zoomIn()" title="Zoom In">+</button>
            <button onclick="resetZoom()" title="Reset">⟲</button>
        </div>
    </div>


    <!-- Footer -->
    <!-- Footer -->
    <footer class="footer" id="contacto">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>OFIEQUIPO<span>DE TAMPICO</span></h3>
                    <p>Soluciones integrales en mobiliario corporativo desde 1997. Comprometidos con la excelencia y la
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
                        <li>Aguila, 89240 Tampico, Tamps.</li>
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
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Navbar category dropdown functionality
            const navbarCategoryToggle = document.getElementById('navbarCategoryToggle');
            const navbarCategoryDropdown = document.getElementById('navbarCategoryDropdown');

            if (navbarCategoryToggle && navbarCategoryDropdown) {
                navbarCategoryToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const isActive = navbarCategoryDropdown.classList.contains('active');

                    if (isActive) {
                        navbarCategoryDropdown.classList.remove('active');
                        navbarCategoryToggle.classList.remove('active');
                    } else {
                        navbarCategoryDropdown.classList.add('active');
                        navbarCategoryToggle.classList.add('active');
                    }

                    // Don't close mobile menu when toggling categories
                    console.log('Category dropdown toggled, mobile menu stays open');
                });

                // Close dropdown when clicking outside (but not on mobile menu)
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

                // Close dropdown and mobile menu when clicking on a category item
                navbarCategoryDropdown.addEventListener('click', function (e) {
                    // Only close dropdown if clicking on actual category items, not the dropdown container
                    if (e.target.closest('.navbar-category-item') || e.target.closest('.navbar-subcategory-item')) {
                        navbarCategoryDropdown.classList.remove('active');
                        navbarCategoryToggle.classList.remove('active');
                        console.log('Category item clicked, dropdown closed');

                        // Close mobile menu when navigating to a category page
                        if (window.innerWidth <= 1024) {
                            const nav = document.querySelector('.nav');
                            const menuToggle = document.querySelector('.menu-toggle');
                            if (nav && menuToggle) {
                                setTimeout(() => {
                                    nav.classList.remove('active');
                                    menuToggle.classList.remove('active');
                                    document.body.style.overflow = 'auto';
                                }, 100);
                            }
                        }
                    }
                });
            }

            // Subcategory toggle functionality
            const categoryGroups = document.querySelectorAll('.navbar-category-group');

            // Remove existing event listeners to prevent duplicates
            categoryGroups.forEach(function (group) {
                const mainCategory = group.querySelector('.navbar-category-main');
                if (mainCategory) {
                    // Clone the element to remove all event listeners
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

                        // Close all other submenus
                        freshCategoryGroups.forEach(function (otherGroup) {
                            const otherSubmenu = otherGroup.querySelector('.navbar-subcategory-menu');
                            const otherMain = otherGroup.querySelector('.navbar-category-main');
                            if (otherSubmenu && otherMain && otherGroup !== group) {
                                otherSubmenu.classList.remove('active');
                                otherMain.classList.remove('active');
                            }
                        });

                        // Toggle current submenu
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
    </script>
    <script>
        (function () {
            const form = document.getElementById('quoteForm');
            const submitBtn = document.getElementById('cotizar');
            const iframe = document.getElementById('hidden_iframe');

            let submitting = false;

            form.addEventListener('submit', function (e) {
                if (form.action && form.action.includes('formsubmit.co')) {
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        e.preventDefault();
                        return;
                    }
                    submitting = true;
                    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Enviando...'; }
                    return;
                }
                e.preventDefault();
            });

            iframe.addEventListener('load', function () {
                if (!submitting) return;
                submitting = false;

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Enviar Cotización';
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Cotización enviada',
                    text: 'Gracias. Hemos recibido su solicitud.',
                    confirmButtonColor: '#1e40af'
                }).then(() => {
                    form.reset();
                    const productSelect = document.getElementById('productSelect');
                    if (productSelect) productSelect.value = '';
                    closeQuoteModal();

                    try {
                        iframe.contentWindow.document.open();
                        iframe.contentWindow.document.write('');
                        iframe.contentWindow.document.close();
                    } catch (err) { }
                });
            });
        })();

        function openQuoteModal(productName = '') {
            const modal = document.getElementById('quoteModal');
            const productSelect = document.getElementById('productSelect');

            if (productName) {
                productSelect.value = productName;
            }

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeQuoteModal() {
            const modal = document.getElementById('quoteModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function openProductModal(name, description, image) {
            const modal = document.getElementById('productModal');
            const modalName = document.getElementById('productModalName');
            const modalDescription = document.getElementById('productModalDescription');
            const modalImage = document.getElementById('productModalImage');

            modalName.textContent = name;
            modalDescription.textContent = description;
            modalImage.src = image;
            modalImage.alt = name;

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeProductModal() {
            const modal = document.getElementById('productModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function openQuoteModalFromProduct() {
            const modal = document.getElementById('productModal');
            const productName = document.getElementById('productModalName').textContent;

            closeProductModal();
            openQuoteModal(productName);
        }

        // Image Zoom Functionality
        let currentZoomLevel = 1;
        const minZoom = 1;
        const maxZoom = 3;
        const zoomStep = 0.25;
        let isDragging = false;
        let startX, startY;
        let translateX = 0, translateY = 0;
        let currentTranslateX = 0, currentTranslateY = 0;

        function openImageZoom() {
            const overlay = document.getElementById('zoomOverlay');
            const zoomedImage = document.getElementById('zoomedImage');
            const productImage = document.getElementById('productModalImage');

            zoomedImage.src = productImage.src;
            zoomedImage.alt = productImage.alt;
            overlay.classList.add('active');
            currentZoomLevel = 1;
            translateX = 0;
            translateY = 0;
            currentTranslateX = 0;
            currentTranslateY = 0;
            updateZoomLevel();

            document.body.style.overflow = 'hidden';
        }

        function closeImageZoom() {
            const overlay = document.getElementById('zoomOverlay');
            overlay.classList.remove('active');
            currentZoomLevel = 1;
            translateX = 0;
            translateY = 0;
            currentTranslateX = 0;
            currentTranslateY = 0;
            updateZoomLevel();

            // Restore body scroll only if product modal is closed
            const productModal = document.getElementById('productModal');
            if (!productModal.classList.contains('active')) {
                document.body.style.overflow = 'auto';
            }
        }

        function zoomIn() {
            if (currentZoomLevel < maxZoom) {
                currentZoomLevel += zoomStep;
                updateZoomLevel();
            }
        }

        function zoomOut() {
            if (currentZoomLevel > minZoom) {
                currentZoomLevel -= zoomStep;
                updateZoomLevel();
            }
        }

        function resetZoom() {
            currentZoomLevel = 1;
            translateX = 0;
            translateY = 0;
            currentTranslateX = 0;
            currentTranslateY = 0;
            updateZoomLevel();
        }

        function updateZoomLevel() {
            const zoomedImage = document.getElementById('zoomedImage');
            const zoomLevelDisplay = document.getElementById('zoomLevel');

            if (currentZoomLevel <= 1) {
                // Reset translation when zoom is at minimum
                translateX = 0;
                translateY = 0;
                currentTranslateX = 0;
                currentTranslateY = 0;
                zoomedImage.style.cursor = 'zoom-out';
            } else {
                zoomedImage.style.cursor = 'grab';
            }

            zoomedImage.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentZoomLevel})`;
            zoomLevelDisplay.textContent = `${Math.round(currentZoomLevel * 100)}%`;
        }

        // Close zoom overlay on click (outside image)
        document.addEventListener('DOMContentLoaded', function () {
            const zoomOverlay = document.getElementById('zoomOverlay');
            const zoomedImage = document.getElementById('zoomedImage');

            zoomOverlay.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeImageZoom();
                }
            });

            // Mouse wheel zoom
            zoomedImage.addEventListener('wheel', function (e) {
                e.preventDefault();
                if (e.deltaY < 0) {
                    zoomIn();
                } else {
                    zoomOut();
                }
            });

            // Drag functionality for zoomed image
            zoomedImage.addEventListener('mousedown', function (e) {
                if (currentZoomLevel > 1) {
                    isDragging = true;
                    startX = e.clientX - currentTranslateX;
                    startY = e.clientY - currentTranslateY;
                    this.style.cursor = 'grabbing';
                    e.preventDefault();
                }
            });

            document.addEventListener('mousemove', function (e) {
                if (!isDragging) return;

                translateX = e.clientX - startX;
                translateY = e.clientY - startY;

                const zoomedImage = document.getElementById('zoomedImage');
                zoomedImage.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentZoomLevel})`;
            });

            document.addEventListener('mouseup', function () {
                if (isDragging) {
                    isDragging = false;
                    currentTranslateX = translateX;
                    currentTranslateY = translateY;
                    const zoomedImage = document.getElementById('zoomedImage');
                    if (currentZoomLevel > 1) {
                        zoomedImage.style.cursor = 'grab';
                    } else {
                        zoomedImage.style.cursor = 'zoom-out';
                    }
                }
            });
        });

        // Keyboard controls for zoom
        document.addEventListener('keydown', function (e) {
            const overlay = document.getElementById('zoomOverlay');
            if (overlay && overlay.classList.contains('active')) {
                if (e.key === 'Escape') {
                    closeImageZoom();
                } else if (e.key === '+' || e.key === '=') {
                    zoomIn();
                } else if (e.key === '-' || e.key === '_') {
                    zoomOut();
                } else if (e.key === '0') {
                    resetZoom();
                }
            }
        });

        document.getElementById('quoteModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeQuoteModal();
            }
        });

        document.getElementById('productModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeProductModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                // Primero verificar si el zoom está activo
                const zoomOverlay = document.getElementById('zoomOverlay');
                if (zoomOverlay && zoomOverlay.classList.contains('active')) {
                    // No hacer nada aquí, el listener de zoom se encarga
                    return;
                }
                closeQuoteModal();
                closeProductModal();
            }
        });

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

        document.querySelectorAll('.category-card, .product-card, .benefit-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        const header = document.querySelector('.header');
        let lastScroll = 0;

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            if (currentScroll > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }

            lastScroll = currentScroll;
        });

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

                    const headerHeight = document.querySelector('.header').offsetHeight;
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
                    // Close for all navigation links (index.php, catalogo.php, index.php#contacto, etc)
                    if (href && !href.startsWith('#') && window.innerWidth <= 1024) {
                        nav.classList.remove('active');
                        menuToggle.classList.remove('active');
                        document.body.style.overflow = 'auto';
                    }
                });
            });
        }

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
    </script>

    <a class="whatsapp-fab" href="https://wa.me/528331881814?text=Hola%20quiero%20una%20cotizaci%C3%B3n" target="_blank"
        rel="noopener noreferrer" aria-label="Contactar por WhatsApp">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path
                d="M20.52 3.48A11.88 11.88 0 0 0 12 .5C5.73.5.99 5.24.99 11.5c0 2.03.53 4.02 1.54 5.78L.5 23.5l6.45-1.68A11.92 11.92 0 0 0 12 23.5c6.27 0 11.01-4.74 11.01-11 0-2.95-1.15-5.73-3.49-7.02z"
                fill="#25D366" />
            <path
                d="M17.3 14.3c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.48-1.76-1.66-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2 0-.38-.02-.53-.02-.15-.67-1.62-.92-2.22-.24-.6-.48-.52-.67-.53-.17-.01-.37-.01-.57-.01-.2 0-.53.08-.81.38-.28.3-1.06 1.04-1.06 2.53 0 1.48 1.09 2.92 1.24 3.12.15.2 2.14 3.42 5.19 4.8 3.05 1.38 3.05.92 3.6.86.55-.07 1.76-.72 2.01-1.41.25-.69.25-1.28.18-1.41-.07-.14-.27-.2-.57-.35z"
                fill="#fff" />
        </svg>
    </a>
<?php require_once __DIR__ . '/includes/cart_drawer.php'; ?>
</body>

</html>