<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

// Auth: debe estar logueado como repartidor
if (empty($_SESSION['admin_user_id'])) {
    admin_redirect('login.php');
}
if (($_SESSION['admin_rol_slug'] ?? '') !== 'repartidor') {
    // Admins/vendedores que naveguen aquí → redirigir al panel
    admin_redirect('index.php');
}

// Insertar rol si aún no existe (primera vez)
$conn->query("INSERT IGNORE INTO admin_roles (id, slug, nombre) VALUES (4, 'repartidor', 'Repartidor')");

$nombre     = $_SESSION['admin_nombre'] ?? 'Repartidor';
$filtro     = trim($_GET['f'] ?? 'activos');
$msg        = '';
$msgType    = 'ok';

// ── Acción: marcar como entregado ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entregar'])) {
    csrf_verify();
    $pid = (int)($_POST['pedido_id'] ?? 0);
    if ($pid > 0) {
        $estado = 'entregado';

        $upE = $conn->prepare("UPDATE admin_envios SET estado = ? WHERE pedido_id = ?");
        $upE->bind_param('si', $estado, $pid);
        $upE->execute();
        $upE->close();

        $upP = $conn->prepare("UPDATE admin_pedidos SET estado = ? WHERE id = ?");
        $upP->bind_param('si', $estado, $pid);
        $upP->execute();
        $upP->close();

        admin_audit($conn, 'entregar', 'envio', $pid, 'marcado entregado por repartidor');
        $msg = 'Pedido marcado como entregado.';
    }
}

// ── Consulta de envíos ─────────────────────────────────────────────────────
$whereEstado = $filtro === 'entregados'
    ? "e.estado = 'entregado'"
    : "e.estado IN ('pendiente','en_preparacion','enviado')";

$envios = [];
$res = $conn->query("
    SELECT e.id AS envio_id, e.pedido_id, e.estado AS envio_estado,
           e.guia_rastreo, e.transportista, e.fecha_estimada, e.notas_internas,
           e.actualizado_en,
           p.numero_pedido, p.nombre_contacto, p.telefono_contacto,
           p.email_contacto, p.total, p.notas AS notas_pedido, p.creado_en
    FROM admin_envios e
    INNER JOIN admin_pedidos p ON p.id = e.pedido_id
    WHERE $whereEstado
    ORDER BY
        FIELD(e.estado,'enviado','en_preparacion','pendiente','entregado'),
        p.creado_en ASC
    LIMIT 200
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        // Productos del pedido
        $stD = $conn->prepare("
            SELECT nombre_producto, cantidad, precio_unitario
            FROM admin_detalle_pedido WHERE pedido_id = ?
        ");
        $stD->bind_param('i', $row['pedido_id']);
        $stD->execute();
        $row['productos'] = $stD->get_result()->fetch_all(MYSQLI_ASSOC);
        $stD->close();
        $envios[] = $row;
    }
}

