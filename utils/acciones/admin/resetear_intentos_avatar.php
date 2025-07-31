<?php
require_once __DIR__ . '/../../../config/init.php';

if (!isset($_SESSION['rango']) || !in_array($_SESSION['rango'], ['moderador', 'administrador'])) {
    $_SESSION['error_message'] = 'No tienes permisos suficientes.';
    header('Location: ' . BASE_URL . 'admin/index.php?page=usuarios');
    exit;
}

$id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);

if ($id_usuario) {
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET intentos_avatar = 0 WHERE id_usuario = :id_usuario");
        $stmt->execute([':id_usuario' => $id_usuario]);
        $_SESSION['success_message'] = 'Intentos de avatar reseteados correctamente.';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error al resetear intentos: ' . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = 'ID de usuario no válido.';
}

header('Location: ' . BASE_URL . 'admin/index.php?page=usuarios');
exit;
