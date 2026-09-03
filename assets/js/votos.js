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
       STORAGE
       ===================================================== */

    var STORAGE_KEY = 'passballVotes';

    var votes = {};

    try {

        votes = JSON.parse(
            localStorage.getItem(STORAGE_KEY) || '{}'
        );

    } catch (e) {

        votes = {};

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

    var myVotesButton =
        document.getElementById('myVotesButton');


    /* =====================================================
       CONTADOR DE VOTOS
       ===================================================== */

    function updateVotesMade() {

        var count = Object.keys(votes).length;

        if (votesMade) {

            votesMade.textContent = String(count);

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

                var categoryId =
                    this.getAttribute(
                        'data-category'
                    );


                var candidateId =
                    this.getAttribute(
                        'data-candidate'
                    );


                if (
                    !categoryId ||
                    !candidateId
                ) {
                    return;
                }


                /* -----------------------------------------
                   YA VOTÓ
                   ----------------------------------------- */

                if (votes[categoryId]) {

                    alert(
                        'Ya realizaste tu voto en esta categoría.'
                    );

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


                /* -----------------------------------------
                   CONFIRMACIÓN
                   ----------------------------------------- */

                var confirmMessage =
                    '¿Confirmar tu voto por "' +
                    candidateName +
                    '" en esta categoría?';


                if (!confirm(confirmMessage)) {

                    return;

                }


                /* -----------------------------------------
                   GUARDAR
                   ----------------------------------------- */

                votes[categoryId] =
                    candidateId;


                try {

                    localStorage.setItem(
                        STORAGE_KEY,
                        JSON.stringify(votes)
                    );

                } catch (e) {

                    console.warn(
                        'No fue posible guardar el voto.'
                    );

                }


                /* -----------------------------------------
                   ACTUALIZAR INTERFAZ
                   ----------------------------------------- */

                markAsVoted(
                    categoryId,
                    candidateId
                );


                updateVotesMade();


                alert(
                    '✓ Voto registrado correctamente'
                );

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


    /* =====================================================
       RESTAURAR VOTOS
       ===================================================== */

    function restoreVotes() {

        Object.keys(votes).forEach(
            function (categoryId) {

                var candidateId =
                    votes[categoryId];


                var button =
                    scope.querySelector(
                        '.btn-vote[data-category="' +
                        categoryId +
                        '"][data-candidate="' +
                        String(candidateId) +
                        '"]'
                    );


                if (button) {

                    markAsVoted(
                        categoryId,
                        candidateId
                    );

                }

            }
        );

    }


    /* =====================================================
       VER MIS VOTOS
       ===================================================== */

    if (myVotesButton) {

        myVotesButton.addEventListener(
            'click',
            function () {

                var names = [];


                Object.keys(votes).forEach(
                    function (categoryId) {

                        var candidateId =
                            votes[categoryId];


                        var button =
                            scope.querySelector(
                                '.btn-vote[data-category="' +
                                categoryId +
                                '"][data-candidate="' +
                                String(candidateId) +
                                '"]'
                            );


                        if (!button) {
                            return;
                        }


                        var candidate =
                            button.closest(
                                '.candidate'
                            );


                        var name =
                            candidate
                                .querySelector(
                                    '.candidate-info strong'
                                )
                                .textContent
                                .trim();


                        names.push(name);

                    }
                );


                if (names.length === 0) {

                    alert(
                        'Aún no has realizado votos.'
                    );

                    return;

                }


                alert(
                    'Tus votos:\n\n• ' +
                    names.join('\n• ')
                );

            }
        );

    }


    /* =====================================================
       INICIALIZAR
       ===================================================== */

    restoreVotes();

    updateVotesMade();

});