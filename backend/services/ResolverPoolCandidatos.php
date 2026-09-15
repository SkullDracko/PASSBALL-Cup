<?php
// Aislado porque lo necesitan tres lugares distintos: el endpoint /pool,
// la validación de POST /votos, y la pantalla de edición de candidatos
// del admin. Una sola fuente de verdad evita que los tres se desincronicen.

class ResolverPoolCandidatos {
    public static function paraCategoria(PDO $pdo, int $categoriaId): array {
        // TODO: leer torneo_categorias_voto (torneo_id, tipo, modo_candidatos)

        // TODO: si modo_candidatos = 'manual':
        //   devolver solo los jugador_id/equipo_id con ajuste = 'incluir'
        //   en torneo_categoria_candidatos para esta categoría

        // TODO: si modo_candidatos = 'automatico':
        //   - equipos: torneo_equipos.estado = 'aprobado' para el torneo
        //   - jugadores: equipo_miembros cuya vigencia (fecha_union/fecha_salida)
        //     se solapa con torneos.fecha_inicio/fecha_fin (fallback: estado='activo'
        //     actual si el torneo no tiene fechas capturadas)
        //   restar los que tengan ajuste = 'excluir' en torneo_categoria_candidatos

        return [];
    }
}
