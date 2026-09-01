/**
 * =========================================================
 * PASSBALL Cup - Votos
 * Lógica de la vista de votaciones dentro del dashboard.
 * =========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    var scope = document.getElementById('view-votos');

    if (!scope) {
        return;
    }

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

    var categoryTabs = scope.querySelectorAll('.category-tab');

    var categoryCards = scope.querySelectorAll('.vote-category-card');

    var globalSearch = document.getElementById('voteSearch');

    var votesMade = document.getElementById('votesMade');

    var myVotesButton = document.getElementById('myVotesButton');


    /* =====================================================
       CONTADOR DE VOTOS REALIZADOS
    ===================================================== */

    function updateVotesMade() {

        var count = Object.keys(votes).length;

        if (votesMade) {
            votesMade.textContent = String(count);
        }

    }


    /* =====================================================
       FILTRAR TARJETAS POR CATEGORÍA
       (data-category="all" muestra todas)
    ===================================================== */

    function filterCards(categoryId) {

        categoryCards.forEach(function (card) {

            var cardCategory =
                card.getAttribute('data-category-card');

            var show =
                categoryId === 'all' ||
                cardCategory === categoryId;

            card.classList.toggle('hidden', !show);

        });

        /* Aplica también el buscador global sobre las visibles */

        applyGlobalSearch();

    }


    /* =====================================================
       BUSCADOR GLOBAL
       Filtra los candidatos de todas las tarjetas.
    ===================================================== */

    function applyGlobalSearch() {

        var term = globalSearch
            ? globalSearch.value.toLowerCase().trim()
            : '';

        categoryCards.forEach(function (card) {

            var input =
                card.querySelector('.candidate-input');

            var candidates =
                card.querySelectorAll('.candidate');

            if (!input) {
                return;
            }

            /* Si hay término global, combina con el local */

            var localTerm =
                input.value.toLowerCase().trim();

            filterCandidates(card, term, localTerm);

        });

    }


    /* =====================================================
       FILTRAR CANDIDATOS DE UNA TARJETA
       Combina el término global y el local.
    ===================================================== */

    function filterCandidates(card, globalTerm, localTerm) {

        var candidates =
            card.querySelectorAll('.candidate');

        candidates.forEach(function (candidate) {

            var name =
                candidate.getAttribute('data-name') || '';

            var matches =
                (!globalTerm || name.indexOf(globalTerm) !== -1) &&
                (!localTerm || name.indexOf(localTerm) !== -1);

            candidate.classList.toggle('hidden', !matches);

        });

    }


    /* =====================================================
       TABS DE CATEGORÍAS
    ===================================================== */

    categoryTabs.forEach(function (tab) {

        tab.addEventListener('click', function () {

            categoryTabs.forEach(function (t) {
                t.classList.remove('active');
            });

            this.classList.add('active');

            var categoryId =
                this.getAttribute('data-category');

            filterCards(categoryId);

        });

    });


    /* =====================================================
       BUSCADOR GLOBAL
    ===================================================== */

    if (globalSearch) {

        globalSearch.addEventListener('input', function () {

            applyGlobalSearch();

        });

    }


    /* =====================================================
       BUSCADOR POR TARJETA
    ===================================================== */

    var cardInputs =
        scope.querySelectorAll('.candidate-input');

    cardInputs.forEach(function (input) {

        input.addEventListener('input', function () {

            var card = this.closest('.vote-category-card');

            if (!card) {
                return;
            }

            var term = globalSearch
                ? globalSearch.value.toLowerCase().trim()
                : '';

            var localTerm = this.value.toLowerCase().trim();

            filterCandidates(card, term, localTerm);

        });

    });


    /* =====================================================
       BOTONES DE VOTAR
    ===================================================== */

    var voteButtons = scope.querySelectorAll('.btn-vote');

    voteButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            var categoryId =
                this.getAttribute('data-category');

            var candidateId =
                this.getAttribute('data-candidate');

            if (!categoryId || !candidateId) {
                return;
            }

            if (votes[categoryId]) {

                alert('Ya realizaste tu voto en esta categoría.');
                return;

            }

            var candidateName =
                this.closest('.candidate')
                    .querySelector('.candidate-info strong')
                    .textContent;

            var confirmMessage =
                '¿Confirmar tu voto por "' +
                candidateName.trim() +
                '" en esta categoría?';

            if (!confirm(confirmMessage)) {
                return;
            }

            votes[categoryId] = candidateId;

            try {
                localStorage.setItem(
                    STORAGE_KEY,
                    JSON.stringify(votes)
                );
            } catch (e) {
                /* almacenamiento no disponible */
            }

            markAsVoted(categoryId, candidateId);

            updateVotesMade();

            alert('✓ Voto registrado correctamente');

        });

    });


    /* =====================================================
       MARCAR COMO VOTADO
       Aplica el estado votado a los candidatos de la
       categoría y deshabilita el resto.
    ===================================================== */

    function markAsVoted(categoryId, candidateId) {

        categoryCards.forEach(function (card) {

            var cardCategory =
                card.getAttribute('data-category-card');

            if (cardCategory !== categoryId) {
                return;
            }

            card.querySelectorAll('.voted')
                .forEach(function (voted) {
                    voted.classList.remove('voted');
                });

            var candidates =
                card.querySelectorAll('.candidate');

            candidates.forEach(function (candidate) {

                var btn =
                    candidate.querySelector('.btn-vote');

                if (!btn) {
                    return;
                }

                var isSelected =
                    btn.getAttribute('data-candidate') ===
                    String(candidateId);

                if (isSelected) {

                    candidate.classList.add('voted');

                    btn.textContent = '✓ Votado';

                } else {

                    btn.disabled = true;

                    btn.style.opacity = '0.45';

                    btn.style.cursor = 'not-allowed';

                }

            });

        });

    }


    /* =====================================================
       RESTAURAR ESTADO DE VOTOS GUARDADOS
       Al cargar, marca los votos ya realizados.
    ===================================================== */

    function restoreVotes() {

        Object.keys(votes).forEach(function (categoryId) {

            var candidateId = votes[categoryId];

            var btn = scope.querySelector(
                '.btn-vote[data-category="' +
                categoryId +
                '"][data-candidate="' +
                String(candidateId) +
                '"]'
            );

            if (btn) {

                markAsVoted(categoryId, candidateId);

            }

        });

    }


    /* =====================================================
       VER MIS VOTOS
    ===================================================== */

    if (myVotesButton) {

        myVotesButton.addEventListener('click', function () {

            var names = [];

            Object.keys(votes).forEach(function (categoryId) {

                var candidateId = votes[categoryId];

                var btn = scope.querySelector(
                    '.btn-vote[data-category="' +
                    categoryId +
                    '"][data-candidate="' +
                    String(candidateId) +
                    '"]'
                );

                var name = btn
                    ? btn.closest('.candidate')
                          .querySelector('.candidate-info strong')
                          .textContent
                    : candidateId;

                names.push(name.trim());

            });

            if (names.length === 0) {

                alert('Aún no has realizado votos.');

            } else {

                alert('Tus votos:\n\n• ' + names.join('\n• '));

            }

        });

    }


    /* =====================================================
       INICIALIZAR
    ===================================================== */

    restoreVotes();

    updateVotesMade();

});
