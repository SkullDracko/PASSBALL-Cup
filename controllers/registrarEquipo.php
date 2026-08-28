<?php
/**
 * PASSBALL Cup - Registrar Equipo (POST handler)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../equipos.php");
    exit;
}

$nombre     = trim($_POST['nombre_equipo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

/* -------------------------------------------
   Validaciones
   ------------------------------------------- */

if ($nombre === '') {
    $_SESSION['flash_error'] = 'El nombre del equipo es obligatorio.';
    header("Location: ../equipos.php");
    exit;
}

if (mb_strlen($nombre, 'UTF-8') < 3) {
    $_SESSION['flash_error'] = 'El nombre debe tener al menos 3 caracteres.';
    header("Location: ../equipos.php");
    exit;
}

if (mb_strlen($nombre, 'UTF-8') > 100) {
    $_SESSION['flash_error'] = 'El nombre no puede exceder 100 caracteres.';
    header("Location: ../equipos.php");
    exit;
}

/* -------------------------------------------
   Verificar que el usuario no tenga ya un equipo
   ------------------------------------------- */

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM equipo_miembros em
        JOIN equipos e ON e.id = em.equipo_id
        WHERE em.usuario_id = ? AND em.estado = 'activo' AND e.activo = 1
    ");
    $stmt->execute([$usuario['id']]);

    if ($stmt->fetchColumn() > 0) {
        $_SESSION['flash_error'] = 'Ya perteneces a un equipo. Sal del equipo actual para crear uno nuevo.';
        header("Location: ../equipos.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("registrarEquipo - verificar equipo: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al verificar tu equipo actual.';
    header("Location: ../equipos.php");
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
        header("Location: ../equipos.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("registrarEquipo - verificar nombre: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al verificar el nombre del equipo.';
    header("Location: ../equipos.php");
    exit;
}

/* -------------------------------------------
   Crear equipo + miembro + actualizar rol
   ------------------------------------------- */

try {
    $pdo->beginTransaction();

    // 1. Insertar equipo
    $stmt = $pdo->prepare("
        INSERT INTO equipos (nombre, lider_id, descripcion)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$nombre, $usuario['id'], $descripcion ?: null]);
    $equipoId = $pdo->lastInsertId();

    // 2. Insertar como miembro activo (líder)
    $stmt = $pdo->prepare("
        INSERT INTO equipo_miembros (equipo_id, usuario_id, estado)
        VALUES (?, ?, 'activo')
    ");
    $stmt->execute([$equipoId, $usuario['id']]);

    // 3. Actualizar rol del usuario a líder (si era miembro)
    $stmt = $pdo->prepare("
        UPDATE usuarios_passball SET rol = 'lider' WHERE id = ?
    ");
    $stmt->execute([$usuario['id']]);

    $pdo->commit();

    // Actualizar sesión con el nuevo rol
    $_SESSION['usuario']['rol'] = 'lider';

    $_SESSION['flash_success'] = "Equipo \"{$nombre}\" registrado exitosamente. ¡Bienvenido, líder!";
    header("Location: ../equipos.php");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("registrarEquipo - crear: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al registrar el equipo. Intenta de nuevo.';
    header("Location: ../equipos.php");
    exit;
}
