<?php
/**
 * PASSBALL Cup - Panel de administración (shell SPA)
 * Cada sección se carga desde admin/partials/
 */

require_once __DIR__ . '/controllers/auth.php';
require_once __DIR__ . '/../config/app.php';

// El panel es dinámico (scripts/cache-busting por filemtime): nunca debe
// servirse una copia vieja del HTML, o el usuario seguiría viendo la
// versión anterior del menú aún cambiando de sección.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$tituloPagina = 'Panel Admin';
$inicialAdmin = mb_strtoupper(mb_substr($admin['nombre'], 0, 1, 'UTF-8'), 'UTF-8');
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $tituloPagina ?> | <?= TORNEO_NOMBRE ?></title>

    <link rel="icon" href="<?= assetUrl('assets/img/passball-cup.png') ?>" type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link
        rel="stylesheet"
        href="../assets/css/fa/all.min.css"
    >

    <link
        rel="stylesheet"
        href="<?= assetUrl('assets/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= assetUrl('admin/assets/css/admin.css') ?>">

</head>

<body>

<div class="pb-loader" id="pbLoader" role="status" aria-label="Cargando">
    <div class="pb-ball-wrap">
        <i class="pb-ball" aria-hidden="true">⚽</i>
        <div class="pb-shadow"></div>
    </div>
</div>

<div class="admin-shell" id="adminShell">

    <!-- =========================================
         SIDEBAR
         ========================================= -->

    <aside class="admin-sidebar" id="adminSidebar">

        <div class="admin-sidebar-brand">

            <span class="admin-brand-mark">
                <img src="<?= assetUrl('assets/img/passball-p-logo-transparent.png') ?>" alt="PASSBALL Cup">
            </span>

            <span>
                PASSBALL Cup <small>Panel de administración</small>
            </span>

        </div>

         <nav class="admin-sidebar-nav">

         <a class="admin-nav-item active" data-target="view-inicio">
                <span class="nav-ico"><i class="fa-solid fa-house"></i></span>
                Inicio
            </a>
            <div class="admin-nav-group-label">Torneo</div>

            <a class="admin-nav-item" data-target="view-torneo">
                <span class="nav-ico"><i class="fa-solid fa-trophy"></i></span>
                 Torneo y Rondas 
            </a>

            <a class="admin-nav-item" data-target="view-resultados">
                <span class="nav-ico"><i class="fa-solid fa-ranking-star"></i></span>
                 Resultados
            </a>

            <a class="admin-nav-item" data-target="view-postulaciones">
                <span class="nav-ico"><i class="fa-solid fa-inbox"></i></span>
                Postulaciones
            </a>

            <div class="admin-nav-group-label">Participantes</div>

            <a class="admin-nav-item" data-target="view-participantes">
                <span class="nav-ico"><i class="fa-solid fa-people-group"></i></span>
                Participantes
            </a>
 
            <div class="admin-nav-group-label">Interacción</div>

            <a class="admin-nav-item" data-target="view-votaciones">
                <span class="nav-ico"><i class="fa-solid fa-star"></i></span>
                 Votaciones
            </a>

            <a class="admin-nav-item" data-target="view-comunidad">
                <span class="nav-ico"><i class="fa-solid fa-comment-dots"></i></span>
                Comunidad
            </a>
        </nav>
        
        <div class="admin-sidebar-footer">

            <a
                class="portal-link"
                href="<?= assetUrl('dashboard.php') ?>"
                target="_blank"
                rel="noopener"
            >
                <span class="nav-ico"><i class="fa-solid fa-globe"></i></span> Ver portal
            </a>

        </div>

    </aside>


    <!-- =========================================
         CONTENIDO
         ========================================= -->

    <main class="admin-main">

        <header class="admin-topbar">

            <button class="burger" id="adminBurger" aria-label="Abrir menú">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="admin-greet">
                <h1 id="adminTitle">Inicio</h1>
                <p>Bienvenida, <?= htmlspecialchars($admin['nombre']) ?></p>
            </div>

            <div class="admin-user">

                <button class="admin-user-btn" type="button">
                    <span class="avatar-nm"><?= htmlspecialchars($inicialAdmin) ?></span>
                    <span class="admin-user-txt">
                        <strong><?= htmlspecialchars($admin['nombre']) ?></strong>
                        <small><?= htmlspecialchars($admin['usuario']) ?></small>
                    </span>
                    <i class="fa-solid fa-chevron-down admin-user-chev"></i>
                </button>

                <div class="admin-user-menu">
                    <div class="admin-user-who">
                        <strong><?= htmlspecialchars($admin['nombre']) ?></strong>
                        <small>Administración</small>
                    </div>
                    <a class="logout" href="<?= assetUrl('admin/controllers/logout.php') ?>">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión
                    </a>
                </div>

            </div>

        </header>


        <div class="admin-views">

            <section class="admin-view active" id="view-inicio">
                <?php include __DIR__ . '/partials/inicio.php'; ?>
            </section>

            <section class="admin-view" id="view-postulaciones">
                <?php include __DIR__ . '/partials/postulaciones.php'; ?>
            </section>

            <section class="admin-view" id="view-torneo">
                <?php include __DIR__ . '/partials/torneo.php'; ?>
            </section>

            <section class="admin-view" id="view-resultados">
                <?php include __DIR__ . '/partials/resultados.php'; ?>
            </section>

            <section class="admin-view" id="view-comunidad">
                <?php include __DIR__ . '/partials/comunidad.php'; ?>
            </section>

            <section class="admin-view" id="view-votaciones">
                <?php include __DIR__ . '/partials/votaciones.php'; ?>
            </section>

            <section class="admin-view" id="view-participantes">
                <?php include __DIR__ . '/partials/participantes.php'; ?>
            </section>

        </div>

    </main>

