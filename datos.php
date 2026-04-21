<?php
session_start();
require_once __DIR__ . '/apis/db.php';
require_once __DIR__ . '/includes/require_login.php';

function getImageUrl($p) {
    if (empty($p)) return 'https://via.placeholder.com/800x600?text=Sin+imagen';
    $p = trim($p);
    if (preg_match('/^https?:\/\//i', $p)) return 'image.php?u=' . rawurlencode($p);
    if (filter_var($p, FILTER_VALIDATE_URL)) return 'image.php?u=' . rawurlencode($p);
    $p = str_replace('\\', '/', $p);
    $t = ltrim($p, '/');
    if (stripos($t, 'uploads/') === 0)
        return 'image.php?path=' . implode('/', array_map('rawurlencode', explode('/', $t)));
    return 'image.php?path=' . implode('/', array_map('rawurlencode', explode('/', 'Uploads/' . $t)));
}

$cart      = $_SESSION['cart'] ?? [];
$cartCount = array_sum(array_column($cart, 'cantidad'));
if (empty($cart)) { header('Location: carrito.php'); exit; }

// Guardar datos en sesión y redirigir a pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['checkout_datos'] = [
        'nombre'       => trim($_POST['nombre']       ?? ''),
        'apellido'     => trim($_POST['apellido']     ?? ''),
        'email'        => trim($_POST['email']        ?? ''),
        'telefono'     => trim($_POST['telefono']     ?? ''),
        'direccion'    => trim($_POST['direccion']    ?? ''),
        'colonia'      => trim($_POST['colonia']      ?? ''),
        'ciudad'       => trim($_POST['ciudad']       ?? ''),
        'estado'       => trim($_POST['estado']       ?? ''),
        'cp'           => trim($_POST['cp']           ?? ''),
        'factura'      => !empty($_POST['factura']),
        'rfc'          => trim($_POST['rfc']          ?? ''),
        'razon_social' => trim($_POST['razon_social'] ?? ''),
        'regimen'      => trim($_POST['regimen']      ?? ''),
        'email_fiscal' => trim($_POST['email_fiscal'] ?? ''),
    ];
    header('Location: pago.php');
    exit;
}

