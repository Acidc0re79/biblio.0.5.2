<?php
// Archivo COMPLETO Y CORREGIDO: /public/admin/includes/modals/modal_gestionar_insignias.php
?>
<div id="modal-gestionar-insignias" class="modal">
  <div class="modal-content large">
    <h3>Gestionar Insignias</h3>
    <form id="form-insignias">
      <div class="insignias-container">
        <div>
            <h4><i class="fas fa-check-circle" style="color: #28a745;"></i> Obtenidas</h4>
            <div id="insignias-obtenidas" class="insignias-list"></div>
        </div>
        <div>
            <h4><i class="fas fa-bullseye" style="color: #ffc107;"></i> A Lograr</h4>
            <div id="insignias-a-lograr" class="insignias-list"></div>
        </div>
      </div>
    </form>
    <div class="modal-actions">
      <button onclick="cerrarModalInsignias()" type="button">Cancelar</button>
      <button id="btn-guardar-insignias" type="button">Guardar Cambios</button>
    </div>
  </div>
</div>

<style>
    .modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.7); }
    .modal-content.large { width: 90%; max-width: 800px; margin: 5% auto; background-color: #2c2c2d; color: #e0e0e0; border: 1px solid #555; border-radius: 8px; padding: 20px; }
    .insignias-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .insignias-list { height: 50vh; overflow-y: auto; background: #1e1e1e; padding: 10px; border-radius: 4px; border: 1px solid #444; }
    .insignia-item { display: flex; align-items: center; margin-bottom: 8px; padding: 5px; border-radius: 4px; }
    .insignia-item:hover { background-color: #3a3a3a; }
    .insignia-item input[type="checkbox"] { margin-right: 10px; transform: scale(1.2); }
    .insignia-item img { width: 40px; height: 40px; margin-right: 10px; border-radius: 50%; background: #2c2c2d; padding: 2px; }
    .insignia-item label { color: #e0e0e0; cursor: pointer; flex-grow: 1; }
    .insignia-item-actions { margin-left: auto; }
    .insignia-item-actions button { background: none; border: none; color: #ccc; cursor: pointer; font-size: 1em; padding: 5px; }
    .modal-actions { margin-top: 20px; text-align: right; }
</style>

<script>
if (typeof modalInsigniasSetup === 'undefined') {
    const modalInsigniasSetup = true;
    const modalInsignias = document.getElementById('modal-gestionar-insignias');
    const listaObtenidas = document.getElementById('insignias-obtenidas');
    const listaALograr = document.getElementById('insignias-a-lograr');
    const btnGuardar = document.getElementById('btn-guardar-insignias');
    let currentUserId = null;

    function abrirModalInsignias(idUsuario) {
        currentUserId = idUsuario;
        listaObtenidas.innerHTML = '<p>Cargando...</p>';
        listaALograr.innerHTML = '<p>Cargando...</p>';
        modalInsignias.style.display = 'block';
        fetch(`${BASE_URL}ajax-handler.php?action=get_user_badges_admin&id_usuario=${idUsuario}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    listaObtenidas.innerHTML = '';
                    listaALograr.innerHTML = '';
                    if (data.insignias.length === 0) {
                        listaALograr.innerHTML = '<p>No hay insignias en el sistema.</p>';
                        return;
                    }
                    data.insignias.forEach(insignia => {
                        const itemHTML = `
                            <div class="insignia-item">
                                <input type="checkbox" id="insignia-${insignia.id_insignia}" name="insignias_ids[]" value="${insignia.id_insignia}" ${insignia.tiene_insignia ? 'checked' : ''}>
                                <img src="${insignia.imagen_thumb}" alt="${insignia.nombre}">
                                <label for="insignia-${insignia.id_insignia}">${insignia.nombre}</label>
                                <div class="insignia-item-actions">
                                    <button type="button" title="Ver Detalles"
                                            onclick="abrirViewerModal({
                                                titulo: '${insignia.nombre.replace(/'/g, "\\'")}',
                                                descripcion: '${insignia.descripcion.replace(/'/g, "\\'")}',
                                                url_imagen: '${insignia.imagen_full}'
                                            })">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        if (insignia.tiene_insignia) {
                            listaObtenidas.innerHTML += itemHTML;
                        } else {
                            listaALograr.innerHTML += itemHTML;
                        }
                    });
                } else {
                    listaObtenidas.innerHTML = `<p style="color:red;">${data.message || 'Error al cargar las insignias.'}</p>`;
                    listaALograr.innerHTML = '';
                }
            });
    }

    function cerrarModalInsignias() {
        modalInsignias.style.display = 'none';
    }

    btnGuardar.addEventListener('click', () => {
        const formData = new FormData();
        formData.append('action', 'update_user_badges_admin');
        formData.append('id_usuario', currentUserId);
        const checkboxes = document.querySelectorAll('#insignias-obtenidas input:checked, #insignias-a-lograr input:checked');
        checkboxes.forEach(cb => {
            formData.append('insignias_ids[]', cb.value);
        });
        fetch(`${BASE_URL}ajax-handler.php`, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Insignias actualizadas con éxito.');
                    cerrarModalInsignias();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    });
}
</script>