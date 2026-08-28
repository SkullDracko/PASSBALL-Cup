/**
 * PASSBALL Cup - Equipos
 */

document.addEventListener('DOMContentLoaded', function () {

    /* =========================================
       BUSCADOR DE EQUIPOS
       ========================================= */

    var searchInput = document.getElementById('teamSearch');
    var teamCards   = document.querySelectorAll('.team-card');
    var noResults   = document.getElementById('noResults');

    if (searchInput) {

        searchInput.addEventListener('input', function () {

            var value = this.value.toLowerCase().trim();
            var visible = 0;

            teamCards.forEach(function (card) {

                var name = card.getAttribute('data-team-name') || '';

                if (name.indexOf(value) !== -1) {

                    card.style.display = '';
                    visible++;

                } else {

                    card.style.display = 'none';

                }

            });

            if (noResults) {

                if (visible === 0) {
                    noResults.classList.add('show');
                } else {
                    noResults.classList.remove('show');
                }

            }

        });

    }


    /* =========================================
       CERRAR MODAL
       ========================================= */

    var modals = document.querySelectorAll('.modal');

    modals.forEach(function (modal) {

        var overlay = modal.querySelector('.modal-overlay');

        if (overlay) {

            overlay.addEventListener('click', function () {

                modal.classList.remove('show');

            });

        }

    });


    /* =========================================
       CERRAR FLASH DESPUÉS DE 4 SEGUNDOS
       ========================================= */

    var flashes = document.querySelectorAll('.flash');

    flashes.forEach(function (flash) {

        setTimeout(function () {

            flash.style.transition = 'opacity 0.4s ease';
            flash.style.opacity = '0';

            setTimeout(function () {

                flash.remove();

            }, 400);

        }, 4000);

    });

});
