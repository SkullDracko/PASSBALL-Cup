<?php
require_once __DIR__ . '/../core/db.php';

class EquipoMiembrosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {}     // GET /api/equipos/{equipoId}/miembros
    public function agregar(array $params): void {}    // POST /api/equipos/{equipoId}/miembros
    public function marcarSalida(array $params): void {} // PATCH /api/equipos/{equipoId}/miembros/{jugadorId}/salida
    public function eliminar(array $params): void {}   // DELETE /api/equipos/{equipoId}/miembros/{jugadorId}

    public function equipoActual(array $params): void {}    // GET /api/jugadores/{jugadorId}/equipo-actual
    public function historialEquipos(array $params): void {} // GET /api/jugadores/{jugadorId}/historial-equipos
}
