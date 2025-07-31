<?php
// Archivo COMPLETO Y FINAL: /public/paginas/perfil.php

// Seguridad: Redirigir si no hay una sesión activa.
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php?p=login_form');
    exit;
}

// ---- Carga de Datos del Usuario ----
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: ' . BASE_URL);
        exit;
    }
} catch (PDOException $e) {
    log_system_event("Error de BD al cargar datos del perfil.", ['id_usuario' => $_SESSION['user_id'], 'error' => $e->getMessage()]);
    die("Error: No se pudieron cargar los datos del perfil.");
}

// Usamos la función helper para el avatar
$avatar_url = get_avatar_url($user['avatar_seleccionado'], $user['email']);

?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/perfil_styles.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<div class="container profile-container">
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 text-center">
            <div class="profile-avatar-wrapper">
                <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar del usuario" class="profile-avatar img-fluid rounded-circle">
                <div class="avatar-overlay">
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#avatarManagerModal">
                        <i class="fas fa-pencil-alt"></i> Editar
                    </button>
                </div>
            </div>
            <h4 class="mt-3"><?php echo htmlspecialchars($user['nickname'] ?? ''); ?></h4>
            <p class="text-muted"><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></p>
            <span class="badge bg-primary"><?php echo ucfirst(htmlspecialchars($user['rango'])); ?></span>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h4>Editar Perfil</h4></div>
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>form-handler.php" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label for="nickname" class="form-label">Nickname</label>
                            <input type="text" class="form-control" id="nickname" name="nickname" value="<?php echo htmlspecialchars($user['nickname'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Sobre mí</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($user['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tema" class="form-label">Tema Visual del Sitio</label>
                            <select class="form-select" id="tema" name="tema">
                                <option value="default" <?php echo ($user['tema'] === 'default') ? 'selected' : ''; ?>>Claro</option>
                                <option value="neon_dark" <?php echo ($user['tema'] === 'neon_dark') ? 'selected' : ''; ?>>Oscuro Neón</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// --- Inclusión de los Modales ---
// El modal principal para gestionar avatares
include_once ROOT_PATH . '/public/includes/modals/modal_avatar_manager.php';
// Nuestro nuevo componente universal de galería
include_once ROOT_PATH . '/public/includes/modals/modal_galeria_avatares.php';
// El modal simple para ver una imagen en grande
include_once ROOT_PATH . '/public/includes/modals/modal_view_avatar.php';

// El modal de crear contraseña solo se carga si el flag de sesión existe.
if (isset($_SESSION['password_creation_required'])) {
    include_once ROOT_PATH . '/public/includes/modals/modal_create_password.php';
}
?>

<script src="<?php echo BASE_URL; ?>assets/js/perfil-main.js?v=<?php echo time(); ?>"></script>