<?php
// Archivo COMPLETO Y CORREGIDO: /utils/helpers.php

/**
 * Obtiene la URL completa y segura de la imagen de avatar de un usuario.
 *
 * @param string|null $avatar_filename El nombre del archivo del avatar desde la BD.
 * @param string $email El email del usuario, necesario para el fallback a Gravatar.
 * @return string La URL completa y segura de la imagen del avatar.
 */
function get_avatar_url($avatar_filename, $email)
{
    if (!empty($avatar_filename)) {
        return BASE_URL . 'assets/img/avatars/users/' . htmlspecialchars($avatar_filename);
    }

    $email_limpio = strtolower(trim($email));
    $gravatar_hash = md5($email_limpio);
    return "https://www.gravatar.com/avatar/" . $gravatar_hash . "?d=mp&s=150";
}

/**
 * Guarda datos binarios de una imagen en un archivo, crea una miniatura
 * y devuelve las rutas.
 *
 * @param string $datosImagenBinarios Los datos crudos de la imagen.
 * @param string $nombreArchivo El nombre de archivo único para guardar la imagen.
 * @return array ['success' => bool, 'data' => 'mensaje_error' o array con rutas]
 */
function guardarYCrearThumbnail($datosImagenBinarios, $nombreArchivo) {
    $rutaCompleta = ROOT_PATH . '/public/assets/img/avatars/users/' . $nombreArchivo;
    $rutaThumbnail = ROOT_PATH . '/public/assets/img/avatars/thumbs/users/' . $nombreArchivo;
    $urlCompleta = BASE_URL . 'assets/img/avatars/users/' . $nombreArchivo;

    if (!file_put_contents($rutaCompleta, $datosImagenBinarios)) {
        log_ia_event('Error crítico al guardar la imagen principal.', ['ruta' => $rutaCompleta]);
        return ['success' => false, 'data' => 'Error del servidor al guardar la imagen.'];
    }

    try {
        $imagenOriginal = imagecreatefromstring($datosImagenBinarios);
        if ($imagenOriginal === false) throw new Exception("GD no pudo procesar los datos de la imagen.");

        $anchoOriginal = imagesx($imagenOriginal);
        $altoOriginal = imagesy($imagenOriginal);
        $anchoThumbnail = 150;
        $altoThumbnail = floor($altoOriginal * ($anchoThumbnail / $anchoOriginal));

        $thumbnail = imagecreatetruecolor($anchoThumbnail, $altoThumbnail);
        imagecopyresampled($thumbnail, $imagenOriginal, 0, 0, 0, 0, $anchoThumbnail, $altoThumbnail, $anchoOriginal, $altoOriginal);
        imagepng($thumbnail, $rutaThumbnail);
        imagedestroy($imagenOriginal);
        imagedestroy($thumbnail);

        return [
            'success' => true,
            'data' => [
                'ruta_completa' => $rutaCompleta,
                'ruta_thumbnail' => $rutaThumbnail,
                'url_completa' => $urlCompleta
            ]
        ];
    } catch (Exception $e) {
        log_ia_event('Error crítico al crear el thumbnail.', ['error' => $e->getMessage()]);
        return ['success' => false, 'data' => 'Error del servidor al procesar la imagen.'];
    }
}

/**
 * Obtiene una lista paginada y filtrada de usuarios para el panel de administración.
 * VERSIÓN 3 (Definitiva): Soluciona el error 'Invalid parameter number'.
 *
 * @param PDO $pdo La conexión a la base de datos.
 * @param int $pagina_actual La página que se está visualizando.
 * @param int $usuarios_por_pagina Cuántos usuarios mostrar por página.
 * @param array $filtros Un array asociativo con los filtros a aplicar.
 * @return array Un array con 'usuarios' y 'total_paginas'.
 */
function get_usuarios_paginados($pdo, $pagina_actual = 1, $usuarios_por_pagina = 15, $filtros = []) {
    $pagina_actual = (int) $pagina_actual > 0 ? (int) $pagina_actual : 1;
    $usuarios_por_pagina = (int) $usuarios_por_pagina;
    $offset = ($pagina_actual - 1) * $usuarios_por_pagina;

    $sql_base = " FROM usuarios";
    $where_clauses = [];
    $params = [];

    if (!empty($filtros['busqueda'])) {
        $where_clauses[] = "(nombre LIKE ? OR apellido LIKE ? OR email LIKE ? OR nickname LIKE ?)";
        $searchTerm = '%' . $filtros['busqueda'] . '%';
        // Añadimos el parámetro 4 veces, una para cada campo de búsqueda
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }
    if (!empty($filtros['rango'])) {
        $where_clauses[] = "rango = ?";
        $params[] = $filtros['rango'];
    }
    if (!empty($filtros['estado'])) {
        $where_clauses[] = "estado_cuenta = ?";
        $params[] = $filtros['estado'];
    }

    $sql_where = "";
    if (!empty($where_clauses)) {
        $sql_where = " WHERE " . implode(" AND ", $where_clauses);
    }

    $stmt_total = $pdo->prepare("SELECT COUNT(*) " . $sql_base . $sql_where);
    $stmt_total->execute($params);
    $total_usuarios = $stmt_total->fetchColumn();
    $total_paginas = ($usuarios_por_pagina > 0) ? ceil($total_usuarios / $usuarios_por_pagina) : 1;

    // A los parámetros existentes, añadimos los de paginación
    $params[] = $usuarios_por_pagina;
    $params[] = $offset;

    $sql_final = "SELECT * " . $sql_base . $sql_where . " ORDER BY fecha_registro DESC LIMIT ? OFFSET ?";
    
    $stmt_usuarios = $pdo->prepare($sql_final);
    $stmt_usuarios->execute($params);
    $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

    return [
        'usuarios' => $usuarios,
        'total_paginas' => (int)$total_paginas,
        'pagina_actual' => (int)$pagina_actual
    ];
}