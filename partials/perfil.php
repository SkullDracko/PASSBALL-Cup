<?php

$miembroEquipo = null;

$esLider = false;

try {

    $stmt = $pdo->prepare(
        "SELECT e.id AS equipo_id,
                e.nombre AS equipo_nombre,
                e.logo AS equipo_logo,
                e.capitan_id,
                em.posicion
           FROM equipo_miembros em
           JOIN equipos e  ON e.id = em.equipo_id
          WHERE em.jugador_id = ?
            AND em.estado = 'activo'
          ORDER BY em.id DESC
          LIMIT 1"
    );

    $stmt->execute([$usuario['id']]);

    $miembroEquipo = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($miembroEquipo) {
        $esLider = ((int) $miembroEquipo['capitan_id'] === (int) $usuario['id']);
    }

} catch (Exception $e) {
    error_log("Perfil PASSBALL: " . $e->getMessage());
}

$posicionesEsp = [
    'POR' => 'Portero',
    'DEF' => 'Defensa',
    'MED' => 'Mediocampista',
    'DEL' => 'Delantero',
];

$avatar = $usuario['avatar'] ?? null;

$aliasJugador = $usuario['alias'] ?? null;

if ($aliasJugador !== null && trim((string) $aliasJugador) === '') {
    $aliasJugador = null;
}

$nombreReal = htmlspecialchars(
    $usuario['nombre'] ?? 'Jugador',
    ENT_QUOTES,
    'UTF-8'
);

$nombreVisible = $aliasJugador ?: ($usuario['nombre'] ?? 'Jugador');

$nombreJugador = htmlspecialchars(
    $nombreVisible,
    ENT_QUOTES,
    'UTF-8'
);

$matriculaJugador = htmlspecialchars(
    $usuario['matricula'] ?? '',
    ENT_QUOTES,
    'UTF-8'
);

$aliasVal = $aliasJugador
    ? htmlspecialchars($aliasJugador, ENT_QUOTES, 'UTF-8')
    : null;

$aliasInput = $aliasJugador
    ? htmlspecialchars($aliasJugador, ENT_QUOTES, 'UTF-8')
    : '';

$textoRolHtml = htmlspecialchars($textoRol, ENT_QUOTES, 'UTF-8');

?>

