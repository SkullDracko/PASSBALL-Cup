<?php
require_once __DIR__ . '/../core/db.php';

// Solo lectura: agregados calculados con JOIN/GROUP BY, sin tabla propia.
class EstadisticasController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function goleadores(array $params): void {}       // GET /api/estadisticas/goleadores?torneo_id=
    public function asistencias(array $params): void {}      // GET /api/estadisticas/asistencias?torneo_id=
    public function tarjetas(array $params): void {}         // GET /api/estadisticas/tarjetas?torneo_id=
    public function porteros(array $params): void {}         // GET /api/estadisticas/porteros?torneo_id=
    public function tablaPosiciones(array $params): void {}  // GET /api/estadisticas/tabla-posiciones?torneo_id=
    public function jugador(array $params): void {}          // GET /api/estadisticas/jugador/{jugadorId}
}
