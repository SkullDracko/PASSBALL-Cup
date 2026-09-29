<?php
/**
 * PASSBALL Cup - Editar equipo (solo capitán)
 * Permite cambiar nombre y logo del equipo.
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../controllers/auth.php';

header('Content-Type: application/json');

$equipoId = (int)($_POST['equipo_id'] ?? 0);

// Solo el capitán puede editar
if ($equipoId <= 0 || !es_capitan($equipoId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');

if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre debe tener entre 3 y 100 caracteres']);
    exit;
}

// Nombre único (excepto el propio equipo)
$stmt = $pdo->prepare("SELECT id FROM equipos WHERE nombre = ? AND id <> ?");
$stmt->execute([$nombre, $equipoId]);
if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ya existe un equipo con ese nombre']);
    exit;
}

$logoNuevo = null;

if (!empty($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $archivo = $_FILES['logo'];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Error al subir el logo']);
        exit;
    }

    if ($archivo['size'] > MAX_FILE_SIZE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El logo excede el tamaño máximo de 5 MB']);
        exit;
    }

    if (!in_array($archivo['type'], ALLOWED_IMAGE_TYPES, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato de imagen no permitido (JPG, PNG, WEBP, GIF)']);
        exit;
    }

    $extension = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ][$archivo['type']];

    $dirEquipos = UPLOADS_PATH . 'equipos/';

    if (!is_dir($dirEquipos)) {
        mkdir($dirEquipos, 0775, true);
    }

    $nombreArchivo = 'eq_' . uniqid('', true) . '.' . $extension;

    if (!move_uploaded_file($archivo['tmp_name'], $dirEquipos . $nombreArchivo)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar el logo']);
        exit;
    }

    $logoNuevo = UPLOADS_URL . 'equipos/' . $nombreArchivo;
}

try {
    // Obtener logo actual para borrar el archivo anterior si se reemplaza
    if ($logoNuevo !== null) {
        $stmt = $pdo->prepare("SELECT logo FROM equipos WHERE id = ?");
        $stmt->execute([$equipoId]);
        $logoActual = $stmt->fetchColumn();

        if ($logoActual && strpos($logoActual, 'uploads/equipos/') === 0) {
            $rutaAnterior = UPLOADS_PATH . 'equipos/' . basename($logoActual);
            if (is_file($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }
    }

    if ($logoNuevo !== null) {
        $stmt = $pdo->prepare("UPDATE equipos SET nombre = ?, logo = ? WHERE id = ?");
        $stmt->execute([$nombre, $logoNuevo, $equipoId]);
    } else {
        $stmt = $pdo->prepare("UPDATE equipos SET nombre = ? WHERE id = ?");
        $stmt->execute([$nombre, $equipoId]);
    }

    echo json_encode(['success' => true, 'message' => 'Equipo actualizado']);
} catch (PDOException $e) {
    error_log("Editar equipo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el equipo']);
}