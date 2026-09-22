<?php
/**
 * PASSBALL Cup - Login del panel de administración
 */
session_start();

require_once __DIR__ . '/../config/app.php';

/*
|--------------------------------------------------------------------------
| Si ya existe una sesión administrativa
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Acceso Administrativo | <?= TORNEO_NOMBRE ?></title>

    <link rel="icon" type="image/png" href="../assets/img/passball-cup.png">

    <!-- Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/admin-login.css">
</head>

<body>

<div class="admin-login">

    <!-- =========================================================
         FONDO
    ========================================================== -->

    <div class="background-overlay"></div>


    <!-- =========================================================
         CONTENIDO PRINCIPAL
    ========================================================== -->

    <main class="login-content">

        <!-- =====================================================
             LOGOS INSTITUCIONALES
        ====================================================== -->

        <div class="institutional-logos">

            <img
                src="../assets/img/facmed.png"
                alt="Facultad de Medicina"
                class="logo-facmed"
            >

            <span class="logo-divider"></span>

            <img
                src="../assets/img/medprev.png"
                alt="Medicina Preventiva y Salud Pública"
                class="logo-medprev"
            >

            <span class="logo-divider"></span>

            <img
                src="../assets/img/password.png"
                alt="PASSWORD"
                class="logo-password"
            >

        </div>


        <!-- =====================================================
             LOGO PASSBALL CUP
        ====================================================== -->

        <div class="passball-logo">

            <img
                src="../assets/img/passball-cup.png"
                alt="PASSBALL Cup"
            >

        </div>


        <!-- =====================================================
             CARD LOGIN
        ====================================================== -->

        <section class="admin-card">

            <!-- Icono seguridad -->

            <div class="security-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>


            <!-- Título -->

            <div class="card-heading">

                <h1>
                    Acceso
                    <span>Administrativo</span>
                </h1>

                <p>
                    Panel de gestión <?= TORNEO_NOMBRE ?>
                </p>

                <div class="heading-line"></div>

            </div>


            <!-- Aviso -->

            <div class="admin-notice">

                <i class="fa-solid fa-shield-halved"></i>

                <span>
                    Acceso exclusivo para el equipo organizador del torneo.
                </span>

            </div>


            <!-- Error -->

            <?php if (!empty($error)): ?>

                <div class="login-error">
                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= htmlspecialchars($error) ?>
                    </span>
                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORMULARIO
            ================================================== -->

            <form
                id="adminLoginForm"
                class="admin-form"
                autocomplete="off"
            >

                <!-- Usuario -->

                <div class="form-group">

                    <label for="usuario">

                        <i class="fa-solid fa-user"></i>

                        Usuario administrador

                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-user input-icon"></i>

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

                </div>


                <!-- Contraseña -->

                <div class="form-group">

                    <label for="contrasena">

                        <i class="fa-solid fa-lock"></i>

                        Contraseña

                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-lock input-icon"></i>

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
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Mostrar contraseña"
                        >

                            <i class="fa-solid fa-eye"></i>

                        </button>

                    </div>

                </div>


                <!-- Botón -->

                <button
                    type="submit"
                    class="login-button"
                    id="loginButton"
                >

                    <i class="fa-solid fa-lock"></i>

                    <span>Entrar al panel</span>

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </form>


            <!-- Volver -->

            <a href="../login.php" class="back-login">

                <i class="fa-solid fa-arrow-left"></i>

                Volver al inicio de sesión

            </a>

        </section>


        <!-- =====================================================
             FOOTER
        ====================================================== -->

        <footer class="admin-footer">

            <span></span>

            <p>
                PANEL ADMINISTRATIVO · <?= TORNEO_EDICION ?>
            </p>

            <span></span>

        </footer>

    </main>

</div>


<!-- JS -->
<script src="assets/js/admin-login.js"></script>

</body>
</html>