<?php
// Archivo NUEVO Y PRINCIPAL: /public/admin/index.php

// 1. CARGA CENTRALIZADA
// Todos los archivos esenciales se cargan aquí, una sola vez.
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/includes/auth.php'; // auth.php se encarga de la seguridad y sesión

// 2. ENRUTADOR SIMPLE
// Determinamos qué página cargar basándonos en el parámetro 'page' de la URL.
$page = $_GET['page'] ?? 'dashboard'; // Por defecto, cargamos el dashboard.

// 3. LISTA BLANCA DE PÁGINAS PERMITIDAS
// Por seguridad, solo permitimos cargar archivos que estén en esta lista.
$allowed_pages = [
    'dashboard'                 => __DIR__ . '/views/dashboard.php',
    'usuarios'                  => __DIR__ . '/views/usuarios.php',
    'editar_usuario'            => __DIR__ . '/views/editar_usuario.php',
    'banco_pruebas_gemini'      => __DIR__ . '/views/sistema/ia/banco_pruebas_gemini.php',
    // --- Configuración ---
    'config_general'            => __DIR__ . '/views/sistema/config/general.php',
    'config_ia'                 => __DIR__ . '/views/sistema/config/ia.php',
    // --- Logs consolidado ---
    'logs'                      => __DIR__ . '/views/logs.php',
	'logs-viewer'              => __DIR__ . '/views/logs-viewer.php',
    // --- Herramientas ---
    'dev_directory_lister'      => __DIR__ . '/views/sistema/tools/directory_lister.php'
];


// 4. SELECCIÓN DE LA VISTA
// Verificamos si la página solicitada es válida. Si no, mostramos un error 404.
if (array_key_exists($page, $allowed_pages) && file_exists($allowed_pages[$page])) {
    $view_file = $allowed_pages[$page];
} else {
    // Si la página no existe, preparamos para mostrar un error.
    http_response_code(404);
    $view_file = __DIR__ . '/views/404.php'; // Un archivo simple para errores 404.
}

// 5. RENDERIZACIÓN DE LA MAQUETA
// Ahora que sabemos qué archivo de contenido cargar, construimos la página completa.
?>
<!DOCTYPE html>
<html lang="es">

<?php include __DIR__ . '/includes/header.php'; ?>

<body>
    <div class="admin-wrapper">
        <?php include __DIR__ . '/includes/nav.php'; // La barra de navegación lateral ?>
        
        <main class="admin-main">
            <?php include $view_file; // ¡Aquí se carga el contenido dinámico! ?>
        </main>
    </div>

    <?php
    // Aquí podríamos incluir un footer o scripts JS globales si fuera necesario.
    // include __DIR__ . '/includes/footer.php';
    ?>
</body>
</html>