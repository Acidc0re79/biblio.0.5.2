<?php
$page = $_GET['page'] ?? 'dashboard';
?>
<nav class="admin-nav">
    <div class="nav-header">
        <a href="<?= BASE_URL ?>admin/" class="nav-brand">
            <i class="fas fa-book-reader"></i>
            <span>BiblioSYS</span>
        </a>
    </div>
    <ul class="nav-menu">
        <li class="<?= ($page == 'dashboard') ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>admin/index.php?page=dashboard"><i class="fas fa-home"></i> Inicio</a>
        </li>

        <?php if (in_array($_SESSION['rango'], ['administrador', 'moderador'])): ?>
            <li class="<?= in_array($page, ['usuarios', 'editar_usuario']) ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>admin/index.php?page=usuarios"><i class="fas fa-users-cog"></i> Usuarios</a>
            </li>
        <?php endif; ?>

        <?php if ($_SESSION['rango'] === 'administrador'): ?>
            <li class="has-submenu <?= (in_array(explode('_', $page)[0], ['config', 'logs', 'banco', 'dev'])) ? 'open' : '' ?>">
                <a href="#"><i class="fas fa-cogs"></i> Sistema</a>
                <ul class="submenu">
                    <li class="<?= (strpos($page, 'config_') === 0) ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/index.php?page=config_general"><i class="fas fa-sliders-h"></i> Configuración</a>
                    </li>
                    <li class="<?= (strpos($page, 'logs_') === 0) ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/index.php?page=logs"><i class="fas fa-clipboard-list"></i> Logs</a>
                    </li>
					<li class="<?= ($page == 'banco_pruebas_gemini') ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/index.php?page=banco_pruebas_gemini"><i class="fas fa-flask"></i> Banco IA</a>
                    </li>
                    <li class="<?= ($page == 'dev_directory_lister') ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/index.php?page=dev_directory_lister"><i class="fas fa-folder-open"></i> Archivos</a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>

        <li>
            <a href="<?= BASE_URL ?>form-handler.php?action=logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </li>
    </ul>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const submenuItems = document.querySelectorAll('.admin-nav .has-submenu > a');
    submenuItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            this.parentElement.classList.toggle('open');
        });
    });

    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const adminNav = document.querySelector('.admin-nav');
    if (mobileMenuToggle && adminNav) {
        mobileMenuToggle.addEventListener('click', function() {
            adminNav.classList.toggle('mobile-open');
        });
    }
});
</script>
