<?php
// Archivo CORREGIDO Y FINAL: /public/form-handler.php

// 1. Carga la configuración y el entorno de la aplicación.
require_once __DIR__ . '/../config/init.php';

// 2. Obtiene la acción solicitada.
$action = $_POST['action'] ?? '';

// 3. Lista blanca de acciones permitidas y sus scripts correspondientes.
$allowed_actions = [
    // Acciones de Autenticación
    'login'           => ROOT_PATH . '/utils/acciones/auth/procesar_login.php',
    'register'        => ROOT_PATH . '/utils/acciones/auth/procesar_registro.php',
    'logout'          => ROOT_PATH . '/utils/acciones/auth/procesar_logout.php',
    
    // Acciones de Perfil de Usuario
    'update_profile'  => ROOT_PATH . '/utils/acciones/perfil/actualizar_perfil.php',
    'crear_password'  => ROOT_PATH . '/utils/acciones/perfil/crear_password.php',

    // Acciones de Administración
    // ✅ CORRECCIÓN: La clave ahora es 'actualizar_usuario_admin' y apunta al script correcto.
    'actualizar_usuario_admin'  => ROOT_PATH . '/utils/acciones/admin/actualizar_usuario.php',
    'eliminar_usuario_admin'    => ROOT_PATH . '/utils/acciones/admin/eliminar_usuario.php',
	'activar_usuario_admin'     => ROOT_PATH . '/utils/acciones/admin/activar_usuario.php',
	'resetear_intentos_avatar'  => ROOT_PATH . '/utils/acciones/admin/resetear_intentos_avatar.php',
	'restringir_avatares'       => ROOT_PATH . '/utils/acciones/admin/restringir_avatares.php',
    'actualizar_config_ia'      => ROOT_PATH . '/utils/acciones/admin/actualizar_config_ia.php',
	'actualizar_config_general' => ROOT_PATH . '/utils/acciones/admin/actualizar_config_general.php',
];

// 4. Verifica y ejecuta la acción.
if (array_key_exists($action, $allowed_actions)) {
    require_once $allowed_actions[$action];
} else {
    log_system_event("form-handler.php: Se recibió una acción no válida.", ['accion_recibida' => $action]);
    $_SESSION['error_message'] = 'Error: La acción solicitada no es válida.';
    header('Location: ' . BASE_URL);
    exit;
}