document.addEventListener('DOMContentLoaded', function () {
    var view = document.getElementById('view-resultados');

    if (!view) return;

    var tournamentSelect = document.getElementById('results-tournament');
    var feedback = document.getElementById('results-feedback');
    var retryButton = document.getElementById('results-retry');
    var matchList = document.getElementById('results-list');
    var emptyState = document.getElementById('results-empty');
    var eventsDialog = document.getElementById('results-events-modal');
    var eventsTitle = document.getElementById('results-events-title');
    var eventsMatchup = document.getElementById('results-events-matchup');
    var eventsFeedback = document.getElementById('results-events-feedback');
    var eventsRetryButton = document.getElementById('results-events-retry');
    var eventsList = document.getElementById('results-event-list');
    var eventsEmpty = document.getElementById('results-events-empty');
    var closeEventsButton = document.getElementById('results-events-close');

    if (
        !tournamentSelect || !feedback || !retryButton || !matchList || !emptyState ||
        !eventsDialog || !eventsTitle || !eventsMatchup || !eventsFeedback ||
        !eventsRetryButton || !eventsList || !eventsEmpty || !closeEventsButton
    ) return;

    var tournaments = [];
    var currentTournament = null;
    var currentEventsMatch = null;
    var matchesController = null;
    var eventsController = null;
    var retryMatchesAction = null;
    var retryEventsAction = null;
    var tournamentsLoaded = false;
    var tournamentsLoading = false;
    var eventsTrigger = null;

    function apiUrl(path) {
        var base = (view.dataset.apiBase || 'backend/api').replace(/\/$/, '');
        return new URL(base + '/' + path.replace(/^\//, ''), document.baseURI).toString();
    }

    async function requestData(path, signal) {
        var response = await fetch(apiUrl(path), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            signal: signal
        });
        var payload;

        try {
            payload = await response.json();
        } catch (error) {
            throw new Error('La respuesta del servidor no es JSON válido.');
        }

        if (!response.ok || !payload || payload.exito !== true) {
            var message = payload && payload.errores && payload.errores.error;
            throw new Error(message || 'No se pudo cargar la información solicitada.');
        }

        return payload.data || {};
    }

    function createElement(tagName, className, text) {
        var element = document.createElement(tagName);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    }

    function createOption(value, label) {
        var option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        return option;
    }

    function setFeedback(message, state, retry) {
        feedback.textContent = message;
        feedback.dataset.state = state || 'info';
        feedback.setAttribute('role', state === 'error' ? 'alert' : 'status');
        retryMatchesAction = typeof retry === 'function' ? retry : null;
        retryButton.hidden = !retryMatchesAction;
    }

    function setEventsFeedback(message, state, retry) {
        eventsFeedback.textContent = message;
        eventsFeedback.dataset.state = state || 'info';
        eventsFeedback.setAttribute('role', state === 'error' ? 'alert' : 'status');
        retryEventsAction = typeof retry === 'function' ? retry : null;
        eventsRetryButton.hidden = !retryEventsAction;
    }

    function showEmpty(message) {
        matchList.replaceChildren();
        matchList.hidden = true;
        matchList.setAttribute('aria-busy', 'false');
        emptyState.textContent = message;
        emptyState.hidden = false;
    }

    function formatDateTime(value) {
        if (!value) return 'Fecha por definir';

        var normalized = String(value).replace(' ', 'T');
        var date = new Date(normalized);
        if (Number.isNaN(date.getTime())) return 'Fecha por definir';

        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }

    function teamInitials(name) {
        var words = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (!words.length) return '–';
        return words.slice(0, 2).map(function (word) {
            return word.charAt(0).toLocaleUpperCase('es');
        }).join('');
    }

    function createTeam(name, side) {
        var team = createElement('div', 'resultado-team resultado-team--' + side);
        var initials = createElement('span', 'resultado-team__initials', teamInitials(name));
        initials.setAttribute('aria-hidden', 'true');
        team.appendChild(initials);
        team.appendChild(createElement('span', 'resultado-team__name', name || 'Equipo por definir'));
        return team;
    }

    function scoreIsAvailable(match) {
        return match.goles_local !== null && match.goles_local !== undefined && match.goles_local !== '' &&
            match.goles_visitante !== null && match.goles_visitante !== undefined && match.goles_visitante !== '';
    }

    function displayScore(value) {
        var score = Number(value);
        return Number.isFinite(score) ? String(score) : '–';
    }

    function createScoreControl(match, homeName, awayName, card) {
        var control = createElement('div', 'resultado-score-control');

        if (!scoreIsAvailable(match)) {
            control.appendChild(createElement('span', 'resultado-score-pending', 'Marcador pendiente'));
            return control;
        }

        var scoreId = 'resultado-score-' + String(match.id);
        var score = createElement('div', 'resultado-score-value');
        score.id = scoreId;
        score.hidden = true;
        score.setAttribute('role', 'status');
        score.setAttribute('aria-live', 'polite');
        score.setAttribute(
            'aria-label',
            'Marcador de ' + homeName + ' contra ' + awayName + ': ' +
            displayScore(match.goles_local) + ' a ' + displayScore(match.goles_visitante)
        );

        var total = createElement('strong', 'resultado-score-value__total');
        total.appendChild(document.createTextNode(displayScore(match.goles_local) + ' '));
        total.appendChild(createElement('span', '', '–'));
        total.appendChild(document.createTextNode(' ' + displayScore(match.goles_visitante)));
        score.appendChild(total);

        if (match.penales_local !== null && match.penales_local !== undefined &&
            match.penales_visitante !== null && match.penales_visitante !== undefined) {
            score.appendChild(createElement(
                'span',
                'resultado-score-value__penalties',
                'Penales ' + match.penales_local + '–' + match.penales_visitante
            ));
        }

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'resultado-score-toggle';
        toggle.setAttribute('aria-controls', scoreId);
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Mostrar marcador: ' + homeName + ' contra ' + awayName);
        toggle.title = 'Mostrar marcador';

        var icon = createElement('i', 'fa-regular fa-eye');
        icon.setAttribute('aria-hidden', 'true');
        toggle.appendChild(icon);
        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            score.hidden = false;
            toggle.remove();
            card.focus({ preventScroll: true });
        });

        control.append(score, toggle);
        return control;
    }

    function statusInfo(status) {
        var states = {
            programado: { label: 'Programado', className: 'programado' },
            en_curso: { label: 'En juego', className: 'en-curso' },
            finalizado: { label: 'Finalizado', className: 'finalizado' },
            cancelado: { label: 'Cancelado', className: 'cancelado' }
        };
        return states[status] || { label: 'Estado por definir', className: 'desconocido' };
    }

    function isFinalRound(roundName) {
        return /^(?:(?:ronda\s+)?(?:gran\s+)?final)(?:\b|$)/i.test(String(roundName || '').trim());
    }

    function createMatchCard(match, index) {
        var homeName = match.equipo_local_nombre || 'Equipo por definir';
        var awayName = match.equipo_visitante_nombre || 'Equipo por definir';
        var status = statusInfo(match.estado);
        var cardClass = 'resultado-card' + (isFinalRound(match.ronda_nombre) ? ' resultado-card--final' : '');
        var card = createElement('article', cardClass);
        card.tabIndex = 0;
        card.setAttribute('aria-haspopup', 'dialog');
        card.setAttribute('aria-controls', 'results-events-modal');
        card.setAttribute(
            'aria-label',
            homeName + ' contra ' + awayName + '. ' + status.label +
            '. Pulsa Enter o Espacio para ver los eventos del partido.'
        );
        card.style.setProperty('--resultado-index', String(Math.min(index, 8)));

        var header = createElement('div', 'resultado-card__header');
        header.appendChild(createElement('span', 'resultado-card__round', match.ronda_nombre || 'Ronda por definir'));
        var statusBadge = createElement('span', 'resultado-status resultado-status--' + status.className, status.label);
        header.appendChild(statusBadge);

        var matchup = createElement('div', 'resultado-card__matchup');
        matchup.appendChild(createTeam(homeName, 'home'));
        matchup.appendChild(createScoreControl(match, homeName, awayName, card));
        matchup.appendChild(createTeam(awayName, 'away'));

        var footer = createElement('div', 'resultado-card__footer');
        var metadata = createElement('div', 'resultado-card__metadata');
        metadata.appendChild(createElement('span', 'resultado-card__date', formatDateTime(match.fecha_hora)));
        metadata.appendChild(createElement('span', 'resultado-card__court', match.cancha || 'Sede por definir'));

        var action = createElement('span', 'resultado-card__action', 'Ver eventos');
        action.setAttribute('aria-hidden', 'true');
        var actionIcon = createElement('i', 'fa-solid fa-arrow-up-right-from-square');
        actionIcon.setAttribute('aria-hidden', 'true');
        action.appendChild(actionIcon);

        footer.append(metadata, action);
        card.append(header, matchup, footer);
        card.addEventListener('click', function (event) {
            if (event.target.closest('.resultado-score-toggle')) return;
            openEvents(match, card, homeName, awayName);
        });
        card.addEventListener('keydown', function (event) {
            if (event.target !== card || (event.key !== 'Enter' && event.key !== ' ')) return;
            event.preventDefault();
            openEvents(match, card, homeName, awayName);
        });
        return card;
    }

    function renderMatches(matches) {
        matchList.replaceChildren();
        matchList.setAttribute('aria-busy', 'false');

        if (!matches.length) {
            showEmpty('Este torneo todavía no tiene partidos registrados.');
            setFeedback('No hay partidos para mostrar.', 'empty');
            return;
        }

        emptyState.hidden = true;
        matchList.hidden = false;
        matches.forEach(function (match, index) {
            matchList.appendChild(createMatchCard(match, index));
        });
        setFeedback(matches.length + (matches.length === 1 ? ' partido cargado.' : ' partidos cargados.'), 'success');
    }

    function loadMatches(tournament) {
        if (matchesController) matchesController.abort();

        currentTournament = tournament;
        matchesController = new AbortController();
        var controller = matchesController;
        matchList.replaceChildren();
        matchList.hidden = true;
        matchList.setAttribute('aria-busy', 'true');
        emptyState.hidden = true;
        setFeedback('Cargando partidos de ' + tournament.nombre + '...', 'loading');

        requestData('partidos?torneo_id=' + encodeURIComponent(String(tournament.id)), controller.signal)
            .then(function (data) {
                if (controller.signal.aborted || currentTournament !== tournament) return;
                renderMatches(Array.isArray(data.partidos) ? data.partidos : []);
            })
            .catch(function (error) {
                if (controller.signal.aborted || error.name === 'AbortError') return;
                showEmpty('No se pudieron cargar los partidos.');
                setFeedback('Error al cargar los partidos. ' + error.message, 'error', function () {
                    loadMatches(tournament);
                });
            });
    }

    function loadTournaments() {
        if (tournamentsLoaded || tournamentsLoading) return;

        tournamentsLoading = true;
        tournamentSelect.disabled = true;
        tournamentSelect.replaceChildren(createOption('', 'Cargando torneos...'));
        matchList.hidden = true;
        emptyState.hidden = true;
        matchList.setAttribute('aria-busy', 'true');
        setFeedback('Cargando torneos...', 'loading');

        requestData('torneos')
            .then(function (data) {
                tournaments = Array.isArray(data.torneos)
                    ? data.torneos.filter(function (tournament) {
                        return tournament && Number(tournament.id) > 0 && tournament.nombre;
                    })
                    : [];
                tournamentsLoaded = true;

                if (!tournaments.length) {
                    tournamentSelect.replaceChildren(createOption('', 'Sin torneos disponibles'));
                    showEmpty('Aún no hay torneos disponibles.');
                    setFeedback('No hay torneos disponibles para consultar.', 'empty');
                    return;
                }

                tournamentSelect.replaceChildren(createOption('', 'Selecciona un torneo'));
                tournaments.forEach(function (tournament) {
                    tournamentSelect.appendChild(createOption(String(tournament.id), tournament.nombre));
                });
                tournamentSelect.disabled = false;

                var requestedId = new URLSearchParams(window.location.search).get('torneoId');
                var selected = tournaments.find(function (tournament) {
                    return String(tournament.id) === requestedId;
                }) || tournaments[0];
                tournamentSelect.value = String(selected.id);
                loadMatches(selected);
            })
            .catch(function (error) {
                showEmpty('No se pudieron cargar los torneos.');
                setFeedback('Error al cargar los torneos. ' + error.message, 'error', loadTournaments);
            })
            .finally(function () {
                tournamentsLoading = false;
            });
    }

    function eventPresentation(type) {
        var presentations = {
            gol: { label: 'Gol', className: 'gol', icon: 'fa-futbol' },
            autogol: { label: 'Autogol', className: 'autogol', icon: 'fa-arrow-rotate-left' },
            penal_anotado: { label: 'Penal anotado', className: 'penal', icon: 'fa-bullseye' },
            tarjeta_amarilla: { label: 'Tarjeta amarilla', className: 'tarjeta-amarilla', icon: 'fa-square' },
            tarjeta_roja: { label: 'Tarjeta roja', className: 'tarjeta-roja', icon: 'fa-square-xmark' }
        };
        return presentations[type] || {
            label: String(type || 'Evento').replace(/_/g, ' '),
            className: 'otro',
            icon: 'fa-circle-info'
        };
    }

    function resolveAvatarUrl(value, fallback) {
        var fallbackUrl = new URL(fallback, document.baseURI).href;
        if (typeof value !== 'string' || !value.trim()) return fallbackUrl;

        try {
            var url = new URL(value.trim(), document.baseURI);
            return url.protocol === 'http:' || url.protocol === 'https:'
                ? url.href
                : fallbackUrl;
        } catch (error) {
            return fallbackUrl;
        }
    }

    function createPlayerAvatar(event) {
        var fallbackUrl = eventsList.dataset.defaultAvatar || 'assets/img/avatar-placeholder.svg';
        var hasAvatar = typeof event.jugador_avatar === 'string' && event.jugador_avatar.trim() !== '';
        var avatar = document.createElement('img');
        avatar.className = 'resultado-event__avatar';
        avatar.src = resolveAvatarUrl(event.jugador_avatar, fallbackUrl);
        avatar.alt = '';
        avatar.loading = 'lazy';
        avatar.decoding = 'async';
        avatar.referrerPolicy = 'no-referrer';
        avatar.dataset.fallbackApplied = String(!hasAvatar);

        avatar.addEventListener('error', function () {
            if (avatar.dataset.placeholderApplied === 'true') return;

            if (avatar.dataset.fallbackApplied !== 'true') {
                avatar.dataset.fallbackApplied = 'true';
                avatar.src = resolveAvatarUrl('', fallbackUrl);
                return;
            }

            avatar.dataset.placeholderApplied = 'true';
            var placeholder = createElement('span', 'resultado-event__avatar-fallback');
            placeholder.setAttribute('aria-hidden', 'true');
            var icon = createElement('i', 'fa-solid fa-user');
            icon.setAttribute('aria-hidden', 'true');
            placeholder.appendChild(icon);
            avatar.replaceWith(placeholder);
        });

        return avatar;
    }

    function renderEvents(events, match) {
        eventsList.replaceChildren();

        if (!events.length) {
            eventsEmpty.textContent = 'Este partido todavía no tiene eventos registrados.';
            eventsEmpty.hidden = false;
            setEventsFeedback('Sin eventos registrados.', 'empty');
            return;
        }

        eventsEmpty.hidden = true;
        events.forEach(function (event) {
            var item = document.createElement('li');
            var presentation = eventPresentation(event.tipo);
            item.className = 'resultado-event resultado-event--' + presentation.className;

            var minute = event.minuto === null || event.minuto === undefined
                ? '—'
                : String(event.minuto) + "'";
            item.appendChild(createElement('span', 'resultado-event__minute', minute));

            var iconBox = createElement('span', 'resultado-event__icon');
            iconBox.setAttribute('aria-hidden', 'true');
            iconBox.appendChild(createElement('i', 'fa-solid ' + presentation.icon));
            item.appendChild(iconBox);
            item.appendChild(createPlayerAvatar(event));

            var detail = createElement('div', 'resultado-event__detail');
            detail.appendChild(createElement('strong', 'resultado-event__type', presentation.label));

            var playerName = event.jugador_nombre || (event.matricula ? 'Jugador ' + event.matricula : 'Jugador');
            detail.appendChild(createElement('span', 'resultado-event__player-name', playerName));

            var metadata = createElement('div', 'resultado-event__metadata');
            if (event.matricula) {
                metadata.appendChild(createElement('span', 'resultado-event__registration', 'Matrícula ' + event.matricula));
            }

            var teamName = event.equipo_nombre || 'Equipo por definir';
            metadata.appendChild(createElement('span', 'resultado-event__team', teamName));
            detail.appendChild(metadata);

            if (event.asistencia_matricula) {
                detail.appendChild(createElement(
                    'span',
                    'resultado-event__assist',
                    'Asistencia · ' + (event.asistencia_nombre || 'Matrícula ' + event.asistencia_matricula)
                ));
            }

            item.appendChild(detail);
            eventsList.appendChild(item);
        });

        setEventsFeedback(events.length + (events.length === 1 ? ' evento registrado.' : ' eventos registrados.'), 'success');
    }

    function loadEvents(match, homeName, awayName) {
        if (eventsController) eventsController.abort();

        eventsController = new AbortController();
        var controller = eventsController;
        eventsList.replaceChildren();
        eventsEmpty.hidden = true;
        setEventsFeedback('Cargando eventos...', 'loading');

        requestData('partidos/' + encodeURIComponent(String(match.id)) + '/eventos', controller.signal)
            .then(function (data) {
                if (controller.signal.aborted || currentEventsMatch !== match) return;
                renderEvents(Array.isArray(data.eventos) ? data.eventos : [], match);
            })
            .catch(function (error) {
                if (controller.signal.aborted || error.name === 'AbortError') return;
                eventsEmpty.textContent = 'No se pudieron cargar los eventos.';
                eventsEmpty.hidden = false;
                setEventsFeedback('Error al cargar eventos. ' + error.message, 'error', function () {
                    loadEvents(match, homeName, awayName);
                });
            });
    }

    function openEvents(match, trigger, homeName, awayName) {
        currentEventsMatch = match;
        eventsTrigger = trigger;
        eventsTitle.textContent = 'Eventos del partido';
        eventsMatchup.textContent = homeName + ' vs. ' + awayName;
        eventsList.replaceChildren();
        eventsEmpty.hidden = true;
        eventsDialog.showModal();
        loadEvents(match, homeName, awayName);
    }

    retryButton.addEventListener('click', function () {
        if (retryMatchesAction) retryMatchesAction();
    });

    eventsRetryButton.addEventListener('click', function () {
        if (retryEventsAction && currentEventsMatch) retryEventsAction();
    });

    tournamentSelect.addEventListener('change', function () {
        var selected = tournaments.find(function (tournament) {
            return String(tournament.id) === tournamentSelect.value;
        });
        if (selected) loadMatches(selected);
    });

    closeEventsButton.addEventListener('click', function () {
        eventsDialog.close();
    });

    eventsDialog.addEventListener('click', function (event) {
        if (event.target === eventsDialog) eventsDialog.close();
    });

    eventsDialog.addEventListener('close', function () {
        if (eventsController) eventsController.abort();
        eventsController = null;
        currentEventsMatch = null;
        if (eventsTrigger && eventsTrigger.isConnected) eventsTrigger.focus();
        eventsTrigger = null;
    });

    document.querySelectorAll('.nav-tab[data-target="view-resultados"]').forEach(function (tab) {
        tab.addEventListener('click', loadTournaments);
    });

    if (view.classList.contains('active')) loadTournaments();
});