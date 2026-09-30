<?php
/**
 * PASSBALL Cup - Admin: Comunidad
 *
 * Publica y administra las novedades del torneo.
 * Datos y acciones por admin/assets/js/views.js contra backend/api.
 * No usar $pdo aquí.
 */
?>
<div class="admin-alert vista-aviso is-error" hidden></div>
<div class="admin-view-head">
<span class="admin-view-head-ico"><i class="fa-solid fa-comment-dots"></i></span>
<div>
<h2 class="admin-section-title">Comunidad</h2>
<p class="admin-section-sub">Publica novedades y administra las publicaciones del torneo.</p>
</div>
</div>
<div class="comunidad-layout">

<section class="admin-card composer">
<div class="composer-head">
<span class="composer-head-ico"><i class="fa-solid fa-pen-to-square"></i></span>
<div>
<h3>Nueva publicación</h3>
<p>Comparte una novedad con los participantes</p>
</div>
</div>
<form class="admin-form" data-accion="crear-post">
<div class="field">
<label>Título</label>
<input type="text" name="titulo" placeholder="Ej. Horarios de la jornada" maxlength="200" required>
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

<section class="admin-card">
<div class="feed-head">
<h3>Publicaciones</h3>
<span class="feed-count" data-comunidad-total>0</span>
</div>
<div class="feed" data-comunidad-lista>
<p class="admin-note">Cargando…</p>
</div>
</section>

</div>
