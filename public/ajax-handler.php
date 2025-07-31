<?php
// Archivo: /public/ajax-handler.php

require_once __DIR__ . '/../config/init.php';

// 🔒 Seguridad: no mostrar errores en producción
ini_set('display_errors', 0);
error_reporting(E_ERROR);

// 🔐 Solo aceptamos peticiones AJAX autenticadas
session_start();
header('Content-Type: application/json');

// ✅ Reemplazo moderno y seguro de FILTER_SANITIZE_STRING
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$action = is_string($action) ? trim($action) : '';

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Acción no especificada.']);
    exit;
}

switch ($action) {
    case 'load_log_json':
        if (!isset($_SESSION['user_id']) || ($_SESSION['rango'] ?? '') !== 'administrador') {
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            exit;
        }

        // Sanitizamos la entrada 'log'
        $log_key = $_GET['log'] ?? '';
        $log_key = is_string($log_key) ? trim($log_key) : '';

        // Mapeamos los logs válidos
        $log_map = [
            'system'   => LOG_PATH . 'system_debug.json',
            'api'      => LOG_PATH . 'api_debug.json',
            'handler'  => LOG_PATH . 'handler_debug.json',
            'frontend' => LOG_PATH . 'frontend_debug.json'
        ];

        if (!array_key_exists($log_key, $log_map)) {
            echo json_encode(['success' => false, 'message' => 'Log no válido.']);
            exit;
        }

        $log_path = $log_map[$log_key];

        if (!file_exists($log_path)) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        $log_entries = [];
        foreach (file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    // Buscamos el primer { para extraer solo el JSON embebido
    $json_start = strpos($line, '{');
    if ($json_start !== false) {
        $json_part = substr($line, $json_start);
        $entry = json_decode($json_part, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $log_entries[] = $entry;
        }
    }
}

        echo json_encode(['success' => true, 'data' => $log_entries]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'La acción solicitada no es válida.']);
        exit;
}
