<?php
/**
 * =========================================================
 * PASSBALL Cup - Equipos
 * =========================================================
 */

/*
|--------------------------------------------------------------------------
| ARCHIVOS PRINCIPALES
|--------------------------------------------------------------------------
| Como este archivo está dentro de /equipos/, necesitamos subir
| un nivel para acceder a controllers, config y assets.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/controllers/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';


/*
|--------------------------------------------------------------------------
| DATOS DEL USUARIO
|--------------------------------------------------------------------------
*/

$nombreUsuario = htmlspecialchars(
    trim(
        ($usuario['nombre'] ?? '') . ' ' .
        ($usuario['apellidop'] ?? '')
    ) ?: 'Usuario',
    ENT_QUOTES,
    'UTF-8'
);

$rolUsuario = strtolower(
    $usuario['rol'] ?? 'participante'
);


/*
|--------------------------------------------------------------------------
| TEXTO DEL ROL
|--------------------------------------------------------------------------
*/

if ($rolUsuario === 'admin') {
    $textoRol = 'Administrador';
} elseif ($rolUsuario === 'lider') {
    $textoRol = 'Líder';
} else {
    $textoRol = 'Participante';
}


/*
|--------------------------------------------------------------------------
| MI EQUIPO
|--------------------------------------------------------------------------
*/

$miEquipo = null;

