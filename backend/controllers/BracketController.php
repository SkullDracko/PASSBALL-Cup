<?php
require_once __DIR__ . '/../core/db.php';

class BracketController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function obtener(array $params): void {
        // GET /api/torneos/{torneoId}/bracket
        // Rondas -> partidos, con partido_origen_local_id/partido_origen_visitante_id resueltos
    }
}
