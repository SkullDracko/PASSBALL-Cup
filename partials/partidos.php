<?php
/**
 * ============================================================
 * PASSBALL Cup - Partidos (vista dentro del dashboard)
 * ============================================================
 * Partial que se incluye dentro de <div id="view-partidos">
 * en dashboard.php. Contiene los datos y el markup de la
 * sección Partidos.
 * ============================================================
 */

/*
|--------------------------------------------------------------------------
| Datos temporales para el mockup
|--------------------------------------------------------------------------
| Posteriormente estos datos pueden venir directamente de MySQL.
*/

$partidos = [
    [
        'id'         => 1,
        'fecha'      => '25 MAY',
        'hora'       => '10:00 AM',
        'local'      => 'Águilas FC',
        'visitante'  => 'Tigres FC',
        'cancha'     => 'Cancha Principal',
        'estadio'    => 'Estadio Municipal',
        'estado'     => 'proximo',
        'local_icon' => '🦅',
        'visit_icon' => '🐯',
    ],
    [
        'id'         => 2,
        'fecha'      => '25 MAY',
        'hora'       => '12:00 PM',
        'local'      => 'Lobos FC',
        'visitante'  => 'Real Passball',
        'cancha'     => 'Cancha Principal',
        'estadio'    => 'Estadio Municipal',
        'estado'     => 'proximo',
        'local_icon' => '🐺',
        'visit_icon' => '⚽',
    ],
    [
        'id'         => 3,
        'fecha'      => '25 MAY',
        'hora'       => '09:00 AM',
        'local'      => 'Guerreros FC',
        'visitante'  => 'Leones FC',
        'cancha'     => 'Cancha Secundaria',
        'estadio'    => 'Estadio Municipal',
        'estado'     => 'jugando',
        'local_icon' => '🛡️',
        'visit_icon' => '🦁',
    ],
    [
        'id'         => 4,
        'fecha'      => '25 MAY',
        'hora'       => '11:00 AM',
        'local'      => 'Halcones FC',
        'visitante'  => 'Panteras FC',
        'cancha'     => 'Cancha Secundaria',
        'estadio'    => 'Estadio Municipal',
        'estado'     => 'finalizado',
        'local_icon' => '🦅',
        'visit_icon' => '🐈‍⬛',
    ],
];

$totalProgramados = 8;
$totalJugando     = 2;
$totalFinalizados = 6;
$totalGoles       = 12;

?>

<!-- ENCABEZADO -->

<div class="page-header">

    <h1>
        <i class="fa-regular fa-calendar-days"></i>
        Partidos
    </h1>

    <p>
        Consulta el calendario de partidos de
        <strong>15 minutos</strong> y los resultados
        del torneo.
    </p>

</div>


<!-- ESTADÍSTICAS -->

<section class="stats-grid partidos-stats">

    <article class="stat-card">

        <div class="stat-icon purple">

            <i class="fa-regular fa-calendar-days"></i>

        </div>

        <div class="stat-info">

            <strong><?= $totalProgramados ?></strong>

            <span>Partidos programados</span>

            <small>Próximos encuentros</small>

        </div>

    </article>


    <article class="stat-card">

        <div class="stat-icon orange">

            <i class="fa-solid fa-stopwatch"></i>

        </div>

        <div class="stat-info">

            <strong><?= $totalJugando ?></strong>

            <span>En juego</span>

            <small>Partidos en curso</small>

        </div>

    </article>


    <article class="stat-card">

        <div class="stat-icon purple">

            <i class="fa-solid fa-check"></i>

        </div>

        <div class="stat-info">

            <strong><?= $totalFinalizados ?></strong>

            <span>Finalizados</span>

            <small>Partidos completados</small>

        </div>

    </article>


    <article class="stat-card">

        <div class="stat-icon orange">

            <i class="fa-solid fa-futbol"></i>

        </div>

        <div class="stat-info">

            <strong><?= $totalGoles ?></strong>

            <span>Goles anotados</span>

            <small>En todo el torneo</small>

        </div>

    </article>

</section>


<!-- CONTENEDOR DE PARTIDOS -->

