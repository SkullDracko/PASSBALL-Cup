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
        Panel Admin | <?= TORNEO_NOMBRE ?>
    </title>

    <link
        rel="icon"
        href="../assets/img/passball-cup.png"
        type="image/png"
    >

    <link
        rel="stylesheet"
        href="../assets/css/variables.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/components.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/pages/login.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/admin.css"
    >

    <!-- Google Fonts -->
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

</head>


<body>

<main class="admin-login">

<section class="login-card admin-login-card">

    <!-- BADGE -->

    <div class="admin-login-badge">

        <svg
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >

            <path
                d="M12 2l7 3v5.2c0 4.3-2.8 7.4-7 9.3-4.2-1.9-7-5-7-9.3V5l7-3z"
                fill="currentColor"
            />

            <path
                d="M8.8 13.6l2.2 2.2 4.2-4.6"
                fill="none"
                stroke="#ffffff"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
            />

        </svg>

    </div>


    <h2 class="admin-login-title">

        Panel de administración

    </h2>


    <p class="admin-login-subtitle">

        Acceso restringido al equipo organizador
        del torneo.

    </p>


    <?php if ($error): ?>

        <div class="alert alert-error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <form
        class="login-form"
        id="adminLoginForm"
    >


        <label for="usuario">

            Usuario

        </label>


        <div class="input-wrapper">

            <span class="input-icon">

                <svg
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >

                    <circle
                        cx="12"
                        cy="8"
                        r="3.5"
                        fill="currentColor"
                    />

                    <path
                        d="M5 20c.5-3.4 3-5.5 7-5.5s6.5 2.1 7 5.5"
                        fill="currentColor"
                    />

                </svg>

            </span>


            <input
                type="text"
                id="usuario"
                class="login-input admin-input"
                placeholder="Tu usuario"
                autocomplete="username"
                required
                autofocus
            >

        </div>


        <label for="contrasena">

            Contraseña

        </label>


        <div class="input-wrapper">

            <span class="input-icon">

                <svg
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >

                    <rect
                        x="4"
                        y="10"
                        width="16"
                        height="11"
                        rx="2.5"
                        fill="currentColor"
                    />

                    <path
                        d="M8 10V7a4 4 0 0 1 8 0v3"
                        fill="none"
                        stroke="#ffffff"
                        stroke-width="2"
                    />

                </svg>

            </span>


            <input
                type="password"
                id="contrasena"
                class="login-input admin-input"
                placeholder="••••••••"
                autocomplete="current-password"
                required
            >

        </div>


        <button
            type="submit"
            class="login-button admin-login-button"
            id="btnAdminLogin"
        >

            <span class="button-text">

                Entrar al panel

            </span>

            <span class="button-arrow">

                →

            </span>

        </button>


    </form>


    <a
        href="../login.php"
        class="login-admin-link admin-back-link"
    >

        ← Volver al inicio de sesión

    </a>


</section>

</main>


<script src="../assets/js/app.js"></script>

<script src="assets/js/login.js"></script>

</body>

</html>