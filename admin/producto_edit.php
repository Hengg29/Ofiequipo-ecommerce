<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('productos');

$pageTitle = 'Editar producto';
$activeId  = 'productos';

$id    = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isNew = $id <= 0;

$hasPrecio = $conn->query("SHOW COLUMNS FROM producto LIKE 'precio'")->num_rows > 0;
$hasActivo = $conn->query("SHOW COLUMNS FROM producto LIKE 'activo'")->num_rows > 0;

$cats = [];
$cr = $conn->query('SELECT id, nombre FROM categoria ORDER BY nombre');
if ($cr) {
    while ($r = $cr->fetch_assoc()) $cats[] = $r;
}

$row = [
    'nombre'       => '',
    'descripcion'  => '',
    'categoria_id' => '',
    'imagen'       => '',
    'stock'        => 1,
    'destacado'    => 0,
    'precio'       => 0,
    'activo'       => 1,
];

if (!$isNew) {
    $st = $conn->prepare('SELECT * FROM producto WHERE id = ?');
    $st->bind_param('i', $id);
    $st->execute();
    $p = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$p) admin_redirect('productos.php');
    $row = array_merge($row, $p);
}

// ─── Subida de imagen ─────────────────────────────────────────────────────────
function handleImageUpload(): array // ['ok'=>bool, 'path'=>string, 'error'=>string]
{
    $file = $_FILES['imagen_upload'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'path' => '', 'error' => ''];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msgs = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
            UPLOAD_ERR_PARTIAL    => 'La subida fue interrumpida.',
            UPLOAD_ERR_NO_TMP_DIR => 'No hay directorio temporal.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir en disco.',
        ];
        return ['ok' => false, 'path' => '', 'error' => $msgs[$file['error']] ?? 'Error al subir el archivo.'];
    }

    // Validar que sea imagen real con getimagesize
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'path' => '', 'error' => 'El archivo no es una imagen válida.'];
    }

    $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
    if (!in_array($info[2], $allowed, true)) {
        return ['ok' => false, 'path' => '', 'error' => 'Solo se permiten imágenes JPG, PNG, GIF o WEBP.'];
    }

    $maxBytes = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'path' => '', 'error' => 'La imagen no puede superar 5 MB.'];
    }

    $exts  = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    $ext   = $exts[$info[2]];
    $fname = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest  = __DIR__ . '/../Uploads/productos/' . $fname;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'path' => '', 'error' => 'No se pudo mover el archivo. Verifica permisos de Uploads/productos/.'];
    }

    return ['ok' => true, 'path' => 'Uploads/productos/' . $fname, 'error' => ''];
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nombre = trim($_POST['nombre'] ?? '');
    $desc   = trim($_POST['descripcion'] ?? '');
    $icat   = (int) ($_POST['id_categoria'] ?: 0);
    $stock  = (int) ($_POST['stock'] ?? 0);
    $dest   = isset($_POST['destacado']) ? 1 : 0;
    $precio = $hasPrecio ? (float) ($_POST['precio'] ?? 0) : 0;
    $activo = $hasActivo ? (isset($_POST['activo']) ? 1 : 0) : 1;
    $modo   = $_POST['imagen_modo'] ?? 'url'; // 'upload' | 'url'

    // Determinar valor final de imagen
    if ($modo === 'upload') {
        $upload = handleImageUpload();
        if ($upload['ok']) {
            // Borrar imagen anterior si era local
            if (!empty($row['imagen']) && str_starts_with($row['imagen'], 'Uploads/')) {
                $old = __DIR__ . '/../' . $row['imagen'];
                if (is_file($old)) @unlink($old);
            }
            $img = $upload['path'];
        } elseif ($upload['error'] !== '') {
            $error = $upload['error'];
            $img   = $row['imagen']; // mantener la anterior
        } else {
            $img = $row['imagen']; // no se subió nada, mantener
        }
    } else {
        $img = trim($_POST['imagen_url'] ?? '');
        // Si dejaron vacío y había imagen anterior, mantenerla
        if ($img === '' && !$isNew) $img = $row['imagen'];
    }

    if ($nombre === '') {
        $error = 'El nombre es obligatorio.';
    }

    if (!$error) {
        if ($isNew) {
            if ($hasPrecio && $hasActivo) {
                $st = $conn->prepare('INSERT INTO producto (nombre,descripcion,precio,categoria_id,imagen,stock,destacado,activo) VALUES (?,?,?,NULLIF(?,0),?,?,?,?)');
                $st->bind_param('ssdissii', $nombre, $desc, $precio, $icat, $img, $stock, $dest, $activo);
            } elseif ($hasPrecio) {
                $st = $conn->prepare('INSERT INTO producto (nombre,descripcion,precio,categoria_id,imagen,stock,destacado) VALUES (?,?,?,NULLIF(?,0),?,?,?)');
                $st->bind_param('ssdissi', $nombre, $desc, $precio, $icat, $img, $stock, $dest);
            } else {
                $st = $conn->prepare('INSERT INTO producto (nombre,descripcion,categoria_id,imagen,stock,destacado) VALUES (?,?,NULLIF(?,0),?,?,?)');
                $st->bind_param('ssisii', $nombre, $desc, $icat, $img, $stock, $dest);
            }
            $st->execute();
            $newId = (int) $conn->insert_id;
            $st->close();
            admin_audit($conn, 'crear', 'producto', $newId, $nombre);
            admin_redirect('producto_edit.php?id=' . $newId);
        } else {
            if ($hasPrecio && $hasActivo) {
                $st = $conn->prepare('UPDATE producto SET nombre=?,descripcion=?,precio=?,categoria_id=NULLIF(?,0),imagen=?,stock=?,destacado=?,activo=? WHERE id=?');
                $st->bind_param('ssdissiii', $nombre, $desc, $precio, $icat, $img, $stock, $dest, $activo, $id);
            } elseif ($hasPrecio) {
                $st = $conn->prepare('UPDATE producto SET nombre=?,descripcion=?,precio=?,categoria_id=NULLIF(?,0),imagen=?,stock=?,destacado=? WHERE id=?');
                $st->bind_param('ssdissii', $nombre, $desc, $precio, $icat, $img, $stock, $dest, $id);
            } else {
                $st = $conn->prepare('UPDATE producto SET nombre=?,descripcion=?,categoria_id=NULLIF(?,0),imagen=?,stock=?,destacado=? WHERE id=?');
                $st->bind_param('ssisiii', $nombre, $desc, $icat, $img, $stock, $dest, $id);
            }
            $st->execute();
            $st->close();
            admin_audit($conn, 'editar', 'producto', $id, $nombre);
            admin_redirect('productos.php');
        }
    }
}