// Conteos para badges
$cActivos = $cEntregados = 0;
$rC = $conn->query("SELECT COUNT(*) AS c FROM admin_envios WHERE estado IN ('pendiente','en_preparacion','enviado')");
if ($rC) $cActivos = (int)$rC->fetch_assoc()['c'];
$rE = $conn->query("SELECT COUNT(*) AS c FROM admin_envios WHERE estado = 'entregado'");
if ($rE) $cEntregados = (int)$rE->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#1D3D8E">
    <title>Mis Envíos — Ofiequipo</title>
    <link rel="icon" href="../icono_logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:    #1D3D8E;
            --primary-l:  #2B4FAF;
            --green:      #16a34a;
            --green-l:    #dcfce7;
            --green-b:    #86efac;
            --orange:     #d97706;
            --orange-l:   #fef3c7;
            --orange-b:   #fcd34d;
            --blue:       #2563eb;
            --blue-l:     #eff6ff;
            --purple:     #7c3aed;
            --purple-l:   #f5f3ff;
            --border:     #e5e7eb;
            --border-l:   #f3f4f6;
            --bg:         #f0f2f5;
            --surface:    #ffffff;
            --text:       #111827;
            --text-2:     #374151;
            --muted:      #6b7280;
            --radius:     16px;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding-bottom: 48px;
            -webkit-font-smoothing: antialiased;
        }

        /* ── HEADER ── */
        .app-header {
            background: linear-gradient(135deg, #152d6e 0%, var(--primary) 60%, var(--primary-l) 100%);
            color: white;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,.25);
        }
        .app-header-inner {
            max-width: 620px;
            margin: 0 auto;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .app-header-left { display: flex; align-items: center; gap: 12px; }
        .app-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: rgba(255,255,255,.18);
            border: 2px solid rgba(255,255,255,.3);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 16px; flex-shrink: 0;
            letter-spacing: -.5px;
        }
        .app-title { font-size: 15px; font-weight: 700; line-height: 1.25; }
        .app-subtitle {
            font-size: 11px; opacity: .6;
            display: flex; align-items: center; gap: 4px; margin-top: 1px;
        }
        .app-subtitle::before {
            content: '';
            display: inline-block; width: 6px; height: 6px;
            border-radius: 50%; background: #4ade80;
        }
        .app-logout {
            display: flex; align-items: center; gap: 5px;
            color: rgba(255,255,255,.8); text-decoration: none;
            font-size: 12px; font-weight: 600;
            padding: 7px 12px; border-radius: 9px;
            border: 1.5px solid rgba(255,255,255,.22);
            transition: background .15s, color .15s;
            white-space: nowrap;
        }
        .app-logout:hover { background: rgba(255,255,255,.12); color: white; }
        .app-logout svg { width: 13px; height: 13px; fill: currentColor; }

        /* ── CONTENT ── */
        .content { max-width: 620px; margin: 0 auto; padding: 20px 16px; }

        /* ── ALERT ── */
        .alert {
            display: flex; align-items: center; gap: 10px;
            padding: 13px 16px; border-radius: 12px;
            font-size: 13.5px; font-weight: 500; margin-bottom: 16px;
        }
        .alert-ok { background: var(--green-l); color: var(--green); border: 1px solid var(--green-b); }
        .alert svg { width: 18px; height: 18px; fill: currentColor; flex-shrink: 0; }

        /* ── REFRESH HINT ── */
        .refresh-hint {
            text-align: center; font-size: 11px; color: var(--muted);
            margin-bottom: 14px; letter-spacing: .02em;
            display: flex; align-items: center; justify-content: center; gap: 5px;
        }
        .refresh-hint::before, .refresh-hint::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        /* ── TABS ── */
        .tabs {
            display: flex; gap: 8px; margin-bottom: 20px;
            overflow-x: auto; -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .tabs::-webkit-scrollbar { display: none; }
        .tab {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px; border-radius: 22px;
            font-size: 13px; font-weight: 600; text-decoration: none;
            white-space: nowrap; transition: all .18s;
            border: 1.5px solid var(--border);
            background: var(--surface); color: var(--muted);
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .tab.active {
            background: var(--primary); color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(29,61,142,.3);
        }
        .tab-badge {
            min-width: 20px; height: 20px;
            border-radius: 10px; padding: 0 6px;
            font-size: 11px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,.22); color: white;
        }
        .tab:not(.active) .tab-badge {
            background: var(--bg); color: var(--primary);
        }

        /* ── EMPTY ── */
        .empty {
            text-align: center; padding: 64px 20px;
            background: var(--surface); border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: 0 1px 4px rgba(0,0,0,.05);
        }
        .empty-icon { font-size: 52px; margin-bottom: 14px; }
        .empty h3 { font-size: 17px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
        .empty p { font-size: 13px; color: var(--muted); line-height: 1.5; }

        /* ── CARD ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            transition: box-shadow .2s;
        }
        .card:not(.entregado):hover {
            box-shadow: 0 4px 16px rgba(0,0,0,.1);
        }
        /* Borde lateral de color según estado */
        .card { border-left: 4px solid var(--border); }
        .card.estado-pendiente      { border-left-color: var(--orange); }
        .card.estado-en_preparacion { border-left-color: var(--blue); }
        .card.estado-enviado        { border-left-color: var(--purple); }
        .card.estado-entregado      { border-left-color: var(--green); }

        /* Encabezado */
        .card-header {
            padding: 14px 18px 13px;
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            background: #fafbfc;
            border-bottom: 1px solid var(--border-l);
        }
        .card-num {
            font-size: 13px; font-weight: 700; color: var(--primary);
            font-family: 'SF Mono', 'Fira Code', monospace;
            background: var(--blue-l); padding: 3px 9px;
            border-radius: 6px; letter-spacing: .02em;
        }
        .card-date { font-size: 11.5px; color: var(--muted); margin-top: 4px; }

        /* Badges de estado */
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 11px; border-radius: 20px;
            font-size: 11px; font-weight: 700; white-space: nowrap;
        }
        .badge::before {
            content: ''; width: 6px; height: 6px; border-radius: 50%;
            background: currentColor; flex-shrink: 0;
        }
        .badge-pendiente      { background: var(--orange-l); color: var(--orange); }
        .badge-en_preparacion { background: var(--blue-l);   color: var(--blue); }
        .badge-enviado        { background: var(--purple-l); color: var(--purple); }
        .badge-entregado      { background: var(--green-l);  color: var(--green); }

        /* Cliente */
        .card-client {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-l);
        }
        .client-name {
            font-size: 16px; font-weight: 700; color: var(--text);
            margin-bottom: 9px; display: flex; align-items: center; gap: 8px;
        }
        .client-name::before {
            content: '';
            display: inline-block; width: 30px; height: 30px;
            background: linear-gradient(135deg, var(--primary), var(--primary-l));
            border-radius: 50%;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z'/%3E%3C/svg%3E");
            background-size: 18px; background-repeat: no-repeat; background-position: center;
            flex-shrink: 0;
        }
        .client-row {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--muted); margin-bottom: 5px;
            padding-left: 2px;
        }
        .client-row:last-child { margin-bottom: 0; }
        .client-row svg { width: 14px; height: 14px; fill: var(--muted); flex-shrink: 0; }
        .client-row a {
            color: var(--primary); text-decoration: none; font-weight: 600;
            transition: color .15s;
        }
        .client-row a:hover { color: var(--primary-l); text-decoration: underline; }

        /* Productos */
        .card-products {
            padding: 13px 18px;
            border-bottom: 1px solid var(--border-l);
            background: #fafbfc;
        }
        .section-label {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; color: var(--muted);
            margin-bottom: 10px;
            display: flex; align-items: center; gap: 6px;
        }
        .section-label svg { width: 12px; height: 12px; fill: var(--muted); }
        .product-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px 12px; border-radius: 9px; margin-bottom: 4px;
            background: white; border: 1px solid var(--border-l);
        }
        .product-row:last-child { margin-bottom: 0; }
        .product-name { color: var(--text-2); font-size: 13.5px; font-weight: 500; flex: 1; }
        .product-qty {
            background: var(--bg); color: var(--primary);
            font-size: 11px; font-weight: 700;
            padding: 2px 8px; border-radius: 10px; white-space: nowrap;
        }

        /* Notas */
        .card-notes {
            padding: 12px 18px;
            border-bottom: 1px solid var(--border-l);
            background: #fffbeb;
        }
        .notes-text { font-size: 13px; color: var(--text-2); line-height: 1.55; }

        /* Guía de rastreo */
        .card-guia {
            padding: 11px 18px;
            border-bottom: 1px solid var(--border-l);
            display: flex; gap: 20px; flex-wrap: wrap;
            background: #f8faff;
        }
        .guia-item { font-size: 11px; color: var(--muted); }
        .guia-item strong {
            display: block; font-size: 13px; color: var(--text); font-weight: 600; margin-top: 2px;
        }

        /* Footer */
        .card-footer {
            padding: 14px 18px;
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
        }
        .card-total-wrap { display: flex; align-items: baseline; gap: 6px; }
        .card-total {
            font-size: 22px; font-weight: 800; color: var(--text);
            letter-spacing: -.5px;
        }
        .card-total-label { font-size: 11px; color: var(--muted); font-weight: 500; }

        .btn-entregar {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 22px;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            color: white; border: none; border-radius: 12px;
            font-size: 14px; font-weight: 700; font-family: inherit;
            cursor: pointer; transition: opacity .15s, transform .1s, box-shadow .15s;
            box-shadow: 0 4px 14px rgba(22,163,74,.35);
            white-space: nowrap;
        }
        .btn-entregar:hover  { opacity: .9; box-shadow: 0 6px 18px rgba(22,163,74,.45); }
        .btn-entregar:active { transform: scale(.97); }
        .btn-entregar svg    { width: 16px; height: 16px; fill: white; }

        /* Card entregada */
        .card.entregado { opacity: .65; }

        /* ── CONFIRM OVERLAY ── */
        .confirm-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.45); backdrop-filter: blur(3px);
            z-index: 200; align-items: flex-end; justify-content: center;
        }
        .confirm-overlay.show { display: flex; }
        .confirm-sheet {
            background: var(--surface); border-radius: 24px 24px 0 0;
            padding: 8px 24px 44px; width: 100%; max-width: 620px;
            animation: slideUp .22s cubic-bezier(.32,1,.4,1);
        }
        .confirm-handle {
            width: 40px; height: 4px; background: var(--border);
            border-radius: 2px; margin: 12px auto 24px;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        .confirm-title { font-size: 18px; font-weight: 800; margin-bottom: 5px; }
        .confirm-sub   { font-size: 14px; color: var(--muted); margin-bottom: 28px; }
        .confirm-actions { display: flex; flex-direction: column; gap: 10px; }
        .btn-confirm {
            width: 100%; padding: 16px;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            color: white; border: none; border-radius: 14px;
            font-size: 16px; font-weight: 700; font-family: inherit;
            cursor: pointer; box-shadow: 0 4px 14px rgba(22,163,74,.3);
        }
        .btn-cancel {
            width: 100%; padding: 15px;
            background: var(--bg); color: var(--muted); border: none;
            border-radius: 14px; font-size: 15px; font-weight: 600; font-family: inherit;
            cursor: pointer; transition: background .15s;
        }
        .btn-cancel:hover { background: var(--border); }

        @media (max-width: 480px) {
            .card-total { font-size: 19px; }
            .btn-entregar { padding: 11px 16px; font-size: 13px; }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="app-header">
    <div class="app-header-inner">
        <div class="app-header-left">
            <div class="app-avatar"><?= strtoupper(mb_substr($nombre, 0, 1)) ?></div>
            <div>
                <div class="app-title"><?= admin_h($nombre) ?></div>
                <div class="app-subtitle">Repartidor · Ofiequipo</div>
            </div>
        </div>
        <a href="logout.php" class="app-logout">
            <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
            Salir
        </a>
    </div>
</header>

<div class="content">

    <?php if ($msg): ?>
    <div class="alert alert-ok">
        <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        <?= admin_h($msg) ?>
    </div>
    <?php endif; ?>

    <div class="refresh-hint">Actualiza la página para ver cambios</div>

    <!-- Tabs -->
    <div class="tabs">
        <a href="?f=activos" class="tab <?= $filtro === 'activos' ? 'active' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
            Pendientes
            <span class="tab-badge"><?= $cActivos ?></span>
        </a>
        <a href="?f=entregados" class="tab <?= $filtro === 'entregados' ? 'active' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            Entregados
            <span class="tab-badge"><?= $cEntregados ?></span>
        </a>
    </div>

    <?php if (empty($envios)): ?>
    <div class="empty">
        <div class="empty-icon"><?= $filtro === 'entregados' ? '📦' : '🎉' ?></div>
        <h3><?= $filtro === 'entregados' ? 'Sin entregados aún' : '¡Todo al día!' ?></h3>
        <p><?= $filtro === 'entregados' ? 'Aquí aparecerán los pedidos que marques como entregados.' : 'No hay envíos pendientes en este momento.' ?></p>
    </div>

    <?php else: foreach ($envios as $e):
        $esEntregado = $e['envio_estado'] === 'entregado';
        $badgeClass  = 'badge-' . str_replace(' ', '_', $e['envio_estado']);
        $cardClass   = 'estado-' . $e['envio_estado'];
        $estadoLabel = match($e['envio_estado']) {
            'pendiente'      => 'Pendiente',
            'en_preparacion' => 'En preparación',
            'enviado'        => 'En camino',
            'entregado'      => 'Entregado',
            default          => $e['envio_estado'],
        };
        $fecha = $e['creado_en']
            ? date('d/m/Y · H:i', strtotime($e['creado_en']))
            : '—';
    ?>
    <div class="card <?= $esEntregado ? 'entregado' : '' ?> <?= $cardClass ?>">

        <!-- Encabezado -->
        <div class="card-header">
            <div>
                <div class="card-num"><?= admin_h($e['numero_pedido']) ?></div>
                <div class="card-date"><?= $fecha ?></div>
            </div>
            <span class="badge <?= $badgeClass ?>"><?= $estadoLabel ?></span>
        </div>

        <!-- Cliente -->
        <div class="card-client">
            <div class="client-name"><?= admin_h($e['nombre_contacto'] ?: 'Sin nombre') ?></div>
            <?php if ($e['telefono_contacto']): ?>
            <div class="client-row">
                <svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                <a href="tel:<?= admin_h($e['telefono_contacto']) ?>"><?= admin_h($e['telefono_contacto']) ?></a>
            </div>
            <?php endif; ?>
            <?php if ($e['email_contacto']): ?>
            <div class="client-row">
                <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                <?= admin_h($e['email_contacto']) ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($e['productos'])): ?>
        <!-- Productos -->
        <div class="card-products">
            <div class="section-label">
                <svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.36C18 2.06 15.94 0 13.36 0c-1.3 0-2.43.52-3.25 1.36L9 2.5l-1.11-1.14C7.07.52 5.94 0 4.64 0 2.06 0 0 2.06 0 4.64c0 .48.1.92.18 1.36H0v2h20V6zm-9.36 0c-.42-.7-.64-1.5-.64-2.36 0-.97.4-1.86 1.04-2.5.64-.64 1.53-1.04 2.5-1.04C15.06 0.1 16.9 1.94 16.9 4.64c0 .85-.22 1.66-.64 2.36H10.64zM0 8v12h9V8H0zm11 12h9V8h-9v12z"/></svg>
                Productos a entregar
            </div>
            <?php foreach ($e['productos'] as $prod): ?>
            <div class="product-row">
                <span class="product-name"><?= admin_h($prod['nombre_producto']) ?></span>
                <span class="product-qty">×<?= (int)$prod['cantidad'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($e['notas_pedido']) || !empty($e['notas_internas'])): ?>
        <!-- Notas / dirección -->
        <div class="card-notes">
            <div class="section-label">
                <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                Dirección / Notas
            </div>
            <div class="notes-text"><?= nl2br(admin_h($e['notas_pedido'] ?: $e['notas_internas'])) ?></div>
        </div>
        <?php endif; ?>

        <?php if ($e['guia_rastreo'] || $e['transportista'] || $e['fecha_estimada']): ?>
        <!-- Guía -->
        <div class="card-guia">
            <?php if ($e['guia_rastreo']): ?>
            <div class="guia-item">Guía<strong><?= admin_h($e['guia_rastreo']) ?></strong></div>
            <?php endif; ?>
            <?php if ($e['transportista']): ?>
            <div class="guia-item">Transportista<strong><?= admin_h($e['transportista']) ?></strong></div>
            <?php endif; ?>
            <?php if ($e['fecha_estimada']): ?>
            <div class="guia-item">Entrega estimada<strong><?= date('d/m/Y', strtotime($e['fecha_estimada'])) ?></strong></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Footer con total y botón -->
        <div class="card-footer">
            <div>
                <div class="card-total-wrap">
                    <span class="card-total">$<?= number_format((float)$e['total'], 2) ?></span>
                    <span class="card-total-label">MXN</span>
                </div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px;">Total del pedido</div>
            </div>

            <?php if (!$esEntregado): ?>
            <button
                class="btn-entregar"
                onclick="confirmar(<?= (int)$e['pedido_id'] ?>, '<?= admin_h($e['numero_pedido']) ?>')"
            >
                <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Marcar entregado
            </button>
            <?php else: ?>
            <span class="badge badge-entregado" style="font-size:12px;padding:8px 14px;">
                ✓ Entregado
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>

</div><!-- /content -->

<!-- Confirmación bottom sheet -->
<div class="confirm-overlay" id="confirmOverlay" onclick="cancelar(event)">
    <div class="confirm-sheet" onclick="event.stopPropagation()">
        <div class="confirm-handle"></div>
        <div class="confirm-title">¿Confirmar entrega?</div>
        <div class="confirm-sub" id="confirmSub">Pedido —</div>
        <form method="POST" action="repartidor.php?f=<?= admin_h($filtro) ?>" id="confirmForm">
            <?= csrf_field() ?>
            <input type="hidden" name="entregar" value="1">
            <input type="hidden" name="pedido_id" id="confirmPedidoId" value="">
            <div class="confirm-actions">
                <button type="submit" class="btn-confirm">Sí, marcar como entregado</button>
                <button type="button" class="btn-cancel" onclick="cancelar()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmar(pedidoId, numPedido) {
    document.getElementById('confirmPedidoId').value = pedidoId;
    document.getElementById('confirmSub').textContent = 'Pedido ' + numPedido;
    document.getElementById('confirmOverlay').classList.add('show');
}
function cancelar(e) {
    if (!e || e.target === document.getElementById('confirmOverlay')) {
        document.getElementById('confirmOverlay').classList.remove('show');
    }
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') cancelar();
});
</script>
</body>
</html>
