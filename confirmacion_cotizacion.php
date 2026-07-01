<?php
session_start();
require_once __DIR__ . '/apis/db.php';

// Redirigir si no hay datos de cotización en sesión
$cot = $_SESSION['last_cotizacion'] ?? null;
if (!$cot) { header('Location: catalogo.php'); exit; }

unset($_SESSION['last_cotizacion']);

$folio   = $cot['folio']   ?? '';
$nombre  = $cot['nombre']  ?? '';
$empresa = $cot['empresa'] ?? '';
$email   = $cot['email']   ?? '';
$items     = $cot['items']   ?? [];
$total     = array_sum(array_column($items, 'cantidad'));
$totalRef  = array_sum(array_map(fn($i) => (float)($i['precio'] ?? 0) * (int)($i['cantidad'] ?? 1), $items));

// Navbar data
$totalProducts = 0;
$tp = $conn->query("SELECT COUNT(*) AS cnt FROM producto");
if ($tp) $totalProducts = $tp->fetch_assoc()['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización recibida — Ofiequipo de Tampico</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="shortcut icon" type="image/png" href="icono_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
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

        /* ── Page ─────────────────────────────────────────────── */
        .conf-page { max-width: 720px; margin: 0 auto; padding: 60px 32px 100px; }

        /* Progress */
        .cot-steps { display: flex; align-items: center; margin-bottom: 48px; }
        .step { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: #94a3b8; white-space: nowrap; }
        .step.done   { color: #16a34a; }
        .step-num { width: 28px; height: 28px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .step.done .step-num { background: #16a34a; color: white; }
        .step-line { flex: 1; height: 2px; background: #e2e8f0; margin: 0 12px; max-width: 60px; }
        .step-line.done { background: #16a34a; }

        /* Success hero */
        .conf-hero {
            text-align: center;
            padding: 48px 32px 40px;
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            margin-bottom: 24px;
            animation: fadeUp 0.5s ease;
        }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

        .conf-check {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 6px 20px rgba(22,163,74,0.3);
        }
        .conf-check svg { width: 36px; height: 36px; fill: white; }

        .conf-hero h1 {
            font-size: 28px; font-weight: 800; color: #0f172a;
            letter-spacing: -0.5px; margin: 0 0 10px;
        }
        .conf-hero p {
            font-size: 15px; color: #475569; margin: 0 0 28px; line-height: 1.6;
        }

        /* Folio badge */
        .folio-badge {
            display: inline-block;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white; border-radius: 14px;
            padding: 14px 32px; margin-bottom: 8px;
        }
        .folio-badge .label { font-size: 11px; font-weight: 600; opacity: 0.75; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; }
        .folio-badge .number { font-size: 28px; font-weight: 800; letter-spacing: 1px; }

        .conf-email-note {
            font-size: 13px; color: #64748b; margin-top: 12px;
        }

        /* Timeline */
        .conf-timeline {
            background: white; border-radius: 16px; border: 1px solid #e2e8f0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            padding: 24px 28px; margin-bottom: 24px;
        }
        .conf-timeline h3 { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 20px; }
        .tl-item { display: flex; gap: 16px; margin-bottom: 20px; }
        .tl-item:last-child { margin-bottom: 0; }
        .tl-dot {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 13px; font-weight: 700;
        }
        .tl-dot.done  { background: #dcfce7; color: #16a34a; }
        .tl-dot.next  { background: #eff6ff; color: #2563eb; }
        .tl-dot.later { background: #f8fafc; color: #94a3b8; }
        .tl-dot svg   { width: 16px; height: 16px; }
        .tl-content { flex: 1; padding-top: 4px; }
        .tl-content strong { display: block; font-size: 14px; font-weight: 600; color: #0f172a; margin-bottom: 2px; }
        .tl-content span { font-size: 13px; color: #64748b; }

        /* Products summary */
        .conf-products {
            background: white; border-radius: 16px; border: 1px solid #e2e8f0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            padding: 24px 28px; margin-bottom: 24px;
        }
        .conf-products h3 { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 16px; }
        .prod-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .prod-row:last-child { border-bottom: none; }
        .prod-row .name { color: #0f172a; flex: 1; padding-right: 12px; }
        .prod-row .qty { font-size: 13px; color: #64748b; font-weight: 600; white-space: nowrap; background: #f8fafc; padding: 3px 10px; border-radius: 20px; }

        /* CTA buttons */
        .conf-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-wa {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 24px;
            background: #16a34a; color: white;
            border: none; border-radius: 11px;
            font-size: 14px; font-weight: 600; font-family: inherit;
            text-decoration: none; cursor: pointer;
            box-shadow: 0 4px 14px rgba(22,163,74,0.3);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-wa:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(22,163,74,0.4); color: white; }
        .btn-catalog {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 24px;
            background: white; color: #1e3a8a;
            border: 1.5px solid #1e3a8a; border-radius: 11px;
            font-size: 14px; font-weight: 600; font-family: inherit;
            text-decoration: none; cursor: pointer;
            transition: background 0.15s, transform 0.15s;
        }
        .btn-catalog:hover { background: #eff6ff; transform: translateY(-1px); color: #1e3a8a; }
        .btn-wa svg, .btn-catalog svg { width: 17px; height: 17px; fill: currentColor; flex-shrink: 0; }

        @media (max-width: 640px) {
            .conf-page { padding: 32px 16px 60px; }
            .conf-hero { padding: 36px 20px 28px; }
            .conf-hero h1 { font-size: 22px; }
            .folio-badge .number { font-size: 22px; }
            .cot-steps .step span { display: none; }
            .conf-actions { flex-direction: column; }
            .btn-wa, .btn-catalog { justify-content: center; }
        }
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
                    </a>
                </div>
                <button class="menu-toggle" aria-label="Toggle menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <main class="conf-page">

        <!-- Progress -->
        <div class="cot-steps">
            <div class="step done">
                <div class="step-num"><svg width="13" height="13" viewBox="0 0 24 24" fill="white"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
                <span>Carrito</span>
            </div>
            <div class="step-line done"></div>
            <div class="step done">
                <div class="step-num"><svg width="13" height="13" viewBox="0 0 24 24" fill="white"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
                <span>Cotización</span>
            </div>
            <div class="step-line done"></div>
            <div class="step done">
                <div class="step-num"><svg width="13" height="13" viewBox="0 0 24 24" fill="white"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
                <span>Confirmación</span>
            </div>
        </div>

        <!-- Success hero -->
        <div class="conf-hero">
            <div class="conf-check">
                <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            </div>
            <h1>¡Solicitud enviada!</h1>
            <p>
                Hemos recibido tu cotización<?= $nombre ? ', <strong>' . htmlspecialchars($nombre) . '</strong>' : '' ?>.
                Te contactaremos en <strong>24–48 horas hábiles</strong>.
            </p>

            <div class="folio-badge">
                <div class="label">Número de folio</div>
                <div class="number"><?= htmlspecialchars($folio) ?></div>
            </div>
            <div class="conf-email-note">
                Confirmación enviada a <strong><?= htmlspecialchars($email) ?></strong>
            </div>
        </div>

        <!-- ¿Qué sigue? -->
        <div class="conf-timeline">
            <h3>¿Qué sigue?</h3>

            <div class="tl-item">
                <div class="tl-dot done">
                    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </div>
                <div class="tl-content">
                    <strong>Solicitud recibida</strong>
                    <span>Tu cotización fue registrada con el folio <?= htmlspecialchars($folio) ?>.</span>
                </div>
            </div>

            <div class="tl-item">
                <div class="tl-dot next">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"/></svg>
                </div>
                <div class="tl-content">
                    <strong>Te contactamos (24–48 horas)</strong>
                    <span>Nuestro equipo revisará disponibilidad y te enviará la cotización con precios a <?= htmlspecialchars($email) ?>.</span>
                </div>
            </div>

            <div class="tl-item">
                <div class="tl-dot later">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                </div>
                <div class="tl-content">
                    <strong>Confirmación y entrega</strong>
                    <span>Coordinaremos instalación y entrega a domicilio (envío gratuito en zona metropolitana).</span>
                </div>
            </div>
        </div>

        <!-- Productos cotizados -->
        <?php if (!empty($items)): ?>
        <div class="conf-products">
            <h3>Productos en esta cotización</h3>
            <?php foreach ($items as $item):
                $precio = (float)($item['precio'] ?? 0);
                $sub    = $precio * (int)($item['cantidad'] ?? 1);
            ?>
            <div class="prod-row">
                <span class="name"><?= htmlspecialchars($item['nombre']) ?></span>
                <span class="qty">×<?= (int)$item['cantidad'] ?></span>
                <?php if ($precio > 0): ?>
                <span style="font-size:13px;color:#1e3a8a;font-weight:700;margin-left:auto;">
                    $<?= number_format($sub, 2) ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if ($totalRef > 0): ?>
            <div class="prod-row" style="border-top:2px solid #e2e8f0;margin-top:8px;padding-top:10px;">
                <span class="name" style="font-weight:700;color:#0f172a;">Total de referencia</span>
                <span style="font-size:16px;font-weight:800;color:#1e3a8a;margin-left:auto;">$<?= number_format($totalRef, 2) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Acciones -->
        <div class="conf-actions">
            <a href="https://wa.me/528331881814?text=<?= urlencode("Hola, acabo de solicitar una cotización con el folio {$folio}. ¿Podrían darme más información?") ?>"
               target="_blank" rel="noopener" class="btn-wa">
                <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                Contactar por WhatsApp
            </a>
            <a href="catalogo.php" class="btn-catalog">
                <svg viewBox="0 0 24 24"><path d="M12 2l-5.5 9h11z M17.5 13c-2.485 0-4.5 2.015-4.5 4.5s2.015 4.5 4.5 4.5 4.5-2.015 4.5-4.5-2.015-4.5-4.5-4.5zM3 21.5h8v-8H3v8z"/></svg>
                Ver más productos
            </a>
        </div>

    </main>

    <script>
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
