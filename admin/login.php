<?php
/**
 * PASSBALL Cup - Login del panel de administración
 */
session_start();

if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/../config/app.php';

$error = $_GET['error'] ?? '';
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
        Acceso Administrativo | <?= TORNEO_NOMBRE ?>
    </title>

    <link
        rel="icon"
        href="../assets/img/passball-cup.png"
        type="image/png"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/admin-login.css"
    >

</head>

<body>

<div class="admin-page">

    <header class="brand-header">

        <div class="institutional-logos">

            <img
                src="../assets/img/facmed.png"
                alt="FACMED"
            >

            <span></span>

            <img
                src="../assets/img/medprev.png"
                alt="MEDPREV"
            >

            <span></span>

            <img
                src="../assets/img/password.png"
                alt="PASSWORD"
            >

        </div>

        <img
            class="passball-logo"
            src="../assets/img/passball-cup.png"
            alt="PASSBALL Cup"
        >

    </header>

    <main class="login-card">

        <div class="security-icon">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <h1>Acceso Administrativo</h1>

        <p class="subtitle">
            Panel de gestión <?= TORNEO_NOMBRE ?>
        </p>

        <div class="divider"></div>

        <p class="notice">
            <i class="fa-solid fa-lock"></i>
            Acceso exclusivo para el equipo organizador del torneo.
        </p>

        <?php if ($error): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form
            class="login-form"
            id="adminLoginForm"
        >

            <label for="usuario">Usuario administrador</label>

            <div class="input-box">

                <i class="fa-solid fa-user"></i>

                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    placeholder="Tu usuario"
                    autocomplete="username"
                    required
                    autofocus
                >

            </div>

            <label for="contrasena">Contraseña</label>

            <div class="input-box">

                <i class="fa-solid fa-lock"></i>

                <input
                    type="password"
                    id="contrasena"
                    name="contrasena"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                >

                <button
                    type="button"
                    id="togglePassword"
                    aria-label="Mostrar u ocultar contraseña"
                >
                    <i class="fa-solid fa-eye"></i>
                </button>

            </div>

            <button
                class="btn-login"
                type="submit"
                id="btnAdminLogin"
            >
                <i class="fa-solid fa-lock"></i>
                Entrar al panel
                <i class="fa-solid fa-arrow-right"></i>
            </button>

        </form>

        <a href="../login.php" class="back">
            ← Volver al inicio de sesión
        </a>

    </main>

    <footer class="admin-login-footer">
        PANEL ADMINISTRATIVO · <?= TORNEO_EDICION ?>
    </footer>

</div>

<script src="assets/js/admin-login.js"></script>

<script src="assets/js/login.js"></script>

</body>

</html>