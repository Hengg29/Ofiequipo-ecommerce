<?php
// Gabu, asistente virtual: flujo guiado de preguntas (sin IA). Al terminar manda las
// respuestas por correo a la admin (apis/gabu_solicitud.php) y pide crear cuenta o iniciar sesión.
// Incluir al final del <body> de las páginas públicas; requiere session_start() previo.
$gabuLogged = !empty($_SESSION['user_id']);
?>
<!-- ── Gabu: avatar + globo ───────────────────────────────── -->
<div class="gabu" id="gabu">
    <button type="button" class="gabu-globo" id="gabuGlobo" aria-hidden="true" tabindex="-1">¿Te ayudo a cotizar?</button>
    <button type="button" class="gabu-avatar" id="gabuAbrir" aria-haspopup="dialog" aria-controls="gabuPanel"
            aria-label="Abrir a Gabu, asistente virtual para cotizar">
        <img src="src/img/gabu.png" alt="" width="72" height="72">
    </button>
</div>

<!-- ── Panel de conversación ──────────────────────────────── -->
<section class="gabu-panel" id="gabuPanel" role="dialog" aria-modal="false" aria-labelledby="gabuTitulo" hidden>
    <header class="gabu-head">
        <img src="src/img/gabu.png" alt="" width="40" height="40">
        <div>
            <h2 id="gabuTitulo">Gabu</h2>
            <span>Asistente virtual · Ofiequipo</span>
        </div>
        <button type="button" class="gabu-cerrar" id="gabuCerrar" aria-label="Cerrar asistente">✕</button>
    </header>

    <div class="gabu-log" id="gabuLog" role="log" aria-live="polite"></div>

    <form class="gabu-form" id="gabuForm" novalidate>
        <label for="gabuInput" class="gabu-sr" id="gabuLabel">Tu respuesta</label>
        <div class="gabu-entrada">
            <input type="text" id="gabuInput" autocomplete="off" maxlength="150">
            <textarea id="gabuTexto" rows="2" maxlength="1000" hidden></textarea>
            <button type="submit" class="gabu-enviar" aria-label="Enviar respuesta">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            </button>
        </div>
        <p class="gabu-error" id="gabuError" role="alert"></p>
    </form>

    <div class="gabu-acciones" id="gabuAcciones" hidden>
        <a class="gabu-btn gabu-btn-primario" id="gabuRegistro" href="register.php">Crear cuenta</a>
        <a class="gabu-btn gabu-btn-secundario" id="gabuLogin" href="login.php">Iniciar sesión</a>
    </div>

    <p class="gabu-nota">Tus datos solo se usan para atender tu solicitud.</p>
</section>

