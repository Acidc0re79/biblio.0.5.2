<?php
// Se asume que ajax-handler.php ha verificado los permisos de admin/mod.

$id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
// Recibimos los IDs de las insignias como un array. Si no se envía nada, es un array vacío.
$insignias_ids = $_POST['insignias_ids'] ?? [];

if (!$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario no válido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Borramos TODAS las insignias actuales del usuario. Es la forma más simple y segura de sincronizar.
    $stmt_delete = $pdo->prepare("DELETE FROM usuarios_insignias WHERE id_usuario = ?");
    $stmt_delete->execute([$id_usuario]);

    // 2. Si se enviaron insignias, las insertamos una por una.
    if (!empty($insignias_ids)) {
        $stmt_insert = $pdo->prepare("INSERT INTO usuarios_insignias (id_usuario, id_insignia) VALUES (?, ?)");
        foreach ($insignias_ids as $id_insignia) {
            $stmt_insert->execute([$id_usuario, $id_insignia]);
        }
    }

    $pdo->commit();
    log_system_event("Insignias de usuario actualizadas por admin.", ['admin_id' => $_SESSION['user_id'], 'usuario_afectado_id' => $id_usuario]);
    echo json_encode(['success' => true, 'message' => 'Insignias del usuario actualizadas correctamente.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    log_system_event("Error de BD al actualizar insignias de usuario.", ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos al guardar los cambios.']);
}