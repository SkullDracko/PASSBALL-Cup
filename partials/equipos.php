<?php
/**
 * ============================================================
 * PASSBALL Cup - Equipos (vista dentro del dashboard)
 * ============================================================
 * Partial que se incluye dentro de <div id="view-equipos">
 * en dashboard.php. Contiene los datos y el markup de la
 * sección Equipos.
 * ============================================================
 */

/*
|--------------------------------------------------------------------------
| MI EQUIPO
|--------------------------------------------------------------------------
*/

$miEquipo = null;

try {

    $stmt = $pdo->prepare("
        SELECT
            e.*,
            (
                SELECT COUNT(*)
                FROM equipo_miembros em2
                WHERE em2.equipo_id = e.id
                AND em2.estado = 'activo'
            ) AS total_miembros

        FROM equipo_miembros em

        INNER JOIN equipos e
            ON e.id = em.equipo_id

        WHERE em.usuario_id = ?
        AND em.estado = 'activo'
        AND e.activo = 1

        LIMIT 1
    ");

    $stmt->execute([
        $usuario['id']
    ]);

    $miEquipo = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log(
        "PASSBALL - Error obteniendo mi equipo: " .
        $e->getMessage()
    );

    $miEquipo = null;
}


/*
|--------------------------------------------------------------------------
| TODOS LOS EQUIPOS
|--------------------------------------------------------------------------
*/

$equipos = [];

try {

    $stmt = $pdo->query("
        SELECT
            e.*,
            (
                SELECT COUNT(*)
                FROM equipo_miembros em
                WHERE em.equipo_id = e.id
                AND em.estado = 'activo'
            ) AS total_miembros

        FROM equipos e

        WHERE e.activo = 1

        ORDER BY e.fecha_creacion DESC
    ");

    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log(
        "PASSBALL - Error listando equipos: " .
        $e->getMessage()
    );

    $equipos = [];
}


/*
|--------------------------------------------------------------------------
| MENSAJES FLASH
|--------------------------------------------------------------------------
*/

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;

unset(
    $_SESSION['flash_success'],
    $_SESSION['flash_error']
);
?>


<!-- FLASH -->

<?php if ($flashSuccess): ?>

    <div class="flash flash-success">
        <i class="fa-solid fa-circle-check"></i>
        <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
    </div>

<?php endif; ?>


<?php if ($flashError): ?>

    <div class="flash flash-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
    </div>

<?php endif; ?>


<!-- ENCABEZADO -->

<div class="equipos-heading">

    <h1>
        Equipos
    </h1>

    <p>
        Busca un equipo existente o registra uno nuevo.
    </p>

</div>


<!-- MI EQUIPO -->

<?php if ($miEquipo): ?>

    <section class="my-team-card">

        <div class="my-team-logo">

            <?php if (!empty($miEquipo['logo'])): ?>

                <img
                    src="<?= htmlspecialchars($miEquipo['logo'], ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($miEquipo['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                >

            <?php else: ?>

                <div
                    class="no-logo"
                    style="background: <?= htmlspecialchars($miEquipo['color_equipo'] ?? '#4b2780', ENT_QUOTES, 'UTF-8') ?>"
                >

                    <?= htmlspecialchars(
                        mb_strtoupper(mb_substr($miEquipo['nombre'], 0, 1, 'UTF-8'), 'UTF-8'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>

        </div>


        <div class="my-team-info">

            <h2>
                <?= htmlspecialchars($miEquipo['nombre'], ENT_QUOTES, 'UTF-8') ?>
            </h2>

            <div class="team-meta">

                <span class="participants">
                    <i class="fa-solid fa-users"></i>
                    <?= (int) $miEquipo['total_miembros'] ?>
                    participante<?= (int) $miEquipo['total_miembros'] !== 1 ? 's' : '' ?>
                </span>

                <span class="registered">
                    <i class="fa-solid fa-circle-check"></i>
                    Equipo registrado
                </span>

            </div>

            <p class="team-role">
                <i class="fa-solid fa-star"></i>

                <?php if ($rolUsuario === 'lider'): ?>
                    Líder del equipo
                <?php else: ?>
                    Miembro del equipo
                <?php endif; ?>

            </p>

        </div>


        <div class="my-team-action">

            <a
                href="equipos/detalle.php?id=<?= (int) $miEquipo['id'] ?>"
                class="btn-purple"
            >

                Ver mi equipo

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>

    </section>

<?php endif; ?>


<!-- ACCIONES -->

<section class="team-actions">

    <div class="section-title">

        <h2>
            Gestiona
        </h2>

        <p>
            Busca un equipo existente o registra uno nuevo.
        </p>

    </div>


    <div class="action-buttons">


        <button
            type="button"
            class="action-card"
            id="focusSearch"
        >

            <div class="action-icon purple">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>

            <div class="action-text">

                <h3>
                    Buscar equipo
                </h3>

                <p>
                    Encuentra un equipo y consulta su información.
                </p>

            </div>

            <i class="fa-solid fa-arrow-right action-arrow"></i>

        </button>


        <?php if (!$miEquipo): ?>

            <button
                type="button"
                class="action-card"
                id="openRegister"
            >

                <div class="action-icon orange">
                    <i class="fa-solid fa-plus"></i>
                </div>

                <div class="action-text">

                    <h3>
                        Registrar equipo
                    </h3>

                    <p>
                        Crea un nuevo equipo y conviértete en líder.
                    </p>

                </div>

                <i class="fa-solid fa-arrow-right action-arrow orange-arrow"></i>

            </button>

        <?php endif; ?>

    </div>

</section>


<!-- EQUIPOS DISPONIBLES -->

<section class="available-section">

    <div class="available-header">

        <div>

            <h2>
                Equipos disponibles
            </h2>

            <p>
                Explora los equipos registrados en el torneo.
            </p>

        </div>


        <div class="search-box">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                id="teamSearch"
                placeholder="Buscar equipo..."
                autocomplete="off"
            >

        </div>

    </div>


    <div
        class="teams-grid"
        id="teamsGrid"
    >

        <?php if (!empty($equipos)): ?>

            <?php foreach ($equipos as $eq): ?>

                <article
                    class="team-card"
                    data-team-name="<?= htmlspecialchars(mb_strtolower($eq['nombre'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
                >

                    <div class="team-card-logo">

                        <?php if (!empty($eq['logo'])): ?>

                            <img
                                src="<?= htmlspecialchars($eq['logo'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($eq['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                            >

                        <?php else: ?>

                            <div
                                class="no-logo"
                                style="background: <?= htmlspecialchars($eq['color_equipo'] ?? '#4b2780', ENT_QUOTES, 'UTF-8') ?>"
                            >

                                <?= htmlspecialchars(
                                    mb_strtoupper(mb_substr($eq['nombre'], 0, 1, 'UTF-8'), 'UTF-8'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <h3>
                        <?= htmlspecialchars($eq['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </h3>


                    <p class="team-members">
                        <i class="fa-solid fa-users"></i>
                        <?= (int) $eq['total_miembros'] ?>
                        participante<?= (int) $eq['total_miembros'] !== 1 ? 's' : '' ?>
                    </p>


                    <a
                        href="equipos/detalle.php?id=<?= (int) $eq['id'] ?>"
                        class="btn-outline <?= ((int)$eq['id'] % 2 === 0) ? 'orange' : '' ?>"
                    >

                        Ver equipo

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>


    <div
        class="no-results"
        id="noResults"
    >

        <i class="fa-solid fa-magnifying-glass"></i>

        <h3>
            No encontramos ese equipo
        </h3>

        <p>
            Intenta buscar con otro nombre.
        </p>

    </div>

</section>


<?php if (!$miEquipo): ?>

    <section class="registration-info">

        <div class="info-icon">
            <i class="fa-solid fa-circle-info"></i>
        </div>

        <div class="info-text">

            <h3>
                ¿No encuentras tu equipo?
            </h3>

            <p>
                Puedes registrar uno nuevo y automáticamente
                serás designado como líder.
            </p>

        </div>

        <button
            type="button"
            class="info-button"
            id="openRegisterBottom"
        >

            Registrar equipo

            <i class="fa-solid fa-arrow-right"></i>

        </button>

    </section>

<?php endif; ?>
