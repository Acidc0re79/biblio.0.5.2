<?php
// Se asume que ajax-handler.php ha iniciado el entorno.

// --- Validaciones y Seguridad ---
$sub_action = $_POST['sub_action'] ?? $_GET['sub_action'] ?? '';
if (empty($sub_action)) {
    echo json_encode(['success' => false, 'message' => 'Acción no especificada.']);
    exit;
}

// Lógica de permisos
$es_admin_o_mod = isset($_SESSION['user_id']) && in_array($_SESSION['rango'], ['administrador', 'moderador']);

// --- Router de Sub-Acciones ---
switch ($sub_action) {
    case 'get_gallery':
        get_avatar_gallery($pdo, $es_admin_o_mod);
        break;
    case 'delete_avatar':
        delete_avatar($pdo, $es_admin_o_mod);
        break;
    case 'select_avatar':
        select_avatar($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción desconocida.']);
        exit;
}

// --- Funciones de Lógica ---

function get_avatar_gallery($pdo, $es_admin_o_mod) {
    $id_usuario = filter_input(INPUT_GET, 'id_usuario', FILTER_VALIDATE_INT);
    if (!$id_usuario) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario no válido.']);
        exit;
    }

    // Un usuario solo puede ver su propia galería, un admin/mod puede ver cualquiera.
    if (!$es_admin_o_mod && $id_usuario != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver esta galería.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, nombre_archivo FROM usuarios_avatares WHERE id_usuario = ? ORDER BY fecha_creacion DESC");
    $stmt->execute([$id_usuario]);
    $avatares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($avatares as &$avatar) {
        $avatar['url_thumb'] = BASE_URL . 'assets/img/avatars/thumbs/users/' . $avatar['nombre_archivo'];
        $avatar['url_full'] = BASE_URL . 'assets/img/avatars/users/' . $avatar['nombre_archivo'];
    }

    echo json_encode(['success' => true, 'avatares' => $avatares]);
}

function delete_avatar($pdo, $es_admin_o_mod) {
    $id_avatar = filter_input(INPUT_POST, 'id_avatar', FILTER_VALIDATE_INT);
    if (!$id_avatar) {
        echo json_encode(['success' => false, 'message' => 'ID de avatar no válido.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $stmt_get = $pdo->prepare("SELECT id_usuario, nombre_archivo FROM usuarios_avatares WHERE id = ?");
        $stmt_get->execute([$id_avatar]);
        $avatar = $stmt_get->fetch(PDO::FETCH_ASSOC);

        if (!$avatar) throw new Exception("Avatar no encontrado.");
        
        // Comprobación de permisos: O eres admin/mod, O el avatar es tuyo.
        if (!$es_admin_o_mod && $avatar['id_usuario'] != $_SESSION['user_id']) {
            throw new Exception("No tienes permisos para eliminar este avatar.");
        }

        $stmt_delete = $pdo->prepare("DELETE FROM usuarios_avatares WHERE id = ?");
        $stmt_delete->execute([$id_avatar]);

        $ruta_completa = ROOT_PATH . '/public/assets/img/avatars/users/' . $avatar['nombre_archivo'];
        $ruta_thumbnail = ROOT_PATH . '/public/assets/img/avatars/thumbs/users/' . $avatar['nombre_archivo'];
        if (file_exists($ruta_completa)) unlink($ruta_completa);
        if (file_exists($ruta_thumbnail)) unlink($ruta_thumbnail);
        
        $pdo->commit();
        log_system_event("Avatar eliminado.", ['actor_id' => $_SESSION['user_id'], 'avatar_id' => $id_avatar]);
        echo json_encode(['success' => true, 'message' => 'Avatar eliminado.']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function select_avatar($pdo) {
    $id_avatar = filter_input(INPUT_POST, 'id_avatar', FILTER_VALIDATE_INT);
    if (!$id_avatar) {
        echo json_encode(['success' => false, 'message' => 'ID de avatar no válido.']);
        exit;
    }
    
    $id_usuario = $_SESSION['user_id'];
    
    // Obtenemos el nombre del archivo y verificamos que pertenezca al usuario
    $stmt_get = $pdo->prepare("SELECT nombre_archivo FROM usuarios_avatares WHERE id = ? AND id_usuario = ?");
    $stmt_get->execute([$id_avatar, $id_usuario]);
    $avatar = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if ($avatar) {
        $stmt_update = $pdo->prepare("UPDATE usuarios SET avatar_seleccionado = ? WHERE id_usuario = ?");
        $stmt_update->execute([$avatar['nombre_archivo'], $id_usuario]);
        
        $_SESSION['avatar_seleccionado'] = $avatar['nombre_archivo']; // Actualizamos la sesión
        echo json_encode(['success' => true, 'message' => 'Avatar seleccionado.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo seleccionar el avatar.']);
    }
}