function initDynamicTabs(config) {
    const links = document.querySelectorAll(config.navSelector);
    const target = document.querySelector(config.target);

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            const logType = this.getAttribute('data-log');

            fetch(`${config.endpoint}?action=load_log_json&log=${encodeURIComponent(logType)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data)) {
                        target.innerHTML = `<pre>${JSON.stringify(data.data, null, 2)}</pre>`;
                    } else {
                        target.innerHTML = `<p>Error en el log: ${data.message || 'Desconocido'}</p>`;
                    }
                })
                .catch(err => {
                    target.innerHTML = `<p>Error al cargar log: ${err}</p>`;
                });
        });
    });
}
