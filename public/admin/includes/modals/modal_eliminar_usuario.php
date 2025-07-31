<div id="modal-eliminar" class="modal">
  <div class="modal-content">
    <h3>Confirmar Eliminación Permanente</h3>
    <p>Esta acción no se puede deshacer. Se borrarán todos los datos, avatares e insignias del usuario.</p>
    <p>Para confirmar, por favor escribe el nickname del usuario: <strong id="nickname-a-eliminar"></strong></p>
    <input type="text" id="confirmacion-nickname" placeholder="Escribe el nickname aquí...">
    <div class="modal-actions">
      <button onclick="cerrarModalEliminar()">Cancelar</button>
      <form id="form-eliminar" action="<?= BASE_URL ?>form-handler.php" method="POST" style="display:inline;">
        <input type="hidden" name="action" value="eliminar_usuario_admin">
        <input type="hidden" id="id-usuario-a-eliminar" name="id_usuario">
        <button type="submit" id="btn-eliminar-confirmado" disabled>Eliminar Definitivamente</button>
      </form>
    </div>
  </div>
</div>

<style>
    /* Estilos para el Modal de Confirmación */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
    .modal-content { background-color: #2c2c2d; margin: 15% auto; padding: 20px; border: 1px solid #555; width: 80%; max-width: 500px; border-radius: 8px; color: #e0e0e0; }
    .modal-content input[type="text"] { width: 100%; margin-top: 10px; padding: 8px; border-radius: 4px; border: 1px solid #555; background-color: #1e1e1e; color: #e0e0e0; }
    .modal-actions { margin-top: 20px; text-align: right; display: flex; gap: 10px; justify-content: flex-end; }
    .modal-actions button { padding: 10px 15px; border-radius: 5px; cursor: pointer; border: none; }
    .modal-actions button[type="submit"] { background-color: #dc3545; color: white; }
    .modal-actions button[type="submit"]:disabled { background-color: #555; cursor: not-allowed; }
</style>

<script>
    // Aseguramos que el script no se ejecute múltiples veces si se incluye en varias páginas
    if (typeof modalEliminarSetup === 'undefined') {
        const modalEliminarSetup = true;

        const modal = document.getElementById('modal-eliminar');
        const nicknameSpan = document.getElementById('nickname-a-eliminar');
        const confirmInput = document.getElementById('confirmacion-nickname');
        const deleteBtn = document.getElementById('btn-eliminar-confirmado');
        const idInput = document.getElementById('id-usuario-a-eliminar');

        function abrirModalEliminar(id, nickname) {
            nicknameSpan.textContent = nickname;
            idInput.value = id;
            confirmInput.value = '';
            deleteBtn.disabled = true;
            modal.style.display = 'block';
            confirmInput.focus();
        }

        function cerrarModalEliminar() {
            modal.style.display = 'none';
        }

        confirmInput.addEventListener('input', () => {
            // Habilitar el botón solo si el texto coincide exactamente
            deleteBtn.disabled = (confirmInput.value !== nicknameSpan.textContent);
        });

        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                cerrarModalEliminar();
            }
        });
    }
</script>