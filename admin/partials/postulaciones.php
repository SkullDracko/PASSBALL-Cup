<?php
/**
 * PASSBALL Cup - Admin: Postulaciones
 *
 * Datos y acciones por admin/assets/js/views.js contra backend/api.
 * El selector de torneo se llena desde la API, no desde $_GET.
 * No usar $pdo aquí.
 */
?>
<div class="admin-alert vista-aviso is-error" hidden></div>
<div class="admin-view-head">
<span class="admin-view-head-ico"><i class="fa-solid fa-inbox"></i></span>
<div>
<h2 class="admin-section-title">Postulaciones</h2>
<p class="admin-section-sub">Revisa y aprueba los equipos que solicitan entrar al torneo.</p>
</div>
</div>
<div class="admin-toolbar">
<div class="field">
<label>Torneo</label>
<select data-torneo-select class="admin-field-xl">
<option>Cargando…</option>
</select>
</div>
</div>
<div id="postulacionesBody">
<p class="admin-note">Cargando…</p>
</div>
