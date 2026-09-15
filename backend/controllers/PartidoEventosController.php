<?php
require_once __DIR__ . '/../core/db.php';

class PartidoEventosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {}     // GET /api/partidos/{partidoId}/eventos — filtros: tipo, jugador_id, equipo_id
    public function registrar(array $params): void {}  // POST /api/partidos/{partidoId}/eventos
    public function actualizar(array $params): void {} // PATCH .../eventos/{eventoId}
    public function eliminar(array $params): void {}   // DELETE .../eventos/{eventoId}
}
