<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
admin_require_login();
admin_require_module('cotizaciones');

$pageTitle = 'Detalle de cotización';
$activeId  = 'cotizaciones';

$id = (int)($_GET['id'] ?? 0);
if (!$id) admin_redirect('cotizaciones.php');

// ── Cargar cotización ────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM cotizaciones WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$cot = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$cot) admin_redirect('cotizaciones.php');

// ── Cargar items ─────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM cotizacion_items WHERE cotizacion_id = ? ORDER BY id");
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Procesar acciones POST ───────────────────────────────────
require_once dirname(__DIR__) . '/includes/mailer.php';

$flash = '';
$flashType = 'ok';

// ── Detección de zona de envío ───────────────────────────────
function detectarZona(string $municipio): array {
    $m = mb_strtolower(trim($municipio));
    $gratis  = ['tampico', 'ciudad madero', 'madero', 'altamira'];
    $costo5k = ['pueblo viejo', 'panuco', 'pánuco'];
    foreach ($gratis  as $c) { if (str_contains($m, $c)) return ['tipo' => 'gratis',  'label' => 'Zona metropolitana', 'info' => 'Envío siempre gratis',        'color' => '#16a34a', 'bg' => '#dcfce7', 'border' => '#86efac']; }
    foreach ($costo5k as $c) { if (str_contains($m, $c)) return ['tipo' => 'costo5k', 'label' => 'Zona extendida',     'info' => 'Gratis a partir de $5,000', 'color' => '#b45309', 'bg' => '#fef3c7', 'border' => '#fcd34d']; }
    return ['tipo' => 'otro', 'label' => 'Fuera de zona', 'info' => 'Verificar costo manualmente', 'color' => '#6b7280', 'bg' => '#f3f4f6', 'border' => '#d1d5db'];
}
$zona = detectarZona($cot['dir_municipio'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Guardar tipo de envío
    if ($accion === 'set_envio') {
        $tipoEnvio   = $_POST['envio_tipo'] ?? '';
        $costoEnvio  = max(0, (float)($_POST['envio_costo'] ?? 0));
        $validTipos  = ['gratis', 'con_costo'];
        if (in_array($tipoEnvio, $validTipos, true)) {
            $upd = $conn->prepare("UPDATE cotizaciones SET envio_tipo = ?, envio_costo = ? WHERE id = ?");
            $upd->bind_param('sdi', $tipoEnvio, $costoEnvio, $id);
            $upd->execute();
            $upd->close();
            $cot['envio_tipo']  = $tipoEnvio;
            $cot['envio_costo'] = $costoEnvio;
            admin_audit($conn, 'actualizar', 'cotizacion', $id, "envio → $tipoEnvio $$costoEnvio");
            $flash = 'Tipo de envío guardado.';
        }
    }

    // Cambiar estado
    if ($accion === 'estado') {
        $validStatuses = ['pendiente', 'en_proceso', 'cotizada', 'rechazada'];
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, $validStatuses, true)) {
            $upd = $conn->prepare("UPDATE cotizaciones SET status = ? WHERE id = ?");
            $upd->bind_param('si', $newStatus, $id);
            $upd->execute();
            $upd->close();
            admin_audit($conn, 'actualizar', 'cotizacion', $id, "status → $newStatus");
            $cot['status'] = $newStatus;
            $flash = 'Estado actualizado correctamente.';
        }
    }

    // Enviar respuesta con cotización
    if ($accion === 'responder') {
        $mensaje  = trim($_POST['mensaje'] ?? '');
        $newStatus = $_POST['status_al_enviar'] ?? 'cotizada';
        $validStatuses = ['pendiente', 'en_proceso', 'cotizada', 'rechazada'];
        if (!in_array($newStatus, $validStatuses, true)) $newStatus = 'cotizada';

        // Recoger precios editados por item
        $preciosEditados = $_POST['precio_item'] ?? []; // [item_id => precio]
        $notasItem       = $_POST['nota_item']   ?? []; // [item_id => nota]

        // Construir tabla HTML del email con IVA
        $itemsHtml  = '';
        $subtotalSinIva = 0;
        foreach ($items as $item) {
            $iid    = (int)$item['id'];
            $nProd  = htmlspecialchars($item['nombre_producto'] ?? '');
            $qty    = (int)$item['cantidad'];
            $precio = max(0, (float)($preciosEditados[$iid] ?? 0));
            $sub    = $precio * $qty;
            $nota   = htmlspecialchars($notasItem[$iid] ?? '');
            $subtotalSinIva += $sub;
            $precioStr = $precio > 0 ? '$' . number_format($precio, 2) : '<em style="color:#94a3b8;">Por confirmar</em>';
            $subStr    = $precio > 0 ? '$' . number_format($sub, 2)    : '—';
            $notaRow   = $nota ? "<div style='font-size:12px;color:#64748b;margin-top:3px;'>$nota</div>" : '';
            $itemsHtml .= "
            <tr>
              <td style='padding:12px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;line-height:1.4;'>{$nProd}{$notaRow}</td>
              <td style='padding:12px 0;border-bottom:1px solid #f1f5f9;color:#475569;text-align:center;width:50px;font-weight:600;font-size:14px;'>{$qty}</td>
              <td style='padding:12px 0;border-bottom:1px solid #f1f5f9;color:#475569;text-align:right;width:100px;font-size:14px;'>{$precioStr}</td>
              <td style='padding:12px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;text-align:right;width:100px;font-size:14px;font-weight:700;'>{$subStr}</td>
            </tr>";
        }

        // IVA y envío
        $ivaAmt     = round($subtotalSinIva * 0.16, 2);
        $tipoEnvioPost  = $cot['envio_tipo']  ?? 'por_definir';
        $costoEnvio     = (float)($cot['envio_costo'] ?? 0);
        $envioAmt   = ($tipoEnvioPost === 'gratis') ? 0 : $costoEnvio;
        $totalFinal = $subtotalSinIva + $ivaAmt + $envioAmt;

        $ivaSep = $subtotalSinIva > 0 ? "
            <tr><td colspan='3' style='padding:10px 0 4px;font-size:12px;color:#64748b;text-align:right;'>Subtotal sin IVA</td><td style='padding:10px 0 4px;font-size:13px;color:#475569;text-align:right;font-weight:600;'>\$" . number_format($subtotalSinIva, 2) . "</td></tr>
            <tr><td colspan='3' style='padding:4px 0;font-size:12px;color:#64748b;text-align:right;'>IVA 16%</td><td style='padding:4px 0;font-size:13px;color:#475569;text-align:right;'>\$" . number_format($ivaAmt, 2) . "</td></tr>" : '';
        $envioRow = ($tipoEnvioPost === 'gratis')
            ? "<tr><td colspan='3' style='padding:4px 0;font-size:12px;color:#16a34a;text-align:right;font-weight:600;'>Envío</td><td style='padding:4px 0;font-size:13px;color:#16a34a;text-align:right;font-weight:700;'>GRATIS</td></tr>"
            : ($costoEnvio > 0 ? "<tr><td colspan='3' style='padding:4px 0;font-size:12px;color:#64748b;text-align:right;'>Envío</td><td style='padding:4px 0;font-size:13px;color:#475569;text-align:right;'>\$" . number_format($costoEnvio, 2) . "</td></tr>" : '');
        $totalStr   = $totalFinal > 0 ? '$' . number_format($totalFinal, 2) : 'A confirmar';
        $itemsHtml .= $ivaSep . $envioRow . "
            <tr><td colspan='3' style='padding:12px 0 0;font-size:13px;color:#0f172a;text-align:right;font-weight:700;text-transform:uppercase;border-top:2px solid #e2e8f0;'>Total con IVA</td>
                <td style='padding:12px 0 0;font-size:17px;color:#1e3a8a;text-align:right;font-weight:800;border-top:2px solid #e2e8f0;'>{$totalStr}</td></tr>";

        $tableHtml = "
        <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px;border-collapse:collapse;'>
            <thead><tr>
                <th style='font-size:11px;color:#94a3b8;font-weight:700;text-align:left;padding-bottom:8px;border-bottom:2px solid #e2e8f0;letter-spacing:.05em;text-transform:uppercase;'>Producto</th>
                <th style='font-size:11px;color:#94a3b8;font-weight:700;text-align:center;padding-bottom:8px;border-bottom:2px solid #e2e8f0;width:50px;letter-spacing:.05em;text-transform:uppercase;'>Cant.</th>
                <th style='font-size:11px;color:#94a3b8;font-weight:700;text-align:right;padding-bottom:8px;border-bottom:2px solid #e2e8f0;width:100px;letter-spacing:.05em;text-transform:uppercase;'>P. Unit.</th>
                <th style='font-size:11px;color:#94a3b8;font-weight:700;text-align:right;padding-bottom:8px;border-bottom:2px solid #e2e8f0;width:100px;letter-spacing:.05em;text-transform:uppercase;'>Subtotal</th>
            </tr></thead>
            <tbody>{$itemsHtml}</tbody>
        </table>";

        // Bloque de envío para el email
        $envioEmailHtml = '';
        if ($tipoEnvioPost === 'gratis') {
            $envioEmailHtml = "<div style='background:#dcfce7;border:1px solid #86efac;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px;'>"
                . "<span style='font-size:20px;'>🚚</span>"
                . "<div><div style='font-size:14px;font-weight:700;color:#15803d;'>¡Tu envío es GRATIS!</div>"
                . "<div style='font-size:12px;color:#16a34a;margin-top:2px;'>Sin costo adicional de entrega.</div></div></div>";
        } elseif ($costoEnvio > 0) {
            $envioEmailHtml = "<div style='background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;padding:14px 18px;margin-bottom:20px;'>"
                . "<div style='font-size:14px;font-weight:700;color:#92400e;'>🚚 Costo de envío: \$" . number_format($costoEnvio, 2) . "</div>"
                . "<div style='font-size:12px;color:#b45309;margin-top:2px;'>Ya incluido en el total mostrado.</div></div>";
        }

        $mensajeHtml = $mensaje
            ? "<div style='background:#f8fafc;border-left:4px solid #1e3a8a;border-radius:0 8px 8px 0;padding:14px 18px;margin-bottom:24px;'><p style='font-size:14px;color:#0f172a;line-height:1.7;margin:0;'>" . nl2br(htmlspecialchars($mensaje)) . "</p></div>"
            : '';

        $folio  = htmlspecialchars($cot['folio']);
        $nombre = htmlspecialchars($cot['nombre']);

        $cuerpoCliente = "
            <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 16px;'>
                Hola <strong style='color:#0f172a;'>{$nombre}</strong>,
            </p>
            <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 20px;'>
                Tu cotización ya está lista. A continuación encontrarás el detalle de los productos solicitados:
            </p>
            <div style='background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 18px;margin-bottom:24px;display:inline-block;'>
                <span style='font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.05em;'>Folio de referencia</span>
                <div style='font-size:22px;font-weight:800;color:#1e3a8a;'>{$folio}</div>
            </div>
            {$mensajeHtml}
            {$tableHtml}
            {$envioEmailHtml}
            <p style='color:#475569;font-size:14px;line-height:1.6;margin:0 0 8px;'>
                Para confirmar tu pedido o resolver cualquier duda, contáctanos:
            </p>
            <ul style='color:#475569;font-size:14px;line-height:1.8;margin:0 0 24px;padding-left:20px;'>
                <li>WhatsApp: <a href='https://wa.me/528331881814' style='color:#1e3a8a;font-weight:600;'>833 188 1814</a></li>
                <li>Teléfono: <strong>(833) 213-3837 | (833) 217-2047</strong></li>
            </ul>
        ";

        $ok = sendMail(
            $cot['email'],
            $cot['nombre'],
            "Tu cotización {$cot['folio']} — OfiEquipo de Tampico",
            _mailBase('¡Tu cotización está lista!', $cuerpoCliente)
                . _mailBtn('https://wa.me/528331881814?text=' . urlencode("Hola, quiero confirmar mi cotización {$cot['folio']}"), 'Confirmar por WhatsApp')
        );

        if ($ok !== false) {
            // Actualizar estado
            $upd = $conn->prepare("UPDATE cotizaciones SET status = ? WHERE id = ?");
            $upd->bind_param('si', $newStatus, $id);
            $upd->execute();
            $upd->close();
            $cot['status'] = $newStatus;
            admin_audit($conn, 'responder', 'cotizacion', $id, "email enviado a {$cot['email']}");
            $flash = "Cotización enviada a {$cot['email']} y estado actualizado a «$newStatus».";
        } else {
            $flash = 'Error al enviar el correo. Revisa la configuración de SendGrid.';
            $flashType = 'err';
        }
    }
}