</div>


<!-- La API se expone antes de api.js: su base tiene que ser absoluta,
     no relativa, para no depender de la forma de la URL. -->
<script>window.PASSBALL_API = <?= apiUrlJs() ?>;</script>

<script src="<?= assetUrl('admin/assets/js/api.js') ?>"></script>
<script src="<?= assetUrl('admin/assets/js/views.js') ?>"></script>
<script src="<?= assetUrl('admin/assets/js/admin.js') ?>"></script>

<!-- Overlay GABY: convierte el form inline "Agregar partido" (que views.js
     genera sin labels) en un MODAL. No altera la lógica: el form conserva su
     data-accion / data-ronda / data-torneo, por lo que el submit delegado de
     views.js sigue funcionando igual. -->
<script>
(function () {
    var modal = null;
    if (window && !window.__gbyLog) window.__gbyLog = [];
    var campoConfig = [
        ["equipo_local_id", "Local"],
        ["equipo_visitante_id", "Visitante"]
    ];

    function label(par, el) {
        var lab = document.createElement("label");
        var sp = document.createElement("span");
        sp.textContent = par[1];
        lab.appendChild(sp);
        lab.appendChild(el);
        return lab;
    }

    function construirModalPartido() {
        modal = document.createElement("div");
        modal.className = "gby-modal";

        var dialog = document.createElement("div");
        dialog.className = "prod-dialog";

        var head = document.createElement("div");
        head.className = "prod-head";
        var h3 = document.createElement("h3");
        h3.textContent = "Agregar partido";
        var chip = document.createElement("span");
        chip.className = "prod-round";
        var x = document.createElement("button");
        x.type = "button";
        x.className = "prod-x";
        x.setAttribute("aria-label", "Cerrar");
        x.textContent = "×";
        x.addEventListener("click", cerrar);
        head.appendChild(h3);
        head.appendChild(chip);
        head.appendChild(x);

        dialog.appendChild(head);
        modal.appendChild(dialog);
        modal.addEventListener("click", function (ev) {
            if (ev.target === modal) cerrar();
        });
        document.body.appendChild(modal);
    }

    function armadas(form) {
        var cuerpo = document.createElement("div");
        cuerpo.className = "prod-body";

        function sec(txt) {
            var s = document.createElement("span");
            s.className = "prod-sec";
            s.textContent = txt;
            return s;
        }

        var grid = document.createElement("div");
        grid.className = "prod-grid2";
        campoConfig.forEach(function (par) {
            var el = form.querySelector('[name="' + par[0] + '"]');
            if (el) {
                if (el.tagName === "SELECT" && el.options && el.options[0]) {
                    el.options[0].disabled = true;
                }
                grid.appendChild(label(par, el));
            }
        });

        cuerpo.appendChild(sec("Equipos"));
        cuerpo.appendChild(grid);

        var fecha = form.querySelector('[name="fecha_hora"]');
        var cancha = form.querySelector('[name="cancha"]');
        var pos = form.querySelector('[name="posicion"]');

        var gp = document.createElement("div");
        gp.className = "prod-grid2";
        if (cancha) gp.appendChild(label(["cancha", "Cancha"], cancha));
        if (pos) gp.appendChild(label(["posicion", "Posición (auto)"], pos));

        if (fecha || gp.children.length) cuerpo.appendChild(sec("Detalles"));
        if (fecha) cuerpo.appendChild(label(["fecha_hora", "Fecha y hora"], fecha));
        if (gp.children.length) cuerpo.appendChild(gp);

        var foot = document.createElement("div");
        foot.className = "prod-foot";
        var cancelar = document.createElement("button");
        cancelar.type = "button";
        cancelar.className = "prod-btn ghost";
        cancelar.textContent = "Cancelar";
        cancelar.addEventListener("click", cerrar);
        var guardar = form.querySelector('[type="submit"]');
        if (guardar) {
            guardar.textContent = "Guardar partido";
            guardar.classList.remove("admin-btn");
            guardar.classList.add("prod-btn");
        }
        foot.appendChild(cancelar);
        if (guardar) foot.appendChild(guardar);

        form.appendChild(cuerpo);
        form.appendChild(foot);
    }

    function cerrar() {
        if (!modal) return;
        modal.classList.remove("abierto");
        var form = modal.querySelector("form");
        if (form) form.reset();
    }

    function preparar(form) {
        if (form.dataset.gbyListo === "1") return;
        form.dataset.gbyListo = "1";

        if (!modal) construirModalPartido();
        var col = form.closest(".round-col");
        if (!col) return;

        armadas(form);

        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "gby-add-btn";
        btn.textContent = "+ Agregar partido";
        btn.dataset.ronda = form.dataset.ronda;
        btn._gbyForm = form;

        // 1º: el botón ocupa el lugar del form en la card.
        col.replaceChild(btn, form);

        // 2º: mover el form al dialogo del modal (después del replace, o el
        // form deja de ser hijo de la card y replaceChild lanza NotFoundError).
        var dialog = modal.querySelector(".prod-dialog");
        var viejo = modal.querySelector("form");
        if (viejo && viejo !== form) dialog.replaceChild(form, viejo);
        else dialog.appendChild(form);
    }

    function abrirPartido(btn) {
        if (!modal) return;
        var form = btn._gbyForm || document.querySelector(".round-add-form");
        if (!form) return;
        form.dataset.ronda = btn.dataset.ronda;
        var col = btn.closest(".round-col");
        var ro = col ? col.querySelector(".round-order") : null;
        modal.querySelector(".prod-round").textContent =
            ro ? ro.textContent : "Ronda " + btn.dataset.ronda;
        modal.classList.add("abierto");
    }

    function barrer() {
        var lista = document.querySelectorAll(".round-add-form");
        window.__gbyLog.push("barrer forms=" + lista.length);
        Array.prototype.forEach.call(lista, preparar);
    }

    // Delegado, igual que los submit de views.js: sobrevive a cualquier
    // re-render de la vista sin necesidad de religar por nodo.
    document.addEventListener("click", function (ev) {
        var btn = ev.target.closest ? ev.target.closest(".gby-add-btn") : null;
        if (btn) {
            ev.preventDefault();
            abrirPartido(btn);
        }
    });

    var obs = new MutationObserver(barrer);
    obs.observe(document.body, { childList: true, subtree: true });
    barrer();

    if (window.console) console.log("[GABY] overlay modal listo");
})();
</script>

</body>

</html>