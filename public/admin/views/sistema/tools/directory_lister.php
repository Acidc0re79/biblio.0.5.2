<?php
// Archivo MEJORADO: /public/admin/views/sistema/tools/directory_lister.php
// @purpose: [Visor de Archivos y Documentación - Mapeador de Directorios Puplicos]
$page_title = "Visor de Archivos y Documentación";

// Seguridad
if ($_SESSION['rango'] !== 'administrador') {
    echo "<div class='admin-content'><p>Acceso denegado.</p></div>";
    return;
}

/**
 * Lee la primera línea de un archivo PHP para extraer el propósito.
 * @param string $filepath La ruta completa al archivo.
 * @return string La descripción del propósito o una cadena vacía.
 */
function get_file_purpose($filepath) {
    if (pathinfo($filepath, PATHINFO_EXTENSION) !== 'php') {
        return '';
    }

    $purpose = '';
    $file = fopen($filepath, 'r');
    if ($file) {
        // Leemos hasta encontrar el propósito o llegar al final (máx 5 líneas)
        for ($i = 0; $i < 5; $i++) {
            $line = fgets($file);
            if ($line === false) break;
            
            // Buscamos nuestra etiqueta especial
            if (strpos($line, '// @purpose:') !== false) {
                // Extraemos el texto después de la etiqueta
                $purpose = trim(str_replace('// @purpose:', '', $line));
                break; // Lo encontramos, salimos del bucle
            }
        }
        fclose($file);
    }
    return htmlspecialchars($purpose);
}

/**
 * Lista el directorio de forma recursiva, incluyendo la descripción de propósito.
 * @param string $dir El directorio a escanear.
 * @return string El HTML de la lista.
 */
function list_directory_with_purpose($dir) {
    $result = '<ul>';
    $files = scandir($dir);
    foreach ($files as $key => $value) {
        if (!in_array($value, [".", "..", ".git", ".idea", "vendor"])) { // Excluimos carpetas comunes
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (is_dir($path)) {
                $result .= '<li class="dir"><i class="fas fa-folder"></i> ' . htmlspecialchars($value) . '</li>';
                $result .= list_directory_with_purpose($path);
            } else {
                $purpose = get_file_purpose($path);
                $purpose_html = $purpose ? '<span class="purpose-tag">' . $purpose . '</span>' : '';
                $result .= '<li class="file"><i class="fas fa-file-code"></i> ' . htmlspecialchars($value) . $purpose_html . '</li>';
            }
        }
    }
    $result .= '</ul>';
    return $result;
}
?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-sitemap"></i> <?= htmlspecialchars($page_title) ?></h2>
        <p>Un árbol de archivos del proyecto que se documenta a sí mismo. El visor lee un comentario especial (`// @purpose:`) en cada archivo PHP.</p>
    </div>
    
    <div class="directory-tree-container">
        <?php echo list_directory_with_purpose(ROOT_PATH); ?>
    </div>
</div>

<?php
ob_start();
?>
<style>
    /* ... (Estilos del Visor de Archivos) ... */
    .purpose-tag {
        color: var(--text-muted-color);
        font-style: italic;
        margin-left: 20px;
        background-color: rgba(255, 255, 255, 0.05);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.9em;
    }
</style>
<?php
$page_specific_styles = ob_get_clean();
?>