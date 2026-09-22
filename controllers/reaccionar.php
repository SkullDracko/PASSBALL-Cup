<?php
/**
 * PASSBALL Cup - Reaccionar a publicación
 * Upsert de reacción (like / me_encanta / me_asombra) por usuario y post.
 */

session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$postId   = (int) ($_POST['post_id'] ?? 0);
$tipo     = $_POST['tipo'] ?? 'like';
$usuario  = $_SESSION['usuario'] ?? null;

if (!$usuario) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

$tiposValidos = ['like', 'me_encanta', 'me_asombra'];
if (!in_array($tipo, $tiposValidos, true) || $postId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, tipo FROM post_reacciones WHERE post_id = ? AND usuario_id = ?");
    $stmt->execute([$postId, $usuario['id']]);
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->beginTransaction();

    if ($existente) {
        if ($existente['tipo'] === $tipo) {
            // Misma reacción: eliminar (toggle off)
            $stmt = $pdo->prepare("DELETE FROM post_reacciones WHERE id = ?");
            $stmt->execute([$existente['id']]);
            $stmt = $pdo->prepare("UPDATE posts SET likes = GREATEST(likes - 1, 0) WHERE id = ?");
            $stmt->execute([$postId]);
            $eliminada = true;
        } else {
            // Cambiar tipo de reacción
            $stmt = $pdo->prepare("UPDATE post_reacciones SET tipo = ? WHERE id = ?");
            $stmt->execute([$tipo, $existente['id']]);
            $eliminada = false;
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO post_reacciones (post_id, usuario_id, tipo) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $usuario['id'], $tipo]);
        $stmt = $pdo->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?");
        $stmt->execute([$postId]);
        $eliminada = false;
    }

    $pdo->commit();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_reacciones WHERE post_id = ?");
    $stmt->execute([$postId]);
    $total = (int) $stmt->fetchColumn();

    echo json_encode([
        'success'   => true,
        'eliminada' => $eliminada,
        'total'     => $total,
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("reaccionar: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}