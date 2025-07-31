<?php
// Archivo independiente: /public/admin/tools/test_logs.php

require_once __DIR__ . '/../../../config/init.php';

$log_files = [
    'system'   => ROOT_PATH . '/logs/system_debug.json',
    'api'      => ROOT_PATH . '/logs/api_debug.json',
    'handler'  => ROOT_PATH . '/logs/handler_debug.json',
    'frontend' => ROOT_PATH . '/logs/frontend_debug.json'
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Logs Directos</title>
    <style>
        body {
            background-color: #1e1e1e;
            color: #f0f0f0;
            font-family: monospace;
            padding: 2rem;
        }
        h2 {
            border-bottom: 1px solid #444;
            padding-bottom: 0.3rem;
        }
        pre {
            background-color: #2c2c2c;
            padding: 1rem;
            overflow-x: auto;
            max-height: 300px;
            border-radius: 6px;
        }
        .log-block {
            margin-bottom: 2rem;
        }
        .exists {
            color: #4caf50;
        }
        .missing {
            color: #ff5252;
        }
    </style>
</head>
<body>
    <h1>📋 Test Logs Directos (sin AJAX)</h1>
    <p><strong>ROOT_PATH:</strong> <?= ROOT_PATH ?></p>

    <?php foreach ($log_files as $name => $path): ?>
        <div class="log-block">
            <h2>Log: <?= htmlspecialchars($name) ?></h2>
            <p><strong>Ruta:</strong> <?= htmlspecialchars($path) ?></p>
            <?php if (file_exists($path)): ?>
                <p class="exists">✅ Archivo existe</p>
                <p><strong>Permisos:</strong> <?= substr(sprintf('%o', fileperms($path)), -4) ?></p>
                <pre><?= htmlspecialchars(file_get_contents($path) ?: "[Vacío]") ?></pre>
            <?php else: ?>
                <p class="missing">❌ Archivo NO existe</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

</body>
</html>
