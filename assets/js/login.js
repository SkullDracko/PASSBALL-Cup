/**
 * PASSBALL Cup - Login
 */

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('loginForm');

    if (!form) return;


    form.addEventListener('submit', async function (e) {

        e.preventDefault();


        const btn = document.getElementById('btnLogin');

        const matriculaInput =
            document.getElementById('matricula');


        const matricula =
            matriculaInput.value.trim();


        /* ======================================
           VALIDAR MATRÍCULA
           ====================================== */

        if (!/^\d{7}$/.test(matricula)) {

            alert(
                'La matrícula debe tener exactamente 7 dígitos'
            );

            matriculaInput.focus();

            return;
        }


        /* ======================================
           ESTADO DE CARGA
           ====================================== */

        btn.dataset.originalText = btn.innerHTML;

        btn.innerHTML =
            '<span class="spinner"></span>';

        btn.disabled = true;


        try {

            /* ==================================
               FORM DATA
               ================================== */

            const formData = new FormData();

            formData.append(
                'matricula',
                matricula
            );


            /* ==================================
               PETICIÓN
               ================================== */

            const res = await fetch(
                'controllers/login.php',
                {
                    method: 'POST',
                    body: formData
                }
            );


            /* ==================================
               RESPUESTA
               ================================== */

            const data = await res.json();


            /* ==================================
               LOGIN CORRECTO
               ================================== */

            if (data.success) {

                window.location.href =
                    'dashboard.php';

                return;
            }


            /* ==================================
               LOGIN INCORRECTO
               ================================== */

            alert(
                data.message ||
                'Error al iniciar sesión'
            );


            btn.innerHTML =
                btn.dataset.originalText;

            btn.disabled = false;


        } catch (err) {

            console.error(
                'Error de login:',
                err
            );


            alert(
                'Error de conexión. Intenta de nuevo.'
            );


            btn.innerHTML =
                btn.dataset.originalText;

            btn.disabled = false;

        }

    });

});
