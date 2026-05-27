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
$cr = $conn->query('SELECT id, nombre, parent_id FROM categoria ORDER BY nombre');
if ($cr) {
    while ($r = $cr->fetch_assoc()) $cats[] = $r;
}
// Organizar en jerarquía: padres → hijos
$catParents  = array_filter($cats, fn($c) => empty($c['parent_id']));
$catChildren = [];
foreach ($cats as $c) {
    if (!empty($c['parent_id'])) $catChildren[(int)$c['parent_id']][] = $c;
}

$row = [
    'nombre'       => '',
    'descripcion'  => '',
    'categoria_id' => '',
    'imagen'       => '',
    'stock'        => 0,
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

    $exts    = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    $ext     = $exts[$info[2]];
    $original = pathinfo($file['name'], PATHINFO_FILENAME);
    $original = trim(preg_replace('/[^a-zA-Z0-9_\-]/', '_', $original), '_') ?: 'prod';
    $fname   = $original . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $destDir = __DIR__ . '/../src/img/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    if (!move_uploaded_file($file['tmp_name'], $destDir . $fname)) {
        return ['ok' => false, 'path' => '', 'error' => 'No se pudo mover el archivo. Verifica permisos de src/img/.'];
    }

    return ['ok' => true, 'path' => 'src/img/' . $fname, 'error' => ''];
}

