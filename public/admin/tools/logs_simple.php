<?php
require_once __DIR__ . '/../../../config/init.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = "Logs dinámicos (con JSON limpio)";
$logs = ['system', 'api', 'handler', 'frontend'];
?>

<div class="admin-content">
    <h2><i class="fas fa-clipboard-list"></i> <?= htmlspecialchars($page_title) ?></h2>

    <div class="tabs-container">
        <ul class="tabs-nav">
            <?php foreach ($logs as $log): ?>
                <li><a href="#" data-log="<?= $log ?>"><?= ucfirst($log) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <div id="tab-content" class="logs-display">
            <p>Selecciona un log para ver su contenido.</p>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<style>
.tabs-container { margin-top: 1rem; }
.tabs-nav { list-style: none; padding: 0; display: flex; gap: 1rem; border-bottom: 1px solid #555; }
.tabs-nav li a {
    text-decoration: none;
    color: var(--nav-text-color);
    padding: 0.5rem 1rem;
    display: block;
}
.tabs-nav li a.active {
    background-color: var(--nav-active-bg);
    border-radius: 4px 4px 0 0;
}
.logs-display {
    padding: 1rem;
    background: #1e1e1e;
    border: 1px solid #333;
    border-top: none;
    max-height: 400px;
    overflow-y: auto;
    white-space: pre-wrap;
    font-family: monospace;
    font-size: 0.85rem;
}
.log-entry { margin-bottom: 0.5rem; border-bottom: 1px solid #444; padding-bottom: 0.5rem; }
.log-timestamp { color: #7bc7ff; font-weight: bold; }
.log-message { color: #ddd; }
</style>
<?php $page_specific_styles = ob_get_clean(); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const links = document.querySelectorAll('.tabs-nav a');
    const target = document.querySelector('#tab-content');

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            const logType = this.getAttribute('data-log');

            fetch(`<?= BASE_URL ?>admin/ajax-handler.php?action=load_log_json&log=${encodeURIComponent(logType)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data)) {
                        if (data.data.length === 0) {
                            target.innerHTML = '<p>No hay entradas en este log.</p>';
                        } else {
                            target.innerHTML = data.data.map(entry => `
                                <div class="log-entry">
                                    <div class="log-timestamp">${entry.timestamp || ''}</div>
                                    <div class="log-message">${entry.message || ''}</div>
                                </div>
                            `).join('');
                        }
                    } else {
                        target.innerHTML = `<p>Error: ${data.message}</p>`;
                    }
                })
                .catch(err => {
                    target.innerHTML = `<p>Error al cargar log: ${err}</p>`;
                });
        });
    });
});
</script>
