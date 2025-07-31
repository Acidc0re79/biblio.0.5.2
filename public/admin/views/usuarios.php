<?php
$page_title = "Gestión de Usuarios";

// Lógica de búsqueda y paginación avanzada
$current_page_num = filter_input(INPUT_GET, 'page_num', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$perPage = 20;
$offset = ($current_page_num - 1) * $perPage;

$search_raw = $_GET['search'] ?? '';
$rango_raw = $_GET['rango'] ?? '';
$estado_raw = $_GET['estado'] ?? '';

$search = htmlspecialchars($search_raw, ENT_QUOTES, 'UTF-8');
$rango = htmlspecialchars($rango_raw, ENT_QUOTES, 'UTF-8');
$estado = htmlspecialchars($estado_raw, ENT_QUOTES, 'UTF-8');

$es_admin = ($_SESSION['rango'] === 'administrador');

// Construcción dinámica de filtros SQL
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(nickname LIKE :search" . ($es_admin ? " OR email LIKE :search" : "") . ")";
    $params[':search'] = "%$search%";
}
if (!empty($rango)) {
    $where[] = "rango = :rango";
    $params[':rango'] = $rango;
}
if (!empty($estado)) {
    $where[] = "estado_cuenta = :estado";
    $params[':estado'] = $estado;
}

$where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT id_usuario, nickname, email, rango, estado_cuenta, intentos_avatar, admin_theme FROM usuarios $where_sql ORDER BY id_usuario DESC LIMIT :limit OFFSET :offset";
$countSql = "SELECT COUNT(id_usuario) FROM usuarios $where_sql";

// Consultas preparadas
$stmtCount = $pdo->prepare($countSql);
foreach ($params as $key => $val) {
    $stmtCount->bindValue($key, $val);
}
$stmtCount->execute();
$totalUsers = $stmtCount->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-users-cog"></i> <?= htmlspecialchars($page_title) ?></h2>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <form method="get" class="filter-bar">
        <input type="hidden" name="page" value="usuarios">
        <input type="text" name="search" placeholder="Buscar por nickname <?= $es_admin ? 'o email' : '' ?>..." value="<?= $search ?>">
        <select name="rango">
            <option value="">Todos los Rangos</option>
            <option value="lector" <?= ($rango === 'lector') ? 'selected' : '' ?>>Lector</option>
            <option value="moderador" <?= ($rango === 'moderador') ? 'selected' : '' ?>>Moderador</option>
            <option value="administrador" <?= ($rango === 'administrador') ? 'selected' : '' ?>>Administrador</option>
        </select>
        <select name="estado">
            <option value="">Todos los Estados</option>
            <option value="activo" <?= ($estado === 'activo') ? 'selected' : '' ?>>Activo</option>
            <option value="pendiente" <?= ($estado === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
            <option value="suspendido" <?= ($estado === 'suspendido') ? 'selected' : '' ?>>Suspendido</option>
        </select>
        <button type="submit"><i class="fas fa-search"></i> Filtrar</button>
    </form>

    <div class="table-responsive">
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nickname</th>
                    <?php if ($es_admin): ?><th>Email</th><?php endif; ?>
                    <th>Rango</th>
                    <th>Estado</th>
                    <th class="text-center">Intentos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <?php $colspan = $es_admin ? 7 : 6; ?>
                    <tr><td colspan="<?= $colspan ?>">No se encontraron usuarios.</td></tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= $usuario['id_usuario'] ?></td>
                            <td><?= htmlspecialchars($usuario['nickname'] ?? 'N/A') ?></td>
                            <?php if ($es_admin): ?><td><?= htmlspecialchars($usuario['email'] ?? 'N/A') ?></td><?php endif; ?>
                            <td><span class="badge badge-<?= htmlspecialchars($usuario['rango'] ?? 'lector') ?>"><?= htmlspecialchars(ucfirst($usuario['rango'] ?? '')) ?></span></td>
                            <td><span class="badge status-<?= htmlspecialchars($usuario['estado_cuenta'] ?? 'pendiente') ?>"><?= htmlspecialchars(ucfirst($usuario['estado_cuenta'] ?? '')) ?></span></td>
                            <td class="text-center"><?= htmlspecialchars($usuario['intentos_avatar'] ?? '0') ?></td>
                            <td class="actions-cell">
                                <a href="index.php?page=editar_usuario&id=<?= $usuario['id_usuario'] ?>" class="btn-action btn-edit" title="Editar Usuario"><i class="fas fa-pencil-alt"></i></a>
                                <?php if ($usuario['estado_cuenta'] === 'pendiente' && $es_admin): ?>
                                    <form action="<?= BASE_URL ?>form-handler.php" method="POST" class="inline-form" onsubmit="return confirm('¿Activar este usuario?')">
                                        <input type="hidden" name="action" value="activar_usuario_admin">
                                        <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                        <button type="submit" class="btn-action btn-success" title="Activar Usuario"><i class="fas fa-check"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form action="<?= BASE_URL ?>form-handler.php?<?= http_build_query($_GET) ?>" method="POST" class="inline-form" onsubmit="return confirm('¿Resetear intentos avatar?')">
                                    <input type="hidden" name="action" value="resetear_intentos_avatar">
                                    <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                    <button type="submit" class="btn-action btn-reset" title="Resetear Intentos"><i class="fas fa-sync-alt"></i></button>
                                </form>
                                <?php if ($es_admin && $_SESSION['user_id'] != $usuario['id_usuario']): ?>
                                    <button onclick="abrirModalEliminar(<?= $usuario['id_usuario'] ?>, '<?= htmlspecialchars($usuario['nickname'] ?? '') ?>')" title="Eliminar Usuario"><i class="fas fa-trash-alt"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="index.php?page=usuarios&page_num=<?= $i ?>&<?= http_build_query(['search'=>$search, 'rango'=>$rango, 'estado'=>$estado]) ?>" class="<?= ($current_page_num == $i) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
