<?php
/**
 * ============================================================
 * PASSBALL Cup - Votos (vista dentro del dashboard)
 * ============================================================
 * Partial que se incluye dentro de <div id="view-votos">
 * en dashboard.php. Contiene los datos y el markup de la
 * sección Votos.
 * ============================================================
 */

/*
|--------------------------------------------------------------------------
| DATOS DE DEMOSTRACIÓN
|--------------------------------------------------------------------------
| Posteriormente estos datos pueden venir directamente de MySQL.
*/

$categorias = [
    [
        'id'          => 'atajadass',
        'nombre'      => 'Más atajadas',
        'descripcion' => 'Mejor portero del torneo',
        'icono'       => '🖐️',
        'tipo'        => 'jugador'
    ],
    [
        'id'          => 'goleador',
        'nombre'      => 'Goleador del torneo',
        'descripcion' => 'Máximo goleador',
        'icono'       => '⚽',
        'tipo'        => 'jugador'
    ],
    [
        'id'          => 'jugador',
        'nombre'      => 'Jugador destacado',
        'descripcion' => 'Mejor rendimiento',
        'icono'       => '⭐',
        'tipo'        => 'jugador'
    ],
    [
        'id'          => 'mejor-equipo',
        'nombre'      => 'Mejor equipo',
        'descripcion' => 'Equipo más sólido',
        'icono'       => '👥',
        'tipo'        => 'equipo'
    ],
    [
        'id'          => 'equipo-ganador',
        'nombre'      => 'Equipo ganador',
        'descripcion' => 'Campeón del torneo',
        'icono'       => '👑',
        'tipo'        => 'equipo'
    ],
    [
        'id'          => 'fair-play',
        'nombre'      => 'Fair Play',
        'descripcion' => 'Mejor espíritu deportivo',
        'icono'       => '🤝',
        'tipo'        => 'equipo'
    ]
];


/*
|--------------------------------------------------------------------------
| JUGADORES
|--------------------------------------------------------------------------
*/

$jugadores = [
    ['id' => 1, 'nombre' => 'Carlos Mendoza', 'equipo' => 'Águilas FC',  'avatar' => 'CM'],
    ['id' => 2, 'nombre' => 'Andrés López',   'equipo' => 'Lobos FC',     'avatar' => 'AL'],
    ['id' => 3, 'nombre' => 'Diego Ramírez',  'equipo' => 'Águilas FC',  'avatar' => 'DR'],
    ['id' => 4, 'nombre' => 'Luis Martínez',  'equipo' => 'Tigres FC',    'avatar' => 'LM'],
    ['id' => 5, 'nombre' => 'Marco Díaz',     'equipo' => 'Real Passball','avatar' => 'MD'],
    ['id' => 6, 'nombre' => 'Juan Pérez',     'equipo' => 'Lobos FC',     'avatar' => 'JP']
];


/*
|--------------------------------------------------------------------------
| EQUIPOS
|--------------------------------------------------------------------------
*/

$equipos = [
    ['id' => 1, 'nombre' => 'Águilas FC',   'participantes' => 5, 'avatar' => '🦅'],
    ['id' => 2, 'nombre' => 'Tigres FC',    'participantes' => 7, 'avatar' => '🐯'],
    ['id' => 3, 'nombre' => 'Lobos FC',     'participantes' => 6, 'avatar' => '🐺'],
    ['id' => 4, 'nombre' => 'Real Passball','participantes' => 4, 'avatar' => '⚽'],
    ['id' => 5, 'nombre' => 'Passball FC',  'participantes' => 3, 'avatar' => '🏆']
];
?>


<!-- ENCABEZADO -->

<div class="page-header">

    <div>

        <h1>
            <i class="fa-solid fa-circle-check"></i>
            Votos
        </h1>

        <p>
            Participa y vota por los mejores jugadores y equipos del torneo.
        </p>

    </div>

</div>


<!-- ESTADÍSTICAS -->

