/**
 * ============================================================
 * PASSBALL Cup - Resultados
 * ============================================================
 * Filtros y controles de la sección Resultados.
 * ============================================================
 */

document.addEventListener("DOMContentLoaded", function () {


    /* ========================================================
       ELEMENTOS
       ======================================================== */

    const resultTabs =
        document.querySelectorAll(
            "#view-resultados .result-tab"
        );

    const resultRows =
        document.querySelectorAll(
            "#view-resultados .result-row"
        );

    const resultsSearch =
        document.getElementById(
            "resultsSearch"
        );

    const resultsCourt =
        document.getElementById(
            "resultsCourt"
        );

    const resultsDateFrom =
        document.getElementById(
            "resultsDateFrom"
        );

    const resultsDateTo =
        document.getElementById(
            "resultsDateTo"
        );

    const clearFilters =
        document.getElementById(
            "clearResultsFilters"
        );

    const resultsEmpty =
        document.getElementById(
            "resultsEmpty"
        );

    const loadMore =
        document.getElementById(
            "loadMoreResults"
        );


    let currentFilter = "todos";


    /* ========================================================
       FILTRAR RESULTADOS
       ======================================================== */

    function filterResults() {

        const searchValue =
            resultsSearch
                ? resultsSearch.value
                    .toLowerCase()
                    .trim()
                : "";

        const courtValue =
            resultsCourt
                ? resultsCourt.value
                : "todos";


        let visibleResults = 0;


        resultRows.forEach(function (row) {

            const court =
                row.getAttribute(
                    "data-court"
                ) || "";


            const searchData =
                row.getAttribute(
                    "data-search"
                ) || "";


            const searchMatches =
                searchValue === "" ||
                searchData.includes(
                    searchValue
                );


            const courtMatches =
                courtValue === "todos" ||
                court === courtValue;


            let tabMatches = true;


            if (
                currentFilter === "goleadores" ||
                currentFilter === "invictas"
            ) {

                tabMatches = true;

            }


            if (
                searchMatches &&
                courtMatches &&
                tabMatches
            ) {

                row.style.display = "";

                visibleResults++;

            } else {

                row.style.display = "none";

            }

        });


        if (resultsEmpty) {

            if (visibleResults === 0) {

                resultsEmpty.classList.add(
                    "show"
                );

            } else {

                resultsEmpty.classList.remove(
                    "show"
                );

            }

        }

    }


    /* ========================================================
       PESTAÑAS
       ======================================================== */

    resultTabs.forEach(function (tab) {

        tab.addEventListener(
            "click",
            function () {

                resultTabs.forEach(
                    function (item) {

                        item.classList.remove(
                            "active"
                        );

                    }
                );


                this.classList.add(
                    "active"
                );


                currentFilter =
                    this.getAttribute(
                        "data-filter"
                    );


                filterResults();

            }
        );

    });


    /* ========================================================
       BUSCADOR
       ======================================================== */

    if (resultsSearch) {

        resultsSearch.addEventListener(
            "input",
            filterResults
        );

    }


    /* ========================================================
       FILTRO DE CANCHA
       ======================================================== */

    if (resultsCourt) {

        resultsCourt.addEventListener(
            "change",
            filterResults
        );

    }


    /* ========================================================
       FILTRO DESDE
       ======================================================== */

    if (resultsDateFrom) {

        resultsDateFrom.addEventListener(
            "change",
            filterResults
        );

    }


    /* ========================================================
       FILTRO HASTA
       ======================================================== */

    if (resultsDateTo) {

        resultsDateTo.addEventListener(
            "change",
            filterResults
        );

    }


    /* ========================================================
       LIMPIAR FILTROS
       ======================================================== */

    if (clearFilters) {

        clearFilters.addEventListener(
            "click",
            function () {


                if (resultsSearch) {

                    resultsSearch.value = "";

                }


                if (resultsCourt) {

                    resultsCourt.value =
                        "todos";

                }


                if (resultsDateFrom) {

                    resultsDateFrom.value =
                        "";

                }


                if (resultsDateTo) {

                    resultsDateTo.value =
                        "";

                }


                resultTabs.forEach(
                    function (tab) {

                        tab.classList.remove(
                            "active"
                        );

                    }
                );


                const firstTab =
                    document.querySelector(
                        "#view-resultados .result-tab[data-filter='todos']"
                    );


                if (firstTab) {

                    firstTab.classList.add(
                        "active"
                    );

                }


                currentFilter = "todos";


                filterResults();

            }
        );

    }


    /* ========================================================
       VER MÁS RESULTADOS
       ======================================================== */

    if (loadMore) {

        loadMore.addEventListener(
            "click",
            function () {

                loadMore.innerHTML =
                    'Mostrando todos los resultados ' +
                    '<i class="fa-solid fa-check"></i>';

                loadMore.disabled = true;

                loadMore.style.opacity = "0.7";

            }
        );

    }


    /* ========================================================
       BOTONES DE DETALLES
       ======================================================== */

    const detailButtons =
        document.querySelectorAll(
            "#view-resultados .result-details"
        );


    detailButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function () {

                    const resultId =
                        this.getAttribute(
                            "data-result"
                        );


                    console.log(
                        "Ver resultado:",
                        resultId
                    );

                }
            );

        }
    );


    /* ========================================================
       INICIALIZAR
       ======================================================== */

    filterResults();

});
