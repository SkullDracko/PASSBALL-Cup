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
       ELEGIR INTEGRANTES AL CREAR EQUIPO
       ========================================= */

    var MAX_MIEMBROS = 12;

    var buscarJugador =
        document.getElementById('buscarJugador');

    var miembrosResultados =
        document.getElementById('miembrosResultados');

    var miembrosElegidos =
        document.getElementById('miembrosElegidos');

    var miembrosHidden =
        document.getElementById('miembrosHidden');

    var integrantesTotal =
        document.getElementById('integrantesTotal');

    if (buscarJugador && miembrosResultados) {

        var seleccionados = {};
        var CAPITAN = 1;

        function contarSeleccionados() {
            return CAPITAN + Object.keys(seleccionados).length;
        }

        function actualizarTotal() {

            var total = contarSeleccionados();

            if (integrantesTotal) {
                integrantesTotal.textContent =
                    total + '/' + MAX_MIEMBROS + ' integrantes';
            }

            return total;
        }

        function cerrarResultados() {

            miembrosResultados.innerHTML = '';
            miembrosResultados.classList.remove('show');
        }

        function crearAvatar(u) {

            var avatar =
                document.createElement('span');

            avatar.className = 'miembro-select-avatar';

            if (u.avatar) {

                var img = document.createElement('img');
                img.src = u.avatar;
                img.alt = '';
                avatar.appendChild(img);

            } else {

                avatar.textContent =
                    (u.nombre || '?').charAt(0).toUpperCase();

            }

            return avatar;
        }

        function agregarJugador(u) {

            if (seleccionados[u.id]) return;

            if (contarSeleccionados() >= MAX_MIEMBROS) return;

            seleccionados[u.id] = true;

            /* Hidden input dentro del formulario */

            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'integrantes[]';
            hidden.value = u.id;
            hidden.setAttribute('data-miembro', u.id);
            miembrosHidden.appendChild(hidden);

            /* Chip visible */

            var chip =
                document.createElement('span');

            chip.className = 'miembro-chip';
            chip.setAttribute('data-chip', u.id);

            var avatar =
                document.createElement('span');

            avatar.className = 'miembro-chip-avatar';

            if (u.avatar) {

                var img = document.createElement('img');
                img.src = u.avatar;
                img.alt = '';
                avatar.appendChild(img);

            } else {

                avatar.textContent =
                    (u.nombre || '?').charAt(0).toUpperCase();

            }

            var nombre =
                document.createElement('span');

            nombre.className = 'miembro-chip-nombre';
            nombre.textContent = u.nombre;

            var remove =
                document.createElement('button');

            remove.type = 'button';
            remove.className = 'miembro-chip-remove';
            remove.title = 'Quitar';
            remove.setAttribute('aria-label', 'Quitar integrante');
            remove.innerHTML = '&times;';

            remove.addEventListener('click', function () {

                quitarJugador(u.id);

            });

            chip.appendChild(avatar);
            chip.appendChild(nombre);
            chip.appendChild(remove);

            miembrosElegidos.appendChild(chip);

            actualizarTotal();
            cerrarResultados();
            buscarJugador.value = '';
            buscarJugador.focus();
        }

        function quitarJugador(id) {

            delete seleccionados[id];

            var hidden =
                miembrosHidden.querySelector(
                    'input[data-miembro="' + id + '"]'
                );

            if (hidden) hidden.remove();

            var chip =
                miembrosElegidos.querySelector(
                    'span[data-chip="' + id + '"]'
                );

            if (chip) chip.remove();

            actualizarTotal();
        }

        function renderResultado(u) {

            var yaSeleccionado =
                !!seleccionados[u.id];

            var ocupado =
                parseInt(u.en_equipo, 10) > 0 && !yaSeleccionado;

            var lleno =
                contarSeleccionados() >= MAX_MIEMBROS && !yaSeleccionado;

            var item =
                document.createElement('div');

            item.className = 'miembro-select-item';

            if (ocupado || lleno) {
                item.classList.add('disabled');
            }

            var avatar = crearAvatar(u);

            var info =
                document.createElement('div');

            info.className = 'miembro-select-info';

            var nombre =
                document.createElement('strong');

            nombre.textContent = u.nombre;

            var matricula =
                document.createElement('span');

            matricula.textContent = 'Mat: ' + u.matricula;

            info.appendChild(nombre);
            info.appendChild(matricula);

            var estado =
                document.createElement('span');

            estado.className = 'miembro-select-estado';

            estado.textContent = ocupado
                ? 'En otro equipo'
                : (yaSeleccionado ? 'Seleccionado' : 'Agregar');

            item.appendChild(avatar);
            item.appendChild(info);
            item.appendChild(estado);

            if (!ocupado && !(lleno)) {
                item.addEventListener('click', function () {
                    agregarJugador(u);
                });
            }

            miembrosResultados.appendChild(item);
        }

        function buscar(q) {

            fetch(
                'controllers/buscarUsuarios.php?q=' +
                encodeURIComponent(q)
            )
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {

                miembrosResultados.innerHTML = '';

                if (!data.success) {
                    miembrosResultados.innerHTML =
                        '<p class="miembro-vacio">Error al buscar.</p>';
                    return;
                }

                if (!data.usuarios.length) {
                    miembrosResultados.innerHTML =
                        '<p class="miembro-vacio">Sin resultados.</p>';
                    return;
                }

                data.usuarios.forEach(function (u) {
                    renderResultado(u);
                });

                miembrosResultados.classList.add('show');

            })
            .catch(function () {
                miembrosResultados.innerHTML =
                    '<p class="miembro-vacio">Error de conexión.</p>';
            });
        }

        var timer = null;

        buscarJugador.addEventListener('input', function () {

            clearTimeout(timer);

            var q = this.value.trim();

            if (q.length < 2) {
                cerrarResultados();
                return;
            }

            timer = setTimeout(function () {
                buscar(q);
            }, 250);
        });

        buscarJugador.addEventListener('keydown', function (e) {

            if (e.key === 'Escape') {
                cerrarResultados();
            }
        });

        document.addEventListener('click', function (e) {

            if (
                miembrosResultados.classList.contains('show') &&
                !miembrosResultados.contains(e.target) &&
                !buscarJugador.contains(e.target)
            ) {
                cerrarResultados();
            }
        });
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
