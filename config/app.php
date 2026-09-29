<?php
/**
 * PASSBALL Cup - Configuración de la aplicación
 */

// AFI Hub
define('AFI_ID', 5);
define('AFI_URL_VERIFICAR', 'https://afihub.encuestapassword2026.com/controllers/publico_verificar_inscripcion.php');
define('AFI_URL_INSCRITOS', 'https://afihub.encuestapassword2026.com/controllers/publico_inscritos.php');

// Uploads
define('UPLOADS_PATH', __DIR__ . '/../uploads/');
define('UPLOADS_URL', 'uploads/');

// URL base del sitio
define('BASE_URL', 'https://passballcup.encuestapassword2026.com/');

/**
 * Ruta web del proyecto, calculada en tiempo de ejecucion.
 *
 * BASE_URL apunta a produccion y no sirve en local, y una ruta relativa
 * tipo "../backend/api" depende de la forma de la URL: si alguien entra
 * en /admin sin barra final, el directorio del documento pasa a ser la
 * raiz del sitio y "../" se sale del proyecto. Apache responde entonces
 * con su propio 404 (295 bytes) en lugar del "Ruta no encontrada" de la
 * API (88 bytes), que es un error mucho mas dificil dediagnosticar.
 *
 * OJO: recorta el sufijo /admin[/archivo], asi que solo es valida para
 * las paginas del panel. El portal publico sigue con rutas relativas y
 * usa include/header.php e include/footer.php, fuera de este panel.
 */
function projectPath()
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $raiz = preg_replace('#/admin(/[^/]*)?$#', '', $script);
    return rtrim($raiz, '/');
}

/** Ruta absoluta de la API REST, para el fetch de admin/assets/js/api.js */
function apiUrl()
{
    return projectPath() . '/backend/api';
}

/** La misma ruta, lista para incrustar en un <script> como cadena JS */
function apiUrlJs()
{
    return json_encode(apiUrl(), JSON_UNESCAPED_SLASHES);
}

/**
 * Ruta absoluta de un asset del proyecto, con la marca de tiempo del
 * archivo como version. Sin esto el navegador sigue ejecutando el JS
 * viejo despues de editarlo, y el panel falla con rutas inexistentes
 * que en el codigo ya no existen.
 */
function assetUrl($ruta)
{
    $ruta = ltrim($ruta, '/');
    $archivo = __DIR__ . '/../' . $ruta;
    $version = @filemtime($archivo) ?: 1;
    return projectPath() . '/' . $ruta . '?v=' . $version;
}

// Nombre del torneo
define('TORNEO_NOMBRE', 'PASSBALL Cup');
define('TORNEO_EDICION', '2026');

// Configuración de subida de archivos
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
