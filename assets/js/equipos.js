/**
 * PASSBALL Cup - Equipos
 */

document.addEventListener('DOMContentLoaded', function () {

    /* =========================================
       BUSCADOR DE EQUIPOS
       ========================================= */

    function solicitarApi(url) {
        return fetch(url, { credentials: 'same-origin' })
            .then(function (response) {
                return response.json().catch(function () {
                    return {};
                }).then(function (payload) {
                    if (!response.ok || !payload.exito) {
                        let message = payload.errores && payload.errores.error;
                        throw new Error(message || 'No se pudo cargar la información.');
                    }

                    return payload.data || {};
                });
            });
    }

    function mostrarErrorEquipos(message) {
        let feedback = document.getElementById('teamsFeedback');

        if (feedback) {
            feedback.textContent = message;
            feedback.hidden = false;
        }
    }

    function crearLogoEquipo(container, team, className) {
        if (team.logo) {
            let image = document.createElement('img');
            image.src = team.logo;
            image.alt = team.nombre || 'Logo del equipo';
            container.appendChild(image);
            return;
        }

        let initials = document.createElement('div');
        initials.className = className;
        initials.style.background = 'let(--purple, #4b2780)';
        initials.textContent = (team.nombre || '?').trim().charAt(0).toLocaleUpperCase();
        container.appendChild(initials);
    }

    function filtrarEquipos() {
        let searchInput = document.getElementById('teamSearch');

        if (!searchInput || searchInput.dataset.bound === 'true') return;
        searchInput.dataset.bound = 'true';

        searchInput.addEventListener('input', function () {
            let value = this.value.toLocaleLowerCase().trim();
            let visible = 0;
            let teamCards = document.querySelectorAll('.team-card');
            let noResults = document.getElementById('noResults');
            let emptyState = document.getElementById('teamsEmpty');
            let grid = document.getElementById('teamsGrid');

            if (grid && grid.dataset.hasTeams === 'false') {
                if (emptyState) emptyState.classList.toggle('show', value === '');
                if (noResults) noResults.classList.remove('show');
                return;
            }

            teamCards.forEach(function (card) {
                let name = card.getAttribute('data-team-name') || '';
                let matches = name.indexOf(value) !== -1;

                card.style.display = matches ? '' : 'none';
                if (matches) visible++;
            });

            if (noResults) {
                noResults.classList.toggle('show', value !== '' && visible === 0);
            }
        });
    }

    function renderizarEquipos(teams) {
        let grid = document.getElementById('teamsGrid');
        let emptyState = document.getElementById('teamsEmpty');

        if (!grid) return;

        grid.replaceChildren();
        grid.dataset.hasTeams = teams.length ? 'true' : 'false';

        if (emptyState) {
            emptyState.classList.toggle('show', teams.length === 0);
        }

        teams.forEach(function (team, index) {
            let card = document.createElement('article');
            card.className = 'team-card';
            card.dataset.teamName = (team.nombre || '').toLocaleLowerCase();

            let logo = document.createElement('div');
            logo.className = 'team-card-logo';
            crearLogoEquipo(logo, team, 'no-logo');

            let name = document.createElement('h3');
            name.textContent = team.nombre || 'Equipo sin nombre';

            let members = document.createElement('p');
            members.className = 'team-members';

            let membersIcon = document.createElement('i');
            membersIcon.className = 'fa-solid fa-users';
            members.appendChild(membersIcon);
            members.appendChild(document.createTextNode(' Participantes: ' + (team.total_miembros || 0)));

            let detail = document.createElement('a');
            detail.className = 'btn-outline' + (index % 2 === 1 ? ' orange' : '');
            detail.href = 'equipos/detalle.php?id=' + encodeURIComponent(team.id);
            detail.textContent = 'Ver equipo ';

            let arrow = document.createElement('i');
            arrow.className = 'fa-solid fa-arrow-right';
            detail.appendChild(arrow);

            card.appendChild(logo);
            card.appendChild(name);
            card.appendChild(members);
            card.appendChild(detail);
            grid.appendChild(card);
        });

        filtrarEquipos();
    }

    function cargarEquiposDisponibles() {
        solicitarApi('backend/api/equipos?estado=activo')
            .then(function (data) {
                renderizarEquipos(Array.isArray(data.equipos) ? data.equipos : []);
            })
            .catch(function (error) {
                let grid = document.getElementById('teamsGrid');
                if (grid) grid.replaceChildren();
                mostrarErrorEquipos(error.message);
            });
    }

    function cargarMiEquipo() {
        let loading = document.getElementById('myTeamLoading');
        let playerSection = document.getElementById('playerTeamsSection');
        let currentUserId;

        solicitarApi('backend/api/auth/me')
            .then(function (data) {
                let usuario = data.usuario;
                currentUserId = usuario && usuario.id;

                if (!currentUserId) {
                    throw new Error('No se pudo identificar al usuario actual.');
                }

                let esJugadorHabilitado = usuario.rol === 'jugador'
                    && usuario.estado === 'activo'
                    && Number(usuario.jugador_activo) === 1;

                if (playerSection) playerSection.hidden = !esJugadorHabilitado;
                if (!esJugadorHabilitado) return null;

                return solicitarApi(
                    'backend/api/jugadores/' + encodeURIComponent(currentUserId) + '/equipo-actual'
                );
            })
            .then(function (data) {
                if (!data) return;

                let team = data.equipo;
                let actions = document.getElementById('teamActions');

                if (loading) loading.hidden = true;

                if (!team) {
                    if (actions) actions.hidden = false;
                    return;
                }

                let card = document.getElementById('myTeamCard');
                let logo = document.getElementById('myTeamLogo');
                let name = document.getElementById('myTeamName');
                let members = document.getElementById('myTeamMembers');
                let role = document.getElementById('myTeamRole');
                let detail = document.getElementById('myTeamDetail');
                let registrationStatus = document.getElementById('myTeamRegistrationStatus');
                let rejectionReason = document.getElementById('myTeamRejectionReason');
                let rejectionReasonText = document.getElementById('myTeamRejectionReasonText');
                let tournamentStatus = document.getElementById('myTeamTournamentStatus');

                if (card) card.hidden = false;
                if (name) name.textContent = team.nombre || 'Equipo sin nombre';
                if (logo) crearLogoEquipo(logo, team, 'no-logo');
                if (members) members.appendChild(document.createTextNode(' Participantes: ' + (team.total_miembros || 0)));
                if (detail) detail.href = 'equipos/detalle.php?id=' + encodeURIComponent(team.id);
                if (registrationStatus) {
                    let status = String(team.estado || '').toLocaleLowerCase();
                    let applicationStatus = String(team.estado_postulacion || '').toLocaleLowerCase();
                    let teamRejected = status === 'rechazado';
                    let applicationRejected = applicationStatus === 'rechazado';
                    let pending = status === 'pendiente';
                    let inactive = status === 'inactivo' || teamRejected;
                    let hasRejection = teamRejected || applicationRejected;
                    let statusLabel = registrationStatus.querySelector('.team-registration-label');
                    let statusIcon = registrationStatus.querySelector('i');

                    registrationStatus.classList.toggle('pendiente', pending);
                    registrationStatus.classList.toggle('inactivo', inactive);

                    if (statusLabel) {
                        statusLabel.textContent = pending
                            ? 'Equipo pendiente de aceptación'
                            : (inactive
                                ? 'Equipo inactivo'
                                : (status === 'activo' ? 'Equipo registrado' : 'Estado del equipo desconocido'));
                    }

                    if (statusIcon) {
                        statusIcon.className = pending
                            ? 'fa-solid fa-clock'
                            : (inactive
                                ? 'fa-solid fa-circle-xmark'
                                : 'fa-solid fa-circle-check');
                    }

                    if (rejectionReason) {
                        rejectionReason.hidden = !hasRejection;
                    }

                    if (rejectionReasonText && hasRejection) {
                        let reason = teamRejected
                            ? team.motivo_rechazo
                            : team.motivo_rechazo_postulacion;
                        rejectionReasonText.textContent = reason || 'Sin motivo registrado.';
                    }
                }

                if (tournamentStatus) {
                    let applicationStatus = String(team.estado_postulacion || '').toLocaleLowerCase();
                    let applicationLabels = {
                        pendiente: 'Estado de postulación: Pendiente',
                        aprobado: 'Estado de postulación: Aprobada',
                        rechazado: 'Estado de postulación: Rechazada',
                        retirado: 'Estado de postulación: Retirada'
                    };
                    let applicationClasses = ['pendiente', 'aprobado', 'rechazado', 'retirado', 'sin-postulacion'];

                    applicationClasses.forEach(function (className) {
                        tournamentStatus.classList.remove(className);
                    });
                    tournamentStatus.classList.add(applicationLabels[applicationStatus]
                        ? applicationStatus
                        : 'sin-postulacion');
                    tournamentStatus.textContent = applicationLabels[applicationStatus]
                        || 'Sin postulación';
                }
                if (role) {
                    let roleIcon = document.createElement('i');
                    roleIcon.className = 'fa-solid fa-star';
                    role.appendChild(roleIcon);
                    role.appendChild(document.createTextNode(
                        Number(team.capitan_id) === Number(currentUserId)
                            ? ' Líder del equipo'
                            : ' Miembro del equipo'
                    ));
                }
            })
            .catch(function (error) {
                if (loading) {
                    loading.textContent = 'No se pudo cargar la información de tu equipo.';
                }
                mostrarErrorEquipos(error.message);
            });
    }

    function iniciarVistaEquipos() {
        let view = document.getElementById('view-equipos');

        if (!view) return;

        let markup = view.querySelector('#teamsGrid')
            ? Promise.resolve()
            : fetch(view.dataset.fetchPartial || 'partials/equipos.html', {
                credentials: 'same-origin'
            }).then(function (response) {
                if (!response.ok) throw new Error('No se pudo cargar la sección de equipos.');
                return response.text();
            }).then(function (html) {
                view.innerHTML = html;
            });

        markup.then(function () {
            filtrarEquipos();
            cargarEquiposDisponibles();
            cargarMiEquipo();
        }).catch(function (error) {
            view.textContent = error.message;
        });
    }


    /* =========================================
       NOTA
       =========================================
       El cierre del modal (.modal-overlay) y el retiro
       automatico de .flash estan en dashboard.js, que
       es quien abre el modal. Estaban duplicados aqui. */


    /* =========================================
       SUBIR LOGO (PREVIEW + COLOR VÍA CANVAS)
       ========================================= */

    let logoInput   = document.getElementById('logo_equipo');
    let logoDrop    = document.getElementById('logoDrop');
    let logoPreview = document.getElementById('logoPreview');

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

        let reader = new FileReader();

        reader.onload = function (e) {

            logoPreview.innerHTML =
                '<img src="' + e.target.result + '" alt="Logo del equipo">';

            logoPreview.style.display = 'flex';
            logoDrop.style.display    = 'none';

            deriletColor(e.target.result);

        };

        reader.readAsDataURL(file);
    }


    /* Color dominante del logo para acento del preview (canvas en navegador) */

    function deriletColor(dataUrl) {

        let img = new Image();

        img.onload = function () {

            let canvas = document.createElement('canvas');
            canvas.width  = img.width;
            canvas.height = img.height;

            let ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            try {

                let data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;

                let r = 0, g = 0, b = 0, n = 0;

                for (let i = 0; i < data.length; i += 40) {
                    r += data[i];
                    g += data[i + 1];
                    b += data[i + 2];
                    n++;
                }

                if (n > 0) {

                    let color = 'rgb(' +
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

    let MAX_MIEMBROS = 12;

    let buscarJugador =
        document.getElementById('buscarJugador');

    let miembrosResultados =
        document.getElementById('miembrosResultados');

    let miembrosElegidos =
        document.getElementById('miembrosElegidos');

    let miembrosHidden =
        document.getElementById('miembrosHidden');

    let integrantesTotal =
        document.getElementById('integrantesTotal');

    if (buscarJugador && miembrosResultados) {

        let seleccionados = {};
        let CAPITAN = 1;

        function contarSeleccionados() {
            return CAPITAN + Object.keys(seleccionados).length;
        }

        function actualizarTotal() {

            let total = contarSeleccionados();

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

            let avatar =
                document.createElement('span');

            avatar.className = 'miembro-select-avatar';

            if (u.avatar) {

                let img = document.createElement('img');
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

            let hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'integrantes[]';
            hidden.value = u.id;
            hidden.setAttribute('data-miembro', u.id);
            miembrosHidden.appendChild(hidden);

            /* Chip visible */

            let chip =
                document.createElement('span');

            chip.className = 'miembro-chip';
            chip.setAttribute('data-chip', u.id);

            let avatar =
                document.createElement('span');

            avatar.className = 'miembro-chip-avatar';

            if (u.avatar) {

                let img = document.createElement('img');
                img.src = u.avatar;
                img.alt = '';
                avatar.appendChild(img);

            } else {

                avatar.textContent =
                    (u.nombre || '?').charAt(0).toUpperCase();

            }

            let nombre =
                document.createElement('span');

            nombre.className = 'miembro-chip-nombre';
            nombre.textContent = u.nombre;

            let remove =
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

            let hidden =
                miembrosHidden.querySelector(
                    'input[data-miembro="' + id + '"]'
                );

            if (hidden) hidden.remove();

            let chip =
                miembrosElegidos.querySelector(
                    'span[data-chip="' + id + '"]'
                );

            if (chip) chip.remove();

            actualizarTotal();
        }

        function renderResultado(u) {

            let yaSeleccionado =
                !!seleccionados[u.id];

            let ocupado =
                parseInt(u.en_equipo, 10) > 0 && !yaSeleccionado;

            let lleno =
                contarSeleccionados() >= MAX_MIEMBROS && !yaSeleccionado;

            let item =
                document.createElement('div');

            item.className = 'miembro-select-item';

            if (ocupado || lleno) {
                item.classList.add('disabled');
            }

            let avatar = crearAvatar(u);

            let info =
                document.createElement('div');

            info.className = 'miembro-select-info';

            let nombre =
                document.createElement('strong');

            nombre.textContent = u.nombre;

            let matricula =
                document.createElement('span');

            matricula.textContent = 'Mat: ' + u.matricula;

            info.appendChild(nombre);
            info.appendChild(matricula);

            let estado =
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
                'backend/api/usuarios/buscar?q=' +
                encodeURIComponent(q),
                { credentials: 'same-origin' }
            )
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                console.log("Esta es la info del endpoint buscar:", data);
                miembrosResultados.innerHTML = '';

                if (!data.exito) {
                    miembrosResultados.innerHTML =
                        '<p class="miembro-vacio">Error al buscar.</p>';
                    return;
                }

                let usuarios = data.data.usuarios;

                if (!usuarios.length) {
                    miembrosResultados.innerHTML =
                        '<p class="miembro-vacio">Sin resultados.</p>';
                    return;
                }

                usuarios.forEach(function (u) {
                    renderResultado(u);
                });

                miembrosResultados.classList.add('show');

            })
            .catch(function () {
                miembrosResultados.innerHTML =
                    '<p class="miembro-vacio">Error de conexión.</p>';
            });
        }

        let timer = null;

        buscarJugador.addEventListener('input', function () {

            clearTimeout(timer);

            let q = this.value.trim();

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
       REGISTRAR EQUIPO (API)
       =========================================
       El <form> sigue apuntando a controllers/registrarEquipo.php como
       red de seguridad para cuando no hay JS, pero si el script carga
       se intercepta el submit y se va por POST /api/equipos.

       No se usa apiRequest() a propósito: esa helper fuerza
       Content-Type: application/json y hace JSON.stringify(), y un
       archivo binario no sobrevive a ninguna de las dos cosas. Con
       FormData el navegador pone el multipart boundary solo y no hay
       que tocar el header. */

    let registerForm = document.getElementById('registerForm');

    if (registerForm) {

        let registerError = document.getElementById('registerError');
        let registerSubmit = registerForm.querySelector('.submit-button');

        registerForm.addEventListener('submit', function (e) {

            e.preventDefault();

            let nombre = document.getElementById('nombre_equipo').value.trim();
            let motivoSolicitud = document.getElementById('motivo_solicitud').value.trim();
            let archivo = document.getElementById('logo_equipo').files[0];

            if (nombre.length < 3) {
                mostrarErrorRegister('El nombre debe tener al menos 3 caracteres.');
                return;
            }

            if (!motivoSolicitud) {
                mostrarErrorRegister('Escribe el motivo para que acepten a tu equipo.');
                return;
            }

            if (!archivo) {
                mostrarErrorRegister('El logo del equipo es obligatorio.');
                return;
            }

            let fd = new FormData();

            fd.append('nombre', nombre);
            fd.append('motivo_solicitud', motivoSolicitud);
            fd.append('logo', archivo);

            miembrosHidden.querySelectorAll('input[name="integrantes[]"]')
                .forEach(function (input) {
                    fd.append('integrantes[]', input.value);
                });

            if (registerSubmit) setLoading(registerSubmit, true);
            if (registerError) registerError.innerHTML = '';

            fetch('backend/api/equipos', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
                .then(function (r) {
                    return r.json().then(function (data) {
                        return { status: r.status, data: data };
                    });
                })
                .then(function (res) {

                    if (!res.data.exito) {

                        mostrarErrorRegister(
                            (res.data.errores && res.data.errores.error)
                                ? res.data.errores.error
                                : 'No se pudo registrar el equipo.'
                        );

                        if (registerSubmit) setLoading(registerSubmit, false);
                        return;
                    }

                    /* El servidor ya guardó el equipo: recargar muestra la
                       tarjeta de "mi equipo" y la postulación al torneo. */

                    window.location.reload();

                })
                .catch(function () {

                    mostrarErrorRegister('Error de conexión. Intenta de nuevo.');

                    if (registerSubmit) setLoading(registerSubmit, false);

                });

        });

        function mostrarErrorRegister(mensaje) {

            if (!registerError) {

                alert(mensaje);
                return;

            }

            registerError.textContent = mensaje;
            registerError.classList.add('show');

        }

    }


    /* =========================================
       CONFIRMAR POSTULACIÓN AL TORNEO
       ========================================= */

    let postularForms = document.querySelectorAll('.postular-form');

    postularForms.forEach(function (form) {

        form.addEventListener('submit', function (e) {

            if (!confirm('¿Postular tu equipo al torneo? El organizador revisará la solicitud.')) {
                e.preventDefault();
            }

        });

    });

    iniciarVistaEquipos();

});
