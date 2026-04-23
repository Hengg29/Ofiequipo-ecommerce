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
            --primary:   #1D3D8E;
            --primary-l: #2B4FAF;
            --green:     #16a34a;
            --green-l:   #dcfce7;
            --orange:    #d97706;
            --orange-l:  #fef3c7;
            --blue:      #2563eb;
            --blue-l:    #eff6ff;
            --red:       #dc2626;
            --border:    #e5e7eb;
            --bg:        #f3f4f6;
            --surface:   #ffffff;
            --text:      #111827;
            --muted:     #6b7280;
            --radius:    14px;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding-bottom: 40px;
            -webkit-font-smoothing: antialiased;
        }

        /* ── HEADER ── */
        .app-header {
            background: var(--primary);
            color: white;
            padding: 0 16px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
        }
        .app-header-inner {
            max-width: 600px;
            margin: 0 auto;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .app-header-left { display: flex; align-items: center; gap: 12px; }
        .app-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: rgba(255,255,255,.2);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 15px; flex-shrink: 0;
        }
        .app-title { font-size: 15px; font-weight: 700; line-height: 1.2; }
        .app-subtitle { font-size: 11px; opacity: .65; }
        .app-logout {
            display: flex; align-items: center; gap: 5px;
            color: rgba(255,255,255,.75); text-decoration: none;
            font-size: 12px; font-weight: 500;
            padding: 6px 10px; border-radius: 8px;
            border: 1px solid rgba(255,255,255,.2);
            transition: background .15s;
        }
        .app-logout:hover { background: rgba(255,255,255,.1); color: white; }
        .app-logout svg { width: 14px; height: 14px; fill: currentColor; }

        /* ── CONTENT ── */
        .content { max-width: 600px; margin: 0 auto; padding: 16px; }

        /* ── ALERT ── */
        .alert {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px; border-radius: 10px;
            font-size: 13.5px; font-weight: 500; margin-bottom: 14px;
        }
        .alert-ok  { background: var(--green-l); color: var(--green); border: 1px solid #86efac; }
        .alert svg { width: 16px; height: 16px; fill: currentColor; flex-shrink: 0; }

        /* ── TABS ── */
        .tabs {
            display: flex; gap: 8px; margin-bottom: 16px;
            overflow-x: auto; -webkit-overflow-scrolling: touch;
            scrollbar-width: none; padding-bottom: 2px;
        }
        .tabs::-webkit-scrollbar { display: none; }
        .tab {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 20px;
            font-size: 13px; font-weight: 600; text-decoration: none;
            white-space: nowrap; transition: all .15s;
            border: 1.5px solid var(--border);
            background: var(--surface); color: var(--muted);
        }
        .tab.active { background: var(--primary); color: white; border-color: var(--primary); }
        .tab-badge {
            background: rgba(255,255,255,.25);
            border-radius: 10px; padding: 1px 7px; font-size: 11px; font-weight: 700;
        }
        .tab:not(.active) .tab-badge { background: var(--bg); color: var(--primary); }

        /* ── EMPTY ── */
        .empty {
            text-align: center; padding: 60px 20px;
            background: var(--surface); border-radius: var(--radius);
            border: 1px solid var(--border);
        }
        .empty-icon { font-size: 48px; margin-bottom: 12px; }
        .empty h3 { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
        .empty p { font-size: 13px; color: var(--muted); }

        /* ── CARD ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 14px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,.05);
        }
        .card-header {
            padding: 14px 16px;
            display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
            border-bottom: 1px solid var(--border);
        }
        .card-num {
            font-size: 15px; font-weight: 800; color: var(--primary);
        }
        .card-date { font-size: 11px; color: var(--muted); margin-top: 2px; }

        /* Estado badges */
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700; text-transform: capitalize; white-space: nowrap;
        }
        .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: .7; flex-shrink: 0; }
        .badge-pendiente    { background: var(--orange-l); color: var(--orange); }
        .badge-en_preparacion { background: var(--blue-l); color: var(--blue); }
        .badge-enviado      { background: #f5f3ff; color: #7c3aed; }
        .badge-entregado    { background: var(--green-l); color: var(--green); }

        /* Info del cliente */
        .card-client { padding: 14px 16px; border-bottom: 1px solid var(--border); }
        .client-name { font-size: 15px; font-weight: 700; margin-bottom: 8px; }
        .client-row {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--muted); margin-bottom: 4px;
        }
        .client-row:last-child { margin-bottom: 0; }
        .client-row svg { width: 14px; height: 14px; fill: var(--muted); flex-shrink: 0; }
        .client-row a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .client-row a:hover { text-decoration: underline; }

        /* Productos */
        .card-products { padding: 12px 16px; border-bottom: 1px solid var(--border); }
        .products-title {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: var(--muted); margin-bottom: 8px;
        }
        .product-row {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 13px; padding: 4px 0; border-bottom: 1px solid #f3f4f6;
        }
        .product-row:last-child { border-bottom: none; }
        .product-name { color: var(--text); font-weight: 500; flex: 1; margin-right: 8px; }
        .product-qty { color: var(--muted); font-size: 12px; white-space: nowrap; }

        /* Notas */
        .card-notes { padding: 12px 16px; border-bottom: 1px solid var(--border); background: #fafafa; }
        .notes-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin-bottom: 4px; }
        .notes-text { font-size: 13px; color: var(--text); line-height: 1.5; }

        /* Guía */
        .card-guia { padding: 10px 16px; border-bottom: 1px solid var(--border); display: flex; gap: 16px; }
        .guia-item { font-size: 12px; color: var(--muted); }
        .guia-item strong { display: block; font-size: 13px; color: var(--text); font-weight: 600; }

        /* Footer con acción */
        .card-footer {
            padding: 14px 16px;
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
        }
        .card-total { font-size: 16px; font-weight: 800; color: var(--text); }
        .card-total span { font-size: 11px; color: var(--muted); font-weight: 400; display: block; }

        .btn-entregar {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 20px;
            background: var(--green);
            color: white; border: none; border-radius: 10px;
            font-size: 14px; font-weight: 700; font-family: inherit;
            cursor: pointer; transition: background .15s, transform .1s;
            box-shadow: 0 3px 10px rgba(22,163,74,.3);
        }
        .btn-entregar:hover  { background: #15803d; }
        .btn-entregar:active { transform: scale(.97); }
        .btn-entregar svg { width: 16px; height: 16px; fill: white; }

        .btn-entregar:disabled {
            background: #d1fae5; color: #6ee7b7;
            box-shadow: none; cursor: default;
        }

        /* Entregado card (atenuada) */
        .card.entregado { opacity: .7; }

        /* Confirmación overlay */
        .confirm-overlay {
            display: none;
            position: fixed; inset: 0; background: rgba(0,0,0,.5);
            z-index: 200; align-items: flex-end; justify-content: center;
        }
        .confirm-overlay.show { display: flex; }
        .confirm-sheet {
            background: var(--surface); border-radius: 20px 20px 0 0;
            padding: 28px 24px 40px; width: 100%; max-width: 600px;
            animation: slideUp .25s ease;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        .confirm-title { font-size: 17px; font-weight: 800; margin-bottom: 6px; }
        .confirm-sub { font-size: 14px; color: var(--muted); margin-bottom: 24px; }
        .confirm-actions { display: flex; flex-direction: column; gap: 10px; }
        .btn-confirm {
            width: 100%; padding: 15px;
            background: var(--green); color: white; border: none;
            border-radius: 12px; font-size: 16px; font-weight: 700; font-family: inherit;
            cursor: pointer;
        }
        .btn-cancel {
            width: 100%; padding: 15px;
            background: var(--bg); color: var(--muted); border: none;
            border-radius: 12px; font-size: 15px; font-weight: 600; font-family: inherit;
            cursor: pointer;
        }

        /* Pull-to-refresh hint */
        .refresh-hint {
            text-align: center; font-size: 11px; color: var(--muted);
            padding: 8px 0 0; margin-bottom: -4px;
        }

        @media (min-width: 480px) {
            .card-footer { flex-direction: row; }
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

    <p class="refresh-hint">↓ Desliza hacia abajo para actualizar</p>

    <!-- Tabs -->
    <div class="tabs">
        <a href="?f=activos" class="tab <?= $filtro === 'activos' ? 'active' : '' ?>">
            Pendientes
            <span class="tab-badge"><?= $cActivos ?></span>
        </a>
        <a href="?f=entregados" class="tab <?= $filtro === 'entregados' ? 'active' : '' ?>">
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
        $estadoLabel = match($e['envio_estado']) {
            'pendiente'      => 'Pendiente',
            'en_preparacion' => 'En preparación',
            'enviado'        => 'En camino',
            'entregado'      => 'Entregado',
            default          => $e['envio_estado'],
        };
        $fecha = $e['creado_en']
            ? date('d/m/Y H:i', strtotime($e['creado_en']))
            : '—';
    ?>
    <div class="card <?= $esEntregado ? 'entregado' : '' ?>">

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
            <div class="products-title">Productos a entregar</div>
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
            <div class="notes-label">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" style="margin-right:3px;vertical-align:middle"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                Dirección / Notas
            </div>
            <div class="notes-text"><?= nl2br(admin_h($e['notas_pedido'] ?: $e['notas_internas'])) ?></div>
        </div>
        <?php endif; ?>

        <?php if ($e['guia_rastreo'] || $e['transportista'] || $e['fecha_estimada']): ?>
        <!-- Guía -->
        <div class="card-guia">
            <?php if ($e['guia_rastreo']): ?>
            <div class="guia-item">
                <span>Guía</span>
                <strong><?= admin_h($e['guia_rastreo']) ?></strong>
            </div>
            <?php endif; ?>
            <?php if ($e['transportista']): ?>
            <div class="guia-item">
                <span>Transportista</span>
                <strong><?= admin_h($e['transportista']) ?></strong>
            </div>
            <?php endif; ?>
            <?php if ($e['fecha_estimada']): ?>
            <div class="guia-item">
                <span>Entrega estimada</span>
                <strong><?= date('d/m/Y', strtotime($e['fecha_estimada'])) ?></strong>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Footer con total y botón -->
        <div class="card-footer">
            <div class="card-total">
                $<?= number_format((float)$e['total'], 2) ?>
                <span>Total del pedido</span>
            </div>

            <?php if (!$esEntregado): ?>
            <button
                class="btn-entregar"
                onclick="confirmar(<?= (int)$e['pedido_id'] ?>, '<?= admin_h($e['numero_pedido']) ?>')"
            >
                <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Entregado
            </button>
            <?php else: ?>
            <span class="badge badge-entregado" style="font-size:13px; padding:8px 14px;">
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
        <div class="confirm-title">¿Confirmar entrega?</div>
        <div class="confirm-sub" id="confirmSub">Pedido —</div>
        <form method="POST" action="repartidor.php?f=<?= admin_h($filtro) ?>" id="confirmForm">
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
