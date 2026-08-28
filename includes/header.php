<?php
/**
 * PASSBALL Cup - Header incluido
 * Uso: <?php require_once 'includes/header.php'; ?>
 */

$pagina_actual = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?? TORNEO_NOMBRE ?> | <?= TORNEO_EDICION ?></title>
    <link rel="icon" href="<?= BASE_URL ?>assets/img/passball-cup.png" type="image/png">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/pages/equipos.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<?php if (isset($usuario)): ?>
<nav class="navbar">
    <div class="nav-container">
        <a href="dashboard.php" class="nav-logo">
            <img src="assets/img/passball-cup.png" alt="PASSBALL Cup" class="nav-logo-img">
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Menú">
            <span></span><span></span><span></span>
        </button>

        <ul class="nav-menu" id="navMenu">
            <li><a href="dashboard.php" class="<?= $pagina_actual === 'dashboard' ? 'active' : '' ?>">Inicio</a></li>
            <li><a href="equipos/index.php" class="<?= $pagina_actual === 'index' && strpos($_SERVER['PHP_SELF'], 'equipos') !== false ? 'active' : '' ?>">Equipos</a></li>
            <li><a href="partidos/index.php" class="<?= strpos($_SERVER['PHP_SELF'], 'partidos') !== false ? 'active' : '' ?>">Partidos</a></li>
            <li><a href="apuestas/index.php" class="<?= strpos($_SERVER['PHP_SELF'], 'apuestas') !== false ? 'active' : '' ?>">Apuestas</a></li>
            <li><a href="resultados/ganadores.php" class="<?= strpos($_SERVER['PHP_SELF'], 'resultados') !== false ? 'active' : '' ?>">Resultados</a></li>
            <li><a href="comunidad/index.php" class="<?= strpos($_SERVER['PHP_SELF'], 'comunidad') !== false ? 'active' : '' ?>">Comunidad</a></li>
            <?php if (es_admin()): ?>
            <li><a href="admin/index.php" class="nav-admin <?= strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : '' ?>">Admin</a></li>
            <?php endif; ?>
        </ul>

        <div class="nav-user">
            <span class="user-name"><?= htmlspecialchars($usuario['nombre']) ?></span>
            <a href="controllers/logout.php" class="btn-logout">Salir</a>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="<?= isset($usuario) ? 'main-content' : 'main-full' ?>">
<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>
