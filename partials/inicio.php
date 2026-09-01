<?php
/**
 * ============================================================
 * PASSBALL Cup - Inicio (vista dentro del dashboard)
 * ============================================================
 * Partial que se incluye dentro de <div id="view-inicio">
 * en dashboard.php. Contiene los datos y el markup de la
 * sección Inicio.
 * ============================================================
 */

/*
|--------------------------------------------------------------------------
| DATOS DEL DASHBOARD
|--------------------------------------------------------------------------
*/

try {

    // Total de equipos activos
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM equipos
        WHERE activo = 1
    ");

    $totalEquipos = (int) $stmt->fetch()['total'];


    // Total de participantes activos
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM usuarios_passball
        WHERE activo = 1
    ");

    $totalInscritos = (int) $stmt->fetch()['total'];


    // Próximo partido
    $stmt = $pdo->query("
        SELECT
            p.*,

            el.nombre AS local_nombre,
            el.color_equipo AS local_color,

            ev.nombre AS visita_nombre,
            ev.color_equipo AS visita_color

        FROM partidos p

        JOIN equipos el
            ON el.id = p.equipo_local_id

        JOIN equipos ev
            ON ev.id = p.equipo_visita_id

        WHERE p.estado = 'programado'

        ORDER BY
            p.fecha ASC,
            p.hora ASC

        LIMIT 1
    ");

    $proximoPartido = $stmt->fetch();


    // Partidos finalizados
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM partidos
        WHERE estado = 'finalizado'
    ");

    $totalFinalizados = (int) $stmt->fetch()['total'];


} catch (PDOException $e) {

    error_log("Dashboard error: " . $e->getMessage());

    $totalEquipos = 0;
    $totalInscritos = 0;
    $proximoPartido = null;
    $totalFinalizados = 0;
}
?>


<!-- BIENVENIDA -->

<div class="welcome-section">

    <h1>
        ¡Bienvenido, <?= $nombreUsuario ?>! 👋
    </h1>

    <p>
        Éxito en el torneo, demuestra tu pasión en cada partido.
    </p>

</div>



<!-- =================================================
     ESTADÍSTICAS
     ================================================= -->

<section class="stats-grid">


    <!-- EQUIPO -->

    <article class="stat-card">

        <div class="stat-icon purple">

            <svg viewBox="0 0 24 24">

                <path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm-8 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3zM16 13c-3.3 0-6 1.8-6 4v2h12v-2c0-2.2-2.7-4-6-4zM8 13c-2.8 0-5 1.5-5 3.5V18h5v-2c0-1.1.4-2.1 1.1-3A6 6 0 0 0 8 13z"/>

            </svg>

        </div>


        <div class="stat-content">

            <strong>
                <?= $totalEquipos ?>
            </strong>

            <span class="stat-title">
                EQUIPO
            </span>

            <small>
                Tu equipo registrado
            </small>

        </div>

    </article>



    <!-- INSCRITOS -->

    <article class="stat-card">

        <div class="stat-icon orange">

            <svg viewBox="0 0 24 24">

                <circle
                    cx="12"
                    cy="8"
                    r="4"
                />

                <path
                    d="M4 21c.7-4 3.3-6 8-6s7.3 2 8 6"
                />

            </svg>

        </div>


        <div class="stat-content">

            <strong>
                <?= $totalInscritos ?>
            </strong>

            <span class="stat-title">
                INSCRITOS
            </span>

            <small>
                Total de participantes
            </small>

        </div>

    </article>



    <!-- PARTIDOS -->

    <article class="stat-card">

        <div class="stat-icon purple">

            <svg viewBox="0 0 24 24">

                <path d="M7 2v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7zm12 17H5V9h14v10zM7 11h4v3H7v-3z"/>

            </svg>

        </div>


        <div class="stat-content">

            <strong>
                <?= $totalFinalizados ?>
            </strong>

            <span class="stat-title">
                PARTIDOS JUGADOS
            </span>

            <small>
                Sigue participando
            </small>

        </div>

    </article>



    <!-- LÍDER -->

    <article class="stat-card">

        <div class="stat-icon orange">

            <svg viewBox="0 0 24 24">

                <path d="M12 2l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17l-5.8 3 1.1-6.5-4.7-4.6 6.5-.9L12 2z"/>

            </svg>

        </div>


        <div class="stat-content">

            <strong class="leader-text">
                LÍDER
            </strong>

            <span class="leader-message">
                Aún no hay líder
            </span>

            <small>
                ¡Tú puedes serlo!
            </small>

        </div>

    </article>


</section>



<!-- =================================================
     PRÓXIMO PARTIDO
     ================================================= -->

<section class="next-match-card">


    <!-- HEADER -->

    <div class="next-match-header">

        <div class="next-match-title">

            <svg viewBox="0 0 24 24">

                <path d="M7 2v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7zm12 17H5V9h14v10z"/>

            </svg>

            <h2>
                Próximo Partido
            </h2>

        </div>

    </div>



    <?php if ($proximoPartido): ?>


        <!-- PARTIDO -->

        <div class="match-layout">


            <!-- EQUIPO LOCAL -->

            <div class="team-side">


                <div
                    class="team-shield purple-shield"
                    style="
                        --team-color:
                        <?= htmlspecialchars(
                            $proximoPartido['local_color'] ?? '#4b2780'
                        ) ?>
                    "
                >

                    ⚽

                </div>


                <span class="team-label">
                    TU EQUIPO
                </span>


                <strong class="team-name">
                    <?= htmlspecialchars(
                        $proximoPartido['local_nombre']
                    ) ?>
                </strong>


            </div>



            <!-- CENTRO -->

            <div class="match-center">


                <span class="match-round">
                    JORNADA 1
                </span>


                <strong class="match-vs-large">
                    VS
                </strong>


                <div class="match-info">


                    <div>

                        <svg viewBox="0 0 24 24">
                            <path d="M7 2v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7zm12 17H5V9h14v10z"/>
                        </svg>

                        <span>
                            <?= date(
                                'd/m/Y',
                                strtotime($proximoPartido['fecha'])
                            ) ?>
                        </span>

                    </div>


                    <?php if (!empty($proximoPartido['hora'])): ?>

                        <div>

                            <svg viewBox="0 0 24 24">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8zm1-13h-2v6l5 3 1-1.7-4-2.3V7z"/>
                            </svg>

                            <span>
                                <?= date(
                                    'H:i',
                                    strtotime($proximoPartido['hora'])
                                ) ?>
                                hrs
                            </span>

                        </div>

                    <?php endif; ?>


                    <?php if (!empty($proximoPartido['cancha'])): ?>

                        <div>

                            <svg viewBox="0 0 24 24">
                                <path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/>
                            </svg>

                            <span>
                                <?= htmlspecialchars(
                                    $proximoPartido['cancha']
                                ) ?>
                            </span>

                        </div>

                    <?php endif; ?>


                </div>

            </div>



            <!-- EQUIPO VISITA -->

            <div class="team-side">


                <div
                    class="team-shield orange-shield"
                    style="
                        --team-color:
                        <?= htmlspecialchars(
                            $proximoPartido['visita_color'] ?? '#ff7200'
                        ) ?>
                    "
                >

                    ⚽

                </div>


                <span class="team-label">
                    RIVALES
                </span>


                <strong class="team-name">
                    <?= htmlspecialchars(
                        $proximoPartido['visita_nombre']
                    ) ?>
                </strong>


            </div>


        </div>



        <!-- BOTÓN -->

        <div class="match-button-container">

            <a
                href="partidos/index.php"
                class="match-button"
            >
                Ver detalles
                <span>→</span>
            </a>

        </div>


    <?php else: ?>


        <!-- SIN PARTIDO -->

        <div class="empty-match">

            <div class="empty-match-icon">

            </div>

            <h3>
                No hay partidos programados aún
            </h3>

            <p>
                Cuando haya un próximo partido aparecerá aquí.
            </p>

        </div>


    <?php endif; ?>


</section>



<!-- =================================================
     ACCIONES
     ================================================= -->

<section class="quick-actions">


    <!-- EQUIPOS -->

    <a
        href="equipos/index.php"
        class="quick-card purple-action"
    >

        <div class="quick-icon">

            <svg viewBox="0 0 24 24">

                <path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm-8 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3zM16 13c-3.3 0-6 1.8-6 4v2h12v-2c0-2.2-2.7-4-6-4zM8 13c-2.8 0-5 1.5-5 3.5V18h5v-2c0-1.1.4-2.1 1.1-3A6 6 0 0 0 8 13z"/>

            </svg>

        </div>


        <div>

            <h3>
                Equipos
            </h3>

            <p>
                Gestiona tu equipo<br>
                y conoce a los participantes
            </p>

        </div>


        <span class="quick-arrow">
            →
        </span>

    </a>



    <!-- VOTOS -->

    <a
        href="apuestas/index.php"
        class="quick-card orange-action"
    >

        <div class="quick-icon">

            <svg viewBox="0 0 24 24">

                <path d="M12 2a10 10 0 1 0 10 10A10.01 10.01 0 0 0 12 2zm0 17a7 7 0 1 1 7-7 7 7 0 0 1-7 7zm1-11h-2v4H8v2h3v3h2v-3h3v-2h-3V8z"/>

            </svg>

        </div>


        <div>

            <h3>
                Votos
            </h3>

            <p>
                Participa y vota<br>
                por tus favoritos
            </p>

        </div>


        <span class="quick-arrow">
            →
        </span>

    </a>



    <!-- COMUNIDAD -->

    <a
        href="comunidad/index.php"
        class="quick-card purple-action"
    >

        <div class="quick-icon">

            <svg viewBox="0 0 24 24">

                <path d="M20 4H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h3v3l4-3h9a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zM6 9h12v2H6V9zm0 4h8v2H6v-2z"/>

            </svg>

        </div>


        <div>

            <h3>
                Comunidad
            </h3>

            <p>
                Participa en la comunidad<br>
                y conversa con otros
            </p>

        </div>


        <span class="quick-arrow">
            →
        </span>

    </a>


</section>
