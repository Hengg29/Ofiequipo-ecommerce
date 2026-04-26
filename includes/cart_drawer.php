<?php
// Cart drawer — include this at the bottom of any page's <body>
// Requires: session_start() already called, apis/cart.php accessible
?>
<!-- ── CART DRAWER OVERLAY ───────────────────────────────── -->
<div id="cartOverlay" onclick="closeCartDrawer()" style="
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.45); z-index:1100;
    backdrop-filter:blur(2px);
    animation: fadeIn 0.2s ease;
"></div>

<!-- ── CART DRAWER PANEL ─────────────────────────────────── -->
<div id="cartDrawer" style="
    position:fixed; top:0; right:0; height:100vh; width:400px; max-width:95vw;
    background:white; z-index:1101; box-shadow:-8px 0 40px rgba(0,0,0,0.15);
    display:flex; flex-direction:column;
    transform:translateX(100%); transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);
">
    <!-- Header -->
    <div style="
        padding:20px 24px; border-bottom:1px solid #e2e8f0;
        display:flex; align-items:center; justify-content:space-between;
        flex-shrink:0;
    ">
        <div style="display:flex;align-items:center;gap:10px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#1e3a8a"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM5.82 5H21l-1.68 8.39c-.16.79-.84 1.36-1.64 1.36H8.08c-.8 0-1.49-.57-1.64-1.36L5 5H3V3H5.82z"/></svg>
            <span style="font-size:17px;font-weight:700;color:#0f172a;">Tu carrito</span>
            <span id="drawerCount" style="
                background:#1e3a8a;color:white;font-size:11px;font-weight:700;
                padding:2px 8px;border-radius:20px;
            ">0</span>
        </div>
        <button onclick="closeCartDrawer()" style="
            background:none;border:none;cursor:pointer;padding:6px;
            border-radius:8px;color:#94a3b8;font-size:20px;line-height:1;
            transition:background 0.15s;
        " onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">✕</button>
    </div>

    <!-- Items (scrollable) -->
    <div id="drawerItems" style="flex:1;overflow-y:auto;padding:8px 0;"></div>

    <!-- Empty state -->
    <div id="drawerEmpty" style="
        flex:1;display:none;flex-direction:column;align-items:center;
        justify-content:center;padding:40px 24px;text-align:center;
    ">
        <svg width="52" height="52" viewBox="0 0 24 24" fill="#cbd5e1" style="margin-bottom:16px">
            <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM5.82 5H21l-1.68 8.39c-.16.79-.84 1.36-1.64 1.36H8.08c-.8 0-1.49-.57-1.64-1.36L5 5H3V3H5.82z"/>
        </svg>
        <p style="font-size:15px;font-weight:600;color:#475569;margin-bottom:6px;">Tu carrito está vacío</p>
        <p style="font-size:13px;color:#94a3b8;">Agrega productos desde el catálogo</p>
        <a href="catalogo.php" onclick="closeCartDrawer()" style="
            margin-top:20px;padding:10px 20px;
            background:#1e3a8a;color:white;border-radius:9px;
            font-size:13px;font-weight:600;text-decoration:none;
            transition:background 0.15s;
        ">Ver catálogo</a>
    </div>

    <!-- Footer -->
    <div id="drawerFooter" style="
        padding:20px 24px; border-top:1px solid #e2e8f0; flex-shrink:0;
        background:white;
    ">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <span style="font-size:14px;color:#475569;font-weight:500;">Total de artículos</span>
            <span id="drawerTotal" style="font-size:16px;font-weight:700;color:#0f172a;">0</span>
        </div>
        <a href="carrito.php" style="
            display:flex;align-items:center;justify-content:center;gap:8px;
            width:100%;padding:13px;margin-bottom:10px;
            background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);
            color:white;border-radius:11px;font-size:15px;font-weight:600;
            text-decoration:none;
            box-shadow:0 4px 14px rgba(37,99,235,0.3);
            transition:transform 0.15s,box-shadow 0.15s;
        " onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"/></svg>
            Proceder a hacer compra
        </a>
        <!--<a href="carrito.php" style="
            display:flex;align-items:center;justify-content:center;gap:8px;
            width:100%;padding:11px;
            background:white;color:#1e3a8a;
            border:2px solid #1e3a8a;border-radius:11px;
            font-size:14px;font-weight:600;text-decoration:none;
            transition:background 0.15s;
        " onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background='white'">
            Ver carrito completo-->
        </a>
    </div>
</div>

<style>
@keyframes fadeIn { from{opacity:0} to{opacity:1} }

