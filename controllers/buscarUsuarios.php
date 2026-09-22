<?php
/**
 * PASSBALL Cup - Buscar jugadores disponibles
 * Devuelve usuarios (rol 'usuario') por nombre o matrícula,
 * marcando si ya pertenecen a un equipo activo.
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode(['success' => true, 'usuarios' => []]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.nombre,
            u.matricula,
            u.avatar,
            (
                SELECT COUNT(*)
                FROM equipo_miembros em
                WHERE em.jugador_id = u.id
                AND em.estado = 'activo'
            ) AS en_equipo
        FROM usuarios u
        WHERE u.rol = 'usuario'
          AND u.estado = 'activo'
          AND u.id <> ?
          AND (u.nombre LIKE ? OR u.matricula LIKE ?)
        ORDER BY u.nombre ASC
        LIMIT 20
    ");

    $like = "%{$q}%";

    $stmt->execute([
        $usuario['id'],
        $like,
        $like,
    ]);

    echo json_encode([
        'success'  => true,
        'usuarios' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ]);

} catch (PDOException $e) {

    error_log("buscarUsuarios: " . $e->getMessage());

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al buscar jugadores.']);
}