// ── Labels de estado ─────────────────────────────────────────
function cot_badge(string $status): string {
    $map = [
        'pendiente'  => ['Pendiente',  'pendiente'],
        'en_proceso' => ['En proceso', 'en_proceso'],
        'cotizada'   => ['Cotizada',   'completado'],
        'rechazada'  => ['Rechazada',  'cancelado'],
    ];
    [$lbl, $cls] = $map[$status] ?? [ucfirst($status), 'pendiente'];
    return '<span class="badge ' . $cls . '">' . admin_h($lbl) . '</span>';
}

require_once __DIR__ . '/includes/layout.php';
?>

<style>
.cot-grid { display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start; }
.info-row { display: flex; margin-bottom: 12px; gap: 8px; }
.info-label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; width: 100px; flex-shrink: 0; margin-top: 1px; }
.info-value { font-size: 14px; color: var(--text); font-weight: 500; }
.item-row { display: grid; grid-template-columns: 1fr 70px 130px 130px; gap: 12px; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--border-light); }
.item-row:last-child { border-bottom: none; }
.price-input { width: 100%; padding: 8px 12px; border: 1.5px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px; font-weight: 600; color: var(--primary); text-align: right; outline: none; transition: border-color .2s; }
.price-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(29,61,142,.1); }
.nota-input { width: 100%; padding: 6px 10px; border: 1px solid var(--border); border-radius: 7px; font-family: inherit; font-size: 12px; color: var(--text-secondary); outline: none; resize: none; height: 38px; transition: border-color .2s; }
.nota-input:focus { border-color: var(--primary); }
@media (max-width: 960px) { .cot-grid { grid-template-columns: 1fr; } }
</style>

