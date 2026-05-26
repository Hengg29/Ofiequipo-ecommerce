</div><!-- .content-area -->
</div><!-- .main -->
</div><!-- .layout -->

<script>
(function() {
    const input = document.getElementById('adminSearchInput');
    const results = document.getElementById('adminSearchResults');
    if (!input || !results) return;

    // Collect all sidebar navigation items
    const navLinks = document.querySelectorAll('.sidebar nav a');
    const items = [];
    navLinks.forEach(function(link) {
        items.push({
            label: link.textContent.trim(),
            href: link.getAttribute('href'),
            icon: link.querySelector('svg') ? link.querySelector('svg').outerHTML : ''
        });
    });

    let activeIndex = -1;

    function renderResults(filtered) {
        results.innerHTML = '';
        if (filtered.length === 0) {
            results.innerHTML = '<div style="padding:14px 16px; color:var(--muted); font-size:13px; text-align:center;">No se encontraron resultados</div>';
            results.style.display = 'block';
            return;
        }
        filtered.forEach(function(item, idx) {
            const div = document.createElement('a');
            div.href = item.href;
            div.style.cssText = 'display:flex; align-items:center; gap:10px; padding:10px 16px; text-decoration:none; color:var(--text); font-size:13px; font-weight:500; transition:background .1s; border-bottom:1px solid var(--border-light);';
            if (idx === activeIndex) {
                div.style.background = 'var(--primary-pale)';
                div.style.color = 'var(--primary)';
            }
            const iconSpan = document.createElement('span');
            iconSpan.style.cssText = 'display:flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; background:var(--neutral); flex-shrink:0; color:var(--text-secondary);';
            iconSpan.innerHTML = item.icon;
            div.appendChild(iconSpan);

            const labelSpan = document.createElement('span');
            labelSpan.textContent = item.label;
            div.appendChild(labelSpan);

            div.addEventListener('mouseenter', function() {
                activeIndex = idx;
                highlightActive();
            });

            results.appendChild(div);
        });
        results.style.display = 'block';
    }

    function highlightActive() {
        const links = results.querySelectorAll('a');
        links.forEach(function(l, i) {
            if (i === activeIndex) {
                l.style.background = 'var(--primary-pale)';
                l.style.color = 'var(--primary)';
            } else {
                l.style.background = 'transparent';
                l.style.color = 'var(--text)';
            }
        });
    }

    function getFiltered() {
        const q = input.value.trim().toLowerCase();
        if (!q) return [];
        return items.filter(function(item) {
            return item.label.toLowerCase().includes(q);
        });
    }

    input.addEventListener('input', function() {
        activeIndex = -1;
        const q = input.value.trim();
        if (!q) {
            results.style.display = 'none';
            results.innerHTML = '';
            return;
        }
        renderResults(getFiltered());
    });

    input.addEventListener('keydown', function(e) {
        const filtered = getFiltered();
        if (filtered.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = (activeIndex + 1) % filtered.length;
            highlightActive();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = activeIndex <= 0 ? filtered.length - 1 : activeIndex - 1;
            highlightActive();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && activeIndex < filtered.length) {
                window.location.href = filtered[activeIndex].href;
            } else if (filtered.length === 1) {
                window.location.href = filtered[0].href;
            }
        } else if (e.key === 'Escape') {
            results.style.display = 'none';
            input.blur();
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.style.display = 'none';
        }
    });

    // Keyboard shortcut: Ctrl+K or Cmd+K to focus search
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            input.focus();
            input.select();
        }
    });
})();
</script>

<?php if (!empty($_GET['saved'])): ?>
<div id="adminToast" style="
    position:fixed;top:18px;right:18px;z-index:9999;
    background:#1D3D8E;color:#fff;
    padding:11px 18px;border-radius:10px;
    font-size:13px;font-weight:600;
    box-shadow:0 4px 16px rgba(0,0,0,.18);
    display:flex;align-items:center;gap:8px;
    opacity:0;transform:translateY(-8px);
    transition:opacity .25s,transform .25s;
    pointer-events:none;">
    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
    <?= !empty($_GET['new']) ? 'Producto creado' : 'Cambios guardados' ?>
</div>
<script>
(function(){
    const t = document.getElementById('adminToast');
    if (!t) return;
    requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; });
    setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(-8px)'; }, 3000);
})();
</script>
<?php endif; ?>
</body>

</html>