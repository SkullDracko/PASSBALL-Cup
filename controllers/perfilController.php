<?php
/**
 * PASSBALL Cup - Perfil del jugador
 *
 * Endpoint que actualiza el alias y/o la foto de perfil
 * del jugador autenticado.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

$accion = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($accion !== 'actualizar_perfil') {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
    exit;
}

$usuarioId = (int) $usuario['id'];

/* -------------------------------------------
   Alias
   ------------------------------------------- */

$alias = trim((string) ($_POST['alias'] ?? ''));

if (mb_strlen($alias) > 40) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El alias no puede superar los 40 caracteres.']);
    exit;
}

$aliasNuevo = $alias === '' ? null : $alias;

/* -------------------------------------------
   Foto de perfil (opcional)
   ------------------------------------------- */

$nuevoAvatar  = null;
$foto         = $_FILES['foto_perfil'] ?? null;
$viejoAvatar  = $usuario['avatar'] ?? null;

if ($foto && ($foto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {

    if ($foto['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hubo un error al subir la foto.']);
        exit;
    }

    if ($foto['size'] > MAX_FILE_SIZE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La foto no puede superar los 5 MB.']);
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($foto['tmp_name']);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La foto debe ser una imagen válida (JPG, PNG, WEBP o GIF).']);
        exit;
    }

    $ext = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ][$mime];

    $dir = UPLOADS_PATH . 'avatars/';

    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar la foto.']);
            exit;
        }
    }

    $nombreArchivo = uniqid('av_', true) . '.' . $ext;
    $destino       = $dir . $nombreArchivo;

    if (!move_uploaded_file($foto['tmp_name'], $destino)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar la foto.']);
        exit;
    }

    $nuevoAvatar = UPLOADS_URL . 'avatars/' . $nombreArchivo;
}

/* -------------------------------------------
   Guardar en la base de datos
   ------------------------------------------- */

try {

    $sql = "UPDATE usuarios SET alias = ?";

    $params = [$aliasNuevo];

    if ($nuevoAvatar) {
        $sql .= ", avatar = ?";
        $params[] = $nuevoAvatar;
    }

    $sql .= " WHERE id = ?";
    $params[] = $usuarioId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

} catch (PDOException $e) {

    if ($nuevoAvatar) {
        @unlink(UPLOADS_PATH . 'avatars/' . basename($nuevoAvatar));
    }

    error_log("Perfil PASSBALL: " . $e->getMessage());

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el perfil.']);
    exit;
}

/* -------------------------------------------
   Eliminar foto anterior si se reemplazó
   ------------------------------------------- */

if ($nuevoAvatar && $viejoAvatar && strpos($viejoAvatar, 'uploads/avatars/') === 0) {
    $rutaVieja = UPLOADS_PATH . 'avatars/' . basename($viejoAvatar);
    if (is_file($rutaVieja)) {
        @unlink($rutaVieja);
    }
}

/* -------------------------------------------
   Actualizar sesión
   ------------------------------------------- */

$_SESSION['usuario']['alias']  = $aliasNuevo;
if ($nuevoAvatar) {
    $_SESSION['usuario']['avatar'] = $nuevoAvatar;
}

echo json_encode([
    'success' => true,
    'message' => 'Perfil actualizado.',
    'usuario' => $_SESSION['usuario'],
]);