<?php
$page_title = "Visor de Logs";

$logs = [
    'System'   => ROOT_PATH . '/logs/system_debug.json',
    'API'      => ROOT_PATH . '/logs/api_debug.json',
    'Handler'  => ROOT_PATH . '/logs/handler_debug.json',
    'Frontend' => ROOT_PATH . '/logs/frontend_debug.json'
];
?>

<div class="admin-content">
    <h2><i class="fas fa-clipboard-list"></i> <?= htmlspecialchars($page_title) ?></h2>

    <div class="tabs-container">
        <ul class="tabs-nav">
            <?php foreach ($logs as $name => $path): ?>
                <li><a href="#" data-log="<?= strtolower($name) ?>"><?= htmlspecialchars($name) ?></a></li>
            <?php endforeach; ?>
        </ul>
		<div class="tabs-controls">
    <label for="log-limit">Mostrar últimos:</label>
    <select id="log-limit">
        <option value="10">10 registros</option>
        <option value="50" selected>50 registros</option>
        <option value="100">100 registros</option>
        <option value="9999">Todos</option>
    </select>
</div>
      </div>
</div>

<div class="log-container"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.11/clipboard.min.js"></script>
<script src="/admin/assets/js/logs-viewer.js"></script>
<link rel="stylesheet" href="/admin/assets/css/logs-viewer.css">


<?php ob_start(); ?>
<?php $page_specific_styles = ob_get_clean(); ?>
<script src="/admin/assets/js/logs-viewer.js"></script>
<script src="<?= BASE_URL ?>admin/assets/js/dynamic_tabs.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    initDynamicTabs({
      target: '#tab-content',
      navSelector: '.tabs-nav a',
      endpoint: '/ajax-handler.php'
    });
  });
</script>