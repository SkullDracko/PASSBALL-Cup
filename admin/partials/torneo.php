<?php
/**
 * PASSBALL Cup - Admin: Torneo y Rondas
 *
 * Datos y acciones por admin/assets/js/views.js contra backend/api.
 * No usar $pdo aquí.
 */
?>
<div class="admin-alert vista-aviso is-error" hidden></div>
<div class="admin-view-head">
<span class="admin-view-head-ico"><i class="fa-solid fa-trophy"></i></span>
<div>
<h2 class="admin-section-title">Torneo y Rondas</h2>
<p class="admin-section-sub">Organiza las rondas y crea los partidos del torneo.</p>
</div>
</div>
<div class="admin-toolbar">
<div class="field">
<label for="selTorneo">Torneo</label>
<select id="selTorneo" data-torneo-select class="admin-field-lg">
<option>Cargando…</option>
</select>
</div>
</div>
<div id="torneoBody">
<p class="admin-note">Cargando…</p>
</div>
