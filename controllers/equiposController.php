<?php
/**
 * PASSBALL Cup - Controller de Equipos
 * Maneja CRUD de equipos y membresías
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../controllers/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // =============================
    // CREAR EQUIPO
    // =============================
    case 'crear':
        // Verificar que no tenga ya un equipo (como líder o miembro)
        $stmt = $pdo->prepare("SELECT id FROM equipo_miembros WHERE usuario_id = ? AND estado = 'activo'");
        $stmt->execute([$usuario['id']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ya perteneces a un equipo. Sal del actual para crear uno nuevo.']);
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $color  = trim($_POST['color'] ?? '#7c4293');
        $desc   = trim($_POST['descripcion'] ?? '');

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

            // Crear equipo
            $stmt = $pdo->prepare("INSERT INTO equipos (nombre, lider_id, color_equipo, descripcion) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $usuario['id'], $color, $desc ?: null]);
            $equipoId = $pdo->lastInsertId();

            // Agregar al líder como miembro
            $stmt = $pdo->prepare("INSERT INTO equipo_miembros (equipo_id, usuario_id, estado) VALUES (?, ?, 'activo')");
            $stmt->execute([$equipoId, $usuario['id']]);

            // Actualizar rol del usuario a líder
            $stmt = $pdo->prepare("UPDATE usuarios_passball SET rol = 'lider' WHERE id = ?");
            $stmt->execute([$usuario['id']]);

            $pdo->commit();

            // Actualizar sesión
            $_SESSION['usuario']['rol'] = 'lider';

            echo json_encode(['success' => true, 'message' => 'Equipo creado exitosamente', 'equipo_id' => $equipoId]);

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
            SELECT e.*, 
                   u.nombre AS lider_nombre, u.apellidop AS lider_apellidop,
                   (SELECT COUNT(*) FROM equipo_miembros em WHERE em.equipo_id = e.id AND em.estado = 'activo') AS total_miembros
            FROM equipos e
            JOIN usuarios_passball u ON u.id = e.lider_id
            WHERE e.activo = 1
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
            SELECT e.*, u.nombre AS lider_nombre, u.apellidop AS lider_apellidop, u.apellidom AS lider_apellidom
            FROM equipos e
            JOIN usuarios_passball u ON u.id = e.lider_id
            WHERE e.id = ? AND e.activo = 1
        ");
        $stmt->execute([$equipoId]);
        $equipo = $stmt->fetch();

        if (!$equipo) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
            exit;
        }

        // Obtener miembros
        $stmt = $pdo->prepare("
            SELECT u.id, u.matricula, u.nombre, u.apellidop, u.apellidom, u.semestre, u.avatar,
                   em.fecha_union, em.estado
            FROM equipo_miembros em
            JOIN usuarios_passball u ON u.id = em.usuario_id
            WHERE em.equipo_id = ?
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
        $equipoId = (int)($_POST['equipo_id'] ?? 0);

        if ($equipoId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de equipo inválido']);
            exit;
        }

        // Verificar que no esté ya en un equipo
        $stmt = $pdo->prepare("SELECT id FROM equipo_miembros WHERE usuario_id = ? AND estado = 'activo'");
        $stmt->execute([$usuario['id']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ya perteneces a un equipo']);
            exit;
        }

        // Verificar que el equipo exista y esté activo
        $stmt = $pdo->prepare("SELECT id, nombre FROM equipos WHERE id = ? AND activo = 1");
        $stmt->execute([$equipoId]);
        $equipo = $stmt->fetch();
        if (!$equipo) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
            exit;
        }

        // Verificar que no esté lleno (7 max)
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM equipo_miembros WHERE equipo_id = ? AND estado = 'activo'");
        $stmt->execute([$equipoId]);
        $count = $stmt->fetch()['total'];
        if ($count >= 7) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El equipo ya está lleno (máximo 7 miembros)']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO equipo_miembros (equipo_id, usuario_id, estado) VALUES (?, ?, 'activo')");
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
        $stmt = $pdo->prepare("SELECT id FROM equipo_miembros WHERE usuario_id = ? AND estado = 'activo'");
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
    // ELIMINAR MIEMBRO (solo líder)
    // =============================
    case 'eliminar_miembro':
        if (!es_lider() && !es_admin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }

        $miembroId = (int)($_POST['miembro_id'] ?? 0);

        // Obtener el equipo del líder
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE lider_id = ? AND activo = 1");
        $stmt->execute([$usuario['id']]);
        $equipo = $stmt->fetch();

        if (!$equipo) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No tienes un equipo']);
            exit;
        }

        // No puede eliminarse a sí mismo
        if ($miembroId === $usuario['id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No puedes eliminarte a ti mismo']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE equipo_miembros SET estado = 'inactivo' WHERE equipo_id = ? AND usuario_id = ?");
            $stmt->execute([$equipo['id'], $miembroId]);

            echo json_encode(['success' => true, 'message' => 'Miembro eliminado del equipo']);
        } catch (PDOException $e) {
            error_log("Eliminar miembro: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar miembro']);
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
            WHERE em.usuario_id = ? AND em.estado = 'activo' AND e.activo = 1
        ");
        $stmt->execute([$usuario['id']]);
        $miEquipo = $stmt->fetch();

        echo json_encode(['success' => true, 'equipo' => $miEquipo]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
