<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../security/authorization.php';

class TorneosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void
    {
        // GET /api/torneos

        $stmt = $this->pdo->query('
            SELECT id, nombre, tipo, fecha_inicio, fecha_fin, estado, fecha_creacion
            FROM torneos
            ORDER BY fecha_creacion DESC, id DESC
        ');

        jsonResponse(true, [
            'torneos' => $stmt->fetchAll()
        ]);
    }

    public function crear(array $params): void
    {
        // POST /api/torneos

        requireAdminAPI();
        $body = jsonBody();

        $nombre = trim((string) ($body['nombre'] ?? ''));
        $tipo = (string) ($body['tipo'] ?? 'eliminacion_directa');
        $fechaInicio = $body['fecha_inicio'] ?? null;
        $fechaFin = $body['fecha_fin'] ?? null;

        $this->validarNombre($nombre);
        requerirEnum($tipo, ['eliminacion_directa'], 'tipo');
        $this->validarFechas($fechaInicio, $fechaFin);

        $stmt = $this->pdo->prepare('
            INSERT INTO torneos (nombre, tipo, fecha_inicio, fecha_fin)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$nombre, $tipo, $fechaInicio, $fechaFin]);

        jsonResponse(true, [
            'mensaje' => 'Torneo creado correctamente',
            'id' => (int) $this->pdo->lastInsertId()
        ], [], 201);
    }

    public function detalle(array $params): void
    {
        // GET /api/torneos/{id}

        $id = $this->obtenerId($params);
        $torneo = $this->buscarPorId($id);

        if (!$torneo) {
            $this->noEncontrado();
        }

        jsonResponse(true, [
            'torneo' => $torneo
        ]);
    }

    public function actualizar(array $params): void
    {
        // PATCH /api/torneos/{id}

        requireAdminAPI();
        $id = $this->obtenerId($params);
        $actual = $this->buscarPorId($id);

        if (!$actual) {
            $this->noEncontrado();
        }

        $body = jsonBody();

        if (!$body) {
            jsonResponse(false, [], [
                'error' => 'Debes enviar al menos un campo para actualizar'
            ], 400);
        }

        $nombre = array_key_exists('nombre', $body)
            ? trim((string) $body['nombre'])
            : $actual['nombre'];
        $tipo = array_key_exists('tipo', $body) ? (string) $body['tipo'] : $actual['tipo'];
        $fechaInicio = array_key_exists('fecha_inicio', $body)
            ? $body['fecha_inicio']
            : $actual['fecha_inicio'];
        $fechaFin = array_key_exists('fecha_fin', $body)
            ? $body['fecha_fin']
            : $actual['fecha_fin'];

        $this->validarNombre($nombre);
        requerirEnum($tipo, ['eliminacion_directa'], 'tipo');
        $this->validarFechas($fechaInicio, $fechaFin);

        $campos = [];
        $values = [];

        foreach (['nombre' => $nombre, 'tipo' => $tipo, 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin] as $campo => $valor) {
            if (array_key_exists($campo, $body)) {
                $campos[] = "{$campo} = ?";
                $values[] = $valor;
            }
        }

        if (array_key_exists('estado', $body)) {
            $estado = (string) $body['estado'];
            requerirEnum($estado, ['programado', 'en_curso', 'finalizado', 'cancelado'], 'estado');
            $campos[] = 'estado = ?';
            $values[] = $estado;
        }

        if (!$campos) {
            jsonResponse(false, [], [
                'error' => 'No hay campos válidos para actualizar'
            ], 400);
        }

        $values[] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE torneos SET ' . implode(', ', $campos) . ' WHERE id = ?'
        );
        $stmt->execute($values);

        jsonResponse(true, [
            'mensaje' => 'Torneo actualizado correctamente'
        ]);
    }

    public function eliminar(array $params): void
    {
        // DELETE /api/torneos/{id}

        requireAdminAPI();
        $id = $this->obtenerId($params);
        $eliminado = false;

        try {
            $stmt = $this->pdo->prepare('DELETE FROM torneos WHERE id = ?');
            $stmt->execute([$id]);
            $eliminado = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            jsonResponse(false, [], [
                'error' => 'No se puede eliminar el torneo porque tiene rondas o inscripciones asociadas'
            ], 409);
        }

        if (!$eliminado) {
            $this->noEncontrado();
        }

        jsonResponse(true, [
            'mensaje' => 'Torneo eliminado correctamente'
        ]);
    }

    private function obtenerId(array $params): int
    {
        $id = $params['id'] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], [
                'error' => 'ID de torneo inválido'
            ], 400);
        }

        return (int) $id;
    }

    private function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, nombre, tipo, fecha_inicio, fecha_fin, estado, fecha_creacion
            FROM torneos
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$id]);

        $torneo = $stmt->fetch();
        return $torneo ?: null;
    }

    private function validarNombre(string $nombre): void
    {
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            jsonResponse(false, [], [
                'error' => 'El nombre es obligatorio y no puede superar 100 caracteres'
            ], 400);
        }
    }

    private function validarFechas($fechaInicio, $fechaFin): void
    {
        foreach (['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin] as $campo => $fecha) {
            if ($fecha !== null && !$this->esFechaValida($fecha)) {
                jsonResponse(false, [], [
                    'error' => "{$campo} debe tener formato YYYY-MM-DD"
                ], 400);
            }
        }

        if ($fechaInicio !== null && $fechaFin !== null && $fechaInicio > $fechaFin) {
            jsonResponse(false, [], [
                'error' => 'La fecha de inicio no puede ser posterior a la fecha de fin'
            ], 422);
        }
    }

    private function esFechaValida($fecha): bool
    {
        if (!is_string($fecha)) {
            return false;
        }

        $fechaObjeto = DateTime::createFromFormat('!Y-m-d', $fecha);
        return $fechaObjeto !== false
            && $fechaObjeto->format('Y-m-d') === $fecha;
    }

    private function noEncontrado(): void
    {
        jsonResponse(false, [], [
            'error' => 'Torneo no encontrado'
        ], 404);
    }
}
