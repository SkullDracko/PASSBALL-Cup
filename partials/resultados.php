<?php
/**
 * ============================================================
 * PASSBALL Cup - Resultados
 * ============================================================
 * Vista de resultados dentro del dashboard del participante.
 * Datos reales: partidos finalizados y top goleador derivado
 * de partido_eventos.
 * ============================================================
 */


/*
|--------------------------------------------------------------------------
| TORNEO ACTIVO
|--------------------------------------------------------------------------
*/

$torneoActivo = $pdo
    ->query("SELECT id, nombre FROM torneos WHERE estado = 'en_curso' ORDER BY id DESC LIMIT 1")
    ->fetch(PDO::FETCH_ASSOC);

if (!$torneoActivo) {
    $torneoActivo = $pdo
        ->query("SELECT id, nombre FROM torneos ORDER BY id DESC LIMIT 1")
        ->fetch(PDO::FETCH_ASSOC);
}

$torneoId = (int) ($torneoActivo['id'] ?? 0);
$torneoNombre = $torneoActivo['nombre'] ?? 'PASSBALL Cup';


/*
|--------------------------------------------------------------------------
| ESTADÍSTICAS
|--------------------------------------------------------------------------
*/

$totalFinalizados  = 0;
$totalGoles        = 0;
$mejorGoleador     = '—';
$golesGoleador     = 0;
$mejorEquipo       = '—';
$equipoVictorias   = '—';
$winsVictorias     = 0;

if ($torneoId > 0) {

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM partidos p
        JOIN torneo_rondas r ON r.id = p.ronda_id
        WHERE r.torneo_id = ? AND p.estado = 'finalizado'
    ");
    $stmt->execute([$torneoId]);
    $totalFinalizados = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(COALESCE(p.goles_local,0) + COALESCE(p.goles_visitante,0)), 0) AS total
        FROM partidos p
        JOIN torneo_rondas r ON r.id = p.ronda_id
        WHERE r.torneo_id = ? AND p.estado = 'finalizado'
    ");
    $stmt->execute([$torneoId]);
    $totalGoles = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT u.nombre AS jugador, e.nombre AS equipo, COUNT(*) AS goles
        FROM partido_eventos pe
        JOIN usuarios u ON u.id = pe.jugador_id
        JOIN equipos e ON e.id = pe.equipo_id
        JOIN partidos p ON p.id = pe.partido_id
        JOIN torneo_rondas r ON r.id = p.ronda_id
        WHERE r.torneo_id = ? AND pe.tipo IN ('gol', 'penal_anotado')
        GROUP BY pe.jugador_id, e.id
        ORDER BY goles DESC, u.nombre ASC
        LIMIT 1
    ");
    $stmt->execute([$torneoId]);
    $goleador = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($goleador) {
        $mejorGoleador = $goleador['jugador'];
        $golesGoleador = (int) $goleador['goles'];
        $mejorEquipo   = $goleador['equipo'];
    }

    $stmt = $pdo->prepare("
        SELECT e.nombre AS equipo, COUNT(*) AS wins
        FROM partidos p
        JOIN torneo_rondas r ON r.id = p.ronda_id
        JOIN equipos e ON e.id = p.ganador_id
        WHERE r.torneo_id = ? AND p.estado = 'finalizado' AND p.ganador_id IS NOT NULL
        GROUP BY p.ganador_id
        ORDER BY wins DESC, e.nombre ASC
        LIMIT 1
    ");
    $stmt->execute([$torneoId]);
    $ganador = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ganador) {
        $equipoVictorias = $ganador['equipo'];
        $winsVictorias   = (int) $ganador['wins'];
    }

    $stmt = $pdo->prepare("
        SELECT DISTINCT p.cancha
        FROM partidos p
        JOIN torneo_rondas r ON r.id = p.ronda_id
        WHERE r.torneo_id = ? AND p.cancha IS NOT NULL AND p.cancha <> ''
        ORDER BY p.cancha
    ");
    $stmt->execute([$torneoId]);
    $canchas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    $canchas = [];
}


/*
|--------------------------------------------------------------------------
| RESULTADOS
|--------------------------------------------------------------------------
*/

$resultados = [];

