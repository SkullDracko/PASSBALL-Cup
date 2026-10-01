/**
 * PASSBALL Cup - Panel admin
 * Navegación por vistas + sidebar móvil
 */

/* =========================================
   SPLASH DE CARGA
   ========================================= */

var pbLoaderHidden = false;
var pbStart = Date.now();
var pbMinShow = 2000;

function pbHideLoader() {

    var loader = document.getElementById('pbLoader');

    if (pbLoaderHidden || !loader) return;

    if (Date.now() - pbStart < pbMinShow) {
        setTimeout(pbHideLoader, pbMinShow - (Date.now() - pbStart));
        return;
    }

    pbLoaderHidden = true;

    loader.classList.add('pb-done');

    setTimeout(function () {
        if (loader.parentNode) {
            loader.parentNode.removeChild(loader);
        }
    }, 650);

}

window.addEventListener('load', pbHideLoader);
setTimeout(pbHideLoader, pbMinShow);

document.addEventListener('DOMContentLoaded', function () {

    // Los datos de Inicio los pide api.js a backend/api.
    if (typeof cargarInicio === 'function') {
        cargarInicio();
    }


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

        // Las vistas de la Etapa 2.4 piden sus datos a la API la primera vez
        // que se abren, no todas al cargar la página.
        if (typeof cargarVista === 'function') {
            cargarVista(targetId);
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

});