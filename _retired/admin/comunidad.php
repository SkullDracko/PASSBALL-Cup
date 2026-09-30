<?php
/**
 * PASSBALL Cup - Admin: Comunidad (POST handler)
 * Acciones: crear_post, eliminar_post, toggle_fijado
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php#view-comunidad");
    exit;
}

$action = $_POST['action'] ?? '';

/* ----------------------------------------------------------------
   Resolver usuario autor (admins como usuarios del sistema)
   ---------------------------------------------------------------- */
function resolveAdminUserId(PDO $pdo, int $adminId): int
{
    $matricula = 'ADM' . str_pad((string) $adminId, 3, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE matricula = ?");
    $stmt->execute([$matricula]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return (int) $row['id'];
    }

    $insert = $pdo->prepare("
        INSERT INTO usuarios (matricula, afi_usuario_id, nombre, rol, jugador_activo, estado)
        VALUES (?, NULL, ?, 'administrador', 0, 'activo')
    ");
    $nombre = $_SESSION['admin']['nombre'] ?? 'Administrador';
    $insert->execute([$matricula, $nombre]);
    return (int) $pdo->lastInsertId();
}

switch ($action) {

    /* =============================
       CREAR POST
       ============================= */
    case 'crear_post':

        $titulo   = trim($_POST['titulo'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $imagenUrl = trim($_POST['imagen_url'] ?? '');
        $fijado   = isset($_POST['fijado']) ? 1 : 0;

        if ($titulo === '' || $contenido === '') {
            $_SESSION['flash_error'] = 'El título y el contenido son obligatorios.';
            header("Location: ../dashboard.php#view-comunidad");
            exit;
        }

        $usuarioId = resolveAdminUserId($pdo, (int) $_SESSION['admin']['id']);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO posts (usuario_id, titulo, contenido, imagen_url, fijado)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $usuarioId,
                $titulo,
                $contenido,
                $imagenUrl !== '' ? $imagenUrl : null,
                $fijado,
            ]);

            $_SESSION['flash_success'] = 'Publicación creada.';
        } catch (PDOException $e) {
            error_log("crear_post: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al crear la publicación.';
        }

        header("Location: ../dashboard.php#view-comunidad");
        exit;

    /* =============================
       ELIMINAR POST
       ============================= */
    case 'eliminar_post':

        $postId = (int) ($_POST['post_id'] ?? 0);

        if ($postId <= 0) {
            $_SESSION['flash_error'] = 'ID de publicación inválido.';
            header("Location: ../dashboard.php#view-comunidad");
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $_SESSION['flash_success'] = 'Publicación eliminada.';
        } catch (PDOException $e) {
            error_log("eliminar_post: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al eliminar la publicación.';
        }

        header("Location: ../dashboard.php#view-comunidad");
        exit;

    /* =============================
       FIJAR / DESFIJAR POST
       ============================= */
    case 'toggle_fijado':

        $postId = (int) ($_POST['post_id'] ?? 0);

        if ($postId <= 0) {
            $_SESSION['flash_error'] = 'ID de publicación inválido.';
            header("Location: ../dashboard.php#view-comunidad");
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE posts SET fijado = NOT fijado WHERE id = ?");
            $stmt->execute([$postId]);
            $_SESSION['flash_success'] = 'Estado de fijado actualizado.';
        } catch (PDOException $e) {
            error_log("toggle_fijado: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al actualizar el fijado.';
        }

        header("Location: ../dashboard.php#view-comunidad");
        exit;

    default:
        $_SESSION['flash_error'] = 'Acción no válida.';
        header("Location: ../dashboard.php#view-comunidad");
        exit;
}
