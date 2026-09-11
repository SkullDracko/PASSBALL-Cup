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

    <link rel="icon" href="../assets/img/passball-cup.png" type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link rel="stylesheet" href="assets/css/admin.css">

</head>

<body>

<div class="admin-shell" id="adminShell">

    <!-- =========================================
         SIDEBAR
         ========================================= -->

    <aside class="admin-sidebar" id="adminSidebar">

        <div class="admin-sidebar-brand">

            <img src="../assets/img/passball-cup.png" alt="PASSBALL Cup">

            <span>
                PASSBALL Cup <small>Panel de administración</small>
            </span>

        </div>


        <nav class="admin-sidebar-nav">

            <a class="admin-nav-item active" data-target="view-inicio">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 10.5L12 3l9 7.5v9a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-9z" stroke-linejoin="round"/></svg>
                Inicio
            </a>

            <a class="admin-nav-item" data-target="view-postulaciones">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12l2 2 4-4M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Postulaciones
            </a>

            <a class="admin-nav-item" data-target="view-torneo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M8 5v14M16 5v14"/></svg>
                Torneo y Rondas
            </a>

            <a class="admin-nav-item" data-target="view-resultados">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h10v2h2a2 2 0 0 1 2 2c0 3.3-2.4 5.8-5.5 6.5V16a5 5 0 0 1-2 4.9V22H10v-1.1a5 5 0 0 1-2-4.9v-2.5C4.9 12.8 2.5 10.3 2.5 7a2 2 0 0 1 2-2h2V3z"/></svg>
                Resultados
            </a>

            <a class="admin-nav-item" data-target="view-comunidad">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke-linejoin="round"/></svg>
                Comunidad
            </a>

            <a class="admin-nav-item" data-target="view-votaciones">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M18 8a6 6 0 0 0-12 0M12 6V3" stroke-linecap="round"/></svg>
                Votaciones
            </a>

            <a class="admin-nav-item" data-target="view-participantes">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.5"/><path d="M3 20c.6-3.3 3-5 6-5s5.4 1.7 6 5M16 5.2a3.2 3.2 0 0 1 0 5.6M18.5 15.2c1.6.8 2.5 2.2 2.8 4.8" stroke-linecap="round"/></svg>
                Participantes
            </a>

        </nav>


        <div class="admin-sidebar-footer">

            <div class="admin-sidebar-user">

                <div class="avatar-nm"><?= htmlspecialchars($inicialAdmin) ?></div>

                <div>
                    <strong><?= htmlspecialchars($admin['nombre']) ?></strong>
                    <small><?= htmlspecialchars($admin['usuario']) ?></small>
                </div>

            </div>

            <a class="logout" href="controllers/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
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

            <h1 id="adminTitle">Inicio</h1>

            <span class="admin-meta">
                <?= TORNEO_NOMBRE ?> · <?= TORNEO_EDICION ?>
            </span>

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


<script src="assets/js/admin.js"></script>

</body>

</html>