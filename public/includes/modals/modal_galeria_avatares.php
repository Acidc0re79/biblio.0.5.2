<?php
// Archivo COMPLETO Y CORREGIDO: /public/includes/modals/modal_galeria_avatares.php
?>
<div id="modal-galeria-avatares" class="modal-galeria">
  <div class="modal-galeria-content">
    <h3 id="galeria-titulo">Mis Creaciones</h3>
    <p id="galeria-subtitulo">Selecciona, visualiza o elimina los avatares que has generado.</p>
    <div id="galeria-body" class="galeria-grid">
      </div>
    <div class="modal-galeria-actions">
      <button onclick="cerrarModalGaleria()">Cerrar</button>
    </div>
  </div>
</div>

<style>
    .modal-galeria { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.7); padding-top: 60px; }
    .modal-galeria-content { background-color: #2c2c2d; color: #e0e0e0; margin: 5% auto; padding: 20px; border: 1px solid #555; width: 90%; max-width: 800px; border-radius: 8px; }
    .galeria-grid { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; max-height: 60vh; overflow-y: auto; background: #1e1e1e; padding: 10px; border-radius: 4px; margin-top: 1rem; margin-bottom: 1rem; }
    .galeria-item { position: relative; cursor: pointer; }
    .galeria-item img { width: 120px; height: 120px; object-fit: cover; border-radius: 5px; }
    .galeria-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; gap: 10px; opacity: 0; transition: opacity 0.3s ease; }
    .galeria-item:hover .galeria-overlay { opacity: 1; }
    .galeria-overlay button { background: rgba(255, 255, 255, 0.8); color: black; border: none; border-radius: 50%; width: 35px; height: 35px; cursor: pointer; font-size: 1.2em; display: flex; justify-content: center; align-items: center; }
    .modal-galeria-actions { margin-top: 20px; text-align: right; }
</style>

<script>
if (typeof galeriaAvataresSetup === 'undefined') {
    const galeriaAvataresSetup = true;
    const modalGaleria = document.getElementById('modal-galeria-avatares');
    const galeriaBody = document.getElementById('galeria-body');
    const galeriaTitulo = document.getElementById('galeria-titulo');
    const galeriaSubtitulo = document.getElementById('galeria-subtitulo');
    let currentMode = 'usuario';

    function abrirModalGaleria(idUsuario, mode = 'usuario') {
        currentMode = mode;
        galeriaBody.innerHTML = '<p>Cargando...</p>';
        if (mode === 'admin') {
            galeriaTitulo.textContent = 'Revisar Avatares Generados';
            galeriaSubtitulo.textContent = 'Modera los avatares creados por el usuario.';
        } else {
            galeriaTitulo.textContent = 'Mis Creaciones';
            galeriaSubtitulo.textContent = 'Selecciona, visualiza o elimina tus avatares.';
        }
        modalGaleria.style.display = 'block';
        fetch(`${BASE_URL}ajax-handler.php?action=gestionar_avatar&sub_action=get_gallery&id_usuario=${idUsuario}`)
            .then(res => res.json()).then(data => {
                galeriaBody.innerHTML = '';
                if (data.success && data.avatares.length > 0) {
                    data.avatares.forEach(avatar => galeriaBody.appendChild(crearAvatarItem(avatar)));
                } else {
                    galeriaBody.innerHTML = '<p>No hay avatares para mostrar.</p>';
                }
            });
    }

    function crearAvatarItem(avatar) {
        const item = document.createElement('div');
        item.className = 'galeria-item';
        item.innerHTML = `<img src="${avatar.url_thumb}" alt="Avatar ${avatar.id}">`;
        const overlay = document.createElement('div');
        overlay.className = 'galeria-overlay';
        const btnVer = document.createElement('button');
        btnVer.innerHTML = '👁️';
        btnVer.title = 'Ver en tamaño completo';
        btnVer.onclick = () => {
            abrirViewerModal({ url_imagen: avatar.url_full });
        };
        overlay.appendChild(btnVer);
        if (currentMode === 'usuario') {
            const btnSeleccionar = document.createElement('button');
            btnSeleccionar.innerHTML = '✅';
            btnSeleccionar.title = 'Seleccionar como avatar';
            btnSeleccionar.onclick = () => seleccionarAvatar(avatar.id);
            overlay.appendChild(btnSeleccionar);
        }
        const btnEliminar = document.createElement('button');
        btnEliminar.innerHTML = '🗑️';
        btnEliminar.title = 'Eliminar avatar';
        btnEliminar.onclick = (e) => { e.stopPropagation(); eliminarAvatar(avatar.id, item); };
        overlay.appendChild(btnEliminar);
        item.appendChild(overlay);
        return item;
    }

    function seleccionarAvatar(idAvatar) {
        const formData = new FormData();
        formData.append('action', 'gestionar_avatar');
        formData.append('sub_action', 'select_avatar');
        formData.append('id_avatar', idAvatar);
        fetch(`${BASE_URL}ajax-handler.php`, { method: 'POST', body: formData })
            .then(res => res.json()).then(data => {
                if(data.success) {
                    alert('Avatar seleccionado. La página se recargará.');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }

    function eliminarAvatar(idAvatar, element) {
        if (!confirm('¿Estás seguro de que quieres eliminar este avatar de forma permanente?')) return;
        const formData = new FormData();
        formData.append('action', 'gestionar_avatar');
        formData.append('sub_action', 'delete_avatar');
        formData.append('id_avatar', idAvatar);
        fetch(`${BASE_URL}ajax-handler.php`, { method: 'POST', body: formData })
            .then(res => res.json()).then(data => {
                if (data.success) {
                    element.remove();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }
    
    function cerrarModalGaleria() { modalGaleria.style.display = 'none'; }
}
</script>