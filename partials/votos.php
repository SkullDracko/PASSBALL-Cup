<?php
/**
 * ============================================================
 * PASSBALL Cup - Votos
 * ============================================================
 * Vista de votaciones dentro del dashboard del participante.
 * Categorías y candidatos reales con voto único por categoría.
 * ============================================================
 */


/*
|--------------------------------------------------------------------------
| TORNEO ACTIVO
|--------------------------------------------------------------------------
*/

$torneoActivo = $pdo
    ->query("SELECT id, nombre FROM torneos WHERE estado = 'en_curso' ORDER BY id DESC LIMIT 1")
    ->fetch(PDO::FETCH_ASSOC);

if (!$torneoActivo) {
    $torneoActivo = $pdo
        ->query("SELECT id, nombre FROM torneos ORDER BY id DESC LIMIT 1")
        ->fetch(PDO::FETCH_ASSOC);
}

$torneoId      = (int) ($torneoActivo['id'] ?? 0);
$usuarioId     = (int) ($_SESSION['usuario']['id'] ?? 0);


/*
|--------------------------------------------------------------------------
| CATEGORÍAS ABIERTAS
|--------------------------------------------------------------------------
*/

$categorias = [];

if ($torneoId > 0) {
    $stmt = $pdo->prepare("
        SELECT c.id, c.clave, c.nombre, c.tipo, c.modo_candidatos, c.orden
        FROM torneo_categorias_voto c
        WHERE c.torneo_id = ? AND c.estado = 'abierta'
        ORDER BY c.orden, c.id
    ");
    $stmt->execute([$torneoId]);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalCategorias = count($categorias);


/*
|--------------------------------------------------------------------------
| GRUPO BASE DE CANDIDATOS
|--------------------------------------------------------------------------
*/

$jugadoresBase = [];
$equiposBase   = [];

if ($torneoId > 0) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.nombre, e.nombre AS equipo, e.id AS equipo_id
        FROM equipo_miembros em
        JOIN usuarios u ON u.id = em.jugador_id
        JOIN equipos e ON e.id = em.equipo_id
        JOIN torneo_equipos te ON te.equipo_id = e.id
        WHERE te.torneo_id = ? AND te.estado = 'aprobado' AND em.estado = 'activo'
        ORDER BY u.nombre
    ");
    $stmt->execute([$torneoId]);
    $jugadoresBase = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT e.id, e.nombre, e.logo
        FROM equipos e
        JOIN torneo_equipos te ON te.equipo_id = e.id
        WHERE te.torneo_id = ? AND te.estado = 'aprobado'
        ORDER BY e.nombre
    ");
    $stmt->execute([$torneoId]);
    $equiposBase = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/*
|--------------------------------------------------------------------------
| AJUSTES DE CANDIDATOS POR CATEGORÍA
|--------------------------------------------------------------------------
*/

$ajustesPorCat = [];

if (!empty($categorias)) {
    $ids  = array_map('intval', array_column($categorias, 'id'));
    $in   = implode(',', $ids);
    $stmt = $pdo->query("
        SELECT categoria_id, jugador_id, equipo_id, ajuste
        FROM torneo_categoria_candidatos
        WHERE categoria_id IN ($in)
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $aj) {
        $ajustesPorCat[(int) $aj['categoria_id']][] = $aj;
    }
}


/*
|--------------------------------------------------------------------------
| VOTOS DEL USUARIO
|--------------------------------------------------------------------------
*/

$votoJugadorPorCat = [];
$votoEquipoPorCat  = [];

if ($usuarioId > 0) {
    $stmt = $pdo->prepare("
        SELECT categoria_id, jugador_id, equipo_id
        FROM torneo_votos
        WHERE usuario_id = ?
    ");
    $stmt->execute([$usuarioId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $v) {
        $votoJugadorPorCat[(int) $v['categoria_id']] = (int) $v['jugador_id'];
        $votoEquipoPorCat[(int) $v['categoria_id']]  = (int) $v['equipo_id'];
    }
}

$misVotos = 0;
if ($usuarioId > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM torneo_votos WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    $misVotos = (int) $stmt->fetchColumn();
}


/*
|--------------------------------------------------------------------------
| TOTAL DE VOTOS DEL TORNEO
|--------------------------------------------------------------------------
*/

$totalVotosTorneo = 0;
if ($torneoId > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM torneo_votos WHERE torneo_id = ?");
    $stmt->execute([$torneoId]);
    $totalVotosTorneo = (int) $stmt->fetchColumn();
}


/*
|--------------------------------------------------------------------------
| RESOLVER CANDIDATOS FINALES DE UNA CATEGORÍA
|--------------------------------------------------------------------------
*/

function resolverCandidatos(array $categoria, array $jugadoresBase, array $equiposBase, array $ajustes): array
{
    $tipo       = $categoria['tipo'];
    $modoManual = $categoria['modo_candidatos'] === 'manual';

    if ($tipo === 'jugador') {
        $base = $jugadoresBase;
    } else {
        $base = $equiposBase;
    }

    $candIds = [];
    foreach ($base as $c) {
        $candIds[(int) $c['id']] = $c;
    }

    foreach ($ajustes as $aj) {
        $id = $tipo === 'jugador' ? (int) $aj['jugador_id'] : (int) $aj['equipo_id'];

        if (!$id) {
            continue;
        }

        if ($aj['ajuste'] === 'incluir') {
            if (!isset($candIds[$id])) {
                $candIds[$id] = ['id' => $id];  // se rellena abajo si existe
            }
        } else {
            unset($candIds[$id]);
        }
    }

    if ($modoManual) {
        $incluidos = [];
        foreach ($ajustes as $aj) {
            $id = $tipo === 'jugador' ? (int) $aj['jugador_id'] : (int) $aj['equipo_id'];
            if ($id && $aj['ajuste'] === 'incluir' && isset($candIds[$id])) {
                $incluidos[$id] = $candIds[$id];
            }
        }
        return array_values($incluidos);
    }

    return array_values($candIds);
}

?>

<div id="view-votos">

    <!-- =====================================================
         ENCABEZADO
         ===================================================== -->

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


    <!-- =====================================================
         ESTADÍSTICAS
         ===================================================== -->

    <div class="vote-stats">

        <!-- CATEGORÍAS -->

        <div class="vote-stat-card">

            <div class="stat-icon purple">
                <i class="fa-solid fa-users"></i>
            </div>

            <div>

                <strong>
                    <?= $totalCategorias ?>
                </strong>

                <span>
                    CATEGORÍAS ACTIVAS
                </span>

                <small>
                    En las que puedes votar
                </small>

            </div>

        </div>


        <!-- VOTOS REALIZADOS -->

        <div class="vote-stat-card">

            <div class="stat-icon orange">
                <i class="fa-solid fa-users"></i>
            </div>

            <div>

                <strong id="votesMade">
                    <?= $misVotos ?>
                </strong>

                <span>
                    VOTOS REALIZADOS
                </span>

                <small>
                    Gracias por participar
                </small>

            </div>

        </div>


        <!-- EQUIPOS -->

        <div class="vote-stat-card">

            <div class="stat-icon purple">
                <i class="fa-regular fa-shield-halved"></i>
            </div>

            <div>

                <strong>
                    <?= count($equiposBase) ?>
                </strong>

                <span>
                    EQUIPOS EN TORNEO
                </span>

                <small>
                    Participantes
                </small>

            </div>

        </div>


        <!-- TOTAL -->

        <div class="vote-stat-card">

            <div class="stat-icon orange">
                <i class="fa-regular fa-star"></i>
            </div>

            <div>

                <strong>
                    <?= $totalVotosTorneo ?>
                </strong>

                <span>
                    TOTAL DE VOTOS
                </span>

                <small>
                    En todo el torneo
                </small>

            </div>

        </div>

    </div>


    <?php if ($totalCategorias === 0): ?>

        <div class="section-title">

            <h2>
                Categorías de votación
            </h2>

        </div>

        <div class="vote-notice" style="margin-top:0;">

            <div class="notice-icon">
                <i class="fa-solid fa-info"></i>
            </div>

            <div>

                <strong>
                    Aún no hay votaciones abiertas
                </strong>

                <p>
                    El comité abrirá las votaciones próximamente.
                </p>

            </div>

        </div>

    <?php else: ?>

    <!-- =====================================================
         CATEGORÍAS
         ===================================================== -->

    <div class="section-title">

        <h2>
            Categorías de votación
        </h2>

    </div>


    <div class="category-tabs">

        <!-- TODAS -->

        <button
            type="button"
            class="category-tab active"
            data-category="all"
        >

            <i class="fa-solid fa-layer-group"></i>

            <div>

                <strong>
                    Todas
                </strong>

                <span>
                    Ver todas
                </span>

            </div>

        </button>


        <?php foreach ($categorias as $categoria): ?>

            <button
                type="button"
                class="category-tab"
                data-category="<?= (int) $categoria['id'] ?>"
            >

                <i class="fa-solid fa-circle-check"></i>

                <div>

                    <strong>
                        <?= htmlspecialchars($categoria['nombre']) ?>
                    </strong>

                    <span>
                        <?= $categoria['tipo'] === 'jugador' ? 'Jugador' : 'Equipo' ?>
                    </span>

                </div>

            </button>

        <?php endforeach; ?>

    </div>


    <!-- =====================================================
         BUSCADOR GENERAL
         ===================================================== -->

    <div class="search-row">

        <label for="voteSearch">
            Buscar para votar
        </label>

        <div class="vote-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                id="voteSearch"
                placeholder="Buscar jugador o equipo..."
                autocomplete="off"
            >

        </div>

    </div>


    <!-- =====================================================
         GRID DE VOTACIONES
         ===================================================== -->

    <div
        class="vote-category-grid"
        id="voteCategoryGrid"
    >

        <?php foreach ($categorias as $categoria): ?>

            <?php
            $catId       = (int) $categoria['id'];
            $esJugador   = $categoria['tipo'] === 'jugador';
            $candidatos  = resolverCandidatos(
                $categoria,
                $jugadoresBase,
                $equiposBase,
                $ajustesPorCat[$catId] ?? []
            );
            $colorAlt    = $esJugador ? 'purple' : 'orange';
            $miVoto      = $esJugador
                ? ($votoJugadorPorCat[$catId] ?? 0)
                : ($votoEquipoPorCat[$catId] ?? 0);
            ?>

            <article
                class="vote-category-card"
                data-category-card="<?= $catId ?>"
                data-tipo="<?= $categoria['tipo'] ?>"
            >

                <div class="category-card-header">

                    <div class="category-icon <?= $colorAlt ?>-light">
                        <?= $esJugador ? '⚽' : '👥' ?>
                    </div>

                    <div>

                        <h3>
                            <?= htmlspecialchars($categoria['nombre']) ?>
                        </h3>

                        <span>
                            Vota una vez por esta categoría
                        </span>

                    </div>

                </div>


                <div class="candidate-search">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        class="candidate-input"
                        placeholder="Buscar <?= $esJugador ? 'jugador' : 'equipo' ?>..."
                        autocomplete="off"
                    >

                </div>


                <div class="candidate-list">

                    <?php foreach ($candidatos as $candidato): ?>

                        <?php
                        $candId     = (int) $candidato['id'];
                        $nombre     = $candidato['nombre'] ?? '—';
                        $subtexto   = $esJugador
                            ? ($candidato['equipo'] ?? '')
                            : (isset($candidato['logo']) && $candidato['logo'] ? 'Equipo' : 'Equipo');
                        $estaVotado = $miVoto === $candId;
                        $avatarCss  = $esJugador
                            ? mb_strtoupper(mb_substr($nombre, 0, 2))
                            : mb_strtoupper(mb_substr($nombre, 0, 1));
                        ?>

                        <div
                            class="candidate <?= $estaVotado ? 'voted' : '' ?>"
                            data-name="<?= htmlspecialchars(strtolower($nombre . ' ' . $subtexto), ENT_QUOTES, 'UTF-8') ?>"
                        >

                            <div class="candidate-avatar <?= $colorAlt ?>-bg">
                                <?= $esJugador
                                    ? htmlspecialchars($avatarCss)
                                    : htmlspecialchars($avatarCss) ?>
                            </div>

                            <div class="candidate-info">

                                <strong>
                                    <?= htmlspecialchars($nombre) ?>
                                </strong>

                                <span>
                                    <?= htmlspecialchars($subtexto) ?>
                                </span>

                            </div>

                            <button
                                type="button"
                                class="btn-vote <?= $colorAlt === 'orange' ? 'orange-button' : '' ?>"
                                data-category="<?= $catId ?>"
                                data-candidate="<?= $candId ?>"
                                <?= $estaVotado ? 'disabled' : '' ?>
                            >
                                <?= $estaVotado ? '✓ Votado' : 'Votar' ?>
                            </button>

                        </div>

                    <?php endforeach; ?>

                    <?php if (empty($candidatos)): ?>

                        <div class="candidate">
                            <div class="candidate-info">
                                <strong>Sin candidatos</strong>
                                <span>Espera la configuración</span>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

    <?php endif; ?>


    <!-- =====================================================
         AVISO
         ===================================================== -->

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
                Registrarás tu voto de forma definitiva al dar clic.
            </p>

        </div>

    </div>

</div>