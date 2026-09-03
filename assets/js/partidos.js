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

    var courtFilter =
        document.getElementById("courtFilter");

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

        var courtValue = courtFilter
            ? courtFilter.value
            : "todos";

        var visibleMatches = 0;

        matchRows.forEach(function (row) {

            var status =
                row.getAttribute("data-status");

            var court =
                row.getAttribute("data-court");

            var searchData =
                row.getAttribute("data-search") || "";

            var statusMatches =
                currentStatus === "todos" ||
                status === currentStatus;

            var courtMatches =
                courtValue === "todos" ||
                court === courtValue;

            var searchMatches =
                searchValue === "" ||
                searchData.indexOf(searchValue) !== -1;

            if (
                statusMatches &&
                courtMatches &&
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
       BUSCADOR / FILTRO DE CANCHA
       ======================================================== */

    if (matchSearch) {

        matchSearch.addEventListener("input", filterMatches);

    }

    if (courtFilter) {

        courtFilter.addEventListener("change", filterMatches);

    }


    /* ========================================================
       INICIALIZAR
       ======================================================== */

    filterMatches();

});
