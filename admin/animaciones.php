<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('configuracion');

$pageTitle = 'Animaciones festivas';
$activeId  = 'animaciones';
$flash     = '';

// Definición de animaciones disponibles
$catalogo = [
    'ninguna' => [
        'label'  => 'Sin animación',
        'emoji'  => '🚫',
        'fechas' => 'Siempre — sin efecto',
        'desc'   => 'La página de inicio se muestra sin efectos visuales.',
        'color'  => '#64748b',
    ],
    'navidad' => [
        'label'  => 'Navidad',
        'emoji'  => '❄️',
        'fechas' => '1 – 25 de Diciembre',
        'desc'   => 'Copos de nieve cayendo en tonos azules y blancos.',
        'color'  => '#3b82f6',
    ],
    'ano_nuevo' => [
        'label'  => 'Año Nuevo',
        'emoji'  => '🎆',
        'fechas' => '26 Dic – 5 Enero',
        'desc'   => 'Confeti dorado y multicolor celebrando el año nuevo.',
        'color'  => '#f59e0b',
    ],
    'reyes' => [
        'label'  => 'Día de Reyes',
        'emoji'  => '⭐',
        'fechas' => '3 – 6 de Enero',
        'desc'   => 'Estrellas doradas que flotan hacia arriba.',
        'color'  => '#d97706',
    ],
    'san_valentin' => [
        'label'  => 'San Valentín',
        'emoji'  => '❤️',
        'fechas' => '10 – 14 de Febrero',
        'desc'   => 'Corazones rosas y rojos flotando desde la parte inferior.',
        'color'  => '#e11d48',
    ],
    'dia_madre' => [
        'label'  => 'Día de la Madre',
        'emoji'  => '🌸',
        'fechas' => '8 – 10 de Mayo',
        'desc'   => 'Flores rosas y lilas que ascienden suavemente.',
        'color'  => '#ec4899',
    ],
    'dia_padre' => [
        'label'  => 'Día del Padre',
        'emoji'  => '🎈',
        'fechas' => '3er domingo de Junio',
        'desc'   => 'Globos de colores azul y verde que se elevan.',
        'color'  => '#2563eb',
    ],
    'independencia' => [
        'label'  => 'Independencia de México',
        'emoji'  => '🇲🇽',
        'fechas' => '13 – 16 de Septiembre',
        'desc'   => 'Confeti tricolor (verde, blanco, rojo) que cae.',
        'color'  => '#16a34a',
    ],
    'dia_muertos' => [
        'label'  => 'Día de Muertos',
        'emoji'  => '🌼',
        'fechas' => '28 Oct – 2 Noviembre',
        'desc'   => 'Flores de cempasúchil naranja y toques morados que suben.',
        'color'  => '#f97316',
    ],
];

// Leer configuración actual
$animActual = 'ninguna';
if (admin_table_exists($conn, 'admin_config')) {
    $r = $conn->query("SELECT valor FROM admin_config WHERE clave = 'animacion_festiva' LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        $animActual = $row['valor'];
    }
}

// Guardar selección
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva = $_POST['animacion'] ?? 'ninguna';
    if (!array_key_exists($nueva, $catalogo)) $nueva = 'ninguna';

    if (admin_table_exists($conn, 'admin_config')) {
        $st = $conn->prepare(
            "INSERT INTO admin_config (clave, valor, grupo) VALUES ('animacion_festiva',?,'animaciones')
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );
        $st->bind_param('s', $nueva);
        $st->execute();
        $st->close();
        admin_audit($conn, 'config', 'admin_config', null, "animacion_festiva → $nueva");
        $animActual = $nueva;
        $flash = '✓ Animación actualizada: ' . htmlspecialchars($catalogo[$nueva]['label']);
    }
}

require __DIR__ . '/includes/layout.php';
?>

<style>
.anim-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
    margin-top: 24px;
}
.anim-card {
    background: var(--surface);
    border: 2px solid var(--border);
    border-radius: 16px;
    padding: 22px 20px 18px;
    cursor: pointer;
    transition: border-color .2s, box-shadow .2s, transform .15s;
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: relative;
}
.anim-card:hover { border-color: var(--primary); box-shadow: 0 4px 16px rgba(29,61,142,.12); transform: translateY(-2px); }
.anim-card.activa { border-color: var(--primary); background: var(--primary-pale); }
.anim-card .badge-activa {
    position: absolute; top: 12px; right: 12px;
    background: var(--primary); color: #fff;
    font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px;
    letter-spacing: .04em; text-transform: uppercase;
}
.anim-emoji { font-size: 36px; line-height: 1; }
.anim-nombre { font-size: 15px; font-weight: 700; color: var(--text); }
.anim-fechas { font-size: 12px; font-weight: 600; color: var(--muted); }
.anim-desc { font-size: 12px; color: var(--text-secondary); line-height: 1.5; flex: 1; }
.anim-btn {
    margin-top: 4px;
    padding: 8px 16px;
    border: none; border-radius: 8px;
    font-size: 13px; font-weight: 600;
    cursor: pointer; transition: opacity .15s, background .15s;
    width: 100%;
}
.anim-card.activa .anim-btn { background: var(--primary); color: #fff; }
.anim-card:not(.activa) .anim-btn { background: var(--surface2); color: var(--text); }
.anim-card:not(.activa) .anim-btn:hover { background: var(--primary); color: #fff; }
</style>

<div class="page-head">
    <h1>🎉 Animaciones festivas</h1>
    <p class="page-subtitle">Activa un efecto visual en la página de inicio de la tienda para fechas especiales.</p>
</div>

<?php if ($flash): ?>
    <div class="alert ok" style="margin-bottom:16px;"><?= $flash ?></div>
<?php endif; ?>

<div class="card" style="padding:20px 24px;">
    <p style="font-size:14px;color:var(--text-secondary);margin:0;">
        La animación activa aparece en <strong>index.php</strong> durante ~10 segundos al entrar a la tienda.
        Selecciona <em>Sin animación</em> para desactivarla en cualquier momento.
    </p>
</div>

<form method="POST" id="animForm">
<div class="anim-grid">
<?php foreach ($catalogo as $key => $anim):
    $esActiva = ($key === $animActual);
?>
    <div class="anim-card <?= $esActiva ? 'activa' : '' ?>">
        <?php if ($esActiva): ?>
            <span class="badge-activa">Activa</span>
        <?php endif; ?>
        <div class="anim-emoji"><?= $anim['emoji'] ?></div>
        <div class="anim-nombre"><?= htmlspecialchars($anim['label']) ?></div>
        <div class="anim-fechas">📅 <?= htmlspecialchars($anim['fechas']) ?></div>
        <div class="anim-desc"><?= htmlspecialchars($anim['desc']) ?></div>
        <button type="submit" name="animacion" value="<?= $key ?>" class="anim-btn">
            <?= $esActiva ? '✓ Activa ahora' : 'Activar' ?>
        </button>
    </div>
<?php endforeach; ?>
</div>
</form>

<?php require __DIR__ . '/includes/layout_end.php'; ?>
