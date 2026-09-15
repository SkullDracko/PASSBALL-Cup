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
   Integrantes seleccionados (opcional)
   Máximo 12 por equipo (capitán + 11)
   ------------------------------------------- */

const MAX_MIEMBROS = 12;

$integrantesRaw = $_POST['integrantes'] ?? [];

$integrantes = array_values(array_unique(array_map('intval', (array) $integrantesRaw)));

$integrantes = array_filter(
    $integrantes,
    fn ($id) => $id > 0 && $id !== (int) $usuario['id']
);

if (count($integrantes) > MAX_MIEMBROS - 1) {
    $_SESSION['flash_error'] = 'El equipo admite máximo ' . MAX_MIEMBROS . ' integrantes (tú + 11). Reduce la lista.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

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

    // 3. Insertar integrantes seleccionados (validados)
    if ($integrantes !== []) {

        $placeholders = implode(',', array_fill(0, count($integrantes), '?'));

        // 3a. Verificar que todos existan y sean usuarios válidos
        $stmt = $pdo->prepare("
            SELECT id, nombre
            FROM usuarios
            WHERE id IN ($placeholders)
              AND rol = 'usuario'
              AND estado = 'activo'
        ");
        $stmt->execute($integrantes);

        $_integrantesValidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($_integrantesValidos) !== count($integrantes)) {
            throw new RuntimeException('Uno de los jugadores seleccionados ya no es válido.');
        }

        // 3b. Verificar que ninguno pertenezca a un equipo activo
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM equipo_miembros
            WHERE jugador_id IN ($placeholders)
              AND estado = 'activo'
        ");
        $stmt->execute($integrantes);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException('Uno de los jugadores seleccionados ya pertenece a otro equipo.');
        }

        // 3c. Insertar miembros
        $stmt = $pdo->prepare("
            INSERT INTO equipo_miembros (equipo_id, jugador_id, estado)
            VALUES (?, ?, 'activo')
        ");

        foreach ($_integrantesValidos as $jugador) {
            $stmt->execute([$equipoId, $jugador['id']]);
        }
    }

    $pdo->commit();

    $totalIntegrantes = 1 + count($integrantes);

    $_SESSION['flash_success'] = "Equipo \"{$nombre}\" registrado con {$totalIntegrantes} integrante(s). ¡Bienvenido, líder!";
    header("Location: ../dashboard.php#view-equipos");
    exit;

} catch (Throwable $e) {
    $pdo->rollBack();

    // Limpiar logo ya subido si falló
    if (is_file($destino)) {
        unlink($destino);
    }

    error_log("registrarEquipo - crear: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al registrar el equipo. ' . ($e instanceof RuntimeException ? $e->getMessage() : 'Intenta de nuevo.');
    header("Location: ../dashboard.php#view-equipos");
    exit;
}