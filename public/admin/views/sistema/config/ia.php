<?php
// Archivo REFACTORIZADO: /public/admin/views/sistema/config/ia.php

$page_title = "Configuración de Inteligencia Artificial";

// 1. LÓGICA DE LA PÁGINA: Cargar todos los datos necesarios

// a) Configuraciones Generales de IA
$claves_config_ia = ['max_consecutive_errors'];
$placeholders = implode(',', array_fill(0, count($claves_config_ia), '?'));
$stmt_config = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ($placeholders)");
$stmt_config->execute($claves_config_ia);
$config_ia = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

// b) Proveedores
$stmt_prov = $pdo->query("SELECT * FROM ia_proveedores ORDER BY nombre_proveedor");
$proveedores = $stmt_prov->fetchAll(PDO::FETCH_ASSOC);

// c) Modelos (con el nombre del proveedor)
$stmt_modelos = $pdo->query("
    SELECT m.*, p.nombre_proveedor 
    FROM ia_modelos m
    JOIN ia_proveedores p ON m.id_proveedor = p.id_proveedor
    ORDER BY p.nombre_proveedor, m.nombre_modelo
");
$modelos = $stmt_modelos->fetchAll(PDO::FETCH_ASSOC);

// d) Personalidades de Lyra
$stmt_pers = $pdo->query("SELECT * FROM lyra_personalidades ORDER BY nombre_legible");
$personalidades = $stmt_pers->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-brain"></i> <?= htmlspecialchars($page_title) ?></h2>
        <p>Gestiona los proveedores, modelos y personalidades que componen el motor de IA "Lyra".</p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <div class="tabs-container">
        <div class="tab-headers">
            <button class="tab-link active" data-tab="tab-general">General</button>
            <button class="tab-link" data-tab="tab-modelos">Modelos</button>
            <button class="tab-link" data-tab="tab-personalidades">Personalidades</button>
            <button class="tab-link" data-tab="tab-proveedores">Proveedores</button>
        </div>

        <div class="tab-content active" id="tab-general">
            <form action="<?= BASE_URL ?>form-handler.php" method="POST" class="styled-form">
                <input type="hidden" name="action" value="actualizar_config_ia">
                <div class="form-section">
                    <h3><i class="fas fa-heartbeat"></i> Failsafe y Resiliencia</h3>
                    <div class="form-group">
                        <label for="max_consecutive_errors">Máximos Errores Consecutivos</label>
                        <input type="number" id="max_consecutive_errors" name="config[max_consecutive_errors]" value="<?= htmlspecialchars($config_ia['max_consecutive_errors'] ?? 5) ?>">
                        <small>Número de fallos seguidos antes de que un modelo se desactive automáticamente (estado 'error').</small>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Configuración General</button>
                </div>
            </form>
        </div>

        <div class="tab-content" id="tab-modelos">
            <h3><i class="fas fa-puzzle-piece"></i> Modelos de IA</h3>
            <p>Define los modelos específicos de cada proveedor, su tipo y estado.</p>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Nombre Modelo</th>
                            <th>Tipo</th>
                            <th>Timeout (s)</th>
                            <th>Estado</th>
                            <th>Errores</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modelos as $modelo): ?>
                            <tr>
                                <td><?= $modelo['id_modelo'] ?></td>
                                <td><?= htmlspecialchars($modelo['nombre_proveedor']) ?></td>
                                <td><?= htmlspecialchars($modelo['nombre_legible']) ?></td>
                                <td><?= htmlspecialchars($modelo['tipo_modelo']) ?></td>
                                <td><?= htmlspecialchars($modelo['timeout_seconds']) ?></td>
                                <td><span class="badge status-<?= htmlspecialchars($modelo['estado']) ?>"><?= htmlspecialchars(ucfirst($modelo['estado'])) ?></span></td>
                                <td><?= htmlspecialchars($modelo['error_count']) ?></td>
                                <td>
                                    <a href="#" class="btn-action btn-edit" title="Editar Modelo"><i class="fas fa-pencil-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-content" id="tab-personalidades">
            <h3><i class="fas fa-theater-masks"></i> Personalidades de Lyra</h3>
            <p>Gestiona los prompts de sistema que definen el comportamiento de Lyra.</p>
             <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Legible</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personalidades as $p): ?>
                            <tr>
                                <td><?= $p['id_personalidad'] ?></td>
                                <td><?= htmlspecialchars($p['nombre_legible']) ?></td>
                                <td><?= htmlspecialchars($p['descripcion']) ?></td>
                                <td><span class="badge status-<?= $p['estado'] == 'activa' ? 'activo' : 'inactivo' ?>"><?= htmlspecialchars(ucfirst($p['estado'])) ?></span></td>
                                <td>
                                    <a href="#" class="btn-action btn-edit" title="Editar Personalidad"><i class="fas fa-pencil-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="tab-content" id="tab-proveedores">
            <h3><i class="fas fa-plug"></i> Proveedores de API</h3>
            <p>Lista de los proveedores de servicios de IA integrados.</p>
            <div class="table-responsive">
                <table class="data-table">
                     <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Proveedor</th>
                            <th>Sitio Web</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $prov): ?>
                            <tr>
                                <td><?= $prov['id_proveedor'] ?></td>
                                <td><?= htmlspecialchars($prov['nombre_proveedor']) ?></td>
                                <td><a href="<?= htmlspecialchars($prov['url_sitio_web']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($prov['url_sitio_web']) ?></a></td>
                                <td>
                                    <a href="#" class="btn-action btn-edit" title="Editar Proveedor"><i class="fas fa-pencil-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
ob_start();
?>
<style>
    .tabs-container {
        width: 100%;
    }
    .tab-headers {
        display: flex;
        border-bottom: 2px solid #444;
        margin-bottom: 1.5rem;
    }
    .tab-link {
        padding: 10px 20px;
        cursor: pointer;
        border: none;
        background-color: transparent;
        color: #ccc;
        font-size: 16px;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
    }
    .tab-link.active {
        color: #0095ff;
        border-bottom-color: #0095ff;
        font-weight: bold;
    }
    .tab-content {
        display: none;
        animation: fadeIn 0.5s;
    }
    .tab-content.active {
        display: block;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .data-table { width: 100%; }
</style>
<?php
$page_specific_styles = ob_get_clean();
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const tabId = link.getAttribute('data-tab');

            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            link.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>