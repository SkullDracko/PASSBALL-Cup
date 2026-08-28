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

// Nombre del torneo
define('TORNEO_NOMBRE', 'PASSBALL Cup');
define('TORNEO_EDICION', '2026');

// Configuración de subida de archivos
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