<div class="vote-stats">

    <div class="vote-stat-card">

        <div class="stat-icon purple">
            <i class="fa-solid fa-users"></i>
        </div>

        <div>

            <strong>6</strong>

            <span>CATEGORÍAS ACTIVAS</span>

            <small>
                En las que puedes votar
            </small>

        </div>

    </div>


    <div class="vote-stat-card">

        <div class="stat-icon orange">
            <i class="fa-solid fa-users"></i>
        </div>

        <div>

            <strong id="votesMade">
                0
            </strong>

            <span>VOTOS REALIZADOS</span>

            <small>
                Gracias por participar
            </small>

        </div>

    </div>


    <div class="vote-stat-card">

        <div class="stat-icon purple">
            <i class="fa-regular fa-clock"></i>
        </div>

        <div>

            <strong>5</strong>

            <span>DÍAS RESTANTES</span>

            <small>
                Para seguir votando
            </small>

        </div>

    </div>


    <div class="vote-stat-card">

        <div class="stat-icon orange">
            <i class="fa-regular fa-star"></i>
        </div>

        <div>

            <strong>128</strong>

            <span>TOTAL DE VOTOS</span>

            <small>
                En todo el torneo
            </small>

        </div>

    </div>

</div>


<!-- CATEGORÍAS -->

<div class="section-title">

    <h2>
        Categorías de votación
    </h2>

</div>


<div class="category-tabs">

    <button
        class="category-tab active"
        data-category="all"
    >

        <i class="fa-solid fa-layer-group"></i>

        <div>
            <strong>Todas</strong>
            <span>Ver todas</span>
        </div>

    </button>


    <?php foreach ($categorias as $categoria): ?>

        <button
            class="category-tab"
            data-category="<?= htmlspecialchars($categoria['id']) ?>"
        >

            <i>
                <?= $categoria['icono'] ?>
            </i>

            <div>

                <strong>
                    <?= htmlspecialchars($categoria['nombre']) ?>
                </strong>

                <span>
                    <?= htmlspecialchars($categoria['descripcion']) ?>
                </span>

            </div>

        </button>

    <?php endforeach; ?>

</div>


<!-- BUSCADOR -->

<div class="search-row">

    <label>
        Buscar para votar
    </label>

    <div class="vote-search">

        <i class="fa-solid fa-magnifying-glass"></i>

        <input
            type="text"
            id="voteSearch"
            placeholder="Buscar jugador o equipo..."
        >

    </div>

</div>


<!-- GRID DE CATEGORÍAS -->

<div
    class="vote-category-grid"
    id="voteCategoryGrid"
