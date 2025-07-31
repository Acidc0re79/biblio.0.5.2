<?php
// auth.php ya está incluido por el form-handler, pero lo verificamos por seguridad
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rango'], ['administrador', 'moderador'])) {
    // Si se accede directamente, se redirige.
    header('Location: ' . BASE_URL);
    exit;
}

if (!isset($_POST['config']) || !is_array($_POST['config'])) {
    $_SESSION['error_message'] = "No se recibieron datos de configuración válidos.";
    header('Location: ' . BASE_URL . 'admin/sistema/config/ia.php');
    exit;
}

$configuraciones = $_POST['config'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE ia_configuracion SET valor = :valor WHERE clave = :clave");

    foreach ($configuraciones as $clave => $valor) {
        $stmt->execute(['valor' => $valor, 'clave' => $clave]);
    }

    $pdo->commit();

    log_system_event("Configuración de IA actualizada por el admin.", ['admin_id' => $_SESSION['user_id']]);
    $_SESSION['success_message'] = "La configuración del motor de IA se ha actualizado correctamente.";

} catch (PDOException $e) {
    $pdo->rollBack();
    log_system_event("Error CRÍTICO al actualizar config IA.", ['error' => $e->getMessage()]);
    $_SESSION['error_message'] = "Error de base de datos al actualizar la configuración.";
}

header('Location: ' . BASE_URL . 'admin/sistema/config/ia.php');
exit;