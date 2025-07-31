<?php
// NO MÁS REQUIRE O INCLUDE AQUÍ.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL);
    exit;
}

// Recogemos y saneamos los datos del formulario.
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$nickname = trim($_POST['nickname'] ?? ''); // ✅ NUEVA VARIABLE
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';

// --- VALIDACIONES ---
if (empty($nombre) || empty($apellido) || empty($nickname) || empty($email) || empty($password) || empty($fecha_nacimiento)) {
    $_SESSION['error_message'] = "Todos los campos del formulario son obligatorios.";
    header('Location: ' . BASE_URL . 'index.php?p=login_form');
    exit;
}
if ($password !== $password_confirm) {
    $_SESSION['error_message'] = "Las contraseñas no coinciden.";
    header('Location: ' . BASE_URL . 'index.php?p=login_form');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "El formato del email no es válido.";
    header('Location: ' . BASE_URL . 'index.php?p=login_form');
    exit;
}
if (strlen($password) < 8) {
    $_SESSION['error_message'] = "La contraseña debe tener al menos 8 caracteres.";
    header('Location: ' . BASE_URL . 'index.php?p=login_form');
    exit;
}

// --- PROCESAMIENTO EN LA BASE DE DATOS ---
try {
    // Verificamos si el email ya existe
    $stmt_email = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
    $stmt_email->execute([$email]);
    if ($stmt_email->fetch()) {
        $_SESSION['error_message'] = "El correo electrónico ya está en uso.";
        header('Location: ' . BASE_URL . 'index.php?p=login_form');
        exit;
    }
    
    // ✅ NUEVA VALIDACIÓN: Verificamos si el nickname ya existe
    $stmt_nick = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nickname = ?");
    $stmt_nick->execute([$nickname]);
    if ($stmt_nick->fetch()) {
        $_SESSION['error_message'] = "El nickname elegido ya está en uso. Por favor, elige otro.";
        header('Location: ' . BASE_URL . 'index.php?p=login_form');
        exit;
    }

    // Creación de la contraseña segura (sin cambios)
    $salt = bin2hex(random_bytes(16));
    $password_peppered = hash_hmac("sha256", $password, PEPPER);
    $hash_password = password_hash($password_peppered . $salt, PASSWORD_DEFAULT);

    // ✅ MODIFICACIÓN: Se añade `nickname` a la consulta INSERT.
    $stmt = $pdo->prepare(
        "INSERT INTO usuarios (nombre, apellido, nickname, email, hash_password, salt, rango, estado_cuenta, proveedor_oauth, fecha_nacimiento)
         VALUES (?, ?, ?, ?, ?, ?, 'lector', 'pendiente', 'local', ?)"
    );
    
    // ✅ MODIFICACIÓN: Se añade la variable $nickname al array de ejecución.
    if ($stmt->execute([$nombre, $apellido, $nickname, $email, $hash_password, $salt, $fecha_nacimiento])) {
        $new_user_id = $pdo->lastInsertId();
        log_system_event("Nuevo registro de usuario local exitoso.", ['id' => $new_user_id, 'email' => $email, 'nickname' => $nickname]);
        
        $_SESSION['success_message'] = "¡Registro completado! Tu cuenta está pendiente de aprobación por un administrador.";
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    } else {
        $_SESSION['error_message'] = "No se pudo completar el registro. Inténtalo de nuevo.";
        header('Location: ' . BASE_URL . 'index.php?p=login_form');
        exit;
    }

} catch (PDOException $e) {
    log_system_event("Error de BD en procesar_registro.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Ocurrió un error en el servidor. Por favor, inténtalo de nuevo más tarde.";
    header('Location: ' . BASE_URL . 'index.php?p=login_form');
    exit;
}