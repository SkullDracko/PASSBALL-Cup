/**
 * ============================================================
 * PASSBALL Cup - Partidos
 * Filtros y pestañas de la sección Partidos (dentro del dashboard)
 * ============================================================
 */

document.addEventListener("DOMContentLoaded", function () {

    /* ========================================================
       ELEMENTOS
       ======================================================== */

    var matchTabs =
        document.querySelectorAll("#view-partidos .match-tab");

    var matchRows =
        document.querySelectorAll("#view-partidos .match-row");

    var matchSearch =
        document.getElementById("matchSearch");

    var categoriaFilter =
        document.getElementById("categoriaFilter");

    var emptyResults =
        document.getElementById("emptyResults");

    var currentStatus = "proximo";


    /* ========================================================
       FILTRAR PARTIDOS
       ======================================================== */

    function filterMatches() {

        var searchValue = matchSearch
            ? matchSearch.value.toLowerCase().trim()
            : "";

        var categoriaValue = categoriaFilter
            ? categoriaFilter.value
            : "todos";

        var visibleMatches = 0;

        matchRows.forEach(function (row) {

            var status =
                row.getAttribute("data-status");

            var categoria =
                row.getAttribute("data-categoria");

            var searchData =
                row.getAttribute("data-search") || "";

            var statusMatches =
                currentStatus === "todos" ||
                status === currentStatus;

            var categoriaMatches =
                categoriaValue === "todos" ||
                categoria === categoriaValue;

            var searchMatches =
                searchValue === "" ||
                searchData.indexOf(searchValue) !== -1;

            if (
                statusMatches &&
                categoriaMatches &&
                searchMatches
            ) {

                row.style.display = "";
                visibleMatches++;

            } else {

                row.style.display = "none";

            }

        });

        if (emptyResults) {

            if (visibleMatches === 0) {

                emptyResults.classList.add("show");

            } else {

                emptyResults.classList.remove("show");

            }

        }

    }


    /* ========================================================
       PESTAÑAS
       ======================================================== */

    matchTabs.forEach(function (tab) {

        tab.addEventListener("click", function () {

            matchTabs.forEach(function (item) {
                item.classList.remove("active");
            });

            this.classList.add("active");

            currentStatus =
                this.getAttribute("data-status");

            filterMatches();

        });

    });


    /* ========================================================
       BUSCADOR / FILTRO DE CATEGORÍA
       ======================================================== */

    if (matchSearch) {

        matchSearch.addEventListener("input", filterMatches);

    }

    if (categoriaFilter) {

        categoriaFilter.addEventListener("change", filterMatches);

    }


    /* ========================================================
       INICIALIZAR
       ======================================================== */

    filterMatches();

});
