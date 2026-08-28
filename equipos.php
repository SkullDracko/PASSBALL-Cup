<?php
/**
 * PASSBALL Cup - Equipos
 */

require_once __DIR__ . '/controllers/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

/*
 * Datos del usuario
 */
$nombreUsuario = htmlspecialchars(
    trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidop'] ?? ''))
    ?: 'Usuario'
);

$rolUsuario = ucfirst($usuario['rol'] ?? 'miembro');


/*
 * Mi equipo
 */
$miEquipo = null;

try {
    $stmt = $pdo->prepare("
        SELECT e.*,
               (SELECT COUNT(*) FROM equipo_miembros em
                WHERE em.equipo_id = e.id AND em.estado = 'activo') AS total_miembros
        FROM equipo_miembros em
        JOIN equipos e ON e.id = em.equipo_id
        WHERE em.usuario_id = ? AND em.estado = 'activo' AND e.activo = 1
        LIMIT 1
    ");
    $stmt->execute([$usuario['id']]);
    $miEquipo = $stmt->fetch();

    if ($miEquipo) {
        $miEquipo['nombre']     = htmlspecialchars($miEquipo['nombre']);
        $miEquipo['descripcion'] = htmlspecialchars($miEquipo['descripcion'] ?? '');
    }

} catch (PDOException $e) {
    error_log("Equipos - mi equipo: " . $e->getMessage());
}


/*
 * Todos los equipos activos
 */
$equipos = [];

try {
    $stmt = $pdo->query("
        SELECT e.*,
               (SELECT COUNT(*) FROM equipo_miembros em
                WHERE em.equipo_id = e.id AND em.estado = 'activo') AS total_miembros
        FROM equipos e
        WHERE e.activo = 1
        ORDER BY e.fecha_creacion DESC
    ");
    $equipos = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Equipos - listar: " . $e->getMessage());
}


/*
 * Flash messages
 */
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Equipos | <?= htmlspecialchars(TORNEO_NOMBRE) ?></title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- CSS -->
    <link
        rel="stylesheet"
        href="assets/css/equipos.css"
    >

</head>

<body>

<!-- =========================================================
     NAVBAR
========================================================= -->

<header class="navbar">

    <div class="navbar-logo">

        <img
            src="assets/img/passball-cup.png"
            alt="PASSBALL Cup"
        >

    </div>


    <nav class="navbar-menu">

        <a href="dashboard.php">
            <i class="fa-solid fa-house"></i>
            <span>Inicio</span>
        </a>

        <a href="equipos.php" class="active">
            <i class="fa-solid fa-users"></i>
            <span>Equipos</span>
        </a>

        <a href="partidos.php">
            <i class="fa-solid fa-calendar-days"></i>
            <span>Partidos</span>
        </a>

        <a href="votos.php">
            <i class="fa-regular fa-circle-check"></i>
            <span>Votos</span>
        </a>

        <a href="resultados.php">
            <i class="fa-solid fa-trophy"></i>
            <span>Resultados</span>
        </a>

        <a href="comunidad.php">
            <i class="fa-solid fa-comment"></i>
            <span>Comunidad</span>
        </a>

    </nav>


    <div class="navbar-user">

        <span>
            <?= $nombreUsuario ?>
        </span>

        <div class="user-icon">
            <i class="fa-solid fa-user"></i>
        </div>

        <i class="fa-solid fa-chevron-down user-arrow"></i>

    </div>

</header>


<!-- =========================================================
     CONTENEDOR PRINCIPAL
========================================================= -->

<div class="page-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="profile-sidebar">

        <div class="profile-content">

            <div class="profile-avatar">

                <i class="fa-solid fa-user"></i>

            </div>


            <h2>
                <?= $nombreUsuario ?>
            </h2>

            <span class="profile-role">
                <?= $rolUsuario ?>
            </span>


            <div class="profile-quote">

                <span class="quote-icon">
                    &ldquo;
                </span>

                <p>
                    Cada partido<br>
                    es una oportunidad<br>
                    para ser campe&oacute;n.
                </p>

                <strong>
                    #PassballCup
                </strong>

            </div>

        </div>

    </aside>


    <!-- =====================================================
         CONTENIDO
    ====================================================== -->

    <main class="main-content">


        <!-- FLASH MESSAGES -->

        <?php if ($flashSuccess): ?>
            <div class="flash flash-success">
                <?= htmlspecialchars($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="flash flash-error">
                <?= htmlspecialchars($flashError) ?>
            </div>
        <?php endif; ?>


        <!-- =================================================
             TITULO
        ================================================== -->

        <section class="page-header">

            <div>

                <h1>
                    Mis equipos
                </h1>

                <p>
                    Aqu&iacute; puedes ver tu equipo actual y su informaci&oacute;n.
                </p>

            </div>

        </section>


        <!-- =================================================
             MI EQUIPO
        ================================================== -->

        <?php if ($miEquipo): ?>

            <section class="my-team-card">

                <div class="my-team-logo">

                    <?php if (!empty($miEquipo['logo'])): ?>

                        <img
                            src="<?= htmlspecialchars($miEquipo['logo']) ?>"
                            alt="<?= $miEquipo['nombre'] ?>"
                        >

                    <?php else: ?>

                        <div
                            class="no-logo"
                            style="background: <?= htmlspecialchars($miEquipo['color_equipo']) ?>"
                        >
                            <?= mb_substr($miEquipo['nombre'], 0, 1, 'UTF-8') ?>
                        </div>

                    <?php endif; ?>

                </div>


                <div class="my-team-info">

                    <h2>
                        <?= $miEquipo['nombre'] ?>
                    </h2>

                    <div class="team-meta">

                        <span class="participants">

                            <i class="fa-solid fa-users"></i>

                            <?= $miEquipo['total_miembros'] ?>
                            participante<?= $miEquipo['total_miembros'] !== 1 ? 's' : '' ?>

                        </span>


                        <span class="registered">

                            <i class="fa-solid fa-circle-check"></i>

                            Equipo registrado

                        </span>

                    </div>


                    <p class="team-role">

                        <i class="fa-solid fa-star"></i>

                        <?php if ($usuario['rol'] === 'lider'): ?>
                            L&iacute;der del equipo
                        <?php else: ?>
                            Miembro del equipo
                        <?php endif; ?>

                    </p>

                </div>


                <div class="my-team-action">

                    <a
                        href="equipos/detalle.php?id=<?= $miEquipo['id'] ?>"
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
                    onclick="document.getElementById('teamSearch').focus(); document.querySelector('.available-section').scrollIntoView({behavior:'smooth'})"
                >

                    <div class="action-icon purple">

                        <i class="fa-solid fa-magnifying-glass"></i>

                    </div>

                    <div class="action-text">

                        <h3>
                            Buscar equipo
                        </h3>

                        <p>
                            Encuentra un equipo y consulta su informaci&oacute;n.
                        </p>

                    </div>

                    <i class="fa-solid fa-arrow-right action-arrow"></i>

                </button>


                <!-- REGISTRAR -->

                <?php if (!$miEquipo): ?>

                    <button
                        type="button"
                        class="action-card register-action"
                        onclick="document.getElementById('registerModal').classList.add('show')"
                    >

                        <div class="action-icon orange">

                            <i class="fa-solid fa-plus"></i>

                        </div>

                        <div class="action-text">

                            <h3>
                                Registrar equipo
                            </h3>

                            <p>
                                Crea un nuevo equipo para participar.
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

        <section class="available-section">


            <div class="available-header">

                <div>

                    <h2>
                        Equipos disponibles
                    </h2>

                    <p>
                        Explora otros equipos del torneo.
                    </p>

                </div>


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


            <div
                class="teams-grid"
                id="teamsGrid"
            >

                <?php foreach ($equipos as $eq): ?>

                    <article
                        class="team-card"
                        data-team-name="<?= strtolower(htmlspecialchars($eq['nombre'])) ?>"
                    >

                        <div class="team-card-logo">

                            <?php if (!empty($eq['logo'])): ?>

                                <img
                                    src="<?= htmlspecialchars($eq['logo']) ?>"
                                    alt="<?= htmlspecialchars($eq['nombre']) ?>"
                                >

                            <?php else: ?>

                                <div
                                    class="no-logo"
                                    style="background: <?= htmlspecialchars($eq['color_equipo']) ?>"
                                >
                                    <?= mb_substr($eq['nombre'], 0, 1, 'UTF-8') ?>
                                </div>

                            <?php endif; ?>

                        </div>


                        <h3>
                            <?= htmlspecialchars($eq['nombre']) ?>
                        </h3>


                        <p class="team-members">

                            <i class="fa-solid fa-users"></i>

                            <?= $eq['total_miembros'] ?>
                            participante<?= $eq['total_miembros'] !== 1 ? 's' : '' ?>

                        </p>


                        <a
                            href="equipos/detalle.php?id=<?= $eq['id'] ?>"
                            class="btn-outline <?= ($eq['id'] % 2 === 0) ? 'orange' : '' ?>"
                        >
                            Ver equipo
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </article>

                <?php endforeach; ?>

            </div>


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
             AVISO REGISTRO
        ================================================== -->

        <?php if (!$miEquipo): ?>

            <section class="registration-info">

                <div class="info-icon">

                    <i class="fa-solid fa-circle-info"></i>

                </div>


                <div class="info-text">

                    <h3>
                        &iquest;No encuentras tu equipo?
                    </h3>

                    <p>
                        Si tu equipo a&uacute;n no aparece, puedes registrar uno nuevo.
                    </p>

                </div>


                <button
                    type="button"
                    class="info-button"
                    onclick="document.getElementById('registerModal').classList.add('show')"
                >

                    Registrar equipo

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </section>

        <?php endif; ?>


        <!-- FOOTER -->

        <footer>

            &copy; <?= date('Y') ?> <?= htmlspecialchars(TORNEO_NOMBRE) ?> &mdash; Todos los derechos reservados.

        </footer>


    </main>

</div>


<!-- =========================================================
     MODAL REGISTRAR EQUIPO
========================================================= -->

<div
    class="modal"
    id="registerModal"
>

    <div class="modal-overlay"></div>


    <div class="modal-content">


        <button
            type="button"
            class="modal-close"
            onclick="document.getElementById('registerModal').classList.remove('show')"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <div class="modal-icon">

            <i class="fa-solid fa-users"></i>

        </div>


        <h2>
            Registrar equipo
        </h2>

        <p class="modal-description">

            Crea un nuevo equipo para participar en
            <?= htmlspecialchars(TORNEO_NOMBRE) ?>.

        </p>


        <!-- AVISO LIDER -->

        <div class="leader-warning">

            <i class="fa-solid fa-star"></i>

            <div>

                <strong>
                    Ser&aacute;s el l&iacute;der del equipo
                </strong>

                <p>
                    Al registrar este equipo,
                    autom&aacute;ticamente ser&aacute;s designado
                    como su l&iacute;der.
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
                >

            </div>


            <label for="descripcion">
                Descripci&oacute;n
                <span>(opcional)</span>
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
                    onclick="document.getElementById('registerModal').classList.remove('show')"
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


<!-- JS -->
<script src="assets/js/equipos.js"></script>

</body>

</html>
