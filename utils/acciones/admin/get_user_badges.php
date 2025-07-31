<?php
// Archivo COMPLETO Y CORREGIDO: /utils/acciones/admin/get_user_badges.php

// Se asume que ajax-handler.php ha verificado los permisos de admin/mod.

// La acción ahora viene por GET desde el JavaScript
// CAMBIO CLAVE: Se usa INPUT_GET en lugar de INPUT_POST
$id_usuario = filter_input(INPUT_GET, 'id_usuario', FILTER_VALIDATE_INT);

if (!$id_usuario) {
    // Este es el error que estabas viendo.
    echo json_encode(['success' => false, 'message' => 'ID de usuario no proporcionado.']);
    exit;
}

try {
    // 1. Obtenemos TODAS las insignias
    $stmt_todas = $pdo->query("SELECT id_insignia, nombre, descripcion, imagen, pista FROM insignias ORDER BY nombre ASC");
    $todas_las_insignias = $stmt_todas->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtenemos los IDs de las insignias que el usuario YA TIENE
    $stmt_usuario = $pdo->prepare("SELECT id_insignia FROM usuarios_insignias WHERE id_usuario = ?");
    $stmt_usuario->execute([$id_usuario]);
    $insignias_del_usuario = $stmt_usuario->fetchAll(PDO::FETCH_COLUMN);

    // 3. Combinamos los datos para el frontend
    $resultado = [];
    foreach ($todas_las_insignias as $insignia) {
        $tiene_insignia = in_array($insignia['id_insignia'], $insignias_del_usuario);
        
        $descripcion_final = $tiene_insignia ? ($insignia['descripcion'] ?? '') : ($insignia['pista'] ?? 'Sigue explorando para desbloquear.');

        $resultado[] = [
            'id_insignia'    => $insignia['id_insignia'],
            'nombre'         => $insignia['nombre'],
            'descripcion'    => $descripcion_final,
            'imagen_thumb'   => BASE_URL . 'assets/img/insignias/thumbs/' . $insignia['imagen'],
            'imagen_full'    => BASE_URL . 'assets/img/insignias/' . $insignia['imagen'],
            'tiene_insignia' => $tiene_insignia
        ];
    }

    echo json_encode(['success' => true, 'insignias' => $resultado]);

} catch (PDOException $e) {
    log_system_event("Error de BD al obtener insignias de usuario para admin.", ['error' => $e->getMessage(), 'user_id' => $id_usuario]);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos. Consulta los logs.']);
}