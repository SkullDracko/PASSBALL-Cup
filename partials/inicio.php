<?php
/**
 * ============================================================
 * PASSBALL Cup - Inicio (vista dentro del dashboard)
 * ============================================================
 * Partial incluido en <div id="view-inicio"> de dashboard.php.
 * El header, la navegación y el banner de perfil (banner-usuario)
 * los aporta el shell (dashboard.php); aquí solo va el contenido.
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
        WHERE estado = 'activo'
    ");

    $totalEquipos = (int) $stmt->fetch()['total'];


    // Total de participantes activos
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM usuarios
        WHERE estado = 'activo'
    ");

    $totalInscritos = (int) $stmt->fetch()['total'];


    // Próximo partido
    $stmt = $pdo->query("
        SELECT
            p.*,

            el.nombre AS local_nombre,
            ev.nombre AS visita_nombre,

            tr.orden AS ronda_orden,
            tr.nombre AS ronda_nombre

        FROM partidos p

        JOIN equipos el
            ON el.id = p.equipo_local_id

        JOIN equipos ev
            ON ev.id = p.equipo_visitante_id

        JOIN torneo_rondas tr
            ON tr.id = p.ronda_id

        WHERE p.estado = 'programado'

        ORDER BY
            p.fecha_hora ASC

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


    // ¿El usuario es capitán de algún equipo (líder)?
    $lider = es_capitan();

    $equipoLider = '';

    if ($lider) {

        $stmt = $pdo->prepare("
            SELECT nombre
            FROM equipos
            WHERE capitan_id = ?
                AND estado = 'activo'
            LIMIT 1
        ");

        $stmt->execute([$usuario['id']]);

        $equipoLider = (string) $stmt->fetchColumn();
    }


} catch (PDOException $e) {

    error_log("Dashboard error: " . $e->getMessage());

    $totalEquipos = 0;
    $totalInscritos = 0;
    $proximoPartido = null;
    $totalFinalizados = 0;
    $lider = false;
    $equipoLider = '';
}
?>


<!-- BIENVENIDA -->

<div class="bienvenida">

    <h1>
        ¡Bienvenido, <?= htmlspecialchars($nombreUsuario) ?>!
        <span>👋</span>
    </h1>

    <p>
        Éxito en el torneo, demuestra tu pasión en cada partido.
    </p>

</div>


<!-- =================================================
     ESTADÍSTICAS
     ================================================= -->

<div class="estadisticas">


    <!-- EQUIPO -->

    <div class="card-estadistica">

        <div class="icono-card morado">
            <i class="fa-solid fa-users"></i>
        </div>

        <div class="info-card">

            <strong>
                <?= $totalEquipos ?>
            </strong>

            <span>EQUIPO</span>

            <small>
                Tu equipo registrado
            </small>

        </div>

    </div>


    <!-- INSCRITOS -->

    <div class="card-estadistica">

        <div class="icono-card naranja">
            <i class="fa-solid fa-user"></i>
        </div>

        <div class="info-card">

            <strong>
                <?= $totalInscritos ?>
            </strong>

            <span>INSCRITOS</span>

            <small>
                Total de participantes
            </small>

        </div>

    </div>


    <!-- PARTIDOS -->

    <div class="card-estadistica">

        <div class="icono-card morado">
            <i class="fa-solid fa-calendar-days"></i>
        </div>

        <div class="info-card">

            <strong>
                <?= $totalFinalizados ?>
            </strong>

            <span>PARTIDOS JUGADOS</span>

            <small>
                Sigue participando
            </small>

        </div>

    </div>


    <!-- LÍDER -->

    <div class="card-estadistica">

        <div class="icono-card naranja">
            <i class="fa-solid fa-star"></i>
        </div>

        <div class="info-card">

            <strong>
                <?= $lider ? 'LÍDER' : 'SIN LÍDER' ?>
            </strong>

            <small>
                <?= $lider
                    ? 'Líder de ' . htmlspecialchars($equipoLider ?: 'tu equipo')
                    : '¡Tú puedes serlo!' ?>
            </small>

        </div>

    </div>

</div>


<!-- =================================================
     PRÓXIMO PARTIDO
     ================================================= -->

<div class="proximo-partido">

    <div class="titulo-seccion">

        <i class="fa-regular fa-calendar"></i>

        <span>Próximo Partido</span>

    </div>


    <?php if ($proximoPartido): ?>

        <?php $fechaHora = strtotime($proximoPartido['fecha_hora'] ?? ''); ?>


        <!-- PARTIDO -->

        <div class="match-layout">

            <div class="team-side">

                <div
                    class="team-shield purple-shield"
                    style="--team-color: <?= htmlspecialchars(
                        $proximoPartido['local_color'] ?? '#4b2780'
                    ) ?>"
                >
                    ⚽
                </div>

                <span class="team-label">
                    TU EQUIPO
                </span>

                <strong class="team-name">
                    <?= htmlspecialchars($proximoPartido['local_nombre']) ?>
                </strong>

            </div>


            <div class="match-center">

                <span class="match-round">
                    JORNADA <?= (int) ($proximoPartido['ronda_orden'] ?? 1) ?>
                </span>

                <strong class="match-vs-large">
                    VS
                </strong>

                <div class="match-info">

                    <div>
                        <i class="fa-regular fa-calendar"></i>
                        <span>
                            <?= $fechaHora
                                ? date('d/m/Y', $fechaHora)
                                : 'Por definir' ?>
                        </span>
                    </div>

                    <?php if ($fechaHora): ?>

                        <div>
                            <i class="fa-regular fa-clock"></i>
                            <span>
                                <?= date('H:i', $fechaHora) ?>
                                hrs
                            </span>
                        </div>

                    <?php endif; ?>

                    <?php if (!empty($proximoPartido['cancha'])): ?>

                        <div>
                            <i class="fa-solid fa-location-dot"></i>
                            <span>
                                <?= htmlspecialchars($proximoPartido['cancha']) ?>
                            </span>
                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <div class="team-side">

                <div
                    class="team-shield orange-shield"
                    style="--team-color: <?= htmlspecialchars(
                        $proximoPartido['visita_color'] ?? '#ff7200'
                    ) ?>"
                >
                    ⚽
                </div>

                <span class="team-label">
                    RIVALES
                </span>

                <strong class="team-name">
                    <?= htmlspecialchars($proximoPartido['visita_nombre']) ?>
                </strong>

            </div>

        </div>


        <!-- BOTÓN -->

        <div class="match-button-container">

            <a
                href="#"
                class="match-button match-tab"
                data-target="view-partidos"
            >
                Ver detalles
                <span>→</span>
            </a>

        </div>


    <?php else: ?>


        <!-- SIN PARTIDO -->

        <div class="partido-vacio">

            <div class="icono-partido">
                <i class="fa-regular fa-calendar"></i>
            </div>

            <strong>
                No hay partidos programados aún
            </strong>

            <p>
                Cuando haya un próximo partido aparecerá aquí.
            </p>

        </div>


    <?php endif; ?>

</div>


<!-- =================================================
     ACCESOS RÁPIDOS
     ================================================= -->

<div class="accesos">


    <!-- EQUIPOS -->

    <a
        href="#"
        class="acceso quick-tab"
        data-target="view-equipos"
    >

        <div class="icono-card morado">
            <i class="fa-solid fa-users"></i>
        </div>

        <div>
            <strong>Equipos</strong>

            <p>
                Gestiona tu equipo<br>
                y conoce a los participantes
            </p>
        </div>

        <span class="flecha morado-fondo">
            →
        </span>

    </a>


    <!-- VOTOS -->

    <a
        href="#"
        class="acceso quick-tab"
        data-target="view-votos"
    >

        <div class="icono-card naranja">
            <i class="fa-solid fa-circle-plus"></i>
        </div>

        <div>
            <strong>Votos</strong>

            <p>
                Participa y vota<br>
                por tus favoritos
            </p>
        </div>

        <span class="flecha naranja-fondo">
            →
        </span>

    </a>


    <!-- COMUNIDAD -->

    <a
        href="#"
        class="acceso quick-tab"
        data-target="view-comunidad"
    >

        <div class="icono-card morado">
            <i class="fa-solid fa-comment"></i>
        </div>

        <div>
            <strong>Comunidad</strong>

            <p>
                Participa en la comunidad<br>
                y conversa con otros
            </p>
        </div>

        <span class="flecha morado-fondo">
            →
        </span>

    </a>

</div>