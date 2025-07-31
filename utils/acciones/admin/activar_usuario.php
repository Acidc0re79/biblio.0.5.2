<?php
require_once __DIR__ . '/../../../config/init.php';

if (!isset($_SESSION['rango']) || !in_array($_SESSION['rango'], ['moderador', 'administrador'])) {
    $_SESSION['error_message'] = 'No tienes permisos para activar usuarios.';
    header('Location: ' . BASE_URL . 'admin/index.php?page=usuarios');
    exit;
}

$id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);

if ($id_usuario) {
    $stmt = $pdo->prepare("UPDATE usuarios SET estado_cuenta = 'activo' WHERE id_usuario = :id_usuario");
    $stmt->execute([':id_usuario' => $id_usuario]);

    $_SESSION['success_message'] = 'Cuenta activada correctamente.';
} else {
    $_SESSION['error_message'] = 'Usuario no válido.';
}

header('Location: ' . BASE_URL . 'admin/index.php?page=usuarios');
exit;
