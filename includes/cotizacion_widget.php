<?php
// Carrito de cotización (reemplaza al carrito de compra).
// Incluir al final del <body> de cualquier página pública; requiere session_start() previo.
//
// - Los productos se guardan en localStorage (sobreviven a recargas y al inicio de sesión).
// - Para enviar se exige sesión; la cotización se guarda en BD y se abre WhatsApp.
// - Botones de producto: <button class="js-add-cot" data-id=".." data-nombre=".." data-imagen="..">
require_once __DIR__ . '/config.php';

$cotLogged = !empty($_SESSION['user_id']);
$cotNombre = $cotLogged ? (string)($_SESSION['user_nombre'] ?? '') : '';
$cotEmail  = $cotLogged ? (string)($_SESSION['user_email'] ?? '') : '';
?>
<!-- ── Modal de cotización ─────────────────────────────────── -->
<div class="cot-overlay" id="cotModal" hidden>
    <div class="cot-dialog" role="dialog" aria-modal="true" aria-labelledby="cotTitulo">
        <div class="cot-head">
            <h2 id="cotTitulo">Mi cotización</h2>
            <button type="button" class="cot-close" data-cot-cerrar aria-label="Cerrar cotización">✕</button>
        </div>

        <div class="cot-body">
            <!-- Resumen -->
            <ul class="cot-lista" id="cotLista" aria-label="Productos en tu cotización"></ul>
            <p class="cot-vacio" id="cotVacio">Aún no agregas productos.</p>

            <!-- Aviso global (carrito vacío, errores de red) -->
            <div class="cot-alerta" id="cotAlerta" role="alert" hidden></div>

            <?php if (!$cotLogged): ?>
            <!-- Sin sesión: los productos se conservan en el navegador -->
            <div class="cot-login" id="cotLogin">
                <strong>Inicia sesión para enviar tu cotización</strong>
                <p>Tus productos se quedan guardados; al volver los encontrarás aquí mismo.</p>
                <div class="cot-login-acciones">
                    <a class="cot-btn cot-btn-primario" id="cotLoginLink" href="login.php">Iniciar sesión</a>
                    <a class="cot-btn cot-btn-secundario" id="cotRegistroLink" href="register.php">Crear cuenta</a>
                </div>
            </div>
            <?php else: ?>
            <!-- Formulario -->
            <form class="cot-form" id="cotForm" novalidate>
                <div class="cot-campo">
                    <label for="cotNombre">Nombre Completo <span aria-hidden="true">*</span></label>
                    <input type="text" id="cotNombre" name="nombre" required autocomplete="name" maxlength="150"
                           value="<?= htmlspecialchars($cotNombre) ?>" aria-describedby="cotNombreErr">
                    <span class="cot-error" id="cotNombreErr"></span>
                </div>
                <div class="cot-campo">
                    <label for="cotEmpresa">Empresa (opcional)</label>
                    <input type="text" id="cotEmpresa" name="empresa" autocomplete="organization" maxlength="150">
                </div>
                <div class="cot-fila">
                    <div class="cot-campo">
                        <label for="cotEmail">Correo Electrónico <span aria-hidden="true">*</span></label>
                        <input type="email" id="cotEmail" name="email" required autocomplete="email" maxlength="150"
                               value="<?= htmlspecialchars($cotEmail) ?>" aria-describedby="cotEmailErr">
                        <span class="cot-error" id="cotEmailErr"></span>
                    </div>
                    <div class="cot-campo">
                        <label for="cotTelefono">Teléfono <span aria-hidden="true">*</span></label>
                        <input type="tel" id="cotTelefono" name="telefono" required autocomplete="tel" maxlength="25"
                               inputmode="tel" placeholder="10 dígitos" aria-describedby="cotTelefonoErr">
                        <span class="cot-error" id="cotTelefonoErr"></span>
                    </div>
                </div>
                <div class="cot-campo">
                    <label for="cotMensaje">Mensaje Adicional</label>
                    <textarea id="cotMensaje" name="mensaje" rows="3" maxlength="2000"
                              placeholder="Colores, medidas, fecha de entrega…"></textarea>
                </div>
            </form>
            <?php endif; ?>
        </div>

        <div class="cot-pie">
            <button type="button" class="cot-btn cot-btn-secundario" data-cot-cerrar>Seguir viendo</button>
            <?php if ($cotLogged): ?>
            <button type="submit" form="cotForm" class="cot-btn cot-btn-primario" id="cotEnviar">Realizar cotización</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Aviso breve (envío exitoso) -->
