<?php
// Se asume que form-handler.php ya cargó init.php

// --- Seguridad y Permisos ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rango'], ['administrador', 'moderador'])) {
    header('Location: ' . BASE_URL);
    exit;
}

// --- Validación de Entradas ---
$id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
$return_url = filter_input(INPUT_POST, 'return_url', FILTER_SANITIZE_URL);

if (!$id_usuario) {
    $_SESSION['error_message'] = "ID de usuario inválido.";
    header('Location: ' . BASE_URL . 'admin/gestion/usuarios.php');
    exit;
}

// --- Lógica de la Base de Datos ---
try {
    // Buscamos el máximo de intentos desde la configuración para la sanción
    $stmt_config = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'intentos_max_avatar'");
    $max_intentos = $stmt_config->fetchColumn() ?: 100; // Un valor por defecto por si acaso

    $stmt = $pdo->prepare("UPDATE usuarios SET intentos_avatar = ? WHERE id_usuario = ?");
    $stmt->execute([$max_intentos, $id_usuario]);
    $_SESSION['success_message'] = "Se ha restringido la generación de avatares para el usuario.";
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error al restringir los avatares.";
    log_system_event("Error restringiendo avatares", ['error' => $e->getMessage()]);
}

// --- Redirección Inteligente ---
$redirect_location = BASE_URL . 'admin/gestion/usuarios.php'; // Destino por defecto

// Si se proveyó una URL de retorno y es una URL local segura...
if ($return_url && strpos($return_url, '/') === 0) {
    $redirect_location = $return_url; // ...la usamos.
}

header('Location: ' . $redirect_location);
exit;