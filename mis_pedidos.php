<?php
session_start();
require_once __DIR__ . '/apis/db.php';
require_once __DIR__ . '/includes/require_login.php';


$userId = (int)$_SESSION['user_id'];

// Obtener cliente_id del usuario
$stmtC = $conn->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
$stmtC->bind_param('i', $userId);
$stmtC->execute();
$cliente = $stmtC->get_result()->fetch_assoc();
$stmtC->close();
$clienteId = $cliente['id'] ?? null;

// Obtener pedidos
$pedidos = [];
if ($clienteId) {
    $stmtP = $conn->prepare("
        SELECT p.id, p.fecha_pedido, p.monto_total, p.estado, p.requiere_factura,
               pg.metodo_pago
        FROM pedidos p
        LEFT JOIN pagos pg ON pg.pedido_id = p.id
        WHERE p.cliente_id = ?
        ORDER BY p.fecha_pedido DESC
    ");
    $stmtP->bind_param('i', $clienteId);
    $stmtP->execute();
    $pedidos = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtP->close();
}

// Obtener detalles de cada pedido
$detalles = [];
foreach ($pedidos as $p) {
    $stmtD = $conn->prepare("
        SELECT dp.cantidad, dp.precio, pr.nombre, pb.imagen
        FROM detalle_pedidos dp
        LEFT JOIN productos pr ON pr.id = dp.producto_id
        LEFT JOIN producto pb ON pb.id = pr.producto_base_id
        WHERE dp.pedido_id = ?
    ");
    $stmtD->bind_param('i', $p['id']);
    $stmtD->execute();
    $detalles[$p['id']] = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtD->close();
}

// Header vars
$cartCount     = array_sum(array_column($_SESSION['cart'] ?? [], 'cantidad'));
$totalProducts = 0;
$tp = $conn->query("SELECT COUNT(*) AS cnt FROM producto");
if ($tp) $totalProducts = $tp->fetch_assoc()['cnt'] ?? 0;

function getImageUrl($p) {
    if (empty($p)) return 'https://via.placeholder.com/80x80?text=Sin+imagen';
    $p = trim($p);
    if (preg_match('/^https?:\/\//i', $p)) return 'image.php?u=' . rawurlencode($p);
    $p = str_replace('\\', '/', $p);
    $t = ltrim($p, '/');
    if (stripos($t, 'uploads/') === 0)
        return 'image.php?path=' . implode('/', array_map('rawurlencode', explode('/', $t)));
    return 'image.php?path=' . implode('/', array_map('rawurlencode', explode('/', 'Uploads/' . $t)));
}

$estadoInfo = [
    'pendiente'  => ['label' => 'Pendiente',   'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'procesando' => ['label' => 'Procesando',  'color' => '#3b82f6', 'bg' => '#eff6ff'],
    'enviado'    => ['label' => 'Enviado',     'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
    'entregado'  => ['label' => 'Entregado',   'color' => '#16a34a', 'bg' => '#dcfce7'],
    'cancelado'  => ['label' => 'Cancelado',   'color' => '#ef4444', 'bg' => '#fef2f2'],
];
$metodoPago = [
    'paypal'           => 'PayPal',
    'tarjeta_credito'  => 'Tarjeta de crédito',
    'tarjeta_debito'   => 'Tarjeta de débito',
    'transferencia'    => 'Transferencia',
    'efectivo'         => 'Efectivo',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos — Ofiequipo de Tampico</title>
    <link rel="icon" type="image/png" href="icono_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Navbar */
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

        /* Page */
        .pedidos-page { max-width: 900px; margin: 0 auto; padding: 40px 32px 80px; }
        .breadcrumb { display:flex; align-items:center; gap:6px; font-size:13px; color:#64748b; margin-bottom:32px; }
        .breadcrumb a { color:var(--primary-blue,#1e3a8a); text-decoration:none; font-weight:500; }
        .breadcrumb a:hover { text-decoration:underline; }
        .breadcrumb-sep { color:#cbd5e1; }

        .page-title { font-size:26px; font-weight:800; color:#0f172a; margin-bottom:6px; }
        .page-subtitle { font-size:14px; color:#64748b; margin-bottom:32px; }

        /* Empty state */
        .empty-state { text-align:center; padding:60px 20px; background:white; border-radius:18px; border:1px solid #e2e8f0; }
        .empty-state svg { width:64px; height:64px; fill:#cbd5e1; margin-bottom:16px; }
        .empty-state h3 { font-size:18px; font-weight:700; color:#0f172a; margin-bottom:8px; }
        .empty-state p { font-size:14px; color:#64748b; margin-bottom:24px; }
        .btn-shop { display:inline-flex; align-items:center; gap:8px; padding:12px 24px; background:linear-gradient(135deg,#1e3a8a,#2563eb); color:white; border-radius:12px; font-size:14px; font-weight:700; text-decoration:none; transition:transform 0.15s, box-shadow 0.15s; }
        .btn-shop:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(37,99,235,0.35); }

        /* Order card */
        .order-card { background:white; border-radius:18px; border:1px solid #e2e8f0; box-shadow:0 2px 12px rgba(0,0,0,0.04); margin-bottom:16px; overflow:hidden; transition:box-shadow 0.2s; }
        .order-card:hover { box-shadow:0 4px 24px rgba(0,0,0,0.08); }
        .order-head { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; cursor:pointer; gap:16px; flex-wrap:wrap; }
        .order-head-left { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
        .order-id { font-size:13px; font-weight:700; color:#0f172a; }
        .order-id span { color:#64748b; font-weight:400; }
        .order-date { font-size:12px; color:#94a3b8; }
        .order-status { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; }
        .order-total { font-size:15px; font-weight:800; color:#0f172a; white-space:nowrap; }
        .order-chevron { width:20px; height:20px; fill:#94a3b8; transition:transform 0.25s; flex-shrink:0; }
        .order-card.open .order-chevron { transform:rotate(180deg); }
        .order-metodo { font-size:12px; color:#64748b; display:flex; align-items:center; gap:4px; }

        /* Order body (expandable) */
        .order-body { display:none; border-top:1px solid #f1f5f9; padding:20px 24px; }
        .order-card.open .order-body { display:block; animation:fadeSlideDown 0.2s ease; }
        @keyframes fadeSlideDown { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }

        .order-items { display:flex; flex-direction:column; gap:12px; }
        .order-item-row { display:flex; align-items:center; gap:14px; }
        .order-item-img { width:56px; height:56px; border-radius:10px; border:1px solid #e2e8f0; object-fit:contain; padding:4px; background:#f8fafc; flex-shrink:0; }
        .order-item-info { flex:1; }
        .order-item-name { font-size:14px; font-weight:600; color:#0f172a; line-height:1.3; }
        .order-item-detail { font-size:12px; color:#64748b; margin-top:2px; }
        .order-item-price { font-size:14px; font-weight:700; color:#0f172a; white-space:nowrap; }

        @media (max-width:640px) { .pedidos-page { padding:24px 16px 60px; } .order-head { padding:14px 16px; } .order-body { padding:16px; } }
    </style>
</head>
<body>

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
                    <div class="navbar-category-dropdown">
                        <a href="#" class="navbar-category-toggle" id="navbarCategoryToggle">
                            Productos
                            <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <div class="navbar-category-dropdown-menu" id="navbarCategoryDropdown">
                            <a href="catalogo.php" class="navbar-category-item">Todos los productos <span class="navbar-category-count"><?= $totalProducts ?></span></a>
                            <?php
                            $mainCategories = ['Sillería','Almacenaje','Línea Italia','Escritorios','Metálico','Líneas'];
                            $mainCatsData   = [];
                            foreach ($mainCategories as $n) {
                                $s = $conn->prepare("SELECT id,nombre FROM categoria WHERE nombre=? AND parent_id IS NULL");
                                $s->bind_param("s",$n); $s->execute();
                                $r = $s->get_result()->fetch_assoc(); $s->close();
                                if ($r) $mainCatsData[] = $r;
                            }
                            if (empty($mainCatsData)) $mainCatsData=[['id'=>1,'nombre'=>'Sillería'],['id'=>9,'nombre'=>'Almacenaje'],['id'=>13,'nombre'=>'Línea Italia'],['id'=>19,'nombre'=>'Escritorios'],['id'=>28,'nombre'=>'Metálico'],['id'=>39,'nombre'=>'Líneas']];
                            foreach ($mainCatsData as $mc):
                                $ss=$conn->prepare("SELECT id,nombre FROM categoria WHERE parent_id=? ORDER BY nombre");
                                $ss->bind_param("i",$mc['id']); $ss->execute();
                                $sub=$ss->get_result()->fetch_all(MYSQLI_ASSOC); $ss->close();
                                if (!empty($sub)):
                            ?>
                                <div class="navbar-category-group">
                                    <div class="navbar-category-main"><?= htmlspecialchars($mc['nombre']) ?><svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
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
                    <a href="carrito.php" class="btn btn-secondary btn-small" style="display:inline-flex;align-items:center;gap:6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM5.82 5H21l-1.68 8.39c-.16.79-.84 1.36-1.64 1.36H8.08c-.8 0-1.49-.57-1.64-1.36L5 5H3V3H5.82z"/></svg>
                        Carrito<?php if ($cartCount > 0): ?> <span style="background:#ef4444;color:white;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;padding:0 3px;"><?= $cartCount ?></span><?php endif; ?>
                    </a>
                    <?php require_once __DIR__ . '/includes/user_avatar.php'; ?>
                </div>
                <button class="menu-toggle" aria-label="Toggle menu"><span></span><span></span><span></span></button>
            </div>
        </div>
    </header>

    <main class="pedidos-page">

        <nav class="breadcrumb">
            <a href="index.php">Inicio</a>
            <span class="breadcrumb-sep">›</span>
            <span style="color:#0f172a;font-weight:500;">Mis Pedidos</span>
        </nav>

        <h1 class="page-title">Mis Pedidos</h1>
        <p class="page-subtitle"><?= count($pedidos) ?> pedido<?= count($pedidos) !== 1 ? 's' : '' ?> registrado<?= count($pedidos) !== 1 ? 's' : '' ?> en tu cuenta</p>

        <?php if (empty($pedidos)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
            <h3>Aún no tienes pedidos</h3>
            <p>Cuando realices tu primera compra aparecerá aquí el historial.</p>
            <a href="catalogo.php" class="btn-shop">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                Ver catálogo
            </a>
        </div>
        <?php else: ?>

        <?php foreach ($pedidos as $ped):
            $est = $estadoInfo[$ped['estado']] ?? ['label' => ucfirst($ped['estado']), 'color' => '#64748b', 'bg' => '#f1f5f9'];
            $items = $detalles[$ped['id']] ?? [];
            $metodo = $metodoPago[$ped['metodo_pago'] ?? ''] ?? ($ped['metodo_pago'] ?? '—');
            $fecha = date('d M Y, H:i', strtotime($ped['fecha_pedido']));
        ?>
        <div class="order-card" id="order-<?= $ped['id'] ?>">
            <div class="order-head" onclick="toggleOrder(<?= $ped['id'] ?>)">
                <div class="order-head-left">
                    <div>
                        <div class="order-id"><span>Pedido #</span><?= $ped['id'] ?></div>
                        <div class="order-date"><?= $fecha ?></div>
                    </div>
                    <span class="order-status" style="color:<?= $est['color'] ?>;background:<?= $est['bg'] ?>;">
                        <svg width="8" height="8" viewBox="0 0 8 8" fill="<?= $est['color'] ?>"><circle cx="4" cy="4" r="4"/></svg>
                        <?= $est['label'] ?>
                    </span>
                    <?php if ($metodo): ?>
                    <span class="order-metodo">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="#94a3b8"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                        <?= htmlspecialchars($metodo) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="order-total">$<?= number_format((float)$ped['monto_total'], 2) ?> MXN</span>
                    <svg class="order-chevron" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                </div>
            </div>
            <div class="order-body">
                <?php if (empty($items)): ?>
                    <p style="font-size:13px;color:#94a3b8;">Sin detalle de productos registrado.</p>
                <?php else: ?>
                <div class="order-items">
                    <?php foreach ($items as $it): ?>
                    <div class="order-item-row">
                        <img class="order-item-img"
                             src="<?= htmlspecialchars(getImageUrl($it['imagen'] ?? '')) ?>"
                             alt="<?= htmlspecialchars($it['nombre'] ?? '') ?>">
                        <div class="order-item-info">
                            <div class="order-item-name"><?= htmlspecialchars($it['nombre'] ?? 'Producto') ?></div>
                            <div class="order-item-detail">Cantidad: <?= (int)$it['cantidad'] ?> · $<?= number_format((float)$it['precio'], 2) ?> c/u</div>
                        </div>
                        <div class="order-item-price">$<?= number_format((float)$it['precio'] * (int)$it['cantidad'], 2) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($ped['requiere_factura'])): ?>
                <p style="margin-top:14px;font-size:12px;color:#2563eb;background:#eff6ff;padding:8px 12px;border-radius:8px;display:inline-flex;align-items:center;gap:6px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="#2563eb"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>
                    Factura solicitada — el equipo de Ofiequipo la gestionará.
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>
    </main>

    <script>
    function toggleOrder(id) {
        const card = document.getElementById('order-' + id);
        card.classList.toggle('open');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const toggle   = document.getElementById('navbarCategoryToggle');
        const dropdown = document.getElementById('navbarCategoryDropdown');
        if (toggle && dropdown) {
            toggle.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); const a=dropdown.classList.toggle('active'); toggle.classList.toggle('active',a); });
            document.addEventListener('click', e => { if(!toggle.contains(e.target)&&!dropdown.contains(e.target)){dropdown.classList.remove('active');toggle.classList.remove('active');} });
        }
        document.querySelectorAll('.navbar-category-group').forEach(g => {
            const m=g.querySelector('.navbar-category-main'), s=g.querySelector('.navbar-subcategory-menu');
            if(m&&s) m.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();document.querySelectorAll('.navbar-subcategory-menu.active').forEach(x=>{if(x!==s){x.classList.remove('active');x.previousElementSibling&&x.previousElementSibling.classList.remove('active');}});s.classList.toggle('active');m.classList.toggle('active');});
        });
        const mt=document.querySelector('.menu-toggle'), nv=document.querySelector('.nav');
        if(mt&&nv) mt.addEventListener('click',()=>{mt.classList.toggle('active');nv.classList.toggle('active');});
    });
    </script>
</body>
</html>