$d = $_SESSION['checkout_datos'] ?? [];

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
    <title>Datos de envío — Ofiequipo de Tampico</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="shortcut icon" type="image/png" href="icono_logo.png">
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
        .datos-page { max-width: 1100px; margin: 0 auto; padding: 40px 32px 80px; }

        .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #64748b; margin-bottom: 32px; }
        .breadcrumb a { color: var(--primary-blue,#1e3a8a); text-decoration: none; font-weight: 500; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb-sep { color: #cbd5e1; }

        /* Progress steps */
        .pay-steps { display: flex; align-items: center; margin-bottom: 40px; }
        .step { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: #94a3b8; white-space: nowrap; }
        .step.active { color: #1e3a8a; }
        .step.done  { color: #16a34a; }
        .step-num { width: 28px; height: 28px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .step.active .step-num { background: #1e3a8a; color: white; }
        .step.done   .step-num { background: #16a34a; color: white; }
        .step-line { flex: 1; height: 2px; background: #e2e8f0; margin: 0 12px; max-width: 52px; }
        .step-line.done { background: #16a34a; }

        /* Layout */
        .datos-layout { display: grid; grid-template-columns: 1fr 360px; gap: 32px; align-items: start; }

        /* Form card */
        .form-card { background: white; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 16px rgba(0,0,0,0.04); overflow: hidden; }
        .form-card-head { padding: 20px 28px; border-bottom: 1px solid #e2e8f0; font-size: 15px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        .form-card-head svg { width: 20px; height: 20px; fill: #1e3a8a; }
        .form-card-body { padding: 28px; }
        .form-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 16px; margin-top: 28px; }
        .form-section-title:first-child { margin-top: 0; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 140px; gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group:last-child { margin-bottom: 0; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }
        .form-group input, .form-group select {
            width: 100%; padding: 12px 14px;
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 14px; font-family: inherit; color: #0f172a;
            background: #f8fafc; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); background: white;
        }
        .form-group input::placeholder { color: #94a3b8; }

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

        /* Address book */
        .addr-book { margin-bottom: 20px; }
        .addr-book-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .addr-book-title { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; }
        .addr-book-title svg { flex-shrink: 0; }
        .addr-cards { display: flex; flex-direction: column; gap: 8px; }
        .addr-card {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
            background: #f8fafc; transition: border-color 0.2s, background 0.2s;
        }
        .addr-card:hover { border-color: #93c5fd; background: white; }
        .addr-card.selected { border-color: #2563eb; background: #eff6ff; }
        .addr-card-text { flex: 1; min-width: 0; }
        .addr-card-text strong { display: block; font-size: 13px; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .addr-card-text span { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .addr-card-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .addr-btn-use {
            padding: 6px 14px; background: #1e3a8a; color: white;
            border: none; border-radius: 7px; font-size: 12px; font-weight: 600;
            font-family: inherit; cursor: pointer; transition: background 0.15s;
        }
        .addr-btn-use:hover { background: #2563eb; }
        .addr-btn-del {
            width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
            background: white; color: #94a3b8; border: 1.5px solid #e2e8f0; border-radius: 7px;
            cursor: pointer; transition: color 0.15s, border-color 0.15s;
        }
        .addr-btn-del:hover { color: #ef4444; border-color: #fca5a5; background: #fef2f2; }

        /* Save address checkbox */
        .save-addr-toggle {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 14px; background: #f8fafc;
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            cursor: pointer; font-size: 13px; color: #475569;
            margin-top: 16px; transition: border-color 0.2s;
        }
        .save-addr-toggle:hover { border-color: #93c5fd; }
        .save-addr-toggle input[type="checkbox"] { width: 16px; height: 16px; accent-color: #1e3a8a; flex-shrink: 0; cursor: pointer; }

        /* Location filled feedback */
        .location-ok {
            display: none; align-items: center; gap: 6px;
            font-size: 12px; color: #16a34a; font-weight: 600;
            background: #dcfce7; border-radius: 8px; padding: 8px 12px;
            margin-bottom: 16px;
        }
        .location-ok.show { display: flex; }
        .location-ok svg { width: 14px; height: 14px; fill: #16a34a; }

        /* Invoice checkbox */
        .factura-toggle {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 16px 18px; background: #f8fafc;
            border: 1.5px solid #e2e8f0; border-radius: 12px;
            cursor: pointer; transition: border-color 0.2s, background 0.2s;
            margin-top: 28px; margin-bottom: 0;
        }
        .factura-toggle:has(input:checked) { border-color: #2563eb; background: #eff6ff; }
        .factura-toggle input[type="checkbox"] {
            width: 18px; height: 18px; accent-color: #1e3a8a;
            flex-shrink: 0; margin-top: 2px; cursor: pointer;
        }
        .factura-toggle-text strong { display: block; font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
        .factura-toggle-text span { font-size: 12px; color: #64748b; line-height: 1.4; }

        /* Invoice fields (hidden until checked) */
        .factura-fields {
            display: none;
            background: #eff6ff; border: 1.5px solid #2563eb;
            border-radius: 12px; padding: 20px;
            margin-top: 12px;
            animation: fadeSlideDown 0.25s ease;
        }
        .factura-fields.show { display: block; }
        @keyframes fadeSlideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .factura-note {
            display: flex; align-items: flex-start; gap: 8px;
            font-size: 12px; color: #1e40af; background: white;
            border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 12px;
            margin-bottom: 16px; line-height: 1.5;
        }
        .factura-note svg { width: 15px; height: 15px; fill: #2563eb; flex-shrink: 0; margin-top: 1px; }

        /* Continue button */
        .btn-continue {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 16px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white; border: none; border-radius: 13px;
            font-size: 16px; font-weight: 700; font-family: inherit; cursor: pointer;
            box-shadow: 0 4px 18px rgba(37,99,235,0.35);
            transition: transform 0.15s, box-shadow 0.15s;
            margin-top: 28px;
        }
        .btn-continue:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(37,99,235,0.45); }
        .btn-continue svg { width: 18px; height: 18px; fill: white; }

        /* Order summary sidebar */
        .order-summary { background: white; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 16px rgba(0,0,0,0.04); position: sticky; top: 100px; overflow: hidden; }
        .order-summary-head { background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); padding: 18px 22px; color: white; }
        .order-summary-head h3 { font-size: 15px; font-weight: 700; margin-bottom: 3px; }
        .order-summary-head p { font-size: 12px; opacity: 0.7; }
        .order-summary-body { padding: 16px; }
        .order-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .order-item:last-child { border-bottom: none; }
        .order-item-img { width: 52px; height: 52px; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: contain; padding: 4px; background: white; flex-shrink: 0; }
        .order-item-name { font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.3; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .order-item-qty { font-size: 12px; color: #64748b; font-weight: 500; flex-shrink: 0; }
        .order-divider { border: none; border-top: 1px solid #e2e8f0; margin: 12px 0; }
        .order-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #475569; margin-bottom: 8px; }
        .order-row strong { color: #0f172a; font-weight: 700; }
        .order-badge { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #16a34a; font-weight: 600; background: #dcfce7; border-radius: 8px; padding: 8px 12px; margin-top: 12px; }
        .order-badge svg { width: 14px; height: 14px; fill: #16a34a; }

        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 900px) { .datos-layout { grid-template-columns: 1fr; } .order-summary { position: static; } }
        @media (max-width: 640px) { .datos-page { padding: 24px 16px 60px; } .form-row, .form-row-3 { grid-template-columns: 1fr; } .pay-steps { gap: 0; } .step span { display: none; } }
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

    <main class="datos-page">

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">Inicio</a>
            <span class="breadcrumb-sep">›</span>
            <a href="catalogo.php">Catálogo</a>
            <span class="breadcrumb-sep">›</span>
            <a href="carrito.php">Mi Carrito</a>
            <span class="breadcrumb-sep">›</span>
            <span style="color:#0f172a;font-weight:500;">Datos</span>
        </nav>

        <!-- Progress steps (4 steps) -->
        <div class="pay-steps">
            <div class="step done">
                <div class="step-num">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="white"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </div>
                <span>Carrito</span>
            </div>
            <div class="step-line done"></div>
            <div class="step active">
                <div class="step-num">2</div>
                <span>Datos</span>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-num">3</div>
                <span>Pago</span>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-num">4</div>
                <span>Confirmación</span>
            </div>
        </div>

        <div class="datos-layout">

            <!-- FORM -->
            <div>
                <form method="POST" action="datos.php" id="datosForm">

                    <!-- Personal info -->
                    <div class="form-card" style="margin-bottom:20px;">
                        <div class="form-card-head">
                            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            Información personal
                        </div>
                        <div class="form-card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Nombre *</label>
                                    <input type="text" name="nombre" placeholder="Juan" required
                                           value="<?= htmlspecialchars($d['nombre'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Apellido *</label>
                                    <input type="text" name="apellido" placeholder="García López" required
                                           value="<?= htmlspecialchars($d['apellido'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-row" style="margin-bottom:0;">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Correo electrónico *</label>
                                    <input type="email" name="email" placeholder="tucorreo@ejemplo.com" required
                                           value="<?= htmlspecialchars($d['email'] ?? '') ?>">
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Teléfono *</label>
                                    <input type="tel" name="telefono" placeholder="833 123 4567" required
                                           value="<?= htmlspecialchars($d['telefono'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping address -->
                    <div class="form-card" style="margin-bottom:20px;">
                        <div class="form-card-head">
                            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            Dirección de entrega
                        </div>
                        <div class="form-card-body">

                            <!-- Saved address book (rendered by JS) -->
                            <div id="addrBook"></div>

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
                                <label>Calle y número *</label>
                                <input type="text" id="direccion" name="direccion"
                                       placeholder="Av. Hidalgo 1234, Int. 5" required
                                       value="<?= htmlspecialchars($d['direccion'] ?? '') ?>">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Colonia</label>
                                    <input type="text" id="colonia" name="colonia" placeholder="Centro"
                                           value="<?= htmlspecialchars($d['colonia'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Ciudad *</label>
                                    <input type="text" id="ciudad" name="ciudad" placeholder="Tampico" required
                                           value="<?= htmlspecialchars($d['ciudad'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-row" style="margin-bottom:0;">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Estado *</label>
                                    <input type="text" id="estado" name="estado" placeholder="Tamaulipas" required
                                           value="<?= htmlspecialchars($d['estado'] ?? '') ?>">
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Código postal *</label>
                                    <input type="text" id="cp" name="cp" placeholder="89240"
                                           maxlength="5" pattern="\d{5}"
                                           value="<?= htmlspecialchars($d['cp'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Save address -->
                            <label class="save-addr-toggle">
                                <input type="checkbox" id="saveAddrCheck">
                                Guardar esta dirección para próximas compras
                            </label>
                        </div>
                    </div>

                    <!-- Invoice checkbox -->
                    <div class="form-card">
                        <div class="form-card-body" style="padding-bottom:24px;">

                            <label class="factura-toggle">
                                <input type="checkbox" name="factura" id="facturaCheck"
                                       onchange="toggleFactura(this)"
                                       <?= !empty($d['factura']) ? 'checked' : '' ?>>
                                <div class="factura-toggle-text">
                                    <strong>¿Requiere factura?</strong>
                                    <span>Proporciona tus datos fiscales y los enviamos a nuestro equipo para que gestionen la factura.</span>
                                </div>
                            </label>

                            <div class="factura-fields <?= !empty($d['factura']) ? 'show' : '' ?>" id="facturaFields">
                                <div class="factura-note">
                                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                                    Solo recopilamos tus datos fiscales para enviarlos a nuestro equipo. La factura la emite Ofiequipo de Tampico directamente — este sistema no genera CFDI.
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>RFC *</label>
                                        <input type="text" name="rfc" id="rfcInput"
                                               placeholder="XAXX010101000" maxlength="13"
                                               style="text-transform:uppercase"
                                               value="<?= htmlspecialchars($d['rfc'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Razón Social *</label>
                                        <input type="text" name="razon_social"
                                               placeholder="Mi Empresa S.A. de C.V."
                                               value="<?= htmlspecialchars($d['razon_social'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="form-row" style="margin-bottom:0;">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label>Régimen Fiscal</label>
                                        <select name="regimen">
                                            <option value="" <?= empty($d['regimen']) ? 'selected' : '' ?>>Seleccionar...</option>
                                            <option value="601" <?= ($d['regimen']??'')==='601'?'selected':'' ?>>601 — General de Ley Personas Morales</option>
                                            <option value="612" <?= ($d['regimen']??'')==='612'?'selected':'' ?>>612 — Personas Físicas con Actividades Empresariales</option>
                                            <option value="616" <?= ($d['regimen']??'')==='616'?'selected':'' ?>>616 — Sin Obligaciones Fiscales</option>
                                            <option value="621" <?= ($d['regimen']??'')==='621'?'selected':'' ?>>621 — Incorporación Fiscal</option>
                                            <option value="626" <?= ($d['regimen']??'')==='626'?'selected':'' ?>>626 — Régimen Simplificado de Confianza</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label>Correo para factura</label>
                                        <input type="email" name="email_fiscal"
                                               placeholder="facturacion@empresa.com"
                                               value="<?= htmlspecialchars($d['email_fiscal'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <button type="submit" class="btn-continue">
                        <svg viewBox="0 0 24 24"><path d="M4 11v2h12l-5.5 5.5 1.42 1.42L19.84 12l-7.92-7.92L10.5 5.5 16 11H4z"/></svg>
                        Continuar al pago
                    </button>

                </form>
            </div>

            <!-- ORDER SUMMARY -->
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
                    <div class="order-badge">
                        <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                        Armado e instalación gratis
                    </div>
                </div>
            </aside>

        </div>
    </main>

    <script>
    // ── Geolocation ─────────────────────────────────────────────
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
                        document.getElementById('direccion').value = street;
                        document.getElementById('colonia').value   = a.suburb || a.neighbourhood || a.quarter || a.city_district || '';
                        document.getElementById('ciudad').value    = a.city || a.town || a.municipality || a.village || '';
                        document.getElementById('estado').value    = a.state || '';
                        document.getElementById('cp').value        = (a.postcode || '').replace(/[^0-9]/g,'').slice(0,5);

                        document.getElementById('locationOk').classList.add('show');
                        resetLocationBtn();
                    })
                    .catch(() => {
                        alert('No se pudo obtener la dirección. Intenta de nuevo.');
                        resetLocationBtn();
                    });
            },
            function(err) {
                const msgs = { 1:'Permiso denegado. Activa la ubicación en tu navegador.', 2:'Ubicación no disponible.', 3:'Tiempo de espera agotado.' };
                alert(msgs[err.code] || 'Error al obtener ubicación.');
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

    // ── Address book (localStorage) ────────────────────────────
    const ADDR_KEY = 'ofiequipo_addresses';

    function loadAddresses() {
        try { return JSON.parse(localStorage.getItem(ADDR_KEY) || '[]'); } catch(e) { return []; }
    }
    function saveAddresses(arr) {
        localStorage.setItem(ADDR_KEY, JSON.stringify(arr));
    }
    function escHtml(s) {
        return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function renderAddressBook() {
        const container = document.getElementById('addrBook');
        if (!container) return;
        const addrs = loadAddresses();
        if (addrs.length === 0) { container.innerHTML = ''; return; }

        let cards = addrs.map((a, i) => `
            <div class="addr-card" id="addrCard${i}">
                <div class="addr-card-text">
                    <strong>${escHtml(a.direccion)}</strong>
                    <span>${escHtml([a.colonia, a.ciudad, a.estado].filter(Boolean).join(', '))}${a.cp ? ' C.P. ' + escHtml(a.cp) : ''}</span>
                </div>
                <div class="addr-card-actions">
                    <button type="button" class="addr-btn-use" onclick="selectAddress(${i})">Usar</button>
                    <button type="button" class="addr-btn-del" onclick="deleteAddress(${i})" title="Eliminar">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    </button>
                </div>
            </div>`).join('');

        container.innerHTML = `
            <div class="addr-book">
                <div class="addr-book-header">
                    <span class="addr-book-title">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="#1e3a8a"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        Direcciones guardadas
                    </span>
                </div>
                <div class="addr-cards">${cards}</div>
            </div>`;
    }

    function selectAddress(i) {
        const addrs = loadAddresses();
        const a = addrs[i];
        if (!a) return;
        document.getElementById('direccion').value = a.direccion || '';
        document.getElementById('colonia').value   = a.colonia   || '';
        document.getElementById('ciudad').value    = a.ciudad    || '';
        document.getElementById('estado').value    = a.estado    || '';
        document.getElementById('cp').value        = a.cp        || '';
        document.querySelectorAll('.addr-card').forEach(c => c.classList.remove('selected'));
        const card = document.getElementById('addrCard' + i);
        if (card) card.classList.add('selected');
        document.getElementById('locationOk').classList.add('show');
        const saveCheck = document.getElementById('saveAddrCheck');
        if (saveCheck) saveCheck.checked = false;
    }

    function deleteAddress(i) {
        const addrs = loadAddresses();
        addrs.splice(i, 1);
        saveAddresses(addrs);
        renderAddressBook();
    }

    function saveCurrentAddress() {
        const addr = {
            direccion: (document.getElementById('direccion').value || '').trim(),
            colonia:   (document.getElementById('colonia').value   || '').trim(),
            ciudad:    (document.getElementById('ciudad').value    || '').trim(),
            estado:    (document.getElementById('estado').value    || '').trim(),
            cp:        (document.getElementById('cp').value        || '').trim(),
        };
        if (!addr.direccion) return;
        const addrs = loadAddresses();
        const exists = addrs.some(a => a.direccion === addr.direccion && a.ciudad === addr.ciudad && a.cp === addr.cp);
        if (!exists) { addrs.push(addr); saveAddresses(addrs); }
    }

    // Save on form submit if checkbox checked
    document.getElementById('datosForm').addEventListener('submit', function() {
        const saveCheck = document.getElementById('saveAddrCheck');
        if (saveCheck && saveCheck.checked) saveCurrentAddress();
    });

    // ── Factura toggle ──────────────────────────────────────────
    function toggleFactura(cb) {
        const fields = document.getElementById('facturaFields');
        if (cb.checked) {
            fields.classList.add('show');
        } else {
            fields.classList.remove('show');
        }
    }

    // ── RFC auto uppercase ──────────────────────────────────────
    const rfcInput = document.getElementById('rfcInput');
    if (rfcInput) rfcInput.addEventListener('input', function() { this.value = this.value.toUpperCase(); });

    // ── Navbar JS ───────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        renderAddressBook();
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