<div class="cot-toast" id="cotToast" role="status" aria-live="polite"></div>

<style>
.cot-btn:focus-visible, .cot-close:focus-visible, .cot-qty button:focus-visible, .cot-quitar:focus-visible {
    outline: 3px solid #93c5fd; outline-offset: 2px;
}
body.cot-abierto .whatsapp-fab { opacity: 0; pointer-events: none; }

.cot-overlay {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(15, 23, 42, .5); backdrop-filter: blur(2px);
    display: flex; align-items: center; justify-content: center; padding: 16px;
}
/* display:flex anularía el atributo hidden: el modal solo se ve al abrirlo */
.cot-overlay[hidden] { display: none; }
.cot-dialog {
    background: #fff; width: 100%; max-width: 560px; max-height: calc(100vh - 32px);
    border-radius: 18px; box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
    display: flex; flex-direction: column; overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.cot-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 24px; border-bottom: 1px solid #e2e8f0;
}
.cot-head h2 { margin: 0; font-size: 18px; font-weight: 700; color: #0f172a; }
.cot-close {
    background: none; border: none; cursor: pointer; padding: 6px 9px; border-radius: 8px;
    color: #64748b; font-size: 18px; line-height: 1;
}
.cot-close:hover { background: #f1f5f9; color: #0f172a; }
.cot-body { flex: 1; overflow-y: auto; padding: 8px 24px 20px; }

.cot-lista { list-style: none; margin: 0; padding: 0; }
.cot-item {
    display: grid; grid-template-columns: 56px 1fr auto; gap: 12px; align-items: center;
    padding: 12px 0; border-bottom: 1px solid #f1f5f9;
}
.cot-item img {
    width: 56px; height: 56px; object-fit: contain; padding: 4px;
    border: 1px solid #e2e8f0; border-radius: 8px; background: #fff;
}
.cot-item-nombre {
    font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.35; margin-bottom: 6px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.cot-qty { display: flex; align-items: center; gap: 6px; }
.cot-qty button {
    width: 28px; height: 28px; border: 1.5px solid #e2e8f0; border-radius: 6px; background: #fff;
    font-size: 15px; font-weight: 600; color: #0f172a; cursor: pointer;
}
.cot-qty button:hover:not(:disabled) { border-color: #2563eb; background: #eff6ff; }
.cot-qty button:disabled { opacity: .4; cursor: not-allowed; }
.cot-qty output { min-width: 26px; text-align: center; font-size: 13px; font-weight: 700; color: #0f172a; }
.cot-quitar {
    background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 15px;
    padding: 6px 8px; border-radius: 6px; line-height: 1;
}
.cot-quitar:hover { color: #ef4444; background: #fef2f2; }
.cot-vacio { text-align: center; color: #64748b; font-size: 14px; padding: 24px 0 8px; margin: 0; }

.cot-alerta {
    margin: 14px 0 0; padding: 11px 14px; border-radius: 10px; font-size: 13px;
    background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c;
}
.cot-alerta.ok { background: #f0fdf4; border-color: #86efac; color: #15803d; }
.cot-alerta a { color: inherit; font-weight: 700; }

.cot-login {
    margin-top: 18px; padding: 16px 18px; border-radius: 12px;
    background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; font-size: 14px;
}
.cot-login p { margin: 4px 0 12px; color: #475569; font-size: 13px; }
.cot-login-acciones { display: flex; gap: 10px; flex-wrap: wrap; }

.cot-form { margin-top: 18px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
.cot-fila { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.cot-campo { display: flex; flex-direction: column; margin-bottom: 12px; }
.cot-campo label { font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 5px; }
.cot-campo label span { color: #ef4444; }
.cot-campo input, .cot-campo textarea {
    padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 9px;
    font-family: inherit; font-size: 14px; color: #0f172a; outline: none; background: #fff;
    transition: border-color .2s, box-shadow .2s;
}
.cot-campo textarea { resize: vertical; min-height: 70px; }
.cot-campo input:focus, .cot-campo textarea:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
.cot-campo input[aria-invalid="true"] { border-color: #ef4444; }
.cot-error { font-size: 12px; color: #dc2626; margin-top: 4px; min-height: 0; }
.cot-error:empty { display: none; }

.cot-pie {
    display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;
    padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #fff;
}
.cot-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 11px 20px; border-radius: 10px; font-family: inherit; font-size: 14px; font-weight: 700;
    cursor: pointer; text-decoration: none; transition: transform .15s, box-shadow .15s, background .15s;
}
.cot-btn-primario {
    border: none; color: #fff; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
    box-shadow: 0 4px 14px rgba(37, 99, 235, .3);
}
.cot-btn-primario:hover { transform: translateY(-1px); }
.cot-btn-primario:disabled { opacity: .6; cursor: wait; transform: none; }
.cot-btn-secundario { background: #fff; color: #475569; border: 1.5px solid #e2e8f0; }
.cot-btn-secundario:hover { background: #f8fafc; color: #1e3a8a; }

.cot-toast {
    position: fixed; left: 50%; bottom: 24px; transform: translate(-50%, 20px); z-index: 10001;
    background: #0f172a; color: #fff; padding: 12px 20px; border-radius: 12px;
    font-size: 14px; font-weight: 500; box-shadow: 0 8px 24px rgba(0, 0, 0, .2);
    opacity: 0; pointer-events: none; transition: opacity .25s, transform .25s; max-width: calc(100vw - 32px);
}
.cot-toast.visible { opacity: 1; transform: translate(-50%, 0); }

/* Feedback del botón "Agregar a cotización" */
.js-add-cot.cot-agregado { background: #16a34a !important; border-color: #16a34a !important; color: #fff !important; }

@media (max-width: 560px) {
    .cot-overlay { padding: 0; align-items: flex-end; }
    .cot-dialog { max-height: 92vh; border-radius: 18px 18px 0 0; }
    .cot-head, .cot-pie { padding-left: 16px; padding-right: 16px; }
    .cot-body { padding-left: 16px; padding-right: 16px; }
    .cot-fila { grid-template-columns: 1fr; gap: 0; }
    .cot-pie .cot-btn { flex: 1; }
}
@media (prefers-reduced-motion: reduce) {
    .cot-btn, .cot-toast { transition: none; }
}
</style>

<script>
(function () {
    'use strict';

    // ── Configuración ──────────────────────────────────────────
    var WHATSAPP_NUMERO = <?= json_encode(WHATSAPP_NUMERO) ?>;
    var LOGUEADO        = <?= $cotLogged ? 'true' : 'false' ?>;
    var STORAGE_KEY     = 'ofi_cotizacion';
    var MAX_CANTIDAD    = 999;
    var IMG_DEFECTO     = 'icono_logo.png';

    var modal   = document.getElementById('cotModal');
    var lista   = document.getElementById('cotLista');
    var vacio   = document.getElementById('cotVacio');
    var alerta  = document.getElementById('cotAlerta');
    var form    = document.getElementById('cotForm');
    var btnEnv  = document.getElementById('cotEnviar');
    var toast   = document.getElementById('cotToast');
    var ultimoFoco = null;

    // ── Persistencia (localStorage) ────────────────────────────
    function leer() {
        try {
            var data = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            return Array.isArray(data) ? data.filter(function (i) { return i && i.id > 0; }) : [];
        } catch (e) { return []; }
    }
    function guardar(items) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(items)); } catch (e) {}
        actualizarContadores(items);
    }
    function totalPiezas(items) {
        return items.reduce(function (s, i) { return s + (parseInt(i.cantidad, 10) || 0); }, 0);
    }

    // Solo se aceptan rutas de imagen del propio sitio o http(s)
    function imagenSegura(src) {
        src = String(src || '');
        return /^(https?:\/\/|image\.php\?|uploads\/|Uploads\/)/.test(src) ? src : IMG_DEFECTO;
    }

    // ── API pública ────────────────────────────────────────────
    function agregar(id, nombre, imagen) {
        id = parseInt(id, 10);
        if (!id) return;
        var items = leer();
        var item  = items.find(function (i) { return i.id === id; });
        if (item) {
            item.cantidad = Math.min(MAX_CANTIDAD, item.cantidad + 1);
        } else {
            items.push({ id: id, nombre: String(nombre || 'Producto').slice(0, 255), imagen: imagenSegura(imagen), cantidad: 1 });
        }
        guardar(items);
        if (!modal.hidden) render();
    }
    function cambiarCantidad(id, delta) {
        var items = leer();
        var item  = items.find(function (i) { return i.id === id; });
        if (!item) return;
        item.cantidad = Math.max(1, Math.min(MAX_CANTIDAD, item.cantidad + delta)); // mínimo 1
        guardar(items);
        render();
    }
    function quitar(id) {
        guardar(leer().filter(function (i) { return i.id !== id; }));
        render();
        // Mantener el foco dentro del modal tras borrar la fila
        var primero = modal.querySelector('.cot-quitar') || modal.querySelector('.cot-close');
        if (primero) primero.focus();
    }

    // ── Render (solo textContent / atributos: nada de innerHTML con datos del usuario) ──
    function actualizarContadores(items) {
        var n = totalPiezas(items || leer());
        document.querySelectorAll('.cart-badge-count').forEach(function (el) {
            el.textContent = n;
            el.style.display = n > 0 ? 'inline-flex' : 'none';
        });
    }

    function crearBoton(texto, clase, etiqueta, onClick) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = clase;
        b.textContent = texto;
        b.setAttribute('aria-label', etiqueta);
        b.addEventListener('click', onClick);
        return b;
    }

    function render() {
        var items = leer();
        lista.textContent = '';
        vacio.hidden = items.length > 0;

        items.forEach(function (item) {
            var li = document.createElement('li');
            li.className = 'cot-item';

            var img = document.createElement('img');
            img.src = imagenSegura(item.imagen);
            img.alt = '';
            img.loading = 'lazy';
            img.onerror = function () { this.onerror = null; this.src = IMG_DEFECTO; };

            var info = document.createElement('div');
            var nombre = document.createElement('div');
            nombre.className = 'cot-item-nombre';
            nombre.textContent = item.nombre;

            var qty = document.createElement('div');
            qty.className = 'cot-qty';
            var menos = crearBoton('−', '', 'Quitar una pieza de ' + item.nombre, function () { cambiarCantidad(item.id, -1); });
            menos.disabled = item.cantidad <= 1;
            var valor = document.createElement('output');
            valor.textContent = item.cantidad;
            valor.setAttribute('aria-label', 'Cantidad');
            var mas = crearBoton('+', '', 'Agregar una pieza de ' + item.nombre, function () { cambiarCantidad(item.id, 1); });
            qty.append(menos, valor, mas);
            info.append(nombre, qty);

            var quitarBtn = crearBoton('✕', 'cot-quitar', 'Quitar ' + item.nombre + ' de la cotización', function () { quitar(item.id); });

            li.append(img, info, quitarBtn);
            lista.appendChild(li);
        });
        actualizarContadores(items);
    }

    function mostrarAlerta(texto, ok) {
        alerta.textContent = texto;
        alerta.classList.toggle('ok', !!ok);
        alerta.hidden = !texto;
    }

    // ── Modal: abrir / cerrar / foco ───────────────────────────
    function urlRegreso() {
        // Al iniciar sesión, volver a esta página con el modal abierto
        var url = new URL(window.location.href);
        url.searchParams.set('cotizacion', 'abrir');
        return (url.pathname.split('/').pop() || 'index.php') + url.search;
    }

    function abrir() {
        ultimoFoco = document.activeElement;
        mostrarAlerta('');
        render();
        var login = document.getElementById('cotLoginLink');
        if (login) {
            login.href = 'login.php?msg=cotizacion&redirect=' + encodeURIComponent(urlRegreso());
        }
        modal.hidden = false;
        document.body.classList.add('cot-abierto');
        document.body.style.overflow = 'hidden';
        var foco = modal.querySelector('.cot-close');
        if (foco) foco.focus();
    }

    function cerrar() {
        if (modal.hidden) return;
        modal.hidden = true;
        document.body.classList.remove('cot-abierto');
        document.body.style.overflow = '';
        if (ultimoFoco && typeof ultimoFoco.focus === 'function') ultimoFoco.focus();
    }

    function focoAtrapado(e) {
        if (e.key !== 'Tab') return;
        var foc = Array.prototype.filter.call(
            modal.querySelectorAll('button, a[href], input, textarea, select, [tabindex]:not([tabindex="-1"])'),
            function (el) { return !el.disabled && el.offsetParent !== null; }
        );
        if (!foc.length) return;
        var primero = foc[0], ultimo = foc[foc.length - 1];
        if (e.shiftKey && document.activeElement === primero) { e.preventDefault(); ultimo.focus(); }
        else if (!e.shiftKey && document.activeElement === ultimo) { e.preventDefault(); primero.focus(); }
    }

    modal.addEventListener('click', function (e) {
        if (e.target === modal || e.target.closest('[data-cot-cerrar]')) cerrar();
    });
    modal.addEventListener('keydown', focoAtrapado);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrar(); });

    // ── Botones "Agregar a cotización" (delegado) ──────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-add-cot');
        if (!btn) return;
        e.preventDefault();
        agregar(btn.dataset.id, btn.dataset.nombre, btn.dataset.imagen);

        // Confirmación "✓ Agregado" durante ~1 segundo
        if (!btn.dataset.textoOriginal) btn.dataset.textoOriginal = btn.textContent.trim();
        btn.textContent = '✓ Agregado';
        btn.classList.add('cot-agregado');
        clearTimeout(btn._cotTimer);
        btn._cotTimer = setTimeout(function () {
            btn.textContent = btn.dataset.textoOriginal;
            btn.classList.remove('cot-agregado');
        }, 1000);
    });

    // Enlaces/botones antiguos del header ("Carrito") abren el modal
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[href="carrito.php"], a[href="cotizacion.php"]');
        if (a) { e.preventDefault(); abrir(); }
    });

    // ── Envío ──────────────────────────────────────────────────
    function validarEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v); }

    function marcarError(id, msg) {
        var input = document.getElementById(id);
        var err   = document.getElementById(id + 'Err');
        if (input) input.setAttribute('aria-invalid', msg ? 'true' : 'false');
        if (err) err.textContent = msg || '';
        return !msg;
    }

    function validar() {
        var nombre = form.nombre.value.trim();
        var email  = form.email.value.trim();
        var tel    = form.telefono.value.trim();
        var ok = true;
        ok = marcarError('cotNombre', nombre.length < 2 ? 'Ingresa tu nombre completo.' : '') && ok;
        ok = marcarError('cotEmail', !email ? 'Ingresa tu correo electrónico.' : (!validarEmail(email) ? 'El formato del correo no es válido.' : '')) && ok;
        ok = marcarError('cotTelefono', tel.replace(/\D/g, '').length < 10 ? 'Ingresa un teléfono de al menos 10 dígitos.' : '') && ok;
        if (!ok) {
            var primero = form.querySelector('[aria-invalid="true"]');
            if (primero) primero.focus();
        }
        return ok;
    }

    // Arma el mensaje con el formato acordado; omite Empresa/Mensaje si están vacíos
    function construirMensaje(datos, items, folio) {
        var lineas = ['*Solicitud de cotización*'];
        if (folio) lineas.push('Folio: ' + folio);
        lineas.push('Nombre: ' + datos.nombre);
        if (datos.empresa) lineas.push('Empresa: ' + datos.empresa);
        lineas.push('Correo: ' + datos.email);
        lineas.push('Teléfono: ' + datos.telefono);
        lineas.push('', '*Productos:*');
        items.forEach(function (i) { lineas.push('- ' + i.cantidad + ' x ' + i.nombre); });
        if (datos.mensaje) lineas.push('', 'Mensaje: ' + datos.mensaje);
        return lineas.join('\n');
    }

    function avisar(texto) {
        toast.textContent = texto;
        toast.classList.add('visible');
        clearTimeout(toast._t);
        toast._t = setTimeout(function () { toast.classList.remove('visible'); }, 4000);
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            mostrarAlerta('');

            var items = leer();
            if (!items.length) {
                mostrarAlerta('Agrega al menos un producto antes de realizar la cotización.');
                return;
            }
            if (!validar()) return;

            var datos = {
                nombre:   form.nombre.value.trim(),
                empresa:  form.empresa.value.trim(),
                email:    form.email.value.trim(),
                telefono: form.telefono.value.trim(),
                mensaje:  form.mensaje.value.trim(),
                items:    items.map(function (i) { return { id: i.id, cantidad: i.cantidad }; })
            };

            // La pestaña se abre ya (gesto del usuario) para que el navegador no la bloquee;
            // se redirige a WhatsApp cuando el servidor confirma que guardó la cotización.
            var ventana = window.open('', '_blank');
            btnEnv.disabled = true;
            btnEnv.textContent = 'Enviando…';

            fetch('apis/procesar_cotizacion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(datos)
            })
            .then(function (r) { return r.json().then(function (j) { j._status = r.status; return j; }); })
            .then(function (res) {
                if (!res.success) {
                    if (ventana) ventana.close();
                    if (res.login) { window.location.href = 'login.php?msg=cotizacion&redirect=' + encodeURIComponent(urlRegreso()); return; }
                    if (res.errors) {
                        if (res.errors.nombre)   marcarError('cotNombre', res.errors.nombre);
                        if (res.errors.email)    marcarError('cotEmail', res.errors.email);
                        if (res.errors.telefono) marcarError('cotTelefono', res.errors.telefono);
                    }
                    mostrarAlerta(res.error || 'No se pudo enviar la cotización.');
                    return;
                }

                var url = 'https://wa.me/' + WHATSAPP_NUMERO + '?text=' +
                          encodeURIComponent(construirMensaje(datos, res.items || items, res.folio));

                guardar([]);
                form.reset();
                form.querySelectorAll('[aria-invalid]').forEach(function (el) { el.removeAttribute('aria-invalid'); });

                if (ventana) {
                    ventana.opener = null;
                    ventana.location.href = url;
                    cerrar();
                    avisar('Cotización ' + res.folio + ' registrada. Envíala desde WhatsApp.');
                } else {
                    // Ventana bloqueada: se ofrece el enlace dentro del modal
                    render();
                    alerta.textContent = 'Cotización ' + res.folio + ' registrada. ';
                    var a = document.createElement('a');
                    a.href = url; a.target = '_blank'; a.rel = 'noopener';
                    a.textContent = 'Abrir WhatsApp para enviarla';
                    alerta.appendChild(a);
                    alerta.classList.add('ok');
                    alerta.hidden = false;
                    a.focus();
                }
            })
            .catch(function () {
                if (ventana) ventana.close();
                mostrarAlerta('Sin conexión con el servidor. Intenta de nuevo.');
            })
            .finally(function () {
                btnEnv.disabled = false;
                btnEnv.textContent = 'Realizar cotización';
            });
        });
    }

    // ── API global (compatibilidad con botones del header y código existente) ──
    window.Cotizacion     = { agregar: agregar, abrir: abrir, cerrar: cerrar };
    window.openCotizacion = abrir;
    window.openCartDrawer = abrir;

    // Sincroniza contadores si otra pestaña modifica la cotización
    window.addEventListener('storage', function (e) {
        if (e.key === STORAGE_KEY) { actualizarContadores(); if (!modal.hidden) render(); }
    });

    actualizarContadores();

    // Abrir automáticamente al volver del login (?cotizacion=abrir)
    var params = new URLSearchParams(window.location.search);
    if (params.get('cotizacion') === 'abrir') {
        params.delete('cotizacion');
        var limpia = window.location.pathname + (params.toString() ? '?' + params : '') + window.location.hash;
        history.replaceState(null, '', limpia);
        abrir();
    }
})();
</script>