<style>
.gabu { position: fixed; left: 20px; bottom: 20px; z-index: 999; display: flex; align-items: flex-end; gap: 10px; }
.gabu-avatar {
    width: 76px; height: 76px; padding: 2px; border-radius: 50%; cursor: pointer;
    background: #fff; border: 2px solid #1e3a8a; box-shadow: 0 6px 20px rgba(30, 58, 138, .3);
    transition: transform .2s; order: 1;
}
.gabu-avatar:hover { transform: scale(1.06); }
.gabu-avatar img { width: 100%; height: 100%; object-fit: contain; display: block; border-radius: 50%; }
.gabu-globo {
    order: 2; margin-bottom: 44px; position: relative; cursor: pointer;
    background: #fff; color: #1e3a8a; border: 1.5px solid #bfdbfe; border-radius: 14px 14px 14px 4px;
    padding: 9px 14px; font-family: inherit; font-size: 13px; font-weight: 700; white-space: nowrap;
    box-shadow: 0 6px 18px rgba(15, 23, 42, .12); animation: gabuEntrar .4s ease .8s both;
}
@keyframes gabuEntrar { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
.gabu.abierto .gabu-globo { display: none; }

.gabu-panel {
    position: fixed; left: 20px; bottom: 108px; z-index: 9990;
    width: 360px; max-width: calc(100vw - 32px); max-height: calc(100vh - 140px);
    display: flex; flex-direction: column; overflow: hidden;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; box-shadow: 0 20px 50px rgba(15, 23, 42, .25);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.gabu-panel[hidden] { display: none; }
.gabu-head {
    display: flex; align-items: center; gap: 10px; padding: 12px 14px;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #fff;
}
.gabu-head img { width: 40px; height: 40px; border-radius: 50%; background: #fff; object-fit: contain; }
.gabu-head h2 { margin: 0; font-size: 15px; font-weight: 700; color: #fff; line-height: 1.2; }
.gabu-head span { font-size: 11px; opacity: .85; }
.gabu-cerrar {
    margin-left: auto; background: rgba(255, 255, 255, .15); border: none; color: #fff; cursor: pointer;
    width: 30px; height: 30px; border-radius: 8px; font-size: 14px;
}
.gabu-cerrar:hover { background: rgba(255, 255, 255, .28); }

.gabu-log { flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 10px; background: #f8fafc; min-height: 180px; }
.gabu-msg { display: flex; align-items: flex-end; gap: 8px; max-width: 92%; }
.gabu-msg img { width: 34px; height: 34px; border-radius: 50%; background: #fff; border: 1px solid #e2e8f0; object-fit: contain; flex-shrink: 0; }
.gabu-msg p {
    margin: 0; padding: 9px 12px; font-size: 13.5px; line-height: 1.45; color: #0f172a;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px 14px 14px 4px; white-space: pre-line;
}
.gabu-msg.usuario { align-self: flex-end; }
.gabu-msg.usuario p { background: #1e3a8a; color: #fff; border-color: #1e3a8a; border-radius: 14px 14px 4px 14px; }

.gabu-form { padding: 10px 12px 4px; border-top: 1px solid #e2e8f0; }
.gabu-form[hidden] { display: none; }
.gabu-entrada { display: flex; gap: 8px; align-items: flex-end; }
.gabu-entrada input, .gabu-entrada textarea {
    flex: 1; min-width: 0; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-family: inherit; font-size: 14px; color: #0f172a; outline: none; resize: none;
}
.gabu-entrada input:focus, .gabu-entrada textarea:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
.gabu-entrada [aria-invalid="true"] { border-color: #ef4444; }
.gabu-enviar {
    width: 42px; height: 42px; flex-shrink: 0; border: none; border-radius: 10px; cursor: pointer;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); display: flex; align-items: center; justify-content: center;
}
.gabu-enviar:disabled { opacity: .6; cursor: wait; }
.gabu-enviar svg { width: 18px; height: 18px; fill: #fff; }
.gabu-error { margin: 4px 2px 0; min-height: 0; font-size: 12px; color: #dc2626; }
.gabu-error:empty { display: none; }

.gabu-acciones { display: flex; gap: 8px; padding: 12px; border-top: 1px solid #e2e8f0; }
.gabu-acciones[hidden] { display: none; }
.gabu-btn {
    flex: 1; text-align: center; padding: 10px 12px; border-radius: 10px; font-size: 14px; font-weight: 700; text-decoration: none;
}
.gabu-btn-primario { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #fff; }
.gabu-btn-secundario { background: #fff; color: #1e3a8a; border: 1.5px solid #e2e8f0; }
.gabu-btn-secundario:hover { background: #f8fafc; }
.gabu-nota { margin: 0; padding: 6px 12px 10px; font-size: 11px; color: #94a3b8; text-align: center; }
.gabu-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

.gabu-avatar:focus-visible, .gabu-globo:focus-visible, .gabu-cerrar:focus-visible, .gabu-enviar:focus-visible, .gabu-btn:focus-visible {
    outline: 3px solid #93c5fd; outline-offset: 2px;
}

@media (max-width: 560px) {
    .gabu { left: 12px; bottom: 14px; }
    .gabu-avatar { width: 64px; height: 64px; }
    .gabu-globo { margin-bottom: 36px; font-size: 12px; }
    .gabu-panel { left: 8px; right: 8px; width: auto; max-width: none; bottom: 88px; max-height: calc(100vh - 110px); }
}
@media (prefers-reduced-motion: reduce) {
    .gabu-globo { animation: none; }
    .gabu-avatar { transition: none; }
}
</style>

<script>
(function () {
    'use strict';

    var LOGUEADO    = <?= $gabuLogged ? 'true' : 'false' ?>;
    var STORAGE_KEY = 'ofi_gabu'; // solicitud enviada que espera inicio de sesión
    var IMG         = 'src/img/gabu.png';

    // Preguntas fijas, en orden
    var PASOS = [
        { campo: 'nombre',      pregunta: '¡Hola! Soy Gabu 🐾 Te ayudo a cotizar. ¿Cuál es tu nombre?',
          tipo: 'text', etiqueta: 'Tu nombre', placeholder: 'Nombre y apellido', autocomplete: 'name',
          validar: function (v) { return v.length >= 2 ? '' : 'Escribe tu nombre.'; } },
        { campo: 'telefono',    pregunta: 'Mucho gusto, {nombre}. ¿A qué teléfono te podemos contactar?',
          tipo: 'tel', etiqueta: 'Tu teléfono', placeholder: '10 dígitos', autocomplete: 'tel', inputmode: 'tel',
          validar: function (v) { return v.replace(/\D/g, '').length >= 10 ? '' : 'El teléfono debe tener al menos 10 dígitos.'; } },
        { campo: 'servicio',    pregunta: '¿Qué deseas realizar? Por ejemplo: remodelación de oficina, equipar una sala de juntas, sillas para tu equipo…',
          tipo: 'textarea', etiqueta: 'Qué deseas realizar', placeholder: 'Cuéntame tu proyecto',
          validar: function (v) { return v.length >= 3 ? '' : 'Cuéntame un poco más de lo que necesitas.'; } },
        { campo: 'presupuesto', pregunta: '¿Cuál es tu presupuesto aproximado? (solo números, en pesos)',
          tipo: 'text', etiqueta: 'Presupuesto en pesos', placeholder: 'Ej. 50000', inputmode: 'numeric',
          validar: function (v) { return /^\d{1,10}$/.test(v) && Number(v) > 0 ? '' : 'Escribe tu presupuesto solo con números.'; } }
    ];

    var raiz      = document.getElementById('gabu');
    var btnAbrir  = document.getElementById('gabuAbrir');
    var globo     = document.getElementById('gabuGlobo');
    var panel     = document.getElementById('gabuPanel');
    var log       = document.getElementById('gabuLog');
    var form      = document.getElementById('gabuForm');
    var input     = document.getElementById('gabuInput');
    var texto     = document.getElementById('gabuTexto');
    var label     = document.getElementById('gabuLabel');
    var errorEl   = document.getElementById('gabuError');
    var acciones  = document.getElementById('gabuAcciones');
    var btnEnviar = form.querySelector('.gabu-enviar');

    var paso = 0, datos = {}, iniciado = false;

    function leerPendiente() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null'); } catch (e) { return null; }
    }
    function guardarPendiente(v) {
        try { v ? localStorage.setItem(STORAGE_KEY, JSON.stringify(v)) : localStorage.removeItem(STORAGE_KEY); } catch (e) {}
    }

    // Mensajes: solo textContent (el texto del usuario nunca se interpreta como HTML)
    function decir(txt, deUsuario) {
        var div = document.createElement('div');
        div.className = 'gabu-msg' + (deUsuario ? ' usuario' : '');
        if (!deUsuario) {
            var img = document.createElement('img');
            img.src = IMG; img.alt = '';
            div.appendChild(img);
        }
        var p = document.createElement('p');
        p.textContent = txt;
        div.appendChild(p);
        log.appendChild(div);
        log.scrollTop = log.scrollHeight;
    }

    function campoActual() { return PASOS[paso].tipo === 'textarea' ? texto : input; }

    function preguntar() {
        var p = PASOS[paso];
        decir(p.pregunta.replace('{nombre}', datos.nombre || ''));
        var esTexto = p.tipo === 'textarea';
        texto.hidden = !esTexto;
        input.hidden = esTexto;
        var campo = campoActual();
        campo.value = '';
        campo.placeholder = p.placeholder;
        campo.removeAttribute('aria-invalid');
        campo.setAttribute('aria-describedby', 'gabuError');
        if (!esTexto) {
            input.type = p.tipo;
            input.setAttribute('autocomplete', p.autocomplete || 'off');
            if (p.inputmode) input.setAttribute('inputmode', p.inputmode); else input.removeAttribute('inputmode');
        }
        label.setAttribute('for', campo.id);
        label.textContent = p.etiqueta;
        errorEl.textContent = '';
        campo.focus();
    }

    function mostrarCuenta() {
        form.hidden = true;
        var url = new URL(window.location.href);
        url.searchParams.set('gabu', 'listo');
        var regreso = (url.pathname.split('/').pop() || 'index.php') + url.search;
        document.getElementById('gabuLogin').href = 'login.php?msg=gabu&redirect=' + encodeURIComponent(regreso);
        acciones.hidden = false;
    }

    function despedirse(nombre) {
        form.hidden = true;
        acciones.hidden = true;
        decir('¡Listo' + (nombre ? ', ' + nombre : '') + '! En breve un asesor de Ofiequipo se pondrá en contacto contigo. 🐾');
    }

    function enviar() {
        btnEnviar.disabled = true;
        decir('Un momento, estoy registrando tu solicitud…');
        fetch('apis/gabu_solicitud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                nombre: datos.nombre, telefono: datos.telefono, servicio: datos.servicio,
                presupuesto: datos.presupuesto, pagina: location.pathname
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) throw new Error(res.error || '');
            if (LOGUEADO) {
                despedirse(datos.nombre);
            } else {
                guardarPendiente({ nombre: datos.nombre });
                decir('¡Gracias, ' + datos.nombre + '! Ya tengo tu información. Para darle seguimiento, crea una cuenta o inicia sesión.');
                mostrarCuenta();
            }
        })
        .catch(function (err) {
            decir((err && err.message) || 'No pude enviar tu solicitud. Revisa tu conexión e inténtalo de nuevo.');
            // Permite reintentar el último paso
            paso = PASOS.length - 1;
            preguntar();
            campoActual().value = datos.presupuesto || '';
        })
        .finally(function () { btnEnviar.disabled = false; });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var p = PASOS[paso];
        var campo = campoActual();
        var valor = campo.value.trim();
        if (p.campo === 'presupuesto') valor = valor.replace(/[\s,$.]/g, '');
        var err = p.validar(valor);
        if (err) {
            errorEl.textContent = err;
            campo.setAttribute('aria-invalid', 'true');
            campo.focus();
            return;
        }
        datos[p.campo] = valor;
        decir(p.campo === 'presupuesto' ? '$' + Number(valor).toLocaleString('es-MX') : valor, true);
        paso++;
        if (paso < PASOS.length) preguntar(); else enviar();
    });

    // Enter envía en el textarea; Shift+Enter hace salto de línea
    texto.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
    });
    // Presupuesto: solo dígitos
    input.addEventListener('input', function () {
        if (PASOS[paso] && PASOS[paso].campo === 'presupuesto') input.value = input.value.replace(/\D/g, '');
    });

    // ── Abrir / cerrar ─────────────────────────────────────────
    function abrir() {
        panel.hidden = false;
        raiz.classList.add('abierto');
        btnAbrir.setAttribute('aria-expanded', 'true');
        if (!iniciado) {
            iniciado = true;
            var pendiente = leerPendiente();
            if (pendiente && !LOGUEADO) {
                decir('¡Hola de nuevo, ' + pendiente.nombre + '! Ya recibimos tu solicitud. Crea una cuenta o inicia sesión para darle seguimiento.');
                mostrarCuenta();
                document.getElementById('gabuRegistro').focus();
                return;
            }
            preguntar();
        } else {
            var foco = !form.hidden ? campoActual() : (!acciones.hidden ? document.getElementById('gabuRegistro') : document.getElementById('gabuCerrar'));
            foco.focus();
        }
    }
    function cerrar() {
        if (panel.hidden) return;
        panel.hidden = true;
        raiz.classList.remove('abierto');
        btnAbrir.setAttribute('aria-expanded', 'false');
        btnAbrir.focus();
    }

    btnAbrir.setAttribute('aria-expanded', 'false');
    btnAbrir.addEventListener('click', function () { panel.hidden ? abrir() : cerrar(); });
    globo.addEventListener('click', abrir);
    document.getElementById('gabuCerrar').addEventListener('click', cerrar);
    panel.addEventListener('keydown', function (e) { if (e.key === 'Escape') { e.stopPropagation(); cerrar(); } });

    // ── Regreso tras iniciar sesión / crear cuenta ─────────────
    var pendiente = leerPendiente();
    var params = new URLSearchParams(location.search);
    if (params.has('gabu')) {
        params.delete('gabu');
        history.replaceState(null, '', location.pathname + (params.toString() ? '?' + params : '') + location.hash);
    }
    if (LOGUEADO && pendiente) {
        guardarPendiente(null);
        iniciado = true;
        panel.hidden = false;
        raiz.classList.add('abierto');
        btnAbrir.setAttribute('aria-expanded', 'true');
        despedirse(pendiente.nombre);
    }
})();
</script>
