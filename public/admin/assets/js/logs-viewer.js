
document.addEventListener('DOMContentLoaded', function () {
    const filterSelect = document.getElementById('log-limit');
    const logContainer = document.querySelector('.log-container');

    function loadLogs(limit = 50) {
        const activeTab = document.querySelector('.tabs-nav a.active');
        if (!activeTab) return;

        const logType = activeTab.getAttribute('data-log');

        fetch(`/ajax-handler.php?action=load_log_json&log=${encodeURIComponent(logType)}`)
            .then(response => response.json())
            .then(data => {
                logContainer.innerHTML = '';
                if (data.success && Array.isArray(data.data)) {
                    let entries = data.data.slice(-limit);
                    for (let entry of entries) {
                        const div = document.createElement('div');
                        div.classList.add('log-entry');
                        div.dataset.level = entry.level || 'INFO';
                        div.textContent = `[${entry.timestamp}] ${entry.level} - ${entry.message}`;

                        const button = document.createElement('button');
                        button.className = 'copy-button';
                        button.textContent = 'Copiar';
                        button.setAttribute('data-clipboard-text', div.textContent);
                        div.appendChild(button);

                        logContainer.appendChild(div);
                    }
                    new ClipboardJS('.copy-button');
                } else {
                    logContainer.innerHTML = '<p>No se pudieron cargar los logs.</p>';
                }
            })
            .catch(err => {
                logContainer.innerHTML = `<p>Error al cargar logs: ${err}</p>`;
            });
    }

    document.querySelectorAll('.tabs-nav a').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelectorAll('.tabs-nav a').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            loadLogs(parseInt(filterSelect.value));
        });
    });

    filterSelect.addEventListener('change', () => loadLogs(parseInt(filterSelect.value)));

    loadLogs(parseInt(filterSelect.value));
});
