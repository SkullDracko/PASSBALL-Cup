<?php
/**
 * ============================================================
 * PASSBALL Cup - Comunidad
 * ============================================================
 * Vista Comunidad dentro del dashboard.
 * ============================================================
 */

/* ============================================================
   DATOS TEMPORALES
   ============================================================ */

$comunidadStats = [
    [
        'valor' => 128,
        'titulo' => 'Miembros',
        'descripcion' => 'En la comunidad',
        'icono' => 'fa-solid fa-users',
        'color' => 'purple'
    ],
    [
        'valor' => 34,
        'titulo' => 'Publicaciones',
        'descripcion' => 'Este mes',
        'icono' => 'fa-solid fa-comment',
        'color' => 'orange'
    ],
    [
        'valor' => 256,
        'titulo' => 'Me gusta',
        'descripcion' => 'Este mes',
        'icono' => 'fa-solid fa-thumbs-up',
        'color' => 'purple'
    ],
    [
        'valor' => 89,
        'titulo' => 'Comentarios',
        'descripcion' => 'Este mes',
        'icono' => 'fa-solid fa-comments',
        'color' => 'orange'
    ]
];

$publicaciones = [
    [
        'id' => 1,
        'equipo' => 'Águilas FC',
        'autor' => 'Águilas FC',
        'tiempo' => 'Hace 2 horas',
        'avatar' => '🦅',
        'texto' => '¡Gran victoria el día de hoy! 💪⚽',
        'descripcion' => 'Partido muy intenso y el equipo demostró entrega durante los 15 minutos.',
        'hashtags' => '#VamosÁguilas 🦅💜',
        'likes' => 31,
        'comentarios' => 8,
        'imagenes' => [
            'https://images.unsplash.com/photo-1517466787929-bc90951d0974?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?auto=format&fit=crop&w=700&q=80'
        ]
    ],
    [
        'id' => 2,
        'equipo' => 'Tigres FC',
        'autor' => 'Tigres FC',
        'tiempo' => 'Hace 5 horas',
        'avatar' => '🐯',
        'texto' => 'Buen empate en un partido muy disputado.',
        'descripcion' => 'Seguimos trabajando y preparándonos para nuestro próximo encuentro. 🧡🐯',
        'hashtags' => '#VamosTigres',
        'likes' => 18,
        'comentarios' => 5,
        'imagenes' => [
            'https://images.unsplash.com/photo-1552318965-6e6be7484ada?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1431324155629-1a6deb1dec8d?auto=format&fit=crop&w=700&q=80'
        ]
    ]
];

$eventos = [
    [
        'dia' => '25',
        'mes' => 'MAY',
        'titulo' => 'Reunión de capitanes',
        'hora' => '10:00 AM',
        'lugar' => 'Sala de reuniones'
    ],
    [
        'dia' => '28',
        'mes' => 'MAY',
        'titulo' => 'Torneo amistoso',
        'hora' => '09:00 AM',
        'lugar' => 'Cancha Principal'
    ],
    [
        'dia' => '01',
        'mes' => 'JUN',
        'titulo' => 'Fiesta de clausura',
        'hora' => '06:00 PM',
        'lugar' => 'Área social'
    ]
];

