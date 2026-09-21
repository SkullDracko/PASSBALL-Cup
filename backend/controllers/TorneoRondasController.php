<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../security/authorization.php';

class TorneoRondasController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void
    {
        // $params: torneoId de la URL. Devuelve rondas ordenadas por orden.
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $this->verificarTorneo($torneoId);

        $stmt = $this->pdo->prepare('
            SELECT id, torneo_id, nombre, orden
            FROM torneo_rondas
            WHERE torneo_id = ?
            ORDER BY orden ASC, id ASC
        ');
        $stmt->execute([$torneoId]);

        jsonResponse(true, [
            'rondas' => $stmt->fetchAll()
        ]);
    }

    public function crear(array $params): void
    {
        // $params: torneoId de la URL. JSON: nombre y orden.
        requireAdminAPI();
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $this->verificarTorneo($torneoId);
        $body = jsonBody();

        $nombre = trim((string) ($body['nombre'] ?? ''));
        $orden = $this->obtenerOrden($body['orden'] ?? null);
        $this->validarNombre($nombre);

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO torneo_rondas (torneo_id, nombre, orden)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$torneoId, $nombre, $orden]);
        } catch (PDOException $e) {
            jsonResponse(false, [], [
                'error' => 'Ya existe una ronda con ese orden en el torneo'
            ], 409);
        }

        jsonResponse(true, [
            'mensaje' => 'Ronda creada correctamente',
            'id' => (int) $this->pdo->lastInsertId()
        ], [], 201);
    }

    public function actualizar(array $params): void
    {
        // $params: torneoId e id de la ronda. JSON opcional: nombre y/o orden.
        requireAdminAPI();
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $rondaId = $this->obtenerId($params, 'id', 'ronda');
        $this->verificarTorneo($torneoId);
        $actual = $this->buscarRonda($torneoId, $rondaId);

        if (!$actual) {
            $this->rondaNoEncontrada();
        }

        $body = jsonBody();
        if (!$body) {
            jsonResponse(false, [], [
                'error' => 'Debes enviar nombre u orden para actualizar'
            ], 400);
        }

        $campos = [];
        $values = [];

        if (array_key_exists('nombre', $body)) {
            $nombre = trim((string) $body['nombre']);
            $this->validarNombre($nombre);
            $campos[] = 'nombre = ?';
            $values[] = $nombre;
        }

        if (array_key_exists('orden', $body)) {
            $campos[] = 'orden = ?';
            $values[] = $this->obtenerOrden($body['orden']);
        }

        if (!$campos) {
            jsonResponse(false, [], [
                'error' => 'No hay campos válidos para actualizar'
            ], 400);
        }

        $values[] = $torneoId;
        $values[] = $rondaId;

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE torneo_rondas SET ' . implode(', ', $campos) . ' WHERE torneo_id = ? AND id = ?'
            );
            $stmt->execute($values);
        } catch (PDOException $e) {
            jsonResponse(false, [], [
                'error' => 'Ya existe una ronda con ese orden en el torneo'
            ], 409);
        }

        jsonResponse(true, [
            'mensaje' => 'Ronda actualizada correctamente'
        ]);
    }

    public function eliminar(array $params): void
    {
        // $params: torneoId e id de la ronda. No se elimina si tiene partidos.
        requireAdminAPI();
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $rondaId = $this->obtenerId($params, 'id', 'ronda');
        $this->verificarTorneo($torneoId);
        $this->buscarRonda($torneoId, $rondaId) ?: $this->rondaNoEncontrada();

        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM torneo_rondas WHERE torneo_id = ? AND id = ?'
            );
            $stmt->execute([$torneoId, $rondaId]);
        } catch (PDOException $e) {
            jsonResponse(false, [], [
                'error' => 'No se puede eliminar la ronda porque tiene partidos asociados'
            ], 409);
        }

        jsonResponse(true, [
            'mensaje' => 'Ronda eliminada correctamente'
        ]);
    }

    private function obtenerId(array $datos, string $campo, string $entidad): int
    {
        $id = $datos[$campo] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], [
                'error' => "ID de {$entidad} inválido"
            ], 400);
        }

        return (int) $id;
    }

    private function obtenerOrden($orden): int
    {
        if (!filter_var($orden, FILTER_VALIDATE_INT) || (int) $orden <= 0) {
            jsonResponse(false, [], [
                'error' => 'El orden debe ser un entero positivo'
            ], 422);
        }

        return (int) $orden;
    }

    private function validarNombre(string $nombre): void
    {
        if ($nombre === '' || mb_strlen($nombre) > 50) {
            jsonResponse(false, [], [
                'error' => 'El nombre es obligatorio y no puede superar 50 caracteres'
            ], 422);
        }
    }

    private function verificarTorneo(int $torneoId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM torneos WHERE id = ? LIMIT 1');
        $stmt->execute([$torneoId]);

        if (!$stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'Torneo no encontrado'
            ], 404);
        }
    }

    private function buscarRonda(int $torneoId, int $rondaId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, torneo_id, nombre, orden
            FROM torneo_rondas
            WHERE torneo_id = ? AND id = ?
            LIMIT 1
        ');
        $stmt->execute([$torneoId, $rondaId]);

        $ronda = $stmt->fetch();
        return $ronda ?: null;
    }

    private function rondaNoEncontrada(): void
    {
        jsonResponse(false, [], [
            'error' => 'Ronda no encontrada en este torneo'
        ], 404);
    }
}