if ($torneoId > 0) {

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.fecha_hora,
            p.cancha,
            p.goles_local,
            p.goles_visitante,
            l.nombre AS local,
            l.logo   AS local_logo,
            v.nombre AS visitante,
            v.logo   AS visitante_logo,
            r.nombre AS ronda
        FROM partidos p
        JOIN torneo_rondas r ON r.id = p.ronda_id
        LEFT JOIN equipos l ON l.id = p.equipo_local_id
        LEFT JOIN equipos v ON v.id = p.equipo_visitante_id
        WHERE r.torneo_id = ? AND p.estado = 'finalizado'
        ORDER BY p.fecha_hora DESC, p.id DESC
    ");
    $stmt->execute([$torneoId]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {

        $fechaHora = $fila['fecha_hora'] ? strtotime($fila['fecha_hora']) : false;

        $marcadorLocal = $fila['goles_local'] !== null
            ? (int) $fila['goles_local']
            : '—';

        $marcadorVisit = $fila['goles_visitante'] !== null
            ? (int) $fila['goles_visitante']
            : '—';

        $resultados[] = [
            'id'          => (int) $fila['id'],
            'fecha'       => $fechaHora
                ? strtoupper(date('d M', $fechaHora))
                : '—',
            'hora'        => $fechaHora
                ? date('g:i A', $fechaHora)
                : '—',
            'local'       => $fila['local'] ?? '—',
            'visitante'   => $fila['visitante'] ?? '—',
            'local_score' => $marcadorLocal,
            'visit_score' => $marcadorVisit,
            'local_logo'  => $fila['local_logo'],
            'visit_logo'  => $fila['visitante_logo'],
            'cancha'      => $fila['cancha'] ?: 'Por definir',
            'estadio'     => $fila['ronda'] ?? '',
        ];
    }
}

?>

<!-- ============================================================
     ENCABEZADO
     ============================================================ -->

<div class="page-header">

    <h1>
        <i class="fa-solid fa-trophy"></i>
        Resultados
    </h1>

    <p>
        Consulta los resultados de los partidos finalizados
        y las estadísticas de <strong><?= htmlspecialchars($torneoNombre, ENT_QUOTES, 'UTF-8') ?></strong>.
    </p>

</div>


<!-- ============================================================
     ESTADÍSTICAS
     ============================================================ -->

<section class="stats-grid resultados-stats">

    <article class="stat-card">

        <div class="stat-icon purple">

            <i class="fa-solid fa-trophy"></i>

        </div>

        <div class="stat-info">

            <strong><?= $totalFinalizados ?></strong>

            <span>Partidos finalizados</span>

            <small>En todo el torneo</small>

        </div>

    </article>


    <article class="stat-card">

        <div class="stat-icon orange">

            <i class="fa-solid fa-futbol"></i>

        </div>

        <div class="stat-info">

            <strong><?= $totalGoles ?></strong>

            <span>Goles anotados</span>

            <small>
                <?= $totalFinalizados > 0
                    ? 'Promedio ' . number_format($totalGoles / $totalFinalizados, 2) . ' por partido'
                    : 'Aún no hay partidos jugados' ?>
            </small>

        </div>

    </article>


    <article class="stat-card">

        <div class="stat-icon purple">

            <i class="fa-solid fa-shoe-prints"></i>

        </div>

        <div class="stat-info">

            <strong><?= $golesGoleador ?></strong>

            <span>Mejor goleador</span>

            <small>
                <?= htmlspecialchars($mejorGoleador, ENT_QUOTES, 'UTF-8') ?>
                (<?= htmlspecialchars($mejorEquipo, ENT_QUOTES, 'UTF-8') ?>)
            </small>

        </div>

    </article>


    <article class="stat-card">

        <div class="stat-icon orange">

            <i class="fa-solid fa-table-cells-large"></i>

        </div>

        <div class="stat-info">

            <strong><?= $winsVictorias ?></strong>

            <span>Victorias</span>

            <small>
                <?= htmlspecialchars($equipoVictorias, ENT_QUOTES, 'UTF-8') ?>
            </small>

        </div>

    </article>

</section>


<!-- ============================================================
     FILTROS
     ============================================================ -->

<section class="results-filter-card">

    <div class="results-tabs">

        <button
            type="button"
            class="result-tab active"
            data-filter="todos"
        >
            <i class="fa-solid fa-trophy"></i>
            Todos los resultados
        </button>

        <button
            type="button"
            class="result-tab"
            data-filter="fecha"
        >
            <i class="fa-regular fa-calendar"></i>
            Por fecha
        </button>

        <button
            type="button"
            class="result-tab"
            data-filter="equipo"
        >
            <i class="fa-solid fa-users"></i>
            Por equipo
        </button>

        <button
            type="button"
            class="result-tab"
            data-filter="goleadores"
        >
            <i class="fa-solid fa-shoe-prints"></i>
            Goleadores
        </button>

        <button
            type="button"
            class="result-tab"
            data-filter="invictas"
        >
            <i class="fa-solid fa-table-cells-large"></i>
            Vallas invictas
        </button>

    </div>


    <div class="results-filters-row">

        <div class="results-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                id="resultsSearch"
                placeholder="Buscar equipo o partido..."
            >

        </div>


        <div class="results-select">

            <select id="resultsCourt">

                <option value="todos">
                    Todas las canchas
                </option>

                <?php foreach ($canchas as $cancha): ?>

                    <option value="<?= htmlspecialchars($cancha, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($cancha, ENT_QUOTES, 'UTF-8') ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <i class="fa-solid fa-chevron-down"></i>

        </div>


        <div class="results-date">

            <span>Desde</span>

            <i class="fa-regular fa-calendar"></i>

            <input
                type="date"
                id="resultsDateFrom"
            >

        </div>


        <div class="results-date">

            <span>Hasta</span>

            <i class="fa-regular fa-calendar"></i>

            <input
                type="date"
                id="resultsDateTo"
            >

        </div>


        <button
            type="button"
            id="clearResultsFilters"
            class="clear-results"
        >

            <i class="fa-solid fa-xmark"></i>

            Limpiar filtros

        </button>

    </div>

</section>


<!-- ============================================================
     CONTENIDO PRINCIPAL
     ============================================================ -->

<div class="results-main-layout">


    <!-- ========================================================
         RESULTADOS
         ======================================================== -->

    <section class="results-card">

        <div class="results-card-header">

            <h2>
                Resultados de partidos
            </h2>

        </div>


        <div
            class="results-list"
            id="resultsList"
        >

            <?php foreach ($resultados as $resultado): ?>

                <article
                    class="result-row"
                    data-court="<?= htmlspecialchars($resultado['cancha'], ENT_QUOTES, 'UTF-8') ?>"
                    data-search="<?= htmlspecialchars(
                        strtolower(
                            $resultado['local'] . ' ' .
                            $resultado['visitante'] . ' ' .
                            $resultado['cancha']
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >


                    <!-- FECHA -->

                    <div class="result-date">

                        <strong>
                            <?= explode(' ', $resultado['fecha'])[0] ?>
                        </strong>

                        <span>
                            <?= explode(' ', $resultado['fecha'])[1] ?>
                        </span>

                        <small>
                            <?= htmlspecialchars($resultado['hora'], ENT_QUOTES, 'UTF-8') ?>
                        </small>

                    </div>


                    <!-- EQUIPO LOCAL -->

                    <div class="result-team local">

                        <div class="result-team-logo purple-logo">
                            <?php if (!empty($resultado['local_logo'])): ?>
                                <img
                                    src="<?= htmlspecialchars($resultado['local_logo'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($resultado['local'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            <?php else: ?>
                                <?= mb_strtoupper(mb_substr($resultado['local'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>

                        <strong>
                            <?= htmlspecialchars(
                                $resultado['local'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                    </div>


                    <!-- MARCADOR -->

                    <div class="result-score">

                        <strong>
                            <?= $resultado['local_score'] == '—'
                                ? '—'
                                : $resultado['local_score'] ?>
                            <span>-</span>
                            <?= $resultado['visit_score'] == '—'
                                ? '—'
                                : $resultado['visit_score'] ?>
                        </strong>

                    </div>


                    <!-- EQUIPO VISITANTE -->

                    <div class="result-team visitor">

                        <div class="result-team-logo orange-logo">
                            <?php if (!empty($resultado['visit_logo'])): ?>
                                <img
                                    src="<?= htmlspecialchars($resultado['visit_logo'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($resultado['visitante'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            <?php else: ?>
                                <?= mb_strtoupper(mb_substr($resultado['visitante'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>

                        <strong>
                            <?= htmlspecialchars(
                                $resultado['visitante'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                    </div>


                    <!-- UBICACIÓN -->

                    <div class="result-location">

                        <strong>
                            <?= htmlspecialchars(
                                $resultado['cancha'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars(
                                $resultado['estadio'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                    </div>


                    <!-- DETALLES -->

                    <button
                        type="button"
                        class="result-details"
                        data-result="<?= $resultado['id'] ?>"
                    >

                        Ver detalles

                        <i class="fa-solid fa-arrow-right"></i>

                    </button>

                </article>

            <?php endforeach; ?>


            <!-- SIN RESULTADOS -->

            <div
                class="results-empty"
                id="resultsEmpty"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                <strong>
                    No encontramos resultados
                </strong>

                <span>
                    Intenta cambiar tu búsqueda o filtros.
                </span>

            </div>

        </div>


        <button
            type="button"
            class="load-more-results"
            id="loadMoreResults"
        >

            Ver más resultados

            <i class="fa-solid fa-chevron-down"></i>

        </button>

    </section>


</div>


<!-- ============================================================
     AVISO FINAL
     ============================================================ -->

<section class="results-notice">

    <div class="results-notice-icon">

        <i class="fa-solid fa-circle-info"></i>

    </div>


    <div class="results-notice-text">

        <strong>
            Los resultados se actualizan en tiempo real
        </strong>

        <span>
            Los marcadores pueden tener cambios hasta ser
            validados por el comité organizador.
        </span>

    </div>


    <button
        type="button"
        class="results-stats-button"
    >

        Estadísticas completas

        <i class="fa-solid fa-arrow-right"></i>

    </button>

</section>