<div class="page-head" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="cotizaciones.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:600;padding:8px 14px;border:1px solid var(--border);border-radius:8px;background:white;transition:all .15s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--muted)'">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Volver
        </a>
        <div>
            <h1 style="font-size:1.4rem;margin-bottom:4px;"><?= admin_h($cot['folio']) ?></h1>
            <p style="margin:0;"><?= cot_badge($cot['status']) ?> &nbsp;·&nbsp; <?= date('d/m/Y H:i', strtotime($cot['fecha'])) ?></p>
        </div>
    </div>

    <!-- Cambio rápido de estado -->
    <form method="POST" style="display:flex;align-items:center;gap:8px;">
        <input type="hidden" name="accion" value="estado">
        <select name="status" style="padding:9px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none;">
            <option value="pendiente"  <?= $cot['status']==='pendiente'  ? 'selected':'' ?>>Pendiente</option>
            <option value="en_proceso" <?= $cot['status']==='en_proceso' ? 'selected':'' ?>>En proceso</option>
            <option value="cotizada"   <?= $cot['status']==='cotizada'   ? 'selected':'' ?>>Cotizada</option>
            <option value="rechazada"  <?= $cot['status']==='rechazada'  ? 'selected':'' ?>>Rechazada</option>
        </select>
        <button type="submit" class="btn btn-ghost btn-sm">Actualizar estado</button>
    </form>
