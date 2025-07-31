<?php
// Archivo REFORZADO Y FINAL: /utils/acciones/admin/actualizar_usuario.php

// Se asume que el form-handler ha iniciado el entorno con init.php

// --- Seguridad: Verificación de Permisos ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rango'], ['administrador', 'moderador'])) {
    header('Location: ' . BASE_URL);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/gestion/usuarios.php');
    exit;
}

// --- Recolección y Saneamiento de Datos ---
$id_usuario_a_editar = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
$nickname = trim($_POST['nickname'] ?? '');
$rango = $_POST['rango'] ?? 'lector';
$estado_cuenta = $_POST['estado_cuenta'] ?? 'pendiente';
$password = $_POST['password'] ?? '';
$puntos = filter_input(INPUT_POST, 'puntos', FILTER_VALIDATE_INT);
$intentos_avatar = filter_input(INPUT_POST, 'intentos_avatar', FILTER_VALIDATE_INT);

if (!$id_usuario_a_editar) {
    $_SESSION['error_message'] = "ID de usuario inválido.";
    header('Location: ' . BASE_URL . 'admin/gestion/usuarios.php');
    exit;
}

// --- Lógica de Construcción de Consulta Dinámica ---
$campos_a_actualizar = [];
$parametros = [];

// Campos que tanto Admin como Moderador pueden cambiar
$campos_a_actualizar[] = "nickname = :nickname";
$parametros[':nickname'] = $nickname;
$campos_a_actualizar[] = "estado_cuenta = :estado_cuenta";
$parametros[':estado_cuenta'] = $estado_cuenta;
$campos_a_actualizar[] = "puntos = :puntos";
$parametros[':puntos'] = $puntos;
$campos_a_actualizar[] = "intentos_avatar = :intentos_avatar";
$parametros[':intentos_avatar'] = $intentos_avatar;

// RBAC: Rango (Moderador no puede ascender a Admin o modificar a otro Admin)
if ($_SESSION['rango'] === 'administrador') {
    $campos_a_actualizar[] = "rango = :rango";
    $parametros[':rango'] = $rango;
} elseif ($rango !== 'administrador') { 
    $campos_a_actualizar[] = "rango = :rango";
    $parametros[':rango'] = $rango;
}

// RBAC: Campos de Información Personal (Solo Admin)
if ($_SESSION['rango'] === 'administrador') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $campos_a_actualizar[] = "nombre = :nombre";
    $parametros[':nombre'] = $nombre;
    $campos_a_actualizar[] = "apellido = :apellido";
    $parametros[':apellido'] = $apellido;
}

// Actualización de Contraseña (si se proporcionó una nueva)
if (!empty($password)) {
    $salt = bin2hex(random_bytes(16));
    $password_peppered = hash_hmac("sha256", $password, PEPPER);
    $hash_password = password_hash($password_peppered . $salt, PASSWORD_DEFAULT);
    
    $campos_a_actualizar[] = "hash_password = :hash_password";
    $parametros[':hash_password'] = $hash_password;
    $campos_a_actualizar[] = "salt = :salt";
    $parametros[':salt'] = $salt;
}

$parametros[':id_usuario'] = $id_usuario_a_editar;

try {
    // Verificamos si el nickname ya está en uso por OTRO usuario
    $stmt_nick = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nickname = ? AND id_usuario != ?");
    $stmt_nick->execute([$nickname, $id_usuario_a_editar]);
    if ($stmt_nick->fetch()) {
        throw new Exception("El nickname elegido ya está en uso por otro usuario.");
    }
    
    $sql = "UPDATE usuarios SET " . implode(', ', $campos_a_actualizar) . " WHERE id_usuario = :id_usuario";
    $stmt_update = $pdo->prepare($sql);
    
    if ($stmt_update->execute($parametros)) {
        log_system_event("Perfil de usuario actualizado desde panel.", ['admin_id' => $_SESSION['user_id'], 'usuario_editado_id' => $id_usuario_a_editar]);
        $_SESSION['success_message'] = "Usuario actualizado correctamente.";
    } else {
        $_SESSION['error_message'] = "No se pudo actualizar el usuario.";
    }

} catch (Exception $e) {
    log_system_event("Error al actualizar usuario desde panel.", ['error' => $e->getMessage()]);
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: ' . BASE_URL . 'admin/gestion/editar_usuario.php?id=' . $id_usuario_a_editar);
exit;