/* Hide WhatsApp FAB while cart drawer is open */
body.cart-drawer-open .whatsapp-fab {
    opacity: 0;
    pointer-events: none;
    transform: scale(0.8);
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.drawer-item {
    display: grid;
    grid-template-columns: 72px 1fr auto;
    gap: 12px;
    align-items: center;
    padding: 14px 24px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}
.drawer-item:hover { background: #f8fafc; }
.drawer-item:last-child { border-bottom: none; }

.drawer-item-img {
    width: 72px; height: 72px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    object-fit: contain;
    padding: 5px;
    background: white;
}
.drawer-item-name {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.35;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.drawer-qty {
    display: flex;
    align-items: center;
    gap: 6px;
}
.drawer-qty-btn {
    width: 26px; height: 26px;
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
    background: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0f172a;
    transition: border-color 0.15s, background 0.15s;
}
.drawer-qty-btn:hover { border-color: #2563eb; background: #eff6ff; }
.drawer-qty-val {
    min-width: 22px;
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}
.drawer-remove {
    background: none;
    border: none;
    cursor: pointer;
    color: #cbd5e1;
    padding: 4px;
    border-radius: 5px;
    transition: color 0.15s, background 0.15s;
    line-height: 1;
}
.drawer-remove:hover { color: #ef4444; background: #fef2f2; }
</style>

<script>
// ── Cart drawer state ──────────────────────────────────────
let cartDrawerOpen = false;

function openCartDrawer(data) {
    cartDrawerOpen = true;
    document.getElementById('cartOverlay').style.display = 'block';
    document.getElementById('cartDrawer').style.transform = 'translateX(0)';
    document.body.style.overflow = 'hidden';
    document.body.classList.add('cart-drawer-open');
    if (data) renderCartDrawer(data); else loadCartDrawer();
}

function closeCartDrawer() {
    cartDrawerOpen = false;
    document.getElementById('cartOverlay').style.display = 'none';
    document.getElementById('cartDrawer').style.transform = 'translateX(100%)';
    document.body.style.overflow = '';
    document.body.classList.remove('cart-drawer-open');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCartDrawer(); });

function loadCartDrawer() {
    fetch('apis/cart.php?action=get')
        .then(r => r.json())
        .then(data => renderCartDrawer(data));
}

function renderCartDrawer(data) {
    const items     = data.cart  || [];
    const count     = data.count || 0;
    const isEmpty   = items.length === 0;

    document.getElementById('drawerCount').textContent = count;
    document.getElementById('drawerTotal').textContent = count;

    document.getElementById('drawerEmpty').style.display  = isEmpty ? 'flex' : 'none';
    document.getElementById('drawerFooter').style.display = isEmpty ? 'none' : 'block';

    const container = document.getElementById('drawerItems');
    if (isEmpty) { container.innerHTML = ''; return; }

    container.innerHTML = items.map(item => `
        <div class="drawer-item" id="ditem-${item.id}">
            <img class="drawer-item-img" src="${escHtml(item.imagen)}" alt="${escHtml(item.nombre)}">
            <div>
                <div class="drawer-item-name">${escHtml(item.nombre)}</div>
                <div class="drawer-qty">
                    <button class="drawer-qty-btn" onclick="drawerUpdateQty(${item.id}, -1)">−</button>
                    <span class="drawer-qty-val" id="dqty-${item.id}">${item.cantidad}</span>
                    <button class="drawer-qty-btn" onclick="drawerUpdateQty(${item.id}, 1)">+</button>
                    <button class="drawer-remove" onclick="drawerRemove(${item.id})" title="Eliminar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function drawerUpdateQty(id, delta) {
    const el  = document.getElementById('dqty-' + id);
    const qty = Math.max(1, parseInt(el.textContent) + delta);
    el.textContent = qty;
    cartPost(`action=update&id=${id}&cantidad=${qty}`).then(d => syncBadges(d.count));
}

function drawerRemove(id) {
    const row = document.getElementById('ditem-' + id);
    row.style.transition = 'opacity 0.25s ease, transform 0.25s ease, max-height 0.25s ease 0.15s, padding 0.2s ease 0.15s';
    row.style.overflow   = 'hidden';
    row.style.maxHeight  = row.offsetHeight + 'px';
    // Force reflow so transition picks up the initial value
    row.offsetHeight;
    row.style.opacity   = '0';
    row.style.transform = 'translateX(50px)';
    setTimeout(() => {
        row.style.maxHeight = '0';
        row.style.paddingTop    = '0';
        row.style.paddingBottom = '0';
    }, 150);

    const req = cartPost(`action=remove&id=${id}`);

    setTimeout(() => {
        row.remove();
        req.then(d => {
            syncBadges(d.count);
            document.getElementById('drawerCount').textContent = d.count;
            document.getElementById('drawerTotal').textContent = d.count;
            if (d.count === 0) {
                document.getElementById('drawerEmpty').style.display  = 'flex';
                document.getElementById('drawerFooter').style.display = 'none';
            }
        });
    }, 420);
}

// Global addToCart — used in producto.php
function addToCart(id, nombre, imagen) {
    cartPost(`action=add&id=${id}&nombre=${encodeURIComponent(nombre)}&imagen=${encodeURIComponent(imagen)}`)
        .then(data => {
            syncBadges(data.count);
            if (typeof onCartAdd === 'function') onCartAdd(data);
        });
}

function cartPost(body) {
    return fetch('apis/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
    }).then(r => r.json());
}

function syncBadges(count) {
    document.querySelectorAll('.cart-badge-count').forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? 'inline-flex' : 'none';
    });
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
