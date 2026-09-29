<?php
/**
 * PASSBALL Cup - Panel de administración (shell SPA)
 * Cada sección se carga desde admin/partials/
 */

require_once __DIR__ . '/controllers/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

$tituloPagina = 'Panel Admin';
$inicialAdmin = mb_strtoupper(mb_substr($admin['nombre'], 0, 1, 'UTF-8'), 'UTF-8');
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $tituloPagina ?> | <?= TORNEO_NOMBRE ?></title>

    <link rel="icon" href="<?= assetUrl('assets/img/passball-cup.png') ?>" type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="<?= assetUrl('assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= assetUrl('admin/assets/css/admin.css') ?>">

</head>

<body>

<div class="admin-shell" id="adminShell">

    <!-- =========================================
         SIDEBAR
         ========================================= -->

    <aside class="admin-sidebar" id="adminSidebar">

        <div class="admin-sidebar-brand">

            <span class="admin-brand-mark">
                <img src="<?= assetUrl('assets/img/passball-p-logo-transparent.png') ?>" alt="PASSBALL Cup">
            </span>

            <span>
                PASSBALL Cup <small>Panel de administración</small>
            </span>

        </div>

         <nav class="admin-sidebar-nav">

         <a class="admin-nav-item active" data-target="view-inicio">
                <span class="nav-ico"><i class="fa-solid fa-house"></i></span>
                Inicio
            </a>
            <div class="admin-nav-group-label">Torneo</div>

            <a class="admin-nav-item" data-target="view-torneo">
                <span class="nav-ico"><i class="fa-solid fa-trophy"></i></span>
                 Torneo y Rondas 
            </a>

            <a class="admin-nav-item" data-target="view-resultados">
                <span class="nav-ico"><i class="fa-solid fa-ranking-star"></i></span>
                 Resultados
            </a>

            <a class="admin-nav-item" data-target="view-postulaciones">
                <span class="nav-ico"><i class="fa-solid fa-inbox"></i></span>
                Postulaciones
            </a>

            <div class="admin-nav-group-label">Participantes</div>

            <a class="admin-nav-item" data-target="view-participantes">
                <span class="nav-ico"><i class="fa-solid fa-people-group"></i></span>
                Participantes
            </a>
 
            <div class="admin-nav-group-label">Interacción</div>

            <a class="admin-nav-item" data-target="view-votaciones">
                <span class="nav-ico"><i class="fa-solid fa-star"></i></span>
                 Votaciones
            </a>

            <a class="admin-nav-item" data-target="view-comunidad">
                <span class="nav-ico"><i class="fa-solid fa-comment-dots"></i></span>
                Comunidad
            </a>
        </nav>
        
        <div class="admin-sidebar-footer">

            <a
                class="portal-link"
                href="<?= assetUrl('dashboard.php') ?>"
                target="_blank"
                rel="noopener"
            >
                <span class="nav-ico"><i class="fa-solid fa-globe"></i></span> Ver portal
            </a>

        </div>

    </aside>


    <!-- =========================================
         CONTENIDO
         ========================================= -->

    <main class="admin-main">

        <header class="admin-topbar">

            <button class="burger" id="adminBurger" aria-label="Abrir menú">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="admin-greet">
                <h1 id="adminTitle">Inicio</h1>
                <p>Bienvenida, <?= htmlspecialchars($admin['nombre']) ?></p>
            </div>

            <div class="admin-user">

                <button class="admin-user-btn" type="button">
                    <span class="avatar-nm"><?= htmlspecialchars($inicialAdmin) ?></span>
                    <span class="admin-user-txt">
                        <strong><?= htmlspecialchars($admin['nombre']) ?></strong>
                        <small><?= htmlspecialchars($admin['usuario']) ?></small>
                    </span>
                    <i class="fa-solid fa-chevron-down admin-user-chev"></i>
                </button>

                <div class="admin-user-menu">
                    <div class="admin-user-who">
                        <strong><?= htmlspecialchars($admin['nombre']) ?></strong>
                        <small>Administración</small>
                    </div>
                    <a class="logout" href="<?= assetUrl('admin/controllers/logout.php') ?>">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión
                    </a>
                </div>

            </div>

        </header>


        <div class="admin-views">

            <section class="admin-view active" id="view-inicio">
                <?php include __DIR__ . '/partials/inicio.php'; ?>
            </section>

            <section class="admin-view" id="view-postulaciones">
                <?php include __DIR__ . '/partials/postulaciones.php'; ?>
            </section>

            <section class="admin-view" id="view-torneo">
                <?php include __DIR__ . '/partials/torneo.php'; ?>
            </section>

            <section class="admin-view" id="view-resultados">
                <?php include __DIR__ . '/partials/resultados.php'; ?>
            </section>

            <section class="admin-view" id="view-comunidad">
                <?php include __DIR__ . '/partials/comunidad.php'; ?>
            </section>

            <section class="admin-view" id="view-votaciones">
                <?php include __DIR__ . '/partials/votaciones.php'; ?>
            </section>

            <section class="admin-view" id="view-participantes">
                <?php include __DIR__ . '/partials/participantes.php'; ?>
            </section>

        </div>

    </main>

</div>


<!-- La API se expone antes de api.js: su base tiene que ser absoluta,
     no relativa, para no depender de la forma de la URL. -->
<script>window.PASSBALL_API = <?= apiUrlJs() ?>;</script>

<script src="<?= assetUrl('admin/assets/js/api.js') ?>"></script>
<script src="<?= assetUrl('admin/assets/js/views.js') ?>"></script>
<script src="<?= assetUrl('admin/assets/js/admin.js') ?>"></script>

</body>

</html>