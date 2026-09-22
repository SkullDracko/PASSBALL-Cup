<?php
/**
 * PASSBALL Cup - Votar (participante)
 * Upsert: un voto por usuario por categoría (puede cambiarse).
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

$usuario = $_SESSION['usuario'] ?? null;

if (!$usuario) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

$categoriaId = (int) ($_POST['categoria_id'] ?? 0);
$jugadorId   = (int) ($_POST['jugador_id'] ?? 0);
$equipoId    = (int) ($_POST['equipo_id'] ?? 0);

if ($categoriaId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Categoría inválida']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, torneo_id, tipo, estado FROM torneo_categorias_voto WHERE id = ?
    ");
    $stmt->execute([$categoriaId]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
        exit;
    }

    if ($categoria['estado'] !== 'abierta') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Esta categoría no está abierta']);
        exit;
    }

    if ($categoria['tipo'] === 'jugador') {
        if ($jugadorId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Selecciona un jugador']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$jugadorId]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Jugador no válido']);
            exit;
        }
        $equipoId = null;
    } else {
        if ($equipoId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Selecciona un equipo']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE id = ?");
        $stmt->execute([$equipoId]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Equipo no válido']);
            exit;
        }
        $jugadorId = null;
    }

    $stmt = $pdo->prepare("
        INSERT INTO torneo_votos (torneo_id, usuario_id, categoria_id, jugador_id, equipo_id)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            jugador_id = VALUES(jugador_id),
            equipo_id  = VALUES(equipo_id),
            fecha_voto = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        $categoria['torneo_id'],
        $usuario['id'],
        $categoriaId,
        $jugadorId,
        $equipoId,
    ]);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM torneo_votos WHERE usuario_id = ?");
    $stmt->execute([$usuario['id']]);
    $totalVotos = (int) $stmt->fetchColumn();

    echo json_encode([
        'success'    => true,
        'message'    => 'Voto registrado.',
        'total_votos'=> $totalVotos,
    ]);

} catch (PDOException $e) {
    error_log("votar: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}