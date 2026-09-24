<?php
// Aislado porque lo necesitan tres lugares distintos: el endpoint /pool,
// la validación de POST /votos, y la pantalla de edición de candidatos
// del admin. Una sola fuente de verdad evita que los tres se desincronicen.

class ResolverPoolCandidatos {
    /**
     * Resuelve el pool final de candidatos de una categoría de votación.
     *
     * Reglas de negocio (fuente: sql/endpoints_implementacion.md §17):
     *  - modo 'manual'   -> el pool es exactamente los ajustes con ajuste='incluir'.
     *  - modo 'automatico' -> equipos = torneo_equipos aprobados del torneo;
     *                         jugadores = miembros cuya vigencia (fecha_union/fecha_salida)
     *                         se solapa con las fechas del torneo (fallback a membresía
     *                         'activo' actual si el torneo no tiene fechas capturadas).
     *                         Se restan los ajuste='excluir'.
     *
     * En modo 'automatico' los ajuste='excluir' se aplican SOLO en esa rama y los
     * ajuste='incluir' se ignoran; en modo 'manual' pasa al revés. La validación
     * cruzada ajuste/modo no se fuerza (decisión de producto), pero el resolver
     * siempre respeta el modo declarado por la categoría.
     *
     * @return array  shape: ['categoria_id', 'tipo', 'modo_candidatos', 'candidatos']
     */
    public static function paraCategoria(PDO $pdo, int $categoriaId): array {
        $stmt = $pdo->prepare('
            SELECT id, torneo_id, tipo, modo_candidatos, estado
            FROM torneo_categorias_voto
            WHERE id = ? LIMIT 1
        ');
        $stmt->execute([$categoriaId]);
        $categoria = $stmt->fetch();

        // La categoría no existe: los controllers validan con un 404 antes de
        // llamar aquí; si llega este caso devolvemos candidatos vacíos en lugar
        // de reventar la ejecución.
        if (!$categoria) {
            return [
                'categoria_id'    => $categoriaId,
                'tipo'            => null,
                'modo_candidatos' => null,
                'candidatos'      => [],
            ];
        }

        $tipo = $categoria['tipo'];

        if ($categoria['modo_candidatos'] === 'manual') {
            $candidatos = self::manual($pdo, $categoriaId, $tipo);
        } else {
            $candidatos = self::automatico($pdo, $categoriaId, (int) $categoria['torneo_id'], $tipo);
        }

        return [
            'categoria_id'    => $categoriaId,
            'tipo'            => $tipo,
            'modo_candidatos' => $categoria['modo_candidatos'],
            'candidatos'      => $candidatos,
        ];
    }

    /**
     * Pool en modo manual: los ajuste='incluir' cargados por el admin.
     */
    private static function manual(PDO $pdo, int $categoriaId, string $tipo): array {
        if ($tipo === 'equipo') {
            $stmt = $pdo->prepare('
                SELECT tcc.equipo_id, e.nombre AS equipo_nombre
                FROM torneo_categoria_candidatos tcc
                INNER JOIN equipos e ON e.id = tcc.equipo_id
                WHERE tcc.categoria_id = ? AND tcc.ajuste = "incluir" AND tcc.equipo_id IS NOT NULL
                ORDER BY e.nombre ASC
            ');
            $stmt->execute([$categoriaId]);
            return $stmt->fetchAll();
        }

        // tipo 'jugador': se adjunta el equipo actual (si tiene membresía activa)
        // para que la boleta muestre a qué equipo pertenece cada candidato.
        $stmt = $pdo->prepare('
            SELECT tcc.jugador_id, u.nombre AS jugador_nombre, u.matricula,
                   em.equipo_id, e.nombre AS equipo_nombre
            FROM torneo_categoria_candidatos tcc
            INNER JOIN usuarios u ON u.id = tcc.jugador_id
            LEFT JOIN equipo_miembros em ON em.jugador_id = tcc.jugador_id AND em.estado = "activo"
            LEFT JOIN equipos e ON e.id = em.equipo_id
            WHERE tcc.categoria_id = ? AND tcc.ajuste = "incluir" AND tcc.jugador_id IS NOT NULL
            ORDER BY u.nombre ASC
        ');
        $stmt->execute([$categoriaId]);
        return $stmt->fetchAll();
    }

    /**
     * Pool en modo automático: derivado del torneo, menos ajuste='excluir'.
     */
    private static function automatico(PDO $pdo, int $categoriaId, int $torneoId, string $tipo): array {
        // Los ajuste='excluir' se cargan una sola vez y se restan en la rama que aplique.
        $excluidos = self::excluidos($pdo, $categoriaId);

        // Fechas del torneo: cuando están capturadas, la vigencia de la membresía
        // debe solaparse con esa ventana; si no, se cae a "membresía activa hoy".
        $stmt = $pdo->prepare('SELECT fecha_inicio, fecha_fin FROM torneos WHERE id = ? LIMIT 1');
        $stmt->execute([$torneoId]);
        $torneo = $stmt->fetch() ?: [];

        $fechaInicio = $torneo['fecha_inicio'] ?? null;
        $fechaFin    = $torneo['fecha_fin']    ?? null;
        // Si el torneo no tiene fechas capturadas, cae al fallback de membresía activa.
        $sinFechas = !$fechaInicio || !$fechaFin;

        if ($tipo === 'equipo') {
            $stmt = $pdo->prepare('
                SELECT te.equipo_id, e.nombre AS equipo_nombre
                FROM torneo_equipos te
                INNER JOIN equipos e ON e.id = te.equipo_id
                WHERE te.torneo_id = ? AND te.estado = "aprobado" AND e.estado = "activo"
                ORDER BY e.nombre ASC
            ');
            $stmt->execute([$torneoId]);
            $candidatos = $stmt->fetchAll();

            return array_values(array_filter(
                $candidatos,
                fn($c) => !in_array((int) $c['equipo_id'], $excluidos['equipo'], true)
            ));
        }

        // Jugadores: miembros de los equipos aprobados, DISTINCT para no duplicar
        // si el historial de membresías del jugador tuviera varias filas.
        $sql = '
            SELECT DISTINCT em.jugador_id, u.nombre AS jugador_nombre, u.matricula,
                   em.equipo_id, e.nombre AS equipo_nombre
            FROM torneo_equipos te
            INNER JOIN equipo_miembros em ON em.equipo_id = te.equipo_id
            INNER JOIN equipos e ON e.id = te.equipo_id
            INNER JOIN usuarios u ON u.id = em.jugador_id
            WHERE te.torneo_id = ? AND te.estado = "aprobado" AND e.estado = "activo"
        ';
        $values = [$torneoId];

        if ($sinFechas) {
            // Fallback sin fechas: solo membresías activas en este momento.
            $sql .= ' AND em.estado = "activo" AND em.fecha_salida IS NULL';
        } else {
            // Solape de vigencia con la ventana del torneo:
            //  - la membresía inicia a más tardar cuando termina el torneo, y
            //  - la membresía termina a más temprano cuando inicia el torneo.
            $sql .= ' AND em.fecha_union <= ? AND (em.fecha_salida IS NULL OR em.fecha_salida >= ?)';
            $values[] = $fechaFin;
            $values[] = $fechaInicio;
        }

        $sql .= ' ORDER BY u.nombre ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $candidatos = $stmt->fetchAll();

        return array_values(array_filter(
            $candidatos,
            fn($c) => !in_array((int) $c['jugador_id'], $excluidos['jugador'], true)
        ));
    }

    /**
     * IDs a excluir del pool automático (ajuste='excluir' de la categoría).
     */
    private static function excluidos(PDO $pdo, int $categoriaId): array {
        $stmt = $pdo->prepare('
            SELECT jugador_id, equipo_id
            FROM torneo_categoria_candidatos
            WHERE categoria_id = ? AND ajuste = "excluir"
        ');
        $stmt->execute([$categoriaId]);

        $jugador = [];
        $equipo  = [];
        foreach ($stmt->fetchAll() as $fila) {
            if ($fila['jugador_id'] !== null) $jugador[] = (int) $fila['jugador_id'];
            if ($fila['equipo_id']  !== null) $equipo[]  = (int) $fila['equipo_id'];
        }

        return ['jugador' => $jugador, 'equipo' => $equipo];
    }
}