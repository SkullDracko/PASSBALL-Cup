/**
 * PASSBALL Cup - Panel admin
 * Navegación por vistas + sidebar móvil
 */

document.addEventListener('DOMContentLoaded', function () {

    var shell  = document.getElementById('adminShell');
    var burger = document.getElementById('adminBurger');
    var title  = document.getElementById('adminTitle');

    var navItems = Array.prototype.slice.call(
        document.querySelectorAll('.admin-nav-item')
    );

    var views = Array.prototype.slice.call(
        document.querySelectorAll('.admin-view')
    );

    function showView(targetId) {

        var target = document.getElementById(targetId);

        if (!target) return;


        views.forEach(function (v) {
            v.classList.toggle('active', v === target);
        });


        navItems.forEach(function (nav) {

            var isActive =
                nav.getAttribute('data-target') === targetId;

            nav.classList.toggle('active', isActive);

            if (isActive && title) {
                title.textContent = nav.textContent.trim();
            }

        });


        if (window.innerWidth <= 860) {
            closeSidebar();
        }

    }


    navItems.forEach(function (nav) {

        nav.addEventListener('click', function (e) {

            e.preventDefault();

            showView(this.getAttribute('data-target'));

        });

    });


    /* =========================================
       ABRIR VISTA DESDE HASH
       ========================================= */

    if (location.hash) {

        var hashTarget = location.hash.substring(1);

        var hashView = document.getElementById(hashTarget);

        if (hashView) {
            showView(hashTarget);
        }

    }


    /* =========================================
       SIDEBAR MÓVIL
       ========================================= */

    function closeSidebar() {

        if (shell) {
            shell.classList.remove('sidebar-open');
        }

    }

    if (burger && shell) {

        burger.addEventListener('click', function (e) {

            e.stopPropagation();

            shell.classList.toggle('sidebar-open');

        });

        document.addEventListener('click', function (e) {

            if (
                window.innerWidth <= 860 &&
                shell.classList.contains('sidebar-open') &&
                !document.getElementById('adminSidebar').contains(e.target) &&
                !burger.contains(e.target)
            ) {
                closeSidebar();
            }

        });

    }


    /* =========================================
       BUSCADOR DE PARTICIPANTES
       ========================================= */

    var buscar = document.getElementById('buscarParticipantes');

    if (buscar) {

        var limpiar   = document.getElementById('limpiarBusquedaParticipantes');
        var conteo    = document.getElementById('conteoParticipantes');
        var vacio     = document.getElementById('participantesSinResultados');
        var filas     = Array.prototype.slice.call(
            document.querySelectorAll('.participante-row')
        );

        var total = filas.length;

        // Sin acentos y en minúsculas, para que "jose" encuentre "José"
        function normalizar(texto) {
            return texto
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        }

        function filtrarParticipantes() {

            var consulta = normalizar(buscar.value);
            var visibles = 0;

            filas.forEach(function (fila) {

                var coincide =
                    consulta === '' ||
                    normalizar(fila.textContent).indexOf(consulta) !== -1;

                fila.hidden = !coincide;

                if (coincide) {
                    visibles++;
                }

            });

            if (vacio) {
                vacio.hidden = visibles > 0 || consulta === '';
            }

            if (limpiar) {
                limpiar.hidden = consulta === '';
            }

            if (conteo) {
                conteo.textContent =
                    consulta === '' ? '' : visibles + ' de ' + total + ' jugadores';
            }

        }

        buscar.addEventListener('input', filtrarParticipantes);

        if (limpiar) {

            limpiar.addEventListener('click', function () {

                buscar.value = '';
                filtrarParticipantes();
                buscar.focus();

            });

        }

    }

});