<?php
// Archivo REFACTORIZADO: /public/admin/views/sistema/logs/api.php

$page_title = "Visor de Logs de la API";

// --- LÓGICA DE LA PÁGINA ---

// Apuntamos al archivo de log específico de la API
$log_file_path = ROOT_PATH . '/logs/api.log';
$log_entries = [];

if (file_exists($log_file_path)) {
    $file_content = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($file_content) {
        // Mostramos los logs más recientes primero
        foreach (array_reverse($file_content) as $line) {
            $decoded_line = json_decode($line, true);
            if (is_array($decoded_line)) {
                $log_entries[] = $decoded_line;
            }
        }
    }
}

?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-satellite-dish"></i> <?= htmlspecialchars($page_title) ?></h2>
        <p>Monitoriza todas las interacciones con las APIs externas, incluyendo peticiones, respuestas y errores.</p>
    </div>

    <div class="table-responsive">
        <table class="log-table">
            <thead>
                <tr>
                    <th style="width: 180px;">Timestamp</th>
                    <th style="width: 100px;">Nivel</th>
                    <th>Mensaje</th>
                    <th>Contexto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($log_entries)): ?>
                    <tr>
                        <td colspan="4">El archivo de log de la API está vacío o no existe.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($log_entries as $entry): ?>
                        <tr>
                            <td><?= htmlspecialchars($entry['timestamp'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge log-level-<?= strtolower(htmlspecialchars($entry['level'] ?? 'info')) ?>">
                                    <?= strtoupper(htmlspecialchars($entry['level'] ?? 'INFO')) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($entry['message'] ?? 'Sin mensaje') ?></td>
                            <td>
                                <?php if (!empty($entry['context'])): ?>
                                    <pre><?= htmlspecialchars(json_encode($entry['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Usamos los mismos estilos que la página de logs del sistema para consistencia visual.
ob_start();
?>
<style>
    .log-table {
        width: 100%;
        font-size: 0.9em;
    }
    .log-table pre {
        background-color: #1e1e1e;
        padding: 10px;
        border-radius: 4px;
        white-space: pre-wrap;
        word-break: break-all;
        max-height: 200px;
        overflow-y: auto;
    }
    .log-level-error { background-color: #dc3545; color: white; }
    .log-level-warning { background-color: #ffc107; color: black; }
    .log-level-info { background-color: #17a2b8; color: white; }
    .log-level-debug { background-color: #6c757d; color: white; }
</style>
<?php
$page_specific_styles = ob_get_clean();
?>