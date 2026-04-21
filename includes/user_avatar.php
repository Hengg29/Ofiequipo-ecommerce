<?php
// Include dentro de header-actions cuando el usuario está logueado
$_avatarName  = trim((string)($_SESSION['user_nombre'] ?? ''));
$_avatarEmail = (string)($_SESSION['user_email'] ?? '');
$_initials    = strtoupper(mb_substr($_avatarName !== '' ? $_avatarName : $_avatarEmail, 0, 1));
if ($_avatarName !== '' && str_contains($_avatarName, ' ')) {
    $_parts    = explode(' ', $_avatarName);
    $_initials = strtoupper(mb_substr($_parts[0], 0, 1) . mb_substr($_parts[1], 0, 1));
}
?>
<div class="user-avatar-wrap" id="userAvatarWrap">
    <div class="user-avatar" onclick="toggleUserDropdown(event)" title="<?= htmlspecialchars($_avatarName ?: $_avatarEmail, ENT_QUOTES) ?>">
        <?= htmlspecialchars($_initials) ?>
    </div>
    <div class="user-dropdown" id="userDropdown">
        <div class="user-dropdown-head">
            <div class="user-dropdown-name"><?= htmlspecialchars($_avatarName ?: 'Mi cuenta', ENT_QUOTES) ?></div>
            <div class="user-dropdown-email"><?= htmlspecialchars($_avatarEmail, ENT_QUOTES) ?></div>
        </div>
        <a href="mis_pedidos.php" class="user-dropdown-item">
            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
            Mis Pedidos
        </a>
        <a href="logout.php" class="user-dropdown-item danger">
            <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
            Cerrar sesión
        </a>
    </div>
</div>
<script>
if (!window._avatarListenerSet) {
    window._avatarListenerSet = true;
    function toggleUserDropdown(e) {
        e.stopPropagation();
        document.getElementById('userDropdown').classList.toggle('open');
    }
    document.addEventListener('click', function(e) {
        const wrap = document.getElementById('userAvatarWrap');
        if (wrap && !wrap.contains(e.target))
            document.getElementById('userDropdown').classList.remove('open');
    });
}
</script>
