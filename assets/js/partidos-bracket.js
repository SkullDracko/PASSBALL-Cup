document.addEventListener('DOMContentLoaded', function () {
    var view = document.getElementById('view-partidos');

    if (!view) return;

    var kicker = document.getElementById('partidos-kicker');
    var title = document.getElementById('partidos-title');
    var picker = document.getElementById('bracket-tournament-picker');
    var tournamentSelect = document.getElementById('bracket-tournament');
    var feedback = document.getElementById('bracket-feedback');
    var retryButton = document.getElementById('bracket-retry');
    var emptyState = document.getElementById('bracket-empty');
    var scrollRegion = document.getElementById('bracket-scroll');
    var board = document.getElementById('bracket-board');
    var connectors = document.getElementById('bracket-connectors');
    var subtitle = view.querySelector('.partidos-subtitle');

    if (
        !picker || !tournamentSelect || !feedback || !retryButton ||
        !emptyState || !scrollRegion || !board || !connectors || !title
    ) return;

    var tournaments = [];
    var currentTournament = null;
    var bracketController = null;
    var retryAction = null;
    var tournamentsLoaded = false;
    var tournamentsLoading = false;
    var renderedMatches = new Map();
    var activeConnections = [];

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
            throw new Error(message || 'No se pudo cargar la información del torneo.');
        }

        return payload.data || {};
    }

    function setFeedback(message, state, retry) {
        feedback.textContent = message;
        feedback.dataset.state = state || 'info';
        feedback.setAttribute('role', state === 'error' ? 'alert' : 'status');
        retryAction = typeof retry === 'function' ? retry : null;
        retryButton.hidden = !retryAction;
    }

    function setEmptyState(message) {
        emptyState.textContent = message;
        emptyState.hidden = false;
        scrollRegion.hidden = true;
    }

    function clearBoard() {
        renderedMatches.clear();
        activeConnections = [];
        connectors.replaceChildren();
        board.replaceChildren(connectors);
    }

    function showError(message, retry) {
        clearBoard();
        setEmptyState(message);
        setFeedback(message, 'error', retry);
        if (!currentTournament) {
            title.textContent = 'Partidos';
            kicker.textContent = 'Información no disponible';
        }
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

    function normalizarTexto(value) {
        return String(value || '').trim().toLocaleLowerCase('es');
    }

    function classForRound(round, index, totalRounds, totalMatches) {
        var name = normalizarTexto(round.nombre);

        if (name.indexOf('cuarto') !== -1 || name.indexOf('quarter') !== -1) {
            return 'bracket-round--quarterfinal';
        }
        if (name.indexOf('semi') !== -1) {
            return 'bracket-round--semifinal';
        }
        if (/^final(?:$|\s)/.test(name) || name === 'final') {
            return 'bracket-round--final';
        }
        if (totalRounds === 3 && index === 0 && totalMatches === 4) {
            return 'bracket-round--quarterfinal';
        }
        if (totalRounds === 3 && index === 1 && totalMatches === 2) {
            return 'bracket-round--semifinal';
        }
        if (totalRounds === 3 && index === 2 && totalMatches === 1) {
            return 'bracket-round--final';
        }
        return 'bracket-round--other';
    }

    function formatTournamentState(state) {
        var labels = {
            programado: 'Torneo programado',
            en_curso: 'Torneo en curso',
            finalizado: 'Torneo finalizado',
            cancelado: 'Torneo cancelado'
        };
        return labels[state] || 'Torneo seleccionado';
    }

    function sourceDescription(origin, sourceMatches) {
        if (!origin || origin.partido_id === null || origin.partido_id === undefined) {
            return 'Equipo por definir';
        }

        var source = sourceMatches.get(String(origin.partido_id));
        if (!source) return 'Ganador pendiente';

        var sourceRound = source.round.nombre || 'ronda anterior';
        var sourcePosition = source.match.posicion || 'sin posición';
        return 'Ganador de ' + sourceRound + ', partido ' + sourcePosition;
    }

    function addTeamRow(list, team, scoreValue, origin, winner, champion, sourceMatches) {
        var row = createElement('li', 'bracket-team');
        var identity = createElement('span', 'bracket-team__identity');
        var name = createElement('span', 'bracket-team__name');
        var score = createElement('span', 'bracket-team__score');
        var outcome = createElement('span', 'bracket-team__outcome');
        var teamName = team && typeof team.nombre === 'string' ? team.nombre.trim() : '';
        var numericScore = scoreValue === null || scoreValue === undefined || scoreValue === ''
            ? null
            : Number(scoreValue);

        name.textContent = teamName || 'Por definir';
        identity.appendChild(name);

        if (!teamName) {
            identity.appendChild(createElement('span', 'bracket-team__source', sourceDescription(origin, sourceMatches)));
        }

        score.textContent = numericScore !== null && Number.isFinite(numericScore)
            ? String(numericScore)
            : '–';
        score.setAttribute(
            'aria-label',
            numericScore !== null && Number.isFinite(numericScore)
                ? numericScore + (numericScore === 1 ? ' gol' : ' goles')
                : 'Marcador pendiente'
        );

        if (winner) {
            row.classList.add('is-winner');
            if (champion) row.classList.add('is-champion');
            outcome.textContent = champion ? 'Campeón' : 'Avanza';
        } else {
            outcome.setAttribute('aria-hidden', 'true');
        }

        row.append(identity, score, outcome);
        list.appendChild(row);
    }

    function addPenalties(header, match) {
        if (match.penales_local === null || match.penales_local === undefined ||
            match.penales_visitante === null || match.penales_visitante === undefined) return;

        var penalties = createElement(
            'span',
            'bracket-match__penalties',
            'PEN ' + match.penales_local + '–' + match.penales_visitante
        );
        penalties.setAttribute(
            'aria-label',
            'Definición por penales: ' + match.penales_local + ' a ' + match.penales_visitante
        );
        header.appendChild(penalties);
    }

    function buildConnections() {
        connectors.replaceChildren();

        var width = Math.max(board.scrollWidth, board.clientWidth, 1);
        var height = Math.max(board.scrollHeight, board.clientHeight, 1);
        var boardRect = board.getBoundingClientRect();
        connectors.setAttribute('viewBox', '0 0 ' + width + ' ' + height);

        activeConnections.forEach(function (connection) {
            var source = renderedMatches.get(String(connection.sourceId));
            var target = renderedMatches.get(String(connection.targetId));
            if (!source || !target) return;

            var sourceRect = source.getBoundingClientRect();
            var targetRect = target.getBoundingClientRect();
            var startX = sourceRect.right - boardRect.left;
            var startY = sourceRect.top + sourceRect.height / 2 - boardRect.top;
            var endX = targetRect.left - boardRect.left;
            var endY = targetRect.top + targetRect.height / 2 - boardRect.top;
            var junctionX = startX + (endX - startX) / 2;
            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

            path.setAttribute(
                'd',
                'M' + startX + ' ' + startY + 'H' + junctionX + 'V' + endY + 'H' + endX
            );
            connectors.appendChild(path);
        });
    }

    function renderBracket(rounds, tournament) {
        var validRounds = rounds.filter(function (round) {
            return round && typeof round === 'object';
        }).sort(function (first, second) {
            return Number(first.orden || 0) - Number(second.orden || 0);
        });
        var sourceMatches = new Map();
        var allMatches = [];

        validRounds.forEach(function (round) {
            var matches = Array.isArray(round.partidos) ? round.partidos.slice() : [];
            matches.sort(function (first, second) {
                return Number(first.posicion || 0) - Number(second.posicion || 0);
            });
            matches.forEach(function (match) {
                allMatches.push({ round: round, match: match });
                if (match.id !== null && match.id !== undefined) {
                    sourceMatches.set(String(match.id), { round: round, match: match });
                }
            });
        });

        if (!validRounds.length || !allMatches.length) {
            clearBoard();
            setEmptyState('Este torneo todavía no tiene rondas o partidos para mostrar.');
            setFeedback('No hay partidos registrados para este torneo.', 'empty');
            return;
        }

        emptyState.hidden = true;
        scrollRegion.hidden = false;
        title.textContent = tournament.nombre || 'Torneo';
        kicker.textContent = formatTournamentState(tournament.estado);
        scrollRegion.setAttribute(
            'aria-label',
            'Cuadro de eliminación directa de ' + (tournament.nombre || 'este torneo') + ': ' +
            validRounds.map(function (round) { return round.nombre || 'Ronda'; }).join(', ')
        );
        if (subtitle) subtitle.textContent = 'Cuadro de eliminación directa';
        clearBoard();
        board.style.setProperty('--bracket-round-count', String(validRounds.length));

        var championRoundIndex = tournament.estado === 'finalizado' ? validRounds.length - 1 : -1;

        validRounds.forEach(function (round, roundIndex) {
            var matches = Array.isArray(round.partidos) ? round.partidos.slice() : [];
            matches.sort(function (first, second) {
                return Number(first.posicion || 0) - Number(second.posicion || 0);
            });
            var roundClass = classForRound(round, roundIndex, validRounds.length, matches.length);
            var roundSection = createElement('section', 'bracket-round ' + roundClass);
            var roundTitleId = 'api-bracket-round-' + roundIndex;
            roundSection.setAttribute('aria-labelledby', roundTitleId);

            var roundHeader = createElement('header', 'bracket-round__header');
            var roundTitle = createElement('h2', '', round.nombre || 'Ronda');
            roundTitle.id = roundTitleId;
            roundHeader.appendChild(roundTitle);
            roundHeader.appendChild(createElement(
                'span',
                'bracket-round__count',
                matches.length + (matches.length === 1 ? ' partido' : ' partidos')
            ));
            roundSection.appendChild(roundHeader);

            if (!matches.length) {
                roundSection.appendChild(createElement(
                    'p',
                    'bracket-empty-round',
                    'Partidos aún no programados.'
                ));
            } else {
                var matchList = createElement('ol', 'bracket-round__matches');

                matches.forEach(function (match, matchIndex) {
                    var slot = document.createElement('li');
                    var position = Number(match.posicion) || matchIndex + 1;
                    var matchId = 'api-bracket-match-' + roundIndex + '-' + matchIndex;
                    var matchCard = createElement('article', 'bracket-match');
                    matchCard.dataset.position = String(position);
                    if (roundClass === 'bracket-round--final') {
                        matchCard.classList.add('bracket-match--final');
                    }
                    if (match.id !== null && match.id !== undefined) {
                        matchCard.dataset.matchId = String(match.id);
                    }
                    matchCard.setAttribute('aria-labelledby', matchId);

                    var matchHeader = createElement('div', 'bracket-match__header');
                    var matchTitle = createElement('h3', 'bracket-match__title', 'Partido ' + position);
                    matchTitle.id = matchId;
                    matchHeader.appendChild(matchTitle);
                    addPenalties(matchHeader, match);
                    matchCard.appendChild(matchHeader);

                    var teams = createElement('ol', 'bracket-match__teams');
                    teams.setAttribute('aria-label', 'Equipos y resultado');
                    var local = match.equipo_local || null;
                    var visitor = match.equipo_visitante || null;
                    var localWinner = Boolean(local && match.ganador_id !== null &&
                        match.ganador_id !== undefined && String(match.ganador_id) === String(local.id));
                    var visitorWinner = Boolean(visitor && match.ganador_id !== null &&
                        match.ganador_id !== undefined && String(match.ganador_id) === String(visitor.id));
                    var isFinal = roundIndex === championRoundIndex;

                    addTeamRow(
                        teams,
                        local,
                        match.goles_local,
                        match.origen_local,
                        localWinner,
                        localWinner && isFinal,
                        sourceMatches
                    );
                    addTeamRow(
                        teams,
                        visitor,
                        match.goles_visitante,
                        match.origen_visitante,
                        visitorWinner,
                        visitorWinner && isFinal,
                        sourceMatches
                    );
                    matchCard.appendChild(teams);
                    slot.appendChild(matchCard);
                    matchList.appendChild(slot);

                    if (match.id !== null && match.id !== undefined) {
                        renderedMatches.set(String(match.id), matchCard);
                    }

                    [match.origen_local, match.origen_visitante].forEach(function (origin) {
                        if (origin && origin.partido_id !== null && origin.partido_id !== undefined &&
                            match.id !== null && match.id !== undefined) {
                            activeConnections.push({
                                sourceId: origin.partido_id,
                                targetId: match.id
                            });
                        }
                    });
                });

                roundSection.appendChild(matchList);
            }

            board.appendChild(roundSection);
        });

        setFeedback(
            allMatches.length + (allMatches.length === 1 ? ' partido cargado.' : ' partidos cargados.'),
            'success'
        );
        window.requestAnimationFrame(buildConnections);
    }

    function loadBracket(tournament) {
        if (bracketController) bracketController.abort();

        currentTournament = tournament;
        bracketController = new AbortController();
        var controller = bracketController;
        var tournamentId = encodeURIComponent(String(tournament.id));

        emptyState.hidden = true;
        scrollRegion.hidden = true;
        clearBoard();
        setFeedback('Cargando el cuadro de ' + (tournament.nombre || 'este torneo') + '...', 'loading');

        requestData('torneos/' + tournamentId + '/bracket', controller.signal)
            .then(function (data) {
                if (controller.signal.aborted || currentTournament !== tournament) return;
                renderBracket(Array.isArray(data.rondas) ? data.rondas : [], tournament);
            })
            .catch(function (error) {
                if (controller.signal.aborted || error.name === 'AbortError') return;
                showError(
                    'No se pudo cargar el cuadro. ' + error.message,
                    function () { loadBracket(tournament); }
                );
            });
    }

    function loadTournaments() {
        if (tournamentsLoaded || tournamentsLoading) return;

        tournamentsLoading = true;
        picker.hidden = false;
        tournamentSelect.disabled = true;
        tournamentSelect.replaceChildren(createOption('', 'Cargando torneos...'));
        title.textContent = 'Partidos';
        kicker.textContent = 'Cargando torneos';
        emptyState.hidden = true;
        scrollRegion.hidden = true;
        clearBoard();
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
                    title.textContent = 'Partidos';
                    kicker.textContent = 'Sin torneos disponibles';
                    showEmptyState('Aún no hay torneos disponibles.');
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
                loadBracket(selected);
            })
            .catch(function (error) {
                showError(
                    'No se pudieron cargar los torneos. ' + error.message,
                    loadTournaments
                );
            })
            .finally(function () {
                tournamentsLoading = false;
            });
    }

    function showEmptyState(message) {
        clearBoard();
        setEmptyState(message);
    }

    retryButton.addEventListener('click', function () {
        if (retryAction) retryAction();
    });

    tournamentSelect.addEventListener('change', function () {
        var selected = tournaments.find(function (tournament) {
            return String(tournament.id) === tournamentSelect.value;
        });
        if (selected) loadBracket(selected);
    });

    document.querySelectorAll('.nav-tab[data-target="view-partidos"]').forEach(function (tab) {
        tab.addEventListener('click', loadTournaments);
    });

    if (view.classList.contains('active')) loadTournaments();

    window.addEventListener('resize', function () {
        if (renderedMatches.size) window.requestAnimationFrame(buildConnections);
    });
});