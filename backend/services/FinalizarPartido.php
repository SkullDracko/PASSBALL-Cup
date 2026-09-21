<?php
// Aislado porque es una transacción de varios pasos (UPDATE del partido +
// UPDATE de hasta dos partidos dependientes) usada solo por
// PartidosController::finalizar, pero con lógica suficientemente delicada
// para no repetirla en línea ni dejarla sin pruebas propias.

class FinalizarPartido {
    public static function ejecutar(PDO $pdo, int $partidoId): void {
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('
                SELECT ganador_id, estado
                FROM partidos
                WHERE id = ?
                FOR UPDATE
            ');
            $stmt->execute([$partidoId]);
            $partido = $stmt->fetch();

            if (!$partido) {
                throw new RuntimeException('Partido no encontrado');
            }

            if ($partido['ganador_id'] === null) {
                throw new RuntimeException('El partido no tiene ganador registrado');
            }

            if ($partido['estado'] === 'finalizado') {
                throw new RuntimeException('El partido ya está finalizado');
            }

            $stmt = $pdo->prepare(
                'UPDATE partidos SET estado = "finalizado" WHERE id = ?'
            );
            $stmt->execute([$partidoId]);

            $stmt = $pdo->prepare(
                'UPDATE partidos SET equipo_local_id = ? WHERE partido_origen_local_id = ?'
            );
            $stmt->execute([$partido['ganador_id'], $partidoId]);

            $stmt = $pdo->prepare(
                'UPDATE partidos SET equipo_visitante_id = ? WHERE partido_origen_visitante_id = ?'
            );
            $stmt->execute([$partido['ganador_id'], $partidoId]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