// Listar imágenes disponibles en src/img/
$srcImgDir   = __DIR__ . '/../src/img/';
$srcImgFiles = [];
if (is_dir($srcImgDir)) {
    foreach (glob($srcImgDir . '*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE) as $f) {
        $srcImgFiles[] = basename($f);
    }
    sort($srcImgFiles);
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
    $modo   = $_POST['imagen_modo'] ?? 'upload'; // 'upload' | 'url' | 'srcimg'

    // Determinar valor final de imagen
    if ($modo === 'upload') {
        $upload = handleImageUpload();
        if ($upload['ok']) {
            if (!empty($row['imagen']) && str_starts_with($row['imagen'], 'Uploads/')) {
                $old = __DIR__ . '/../' . $row['imagen'];
                if (is_file($old)) @unlink($old);
            }
            $img = $upload['path'];
        } elseif ($upload['error'] !== '') {
            $error = $upload['error'];
            $img   = $row['imagen'] ?? '';
        } else {
            $img = $row['imagen'] ?? '';
        }
    } elseif ($modo === 'srcimg') {
        $sel = basename(trim($_POST['imagen_srcimg'] ?? ''));
        $img = ($sel && is_file($srcImgDir . $sel)) ? 'src/img/' . $sel : ($row['imagen'] ?? '');
    } else {
        $img = trim($_POST['imagen_url'] ?? '');
        if ($img === '' && !$isNew) $img = $row['imagen'] ?? '';
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
            if (!$st->execute()) {
                $error = 'Error al guardar: ' . $st->error;
            } else {
                $newId = (int) $conn->insert_id;
                $st->close();
                admin_audit($conn, 'crear', 'producto', $newId, $nombre);
                admin_redirect('producto_edit.php?id=' . $newId . '&saved=1&new=1');
            }
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
            admin_redirect('productos.php?saved=1');
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
<link rel="stylesheet" href="assets/cropperjs/cropper.min.css">
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
.src-img-thumb { transition: border-color .15s, transform .15s; }
.src-img-thumb:hover { border-color: #93c5fd !important; transform: scale(1.04); }
.src-img-thumb.selected { border-color: #1d4ed8 !important; box-shadow: 0 0 0 3px #bfdbfe; }

.img-current-badge {
    display:inline-flex; align-items:center; gap:6px;
    background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;
    padding:6px 12px; font-size:12px; color:#15803d; font-weight:600;
    margin-bottom:10px;
}
/* Reemplazar checkerboard de Cropper.js con fondo gris igual al catálogo */
.cropper-bg { background-image: none !important; background-color: #f1f5f9 !important; }
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
        <input name="nombre" required value="<?= admin_h($row['nombre'] ?? '') ?>">
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
            <option value="0">— Sin categoría —</option>
            <?php foreach ($catParents as $parent):
                $pid      = (int)$parent['id'];
                $children = $catChildren[$pid] ?? [];
                $selParent = (int)($row['categoria_id'] ?? 0) === $pid ? 'selected' : '';
            ?>
                <?php if ($children): ?>
                    <optgroup label="<?= admin_h($parent['nombre']) ?>">
                        <option value="<?= $pid ?>" <?= $selParent ?>>&nbsp;&nbsp;<?= admin_h($parent['nombre']) ?> (general)</option>
                        <?php foreach ($children as $ch): ?>
                            <option value="<?= (int)$ch['id'] ?>" <?= (int)($row['categoria_id'] ?? 0) === (int)$ch['id'] ? 'selected' : '' ?>>
                                &nbsp;&nbsp;<?= admin_h($ch['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php else: ?>
                    <option value="<?= $pid ?>" <?= $selParent ?>><?= admin_h($parent['nombre']) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- ── Imagen ──────────────────────────────────────────────────── -->
    <div class="form-row" style="flex-direction:column;align-items:flex-start;gap:12px;">
        <label style="margin-bottom:0;">Imagen del producto</label>

        <!-- Imagen actual -->
        <div style="display:flex;align-items:center;gap:12px;">
            <div id="imgThumb" style="width:100px;height:100px;border-radius:10px;overflow:hidden;background:#f1f5f9;border:1.5px solid #e2e8f0;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                <?php if ($previewUrl): ?>
                <img id="imgThumbImg" src="<?= htmlspecialchars($previewUrl) ?>" alt="Imagen" style="width:100%;height:100%;object-fit:cover;display:block;">
                <?php else: ?>
                <svg id="imgThumbPlaceholder" width="32" height="32" fill="none" stroke="#cbd5e1" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                <?php endif; ?>
            </div>
            <?php if ($previewUrl): ?>
            <button type="button" id="btnAjustarActual"
                onclick="initCropper('<?= htmlspecialchars($previewUrl, ENT_QUOTES) ?>')"
                style="padding:7px 14px;border:1.5px solid #e2e8f0;background:#fff;color:#374151;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Ajustar imagen actual
            </button>
            <?php endif; ?>
        </div>

        <!-- Opciones de imagen siempre visibles -->
        <div id="imgChangePanel" style="width:100%;border:1.5px solid #e2e8f0;border-radius:12px;padding:16px;background:#fafafa;">

            <!-- Subir desde computadora -->
            <p style="font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;">Subir desde computadora</p>
            <label class="drop-zone" id="dropZone">
                <input type="file" name="imagen_upload" id="imagen_upload"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       onchange="onFileSelected(this)">
                <div class="drop-zone-icon">🖼️</div>
                <div class="drop-zone-label" id="dropLabel">Arrastra aquí o haz clic para seleccionar</div>
                <div class="drop-zone-sub">JPG, PNG, GIF, WEBP · Máximo 5 MB</div>
            </label>
            <div class="img-preview-wrap" id="previewWrap">
                <img id="previewImg" src="" alt="Preview">
                <button type="button" class="img-preview-btn" onclick="clearPreview()">✕ Quitar</button>
            </div>

            <!-- Separador -->
            <div style="display:flex;align-items:center;gap:10px;margin:14px 0;">
                <div style="flex:1;height:1px;background:#e2e8f0;"></div>
                <span style="font-size:12px;color:#94a3b8;font-weight:500;">o ingresa una URL</span>
                <div style="flex:1;height:1px;background:#e2e8f0;"></div>
            </div>

            <!-- URL externa -->
            <input type="text" name="imagen_url" id="imagen_url"
                   placeholder="https://ejemplo.com/imagen.jpg"
                   value="<?= preg_match('/^https?:\/\//i', $imgActual) ? admin_h($imgActual) : '' ?>"
                   style="width:100%;"
                   oninput="previewFromUrl(this.value)">
            <div class="img-preview-wrap" id="previewUrlWrap" style="margin-top:10px;">
                <img id="previewUrlImg" src="" alt="Preview URL">
                <button type="button" class="img-preview-btn" onclick="clearUrlPreview()">✕ Quitar</button>
            </div>

            <!-- Ajustar imagen (cropper) -->
            <div id="cropperWrap" style="display:none;margin-top:14px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
                    <span style="font-size:12px;font-weight:600;color:#374151;">Ajustar imagen</span>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <label style="font-size:11px;color:#64748b;font-weight:500;">Proporción:</label>
                        <select id="cropAspect" style="font-size:12px;padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0;">
                            <option value="0">Libre (catálogo)</option>
                            <option value="1">1:1 (cuadrada)</option>
                            <option value="1.333">4:3</option>
                            <option value="1.778">16:9</option>
                        </select>
                        <button type="button" onclick="rotateCropper(-90)" style="padding:4px 8px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;cursor:pointer;font-size:12px;" title="Rotar izquierda">↺</button>
                        <button type="button" onclick="rotateCropper(90)"  style="padding:4px 8px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;cursor:pointer;font-size:12px;" title="Rotar derecha">↻</button>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 180px;gap:12px;align-items:start;">
                    <!-- Cropper -->
                    <div style="border-radius:10px;overflow:hidden;height:320px;background:#f1f5f9;">
                        <div style="position:relative;width:100%;height:100%;">
                            <img id="cropperImg" src="" alt="Crop" style="display:block;max-width:100%;max-height:100%;">
                        </div>
                    </div>
                    <!-- Preview catálogo -->
                    <div>
                        <p style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Vista en catálogo</p>
                        <!-- Replica exacta del .product-card de css/style.css -->
                        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                            <div id="cropPreview" style="width:100%;overflow:hidden;background:#f3f4f6;position:relative;min-height:80px;"></div>
                            <div style="padding:12px 14px;">
                                <div style="font-size:13px;font-weight:600;color:#1f2937;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:-0.3px;"><?= admin_h($row['nombre'] ?: 'Nombre producto') ?></div>
                                <div style="font-size:11px;color:#6b7280;margin-bottom:10px;line-height:1.6;">Descripción breve...</div>
                                <div style="display:flex;gap:6px;">
                                    <div style="flex:1;padding:7px 0;background:#1e3a8a;color:#fff;border-radius:6px;font-size:11px;font-weight:600;text-align:center;">Cotizar</div>
                                    <div style="flex:1;padding:7px 0;background:#fff;color:#1e3a8a;border:1.5px solid #1e3a8a;border-radius:6px;font-size:11px;font-weight:600;text-align:center;">Comprar</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:10px;">
                    <button type="button" id="btnAplicarCrop"
                        style="flex:1;padding:9px 0;background:#1D3D8E;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        Aplicar recorte
                    </button>
                    <button type="button" id="btnCancelarCrop"
                        style="padding:9px 16px;background:#fff;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;color:#374151;">
                        Cancelar
                    </button>
                </div>
                <p id="cropStatus" style="font-size:12px;color:#2563eb;margin-top:6px;min-height:16px;"></p>
            </div>

            <!-- Inputs ocultos necesarios para el backend -->
            <input type="hidden" name="imagen_srcimg" id="imagen_srcimg" value="<?= str_starts_with($imgActual, 'src/img/') ? admin_h(basename($imgActual)) : '' ?>">
            <!-- Biblioteca oculta (funcional pero no visible) -->
            <div id="srcImgGrid" style="display:none;">
                <?php foreach ($srcImgFiles as $fname): ?>
                <div class="src-img-thumb" onclick="selectSrcImg('<?= admin_h($fname) ?>')" title="<?= admin_h($fname) ?>"></div>
                <?php endforeach; ?>
            </div>
            <span id="srcUploadSpinner" style="display:none;"></span>
            <span id="srcUploadLabel" style="display:none;"></span>
            <div id="srcUploadError" style="display:none;"></div>
            <p id="srcImgSelected" style="display:none;"></p>
        </div>

        <!-- ── Vista en catálogo (siempre visible) ─────────────────── -->
        <div style="padding:14px;border:1.5px dashed #cbd5e1;border-radius:12px;background:#f8fafc;">
            <p style="font-size:11px;color:#94a3b8;font-weight:600;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px;">Vista en catálogo</p>
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;max-width:220px;">
                <div style="width:100%;aspect-ratio:4/3;overflow:hidden;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                    <img id="catalogPreviewImg" src="<?= htmlspecialchars($previewUrl) ?>" alt=""
                         style="width:100%;height:100%;object-fit:contain;display:block;">
                </div>
                <div style="padding:12px 14px;">
                    <div style="font-size:13px;font-weight:600;color:#1f2937;margin-bottom:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= admin_h($row['nombre'] ?: 'Nombre producto') ?></div>
                    <div style="display:flex;gap:6px;">
                        <div style="flex:1;padding:7px 0;background:#1e3a8a;color:#fff;border-radius:6px;font-size:11px;font-weight:600;text-align:center;">Cotizar</div>
                        <div style="flex:1;padding:7px 0;background:#fff;color:#1e3a8a;border:1.5px solid #1e3a8a;border-radius:6px;font-size:11px;font-weight:600;text-align:center;">Comprar</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ── /Imagen ─────────────────────────────────────────────────── -->

    <div class="form-row">
        <label>Disponibilidad</label>
        <select name="stock">
            <option value="1" <?= (int)($row['stock'] ?? 0) >= 1 ? 'selected' : '' ?>>Con existencia</option>
            <option value="0" <?= (int)($row['stock'] ?? 0) === 0 ? 'selected' : '' ?>>Sin existencia</option>
        </select>
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

    <button type="submit" class="btn btn-primary" id="btnGuardar"><?= $isNew ? 'Crear producto' : 'Guardar cambios' ?></button>
</form>

<?php if (!$isNew): ?>
<div id="saveModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:32px 28px;max-width:380px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);text-align:center;animation:modalIn .2s ease;">
        <div style="width:48px;height:48px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <svg width="22" height="22" fill="none" stroke="#1D3D8E" stroke-width="2.2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        </div>
        <h3 style="font-size:17px;font-weight:700;color:#111827;margin-bottom:8px;">¿Guardar cambios?</h3>
        <p style="font-size:13.5px;color:#6b7280;margin-bottom:24px;line-height:1.5;">Los cambios en <strong style="color:#111827;"><?= admin_h($row['nombre'] ?? 'este producto') ?></strong> se guardarán de inmediato.</p>
        <div style="display:flex;gap:10px;justify-content:center;">
            <button id="modalCancelar" style="flex:1;padding:10px 0;border:1.5px solid #e5e7eb;background:#fff;border-radius:9px;font-size:14px;font-weight:600;color:#374151;cursor:pointer;">Cancelar</button>
            <button id="modalConfirmar" style="flex:1;padding:10px 0;border:none;background:#1D3D8E;border-radius:9px;font-size:14px;font-weight:600;color:#fff;cursor:pointer;">Sí, guardar</button>
        </div>
    </div>
</div>
<style>
@keyframes modalIn { from { opacity:0; transform:scale(.95) translateY(8px); } to { opacity:1; transform:scale(1) translateY(0); } }
</style>
<?php endif; ?>

<script>
let cropper = null;

// ── Actualizar thumb al elegir nueva imagen ───────────────────────
function updateThumb(src) {
    const wrap = document.getElementById('imgThumb');
    let img = document.getElementById('imgThumbImg');
    if (!img) {
        const ph = document.getElementById('imgThumbPlaceholder');
        if (ph) ph.remove();
        img = document.createElement('img');
        img.id = 'imgThumbImg';
        img.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;';
        wrap.appendChild(img);
    }
    img.src = src;
    const catImg = document.getElementById('catalogPreviewImg');
    if (catImg) catImg.src = src;
}

// ── Modo de imagen (sin tabs, se detecta automáticamente) ────────
function switchTab(mode) {
    document.getElementById('imagen_modo').value = mode;
}

// ── Biblioteca src/img ────────────────────────────────────────────
function selectSrcImg(fname) {
    document.getElementById('imagen_srcimg').value = fname;
    document.querySelectorAll('.src-img-thumb').forEach(el => {
        el.classList.toggle('selected', el.title === fname);
    });
    const lbl = document.getElementById('srcImgSelected');
    if (lbl) lbl.textContent = 'Seleccionada: ' + fname;
    updateThumb('../image.php?path=' + encodeURIComponent('src/img/' + fname));
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
        switchTab('upload');
        document.getElementById('imagen_url').value = '';
        showPreview(input.files[0]);
    }
}

function showPreview(file) {
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewWrap').classList.add('visible');
        updateThumb(e.target.result);
        initCropper(e.target.result);
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

// ── Upload a src/img via AJAX ────────────────────────────────────
(function() {
    const fileInput = document.getElementById('srcImgFileInput');
    const srcZone   = document.getElementById('srcUploadZone');
    if (!fileInput || !srcZone) return;

    fileInput.addEventListener('change', function() {
        if (this.files[0]) uploadToSrcImg(this.files[0]);
    });

    srcZone.addEventListener('dragover',  e => { e.preventDefault(); srcZone.style.borderColor = '#93c5fd'; });
    srcZone.addEventListener('dragleave', () => { srcZone.style.borderColor = ''; });
    srcZone.addEventListener('drop', e => {
        e.preventDefault(); srcZone.style.borderColor = '';
        if (e.dataTransfer.files[0]) uploadToSrcImg(e.dataTransfer.files[0]);
    });
})();

function uploadToSrcImg(file) {
    const spinner = document.getElementById('srcUploadSpinner');
    const errEl   = document.getElementById('srcUploadError');
    const lbl     = document.getElementById('srcUploadLabel');
    spinner.style.display = 'inline'; errEl.style.display = 'none';
    lbl.textContent = 'Subiendo ' + file.name + '…';
    const fd = new FormData();
    fd.append('file', file);
    fetch('apis/upload_srcimg.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            spinner.style.display = 'none';
            lbl.textContent = 'Subir imagen nueva a la biblioteca…';
            if (data.error) { errEl.textContent = data.error; errEl.style.display = 'block'; return; }
            const grid = document.getElementById('srcImgGrid');
            const div  = document.createElement('div');
            div.className = 'src-img-thumb';
            div.title     = data.filename;
            div.onclick   = () => selectSrcImg(data.filename);
            div.style.cssText = 'border:2px solid transparent;border-radius:8px;overflow:hidden;cursor:pointer;background:#f1f5f9;aspect-ratio:1;';
            div.innerHTML = '<img src="../image.php?path=' + encodeURIComponent('src/img/' + data.filename) + '" style="width:100%;height:100%;object-fit:cover;display:block;">';
            grid.prepend(div);
            selectSrcImg(data.filename);
        })
        .catch(() => {
            spinner.style.display = 'none';
            errEl.textContent = 'Error de red al subir.';
            errEl.style.display = 'block';
            document.getElementById('srcUploadLabel').textContent = 'Subir imagen nueva a la biblioteca…';
        });
}

// ── Cropper.js ───────────────────────────────────────────────────
function updatePreviewAspect(valStr) {
    const val = parseFloat(valStr);
    const preview = document.getElementById('cropPreview');
    if (val && val > 0) {
        preview.style.aspectRatio = val.toString();
    } else {
        // Libre: usar proporciones naturales de la imagen
        const img = document.getElementById('cropperImg');
        const ratio = (img && img.naturalWidth && img.naturalHeight)
            ? img.naturalWidth / img.naturalHeight
            : 4 / 3;
        preview.style.aspectRatio = ratio.toFixed(4);
    }
    preview.style.height = '';
}

function initCropper(src) {
    const wrap = document.getElementById('cropperWrap');
    const img  = document.getElementById('cropperImg');
    if (cropper) { cropper.destroy(); cropper = null; }
    wrap.style.display = 'block';
    img.src = '';
    img.onload = () => {
        const aspectVal = parseFloat(document.getElementById('cropAspect').value);
        updatePreviewAspect(document.getElementById('cropAspect').value);
        cropper = new Cropper(img, {
            aspectRatio: aspectVal || NaN,
            viewMode: 1,
            autoCropArea: 1,
            movable: true,
            zoomable: true,
            rotatable: true,
            preview: '#cropPreview',
        });
    };
    img.onerror = () => {
        document.getElementById('cropStatus').style.color = '#dc2626';
        document.getElementById('cropStatus').textContent = 'No se pudo cargar la imagen para recortar.';
    };
    img.src = src;
}

document.getElementById('cropAspect').addEventListener('change', function() {
    if (!cropper) return;
    const val = parseFloat(this.value);
    updatePreviewAspect(this.value);
    // requestAnimationFrame para que el div actualice su tamaño antes del re-render
    requestAnimationFrame(() => cropper.setAspectRatio(val || NaN));
});

function rotateCropper(deg) { if (cropper) cropper.rotate(deg); }

document.getElementById('btnCancelarCrop').addEventListener('click', () => {
    if (cropper) { cropper.destroy(); cropper = null; }
    document.getElementById('cropperWrap').style.display = 'none';
});

document.getElementById('btnAplicarCrop').addEventListener('click', () => {
    if (!cropper) return;
    const status = document.getElementById('cropStatus');
    status.textContent = 'Subiendo recorte…';
    const canvas = cropper.getCroppedCanvas({ maxWidth: 1200, maxHeight: 1200, fillColor: '#fff' });
    canvas.toBlob(blob => {
        const fd = new FormData();
        fd.append('file', blob, 'recorte.jpg');
        fetch('apis/upload_srcimg.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.error) { status.style.color='#dc2626'; status.textContent = data.error; return; }
                document.getElementById('imagen_srcimg').value = data.filename;
                switchTab('srcimg');
                document.getElementById('imagen_upload').value = '';
                document.getElementById('imagen_url').value = '';
                updateThumb('../image.php?path=' + encodeURIComponent('src/img/' + data.filename));
                status.style.color = '#16a34a';
                status.textContent = '✓ Recorte aplicado: ' + data.filename;
                cropper.destroy(); cropper = null;
                setTimeout(() => { document.getElementById('cropperWrap').style.display = 'none'; status.textContent = ''; }, 1800);
            })
            .catch(() => { status.style.color='#dc2626'; status.textContent = 'Error de red.'; });
    }, 'image/jpeg', 0.92);
});

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
        img.onload  = () => {
            document.getElementById('previewUrlWrap').classList.add('visible');
            switchTab('url');
            document.getElementById('imagen_upload').value = '';
            updateThumb(url);
            initCropper(url);
        };
        img.onerror = () => document.getElementById('previewUrlWrap').classList.remove('visible');
        img.src = url;
    }, 600);
}

function clearUrlPreview() {
    document.getElementById('imagen_url').value = '';
    document.getElementById('previewUrlImg').src = '';
    document.getElementById('previewUrlWrap').classList.remove('visible');
}

// ── Confirm modal antes de guardar ───────────────────────────────
<?php if (!$isNew): ?>
(function() {
    const form      = document.querySelector('form.card');
    const modal     = document.getElementById('saveModal');
    const btnCancel = document.getElementById('modalCancelar');
    const btnOk     = document.getElementById('modalConfirmar');
    let confirmed   = false;

    form.addEventListener('submit', function(e) {
        if (!confirmed) {
            e.preventDefault();
            modal.style.display = 'flex';
        }
    });
    btnCancel.addEventListener('click', () => { modal.style.display = 'none'; });
    modal.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });
    btnOk.addEventListener('click', () => { confirmed = true; modal.style.display = 'none'; form.submit(); });
})();
<?php endif; ?>

// ── Init: activar la pestaña correcta según imagen guardada ───────
(function() {
    <?php if (str_starts_with($imgActual, 'src/img/')): ?>
    switchTab('srcimg');
    selectSrcImg('<?= admin_h(basename($imgActual)) ?>');
    <?php elseif (preg_match('/^https?:\/\//i', $imgActual)): ?>
    switchTab('url');
    previewFromUrl(document.getElementById('imagen_url').value.trim());
    <?php else: ?>
    switchTab('upload');
    <?php endif; ?>
})();
</script>

<script src="assets/cropperjs/cropper.min.js"></script>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
