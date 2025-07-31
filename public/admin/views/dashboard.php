<?php
// Archivo REFACTORIZADO: /public/admin/views/dashboard.php

// Cada "vista" definirá su propio título. El index.php principal lo usará en la etiqueta <title>.
$page_title = "Dashboard";
?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-tachometer-alt"></i> <?= htmlspecialchars($page_title) ?></h2>
        <p>Bienvenido al panel de administración de BiblioSYS. Desde aquí puedes gestionar todos los aspectos de la plataforma.</p>
    </div>
    
    <div class="dashboard-widgets">
        <div class="widget">
            <h3><i class="fas fa-users"></i> Usuarios Recientes</h3>
            <div class="widget-content">
                <p>Widget pendiente de implementación. Aquí se mostrará una lista de los últimos usuarios registrados.</p>
            </div>
        </div>
        <div class="widget">
            <h3><i class="fas fa-brain"></i> Actividad de la IA</h3>
             <div class="widget-content">
                <p>Widget pendiente de implementación. Mostrará estadísticas de uso de las APIs, errores, etc.</p>
            </div>
        </div>
         <div class="widget">
            <h3><i class="fas fa-book"></i> Actividad de la Biblioteca</h3>
             <div class="widget-content">
                <p>Widget pendiente de implementación. Resumen de préstamos, libros más populares, etc.</p>
            </div>
        </div>
    </div>
</div>