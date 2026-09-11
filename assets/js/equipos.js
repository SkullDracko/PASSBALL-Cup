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


    /* =========================================
       SUBIR LOGO (PREVIEW + COLOR VÍA CANVAS)
       ========================================= */

    var logoInput   = document.getElementById('logo_equipo');
    var logoDrop    = document.getElementById('logoDrop');
    var logoPreview = document.getElementById('logoPreview');

    if (logoInput && logoDrop) {

        logoDrop.addEventListener('click', function (e) {
            e.preventDefault();
            logoInput.click();
        });

        logoDrop.addEventListener('dragover', function (e) {
            e.preventDefault();
            logoDrop.classList.add('drag-over');
        });

        logoDrop.addEventListener('dragleave', function () {
            logoDrop.classList.remove('drag-over');
        });

        logoDrop.addEventListener('drop', function (e) {
            e.preventDefault();
            logoDrop.classList.remove('drag-over');

            if (e.dataTransfer.files.length) {
                prepararLogo(e.dataTransfer.files[0]);
            }
        });

        logoInput.addEventListener('change', function () {
            prepararLogo(this.files[0]);
        });

    }


    function prepararLogo(file) {

        if (!file) return;

        if (!/^image\/(jpeg|png|webp|gif)$/.test(file.type)) {

            alert('El logo debe ser una imagen válida (JPG, PNG, WEBP o GIF).');
            logoInput.value = '';
            return;
        }

        if (file.size > 5 * 1024 * 1024) {

            alert('El logo no puede superar los 5 MB.');
            logoInput.value = '';
            return;
        }

        var reader = new FileReader();

        reader.onload = function (e) {

            logoPreview.innerHTML =
                '<img src="' + e.target.result + '" alt="Logo del equipo">';

            logoPreview.style.display = 'flex';
            logoDrop.style.display    = 'none';

            derivarColor(e.target.result);

        };

        reader.readAsDataURL(file);
    }


    /* Color dominante del logo para acento del preview (canvas en navegador) */

    function derivarColor(dataUrl) {

        var img = new Image();

        img.onload = function () {

            var canvas = document.createElement('canvas');
            canvas.width  = img.width;
            canvas.height = img.height;

            var ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            try {

                var data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;

                var r = 0, g = 0, b = 0, n = 0;

                for (var i = 0; i < data.length; i += 40) {
                    r += data[i];
                    g += data[i + 1];
                    b += data[i + 2];
                    n++;
                }

                if (n > 0) {

                    var color = 'rgb(' +
                        Math.round(r / n) + ',' +
                        Math.round(g / n) + ',' +
                        Math.round(b / n) + ')';

                    logoPreview.style.borderColor = color;
                }

            } catch (err) {
                /* Canvas taint: solo estético, se ignora */
            }

        };

        img.src = dataUrl;
    }


    /* =========================================
       CONFIRMAR POSTULACIÓN AL TORNEO
       ========================================= */

    var postularForms = document.querySelectorAll('.postular-form');

    postularForms.forEach(function (form) {

        form.addEventListener('submit', function (e) {

            if (!confirm('¿Postular tu equipo al torneo? El organizador revisará la solicitud.')) {
                e.preventDefault();
            }

        });

    });

});