>


    <!-- MÁS ATAJADAS -->

    <article
        class="vote-category-card"
        data-category-card="atajadass"
        data-type="jugador"
    >

        <div class="category-card-header">

            <div class="category-icon purple-light">
                🖐️
            </div>

            <div>

                <h3>
                    Más atajadas
                </h3>

                <span>
                    Mejor portero del torneo
                </span>

            </div>

        </div>


        <div class="candidate-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                class="candidate-input"
                placeholder="Buscar portero..."
            >

        </div>


        <div class="candidate-list">

            <?php foreach ($jugadores as $jugador): ?>

                <div
                    class="candidate"
                    data-name="<?= strtolower(htmlspecialchars($jugador['nombre'] . ' ' . $jugador['equipo'])) ?>"
                >

                    <div class="candidate-avatar purple-bg">
                        <?= htmlspecialchars($jugador['avatar']) ?>
                    </div>

                    <div class="candidate-info">

                        <strong>
                            <?= htmlspecialchars($jugador['nombre']) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars($jugador['equipo']) ?>
                        </span>

                    </div>

                    <button
                        class="btn-vote"
                        data-candidate="<?= $jugador['id'] ?>"
                        data-category="atajadass"
                    >
                        Votar
                    </button>

                </div>

            <?php endforeach; ?>

        </div>

        <button class="view-all">
            Ver todos los porteros
            <i class="fa-solid fa-arrow-right"></i>
        </button>

    </article>


    <!-- GOLEADOR -->

    <article
        class="vote-category-card"
        data-category-card="goleador"
        data-type="jugador"
    >

        <div class="category-card-header">

            <div class="category-icon orange-light">
                ⚽
            </div>

            <div>

                <h3>
                    Goleador del torneo
                </h3>

                <span>
                    Máximo goleador
                </span>

            </div>

        </div>


        <div class="candidate-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                class="candidate-input"
                placeholder="Buscar jugador..."
            >

        </div>


        <div class="candidate-list">

            <?php foreach ($jugadores as $jugador): ?>

                <div
                    class="candidate"
                    data-name="<?= strtolower(htmlspecialchars($jugador['nombre'] . ' ' . $jugador['equipo'])) ?>"
                >

                    <div class="candidate-avatar orange-bg">
                        <?= htmlspecialchars($jugador['avatar']) ?>
                    </div>

                    <div class="candidate-info">

                        <strong>
                            <?= htmlspecialchars($jugador['nombre']) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars($jugador['equipo']) ?>
                        </span>

                    </div>

                    <button
                        class="btn-vote orange-button"
                        data-candidate="<?= $jugador['id'] ?>"
                        data-category="goleador"
                    >
                        Votar
                    </button>

                </div>

            <?php endforeach; ?>

        </div>

        <button class="view-all">
            Ver todos los jugadores
            <i class="fa-solid fa-arrow-right"></i>
        </button>

    </article>


    <!-- JUGADOR DESTACADO -->

    <article
        class="vote-category-card"
        data-category-card="jugador"
        data-type="jugador"
    >

        <div class="category-card-header">

            <div class="category-icon purple-light">
                ⭐
            </div>

            <div>

                <h3>
                    Jugador destacado
                </h3>

                <span>
                    Mejor rendimiento general
                </span>

            </div>

        </div>


        <div class="candidate-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                class="candidate-input"
                placeholder="Buscar jugador..."
            >

        </div>


        <div class="candidate-list">

            <?php foreach ($jugadores as $jugador): ?>

                <div
                    class="candidate"
                    data-name="<?= strtolower(htmlspecialchars($jugador['nombre'] . ' ' . $jugador['equipo'])) ?>"
                >

                    <div class="candidate-avatar purple-bg">
                        <?= htmlspecialchars($jugador['avatar']) ?>
                    </div>

                    <div class="candidate-info">

                        <strong>
                            <?= htmlspecialchars($jugador['nombre']) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars($jugador['equipo']) ?>
                        </span>

                    </div>

                    <button
                        class="btn-vote"
                        data-candidate="<?= $jugador['id'] ?>"
                        data-category="jugador"
                    >
                        Votar
                    </button>

                </div>

            <?php endforeach; ?>

        </div>

        <button class="view-all">
            Ver todos los jugadores
            <i class="fa-solid fa-arrow-right"></i>
        </button>

    </article>


    <!-- MEJOR EQUIPO -->

    <article
        class="vote-category-card"
        data-category-card="mejor-equipo"
        data-type="equipo"
    >

        <div class="category-card-header">

            <div class="category-icon orange-light">
                👥
            </div>

            <div>

                <h3>
                    Mejor equipo
                </h3>

                <span>
                    Equipo más sólido del torneo
                </span>

            </div>

        </div>


        <div class="candidate-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                class="candidate-input"
                placeholder="Buscar equipo..."
            >

        </div>


        <div class="candidate-list">

            <?php foreach ($equipos as $equipo): ?>

                <div
                    class="candidate"
                    data-name="<?= strtolower(htmlspecialchars($equipo['nombre'])) ?>"
                >

                    <div class="candidate-avatar orange-bg">
                        <?= htmlspecialchars($equipo['avatar']) ?>
                    </div>

                    <div class="candidate-info">

                        <strong>
                            <?= htmlspecialchars($equipo['nombre']) ?>
                        </strong>

                        <span>
                            <?= $equipo['participantes'] ?> participantes
                        </span>

                    </div>

                    <button
                        class="btn-vote orange-button"
                        data-candidate="<?= $equipo['id'] ?>"
                        data-category="mejor-equipo"
                    >
                        Votar
                    </button>

                </div>

            <?php endforeach; ?>

        </div>

        <button class="view-all">
            Ver todos los equipos
            <i class="fa-solid fa-arrow-right"></i>
        </button>

    </article>


    <!-- EQUIPO GANADOR -->

    <article
        class="vote-category-card"
        data-category-card="equipo-ganador"
        data-type="equipo"
    >

        <div class="category-card-header">

            <div class="category-icon purple-light">
                👑
            </div>

            <div>

                <h3>
                    Equipo ganador
                </h3>

                <span>
                    Campeón del torneo
                </span>

            </div>

        </div>


        <div class="candidate-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                class="candidate-input"
                placeholder="Buscar equipo..."
            >

        </div>


        <div class="candidate-list">

            <?php foreach ($equipos as $equipo): ?>

                <div
                    class="candidate"
                    data-name="<?= strtolower(htmlspecialchars($equipo['nombre'])) ?>"
                >

                    <div class="candidate-avatar purple-bg">
                        <?= htmlspecialchars($equipo['avatar']) ?>
                    </div>

                    <div class="candidate-info">

                        <strong>
                            <?= htmlspecialchars($equipo['nombre']) ?>
                        </strong>

                        <span>
                            <?= $equipo['participantes'] ?> participantes
                        </span>

                    </div>

                    <button
                        class="btn-vote"
                        data-candidate="<?= $equipo['id'] ?>"
                        data-category="equipo-ganador"
                    >
                        Votar
                    </button>

                </div>

            <?php endforeach; ?>

        </div>

        <button class="view-all">
            Ver todos los equipos
            <i class="fa-solid fa-arrow-right"></i>
        </button>

    </article>


    <!-- FAIR PLAY -->

    <article
        class="vote-category-card"
        data-category-card="fair-play"
        data-type="equipo"
    >

        <div class="category-card-header">

            <div class="category-icon orange-light">
                🤝
            </div>

            <div>

                <h3>
                    Fair Play
                </h3>

                <span>
                    Mejor espíritu deportivo
                </span>

            </div>

        </div>


        <div class="candidate-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                class="candidate-input"
                placeholder="Buscar equipo..."
            >

        </div>


        <div class="candidate-list">

            <?php foreach ($equipos as $equipo): ?>

                <div
                    class="candidate"
                    data-name="<?= strtolower(htmlspecialchars($equipo['nombre'])) ?>"
                >

                    <div class="candidate-avatar orange-bg">
                        <?= htmlspecialchars($equipo['avatar']) ?>
                    </div>

                    <div class="candidate-info">

                        <strong>
                            <?= htmlspecialchars($equipo['nombre']) ?>
                        </strong>

                        <span>
                            <?= $equipo['participantes'] ?> participantes
                        </span>

                    </div>

                    <button
                        class="btn-vote orange-button"
                        data-candidate="<?= $equipo['id'] ?>"
                        data-category="fair-play"
                    >
                        Votar
                    </button>

                </div>

            <?php endforeach; ?>

        </div>

        <button class="view-all">
            Ver todos los equipos
            <i class="fa-solid fa-arrow-right"></i>
        </button>

    </article>


</div>


<!-- AVISO -->

<div class="vote-notice">

    <div class="notice-icon">

        <i class="fa-solid fa-info"></i>

    </div>

    <div>

        <strong>
            Tu voto cuenta
        </strong>

        <p>
            Puedes votar una vez por cada categoría.
            Una vez registrado, tu voto no podrá modificarse.
        </p>

    </div>

    <button id="myVotesButton">

        Ver mis votos

        <i class="fa-solid fa-arrow-right"></i>

    </button>

</div>
