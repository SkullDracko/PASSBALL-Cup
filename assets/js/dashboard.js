/**
 * PASSBALL Cup - Dashboard
 */

document.addEventListener('DOMContentLoaded', function () {

    /* =========================================
       TOGGLE SIDEBAR (MÓVIL)
       ========================================= */

    var sidebar = document.querySelector('.dashboard-sidebar');
    var topbar  = document.querySelector('.topbar');

    if (!sidebar || !topbar) return;


    var toggle = document.createElement('button');

    toggle.className = 'sidebar-toggle';
    toggle.setAttribute('aria-label', 'Menú');
    toggle.innerHTML = '☰';

    toggle.style.cssText = [
        'background: none',
        'border: none',
        'color: white',
        'font-size: 20px',
        'cursor: pointer',
        'padding: 4px 8px',
        'display: none'
    ].join(';');


    var navLeft = topbar.querySelector('.nav-left');

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

});
