<?php
require_once __DIR__ . '/../../../config/init.php';

if (!isset($_SESSION['rango']) || $_SESSION['rango'] !== 'administrador') {
    $_SESSION['error_message'] = 'No tienes permisos para eliminar usuarios.';
    header('Location: ' . BASE_URL . 'admin/index.php?page=usuarios');
    exit;
}

$id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);

if ($id_usuario && $id_usuario != $_SESSION['user_id']) {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = :id_usuario");
    $stmt->execute([':id_usuario' => $id_usuario]);

    $_SESSION['success_message'] = 'Usuario eliminado correctamente.';
} else {
    $_SESSION['error_message'] = 'Operación no válida.';
}

header('Location: ' . BASE_URL . 'admin/index.php?page=usuarios');
exit;
