<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('productos');

$pageTitle = 'Editar producto';
$activeId  = 'productos';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isNew = $id <= 0;

$hasPrecio = $conn->query("SHOW COLUMNS FROM producto LIKE 'precio'")->num_rows > 0;
$hasActivo = $conn->query("SHOW COLUMNS FROM producto LIKE 'activo'")->num_rows > 0;

$cats = [];
$cr = $conn->query('SELECT id, nombre FROM categoria ORDER BY nombre');
if ($cr) {
    while ($row = $cr->fetch_assoc()) {
        $cats[] = $row;
    }
}

$row = [
    'nombre' => '',
    'descripcion' => '',
    'categoria_id' => '',
    'imagen' => '',
    'stock' => 1,
    'destacado' => 0,
    'precio' => 0,
    'activo' => 1,
];

if (!$isNew) {
    $st = $conn->prepare('SELECT * FROM producto WHERE id = ?');
    $st->bind_param('i', $id);
    $st->execute();
    $p = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$p) {
        admin_redirect('productos.php');
    }
    $row = array_merge($row, $p);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $desc   = trim($_POST['descripcion'] ?? '');
    $icat   = (int) ($_POST['id_categoria'] ?: 0);
    $img    = trim($_POST['imagen'] ?? '');
    $stock  = (int) ($_POST['stock'] ?? 0);
    $dest   = isset($_POST['destacado']) ? 1 : 0;
    $precio = $hasPrecio ? (float) ($_POST['precio'] ?? 0) : 0;
    $activo = $hasActivo ? (isset($_POST['activo']) ? 1 : 0) : 1;

    if ($nombre === '') {
        $error = 'El nombre es obligatorio.';
    } else {
        if ($isNew) {
            if ($hasPrecio && $hasActivo) {
                $st = $conn->prepare(
                    'INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES (?,?,?,NULLIF(?,0),?,?,?,?)'
                );
                $st->bind_param(
                    'ssdissii',
                    $nombre,
                    $desc,
                    $precio,
                    $icat,
                    $img,
                    $stock,
                    $dest,
                    $activo
                );
            } elseif ($hasPrecio) {
                $st = $conn->prepare(
                    'INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado) VALUES (?,?,?,NULLIF(?,0),?,?,?)'
                );
                $st->bind_param(
                    'ssdissi',
                    $nombre,
                    $desc,
                    $precio,
                    $icat,
                    $img,
                    $stock,
                    $dest
                );
            } else {
                $st = $conn->prepare(
                    'INSERT INTO producto (nombre, descripcion, categoria_id, imagen, stock, destacado) VALUES (?,?,NULLIF(?,0),?,?,?)'
                );
                $st->bind_param('ssisii', $nombre, $desc, $icat, $img, $stock, $dest);
            }
            $st->execute();
            $newId = (int) $conn->insert_id;
            $st->close();
            admin_audit($conn, 'crear', 'producto', $newId, $nombre);
            admin_redirect('producto_edit.php?id=' . $newId);
        } else {
            if ($hasPrecio && $hasActivo) {
                $st = $conn->prepare(
                    'UPDATE producto SET nombre=?, descripcion=?, precio=?, categoria_id=NULLIF(?,0), imagen=?, stock=?, destacado=?, activo=? WHERE id=?'
                );
                $st->bind_param(
                    'ssdissiii',
                    $nombre,
                    $desc,
                    $precio,
                    $icat,
                    $img,
                    $stock,
                    $dest,
                    $activo,
                    $id
                );
            } elseif ($hasPrecio) {
                $st = $conn->prepare(
                    'UPDATE producto SET nombre=?, descripcion=?, precio=?, categoria_id=NULLIF(?,0), imagen=?, stock=?, destacado=? WHERE id=?'
                );
                $st->bind_param('ssdissii', $nombre, $desc, $precio, $icat, $img, $stock, $dest, $id);
            } else {
                $st = $conn->prepare(
                    'UPDATE producto SET nombre=?, descripcion=?, categoria_id=NULLIF(?,0), imagen=?, stock=?, destacado=? WHERE id=?'
                );
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
?>
<div class="page-head">
    <h1><?= $isNew ? 'Nuevo producto' : 'Editar producto #' . $id ?></h1>
    <p><a href="productos.php" style="color: var(--accent);">← Volver</a></p>
</div>
<?php if ($error): ?><div class="alert err"><?= admin_h($error) ?></div><?php endif; ?>

<form method="post" class="card">
    <div class="form-row"><label>Nombre</label><input name="nombre" required value="<?= admin_h($row['nombre']) ?>"></div>
    <div class="form-row"><label>Descripción</label><textarea name="descripcion" rows="5"><?= admin_h($row['descripcion'] ?? '') ?></textarea></div>
    <?php if ($hasPrecio): ?>
    <div class="form-row"><label>Precio</label><input type="number" step="0.01" name="precio" value="<?= admin_h((string) ($row['precio'] ?? 0)) ?>"></div>
    <?php endif; ?>
    <div class="form-row"><label>Categoría</label>
        <select name="id_categoria">
            <option value="0">—</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) ($row['categoria_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= admin_h($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-row"><label>URL o ruta de imagen</label><input name="imagen" value="<?= admin_h($row['imagen'] ?? '') ?>" placeholder="https://... o Uploads/..."></div>
    <div class="form-row"><label>Stock</label><input type="number" name="stock" value="<?= (int) ($row['stock'] ?? 0) ?>"></div>
    <div class="form-row"><label><input type="checkbox" name="destacado" value="1" <?= !empty($row['destacado']) ? 'checked' : '' ?>> Destacado</label></div>
    <?php if ($hasActivo): ?>
    <div class="form-row"><label><input type="checkbox" name="activo" value="1" <?= !empty($row['activo']) ? 'checked' : '' ?>> Activo en tienda</label></div>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary"><?= $isNew ? 'Crear' : 'Guardar' ?></button>
</form>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
