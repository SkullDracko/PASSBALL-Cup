/**
 * PASSBALL Cup - Dashboard
 * Navegación por pestañas + funcionalidad de equipos
 */

document.addEventListener('DOMContentLoaded', function () {

    /* =========================================
       NAVEGACIÓN POR PESTAÑAS
       ========================================= */

    var tabs = document.querySelectorAll('.nav-tab, .quick-tab, .match-tab');

    var views = document.querySelectorAll('.dashboard-view');

    function showView(targetId) {

        var target =
            document.getElementById(targetId);

        if (!target) {
            return;
        }

        /* Ocultar todas las vistas */

        views.forEach(function (v) {
            v.classList.remove('active');
        });

        /* Mostrar la vista objetivo */

        target.classList.add('active');

        var scrollEl =
            document.querySelector('.main-area');

        if (scrollEl) {
            scrollEl.scrollTop = 0;
        }

    }


    function setActiveTab(tab) {

        /* Quitar 'active' a todas las pestañas */

        tabs.forEach(function (t) {
            t.classList.remove('active');
        });

        /* Activar la pestaña clicada */

        if (tab) {
            tab.classList.add('active');
        }

    }


    tabs.forEach(function (tab) {

        tab.addEventListener('click', function (e) {

            e.preventDefault();

            var target =
                this.getAttribute('data-target');

            if (!target) {
                return;
            }

            showView(target);
            setActiveTab(this);

            /* Si la pestaña es una acción de equipos,
               además enfocar el buscador */

            if (
                this.classList.contains('quick-tab') &&
                target === 'view-equipos'
            ) {
                return;
            }

        });

    });


    /* =========================================
       ACERCAR / BUSCAR EQUIPOS
       ========================================= */

    var focusSearch =
        document.getElementById('focusSearch');

    var teamSearch =
        document.getElementById('teamSearch');

    var teamCards =
        document.querySelectorAll('.team-card');

    var noResults =
        document.getElementById('noResults');


    if (focusSearch && teamSearch) {

        focusSearch.addEventListener(
            'click',
            function () {
                showView('view-equipos');
                teamSearch.focus();
            }
        );

    }


    if (teamSearch) {

        teamSearch.addEventListener(
            'input',
            function () {

                var value =
                    this.value.toLowerCase().trim();

                var visible = 0;

                teamCards.forEach(function (card) {

                    var name =
                        card.getAttribute('data-team-name') || '';

                    if (name.indexOf(value) !== -1) {

                        card.style.display = '';
                        visible++;

                    } else {

                        card.style.display = 'none';

                    }

                });

                if (noResults) {

                    if (visible === 0 && value !== '') {

                        noResults.classList.add('show');

                    } else {

                        noResults.classList.remove('show');

                    }

                }

            }
        );

    }


    /* =========================================
       MODAL REGISTRAR EQUIPO
       ========================================= */

    var registerModal =
        document.getElementById('registerModal');

    var openRegister =
        document.getElementById('openRegister');

    var openRegisterBottom =
        document.getElementById('openRegisterBottom');

    var closeRegister =
        document.getElementById('closeRegister');

    var cancelRegister =
        document.getElementById('cancelRegister');

    var modalOverlay = registerModal
        ? registerModal.querySelector('.modal-overlay')
        : null;


    function openModal() {

        if (!registerModal) {
            return;
        }

        registerModal.classList.add('show');
        registerModal.setAttribute('aria-hidden', 'false');

        var nombre =
            document.getElementById('nombre_equipo');

        if (nombre) {
            setTimeout(function () {
                nombre.focus();
            }, 100);
        }

    }


    function closeModal() {

        if (!registerModal) {
            return;
        }

        registerModal.classList.remove('show');
        registerModal.setAttribute('aria-hidden', 'true');

    }


    if (openRegister) {
        openRegister.addEventListener('click', openModal);
    }

    if (openRegisterBottom) {
        openRegisterBottom.addEventListener('click', openModal);
    }

    if (closeRegister) {
        closeRegister.addEventListener('click', closeModal);
    }

    if (cancelRegister) {
        cancelRegister.addEventListener('click', closeModal);
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', closeModal);
    }


    document.addEventListener('keydown', function (e) {

        if (
            e.key === 'Escape' &&
            registerModal &&
            registerModal.classList.contains('show')
        ) {
            closeModal();
        }

    });


    /* =========================================
       CERRAR FLASH DESPUÉS DE 4 SEGUNDOS
       ========================================= */

    var flashes =
        document.querySelectorAll('.flash');

    flashes.forEach(function (flash) {

        setTimeout(function () {

            flash.style.transition = 'opacity 0.4s ease';
            flash.style.opacity = '0';

            setTimeout(function () {
                flash.remove();
            }, 400);

        }, 4000);

    });


    /* =========================================
       TOGGLE SIDEBAR / MENÚ MÓVIL (si existe)
       ========================================= */

    var sidebar =
        document.querySelector('.dashboard-sidebar');

    var topbar =
        document.querySelector('.topbar');

    if (sidebar && topbar && document.querySelector('.nav-left')) {

        var toggle = document.createElement('button');

        toggle.className = 'sidebar-toggle';
        toggle.setAttribute('aria-label', 'Menú');
        toggle.setAttribute('type', 'button');
        toggle.innerHTML = '&#9776;';

        toggle.style.cssText = [
            'background: none',
            'border: none',
            'color: white',
            'font-size: 20px',
            'cursor: pointer',
            'padding: 4px 8px',
            'display: none'
        ].join(';');

        var navLeft =
            topbar.querySelector('.nav-left');

        if (navLeft) {
            navLeft.insertBefore(toggle, navLeft.firstChild);
        }

        function checkWidth() {

            if (window.innerWidth <= 800) {

                toggle.style.display = 'block';

            } else {

                toggle.style.display = 'none';
                sidebar.classList.remove('sidebar-open');

            }

        }

        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('sidebar-open');
        });

        document.addEventListener('click', function (e) {

            if (
                window.innerWidth <= 800 &&
                sidebar.classList.contains('sidebar-open') &&
                !sidebar.contains(e.target) &&
                e.target !== toggle
            ) {
                sidebar.classList.remove('sidebar-open');
            }

        });

        checkWidth();

        window.addEventListener('resize', checkWidth);

    }

});
