<?php
/**
 * PASSBALL Cup - Postular equipo al torneo activo
 * INSERT en torneo_equipos con estado 'pendiente'
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

/* -------------------------------------------
   Buscar torneo activo
   ------------------------------------------- */

try {
    $stmt = $pdo->prepare("
        SELECT id, nombre
        FROM torneos
        WHERE estado IN ('programado', 'en_curso')
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute();
    $torneo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$torneo) {
        $_SESSION['flash_error'] = 'No hay un torneo activo en este momento. Vuelve más tarde.';
        header("Location: ../dashboard.php#view-equipos");
        exit;
    }
} catch (PDOException $e) {
    error_log("postularEquipo - torneo: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al consultar el torneo.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

/* -------------------------------------------
   Verificar que sea capitán de un equipo activo
   ------------------------------------------- */

try {
    $stmt = $pdo->prepare("
        SELECT id FROM equipos
        WHERE capitan_id = ? AND estado = 'activo'
        LIMIT 1
    ");
    $stmt->execute([$usuario['id']]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$equipo) {
        $_SESSION['flash_error'] = 'Solo el líder de un equipo puede postularlo al torneo.';
        header("Location: ../dashboard.php#view-equipos");
        exit;
    }
} catch (PDOException $e) {
    error_log("postularEquipo - capitan: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al verificar tu equipo.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

/* -------------------------------------------
   No postular dos veces el mismo equipo
   ------------------------------------------- */

try {
    $stmt = $pdo->prepare("
        SELECT estado FROM torneo_equipos
        WHERE torneo_id = ? AND equipo_id = ?
        LIMIT 1
    ");
    $stmt->execute([$torneo['id'], $equipo['id']]);
    $estado = $stmt->fetchColumn();

    if ($estado !== false) {
        $_SESSION['flash_error'] = 'Tu equipo ya tiene una postulación en este torneo.';
        header("Location: ../dashboard.php#view-equipos");
        exit;
    }
} catch (PDOException $e) {
    error_log("postularEquipo - duplicado: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al verificar tu postulación.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}

/* -------------------------------------------
   Insertar postulación
   ------------------------------------------- */

try {
    $stmt = $pdo->prepare("
        INSERT INTO torneo_equipos (torneo_id, equipo_id, estado)
        VALUES (?, ?, 'pendiente')
    ");
    $stmt->execute([$torneo['id'], $equipo['id']]);

    $_SESSION['flash_success'] = '¡Tu equipo se postuló al ' . $torneo['nombre'] . '! El organizador revisará la solicitud.';
    header("Location: ../dashboard.php#view-equipos");
    exit;

} catch (PDOException $e) {
    error_log("postularEquipo - insertar: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al postular tu equipo. Intenta de nuevo.';
    header("Location: ../dashboard.php#view-equipos");
    exit;
}