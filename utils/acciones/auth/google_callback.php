<?php
// No se necesita `require_once init.php` aquí porque el script que lo llama ya lo ha cargado.

/**
 * Genera un nickname único y anónimo.
 * Crea un apodo genérico y le añade un número aleatorio hasta encontrar uno disponible.
 *
 * @param PDO $pdo La conexión a la base de datos.
 * @return string Un nickname único y anónimo garantizado.
 */
function generarNicknameUnico($pdo) {
    // Usamos un prefijo genérico para no exponer ninguna información personal.
    $nicknameBase = 'Usuario_';
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nickname = ?");

    // Bucle para encontrar un nickname único
    while (true) {
        // Generamos un número aleatorio grande para mayor privacidad
        $numeroAleatorio = random_int(10000, 99999);
        $nicknameFinal = $nicknameBase . $numeroAleatorio;
        
        $stmt->execute([$nicknameFinal]);
        if ($stmt->fetchColumn() == 0) {
            // Si el conteo es 0, el nickname está libre
            return $nicknameFinal;
        }
        // Si (en el rarísimo caso) ya existe, el bucle simplemente se repetirá
        // y generará un nuevo número aleatorio.
    }
}


if (!isset($_GET['code'])) {
    log_system_event("Google Callback: No se recibió el 'code' de autorización.");
    $_SESSION['error_message'] = "Error de autenticación: no se recibió el código de Google.";
    header('Location: ' . BASE_URL . 'index.php?p=login_form');
    exit;
}

try {
    // --- 1. Intercambio de código por token de acceso ---
    $token_endpoint = 'https://oauth2.googleapis.com/token';
    $token_params = [
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $token_data = json_decode($response, true);
    if (isset($token_data['error'])) {
        throw new Exception('Error al obtener token de Google: ' . ($token_data['error_description'] ?? 'Error desconocido'));
    }
    $access_token = $token_data['access_token'];

    // --- 2. Obtener información del perfil del usuario ---
    $userinfo_endpoint = 'https://www.googleapis.com/oauth2/v3/userinfo';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userinfo_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    $response = curl_exec($ch);
    curl_close($ch);
    $profile_data = json_decode($response, true);

    // --- 3. Buscar o crear el usuario en la base de datos ---
    $email = $profile_data['email'];
    $google_id = $profile_data['sub'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? OR oauth_id = ?");
    $stmt->execute([$email, $google_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        log_system_event("Google Login: Usuario existente encontrado.", ['id_usuario' => $user['id_usuario'], 'email' => $email]);
        if (empty($user['oauth_id'])) {
            $update_stmt = $pdo->prepare("UPDATE usuarios SET oauth_id = ? WHERE id_usuario = ?");
            $update_stmt->execute([$google_id, $user['id_usuario']]);
            log_system_event("Cuenta local unificada con Google.", ['id_usuario' => $user['id_usuario']]);
        }
    } else {
        // 1. Generar un nickname único y ANÓNIMO
        $nickname = generarNicknameUnico($pdo);
        
        // 2. Modificar la consulta INSERT para incluir el nuevo nickname
        $stmt_insert = $pdo->prepare(
            "INSERT INTO usuarios (nombre, apellido, nickname, email, proveedor_oauth, oauth_id, avatar_google, estado_cuenta, rango, fecha_nacimiento) 
             VALUES (?, ?, ?, ?, 'google', ?, ?, 'activo', 'lector', NOW())"
        );
        $stmt_insert->execute([
            $profile_data['given_name'], 
            $profile_data['family_name'] ?? '', 
            $nickname,
            $email, 
            $google_id, 
            $profile_data['picture']
        ]);
        
        $user_id = $pdo->lastInsertId();
        $stmt_new = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
        $stmt_new->execute([$user_id]);
        $user = $stmt_new->fetch();
        
        log_system_event("Google Login: Nuevo usuario creado.", ['id_usuario' => $user_id, 'email' => $email, 'nickname' => $nickname]);
    }

    // --- 4. Comprobación final y creación de la sesión ---
    if ($user['estado_cuenta'] !== 'activo') {
        throw new Exception("Tu cuenta se encuentra en estado '{$user['estado_cuenta']}'. Contacta con un administrador.");
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id_usuario'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['apellido'] = $user['apellido'];
    $_SESSION['nickname'] = $user['nickname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['rango'] = $user['rango'];
    $_SESSION['tema'] = $user['tema'];

    if (empty($user['hash_password']) && !$user['ignorar_unificacion_pwd']) {
        $_SESSION['password_creation_required'] = true;
        log_system_event("Flag 'password_creation_required' activado.", ['id_usuario' => $user['id_usuario']]);
    }

    log_system_event("Login con Google exitoso.", ['id_usuario' => $user['id_usuario']]);
    header('Location: ' . BASE_URL . 'index.php?p=perfil');
    exit;

} catch (Exception $e) {
    log_system_event("Error CRÍTICO en google_callback.php.", ['error_message' => $e->getMessage()]);
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: ' . BASE_URL . 'index.php?p=login_form');
    exit;
}