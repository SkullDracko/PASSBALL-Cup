/**
 * PASSBALL Cup - Login admin
 * Mostrar / ocultar contraseña
 */

document.addEventListener('DOMContentLoaded', function () {

    var password = document.getElementById('contrasena');
    var toggle   = document.getElementById('togglePassword');

    if (!password || !toggle) return;

    toggle.addEventListener('click', function () {

        var visible = password.type === 'text';

        password.type = visible ? 'password' : 'text';

        toggle.innerHTML = visible
            ? '<i class="fa-solid fa-eye"></i>'
            : '<i class="fa-solid fa-eye-slash"></i>';

    });

});