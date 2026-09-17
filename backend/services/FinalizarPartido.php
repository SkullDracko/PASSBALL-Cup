<?php
// Aislado porque es una transacción de varios pasos (UPDATE del partido +
// UPDATE de hasta dos partidos dependientes) usada solo por
// PartidosController::finalizar, pero con lógica suficientemente delicada
// para no repetirla en línea ni dejarla sin pruebas propias.

class FinalizarPartido {
    public static function ejecutar(PDO $pdo, int $partidoId): void {
        $pdo->beginTransaction();

        try {
            // TODO: 1) marcar partidos.estado = 'finalizado' para $partidoId
            // TODO: 2) leer ganador_id del partido
            // TODO: 3) UPDATE partidos SET equipo_local_id = ganador
            //          WHERE partido_origen_local_id = $partidoId
            // TODO: 4) UPDATE partidos SET equipo_visitante_id = ganador
            //          WHERE partido_origen_visitante_id = $partidoId

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
