/* ============================================================
   PASSBALL CUP
   LOGIN ADMINISTRATIVO
============================================================ */

document.addEventListener("DOMContentLoaded", () => {

    const passwordInput =
        document.getElementById("contrasena");

    const togglePassword =
        document.getElementById("togglePassword");

    const loginForm =
        document.getElementById("adminLoginForm");

    const loginButton =
        document.getElementById("loginButton");


    /* ========================================================
       MOSTRAR / OCULTAR CONTRASEÑA
    ======================================================== */

    if (togglePassword && passwordInput) {

        togglePassword.addEventListener("click", () => {

            const isPassword =
                passwordInput.type === "password";

            passwordInput.type =
                isPassword ? "text" : "password";


            const icon =
                togglePassword.querySelector("i");


            if (isPassword) {

                icon.classList.remove("fa-eye");

                icon.classList.add("fa-eye-slash");

                togglePassword.setAttribute(
                    "aria-label",
                    "Ocultar contraseña"
                );

            } else {

                icon.classList.remove("fa-eye-slash");

                icon.classList.add("fa-eye");

                togglePassword.setAttribute(
                    "aria-label",
                    "Mostrar contraseña"
                );

            }

        });

    }


    /* ========================================================
       SUBMIT
    ======================================================== */

    if (loginForm) {

        loginForm.addEventListener("submit", async (e) => {

            e.preventDefault();

            if (!loginButton) return;


            const usuario =
                document.getElementById("usuario").value.trim();

            const contrasena =
                document.getElementById("contrasena").value;


            if (usuario === "" || contrasena === "") {

                loginButton.disabled = false;

                loginButton.style.opacity = "";

                loginButton.style.cursor = "";

                return;
            }


            loginButton.disabled = true;

            loginButton.style.opacity = "0.75";

            loginButton.style.cursor = "wait";


            loginButton.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Verificando...</span>
            `;
            try {

                // Autentica contra la API, no contra controllers/login.php.
                // Así la sesión que abre el panel es la misma que exigen los
                // endpoints de backend (mismo PHPSESSID, mismas claves).
                await API.loginAdmin(usuario, contrasena);

                window.location.href = "dashboard.php";

                return;


            } catch (err) {

                console.error("Error de login admin:", err);

                // La API ya distingue "credenciales inválidas" de
                // "administrador inactivo"; se muestra su mensaje.
                alert(
                    err.message || "Error al iniciar sesión"
                );

            }


            /* Restaurar botón */

            loginButton.disabled = false;

            loginButton.style.opacity = "";

            loginButton.style.cursor = "";

            loginButton.innerHTML = `
                <i class="fa-solid fa-lock"></i>
                <span>Entrar al panel</span>
                <i class="fa-solid fa-arrow-right"></i>
            `;


            const passwordInput2 =
                document.getElementById("contrasena");

            if (passwordInput2) {

                passwordInput2.value = "";

                passwordInput2.focus();
            }

        });

    }


    /* ========================================================
       ANIMACIÓN INICIAL
    ======================================================== */

    const card =
        document.querySelector(".admin-card");

    const logos =
        document.querySelector(".institutional-logos");

    const brand =
        document.querySelector(".passball-logo");


    if (logos) {

        logos.animate(
            [
                {
                    opacity: 0,
                    transform: "translateY(-15px)"
                },
                {
                    opacity: 1,
                    transform: "translateY(0)"
                }
            ],
            {
                duration: 650,
                easing: "ease-out",
                fill: "forwards"
            }
        );

    }


    if (brand) {

        brand.animate(
            [
                {
                    opacity: 0,
                    transform: "translateY(-10px)"
                },
                {
                    opacity: 1,
                    transform: "translateY(0)"
                }
            ],
            {
                duration: 650,
                delay: 100,
                easing: "ease-out",
                fill: "forwards"
            }
        );

    }


    if (card) {

        card.animate(
            [
                {
                    opacity: 0,
                    transform:
                        "translateY(20px) scale(.98)"
                },
                {
                    opacity: 1,
                    transform:
                        "translateY(0) scale(1)"
                }
            ],
            {
                duration: 700,
                delay: 180,
                easing: "cubic-bezier(.2,.8,.2,1)",
                fill: "forwards"
            }
        );

    }

});