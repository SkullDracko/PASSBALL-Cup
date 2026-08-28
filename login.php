<?php
/**
 * PASSBALL Cup - Login
 */
session_start();

if (isset($_SESSION['usuario'])) {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/config/app.php';

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
        Iniciar Sesión | <?= TORNEO_NOMBRE ?>
    </title>

    <link
        rel="icon"
        href="assets/img/passball-cup.png"
        type="image/png"
    >

    <link
        rel="stylesheet"
        href="assets/css/variables.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/components.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/pages/login.css"
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

<main class="login-container">


    <!-- ==========================================
         ESTADIO
         ========================================== -->

    <div class="stadium-content">

        <div class="stadium-brand">


        </div>

    </div>


    <!-- ==========================================
         TARJETA DE LOGIN
         ========================================== -->

    <section class="login-card">


        <!-- LOGO -->

        <img
            src="assets/img/passball-cup.png"
            class="login-logo"
            alt="PASSBALL Cup"
        >


        <!-- ======================================
             DIVISOR
             ====================================== -->

        <div class="login-divider">

            <span class="football-icon">

                <svg
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >

                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                    />

                    <path
                        d="M12 7l3 2.2-1.1 3.5h-3.8L9 9.2 12 7z"
                        fill="currentColor"
                    />

                    <path
                        d="M12 7V4.5
                           M15 9.2l2.8-1.1
                           M13.9 12.7l1.8 2.5
                           M10.1 12.7l-1.8 2.5
                           M9 9.2L6.2 8.1"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.2"
                    />

                </svg>

            </span>

        </div>


        <!-- ======================================
             TITULO
             ====================================== -->

        <h2 class="login-title">

            Inicia sesión para continuar

        </h2>


        <!-- ======================================
             ERROR
             ====================================== -->

        <?php if ($error): ?>

            <div class="alert alert-error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- ======================================
             FORMULARIO
             ====================================== -->

        <form
            class="login-form"
            id="loginForm"
        >


            <label for="matricula">

                Matrícula

            </label>


            <!-- INPUT -->

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
                            d="M5 20
                               c.5-3.4 3-5.5 7-5.5
                               s6.5 2.1 7 5.5"
                            fill="currentColor"
                        />

                    </svg>

                </span>


                <input
                    type="text"
                    id="matricula"
                    class="login-input"
                    placeholder="Ej: 1234567"
                    maxlength="7"
                    pattern="\d{7}"
                    inputmode="numeric"
                    autocomplete="username"
                    required
                    autofocus
                >

            </div>


            <!-- ==================================
                 BOTÓN
                 ================================== -->

            <button
                type="submit"
                class="login-button"
                id="btnLogin"
            >

                <span class="button-text">

                    Entrar al Torneo

                </span>

                <span class="button-arrow">

                    →

                </span>

            </button>


        </form>


        <!-- ======================================
             INFORMACIÓN
             ====================================== -->

        <div class="login-info">


            <div class="login-info-icon">

                <svg
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >

                    <path
                        d="M12 3.5
                           l7 3
                           v5.2
                           c0 4.3-2.8 7.4-7 9.3
                           -4.2-1.9-7-5-7-9.3
                           V6.5
                           l7-3z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linejoin="round"
                    />

                    <path
                        d="M9 12l2 2 4-4"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />

                </svg>

            </div>


            <span>

                Solo participantes inscritos en la AFI
                pueden acceder

            </span>


        </div>


    </section>


</main>


<!-- ==========================================
     JAVASCRIPT
     ========================================== -->

<script src="assets/js/app.js"></script>

<script src="assets/js/login.js"></script>


</body>

</html>
