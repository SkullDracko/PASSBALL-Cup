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
        $sql = "
            SELECT
                id,
                matricula,
                nombre,
                apellidop,
                apellidom,
                rol,
                estado,
                jugador_activo
            FROM usuarios
        ";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        $usuarios = $stmt->fetchAll();

        jsonResponse(true, [
            'usuarios' => $usuarios,
            'total'    => count($usuarios)
        ]);
    }
}
