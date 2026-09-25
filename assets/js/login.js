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

        if (!/^[0-9]{7}$/.test(matricula)) {

            showAlert('La matrícula debe tener exactamente 7 números', 'error');

            matriculaInput.focus();

            return;
        }


        /* ======================================
           ESTADO DE CARGA
           ====================================== */

        setLoading(btn, true);


        try {

            /* ==================================
               PETICIÓN
               ================================== */

            const data = await apiRequest(
                'backend/api/auth/login',
                'POST',
                { matricula: matricula }
            );


            /* ==================================
               RESPUESTA
               ================================== */

            if (data.exito) {

                window.location.href =
                    'dashboard.php';

                return;
            }


            /* ==================================
               LOGIN INCORRECTO
               ================================== */

            showAlert(
                (data.errores || {}).error
                    || 'Error al iniciar sesión',
                'error'
            );

            setLoading(btn, false);


        } catch (err) {

            console.error(
                'Error de login:',
                err
            );


            showAlert('Error de conexión. Intenta de nuevo.', 'error');

            setLoading(btn, false);

        }

    });

});