<div class="perfil-container">

    <section class="perfil-hero">

        <div class="perfil-avatar">

            <?php if (!empty($avatar)): ?>
                <img
                    src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>"
                    alt="Foto de perfil"
                >
            <?php else: ?>
                <i class="fa-solid fa-user"></i>
            <?php endif; ?>

        </div>

        <div class="perfil-ident">

            <h2 class="perfil-nombre">
                <?= $nombreJugador ?>
            </h2>

            <p class="perfil-matricula">
                Matrícula
                <b><?= $matriculaJugador ?></b>
            </p>

            <span class="perfil-rol-badge">
                <?= $textoRolHtml ?>
            </span>

        </div>

    </section>


    <section class="perfil-panel">

        <h3 class="perfil-panel-title">
            <i class="fa-solid fa-circle-user"></i>
            Mis datos
        </h3>

        <div class="perfil-filas">

            <div class="perfil-fila">
                <span>Nombre</span>
                <b><?= $nombreReal ?></b>
            </div>

            <div class="perfil-fila">
                <span>Alias</span>
                <b id="perfilAliasVal"><?= $aliasVal ?? '—' ?></b>
            </div>

            <div class="perfil-fila">
                <span>Matrícula</span>
                <b><?= $matriculaJugador ?></b>
            </div>

            <div class="perfil-fila">
                <span>Rol</span>
                <b><?= $textoRolHtml ?></b>
            </div>

        </div>

    </section>


    <section class="perfil-panel">

        <h3 class="perfil-panel-title">
            <i class="fa-solid fa-pen"></i>
            Editar perfil
        </h3>

        <form class="perfil-form" id="perfilForm" enctype="multipart/form-data">

            <div class="perfil-field">

                <label for="aliasInput">
                    Alias
                </label>

                <input
                    type="text"
                    id="aliasInput"
                    name="alias"
                    maxlength="40"
                    placeholder="Tu apodo"
                    value="<?= $aliasInput ?>"
                >

                <small>
                    Tu nombre aparece con el alias. Si lo dejas vacío, se usa tu nombre real.
                </small>

            </div>


            <div class="perfil-field">

                <label for="fotoInput">
                    Foto de perfil
                </label>

                <input
                    type="file"
                    id="fotoInput"
                    name="foto_perfil"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                >

                <small>
                    JPG, PNG, WEBP o GIF · máximo 5 MB
                </small>

            </div>


            <button
                type="submit"
                class="btn-purple perfil-submit"
            >
                <i class="fa-solid fa-check"></i>
                Guardar
            </button>

            <span class="perfil-msg" id="perfilMsg"></span>

        </form>

    </section>


    <section class="perfil-panel">

        <h3 class="perfil-panel-title">
            <i class="fa-solid fa-shield-halved"></i>
            Mi equipo
        </h3>

        <?php if ($miembroEquipo): ?>

            <div class="perfil-equipo">

                <div class="perfil-equipo-logo">

                    <?php if (!empty($miembroEquipo['equipo_logo'])): ?>
                        <img
                            src="<?= htmlspecialchars($miembroEquipo['equipo_logo'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($miembroEquipo['equipo_nombre'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                    <?php else: ?>
                        <i class="fa-solid fa-shield-halved"></i>
                    <?php endif; ?>

                </div>

                <div class="perfil-equipo-info">

                    <b>
                        <?= htmlspecialchars($miembroEquipo['equipo_nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </b>

                    <span>
                        <?= $esLider ? 'Líder' : 'Jugador' ?>
                        ·
                        <?= htmlspecialchars($posicionesEsp[$miembroEquipo['posicion']] ?? $miembroEquipo['posicion'], ENT_QUOTES, 'UTF-8') ?>
                    </span>

                </div>

                <a
                    class="perfil-equipo-link"
                    href="equipos/detalle.php?id=<?= (int) $miembroEquipo['equipo_id'] ?>"
                >
                    Ver equipo
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>

        <?php else: ?>

            <p class="perfil-vacio">
                Aún no perteneces a ningún equipo.
            </p>

        <?php endif; ?>

    </section>

</div>


<script>
(function () {

    var form = document.getElementById('perfilForm');

    if (!form) {
        return;
    }

    var msg        = document.getElementById('perfilMsg');
    var heroName   = document.querySelector('.perfil-nombre');
    var heroAvatar = document.querySelector('.perfil-avatar');
    var aliasVal   = document.getElementById('perfilAliasVal');
    var topbarName = document.querySelector('.topbar-user-name');
    var topbarAva  = document.querySelector('.topbar-avatar');
    var bannerAva  = document.querySelector('.profile-avatar');
    var fotoInput  = document.getElementById('fotoInput');

    /* Previsualizar la foto elegida en la cabecera */

    fotoInput.addEventListener('change', function () {

        var file = this.files[0];

        if (!file || !heroAvatar) {
            return;
        }

        var reader = new FileReader();

        reader.onload = function (e) {

            heroAvatar.innerHTML =
                '<img src="' + e.target.result + '" alt="Foto de perfil" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">';
        };

        reader.readAsDataURL(file);
    });

    form.addEventListener('submit', function (e) {

        e.preventDefault();

        var data = new FormData(form);

        data.append('action', 'actualizar_perfil');

        msg.textContent = 'Guardando…';
        msg.className   = 'perfil-msg';

        fetch('controllers/perfilController.php', {
            method: 'POST',
            body:   data,
        })
        .then(function (r) {
            return r.json();
        })
        .then(function (res) {

            if (!res.success) {
                msg.textContent = res.message || 'No se pudo guardar.';
                msg.className   = 'perfil-msg error';
                return;
            }

            msg.textContent = res.message || 'Perfil actualizado.';
            msg.className   = 'perfil-msg ok';

            var u = res.usuario || {};

            var display = '';

            if (u.alias && u.alias !== '') {
                display = u.alias;
            } else if (u.nombre) {
                display = u.nombre;
            }

            if (heroName) {
                heroName.textContent = display;
            }

            if (aliasVal) {
                aliasVal.textContent = (u.alias && u.alias !== '') ? u.alias : '—';
            }

            if (topbarName) {
                topbarName.textContent = display;
            }

            if (u.avatar) {

                var setAvatar = function (container) {

                    if (!container) {
                        return;
                    }

                    var img = container.querySelector('img');

                    if (img) {
                        img.src = u.avatar;
                    } else {
                        container.innerHTML =
                            '<img src="' + u.avatar + '" alt="Foto de perfil" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">';
                    }
                };

                if (heroAvatar) {
                    setAvatar(heroAvatar);
                }

                setAvatar(topbarAva);
                setAvatar(bannerAva);
            }

        })
        .catch(function () {
            msg.textContent = 'Error de conexión.';
            msg.className   = 'perfil-msg error';
        });
    });

})();
</script>