</div>

<?php if ($flash): ?>
<div class="alert <?= $flashType ?>" style="margin-bottom:20px;">
    <?= admin_h($flash) ?>
</div>
<?php endif; ?>

<div class="cot-grid">

    <!-- Columna izquierda: formulario de respuesta -->
    <div>
        <!-- Productos + precios -->
        <div class="card" style="margin-bottom:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <h2 style="margin:0;">Productos solicitados</h2>
                <span style="font-size:12px;color:var(--muted);"><?= count($items) ?> productos · <?= array_sum(array_column($items, 'cantidad')) ?> unidades</span>
            </div>

            <!-- Cabecera de columnas -->
            <div style="display:grid;grid-template-columns:1fr 70px 130px 130px;gap:12px;padding-bottom:10px;border-bottom:2px solid var(--border-light);margin-bottom:2px;">
                <span style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Producto</span>
                <span style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;text-align:center;">Cant.</span>
                <span style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;text-align:right;">P. Referencia</span>
                <span style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;text-align:right;">Subtotal</span>
            </div>

            <?php
            $totalRef = 0;
            foreach ($items as $item):
                $precio = (float)($item['precio'] ?? 0);
                $sub    = $precio * (int)$item['cantidad'];
                $totalRef += $sub;
            ?>
            <div class="item-row">
                <div>
                    <div style="font-size:13px;font-weight:600;color:var(--text);line-height:1.3;"><?= admin_h($item['nombre_producto']) ?></div>
                    <?php if ($item['producto_id']): ?>
                    <a href="producto_edit.php?id=<?= (int)$item['producto_id'] ?>" style="font-size:11px;color:var(--muted);text-decoration:none;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--muted)'">ID #<?= (int)$item['producto_id'] ?></a>
                    <?php endif; ?>
                </div>
                <div style="text-align:center;font-size:15px;font-weight:700;color:var(--text);"><?= (int)$item['cantidad'] ?></div>
                <div style="text-align:right;font-size:13px;color:var(--muted);">
                    <?= $precio > 0 ? '$' . number_format($precio, 2) : '<span style="color:#cbd5e1;">—</span>' ?>
                </div>
                <div style="text-align:right;font-size:14px;font-weight:700;color:var(--primary);">
                    <?= $sub > 0 ? '$' . number_format($sub, 2) : '<span style="color:#cbd5e1;">—</span>' ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ($totalRef > 0): ?>
            <div style="display:flex;justify-content:flex-end;padding-top:14px;border-top:2px solid var(--border-light);margin-top:4px;gap:24px;align-items:center;">
                <span style="font-size:13px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Total de referencia</span>
                <span style="font-size:19px;font-weight:800;color:var(--primary);">$<?= number_format($totalRef, 2) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Formulario de respuesta -->
        <div class="card">
            <h2 style="margin-bottom:4px;">Enviar cotización al cliente</h2>
            <p style="font-size:13px;color:var(--muted);margin-bottom:20px;">Llena los precios, escribe un mensaje opcional y envía. Se mandará un email a <strong><?= admin_h($cot['email']) ?></strong>.</p>

            <form method="POST" id="formResponder">
                <input type="hidden" name="accion" value="responder">

                <!-- Precios por producto -->
                <div style="margin-bottom:24px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;">Precios cotizados</label>

                    <!-- Encabezado -->
                    <div style="display:grid;grid-template-columns:1fr 70px 130px 130px;gap:12px;padding-bottom:8px;border-bottom:1px solid var(--border-light);margin-bottom:4px;">
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Producto</span>
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;text-align:center;">Cant.</span>
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;text-align:right;">Precio unit. ($)</span>
                        <span style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;text-align:right;">Subtotal</span>
                    </div>

                    <?php foreach ($items as $item):
                        $precio = (float)($item['precio'] ?? 0);
                    ?>
                    <div style="display:grid;grid-template-columns:1fr 70px 130px 130px;gap:12px;align-items:start;padding:12px 0;border-bottom:1px solid var(--border-light);">
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--text);"><?= admin_h($item['nombre_producto']) ?></div>
                            <textarea name="nota_item[<?= (int)$item['id'] ?>]"
                                class="nota-input" style="margin-top:6px;"
                                placeholder="Nota (ej: color disponible, tiempo de entrega...)"></textarea>
                        </div>
                        <div style="text-align:center;font-size:15px;font-weight:700;color:var(--text);padding-top:8px;"><?= (int)$item['cantidad'] ?></div>
                        <div>
                            <input type="number" step="0.01" min="0"
                                name="precio_item[<?= (int)$item['id'] ?>]"
                                class="price-input"
                                value="<?= $precio > 0 ? number_format($precio, 2, '.', '') : '' ?>"
                                placeholder="0.00"
                                data-qty="<?= (int)$item['cantidad'] ?>"
                                data-item-id="<?= (int)$item['id'] ?>"
                                oninput="recalcItem(this)">
                        </div>
                        <div style="text-align:right;font-size:14px;font-weight:700;color:var(--primary);padding-top:8px;" id="sub-<?= (int)$item['id'] ?>">
                            <?= $precio > 0 ? '$' . number_format($precio * (int)$item['cantidad'], 2) : '—' ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div style="padding-top:14px;border-top:2px solid var(--border-light);margin-top:4px;">
                        <div style="display:flex;justify-content:flex-end;gap:20px;padding:3px 0;">
                            <span style="font-size:12px;color:var(--muted);">Subtotal sin IVA</span>
                            <span id="subtotalCotizado" style="font-size:12px;color:var(--muted);font-weight:600;min-width:110px;text-align:right;">$0.00</span>
                        </div>
                        <div style="display:flex;justify-content:flex-end;gap:20px;padding:3px 0;">
                            <span style="font-size:12px;color:var(--muted);">IVA 16%</span>
                            <span id="ivaCotizado" style="font-size:12px;color:var(--muted);min-width:110px;text-align:right;">$0.00</span>
                        </div>
                        <div style="display:flex;justify-content:flex-end;gap:20px;padding:3px 0;" id="envioRowDisplay"></div>
                        <div style="display:flex;justify-content:flex-end;align-items:center;gap:20px;padding-top:10px;border-top:1px solid var(--border-light);margin-top:6px;">
                            <span style="font-size:13px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.05em;">Total con IVA</span>
                            <span id="totalCotizado" style="font-size:20px;font-weight:800;color:var(--primary);">$0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Mensaje adicional -->
                <div class="form-row">
                    <label>Mensaje para el cliente (opcional)</label>
                    <textarea name="mensaje" style="max-width:100%;min-height:110px;"
                        placeholder="Ej: Los precios incluyen IVA. Tiempo de entrega estimado: 5-7 días hábiles. Para pedidos mayores a 5 piezas aplica descuento especial..."></textarea>
                </div>

                <!-- Estado al enviar -->
                <div class="form-row" style="margin-bottom:20px;">
                    <label>Estado tras enviar</label>
                    <select name="status_al_enviar" style="max-width:220px;">
                        <option value="cotizada" selected>Cotizada</option>
                        <option value="en_proceso">En proceso</option>
                        <option value="pendiente">Pendiente</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="gap:10px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                    Enviar cotización por email
                </button>
            </form>
        </div>
    </div>

    <!-- Columna derecha: info del cliente -->
    <div>
        <div class="card" style="margin-bottom:16px;">
            <h2 style="margin-bottom:16px;">Datos del cliente</h2>

            <div class="info-row">
                <span class="info-label">Nombre</span>
                <span class="info-value"><?= admin_h($cot['nombre']) ?></span>
            </div>
            <?php if ($cot['empresa']): ?>
            <div class="info-row">
                <span class="info-label">Empresa</span>
                <span class="info-value"><?= admin_h($cot['empresa']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value">
                    <a href="mailto:<?= admin_h($cot['email']) ?>" style="color:var(--primary);text-decoration:none;font-weight:600;"><?= admin_h($cot['email']) ?></a>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Teléfono</span>
                <span class="info-value">
                    <a href="tel:<?= admin_h($cot['telefono']) ?>" style="color:var(--primary);text-decoration:none;font-weight:600;"><?= admin_h($cot['telefono']) ?></a>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha</span>
                <span class="info-value"><?= date('d/m/Y H:i', strtotime($cot['fecha'])) ?></span>
            </div>

            <?php
            $addrParts = array_filter([
                $cot['dir_calle']    ?? '',
                !empty($cot['dir_colonia'])  ? 'Col. ' . $cot['dir_colonia']  : '',
                $cot['dir_municipio'] ?? '',
                !empty($cot['dir_cp'])       ? 'C.P. ' . $cot['dir_cp']       : '',
            ]);
            if ($addrParts): ?>
            <hr style="border:none;border-top:1px solid var(--border-light);margin:4px 0 14px;">
            <div class="info-row" style="margin-bottom:0;">
                <span class="info-label">Dirección</span>
                <span class="info-value" style="line-height:1.7;">
                    <?= admin_h(implode(', ', $addrParts)) ?>
                    <?php if (!empty($cot['dir_refs'])): ?>
                    <br><span style="font-size:12px;color:var(--muted);">Ref: <?= admin_h($cot['dir_refs']) ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>

            <?php if ($cot['mensaje']): ?>
            <hr style="border:none;border-top:1px solid var(--border-light);margin:16px 0;">
            <div>
                <span style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:8px;">Comentarios del cliente</span>
                <p style="font-size:13px;color:var(--text-secondary);line-height:1.6;margin:0;background:var(--neutral);padding:12px 14px;border-radius:8px;"><?= nl2br(admin_h($cot['mensaje'])) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Zona de envío -->
        <div class="card" style="margin-bottom:16px;">
            <h2 style="margin-bottom:14px;">Envío</h2>

            <!-- Banner de zona detectada -->
            <div style="background:<?= $zona['bg'] ?>;border:1px solid <?= $zona['border'] ?>;border-radius:10px;padding:12px 16px;margin-bottom:16px;">
                <div style="font-size:11px;font-weight:700;color:<?= $zona['color'] ?>;text-transform:uppercase;letter-spacing:.05em;"><?= $zona['label'] ?></div>
                <div style="font-size:13px;color:<?= $zona['color'] ?>;margin-top:3px;"><?= $zona['info'] ?></div>
                <?php if (!empty($cot['dir_municipio'])): ?>
                <div style="font-size:12px;color:var(--muted);margin-top:6px;">&#128205; <?= admin_h($cot['dir_municipio']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Estado actual si ya está definido -->
            <?php if (($cot['envio_tipo'] ?? 'por_definir') !== 'por_definir'): ?>
            <div style="padding:10px 14px;background:var(--neutral);border-radius:8px;font-size:13px;margin-bottom:14px;">
                <?php if ($cot['envio_tipo'] === 'gratis'): ?>
                <span style="color:#16a34a;font-weight:700;">&#10003; Envío GRATIS confirmado</span>
                <?php else: ?>
                <span style="color:#b45309;font-weight:700;">&#10003; Con costo: $<?= number_format((float)$cot['envio_costo'], 2) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Botones de envío -->
            <form method="POST" id="formEnvio">
                <input type="hidden" name="accion" value="set_envio">
                <input type="hidden" name="envio_tipo" id="envioTipoInput" value="">
                <input type="hidden" name="envio_costo" id="envioCostoHidden" value="0">

                <div style="display:flex;gap:10px;margin-bottom:12px;">
                    <button type="button" onclick="setEnvioGratis()" id="btnGratis"
                        style="flex:1;padding:11px;border:2px solid <?= ($cot['envio_tipo']??'')==='gratis' ? '#16a34a' : 'var(--border)' ?>;border-radius:9px;background:<?= ($cot['envio_tipo']??'')==='gratis' ? '#dcfce7' : 'white' ?>;color:<?= ($cot['envio_tipo']??'')==='gratis' ? '#15803d' : 'var(--text-secondary)' ?>;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;">
                        &#128666; Envío GRATIS
                    </button>
                    <button type="button" onclick="setEnvioCosto()" id="btnCosto"
                        style="flex:1;padding:11px;border:2px solid <?= ($cot['envio_tipo']??'')==='con_costo' ? '#f59e0b' : 'var(--border)' ?>;border-radius:9px;background:<?= ($cot['envio_tipo']??'')==='con_costo' ? '#fef3c7' : 'white' ?>;color:<?= ($cot['envio_tipo']??'')==='con_costo' ? '#92400e' : 'var(--text-secondary)' ?>;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;">
                        &#128230; Con costo
                    </button>
                </div>

                <div id="envioCostoField" style="display:<?= ($cot['envio_tipo']??'')==='con_costo' ? 'flex' : 'none' ?>;align-items:center;gap:10px;margin-bottom:12px;">
                    <label style="font-size:12px;font-weight:700;color:var(--muted);white-space:nowrap;">Costo $</label>
                    <input type="number" id="envioCostoInput" step="0.01" min="0"
                        value="<?= number_format((float)($cot['envio_costo'] ?? 0), 2, '.', '') ?>"
                        placeholder="0.00"
                        oninput="document.getElementById('envioCostoHidden').value=this.value;window._envioCosto=parseFloat(this.value)||0;recalcTotal();"
                        style="flex:1;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:14px;font-weight:600;color:var(--primary);text-align:right;outline:none;">
                </div>

                <button type="submit" id="btnGuardarEnvio"
                    style="display:none;width:100%;padding:10px;border:none;border-radius:9px;background:var(--primary);color:white;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">
                    Guardar tipo de envío
                </button>
            </form>
        </div>

        <!-- Contacto rápido -->
        <div class="card" style="margin-bottom:16px;">
            <h2 style="margin-bottom:14px;">Contacto rápido</h2>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <a href="https://wa.me/52<?= preg_replace('/\D/', '', $cot['telefono']) ?>?text=<?= urlencode("Hola {$cot['nombre']}, le contactamos de OfiEquipo respecto a su cotización {$cot['folio']}.") ?>"
                   target="_blank" rel="noopener"
                   style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600;transition:background .15s;"
                   onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#15803d"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                    WhatsApp
                </a>
                <a href="mailto:<?= admin_h($cot['email']) ?>?subject=Cotización <?= admin_h($cot['folio']) ?> — OfiEquipo&body=Hola <?= rawurlencode($cot['nombre']) ?>,"
                   style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--neutral);color:var(--text-secondary);border:1px solid var(--border);border-radius:10px;text-decoration:none;font-size:13px;font-weight:600;transition:background .15s;"
                   onmouseover="this.style.background='var(--border-light)'" onmouseout="this.style.background='var(--neutral)'">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Correo directo
                </a>
            </div>
        </div>

        <!-- Info de folio -->
        <div class="card" style="background:var(--primary);border-color:transparent;">
            <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Folio</div>
            <div style="font-size:24px;font-weight:800;color:white;letter-spacing:-0.5px;margin-bottom:8px;"><?= admin_h($cot['folio']) ?></div>
            <div style="display:flex;align-items:center;gap:8px;">
                <?= str_replace('class="badge ', 'style="background:rgba(255,255,255,.15);color:white;border:1px solid rgba(255,255,255,.25);" class="badge ', cot_badge($cot['status'])) ?>
            </div>
        </div>
    </div>

