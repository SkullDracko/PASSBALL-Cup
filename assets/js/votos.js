/**
 * =========================================================
 * PASSBALL Cup - Votos
 * =========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    /* =====================================================
       CONTENEDOR
       ===================================================== */

    var scope = document.getElementById('view-votos');

    if (!scope) {
        return;
    }


    /* =====================================================
       ELEMENTOS
       ===================================================== */

    var categoryTabs =
        scope.querySelectorAll('.category-tab');

    var categoryCards =
        scope.querySelectorAll('.vote-category-card');

    var globalSearch =
        document.getElementById('voteSearch');

    var votesMade =
        document.getElementById('votesMade');


    /* =====================================================
       CONTADOR DE VOTOS
       ===================================================== */

    function updateVotesMade(count) {

        if (votesMade) {

            votesMade.textContent =
                String(count || '0');

        }

    }


    /* =====================================================
       FILTRAR CATEGORÍAS
       ===================================================== */

    function filterCards(categoryId) {

        categoryCards.forEach(function (card) {

            var cardCategory =
                card.getAttribute('data-category-card');

            var show =
                categoryId === 'all' ||
                cardCategory === categoryId;

            card.classList.toggle(
                'hidden',
                !show
            );

        });

        applyGlobalSearch();

    }


    /* =====================================================
       BUSCADOR GLOBAL
       ===================================================== */

    function applyGlobalSearch() {

        var globalTerm =
            globalSearch
                ? globalSearch.value
                    .toLowerCase()
                    .trim()
                : '';


        categoryCards.forEach(function (card) {

            var input =
                card.querySelector(
                    '.candidate-input'
                );

            var localTerm =
                input
                    ? input.value
                        .toLowerCase()
                        .trim()
                    : '';


            filterCandidates(
                card,
                globalTerm,
                localTerm
            );

        });

    }


    /* =====================================================
       FILTRAR CANDIDATOS
       ===================================================== */

    function filterCandidates(
        card,
        globalTerm,
        localTerm
    ) {

        var candidates =
            card.querySelectorAll('.candidate');


        candidates.forEach(function (candidate) {

            var name =
                (
                    candidate.getAttribute(
                        'data-name'
                    ) || ''
                ).toLowerCase();


            var matchesGlobal =
                !globalTerm ||
                name.indexOf(globalTerm) !== -1;


            var matchesLocal =
                !localTerm ||
                name.indexOf(localTerm) !== -1;


            var visible =
                matchesGlobal &&
                matchesLocal;


            candidate.classList.toggle(
                'hidden',
                !visible
            );

        });

    }


    /* =====================================================
       TABS
       ===================================================== */

    categoryTabs.forEach(function (tab) {

        tab.addEventListener(
            'click',
            function () {

                categoryTabs.forEach(
                    function (item) {

                        item.classList.remove(
                            'active'
                        );

                    }
                );


                this.classList.add('active');


                var categoryId =
                    this.getAttribute(
                        'data-category'
                    );


                filterCards(categoryId);

            }
        );

    });


    /* =====================================================
       BUSCADOR GLOBAL
       ===================================================== */

    if (globalSearch) {

        globalSearch.addEventListener(
            'input',
            function () {

                applyGlobalSearch();

            }
        );

    }


    /* =====================================================
       BUSCADORES INDIVIDUALES
       ===================================================== */

    var cardInputs =
        scope.querySelectorAll(
            '.candidate-input'
        );


    cardInputs.forEach(function (input) {

        input.addEventListener(
            'input',
            function () {

                var card =
                    this.closest(
                        '.vote-category-card'
                    );


                if (!card) {
                    return;
                }


                var globalTerm =
                    globalSearch
                        ? globalSearch.value
                            .toLowerCase()
                            .trim()
                        : '';


                var localTerm =
                    this.value
                        .toLowerCase()
                        .trim();


                filterCandidates(
                    card,
                    globalTerm,
                    localTerm
                );

            }
        );

    });


    /* =====================================================
       BOTONES VOTAR
       ===================================================== */

    var voteButtons =
        scope.querySelectorAll(
            '.btn-vote'
        );


    voteButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                if (this.disabled) {
                    return;
                }

                var categoryId =
                    this.getAttribute(
                        'data-category'
                    );


                var candidateId =
                    this.getAttribute(
                        'data-candidate'
                    );


                if (!categoryId || !candidateId) {
                    return;
                }


                /* -----------------------------------------
                   NOMBRE DEL CANDIDATO
                   ----------------------------------------- */

                var candidate =
                    this.closest('.candidate');


                var candidateName =
                    candidate
                        .querySelector(
                            '.candidate-info strong'
                        )
                        .textContent
                        .trim();


                if (!confirm(
                    '¿Confirmar tu voto por "' +
                    candidateName +
                    '" en esta categoría?'
                )) {

                    return;

                }


                /* -----------------------------------------
                   ENVIAR AL SERVIDOR
                   ----------------------------------------- */

                var catCard =
                    this.closest(
                        '.vote-category-card'
                    );

                var esJugador =
                    (catCard.getAttribute(
                        'data-tipo'
                    ) || 'jugador') === 'jugador';


                var body =
                    'categoria_id=' +
                    encodeURIComponent(categoryId) +
                    '&' + (esJugador
                        ? 'jugador_id'
                        : 'equipo_id') +
                    '=' + encodeURIComponent(candidateId);


                this.disabled = true;


                fetch(
                    'controllers/votar.php',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded'
                        },
                        body: body
                    }
                )
                    .then(function (resp) {
                        return resp.json();
                    })
                    .then(function (data) {

                        if (!data.success) {

                            alert(
                                data.message ||
                                'No se pudo registrar tu voto.'
                            );

                            return;

                        }


                        markAsVoted(
                            categoryId,
                            candidateId
                        );


                        updateVotesMade(
                            data.total_votos
                        );


                        alert(
                            '✓ Voto registrado correctamente'
                        );

                    })
                    .catch(function () {

                        alert(
                            'Error de conexión. Intenta de nuevo.'
                        );

                    });

            }
        );

    });


    /* =====================================================
       MARCAR VOTO
       ===================================================== */

    function markAsVoted(
        categoryId,
        candidateId
    ) {

        categoryCards.forEach(function (card) {

            var cardCategory =
                card.getAttribute(
                    'data-category-card'
                );


            if (
                cardCategory !== categoryId
            ) {
                return;
            }


            var candidates =
                card.querySelectorAll(
                    '.candidate'
                );


            candidates.forEach(
                function (candidate) {

                    var button =
                        candidate.querySelector(
                            '.btn-vote'
                        );


                    if (!button) {
                        return;
                    }


                    var candidateButtonId =
                        button.getAttribute(
                            'data-candidate'
                        );


                    var isSelected =
                        candidateButtonId ===
                        String(candidateId);


                    if (isSelected) {

                        candidate.classList.add(
                            'voted'
                        );


                        button.textContent =
                            '✓ Votado';


                        button.disabled =
                            true;


                    } else {

                        button.disabled =
                            true;


                        button.style.opacity =
                            '0.45';


                        button.style.cursor =
                            'not-allowed';

                    }

                }
            );

        });

    }

});