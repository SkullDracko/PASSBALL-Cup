<?php
/**
 * PASSBALL Cup - Middleware de autenticación (participante)
 * Incluir en cada página que requiera login
 * Esquema: tabla usuarios (BD definitiva)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    // El login vive en la raíz del proyecto: la ruta relativa "login.php"
    // rompe en subcarpetas (equipos/, etc.). Calculamos la raíz web del
    // proyecto a partir del documento raíz del servidor.
    $raizAbs  = str_replace('\\', '/', realpath(__DIR__ . '/../'));
    $docRoot  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $raizWeb  = ($docRoot && strpos($raizAbs, $docRoot) === 0)
        ? substr($raizAbs, strlen(rtrim($docRoot, '/')))
        : '';

    header('Location: ' . $raizWeb . '/login.php');
    exit;
}

$usuario = $_SESSION['usuario'];

// Helper: verificar si el usuario es capitán de algún equipo (antes "lider")
function es_capitan(?int $equipoId = null): bool {
    global $pdo, $usuario;

    // Si no hay $pdo (páginas ligeras), no podemos consultar
    if (!isset($pdo)) {
        return false;
    }

    $sql = "SELECT id FROM equipos WHERE capitan_id = ?";
    $params = [$usuario['id']];

    if ($equipoId !== null) {
        $sql .= " AND id = ?";
        $params[] = $equipoId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetch();
}

// Helper: rol de participante (solo 'usuario')
function tiene_rol(string $rol): bool {
    global $usuario;
    return $usuario['rol'] === $rol;
}