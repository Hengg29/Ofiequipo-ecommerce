<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $activeId */
/** @var mysqli $conn */

$brand = 'Ofiequipo Admin';
$cfgRes = @$conn->query("SELECT valor FROM admin_config WHERE clave='tienda_nombre' LIMIT 1");
if ($cfgRes && $row = $cfgRes->fetch_assoc()) {
    $brand = $row['valor'] . ' · Admin';
}

/* Iconos SVG para el sidebar */
$sidebarIcons = [
    'dashboard'     => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
    'ventas'        => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 100 7h5a3.5 3.5 0 110 7H6"/></svg>',
    'envios'        => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
    'analisis'      => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>',
    'clientes'      => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
    'usuarios'      => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'productos'     => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/></svg>',
    'inventario'    => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4M4 7l8 4M4 7v10l8 4m0-10v10"/></svg>',
    'promociones'   => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
    'reportes'      => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
    'configuracion' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
    'auditoria'     => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= admin_h($pageTitle) ?> — Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ============================================= */
        /*  OFIEQUIPO ADMIN — LIGHT PREMIUM THEME        */
        /* ============================================= */
        :root {
            --primary: #1D3D8E;
            --primary-light: #2B4FAF;
            --primary-pale: rgba(29,61,142,.08);
            --primary-pale2: rgba(29,61,142,.15);
            --secondary: #6C7699;
            --tertiary: #722E00;
            --neutral: #F5F7FA;
            --bg: #F5F7FA;
            --surface: #FFFFFF;
            --surface2: #F0F2F7;
            --border: #E3E7EF;
            --border-light: #EEF1F6;
            --text: #1A1D26;
            --text-secondary: #4A5068;
            --muted: #8792AB;
            --accent: #1D3D8E;
            --accent2: #22C55E;
            --warning: #F59E0B;
            --danger: #EF4444;
            --sidebar-w: 240px;
            --topbar-h: 64px;
            --radius: 16px;
            --radius-sm: 8px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.04), 0 1px 2px rgba(0,0,0,.03);
            --shadow-md: 0 4px 16px rgba(0,0,0,.06);
            --shadow-lg: 0 8px 30px rgba(0,0,0,.08);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* ---- LAYOUT ---- */
        .layout { display: flex; min-height: 100vh; }

        /* ---- SIDEBAR ---- */
        .sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            background: linear-gradient(175deg, #0f172a 0%, #1e3a8a 55%, #1d4ed8 100%);
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            z-index: 50;
        }

        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-brand-icon {
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-brand-name {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            font-size: 13px;
            color: white;
            letter-spacing: 1px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .sidebar-brand span {
            display: block;
            font-size: 9px;
            font-weight: 500;
            color: rgba(255,255,255,0.45);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        nav { padding: 10px 12px; flex: 1; }
        nav a {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 9px 12px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border-radius: 9px;
            transition: all .15s ease;
            margin: 2px 0;
        }
        nav a svg { flex-shrink: 0; transition: opacity .15s; }
        nav a:hover {
            background: rgba(255,255,255,0.10);
            color: white;
        }
        nav a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            font-weight: 600;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.12);
        }

        .sidebar-bottom {
            border-top: 1px solid rgba(255,255,255,0.08);
            padding: 14px 12px;
        }
        .sidebar-create-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px 16px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
            margin-bottom: 14px;
        }
        .sidebar-create-btn:hover { background: rgba(255,255,255,0.22); }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-user-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.25);
            display: flex; align-items: center; justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        .sidebar-user-info { flex: 1; min-width: 0; }
        .sidebar-user-name {
            font-weight: 600;
            font-size: 13px;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-user-role {
            font-size: 11px;
            color: rgba(255,255,255,0.45);
        }
        .sidebar-user-role a { color: rgba(255,255,255,0.7); text-decoration: none; font-weight: 500; }
        .sidebar-user-role a:hover { color: white; }

        /* ---- MAIN AREA ---- */
        .main {
            flex: 1;
            margin-left: var(--sidebar-w);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ---- TOP BAR ---- */
        .topbar {
            height: var(--topbar-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .topbar-left { display: flex; align-items: center; gap: 8px; }
        .breadcrumb {
            font-size: 13px;
            color: var(--muted);
        }
        .breadcrumb-current {
            color: var(--text);
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--neutral);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 14px;
            min-width: 220px;
            transition: border-color .2s;
        }
        .search-box:focus-within { border-color: var(--primary); }
        .search-box svg { color: var(--muted); flex-shrink: 0; }
        .search-box input {
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 13px;
            color: var(--text);
            outline: none;
            width: 100%;
        }
        .search-box input::placeholder { color: var(--muted); }

        .topbar-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            cursor: pointer;
            transition: background .15s;
            background: transparent;
            border: none;
        }
        .topbar-icon:hover { background: var(--neutral); }

        /* ---- CONTENT ---- */
        .content-area {
            flex: 1;
            padding: 24px 28px 48px;
        }

        /* ---- PAGE HEAD ---- */
        .page-head { margin-bottom: 24px; }
        .page-head h1 {
            font-family: 'Manrope', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }
        .page-head p {
            color: var(--muted);
            font-size: 14px;
            margin: 0;
        }
        .page-head p a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .page-head p a:hover { text-decoration: underline; }

        /* ---- CARDS ---- */
        .card {
            background: var(--surface);
            border: 1px solid var(--border-light);
            border-radius: var(--radius);
            padding: 20px 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }
        .card h2 {
            font-family: 'Manrope', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            margin: 0 0 16px;
        }
        .card h3 {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        /* ---- GRID ---- */
        .grid { display: grid; gap: 16px; }
        .grid.kpi { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }

        /* ---- KPI BOXES ---- */
        .kpi-box {
            background: var(--surface);
            border: 1px solid var(--border-light);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            position: relative;
        }
        .kpi-box .kpi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .kpi-box .kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-pale);
            color: var(--primary);
        }
        .kpi-box .kpi-badge {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .kpi-badge-live { background: rgba(34,197,94,.12); color: #16a34a; }
        .kpi-badge-daily { background: rgba(29,61,142,.1); color: var(--primary); }
        .kpi-badge-target { background: var(--neutral); color: var(--muted); }

        .kpi-box .lbl {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .kpi-box .val {
            font-family: 'Manrope', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            margin-top: 4px;
            line-height: 1.1;
        }
        .kpi-box .sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 6px;
        }

        /* ---- TABLES ---- */
        table.data { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.data th, table.data td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }
        table.data th {
            color: var(--muted);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .05em;
            background: var(--neutral);
        }
        table.data tr:last-child td { border-bottom: none; }
        table.data tr:hover td { background: rgba(29,61,142,.02); }
        table.data td { color: var(--text-secondary); }

        /* ---- BADGES ---- */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .badge.pendiente       { background: rgba(245,158,11,.12); color: #b45309; }
        .badge.pendiente::before { background: #f59e0b; }
        .badge.en_preparacion  { background: rgba(249,115,22,.12); color: #c2410c; }
        .badge.en_preparacion::before { background: #f97316; }
        .badge.en_proceso      { background: rgba(59,130,246,.12); color: #1d4ed8; }
        .badge.en_proceso::before { background: #3b82f6; }
        .badge.enviado         { background: rgba(139,92,246,.12); color: #6d28d9; }
        .badge.enviado::before { background: #8b5cf6; }
        .badge.en_camino       { background: rgba(139,92,246,.12); color: #6d28d9; }
        .badge.en_camino::before { background: #8b5cf6; }
        .badge.entregado       { background: rgba(34,197,94,.12); color: #15803d; }
        .badge.entregado::before { background: #22c55e; }
        .badge.completado      { background: rgba(34,197,94,.12); color: #15803d; }
        .badge.completado::before { background: #22c55e; }
        .badge.cancelado       { background: rgba(239,68,68,.12); color: #b91c1c; }
        .badge.cancelado::before { background: #ef4444; }

        /* Badge label override via data attribute */
        [data-estado-label]::after { content: attr(data-estado-label); }

        /* ---- BUTTONS ---- */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
            transition: all .15s ease;
            line-height: 1.4;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-primary:hover {
            background: var(--primary-light);
            box-shadow: 0 4px 12px rgba(29,61,142,.25);
        }
        .btn-ghost {
            background: var(--surface);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover {
            background: var(--neutral);
            border-color: var(--primary);
            color: var(--primary);
        }
        .btn-sm { padding: 6px 14px; font-size: 12px; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: #dc2626; }

        /* ---- FORMS ---- */
        .form-row { margin-bottom: 16px; }
        .form-row label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .form-row input,
        .form-row select,
        .form-row textarea {
            width: 100%;
            max-width: 420px;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            font-family: inherit;
            font-size: 13.5px;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-row input:focus,
        .form-row select:focus,
        .form-row textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(29,61,142,.1);
        }
        .form-row textarea { min-height: 100px; max-width: 100%; resize: vertical; }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 20px;
        }
        .filters .form-row { margin: 0; }

        /* ---- ALERTS ---- */
        .alert { padding: 12px 18px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 13px; }
        .alert.ok { background: rgba(34,197,94,.08); border: 1px solid rgba(34,197,94,.2); color: #16a34a; }
        .alert.err { background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2); color: #dc2626; }

        /* ---- UTILS ---- */
        .muted { color: var(--muted); font-size: 13px; }
        .chart-wrap { height: 280px; position: relative; }

        /* ---- QUICK ACCESS ---- */
        .quick-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .quick-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 20px 12px;
            border-radius: var(--radius);
            text-decoration: none;
            transition: all .15s;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }
        .quick-card-primary {
            background: var(--primary);
            color: #fff;
        }
        .quick-card-primary:hover { background: var(--primary-light); }
        .quick-card-light {
            background: var(--neutral);
            color: var(--text-secondary);
            border: 1px solid var(--border-light);
        }
        .quick-card-light:hover { background: var(--border-light); }
        .quick-card svg { opacity: .85; }

        /* ---- TOTAL SALES BADGE ---- */
        .total-sales-badge {
            background: var(--surface);
            border: 1px solid var(--border-light);
            border-radius: var(--radius);
            padding: 12px 20px;
            text-align: right;
            box-shadow: var(--shadow-sm);
        }
        .total-sales-badge .ts-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }
        .total-sales-badge .ts-val {
            font-family: 'Manrope', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text);
        }

        /* ---- PRODUCT LIST ITEM ---- */
        .product-list-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
        }
        .product-list-item:last-child { border-bottom: none; }
        .product-list-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: var(--neutral);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .product-list-info { flex: 1; min-width: 0; }
        .product-list-name { font-weight: 600; font-size: 13px; color: var(--text); }
        .product-list-sub { font-size: 11px; color: var(--muted); }
        .product-list-sales {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            white-space: nowrap;
        }

        /* ---- SECTION HEADER ---- */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .section-header h2 {
            font-family: 'Manrope', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }
        .section-header a {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: .05em;
            transition: color .15s;
        }
        .section-header a:hover { color: var(--primary); }

        /* ---- RESPONSIVE ---- */
        @media (max-width: 960px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .grid.kpi { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .content-area { padding: 16px; }
            .topbar { padding: 0 16px; }
            .search-box { min-width: 160px; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
            </div>
            <div>
                <div class="sidebar-brand-name">OFIEQUIPO<span>Panel Admin</span></div>
            </div>
        </div>
        <nav>
            <?php foreach (admin_menu_items() as $item):
                if (!admin_can($item['module'])) {
                    continue;
                }
                $isActive = ($item['id'] === $activeId);
                $icon = $sidebarIcons[$item['id']] ?? '';
            ?>
                <a class="<?= $isActive ? 'active' : '' ?>" href="<?= admin_h($item['href']) ?>">
                    <?= $icon ?>
                    <?= admin_h($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <?php if (admin_can('ventas')): ?>
                <a class="sidebar-create-btn" href="ventas_nuevo.php">+ Crear Pedido</a>
            <?php endif; ?>
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?= strtoupper(mb_substr($_SESSION['admin_nombre'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= admin_h($_SESSION['admin_nombre'] ?? 'Admin') ?></div>
                    <div class="sidebar-user-role">
                        <?= admin_h($_SESSION['admin_rol_nombre'] ?? '') ?> · <a href="logout.php">Salir</a>
                    </div>
                </div>
            </div>
        </div>
    </aside>
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <span class="breadcrumb">Páginas / <span class="breadcrumb-current"><?= admin_h($pageTitle) ?></span></span>
            </div>
            <div class="topbar-right">
                <div class="search-box" style="position:relative;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" id="adminSearchInput" placeholder="Buscar módulos..." autocomplete="off">
                    <div id="adminSearchResults" style="display:none; position:absolute; top:calc(100% + 6px); left:0; right:0; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); box-shadow:var(--shadow-md); z-index:100; max-height:280px; overflow-y:auto;"></div>
                </div>
                <button class="topbar-icon" title="Notificaciones">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                </button>
                <button class="topbar-icon" title="Ayuda">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </button>
                <button class="topbar-icon" title="Perfil">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </button>
            </div>
        </header>
        <div class="content-area">
