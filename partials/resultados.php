<?php
/**
 * ============================================================
 * PASSBALL Cup - Resultados
 * ============================================================
 * Vista de resultados dentro del dashboard.
 * Este archivo funciona como partial incluido en:
 *
 * <div id="view-resultados">
 *
 * Posteriormente los datos pueden sustituirse por consultas
 * reales a MySQL.
 * ============================================================
 */


/*
|--------------------------------------------------------------------------
| ESTADÍSTICAS
|--------------------------------------------------------------------------
*/

$totalFinalizados = 24;
$totalGoles       = 78;
$mejorGoleador    = 'Carlos Mendoza';
$golesGoleador    = 15;
$mejorEquipo      = 'Águilas FC';
$victoriasEquipo  = 6;


/*
|--------------------------------------------------------------------------
| RESULTADOS
|--------------------------------------------------------------------------
*/

$resultados = [

    [
        'id'          => 1,
        'fecha'       => '24 MAY',
        'hora'        => '09:00 AM',
        'local'       => 'Águilas FC',
        'visitante'   => 'Tigres FC',
        'local_score' => 3,
        'visit_score' => 1,
        'cancha'      => 'Cancha Principal',
        'estadio'     => 'Estadio Municipal',
        'local_icon'  => '🦅',
        'visit_icon'  => '🐯',
    ],

    [
        'id'          => 2,
        'fecha'       => '24 MAY',
        'hora'        => '10:00 AM',
        'local'       => 'Lobos FC',
        'visitante'   => 'Real Passball',
        'local_score' => 2,
        'visit_score' => 2,
        'cancha'      => 'Cancha Principal',
        'estadio'     => 'Estadio Municipal',
        'local_icon'  => '🐺',
        'visit_icon'  => '⚽',
    ],

    [
        'id'          => 3,
        'fecha'       => '24 MAY',
        'hora'        => '11:00 AM',
        'local'       => 'Guerreros FC',
        'visitante'   => 'Leones FC',
        'local_score' => 0,
        'visit_score' => 4,
        'cancha'      => 'Cancha Secundaria',
        'estadio'     => 'Estadio Municipal',
        'local_icon'  => '🛡️',
        'visit_icon'  => '🦁',
    ],

    [
        'id'          => 4,
        'fecha'       => '24 MAY',
        'hora'        => '12:00 PM',
        'local'       => 'Halcones FC',
        'visitante'   => 'Panteras FC',
        'local_score' => 1,
        'visit_score' => 0,
        'cancha'      => 'Cancha Secundaria',
        'estadio'     => 'Estadio Municipal',
        'local_icon'  => '🦅',
        'visit_icon'  => '🐈‍⬛',
    ],

    [
        'id'          => 5,
        'fecha'       => '23 MAY',
        'hora'        => '05:00 PM',
        'local'       => 'Águilas FC',
        'visitante'   => 'Lobos FC',
        'local_score' => 2,
        'visit_score' => 0,
        'cancha'      => 'Cancha Principal',
        'estadio'     => 'Estadio Municipal',
        'local_icon'  => '🦅',
        'visit_icon'  => '🐺',
    ],

    [
        'id'          => 6,
        'fecha'       => '23 MAY',
        'hora'        => '06:00 PM',
        'local'       => 'Tigres FC',
        'visitante'   => 'Real Passball',
        'local_score' => 1,
        'visit_score' => 1,
        'cancha'      => 'Cancha Principal',
        'estadio'     => 'Estadio Municipal',
        'local_icon'  => '🐯',
        'visit_icon'  => '⚽',
    ],

    [
        'id'          => 7,
        'fecha'       => '22 MAY',
        'hora'        => '09:00 AM',
        'local'       => 'Leones FC',
        'visitante'   => 'Halcones FC',
        'local_score' => 3,
        'visit_score' => 2,
        'cancha'      => 'Cancha Secundaria',
        'estadio'     => 'Estadio Municipal',
        'local_icon'  => '🦁',
        'visit_icon'  => '🦅',
    ],

    [
        'id'          => 8,
        'fecha'       => '22 MAY',
        'hora'        => '10:00 AM',
        'local'       => 'Panteras FC',
        'visitante'   => 'Guerreros FC',
        'local_score' => 0,
        'visit_score' => 2,
        'cancha'      => 'Cancha Secundaria',
        'estadio'     => 'Estadio Municipal',
        'local_icon'  => '🐈‍⬛',
        'visit_icon'  => '🛡️',
    ],

];


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
        y las estadísticas del torneo.
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
                Promedio 3.25 por partido
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

            <strong><?= $victoriasEquipo ?></strong>

            <span>Victorias</span>

            <small>
                <?= htmlspecialchars($mejorEquipo, ENT_QUOTES, 'UTF-8') ?>
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

                <option value="Cancha Principal">
                    Cancha Principal
                </option>

                <option value="Cancha Secundaria">
                    Cancha Secundaria
                </option>

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
                            <?= $resultado['local_icon'] ?>
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
                            <?= $resultado['local_score'] ?>
                            <span>-</span>
                            <?= $resultado['visit_score'] ?>
                        </strong>

                    </div>


                    <!-- EQUIPO VISITANTE -->

                    <div class="result-team visitor">

                        <div class="result-team-logo orange-logo">
                            <?= $resultado['visit_icon'] ?>
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
