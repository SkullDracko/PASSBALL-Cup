<?php
/**
 * PASSBALL Cup - Admin: Postulaciones (POST handler)
 * Acciones: aprobar, rechazar
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php#view-postulaciones");
    exit;
}

$action    = $_POST['action'] ?? '';
$postId    = (int) ($_POST['postulacion_id'] ?? 0);
$torneoId  = (int) ($_POST['torneo_id'] ?? 0);

if ($postId <= 0 || $torneoId <= 0) {
    $_SESSION['flash_error'] = 'Parámetros inválidos.';
    header("Location: ../dashboard.php#view-postulaciones");
    exit;
}

// Verificar que la postulación exista y esté pendiente
$stmt = $pdo->prepare("SELECT id, estado FROM torneo_equipos WHERE id = ? AND torneo_id = ?");
$stmt->execute([$postId, $torneoId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    $_SESSION['flash_error'] = 'La postulación no existe.';
    header("Location: ../dashboard.php#view-postulaciones");
    exit;
}

if ($post['estado'] !== 'pendiente') {
    $_SESSION['flash_error'] = 'La postulación ya fue procesada.';
    header("Location: ../dashboard.php#view-postulaciones");
    exit;
}

switch ($action) {

    case 'aprobar':
        $stmt = $pdo->prepare("
            UPDATE torneo_equipos
            SET estado = 'aprobado', aprobado_por = ?, fecha_aprobacion = NOW()
            WHERE id = ? AND torneo_id = ?
        ");
        $stmt->execute([$admin['id'], $postId, $torneoId]);
        $_SESSION['flash_success'] = 'Postulación aprobada.';
        break;

    case 'rechazar':
        $motivo = trim($_POST['motivo'] ?? '');
        $stmt = $pdo->prepare("
            UPDATE torneo_equipos
            SET estado = 'rechazado', aprobado_por = ?, fecha_aprobacion = NOW(), motivo_rechazo = ?
            WHERE id = ? AND torneo_id = ?
        ");
        $stmt->execute([$admin['id'], $motivo ?: null, $postId, $torneoId]);
        $_SESSION['flash_success'] = 'Postulación rechazada.';
        break;

    default:
        $_SESSION['flash_error'] = 'Acción no válida.';
        break;
}

header("Location: ../dashboard.php#view-postulaciones");
exit;