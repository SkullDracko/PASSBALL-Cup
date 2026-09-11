<?php
/**
 * PASSBALL Cup - Registrar Equipo (POST handler)
 * Esquema: tabla equipos (BD definitiva)
 * - Logo obligatorio, subido por el capitán (no cambiable después)
 * - capitan_id = usuario que crea el equipo
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

$nombre = trim($_POST['nombre_equipo'] ?? '');

/* -------------------------------------------
   Validaciones
   ------------------------------------------- */

if ($nombre === '') {
    $_SESSION['flash_error'] = 'El nombre del equipo es obligatorio.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

if (mb_strlen($nombre, 'UTF-8') < 3) {
    $_SESSION['flash_error'] = 'El nombre debe tener al menos 3 caracteres.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

if (mb_strlen($nombre, 'UTF-8') > 100) {
    $_SESSION['flash_error'] = 'El nombre no puede exceder 100 caracteres.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

/* -------------------------------------------
   Logo obligatorio
   ------------------------------------------- */

$logo = $_FILES['logo_equipo'] ?? null;

if (!$logo || ($logo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    $_SESSION['flash_error'] = 'El logo del equipo es obligatorio. Sube una imagen (JPG, PNG, WEBP o GIF).';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

if ($logo['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Hubo un error al subir el logo del equipo.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

if ($logo['size'] > MAX_FILE_SIZE) {
    $_SESSION['flash_error'] = 'El logo no puede superar los 5 MB.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($logo['tmp_name']);

if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
    $_SESSION['flash_error'] = 'El logo debe ser una imagen válida (JPG, PNG, WEBP o GIF).';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

$ext = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
][$mime];

/* -------------------------------------------
   Verificar que el usuario no tenga ya un equipo
   ------------------------------------------- */

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM equipo_miembros
        WHERE jugador_id = ? AND estado = 'activo'
    ");
    $stmt->execute([$usuario['id']]);

    if ($stmt->fetchColumn() > 0) {
        $_SESSION['flash_error'] = 'Ya perteneces a un equipo. Sal del equipo actual para crear uno nuevo.';
        header("Location: ../dashboard.php#view-equipos");
        exit;
    }
} catch (PDOException $e) {
    error_log("registrarEquipo - verificar equipo: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al verificar tu equipo actual.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

/* -------------------------------------------
   Verificar nombre único
   ------------------------------------------- */

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM equipos WHERE nombre = ?");
    $stmt->execute([$nombre]);

    if ($stmt->fetchColumn() > 0) {
        $_SESSION['flash_error'] = 'Ya existe un equipo con ese nombre. Elige otro.';
        header("Location: ../dashboard.php#view-equipos");
        exit;
    }
} catch (PDOException $e) {
    error_log("registrarEquipo - verificar nombre: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al verificar el nombre del equipo.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

/* -------------------------------------------
   Guardar logo
   ------------------------------------------- */

$dir = UPLOADS_PATH . 'equipos/';

if (!is_dir($dir)) {
    if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
        $_SESSION['flash_error'] = 'No se pudo guardar el logo. Intenta de nuevo.';
        header("Location: ../dashboard.php#view-equipos");
        exit;
    }
}

$nombreArchivo = uniqid('eq_', true) . '.' . $ext;
$destino       = $dir . $nombreArchivo;

if (!move_uploaded_file($logo['tmp_name'], $destino)) {
    $_SESSION['flash_error'] = 'No se pudo guardar el logo. Intenta de nuevo.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

$logoUrl = UPLOADS_URL . 'equipos/' . $nombreArchivo;

/* -------------------------------------------
   Crear equipo + miembro (capitán)
   ------------------------------------------- */

try {
    $pdo->beginTransaction();

    // 1. Insertar equipo
    $stmt = $pdo->prepare("
        INSERT INTO equipos (nombre, logo, capitan_id, estado)
        VALUES (?, ?, ?, 'activo')
    ");
    $stmt->execute([$nombre, $logoUrl, $usuario['id']]);
    $equipoId = $pdo->lastInsertId();

    // 2. Insertar al capitán como miembro activo
    $stmt = $pdo->prepare("
        INSERT INTO equipo_miembros (equipo_id, jugador_id, estado)
        VALUES (?, ?, 'activo')
    ");
    $stmt->execute([$equipoId, $usuario['id']]);

    $pdo->commit();

    $_SESSION['flash_success'] = "Equipo \"{$nombre}\" registrado exitosamente. ¡Bienvenido, líder!";
    header("Location: ../dashboard.php#view-equipos");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();

    // Limpiar logo ya subido si falló la BD
    if (is_file($destino)) {
        unlink($destino);
    }

    error_log("registrarEquipo - crear: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al registrar el equipo. Intenta de nuevo.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}