<?php
/**
 * ============================================================
 * PASSBALL Cup - Comunidad
 * ============================================================
 * Vista Comunidad dentro del dashboard del participante.
 * Solo el admin publica; el participante reacciona.
 * ============================================================
 */


/*
|--------------------------------------------------------------------------
| ESTADÍSTICAS
|--------------------------------------------------------------------------
*/

$totalMiembros = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'activo'")->fetchColumn();
$totalPosts    = (int) $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalLikes    = (int) $pdo->query("SELECT COALESCE(SUM(likes), 0) FROM posts")->fetchColumn();
$totalReacc    = (int) $pdo->query("SELECT COUNT(*) FROM post_reacciones")->fetchColumn();

$comunidadStats = [
    [
        'valor' => $totalMiembros,
        'titulo' => 'Miembros',
        'descripcion' => 'En la comunidad',
        'icono' => 'fa-solid fa-users',
        'color' => 'purple',
    ],
    [
        'valor' => $totalPosts,
        'titulo' => 'Publicaciones',
        'descripcion' => 'Del comité',
        'icono' => 'fa-solid fa-comment',
        'color' => 'orange',
    ],
    [
        'valor' => $totalLikes,
        'titulo' => 'Me gusta',
        'descripcion' => 'En total',
        'icono' => 'fa-solid fa-thumbs-up',
        'color' => 'purple',
    ],
    [
        'valor' => $totalReacc,
        'titulo' => 'Reacciones',
        'descripcion' => 'En total',
        'icono' => 'fa-solid fa-heart',
        'color' => 'orange',
    ],
];


/*
|--------------------------------------------------------------------------
| PUBLICACIONES (solo admin publica)
|--------------------------------------------------------------------------
*/

$publicaciones = [];
$stmt = $pdo->query("
    SELECT p.id, p.titulo, p.contenido, p.imagen_url, p.likes, p.fijado, p.fecha,
           u.nombre AS autor, u.avatar
    FROM posts p
    JOIN usuarios u ON u.id = p.usuario_id
    ORDER BY p.fijado DESC, p.fecha DESC
    LIMIT 50
");

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $post) {
    $publicaciones[] = [
        'id'       => (int) $post['id'],
        'titulo'   => $post['titulo'],
        'texto'    => $post['contenido'],
        'imagen'   => $post['imagen_url'],
        'likes'    => (int) $post['likes'],
        'fijado'   => (int) $post['fijado'],
        'autor'    => $post['autor'] ?? 'Comité PASSBALL',
        'avatar'   => $post['avatar'],
        'tiempo'   => timeAgo($post['fecha']),
        'reacciones' => [],  // counts per tipo
    ];
}

function timeAgo(string $fecha): string
{
    $ts   = strtotime($fecha);
    $diff = time() - $ts;

    if ($diff < 60)       return 'Hace un momento';
    if ($diff < 3600)     return 'Hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)    return 'Hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800)   return 'Hace ' . floor($diff / 86400) . ' d';
    return date('d M Y', $ts);
}

// Reacciones del post para el participante actual
$usuarioId = $_SESSION['usuario']['id'] ?? 0;
$reaccionesUsuario = [];

if ($usuarioId > 0 && !empty($publicaciones)) {
    $ids = array_map('intval', array_column($publicaciones, 'id'));
    $in  = implode(',', $ids);
    $stmt = $pdo->query("
        SELECT post_id, tipo FROM post_reacciones
        WHERE usuario_id = $usuarioId AND post_id IN ($in)
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $reaccionesUsuario[(int) $r['post_id']] = $r['tipo'];
    }
}


/*
|--------------------------------------------------------------------------
| PRÓXIMOS PARTIDOS (eventos)
|--------------------------------------------------------------------------
*/

$eventos = [];
$stmt = $pdo->query("
    SELECT p.fecha_hora, p.cancha, p.goles_local, p.goles_visitante,
           l.nombre AS local, v.nombre AS visitante
    FROM partidos p
    JOIN torneo_rondas r ON r.id = p.ronda_id
    LEFT JOIN equipos l ON l.id = p.equipo_local_id
    LEFT JOIN equipos v ON v.id = p.equipo_visitante_id
    WHERE p.estado = 'programado'
    ORDER BY p.fecha_hora ASC
    LIMIT 5
");

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $partido) {
    if (!empty($partido['fecha_hora'])) {
        $ts = strtotime($partido['fecha_hora']);
        $eventos[] = [
            'dia'   => date('d', $ts),
            'mes'   => strtoupper(date('M', $ts)),
            'titulo' => trim(($partido['local'] ?? '—') . ' vs ' . ($partido['visitante'] ?? '—')),
            'hora'  => date('g:i A', $ts),
            'lugar' => $partido['cancha'] ?: 'Por definir',
        ];
    }
}

