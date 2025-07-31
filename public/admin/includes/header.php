<?php
// Carga de themes disponibles desde la base de datos
$stmt_themes = $pdo->prepare("SELECT theme_key, theme_name FROM admin_themes WHERE status = 'activo' ORDER BY theme_name ASC");
$stmt_themes->execute();
$available_themes = $stmt_themes->fetchAll(PDO::FETCH_ASSOC);

// Determinar theme del usuario actual
if (empty($_SESSION['admin_theme'])) {
    $_SESSION['admin_theme'] = $_SESSION['user_data']['admin_theme'] ?? 'neon_dark';
}
$user_theme = $_SESSION['admin_theme'];

$theme_file_path = BASE_URL . 'admin/assets/themes/' . htmlspecialchars($user_theme) . '/theme.css';
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>Admin BiblioSYS</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/css/admin.css">
    <link id="admin-theme-stylesheet" rel="stylesheet" href="<?= $theme_file_path ?>">
	<link rel="stylesheet" href="/admin/assets/css/logs-viewer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <?= $page_specific_styles ?? '' ?>
</head>

<header class="admin-header">
    <div class="header-left">
        <button id="mobile-menu-toggle" class="mobile-menu-button" title="Mostrar/Ocultar menú">
            <i class="fas fa-bars"></i>
        </button>
        <span class="header-title"><?= htmlspecialchars($page_title ?? 'Panel de Administración') ?></span>
    </div>
    <div class="header-right">
        <div class="theme-selector">
            <label for="theme-switcher" title="Seleccionar tema visual"><i class="fas fa-palette"></i></label>
            <select id="theme-switcher">
                <?php foreach ($available_themes as $theme): ?>
                    <option value="<?= htmlspecialchars($theme['theme_key']) ?>" <?= ($user_theme == $theme['theme_key']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($theme['theme_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?= htmlspecialchars($_SESSION['nickname'] ?? 'Usuario') ?></span>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeSwitcher = document.getElementById('theme-switcher');
    if (themeSwitcher) {
        themeSwitcher.addEventListener('change', function() {
            const selectedTheme = this.value;
            const formData = new FormData();
            formData.append('action', 'update_admin_theme');
            formData.append('theme', selectedTheme);

            fetch('<?= BASE_URL ?>ajax-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const newHref = '<?= BASE_URL ?>admin/assets/themes/' + selectedTheme + '/theme.css';
                    document.getElementById('admin-theme-stylesheet').setAttribute('href', newHref);
                    sessionStorage.setItem('admin_theme', selectedTheme);
                } else {
                    alert('Error al cambiar el tema: ' + (data.message || 'Error desconocido.'));
                }
            })
            .catch(error => console.error('Error al cambiar el tema:', error));
        });
    }
});
</script>
