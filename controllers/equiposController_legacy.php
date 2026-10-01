<?php
/**
 * PASSBALL Cup - Controller de Equipos
 * CRUD de equipos y membresías (BD definitiva)
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../controllers/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper local: ¿el usuario es capitán del equipo dado?
function esCapitanDe($pdo, $usuarioId, $equipoId): bool {
    $stmt = $pdo->prepare("SELECT id FROM equipos WHERE id = ? AND capitan_id = ?");
    $stmt->execute([$equipoId, $usuarioId]);
    return (bool) $stmt->fetch();
}

function requireJugadorActivoLegacy($pdo, $usuarioId): void {
    $stmt = $pdo->prepare(
        "SELECT rol, estado, jugador_activo FROM usuarios WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$usuarioId]);
    $jugador = $stmt->fetch();

    if (
        !$jugador
        || $jugador['rol'] !== 'jugador'
        || $jugador['estado'] !== 'activo'
        || !(bool) $jugador['jugador_activo']
    ) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Se requiere un jugador activo']);
        exit;
    }
}

switch ($action) {

    // =============================
    // CREAR EQUIPO
    // =============================
    case 'crear':
        // Verificar que no tenga ya un equipo
        $stmt = $pdo->prepare("SELECT id FROM equipo_miembros WHERE jugador_id = ? AND estado = 'activo'");
        $stmt->execute([$usuario['id']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ya perteneces a un equipo. Sal del actual para crear uno nuevo.']);
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? '');

        if (strlen($nombre) < 3 || strlen($nombre) > 100) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El nombre debe tener entre 3 y 100 caracteres']);
            exit;
        }

        // Verificar nombre único
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE nombre = ?");
        $stmt->execute([$nombre]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ya existe un equipo con ese nombre']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO equipos (nombre, capitan_id, estado) VALUES (?, ?, 'activo')");
            $stmt->execute([$nombre, $usuario['id']]);
            $equipoId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO equipo_miembros (equipo_id, jugador_id, estado) VALUES (?, ?, 'activo')");
            $stmt->execute([$equipoId, $usuario['id']]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Equipo creado exitosamente. Sube un logo desde el panel.', 'equipo_id' => $equipoId]);

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Crear equipo: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear el equipo']);
        }
        break;

    // =============================
    // OBTENER TODOS LOS EQUIPOS
    // =============================
    case 'listar':
        $busqueda = trim($_GET['q'] ?? '');

        $sql = "
            SELECT
                e.*,
                u.nombre AS capitan_nombre,
                (SELECT COUNT(*) FROM equipo_miembros em WHERE em.equipo_id = e.id AND em.estado = 'activo') AS total_miembros
            FROM equipos e
            LEFT JOIN usuarios u ON u.id = e.capitan_id
            WHERE e.estado = 'activo'
        ";
        $params = [];

        if ($busqueda !== '') {
            $sql .= " AND e.nombre LIKE ?";
            $params[] = "%$busqueda%";
        }

        $sql .= " ORDER BY e.fecha_creacion DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $equipos = $stmt->fetchAll();

        echo json_encode(['success' => true, 'equipos' => $equipos]);
        break;

    // =============================
    // DETALLE DE UN EQUIPO
    // =============================
    case 'detalle':
        $equipoId = (int)($_GET['id'] ?? 0);

        if ($equipoId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de equipo inválido']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT e.*, u.nombre AS capitan_nombre
            FROM equipos e
            LEFT JOIN usuarios u ON u.id = e.capitan_id
            WHERE e.id = ? AND e.estado = 'activo'
        ");
        $stmt->execute([$equipoId]);
        $equipo = $stmt->fetch();

        if (!$equipo) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT u.id, u.matricula, u.nombre, u.avatar, em.fecha_union, em.estado
            FROM equipo_miembros em
            JOIN usuarios u ON u.id = em.jugador_id
            WHERE em.equipo_id = ? AND em.estado = 'activo'
            ORDER BY em.fecha_union ASC
        ");
        $stmt->execute([$equipoId]);
        $miembros = $stmt->fetchAll();

        $equipo['miembros'] = $miembros;

        echo json_encode(['success' => true, 'equipo' => $equipo]);
        break;

    // =============================
    // UNIRSE A UN EQUIPO
    // =============================
    case 'unirse':
        requireJugadorActivoLegacy($pdo, (int) $usuario['id']);
        $equipoId = (int)($_POST['equipo_id'] ?? 0);

        if ($equipoId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de equipo inválido']);
            exit;
        }

        // Verificar que no esté ya en un equipo
        $stmt = $pdo->prepare("SELECT id FROM equipo_miembros WHERE jugador_id = ? AND estado = 'activo'");
        $stmt->execute([$usuario['id']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ya perteneces a un equipo']);
            exit;
        }

        // Verificar que el equipo exista y esté activo
        $stmt = $pdo->prepare("SELECT id, nombre FROM equipos WHERE id = ? AND estado = 'activo'");
        $stmt->execute([$equipoId]);
        $equipo = $stmt->fetch();
        if (!$equipo) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
            exit;
        }

        // Verificar que no esté lleno (12 max)
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM equipo_miembros WHERE equipo_id = ? AND estado = 'activo'");
        $stmt->execute([$equipoId]);
        $count = $stmt->fetch()['total'];
        if ($count >= 12) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El equipo ya está lleno (máximo 12 miembros)']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO equipo_miembros (equipo_id, jugador_id, estado) VALUES (?, ?, 'activo')");
            $stmt->execute([$equipoId, $usuario['id']]);

            echo json_encode(['success' => true, 'message' => "Te uniste al equipo {$equipo['nombre']}"]);
        } catch (PDOException $e) {
            error_log("Unirse equipo: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al unirse al equipo']);
        }
        break;

    // =============================
    // SALIR DE UN EQUIPO
    // =============================
    case 'salir':
        requireJugadorActivoLegacy($pdo, (int) $usuario['id']);
        $stmt = $pdo->prepare("SELECT id FROM equipo_miembros WHERE jugador_id = ? AND estado = 'activo'");
        $stmt->execute([$usuario['id']]);
        $membresia = $stmt->fetch();

        if (!$membresia) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No perteneces a ningún equipo']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE equipo_miembros SET estado = 'inactivo' WHERE id = ?");
            $stmt->execute([$membresia['id']]);

            echo json_encode(['success' => true, 'message' => 'Saliste del equipo']);
        } catch (PDOException $e) {
            error_log("Salir equipo: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al salir del equipo']);
        }
        break;

    // =============================
    // ELIMINAR MIEMBRO (solo capitán)
    // =============================
    case 'eliminar_miembro':
        $equipoId = (int)($_POST['equipo_id'] ?? 0);
        $miembroId = (int)($_POST['miembro_id'] ?? 0);

        if (!esCapitanDe($pdo, $usuario['id'], $equipoId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }

        // No puede eliminarse a sí mismo
        if ($miembroId === $usuario['id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No puedes eliminarte a ti mismo']);
            exit;
        }

        // Verificar que el miembro exista en este equipo
        $stmt = $pdo->prepare("SELECT id FROM equipo_miembros WHERE id = ? AND equipo_id = ? AND estado = 'activo'");
        $stmt->execute([$miembroId, $equipoId]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Miembro no encontrado en este equipo']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE equipo_miembros SET estado = 'inactivo' WHERE id = ? AND equipo_id = ?");
            $stmt->execute([$miembroId, $equipoId]);

            echo json_encode(['success' => true, 'message' => 'Miembro eliminado del equipo']);
        } catch (PDOException $e) {
            error_log("Eliminar miembro: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar miembro']);
        }
        break;

    // =============================
    // CAMBIAR POSICIÓN (solo capitán)
    // =============================
    case 'cambiar_posicion':
        $equipoId  = (int)($_POST['equipo_id'] ?? 0);
        $miembroId = (int)($_POST['miembro_id'] ?? 0);
        $posicion  = strtoupper(trim($_POST['posicion'] ?? ''));

        if (!esCapitanDe($pdo, $usuario['id'], $equipoId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }

        if (!in_array($posicion, ['POR', 'DEF', 'MED', 'DEL'], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Posición no válida']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, jugador_id, equipo_id FROM equipo_miembros WHERE id = ? AND estado = 'activo'");
        $stmt->execute([$miembroId]);
        $miembro = $stmt->fetch();

        if (!$miembro || (int) $miembro['equipo_id'] !== $equipoId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Miembro no encontrado en este equipo']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE equipo_miembros SET posicion = ? WHERE id = ?");
            $stmt->execute([$posicion, $miembroId]);

            echo json_encode(['success' => true, 'message' => 'Posición actualizada']);
        } catch (PDOException $e) {
            error_log("Cambiar posición: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la posición']);
        }
        break;

    // =============================
    // INVITAR JUGADOR POR MATRÍCULA (solo capitán)
    // =============================
    case 'invitar':
        $equipoId  = (int)($_POST['equipo_id'] ?? 0);
        $matricula = trim($_POST['matricula'] ?? '');

        if (!esCapitanDe($pdo, $usuario['id'], $equipoId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }

        if ($matricula === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ingresa la matrícula del jugador']);
            exit;
        }

        // Equipo no lleno (máximo 12)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM equipo_miembros WHERE equipo_id = ? AND estado = 'activo'");
        $stmt->execute([$equipoId]);
        if ((int) $stmt->fetchColumn() >= 12) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El equipo ya está lleno (máximo 12 miembros)']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE matricula = ? AND estado = 'activo'");
        $stmt->execute([$matricula]);
        $jugador = $stmt->fetch();

        if (!$jugador) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No se encontró un jugador activo con esa matrícula']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, equipo_id FROM equipo_miembros WHERE jugador_id = ? AND estado = 'activo'");
        $stmt->execute([$jugador['id']]);
        $membresia = $stmt->fetch();

        if ($membresia) {
            if ((int) $membresia['equipo_id'] === $equipoId) {
                echo json_encode(['success' => false, 'message' => 'Ese jugador ya forma parte del equipo']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Ese jugador ya pertenece a otro equipo']);
            }
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO equipo_miembros (equipo_id, jugador_id, estado) VALUES (?, ?, 'activo')");
            $stmt->execute([$equipoId, $jugador['id']]);

            echo json_encode(['success' => true, 'message' => "{$jugador['nombre']} se unió al equipo"]);
        } catch (PDOException $e) {
            error_log("Invitar jugador: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al invitar al jugador']);
        }
        break;

    // =============================
    // VERIFICAR SI ESTOY EN UN EQUIPO
    // =============================
    case 'mi_equipo':
        $stmt = $pdo->prepare("
            SELECT e.*,
                   (SELECT COUNT(*) FROM equipo_miembros em WHERE em.equipo_id = e.id AND em.estado = 'activo') AS total_miembros
            FROM equipo_miembros em
            JOIN equipos e ON e.id = em.equipo_id
            WHERE em.jugador_id = ? AND em.estado = 'activo' AND e.estado = 'activo'
        ");
        $stmt->execute([$usuario['id']]);
        $miEquipo = $stmt->fetch();

        echo json_encode(['success' => true, 'equipo' => $miEquipo]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}