require __DIR__ . '/includes/layout.php';

// URL de preview de imagen actual
$appUrl     = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
$imgActual  = $row['imagen'] ?? '';
$previewUrl = '';
if ($imgActual !== '') {
    $previewUrl = preg_match('/^https?:\/\//i', $imgActual)
        ? $imgActual
        : $appUrl . '/' . ltrim($imgActual, '/');
}
?>

<style>
.img-tabs { display:flex; gap:0; margin-bottom:0; border-bottom:2px solid var(--border,#e5e7eb); }
.img-tab  {
    padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;
    border:none; background:none; color:var(--muted,#6b7280);
    border-bottom:2px solid transparent; margin-bottom:-2px;
    transition:color .15s, border-color .15s;
}
.img-tab.active { color:var(--accent,#1D3D8E); border-bottom-color:var(--accent,#1D3D8E); }
.img-panel { display:none; padding:14px 0 0; }
.img-panel.active { display:block; }

.drop-zone {
    border:2px dashed var(--border,#d1d5db); border-radius:12px;
    padding:32px 20px; text-align:center; cursor:pointer;
    transition:border-color .2s, background .2s;
    background:#fafafa; display:block;
    /* como es <label>, max-width:none para no heredar del layout */
    max-width:none !important;
}
.drop-zone.over { border-color:var(--accent,#1D3D8E); background:#eff6ff; }
/* Ocultar el input real — el <label> reenvía el click automáticamente */
.drop-zone input[type=file] {
    display:none !important;
}
.drop-zone-icon { font-size:32px; margin-bottom:8px; }
.drop-zone-label { font-size:13.5px; color:var(--text,#111827); font-weight:500; }
.drop-zone-sub   { font-size:12px; color:var(--muted,#6b7280); margin-top:4px; }

.img-preview-wrap {
    margin-top:14px; display:none;
    border:1px solid var(--border,#e5e7eb); border-radius:10px; overflow:hidden;
    position:relative; background:#f3f4f6;
}
.img-preview-wrap.visible { display:block; }
.img-preview-wrap img { display:block; max-height:220px; margin:0 auto; object-fit:contain; padding:8px; }
.img-preview-btn {
    position:absolute; top:8px; right:8px;
    background:rgba(220,38,38,.85); color:white; border:none;
    border-radius:6px; font-size:11px; font-weight:700;
    padding:4px 10px; cursor:pointer;
}
.img-current-badge {
    display:inline-flex; align-items:center; gap:6px;
    background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;
    padding:6px 12px; font-size:12px; color:#15803d; font-weight:600;
    margin-bottom:10px;
}
</style>

<div class="page-head">
    <h1><?= $isNew ? 'Nuevo producto' : 'Editar producto #' . $id ?></h1>
    <p><a href="productos.php" style="color:var(--accent);">← Volver</a></p>
</div>
<?php if ($error): ?><div class="alert err"><?= admin_h($error) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card">
    <?= csrf_field() ?>
    <input type="hidden" name="imagen_modo" id="imagen_modo" value="url">

    <div class="form-row">
        <label>Nombre</label>
        <input name="nombre" required value="<?= admin_h($row['nombre']) ?>">
    </div>

    <div class="form-row">
        <label>Descripción</label>
        <textarea name="descripcion" rows="4"><?= admin_h($row['descripcion'] ?? '') ?></textarea>
    </div>

    <?php if ($hasPrecio): ?>
    <div class="form-row">
        <label>Precio</label>
        <input type="number" step="0.01" min="0" name="precio" value="<?= admin_h((string)($row['precio'] ?? 0)) ?>">
    </div>
    <?php endif; ?>

    <div class="form-row">
        <label>Categoría</label>
        <select name="id_categoria">
            <option value="0">—</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)($row['categoria_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= admin_h($c['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- ── Imagen ──────────────────────────────────────────────────── -->
    <div class="form-row" style="flex-direction:column;align-items:flex-start;gap:10px;">
        <label style="margin-bottom:0;">Imagen del producto</label>

        <?php if ($previewUrl): ?>
        <div class="img-current-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
            Imagen actual guardada
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div style="width:100%;">
            <div class="img-tabs">
                <button type="button" class="img-tab active" onclick="switchTab('upload')">
                    ↑ Subir archivo
                </button>
                <button type="button" class="img-tab" onclick="switchTab('url')">
                    🔗 URL externa
                </button>
            </div>

            <!-- Panel: Subir archivo -->
            <div class="img-panel active" id="panel-upload">
                <label class="drop-zone" id="dropZone">
                    <input type="file" name="imagen_upload" id="imagen_upload"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           onchange="onFileSelected(this)">
                    <div class="drop-zone-icon">🖼️</div>
                    <div class="drop-zone-label" id="dropLabel">Arrastra la imagen aquí o haz clic para seleccionar</div>
                    <div class="drop-zone-sub">JPG, PNG, GIF, WEBP · Máximo 5 MB</div>
                </label>
                <div class="img-preview-wrap <?= $previewUrl ? 'visible' : '' ?>" id="previewWrap">
                    <img id="previewImg" src="<?= htmlspecialchars($previewUrl) ?>" alt="Preview">
                    <button type="button" class="img-preview-btn" onclick="clearPreview()">✕ Quitar</button>
                </div>
            </div>

            <!-- Panel: URL -->
            <div class="img-panel" id="panel-url">
                <input type="text" name="imagen_url" id="imagen_url"
                       placeholder="https://ejemplo.com/imagen.jpg"
                       value="<?= preg_match('/^https?:\/\//i', $imgActual) ? admin_h($imgActual) : '' ?>"
                       style="width:100%;margin-top:4px;"
                       oninput="previewFromUrl(this.value)">
                <div class="img-preview-wrap" id="previewUrlWrap" style="margin-top:10px;">
                    <img id="previewUrlImg" src="" alt="Preview URL">
                    <button type="button" class="img-preview-btn" onclick="clearUrlPreview()">✕ Quitar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- ── /Imagen ─────────────────────────────────────────────────── -->

    <div class="form-row">
        <label>Stock</label>
        <input type="number" min="0" name="stock" value="<?= (int)($row['stock'] ?? 0) ?>">
    </div>

    <div class="form-row">
        <label>
            <input type="checkbox" name="destacado" value="1" <?= !empty($row['destacado']) ? 'checked' : '' ?>>
            Destacado en tienda
        </label>
    </div>

    <?php if ($hasActivo): ?>
    <div class="form-row">
        <label>
            <input type="checkbox" name="activo" value="1" <?= !empty($row['activo']) ? 'checked' : '' ?>>
            Activo en tienda
        </label>
    </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary"><?= $isNew ? 'Crear producto' : 'Guardar cambios' ?></button>
</form>

<script>
// ── Tabs ──────────────────────────────────────────────────────────
function switchTab(mode) {
    document.getElementById('imagen_modo').value = mode;

    document.querySelectorAll('.img-tab').forEach((t, i) => {
        t.classList.toggle('active', (mode === 'upload') ? i === 0 : i === 1);
    });
    document.querySelectorAll('.img-panel').forEach((p, i) => {
        p.classList.toggle('active', (mode === 'upload') ? i === 0 : i === 1);
    });
}

// ── Drop zone ─────────────────────────────────────────────────────
const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('over');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('imagen_upload').files = dt.files;
        showPreview(file);
    }
});

function onFileSelected(input) {
    if (input.files && input.files[0]) {
        showPreview(input.files[0]);
    }
}

function showPreview(file) {
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewWrap').classList.add('visible');
    };
    reader.readAsDataURL(file);
    document.getElementById('dropLabel').textContent = file.name;
}

function clearPreview() {
    document.getElementById('imagen_upload').value = '';
    document.getElementById('previewImg').src = '';
    document.getElementById('previewWrap').classList.remove('visible');
    document.getElementById('dropLabel').textContent = 'Arrastra la imagen aquí o haz clic para seleccionar';
}

// ── URL preview ───────────────────────────────────────────────────
let urlTimer;
function previewFromUrl(url) {
    clearTimeout(urlTimer);
    if (!url.match(/^https?:\/\//i)) {
        document.getElementById('previewUrlWrap').classList.remove('visible');
        return;
    }
    urlTimer = setTimeout(() => {
        const img = document.getElementById('previewUrlImg');
        img.onload  = () => document.getElementById('previewUrlWrap').classList.add('visible');
        img.onerror = () => document.getElementById('previewUrlWrap').classList.remove('visible');
        img.src = url;
    }, 600);
}

function clearUrlPreview() {
    document.getElementById('imagen_url').value = '';
    document.getElementById('previewUrlImg').src = '';
    document.getElementById('previewUrlWrap').classList.remove('visible');
}

// ── Init: si ya hay URL externa, activar ese tab ──────────────────
(function() {
    const urlVal = document.getElementById('imagen_url').value.trim();
    if (urlVal) {
        switchTab('url');
        previewFromUrl(urlVal);
    }
    <?php if ($previewUrl && !preg_match('/^https?:\/\//i', $imgActual)): ?>
    // Imagen guardada es local → activar tab upload con preview
    switchTab('upload');
    <?php endif; ?>
})();
</script>

<?php require __DIR__ . '/includes/layout_end.php'; ?>
