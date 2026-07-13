<?php
/**
 * Motor de animaciones festivas para index.php
 * Incluir después de conectar $conn. Requiere $animacionActiva (string).
 */
if (!isset($animacionActiva) || $animacionActiva === 'ninguna') return;

$animaciones = [
    'navidad' => [
        'label'     => 'Navidad',
        'parts'     => ['❄','❅','❆','✦'],
        'colores'   => ['#bfdbfe','#93c5fd','#dbeafe','#eff6ff','#ffffff'],
        'dir'       => 'abajo',
        'duracion'  => 9,
        'intervalo' => 180,
        'minSize'   => 14,
        'maxSize'   => 32,
        'opacidad'  => 0.9,
    ],
    'ano_nuevo' => [
        'label'     => 'Año Nuevo',
        'parts'     => ['✦','✧','★','◆','●','▲'],
        'colores'   => ['#fbbf24','#f59e0b','#facc15','#fde68a','#ff6b6b','#4ade80','#60a5fa'],
        'dir'       => 'abajo',
        'duracion'  => 7,
        'intervalo' => 120,
        'minSize'   => 10,
        'maxSize'   => 22,
        'opacidad'  => 1,
    ],
    'reyes' => [
        'label'     => 'Día de Reyes',
        'parts'     => ['★','✦','✧','⭑','✩'],
        'colores'   => ['#fbbf24','#f59e0b','#facc15','#fde68a','#fef3c7'],
        'dir'       => 'arriba',
        'duracion'  => 8,
        'intervalo' => 220,
        'minSize'   => 16,
        'maxSize'   => 34,
        'opacidad'  => 0.95,
    ],
    'san_valentin' => [
        'label'     => 'San Valentín',
        'parts'     => ['♥','❤','♡','✿'],
        'colores'   => ['#f43f5e','#fb7185','#fda4af','#e11d48','#ff6b9d'],
        'dir'       => 'arriba',
        'duracion'  => 8,
        'intervalo' => 200,
        'minSize'   => 16,
        'maxSize'   => 36,
        'opacidad'  => 0.9,
    ],
    'dia_madre' => [
        'label'     => 'Día de la Madre',
        'parts'     => ['✿','❀','✾','꽃'],
        'colores'   => ['#f472b6','#ec4899','#f9a8d4','#fce7f3','#c026d3','#e879f9'],
        'dir'       => 'arriba',
        'duracion'  => 9,
        'intervalo' => 190,
        'minSize'   => 18,
        'maxSize'   => 36,
        'opacidad'  => 0.9,
    ],
    'dia_padre' => [
        'label'     => 'Día del Padre',
        'parts'     => ['●','◉','◎','○'],
        'colores'   => ['#3b82f6','#60a5fa','#93c5fd','#1d4ed8','#22d3ee','#34d399'],
        'dir'       => 'arriba',
        'duracion'  => 9,
        'intervalo' => 220,
        'minSize'   => 20,
        'maxSize'   => 42,
        'opacidad'  => 0.75,
    ],
    'independencia' => [
        'label'     => 'Independencia',
        'parts'     => ['▬','◼','◆','▲','●'],
        'colores'   => ['#16a34a','#ffffff','#dc2626','#15803d','#f9fafb','#b91c1c'],
        'dir'       => 'abajo',
        'duracion'  => 8,
        'intervalo' => 130,
        'minSize'   => 8,
        'maxSize'   => 20,
        'opacidad'  => 0.95,
    ],
    'dia_muertos' => [
        'label'     => 'Día de Muertos',
        'parts'     => ['✿','❀','✦','◆'],
        'colores'   => ['#f97316','#fb923c','#fdba74','#ea580c','#7c3aed','#a855f7'],
        'dir'       => 'arriba',
        'duracion'  => 9,
        'intervalo' => 200,
        'minSize'   => 16,
        'maxSize'   => 36,
        'opacidad'  => 0.9,
    ],
];

if (!isset($animaciones[$animacionActiva])) return;
$cfg = $animaciones[$animacionActiva];
$cfgJson = json_encode($cfg, JSON_UNESCAPED_UNICODE);
?>
<!-- Animación festiva: <?= htmlspecialchars($cfg['label']) ?> -->
<div id="_anim-container" style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;overflow:hidden;"></div>
<script>
(function(){
    var cfg = <?= $cfgJson ?>;
    var container = document.getElementById('_anim-container');
    if (!container) return;

    var durMs   = (cfg.duracion || 8) * 1000;
    var endTime = Date.now() + durMs;

    function rand(min, max){ return min + Math.random() * (max - min); }
    function pick(arr){ return arr[Math.floor(Math.random() * arr.length)]; }

    function createParticula() {
        if (Date.now() > endTime) return;
        var el = document.createElement('div');
        var size = rand(cfg.minSize, cfg.maxSize);
        var color = pick(cfg.colores);
        var char  = pick(cfg.parts);
        var animDur = rand(5, 10);
        var left    = rand(0, 100);
        var spin    = rand(-360, 360);
        var wobble  = rand(-30, 30);

        el.textContent = char;

        var startY = cfg.dir === 'abajo' ? '-60px' : '110vh';
        var endY   = cfg.dir === 'abajo' ? '110vh'  : '-60px';

        el.setAttribute('style',
            'position:absolute;' +
            'font-size:' + size + 'px;' +
            'color:' + color + ';' +
            'left:' + left + 'vw;' +
            'top:' + startY + ';' +
            'opacity:' + cfg.opacidad + ';' +
            'user-select:none;' +
            'will-change:transform,opacity;' +
            'animation:_anim_fall ' + animDur + 's linear forwards;'
        );

        container.appendChild(el);
        setTimeout(function(){ if(el.parentNode) el.parentNode.removeChild(el); }, animDur * 1000 + 200);
    }

    // Inyectar keyframes dinámicamente por dirección
    var styleId = '_anim_style';
    if (!document.getElementById(styleId)) {
        var s = document.createElement('style');
        s.id = styleId;
        var endYVal = cfg.dir === 'abajo' ? '110vh' : '-60px';
        s.textContent =
            '@keyframes _anim_fall{' +
            '  0%  { transform:translateX(0) rotate(0deg) scale(0.6); opacity:0; }' +
            ' 10%  { opacity:' + cfg.opacidad + '; }' +
            ' 90%  { opacity:' + (cfg.opacidad * 0.7) + '; }' +
            '100%  { transform:translateX(' + rand(-60,60) + 'px) rotate(720deg) scale(1.1);' +
            '        top:' + endYVal + '; opacity:0; }' +
            '}';
        document.head.appendChild(s);
    }

    var interval = setInterval(createParticula, cfg.intervalo);

    // Detener creación y limpiar contenedor
    setTimeout(function(){
        clearInterval(interval);
        setTimeout(function(){ if(container.parentNode) container.parentNode.removeChild(container); }, 12000);
    }, durMs);
})();
</script>
