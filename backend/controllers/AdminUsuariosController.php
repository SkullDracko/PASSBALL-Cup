<?php
require_once __DIR__ . '/../core/db.php';

// Listados que el panel admin necesita y que /api/usuarios no puede servir:
// ese endpoint exige sesion de JUGADOR (requireAuthAPI), asi que un admin
// recibia 401 aunque su sesion fuera valida. Aqui se replica el mismo
// contrato de filtros, pero con requireAdminAPI.

class AdminUsuariosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {
        // GET /api/admin/usuarios?estado=activo&jugador_activo=1&rol=admin

        requireAdminAPI();

        $filtros = array_merge($_GET, $params);

        $where = [];
        $values = [];

        if (!empty($filtros['estado'])) {
            $where[] = 'estado = ?';
            $values[] = $filtros['estado'];
        }

        if (isset($filtros['jugador_activo'])) {
            $valor = filter_var(
                $filtros['jugador_activo'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($valor === null) {
                jsonResponse(false, [], [
                    'error' => 'jugador_activo debe ser booleano'
                ], 400);
            }

            $where[] = 'jugador_activo = ?';
            $values[] = $valor ? 1 : 0;
        }

        if (!empty($filtros['rol'])) {
            $where[] = 'rol = ?';
            $values[] = $filtros['rol'];
        }

        // Ojo: en el esquema los apellidos van sin guion (apellidop, appellidom).
        // 'equipo' es el subquery que usaba el legacy para la columna Equipo.
        $sql = "
            SELECT
                id,
                matricula,
                nombre,
                apellidop,
                apellidom,
                rol,
                estado,
                jugador_activo,
                (
                    SELECT e.nombre
                    FROM equipo_miembros em
                    JOIN equipos e ON e.id = em.equipo_id
                    WHERE em.jugador_id = usuarios.id
                      AND em.estado = 'activo'
                    LIMIT 1
                ) AS equipo
            FROM usuarios
        ";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        // El legacy ordenaba por id ASC; la API por id DESC como usa el resto
        // del panel. Se deja configurable.
        $sql .= (!empty($filtros['orden']) && strtolower($filtros['orden']) === 'asc')
            ? ' ORDER BY id ASC'
            : ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        $usuarios = $stmt->fetchAll();

        jsonResponse(true, [
            'usuarios' => $usuarios,
            'total'    => count($usuarios)
        ]);
    }
}