<section class="matches-section">


    <!-- BARRA DE HERRAMIENTAS -->

    <div class="matches-toolbar">


        <div class="match-tabs">

            <button
                class="match-tab active"
                data-status="proximo"
            >

                <i class="fa-regular fa-calendar-days"></i>

                Próximos

            </button>


            <button
                class="match-tab"
                data-status="jugando"
            >

                <i class="fa-regular fa-clock"></i>

                En juego

            </button>


            <button
                class="match-tab"
                data-status="finalizado"
            >

                <i class="fa-solid fa-circle-check"></i>

                Finalizados

            </button>


            <button
                class="match-tab"
                data-status="todos"
            >

                <i class="fa-solid fa-list"></i>

                Todos

            </button>

        </div>


        <div class="match-tools">


            <div class="search-box match-search">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    id="matchSearch"
                    placeholder="Buscar partido..."
                >

            </div>


            <div class="filter-box">

                <i class="fa-solid fa-filter"></i>

                <select id="courtFilter">

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

            </div>

        </div>

    </div>


    <!-- AVISO 15 MINUTOS -->

    <div class="duration-notice">

        <div class="notice-icon">

            <i class="fa-solid fa-circle-info"></i>

        </div>

        <div>

            <strong>
                Todos los partidos tienen una duración
                de 15 minutos.
            </strong>

            <p>
                Los horarios pueden variar ligeramente
                según el desarrollo del torneo.
            </p>

        </div>

    </div>


    <!-- CUERPO DEL GRID -->

    <div class="matches-layout">


        <!-- LISTA -->

        <div class="matches-list" id="matchesList">

            <?php foreach ($partidos as $partido): ?>

                <article
                    class="match-row"
                    data-status="<?= $partido['estado'] ?>"
                    data-court="<?= htmlspecialchars($partido['cancha'], ENT_QUOTES, 'UTF-8') ?>"
                    data-search="<?= htmlspecialchars(
                        strtolower(
                            $partido['local'] . ' ' .
                            $partido['visitante'] . ' ' .
                            $partido['cancha']
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >


                    <div class="match-date">

                        <strong>
                            <?= explode(' ', $partido['fecha'])[0] ?>
                        </strong>

                        <span>
                            <?= explode(' ', $partido['fecha'])[1] ?>
                        </span>

                    </div>


                    <div class="match-time">

                        <strong><?= $partido['hora'] ?></strong>

                        <span>
                            <i class="fa-regular fa-clock"></i>
                            15 min
                        </span>

                    </div>


                    <div class="match-teams">

                        <div class="team">

                            <div class="team-logo purple-logo">
                                <?= $partido['local_icon'] ?>
                            </div>

                            <strong><?= htmlspecialchars($partido['local'], ENT_QUOTES, 'UTF-8') ?></strong>

                        </div>


                        <div class="vs">

                            <span>VS</span>

                        </div>


                        <div class="team">

                            <div class="team-logo orange-logo">
                                <?= $partido['visit_icon'] ?>
                            </div>

                            <strong><?= htmlspecialchars($partido['visitante'], ENT_QUOTES, 'UTF-8') ?></strong>

                        </div>

                    </div>


                    <div class="match-location">

                        <i class="fa-solid fa-location-dot"></i>

                        <div>

                            <strong><?= htmlspecialchars($partido['cancha'], ENT_QUOTES, 'UTF-8') ?></strong>

                            <span><?= htmlspecialchars($partido['estadio'], ENT_QUOTES, 'UTF-8') ?></span>

                        </div>

                    </div>


                    <div class="match-status">

                        <?php if ($partido['estado'] === 'proximo'): ?>

                            <span class="status upcoming">
                                Próximo
                            </span>

                        <?php elseif ($partido['estado'] === 'jugando'): ?>

                            <span class="status live">

                                <span class="live-dot"></span>

                                En juego

                            </span>

                        <?php else: ?>

                            <span class="status finished">
                                Finalizado
                            </span>

                        <?php endif; ?>

                    </div>


                    <button
                        type="button"
                        class="match-details"
                        data-match="<?= $partido['id'] ?>"
                    >

                        <i class="fa-solid fa-arrow-right"></i>

                    </button>

                </article>

            <?php endforeach; ?>


            <!-- SIN RESULTADOS -->

            <div
                class="empty-results"
                id="emptyResults"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                <strong>
                    No encontramos partidos
                </strong>

                <span>
                    Intenta cambiar tu búsqueda o filtro.
                </span>

            </div>

        </div>


    </div>

</section>
