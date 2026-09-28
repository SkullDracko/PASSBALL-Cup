<?php
/**
 * PASSBALL Cup - Admin: Comunidad
 * Publica y administra posts del torneo
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$posts = $pdo->query("
    SELECT p.id, p.titulo, p.contenido, p.imagen_url, p.fijado, p.likes, p.fecha,
           u.nombre AS autor
    FROM posts p
    LEFT JOIN usuarios u ON u.id = p.usuario_id
    ORDER BY p.fijado DESC, p.fecha DESC
")->fetchAll(PDO::FETCH_ASSOC);

function timeAgoAdmin(string $fecha): string
{
    $ts = strtotime($fecha);
    $diff = time() - $ts;

    if ($diff < 60)       return 'Hace un momento';
    if ($diff < 3600)     return 'Hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)    return 'Hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800)   return 'Hace ' . floor($diff / 86400) . ' d';
    return date('d M Y', $ts);
}
?>
<?php if ($flashSuccess): ?>
<div class="admin-alert is-ok">
<strong><?= htmlspecialchars($flashSuccess) ?></strong>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="admin-alert is-error">
<strong><?= htmlspecialchars($flashError) ?></strong>
</div>
<?php endif; ?>
<div class="admin-view-head">
<span class="admin-view-head-ico"><i class="fa-solid fa-comment-dots"></i></span>
<div>
<h2 class="admin-section-title">Comunidad</h2>
<p class="admin-section-sub">Publica novedades y administra las publicaciones del torneo.</p>
</div>
</div>
<!-- Formulario crear post -->
<div class="comunidad-layout">

    <section class="admin-card composer">
        <div class="composer-head">
            <span class="composer-head-ico"><i class="fa-solid fa-pen-to-square"></i></span>
            <div>
                <h3>Nueva publicación</h3>
                <p>Comparte una novedad con los participantes</p>
            </div>
        </div>

        <form class="admin-form" method="POST" action="controllers/comunidad.php">
            <input type="hidden" name="action" value="crear_post">
            <div class="field">
                <label>Título</label>
                <input type="text" name="titulo" placeholder="Ej. Horarios de la jornada" required>
            </div>
            <div class="field">
                <label>Contenido</label>
                <textarea name="contenido" rows="6" placeholder="Escribe el contenido de la publicación..." required></textarea>
            </div>
            <div class="field">
                <label>URL de imagen <em>(opcional)</em></label>
                <input type="url" name="imagen_url" placeholder="https://...">
            </div>
            <div class="composer-foot">
                <label class="admin-check">
                    <input type="checkbox" name="fijado" value="1"> Fijar publicación
                </label>
                <button type="submit" class="admin-btn">
                    <i class="fa-solid fa-paper-plane"></i> Publicar
                </button>
            </div>
        </form>
    </section>

    <!-- Listado de posts -->
    <section class="admin-card">
        <div class="feed-head">
            <h3>Publicaciones</h3>
            <span class="feed-count"><?= count($posts) ?></span>
        </div>

        <?php if (empty($posts)): ?>
        <div class="admin-stub compact">
            <h3>No hay publicaciones aún</h3>
            <p>Usa el formulario para publicar la primera novedad.</p>
        </div>
        <?php else: ?>
        <div class="feed">
        <?php foreach ($posts as $post): ?>
            <article class="post<?= $post['fijado'] ? ' pinned' : '' ?>">
                <span class="post-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr(trim($post['autor'] ?? 'P'), 0, 1))) ?></span>
                <div class="post-body">
                    <div class="post-meta">
                        <strong><?= htmlspecialchars($post['autor'] ?? '—') ?></strong>
                        <span class="dot">&bull;</span>
                        <span class="post-time"><?= timeAgoAdmin($post['fecha']) ?></span>
                        <?php if ($post['fijado']): ?>
                        <span class="post-pin"><i class="fa-solid fa-thumbtack"></i> FIJADO</span>
                        <?php endif; ?>
                    </div>
                    <h4 class="post-title"><?= htmlspecialchars($post['titulo']) ?></h4>
                    <p class="post-text"><?= nl2br(htmlspecialchars($post['contenido'])) ?></p>
                    <?php if ($post['imagen_url']): ?>
                    <div class="post-media">
                        <img src="<?= htmlspecialchars($post['imagen_url']) ?>" alt="Imagen de la publicación">
                    </div>
                    <?php endif; ?>
                    <div class="post-foot">
                        <span class="post-likes">
                            <i class="fa-solid fa-thumbs-up"></i> <?= (int) $post['likes'] ?>
                        </span>
                        <div class="post-acts">
                            <form method="POST" action="controllers/comunidad.php" class="admin-form nostyle">
                                <input type="hidden" name="action" value="toggle_fijado">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <button type="submit" class="admin-btn ghost mini">
                                    <i class="fa-solid fa-thumbtack"></i>
                                    <?= $post['fijado'] ? 'Desfijar' : 'Fijar' ?>
                                </button>
                            </form>
                            <form method="POST" action="controllers/comunidad.php" class="admin-form nostyle" onsubmit="return confirm('Eliminar esta publicación?');">
                                <input type="hidden" name="action" value="eliminar_post">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <button type="submit" class="admin-btn ghost mini danger">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

</div>