try {

    $stmt = $pdo->prepare("
        SELECT
            e.*,

            (
                SELECT COUNT(*)
                FROM equipo_miembros em2
                WHERE em2.equipo_id = e.id
                AND em2.estado = 'activo'
            ) AS total_miembros

        FROM equipo_miembros em

        INNER JOIN equipos e
            ON e.id = em.equipo_id

        WHERE em.usuario_id = ?
        AND em.estado = 'activo'
        AND e.activo = 1

        LIMIT 1
    ");

    $stmt->execute([
        $usuario['id']
    ]);

    $miEquipo = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log(
        "PASSBALL - Error obteniendo mi equipo: " .
        $e->getMessage()
    );

    $miEquipo = null;
}


/*
|--------------------------------------------------------------------------
| TODOS LOS EQUIPOS
|--------------------------------------------------------------------------
*/

$equipos = [];

try {

    $stmt = $pdo->query("
        SELECT
            e.*,

            (
                SELECT COUNT(*)
                FROM equipo_miembros em
                WHERE em.equipo_id = e.id
                AND em.estado = 'activo'
            ) AS total_miembros

        FROM equipos e

        WHERE e.activo = 1

        ORDER BY e.fecha_creacion DESC
    ");

    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log(
        "PASSBALL - Error listando equipos: " .
        $e->getMessage()
    );

    $equipos = [];
}


/*
|--------------------------------------------------------------------------
| MENSAJES FLASH
|--------------------------------------------------------------------------
*/

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;

unset(
    $_SESSION['flash_success'],
    $_SESSION['flash_error']
);

?>
<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Equipos |
        <?= htmlspecialchars(
            defined('TORNEO_NOMBRE')
                ? TORNEO_NOMBRE
                : 'PASSBALL Cup',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>


    <!-- =====================================================
         FUENTE
    ====================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         CSS
         IMPORTANTE: ../ porque estamos dentro de /equipos/
    ====================================================== -->

    <link
        rel="stylesheet"
        href="assets/css/equipos.css"
    >

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<header class="topbar">


    <!-- LOGO -->

    <a
        href="dashboard.php"
        class="topbar-logo"
    >

        <img
            src="assets/img/passball-cup.png"
            alt="PASSBALL Cup"
        >

    </a>


    <!-- NAVEGACIÓN -->

    <nav class="topbar-nav">


        <!-- INICIO -->

        <a
            href="dashboard.php"
            class="nav-item"
        >

            <i class="fa-solid fa-house"></i>

            <span>Inicio</span>

        </a>


                <!-- EQUIPOS -->

                <a
                    href="equipos.php"
                    class="nav-item active"
                >

            <i class="fa-solid fa-users"></i>

            <span>Equipos</span>

        </a>


        <!-- PARTIDOS -->

        <a
            href="partidos/index.php"
            class="nav-item"
        >

            <i class="fa-solid fa-calendar-days"></i>

            <span>Partidos</span>

        </a>


        <!-- VOTOS -->

        <a
            href="votos/index.php"
            class="nav-item"
        >

            <i class="fa-regular fa-circle-check"></i>

            <span>Votos</span>

        </a>


        <!-- RESULTADOS -->

        <a
            href="resultados/index.php"
            class="nav-item"
        >

            <i class="fa-solid fa-trophy"></i>

            <span>Resultados</span>

        </a>


        <!-- COMUNIDAD -->

        <a
            href="comunidad/index.php"
            class="nav-item"
        >

            <i class="fa-solid fa-comment"></i>

            <span>Comunidad</span>

        </a>

    </nav>


    <!-- USUARIO -->

    <div class="topbar-user">

        <span class="topbar-user-name">
            <?= $nombreUsuario ?>
        </span>


        <div class="topbar-avatar">

            <i class="fa-solid fa-user"></i>

        </div>


        <i class="fa-solid fa-chevron-down topbar-chevron"></i>

    </div>

</header>



<!-- =========================================================
     CONTENIDO
========================================================= -->

<div class="dashboard-content">


    <!-- =====================================================
         PANEL IZQUIERDO
    ====================================================== -->

    <aside class="profile-panel">


        <div class="profile-overlay"></div>


        <!-- PERFIL -->

        <div class="profile-top">


            <div class="profile-avatar">

                <i class="fa-solid fa-user"></i>

            </div>


            <h2>
                <?= $nombreUsuario ?>
            </h2>


            <span class="profile-role">
                <?= htmlspecialchars(
                    $textoRol,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

        </div>


        <!-- FRASE -->

        <div class="profile-quote">


            <div class="quote-mark">
                “
            </div>


            <p>
                Cada partido<br>
                es una oportunidad<br>
                para ser campeón.
            </p>


            <span>
                #PassballCup
            </span>

        </div>

    </aside>



    <!-- =====================================================
         ÁREA PRINCIPAL
    ====================================================== -->

    <main class="main-area">


        <!-- FLASH SUCCESS -->

        <?php if ($flashSuccess): ?>

            <div class="flash flash-success">

                <i class="fa-solid fa-circle-check"></i>

                <?= htmlspecialchars(
                    $flashSuccess,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <!-- FLASH ERROR -->

        <?php if ($flashError): ?>

            <div class="flash flash-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= htmlspecialchars(
                    $flashError,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             ENCABEZADO
        ================================================== -->

        <section class="page-header">


            <div>

                <h1>
                    Mis equipos
                </h1>

                <p>
                    Busca un equipo existente o registra uno nuevo.
                </p>

            </div>


        </section>



        <!-- =================================================
             MI EQUIPO
        ================================================== -->

        <?php if ($miEquipo): ?>

            <section class="my-team-card">


                <!-- LOGO -->

                <div class="my-team-logo">

                    <?php if (!empty($miEquipo['logo'])): ?>

                        <img
                            src="<?= htmlspecialchars(
                                ltrim(
                                    $miEquipo['logo'],
                                    '/'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $miEquipo['nombre'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    <?php else: ?>

                        <div
                            class="no-logo"
                            style="
                                background:
                                <?= htmlspecialchars(
                                    $miEquipo['color_equipo'] ?? '#4b2780',
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                                ?>
                            "
                        >

                            <?= htmlspecialchars(
                                mb_strtoupper(
                                    mb_substr(
                                        $miEquipo['nombre'],
                                        0,
                                        1,
                                        'UTF-8'
                                    ),
                                    'UTF-8'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>

                    <?php endif; ?>

                </div>



                <!-- INFORMACIÓN -->

                <div class="my-team-info">


                    <h2>
                        <?= htmlspecialchars(
                            $miEquipo['nombre'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h2>


                    <div class="team-meta">


                        <span class="participants">

                            <i class="fa-solid fa-users"></i>

                            <?= (int) $miEquipo['total_miembros'] ?>

                            participante<?=

                                (int) $miEquipo['total_miembros'] !== 1
                                    ? 's'
                                    : ''

                            ?>

                        </span>


                        <span class="registered">

                            <i class="fa-solid fa-circle-check"></i>

                            Equipo registrado

                        </span>

                    </div>


                    <p class="team-role">

                        <i class="fa-solid fa-star"></i>

                        <?php if ($rolUsuario === 'lider'): ?>

                            Líder del equipo

                        <?php else: ?>

                            Miembro del equipo

                        <?php endif; ?>

                    </p>

                </div>



                <!-- BOTÓN -->

                <div class="my-team-action">

                    <a
                        href="equipos/detalle.php?id=<?= (int) $miEquipo['id'] ?>"
                        class="btn-purple"
                    >

                        Ver mi equipo

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>

            </section>

        <?php endif; ?>



        <!-- =================================================
             ACCIONES
        ================================================== -->

        <section class="team-actions">


            <div class="section-title">

                <h2>
                    Equipos
                </h2>

                <p>
                    Busca un equipo existente o registra uno nuevo.
                </p>

            </div>



            <div class="action-buttons">


                <!-- BUSCAR -->

                <button
                    type="button"
                    class="action-card search-action"
                    id="focusSearch"
                >

                    <div class="action-icon purple">

                        <i class="fa-solid fa-magnifying-glass"></i>

                    </div>


                    <div class="action-text">

                        <h3>
                            Buscar equipo
                        </h3>

                        <p>
                            Encuentra un equipo y consulta su información.
                        </p>

                    </div>


                    <i class="fa-solid fa-arrow-right action-arrow"></i>

                </button>



                <!-- REGISTRAR -->

                <?php if (!$miEquipo): ?>

                    <button
                        type="button"
                        class="action-card register-action"
                        id="openRegister"
                    >

                        <div class="action-icon orange">

                            <i class="fa-solid fa-plus"></i>

                        </div>


                        <div class="action-text">

                            <h3>
                                Registrar equipo
                            </h3>

                            <p>
                                Crea un nuevo equipo y conviértete en líder.
                            </p>

                        </div>


                        <i class="fa-solid fa-arrow-right action-arrow orange-arrow"></i>

                    </button>

                <?php endif; ?>

            </div>

        </section>



        <!-- =================================================
             EQUIPOS DISPONIBLES
        ================================================== -->

        <section
            class="available-section"
            id="availableSection"
        >


            <div class="available-header">


                <div>

                    <h2>
                        Equipos disponibles
                    </h2>

                    <p>
                        Explora los equipos registrados en el torneo.
                    </p>

                </div>


                <!-- BUSCADOR -->

                <div class="search-box">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="teamSearch"
                        placeholder="Buscar equipo..."
                        autocomplete="off"
                    >

                </div>

            </div>



            <!-- GRID -->

            <div
                class="teams-grid"
                id="teamsGrid"
            >


                <?php if (!empty($equipos)): ?>


                    <?php foreach ($equipos as $eq): ?>

                        <article
                            class="team-card"
                            data-team-name="<?= htmlspecialchars(
                                mb_strtolower(
                                    $eq['nombre'],
                                    'UTF-8'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >


                            <!-- LOGO -->

                            <div class="team-card-logo">


                                <?php if (!empty($eq['logo'])): ?>

                                    <img
                                        src="<?= htmlspecialchars(
                                            ltrim(
                                                $eq['logo'],
                                                '/'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $eq['nombre'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                <?php else: ?>

                                    <div
                                        class="no-logo"
                                        style="
                                            background:
                                            <?= htmlspecialchars(
                                                $eq['color_equipo'] ?? '#4b2780',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            ?>
                                        "
                                    >

                                        <?= htmlspecialchars(
                                            mb_strtoupper(
                                                mb_substr(
                                                    $eq['nombre'],
                                                    0,
                                                    1,
                                                    'UTF-8'
                                                ),
                                                'UTF-8'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                            </div>



                            <!-- NOMBRE -->

                            <h3>

                                <?= htmlspecialchars(
                                    $eq['nombre'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </h3>



                            <!-- MIEMBROS -->

                            <p class="team-members">

                                <i class="fa-solid fa-users"></i>

                                <?= (int) $eq['total_miembros'] ?>

                                participante<?=

                                    (int) $eq['total_miembros'] !== 1
                                        ? 's'
                                        : ''

                                ?>

                            </p>



                            <!-- BOTÓN -->

                            <a
                                href="equipos/detalle.php?id=<?= (int) $eq['id'] ?>"
                                class="btn-outline
                                    <?= ((int)$eq['id'] % 2 === 0)
                                        ? 'orange'
                                        : ''
                                    ?>"
                            >

                                Ver equipo

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>


                        </article>

                    <?php endforeach; ?>


                <?php endif; ?>


            </div>



            <!-- SIN RESULTADOS -->

            <div
                class="no-results"
                id="noResults"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                <h3>
                    No encontramos ese equipo
                </h3>

                <p>
                    Intenta buscar con otro nombre.
                </p>

            </div>


        </section>



        <!-- =================================================
             AVISO PARA REGISTRAR
        ================================================== -->

        <?php if (!$miEquipo): ?>

            <section class="registration-info">


                <div class="info-icon">

                    <i class="fa-solid fa-circle-info"></i>

                </div>


                <div class="info-text">

                    <h3>
                        ¿No encuentras tu equipo?
                    </h3>

                    <p>
                        Puedes registrar uno nuevo y automáticamente
                        serás designado como líder.
                    </p>

                </div>


                <button
                    type="button"
                    class="info-button"
                    id="openRegisterBottom"
                >

                    Registrar equipo

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </section>

        <?php endif; ?>



        <!-- =================================================
             FOOTER
        ================================================== -->

        <footer class="dashboard-footer">

            &copy;
            <?= date('Y') ?>
            PASSBALL Cup —
            Todos los derechos reservados.

        </footer>


    </main>

</div>



<!-- =========================================================
     MODAL REGISTRAR EQUIPO
========================================================= -->

<div
    class="modal"
    id="registerModal"
    aria-hidden="true"
>


    <div class="modal-overlay"></div>


    <div
        class="modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="registerTitle"
    >


        <!-- CERRAR -->

        <button
            type="button"
            class="modal-close"
            id="closeRegister"
            aria-label="Cerrar"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>



        <!-- ICONO -->

        <div class="modal-icon">

            <i class="fa-solid fa-users"></i>

        </div>



        <h2 id="registerTitle">
            Registrar equipo
        </h2>


        <p class="modal-description">

            Crea un nuevo equipo para participar en
            <?= htmlspecialchars(
                defined('TORNEO_NOMBRE')
                    ? TORNEO_NOMBRE
                    : 'PASSBALL Cup',
                ENT_QUOTES,
                'UTF-8'
            ) ?>.

        </p>



        <!-- AVISO DE LÍDER -->

        <div class="leader-warning">

            <div class="leader-warning-icon">

                <i class="fa-solid fa-star"></i>

            </div>


            <div>

                <strong>
                    Serás el líder del equipo
                </strong>

                <p>
                    Al registrar este equipo,
                    automáticamente serás designado
                    como su líder.
                </p>

            </div>

        </div>



        <!-- FORMULARIO -->

        <form
            action="controllers/registrarEquipo.php"
            method="POST"
        >


            <label for="nombre_equipo">

                Nombre del equipo

            </label>


            <div class="modal-input">

                <i class="fa-solid fa-shield-halved"></i>

                <input
                    type="text"
                    id="nombre_equipo"
                    name="nombre_equipo"
                    placeholder="Ej. PASSBALL FC"
                    maxlength="100"
                    required
                    autocomplete="off"
                >

            </div>



            <label for="descripcion">

                Descripción

                <span>
                    (opcional)
                </span>

            </label>


            <textarea
                id="descripcion"
                name="descripcion"
                placeholder="Describe brevemente a tu equipo..."
                maxlength="200"
            ></textarea>



            <div class="modal-buttons">


                <button
                    type="button"
                    class="cancel-button"
                    id="cancelRegister"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="submit-button"
                >

                    Registrar equipo

                    <i class="fa-solid fa-arrow-right"></i>

                </button>


            </div>

        </form>


    </div>

</div>



<!-- =========================================================
     JAVASCRIPT
     IMPORTANTE: ../ porque estamos dentro de /equipos/
========================================================= -->

<script src="assets/js/equipos.js"></script>


</body>

</html>