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
    <div class="admin-stub" style="padding:14px 18px; background:#e8f7ee; color:#1a7f3a; margin-bottom:18px;">
        <strong><?= htmlspecialchars($flashSuccess) ?></strong>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="admin-stub" style="padding:14px 18px; background:#fdeeee; color:#b3261e; margin-bottom:18px;">
        <strong><?= htmlspecialchars($flashError) ?></strong>
    </div>
<?php endif; ?>

<h2 class="admin-section-title">Comunidad</h2>
<p class="admin-section-sub">Publica novedades y administra las publicaciones del torneo.</p>

<!-- Formulario crear post -->
<div style="background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(47,30,80,0.07); padding:18px; margin-bottom:22px;">
    <h3 style="margin:0 0 14px; font-size:15px; font-weight:800;">Nueva publicación</h3>
    <form class="admin-form" method="POST" action="controllers/comunidad.php">
        <input type="hidden" name="action" value="crear_post">

        <div class="field" style="margin-bottom:10px;">
            <label>Título</label>
            <input type="text" name="titulo" placeholder="Ej. Horarios de la jornada" required style="width:100%;">
        </div>

        <div class="field" style="margin-bottom:10px;">
            <label>Contenido</label>
            <textarea name="contenido" rows="4" placeholder="Escribe el contenido de la publicación..." required style="width:100%; resize:vertical;"></textarea>
        </div>

        <div class="field" style="margin-bottom:10px;">
            <label>URL de imagen (opcional)</label>
            <input type="url" name="imagen_url" placeholder="https://..." style="width:100%;">
        </div>

        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <label style="display:flex; align-items:center; gap:6px; font-size:12px; cursor:pointer;">
                <input type="checkbox" name="fijado" value="1"> Fijar publicación
            </label>
            <button type="submit" class="admin-btn">Publicar</button>
        </div>
    </form>
</div>

<!-- Listado de posts -->
<div style="background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(47,30,80,0.07); padding:14px;">
    <h3 style="margin:0 0 12px; font-size:14px; font-weight:800;">Publicaciones (<?= count($posts) ?>)</h3>

    <?php if (empty($posts)): ?>
        <div class="admin-stub" style="padding:20px;">
            <p>No hay publicaciones aún.</p>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div style="border:1px solid #f0edf4; border-radius:10px; padding:14px; margin-bottom:10px;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">
                    <div style="min-width:0; flex:1;">
                        <div style="font-size:11px; color:#888; margin-bottom:4px;">
                            <?= htmlspecialchars($post['autor'] ?? '—') ?>
                            · <?= timeAgoAdmin($post['fecha']) ?>
                            <?php if ($post['fijado']): ?>
                                <span style="background:var(--admin-purple); color:#fff; padding:2px 8px; border-radius:10px; font-size:9px; font-weight:700; margin-left:6px;">FIJADO</span>
                            <?php endif; ?>
                        </div>
                        <strong style="font-size:13px; display:block; margin-bottom:4px;"><?= htmlspecialchars($post['titulo']) ?></strong>
                        <p style="margin:0; font-size:12px; color:#555; line-height:1.5;"><?= nl2br(htmlspecialchars($post['contenido'])) ?></p>
                        <?php if ($post['imagen_url']): ?>
                            <div style="margin-top:8px;">
                                <img src="<?= htmlspecialchars($post['imagen_url']) ?>" alt="Imagen" style="max-width:280px; border-radius:8px;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:6px; flex-shrink:0;">
                        <form method="POST" action="controllers/comunidad.php" style="margin:0;">
                            <input type="hidden" name="action" value="toggle_fijado">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <button type="submit" class="admin-btn ghost" style="font-size:10px; padding:4px 10px;">
                                <?= $post['fijado'] ? 'Desfijar' : 'Fijar' ?>
                            </button>
                        </form>
                        <form method="POST" action="controllers/comunidad.php" style="margin:0;" onsubmit="return confirm('Eliminar esta publicación?');">
                            <input type="hidden" name="action" value="eliminar_post">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <button type="submit" class="admin-btn ghost" style="font-size:10px; padding:4px 10px; color:#b3261e;">Eliminar</button>
                        </form>
                    </div>
                </div>
                <div style="font-size:10px; color:#888; margin-top:6px;">
                    <?= $post['likes'] ?> likes
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