</div>

<script>
window._envioTipo  = <?= json_encode($cot['envio_tipo'] ?? 'por_definir') ?>;
window._envioCosto = <?= json_encode((float)($cot['envio_costo'] ?? 0)) ?>;

function recalcItem(input) {
    const precio = parseFloat(input.value) || 0;
    const qty    = parseInt(input.dataset.qty) || 1;
    const itemId = input.dataset.itemId;
    const sub    = precio * qty;
    const subEl  = document.getElementById('sub-' + itemId);
    if (subEl) subEl.textContent = sub > 0 ? '$' + sub.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    recalcTotal();
}

function recalcTotal() {
    let subtotal = 0;
    document.querySelectorAll('input[name^="precio_item"]').forEach(inp => {
        subtotal += (parseFloat(inp.value) || 0) * (parseInt(inp.dataset.qty) || 1);
    });
    const iva         = subtotal * 0.16;
    const envioGratis = window._envioTipo === 'gratis';
    const envioAmt    = envioGratis ? 0 : (window._envioCosto || 0);
    const total       = subtotal + iva + envioAmt;

    const fmt = n => '$' + n.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
    const el  = id  => document.getElementById(id);

    if (el('subtotalCotizado')) el('subtotalCotizado').textContent = fmt(subtotal);
    if (el('ivaCotizado'))      el('ivaCotizado').textContent      = fmt(iva);
    if (el('envioRowDisplay')) {
        if (envioGratis) {
            el('envioRowDisplay').innerHTML = '<span style="font-size:12px;color:#16a34a;">Envío</span><span style="font-size:12px;font-weight:700;color:#16a34a;min-width:110px;text-align:right;">GRATIS</span>';
        } else if (envioAmt > 0) {
            el('envioRowDisplay').innerHTML = '<span style="font-size:12px;color:var(--muted);">Envío</span><span style="font-size:12px;color:var(--muted);min-width:110px;text-align:right;">' + fmt(envioAmt) + '</span>';
        } else {
            el('envioRowDisplay').innerHTML = '';
        }
    }
    if (el('totalCotizado')) el('totalCotizado').textContent = fmt(total);
}

