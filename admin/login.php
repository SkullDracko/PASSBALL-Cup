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

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Acceso Administrativo | <?= TORNEO_NOMBRE ?></title>

    <link rel="icon"
          href="../assets/img/passball-cup.png"
          type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet"
          href="assets/css/admin-login.css">

</head>

<body>

    <!-- =========================================================
         FONDO PRINCIPAL
    ========================================================== -->

    <main class="admin-login">

        <!-- Capa oscura -->
        <div class="background-overlay"></div>

        <!-- Decoración lateral -->
        <div class="cup-decoration">
            <i class="fa-solid fa-trophy"></i>
        </div>


        <!-- =====================================================
             CONTENIDO
        ====================================================== -->

        <section class="login-wrapper">


            <!-- =================================================
                 LOGOS INSTITUCIONALES
            ================================================== -->

            <div class="institutional-logos">

                <!-- FACMED -->
                <div class="institution-logo facmed-logo">
                    <img
                        src="../assets/img/facmed.png"
                        alt="Facultad de Medicina"
                    >
                </div>


                <div class="logo-divider"></div>


                <!-- MEDPREV -->
                <div class="institution-logo medprev-logo">
                    <img
                        src="../assets/img/medprev.png"
                        alt="Medicina Preventiva y Salud Pública"
                    >
                </div>


                <div class="logo-divider"></div>


                <!-- PASSWORD -->
                <div class="institution-logo password-logo">
                    <img
                        src="../assets/img/password.png"
                        alt="PASSWORD"
                    >
                </div>

            </div>


            <!-- =================================================
                 LOGO PASSBALL CUP
            ================================================== -->

            <div class="passball-brand">

                <img
                    src="../assets/img/passball-cup.png"
                    alt="PASSBALL Cup"
                >

                <span>
                    Torneo de Fútbol Medicina Preventiva
                </span>

            </div>


            <!-- =================================================
                 TARJETA LOGIN
            ================================================== -->

            <div class="login-card">


                <!-- Escudo -->
                <div class="security-icon">

                    <i class="fa-solid fa-shield-halved"></i>

                </div>


                <!-- Título -->
                <h1>
                    Acceso
                    <span>Administrativo</span>
                </h1>


                <p class="login-subtitle">
                    Panel de gestión <?= TORNEO_NOMBRE ?>
                </p>


                <!-- Línea amarilla -->
                <div class="title-line"></div>


                <!-- Mensaje -->
                <div class="access-message">

                    <i class="fa-solid fa-shield-halved"></i>

                    <span>
                        Acceso exclusivo para el equipo organizador
                        del torneo.
                    </span>

                </div>


                <!-- =============================================
                     FORMULARIO
                ============================================== -->

                <form
                    id="adminLoginForm"
                    autocomplete="off"
                >


                    <!-- USUARIO -->

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
                                required
                                autocomplete="username"
                                autofocus
                            >

                        </div>

                    </div>


                    <!-- CONTRASEÑA -->

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
                                required
                                autocomplete="current-password"
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


                    <!-- ERROR -->

                    <?php if (!empty($error)): ?>

                        <div class="login-error">

                            <i class="fa-solid fa-circle-exclamation"></i>

                            <span>
                                <?= htmlspecialchars($error) ?>
                            </span>

                        </div>

                    <?php endif; ?>


                    <!-- BOTÓN -->

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


                <!-- VOLVER -->

                <a
                    href="../login.php"
                    class="back-link"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Volver al inicio de sesión

                </a>


            </div>


            <!-- =================================================
                 FOOTER
            ================================================== -->

            <div class="admin-footer">

                <span class="footer-line"></span>

                <span>
                    PANEL ADMINISTRATIVO · <?= TORNEO_EDICION ?>
                </span>

                <span class="footer-line"></span>

            </div>


        </section>

    </main>


    <!-- JS -->
    <script src="assets/js/admin-login.js"></script>

</body>

</html>