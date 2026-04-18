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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= admin_h($pageTitle) ?> — Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f1419;
            --surface: #1a2332;
            --surface2: #243044;
            --border: #2d3a4f;
            --text: #e8edf4;
            --muted: #8b9cb3;
            --accent: #3b82f6;
            --accent2: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --sidebar-w: 260px;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DM Sans', system-ui, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar {
            width: var(--sidebar-w); flex-shrink: 0; background: var(--surface);
            border-right: 1px solid var(--border); padding: 20px 0; position: sticky; top: 0; height: 100vh; overflow-y: auto;
        }
        .sidebar-brand { padding: 0 20px 20px; font-weight: 700; font-size: 1.05rem; color: var(--text); border-bottom: 1px solid var(--border); margin-bottom: 12px; }
        .sidebar-brand span { display: block; font-size: 0.75rem; font-weight: 500; color: var(--muted); margin-top: 4px; }
        nav a {
            display: block; padding: 10px 20px; color: var(--muted); text-decoration: none; font-size: 0.9rem;
            border-left: 3px solid transparent; transition: background .15s, color .15s;
        }
        nav a:hover { background: var(--surface2); color: var(--text); }
        nav a.active { background: rgba(59,130,246,.12); color: var(--accent); border-left-color: var(--accent); font-weight: 600; }
        .sidebar-user { margin-top: auto; padding: 16px 20px; border-top: 1px solid var(--border); font-size: 0.8rem; color: var(--muted); }
        .sidebar-user strong { color: var(--text); display: block; margin-bottom: 8px; }
        .sidebar-user a { color: var(--accent); }
        .main { flex: 1; padding: 28px 32px 48px; overflow-x: auto; }
        .page-head { margin-bottom: 28px; }
        .page-head h1 { margin: 0 0 8px; font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; }
        .page-head p { margin: 0; color: var(--muted); font-size: 0.9rem; }
        .card {
            background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 20px;
        }
        .card h2 { margin: 0 0 16px; font-size: 1rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
        .grid { display: grid; gap: 16px; }
        .grid.kpi { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
        .kpi-box {
            background: linear-gradient(145deg, var(--surface2), var(--surface)); border: 1px solid var(--border);
            border-radius: 12px; padding: 16px 18px;
        }
        .kpi-box .lbl { font-size: 0.75rem; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
        .kpi-box .val { font-size: 1.5rem; font-weight: 700; margin-top: 8px; }
        .kpi-box .sub { font-size: 0.8rem; color: var(--muted); margin-top: 6px; }
        table.data { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        table.data th, table.data td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border); }
        table.data th { color: var(--muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; }
        table.data tr:hover td { background: rgba(255,255,255,.02); }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 600; text-transform: uppercase; }
        .badge.pendiente { background: rgba(245,158,11,.2); color: #fbbf24; }
        .badge.en_preparacion { background: rgba(59,130,246,.2); color: #93c5fd; }
        .badge.enviado { background: rgba(139,92,246,.2); color: #c4b5fd; }
        .badge.entregado { background: rgba(34,197,94,.2); color: #86efac; }
        .badge.cancelado { background: rgba(239,68,68,.2); color: #fca5a5; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; border: none;
            font-size: 0.875rem; font-weight: 600; cursor: pointer; text-decoration: none; font-family: inherit;
        }
        .btn-primary { background: var(--accent); color: white; }
        .btn-primary:hover { filter: brightness(1.08); }
        .btn-ghost { background: transparent; color: var(--text); border: 1px solid var(--border); }
        .btn-ghost:hover { background: var(--surface2); }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }
        .form-row { margin-bottom: 14px; }
        .form-row label { display: block; font-size: 0.8rem; color: var(--muted); margin-bottom: 6px; }
        .form-row input, .form-row select, .form-row textarea {
            width: 100%; max-width: 420px; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border);
            background: var(--bg); color: var(--text); font-family: inherit;
        }
        .form-row textarea { min-height: 100px; max-width: 100%; }
        .filters { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; margin-bottom: 20px; }
        .filters .form-row { margin: 0; }
        .muted { color: var(--muted); font-size: 0.85rem; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
        .alert.ok { background: rgba(34,197,94,.15); border: 1px solid rgba(34,197,94,.35); color: #86efac; }
        .alert.err { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.35); color: #fca5a5; }
        .chart-wrap { height: 280px; position: relative; }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand"><?= admin_h($brand) ?><span>Panel administrativo</span></div>
        <nav>
            <?php foreach (admin_menu_items() as $item):
                if (!admin_can($item['module'])) {
                    continue;
                }
                $isActive = ($item['id'] === $activeId);
            ?>
                <a class="<?= $isActive ? 'active' : '' ?>" href="<?= admin_h($item['href']) ?>"><?= admin_h($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-user">
            <strong><?= admin_h($_SESSION['admin_nombre'] ?? '') ?></strong>
            <?= admin_h($_SESSION['admin_rol_nombre'] ?? '') ?><br>
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </aside>
    <main class="main">