if (empty($eventos)) {
    $eventos[] = [
        'dia'   => date('d'),
        'mes'   => strtoupper(date('M')),
        'titulo' => 'Sin partidos programados',
        'hora'  => '—',
        'lugar' => 'Vuelve pronto',
    ];
}


/*
|--------------------------------------------------------------------------
| MIEMBROS DESTACADOS (capitanes)
|--------------------------------------------------------------------------
*/

$miembros = [];
$stmt = $pdo->query("
    SELECT u.nombre, e.nombre AS equipo, u.id AS usuario_id
    FROM equipos e
    JOIN usuarios u ON u.id = e.capitan_id
    WHERE e.estado = 'activo'
    ORDER BY e.nombre
    LIMIT 6
");

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
    $miembros[] = [
        'nombre'  => $m['nombre'],
        'equipo'  => $m['equipo'],
        'rol'     => 'Capitán',
        'tipo'    => 'capitan',
        'avatar'  => '<i class="fa-solid fa-user"></i>',
        'usuario_id' => (int) $m['usuario_id'],
    ];
}

?>

<!-- ============================================================
     COMUNIDAD - CONTENEDOR PRINCIPAL
     ============================================================ -->

<div class="comunidad-page" id="view-comunidad">

    <!-- ========================================================
         ENCABEZADO
         ======================================================== -->

    <div class="page-header comunidad-header">

        <div class="comunidad-title-icon">
            <i class="fa-solid fa-comments"></i>
        </div>

        <div>

            <h1>Comunidad</h1>

            <p>
                Comparte, comenta y vive la pasión del torneo.
            </p>

        </div>

    </div>


    <!-- ========================================================
         ESTADÍSTICAS
         ======================================================== -->

    <section class="stats-grid comunidad-stats">

        <?php foreach ($comunidadStats as $stat): ?>

            <article class="stat-card comunidad-stat-card">

                <div class="stat-icon <?= $stat['color'] ?>">

                    <i class="<?= $stat['icono'] ?>"></i>

                </div>

                <div class="stat-info">

                    <strong>
                        <?= $stat['valor'] ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(
                            $stat['titulo'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <small>
                        <?= htmlspecialchars(
                            $stat['descripcion'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </small>

                </div>

            </article>

        <?php endforeach; ?>

    </section>


    <!-- ========================================================
         CONTENIDO PRINCIPAL
         ======================================================== -->

    <div class="comunidad-layout">


        <!-- ====================================================
             COLUMNA PRINCIPAL
             ==================================================== -->

        <main class="comunidad-main">


            <!-- ==================================================
                 PUBLICACIONES
                 ================================================== -->

            <section class="posts-section">

                <div class="section-heading">

                    <div>

                        <i class="fa-regular fa-newspaper"></i>

                        <h2>
                            Publicaciones del comité
                        </h2>

                    </div>

                </div>


                <div
                    class="posts-list"
                    id="postsList"
                >

                    <?php if (empty($publicaciones)): ?>

                        <article class="community-post">

                            <div class="post-body">

                                <p class="post-description">
                                    Aún no hay publicaciones. Vuelve pronto.
                                </p>

                            </div>

                        </article>

                    <?php endif; ?>

                    <?php foreach ($publicaciones as $publicacion): ?>

                        <article
                            class="community-post"
                            data-post-id="<?= $publicacion['id'] ?>"
                            data-likes="<?= $publicacion['likes'] ?>"
                        >

                            <!-- CABECERA -->

                            <div class="post-header">

                                <div class="post-user-avatar">

                                    <?= $publicacion['avatar']
                                        ? '<img src="' . htmlspecialchars($publicacion['avatar'], ENT_QUOTES, 'UTF-8') . '" alt="" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">'
                                        : '<i class="fa-solid fa-user-shield"></i>' ?>

                                </div>

                                <div class="post-user-info">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $publicacion['autor'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>

                                    <span>
                                        Comité organizador · <?= htmlspecialchars($publicacion['tiempo'], ENT_QUOTES, 'UTF-8') ?>
                                        <i class="fa-solid fa-earth-americas"></i>
                                    </span>

                                </div>

                                <?php if ($publicacion['fijado']): ?>
                                    <button
                                        type="button"
                                        class="post-menu"
                                        title="Publicación fijada"
                                        style="cursor:default;"
                                    >
                                        <i class="fa-solid fa-thumbtack"></i>
                                    </button>
                                <?php endif; ?>

                            </div>


                            <!-- TEXTO -->

                            <div class="post-body">

                                <?php if ($publicacion['titulo'] !== ''): ?>
                                    <p class="post-main-text">
                                        <?= htmlspecialchars(
                                            $publicacion['titulo'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>
                                <?php endif; ?>

                                <p class="post-description">
                                    <?= nl2br(htmlspecialchars(
                                        $publicacion['texto'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )) ?>
                                </p>

                            </div>


                            <!-- IMAGEN -->

                            <?php if (!empty($publicacion['imagen'])): ?>

                                <div class="post-gallery gallery-1">

                                    <div class="post-image">

                                        <img
                                            src="<?= htmlspecialchars(
                                                $publicacion['imagen'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            alt="Imagen de publicación"
                                            loading="lazy"
                                        >

                                    </div>

                                </div>

                            <?php endif; ?>


                            <!-- REACCIONES -->

                            <div class="post-reactions">

                                <div class="reaction-summary">

                                    <span>
                                        <i class="fa-solid fa-thumbs-up" style="color:#3187e8;"></i>
                                        <strong data-likes-count><?= $publicacion['likes'] ?></strong> reacciones
                                    </span>

                                </div>

                                <span>
                                    <?= $publicacion['fijado'] ? 'Fijado por el comité' : '' ?>
                                </span>

                            </div>


                            <!-- ACCIONES -->

                            <div class="post-buttons">

                                <?php
                                $miReaccion = $reaccionesUsuario[$publicacion['id']] ?? null;
                                $tipos = [
                                    'like'        => ['fa-thumbs-up', 'Me gusta'],
                                    'me_encanta'  => ['fa-heart', 'Me encanta'],
                                    'me_asombra'  => ['fa-face-surprise', 'Me asombra'],
                                ];
                                foreach ($tipos as $tipoReac => $def):
                                    $activa = $miReaccion === $tipoReac;
                                ?>

                                    <button
                                        type="button"
                                        class="post-action react-button <?= $activa ? 'liked' : '' ?>"
                                        data-post-id="<?= $publicacion['id'] ?>"
                                        data-tipo="<?= $tipoReac ?>"
                                    >

                                        <i class="<?= $activa ? 'fa-solid' : 'fa-regular' ?> <?= $def[0] ?>"></i>

                                        <span>
                                            <?= $def[1] ?>
                                        </span>

                                    </button>

                                <?php endforeach; ?>

                            </div>


                            <!-- COMENTARIOS -->

                            <div class="comments-area">

                                <input
                                    type="text"
                                    class="comment-input"
                                    placeholder="Escribe un comentario..."
                                >

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

        </main>


        <!-- ====================================================
             COLUMNA DERECHA
             ==================================================== -->

        <aside class="comunidad-sidebar">


            <!-- ==================================================
                 PRÓXIMOS PARTIDOS
                 ================================================== -->

            <article class="community-card events-card">

                <div class="community-card-heading">

                    <div>

                        <i class="fa-regular fa-calendar"></i>

                        <h3>
                            Próximos partidos
                        </h3>

                    </div>

                </div>


                <div class="events-list">

                    <?php foreach ($eventos as $evento): ?>

                        <div class="event-item">

                            <div class="event-date">

                                <strong>
                                    <?= $evento['dia'] ?>
                                </strong>

                                <span>
                                    <?= $evento['mes'] ?>
                                </span>

                            </div>


                            <div class="event-info">

                                <strong>
                                    <?= htmlspecialchars(
                                        $evento['titulo'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <span>
                                    <i class="fa-regular fa-clock"></i>
                                    <?= $evento['hora'] ?>
                                </span>

                                <span>
                                    <i class="fa-solid fa-location-dot"></i>
                                    <?= htmlspecialchars(
                                        $evento['lugar'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </article>


            <!-- ==================================================
                 MIEMBROS DESTACADOS
                 ================================================== -->

            <article class="community-card members-card">

                <div class="community-card-heading">

                    <div>

                        <i class="fa-solid fa-users"></i>

                        <h3>
                            Capitanes
                        </h3>

                    </div>

                </div>


                <div class="members-list">

                    <?php if (empty($miembros)): ?>

                        <div class="member-item">

                            <div class="member-info">

                                <strong>Aún no hay equipos</strong>

                            </div>

                        </div>

                    <?php endif; ?>

                    <?php foreach ($miembros as $miembro): ?>

                        <div class="member-item">

                            <div class="member-avatar">

                                <?= $miembro['avatar'] ?>

                            </div>


                            <div class="member-info">

                                <strong>
                                    <?= htmlspecialchars(
                                        $miembro['nombre'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <span>
                                    <?= htmlspecialchars(
                                        $miembro['equipo'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </div>


                            <div
                                class="member-role <?= $miembro['tipo'] ?>"
                            >
                                <?= htmlspecialchars(
                                    $miembro['rol'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>


                            <span class="online-dot"></span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </article>

        </aside>

    </div>

</div>