<?php
// Archivo NUEVO Y DEFINITIVO: /public/includes/modals/modal_viewer.php
?>
<div id="universal-viewer-modal" class="viewer-modal">
    <div class="viewer-modal-content">
        <span class="viewer-modal-close" onclick="cerrarViewerModal()">&times;</span>
        <h5 id="viewer-modal-title"></h5>
        <div class="viewer-modal-body">
            <img id="viewer-modal-image" src="" alt="Vista completa">
            <p id="viewer-modal-description"></p>
        </div>
    </div>
</div>

<style>
    .viewer-modal {
        display: none; position: fixed; z-index: 2000;
        left: 0; top: 0; width: 100%; height: 100%;
        overflow: auto; background-color: rgba(0,0,0,0.85);
        display: none;
        justify-content: center; align-items: center;
    }
    .viewer-modal-content {
        position: relative; background-color: #2c2c2d;
        margin: auto; padding: 20px; border: 1px solid #555;
        width: auto; max-width: 90vw; border-radius: 8px;
        text-align: center; color: #e0e0e0;
    }
    .viewer-modal-close {
        position: absolute; top: 10px; right: 25px; color: #f1f1f1;
        font-size: 35px; font-weight: bold; cursor: pointer;
    }
    #viewer-modal-title { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #555; }
    #viewer-modal-image { max-width: 100%; max-height: 70vh; margin-bottom: 1rem; }
    #viewer-modal-description { color: #ccc; }
</style>

<script>
    const viewerModal = document.getElementById('universal-viewer-modal');
    
    function abrirViewerModal(config) {
        document.getElementById('viewer-modal-title').textContent = config.titulo || '';
        document.getElementById('viewer-modal-image').src = config.url_imagen || '';
        document.getElementById('viewer-modal-description').textContent = config.descripcion || '';
        
        document.getElementById('viewer-modal-title').style.display = config.titulo ? 'block' : 'none';
        document.getElementById('viewer-modal-description').style.display = config.descripcion ? 'block' : 'none';

        viewerModal.style.display = 'flex';
    }

    function cerrarViewerModal() {
        viewerModal.style.display = 'none';
    }

    window.addEventListener('click', function(event) {
        if (event.target == viewerModal) {
            cerrarViewerModal();
        }
    });
</script>