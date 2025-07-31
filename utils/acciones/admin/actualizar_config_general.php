<?php
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rango'], ['administrador', 'moderador'])) {
    header('Location: ' . BASE_URL);
    exit;
}

if (!isset($_POST['config']) || !is_array($_POST['config'])) {
    $_SESSION['error_message'] = "No se recibieron datos de configuración válidos.";
    header('Location: ' . BASE_URL . 'admin/sistema/config/general.php');
    exit;
}

$configuraciones = $_POST['config'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE configuracion SET valor = :valor WHERE clave = :clave");

    foreach ($configuraciones as $clave => $valor) {
        $stmt->execute(['valor' => $valor, 'clave' => $clave]);
    }

    $pdo->commit();

    log_system_event("Configuración general actualizada por el admin.", ['admin_id' => $_SESSION['user_id']]);
    $_SESSION['success_message'] = "La configuración general del sitio se ha actualizado correctamente.";

} catch (PDOException $e) {
    $pdo->rollBack();
    log_system_event("Error CRÍTICO al actualizar config general.", ['error' => $e->getMessage()]);
    $_SESSION['error_message'] = "Error de base de datos al actualizar la configuración.";
}

header('Location: ' . BASE_URL . 'admin/sistema/config/general.php');
exit;