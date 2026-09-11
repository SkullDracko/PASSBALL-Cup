/**
 * PASSBALL Cup - Login del panel admin
 */

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('adminLoginForm');

    if (!form) return;


    form.addEventListener('submit', async function (e) {

        e.preventDefault();


        const btn = document.getElementById('btnAdminLogin');

        const usuarioInput  = document.getElementById('usuario');
        const contrasenaInput = document.getElementById('contrasena');


        const usuario    = usuarioInput.value.trim();
        const contrasena = contrasenaInput.value;


        if (usuario === '' || contrasena === '') {

            alert('Ingresa usuario y contraseña');

            usuarioInput.focus();

            return;
        }


        btn.dataset.originalText = btn.innerHTML;

        btn.innerHTML =
            '<span class="spinner"></span>';

        btn.disabled = true;


        try {

            const formData = new FormData();

            formData.append('usuario', usuario);
            formData.append('contrasena', contrasena);


            const res = await fetch(
                'controllers/login.php',
                {
                    method: 'POST',
                    body: formData
                }
            );


            const data = await res.json();


            if (data.success) {

                window.location.href =
                    data.redirect || 'dashboard.php';

                return;
            }


            alert(
                data.message ||
                'Error al iniciar sesión'
            );


            btn.innerHTML =
                btn.dataset.originalText;

            btn.disabled = false;

            contrasenaInput.value = '';
            contrasenaInput.focus();


        } catch (err) {

            console.error(
                'Error de login admin:',
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