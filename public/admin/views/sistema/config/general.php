<?php
// Archivo REFACTORIZADO: /public/admin/views/sistema/config/general.php

$page_title = "Configuración General";

// 1. LÓGICA DE LA PÁGINA
// Obtenemos todas las configuraciones generales de la base de datos.
// Usamos un array de claves para saber qué buscar.
$claves_config = [
    'puntos_por_registro',
    'intentos_avatar_default',
    'nombre_sitio',
    'maintenance_mode'
];
$placeholders = implode(',', array_fill(0, count($claves_config), '?'));

$stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ($placeholders)");
$stmt->execute($claves_config);
$configuraciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Crea un array asociativo [clave => valor]

?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-cogs"></i> <?= htmlspecialchars($page_title) ?></h2>
        <p>Ajusta los parámetros globales que afectan a toda la plataforma.</p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <form action="<?= BASE_URL ?>form-handler.php" method="POST" class="styled-form">
            <input type="hidden" name="action" value="actualizar_config_general">

            <div class="form-section">
                <h3><i class="fas fa-globe"></i> Ajustes del Sitio</h3>
                <div class="form-group">
                    <label for="nombre_sitio">Nombre del Sitio</label>
                    <input type="text" id="nombre_sitio" name="config[nombre_sitio]" value="<?= htmlspecialchars($configuraciones['nombre_sitio'] ?? 'BiblioSYS') ?>">
                </div>
                <div class="form-group">
                    <label for="maintenance_mode">Modo Mantenimiento</label>
                    <select id="maintenance_mode" name="config[maintenance_mode]">
                        <option value="off" <?= (($configuraciones['maintenance_mode'] ?? 'off') == 'off') ? 'selected' : '' ?>>Desactivado</option>
                        <option value="on" <?= (($configuraciones['maintenance_mode'] ?? 'off') == 'on') ? 'selected' : '' ?>>Activado</option>
                    </select>
                    <small>Cuando está activado, solo administradores y moderadores pueden acceder al sitio.</small>
                </div>
            </div>
            
            <div class="form-section">
                <h3><i class="fas fa-gamepad"></i> Gamificación</h3>
                <div class="form-group">
                    <label for="puntos_por_registro">Puntos por Registro</label>
                    <input type="number" id="puntos_por_registro" name="config[puntos_por_registro]" value="<?= htmlspecialchars($configuraciones['puntos_por_registro'] ?? 100) ?>">
                    <small>Cantidad de puntos que recibe un usuario nuevo al completar su registro.</small>
                </div>
                <div class="form-group">
                    <label for="intentos_avatar_default">Intentos de Avatar por Defecto</label>
                    <input type="number" id="intentos_avatar_default" name="config[intentos_avatar_default]" value="<?= htmlspecialchars($configuraciones['intentos_avatar_default'] ?? 5) ?>">
                    <small>Cantidad de intentos para generar avatares que recibe un usuario nuevo.</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Configuración</button>
            </div>
        </form>
    </div>
</div>