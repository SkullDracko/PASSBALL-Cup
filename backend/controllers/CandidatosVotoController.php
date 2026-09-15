<?php
require_once __DIR__ . '/../core/db.php';

class CandidatosVotoController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {}
    // GET /api/torneos/{torneoId}/categorias-voto/{categoriaId}/candidatos
    // Ajustes crudos (incluir/excluir), sin resolver — para la vista de edición del admin.

    public function agregar(array $params): void {}
    // POST /api/torneos/{torneoId}/categorias-voto/{categoriaId}/candidatos
    // TODO: validar que jugador_id/equipo_id corresponda al `tipo` de la categoría
    // (el CHECK de la tabla no cubre esto).

    public function eliminar(array $params): void {}
    // DELETE .../candidatos/{id}
}