function _btnStyle(id, border, bg, color) {
    const b = document.getElementById(id);
    if (!b) return;
    b.style.borderColor = border;
    b.style.background  = bg;
    b.style.color       = color;
}

function setEnvioGratis() {
    document.getElementById('envioTipoInput').value    = 'gratis';
    document.getElementById('envioCostoHidden').value  = '0';
    document.getElementById('envioCostoField').style.display    = 'none';
    document.getElementById('btnGuardarEnvio').style.display    = 'block';
    _btnStyle('btnGratis', '#16a34a', '#dcfce7', '#15803d');
    _btnStyle('btnCosto',  '#cbd5e1', 'white',   '#9ca3af');
    window._envioTipo  = 'gratis';
    window._envioCosto = 0;
    recalcTotal();
}

function setEnvioCosto() {
    document.getElementById('envioTipoInput').value   = 'con_costo';
    document.getElementById('envioCostoField').style.display   = 'flex';
    document.getElementById('btnGuardarEnvio').style.display   = 'block';
    _btnStyle('btnCosto',  '#f59e0b', '#fef3c7', '#92400e');
    _btnStyle('btnGratis', '#cbd5e1', 'white',   '#9ca3af');
    window._envioTipo  = 'con_costo';
    window._envioCosto = parseFloat(document.getElementById('envioCostoInput').value) || 0;
    recalcTotal();
}

document.addEventListener('DOMContentLoaded', recalcTotal);
</script>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
