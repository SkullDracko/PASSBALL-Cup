<?php
/**
 * PASSBALL Cup - Dashboard
 *
 * Esqueleto del dashboard de una sola página (SPA).
 * Cada sección se carga desde su propio partial interno,
 * manteniendo este archivo únicamente con el contenedor.
 */

require_once __DIR__ . '/controllers/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

$tituloPagina = 'Inicio';


/*
|--------------------------------------------------------------------------
| DATOS DEL USUARIO
|--------------------------------------------------------------------------
*/

$nombreUsuario = htmlspecialchars(
    $usuario['nombre'] ?? 'Usuario',
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
        <?= $tituloPagina ?>
        |
        <?= defined('TORNEO_NOMBRE')
            ? TORNEO_NOMBRE
            : 'PASSBALL Cup'
        ?>
    </title>


    <!-- FAVICON -->

    <link
        rel="icon"
        href="assets/img/passball-cup.png"
        type="image/png"
    >


    <!-- FUENTE -->

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
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- CSS DEL DASHBOARD -->

    <link
        rel="stylesheet"
        href="assets/css/pages/dashboard.css"
    >

    <!-- CSS DE PARTIDOS -->

    <link
        rel="stylesheet"
        href="assets/css/pages/partidos.css"
    >

    <!-- CSS DE VOTOS -->

    <link
        rel="stylesheet"
        href="assets/css/pages/votos.css"
    >

    <!-- CSS DE RESULTADOS -->

    <link
        rel="stylesheet"
        href="assets/css/pages/resultados.css"
    >

</head>


<body>

<div class="dashboard">

    <!-- =====================================================
         BARRA SUPERIOR
         ===================================================== -->

    <header class="topbar">

        <div class="nav-left">

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

        </div>


        <!-- NAVEGACIÓN POR PESTAÑAS -->

        <nav class="topbar-nav">

            <a
                class="nav-item nav-tab active"
                data-target="view-inicio"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M3 10.5L12 3l9 7.5v9a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-9z"/>
                </svg>

                <span>Inicio</span>

            </a>

            <a
                class="nav-item nav-tab"
                data-target="view-equipos"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm-8 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm8 2c-3.3 0-6 1.8-6 4v2h12v-2c0-2.2-2.7-4-6-4zM8 13c-2.8 0-5 1.5-5 3.5V18h5v-2c0-1.1.4-2.1 1.1-3A6 6 0 0 0 8 13z"/>
                </svg>

                <span>Equipos</span>

            </a>

            <a
                class="nav-item nav-tab"
                data-target="view-partidos"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M7 2v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7zm12 17H5V9h14v10zM7 11h4v3H7v-3z"/>
                </svg>

                <span>Partidos</span>

            </a>

            <a
                class="nav-item nav-tab"
                data-target="view-votos"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M12 2a10 10 0 1 0 10 10A10.01 10.01 0 0 0 12 2zm0 17a7 7 0 1 1 7-7 7 7 0 0 1-7 7zm1-11h-2v4H8v2h3v3h2v-3h3v-2h-3V8z"/>
                </svg>

                <span>Votos</span>

            </a>

            <a
                class="nav-item nav-tab"
                data-target="view-resultados"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M7 3h10v2h2a2 2 0 0 1 2 2c0 3.3-2.4 5.8-5.5 6.5A4.99 4.99 0 0 1 13 16.9V19h4v2H7v-2h4v-2.1a4.99 4.99 0 0 1-2.5-3.4C5.4 12.8 3 10.3 3 7a2 2 0 0 1 2-2h2V3zm-2 4c0 1.8 1.1 3.2 2.8 3.7L8 7H5zm14 0h-3l-.8 3.7C16.9 10.2 18 8.8 18 7h1z"/>
                </svg>

                <span>Resultados</span>

            </a>

            <a
                class="nav-item nav-tab"
                data-target="view-comunidad"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M20 4H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h3v3l4-3h9a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zM6 9h12v2H6V9zm0 4h8v2H6v-2z"/>
                </svg>

                <span>Comunidad</span>

            </a>

        </nav>


        <!-- USUARIO -->

        <div class="topbar-user">

            <span class="topbar-user-name">
                <?= $nombreUsuario ?>
            </span>


            <div class="topbar-avatar">

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


            <span class="topbar-chevron">
                ⌄
            </span>

        </div>

    </header>



    <!-- =====================================================
         CONTENIDO
         ===================================================== -->

    <main class="dashboard-content">


        <!-- =================================================
             PANEL IZQUIERDO
             ================================================= -->

        <aside class="profile-panel">


            <div class="profile-overlay"></div>


            <!-- PERFIL -->

            <div class="profile-top">


                <div class="profile-avatar">

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


                <h2>
                    <?= $nombreUsuario ?>
                </h2>


                <span class="profile-role">
                    <?= htmlspecialchars($textoRol) ?>
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



        <!-- =================================================
             ÁREA PRINCIPAL
             ================================================= -->

        <section class="main-area">


            <!-- VISTA: INICIO -->

            <div
                class="dashboard-view active"
                id="view-inicio"
            >

                <?php include __DIR__ . '/partials/inicio.php'; ?>

            </div>


            <!-- VISTA: EQUIPOS -->

            <div
                class="dashboard-view"
                id="view-equipos"
            >

                <?php include __DIR__ . '/partials/equipos.php'; ?>

            </div>


            <!-- VISTA: PARTIDOS -->

            <div
                class="dashboard-view"
                id="view-partidos"
            >

                <?php include __DIR__ . '/partials/partidos.php'; ?>

            </div>


            <!-- VISTA: VOTOS -->

            <div
                class="dashboard-view"
                id="view-votos"
            >

                <?php include __DIR__ . '/partials/votos.php'; ?>

            </div>


            <!-- VISTA: RESULTADOS -->

            <div
                class="dashboard-view"
                id="view-resultados"
            >

                <?php include __DIR__ . '/partials/resultados.php'; ?>

            </div>


            <!-- VISTA: COMUNIDAD -->

            <div
                class="dashboard-view"
                id="view-comunidad"
            >

                <?php include __DIR__ . '/partials/comunidad.php'; ?>

            </div>


            <!-- FOOTER -->

            <footer class="dashboard-footer">

                © 2026 PASSBALL Cup — Todos los derechos reservados.

            </footer>


        </section>

    </main>

</div>


<!-- =====================================================
     MODAL: REGISTRAR EQUIPO
     ===================================================== -->

<div
    class="modal"
    id="registerModal"
>

    <div class="modal-overlay"></div>

    <div class="modal-content">

        <button
            type="button"
            class="modal-close"
            id="closeRegister"
            aria-label="Cerrar"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <div class="modal-icon">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <h2>
            Registrar equipo
        </h2>

        <p class="modal-description">
            Completa los datos para crear un nuevo equipo.
        </p>


        <form
            action="controllers/registrarEquipo.php"
            method="POST"
        >


            <div class="leader-warning">

                <i class="fa-solid fa-triangle-exclamation"></i>

                <div>

                    <strong>
                        Serás designado como líder
                    </strong>

                    <p>
                        Al registrar un equipo, automáticamente
                        quedarás a cargo de él.
                    </p>

                </div>

            </div>


            <label for="nombre_equipo">
                Nombre del equipo
                <span>*</span>
            </label>

            <div class="modal-input">

                <i class="fa-solid fa-users"></i>

                <input
                    type="text"
                    id="nombre_equipo"
                    name="nombre_equipo"
                    placeholder="Ej. Los Tigres"
                    required
                >

            </div>


            <label for="color_equipo">
                Color del equipo
                <span>(opcional)</span>
            </label>

            <div class="modal-input">

                <i class="fa-solid fa-palette"></i>

                <input
                    type="text"
                    id="color_equipo"
                    name="color_equipo"
                    placeholder="Ej. #4b2780"
                >

            </div>


            <label for="descripcion_equipo">
                Descripción
                <span>(opcional)</span>
            </label>

            <textarea
                id="descripcion_equipo"
                name="descripcion_equipo"
                placeholder="Cuéntanos sobre tu equipo..."
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


<script src="assets/js/dashboard.js"></script>

<script src="assets/js/partidos.js"></script>

<script src="assets/js/votos.js"></script>

<script src="assets/js/resultados.js"></script>

</body>

</html>