$miembros = [
    [
        'nombre' => 'Carlos Mendoza',
        'equipo' => 'Administrador',
        'rol' => 'Admin',
        'tipo' => 'admin',
        'avatar' => '👨🏻'
    ],
    [
        'nombre' => 'María González',
        'equipo' => 'Águilas FC',
        'rol' => 'Capitán',
        'tipo' => 'capitan',
        'avatar' => '👩🏻'
    ],
    [
        'nombre' => 'Juan Pérez',
        'equipo' => 'Tigres FC',
        'rol' => 'Capitán',
        'tipo' => 'capitan',
        'avatar' => '👨🏻'
    ],
    [
        'nombre' => 'Luis Ramírez',
        'equipo' => 'Lobos FC',
        'rol' => 'Miembro',
        'tipo' => 'miembro',
        'avatar' => '👨🏻'
    ],
    [
        'nombre' => 'Ana Torres',
        'equipo' => 'Real Passball',
        'rol' => 'Miembro',
        'tipo' => 'miembro',
        'avatar' => '👩🏻'
    ]
];

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
                 CREAR PUBLICACIÓN
                 ================================================== -->

            <article class="create-post-card">

                <div class="create-post-top">

                    <div class="current-user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div class="create-post-content">

                        <strong>
                            ¿Qué quieres compartir?
                        </strong>

                        <textarea
                            id="postContent"
                            placeholder="Comparte algo con la comunidad de PASSBALL Cup..."
                            maxlength="500"
                        ></textarea>

                    </div>

                </div>


                <div class="create-post-bottom">

                    <div class="post-actions">

                        <button
                            type="button"
                            class="post-type-button image"
                            data-type="imagen"
                        >
                            <i class="fa-solid fa-image"></i>
                            Imagen
                        </button>

                        <button
                            type="button"
                            class="post-type-button video"
                            data-type="video"
                        >
                            <i class="fa-solid fa-play"></i>
                            Video
                        </button>

                        <button
                            type="button"
                            class="post-type-button poll"
                            data-type="encuesta"
                        >
                            <i class="fa-solid fa-square-poll-vertical"></i>
                            Encuesta
                        </button>

                        <button
                            type="button"
                            class="post-type-button event"
                            data-type="evento"
                        >
                            <i class="fa-regular fa-calendar"></i>
                            Evento
                        </button>

                    </div>

                    <button
                        type="button"
                        class="publish-button"
                        id="publishPost"
                    >
                        Publicar
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>

                </div>

                <div
                    class="post-message"
                    id="postMessage"
                ></div>

            </article>


            <!-- ==================================================
                 PUBLICACIONES
                 ================================================== -->

            <section class="posts-section">

                <div class="section-heading">

                    <div>

                        <i class="fa-regular fa-newspaper"></i>

                        <h2>
                            Publicaciones recientes
                        </h2>

                    </div>

                    <select
                        id="postSort"
                        class="post-sort"
                    >

                        <option value="recent">
                            Más recientes
                        </option>

                        <option value="popular">
                            Más populares
                        </option>

                    </select>

                </div>


                <div
                    class="posts-list"
                    id="postsList"
                >

                    <?php foreach ($publicaciones as $publicacion): ?>

                        <article
                            class="community-post"
                            data-likes="<?= $publicacion['likes'] ?>"
                        >

                            <!-- CABECERA -->

                            <div class="post-header">

                                <div class="post-user-avatar">

                                    <?= $publicacion['avatar'] ?>

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
                                        <?= htmlspecialchars(
                                            $publicacion['tiempo'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        <i class="fa-solid fa-earth-americas"></i>
                                    </span>

                                </div>

                                <button
                                    type="button"
                                    class="post-menu"
                                    title="Más opciones"
                                >
                                    <i class="fa-solid fa-ellipsis"></i>
                                </button>

                            </div>


                            <!-- TEXTO -->

                            <div class="post-body">

                                <p class="post-main-text">
                                    <?= htmlspecialchars(
                                        $publicacion['texto'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                                <p class="post-description">
                                    <?= htmlspecialchars(
                                        $publicacion['descripcion'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                                <p class="post-hashtags">
                                    <?= htmlspecialchars(
                                        $publicacion['hashtags'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                            </div>


                            <!-- IMÁGENES -->

                            <?php if (!empty($publicacion['imagenes'])): ?>

                                <div
                                    class="post-gallery gallery-<?= count($publicacion['imagenes']) ?>"
                                >

                                    <?php foreach (
                                        $publicacion['imagenes']
                                        as $index => $imagen
                                    ): ?>

                                        <div class="post-image">

                                            <img
                                                src="<?= htmlspecialchars(
                                                    $imagen,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                alt="Imagen de publicación"
                                                loading="lazy"
                                            >

                                            <?php if (
                                                count($publicacion['imagenes']) > 4 &&
                                                $index === 3
                                            ): ?>

                                                <div class="more-images">
                                                    +<?= count($publicacion['imagenes']) - 4 ?>
                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>


                            <!-- REACCIONES -->

                            <div class="post-reactions">

                                <div class="reaction-summary">

                                    <span class="reaction-icons">

                                        <span class="reaction-like">
                                            <i class="fa-solid fa-thumbs-up"></i>
                                        </span>

                                        <span class="reaction-heart">
                                            <i class="fa-solid fa-heart"></i>
                                        </span>

                                        <span class="reaction-wow">
                                            <i class="fa-solid fa-face-surprise"></i>
                                        </span>

                                    </span>

                                    <span>
                                        Tú, Juan Pérez y
                                        <?= $publicacion['likes'] ?> personas más
                                    </span>

                                </div>

                                <span>
                                    <?= $publicacion['comentarios'] ?>
                                    comentarios
                                </span>

                            </div>


                            <!-- ACCIONES -->

                            <div class="post-buttons">

                                <button
                                    type="button"
                                    class="post-action like-button"
                                >

                                    <i class="fa-regular fa-thumbs-up"></i>

                                    <span>
                                        Me gusta
                                    </span>

                                </button>


                                <button
                                    type="button"
                                    class="post-action comment-button"
                                >

                                    <i class="fa-regular fa-comment"></i>

                                    <span>
                                        Comentar
                                    </span>

                                </button>


                                <button
                                    type="button"
                                    class="post-action share-button"
                                >

                                    <i class="fa-solid fa-share"></i>

                                    <span>
                                        Compartir
                                    </span>

                                </button>

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
                 PRÓXIMOS EVENTOS
                 ================================================== -->

            <article class="community-card events-card">

                <div class="community-card-heading">

                    <div>

                        <i class="fa-regular fa-calendar"></i>

                        <h3>
                            Próximos eventos
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


                <button
                    type="button"
                    class="community-card-link"
                >

                    Ver calendario completo

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </article>


            <!-- ==================================================
                 MIEMBROS DESTACADOS
                 ================================================== -->

            <article class="community-card members-card">

                <div class="community-card-heading">

                    <div>

                        <i class="fa-solid fa-users"></i>

                        <h3>
                            Miembros destacados
                        </h3>

                    </div>

                </div>


                <div class="members-list">

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


                <button
                    type="button"
                    class="community-card-link"
                >

                    Ver todos los miembros

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </article>

        </aside>

    </div>

</div>
