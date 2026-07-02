<?php
/**
 * Convierte el valor de imagen guardado en la BD a una URL usable en <img src="">.
 *
 * Formatos soportados en la BD:
 *   - https://... o http://...   → proxy vía image.php?u=
 *   - src/img/archivo.jpg        → servido vía image.php?path=src/img/...
 *   - Uploads/productos/...      → servido vía image.php?path=Uploads/...
 *   - nombre.jpg (sin prefijo)   → asume Uploads/
 */
function getImageUrl(?string $imagePath): string
{
    $placeholder = 'https://via.placeholder.com/800x600?text=Sin+imagen';

    if (empty($imagePath)) return $placeholder;

    $imagePath = trim(str_replace('\\', '/', $imagePath));

    if (empty($imagePath)) return $placeholder;

    // URL externa
    if (preg_match('/^https?:\/\//i', $imagePath) || filter_var($imagePath, FILTER_VALIDATE_URL)) {
        return 'image.php?u=' . rawurlencode($imagePath);
    }

    $imgTrim = ltrim($imagePath, '/');

    // src/img/
    if (stripos($imgTrim, 'src/img/') === 0) {
        $parts = array_map('rawurlencode', explode('/', $imgTrim));
        return 'image.php?path=' . implode('/', $parts);
    }

    // Uploads/
    if (stripos($imgTrim, 'uploads/') === 0) {
        $parts = array_map('rawurlencode', explode('/', $imgTrim));
        return 'image.php?path=' . implode('/', $parts);
    }

    // Sin prefijo → asumir Uploads/
    $fullPath = 'Uploads/' . $imgTrim;
    $parts = array_map('rawurlencode', explode('/', $fullPath));
    return 'image.php?path=' . implode('/', $parts);
}
