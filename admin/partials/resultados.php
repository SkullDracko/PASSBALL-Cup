<?php
/**
 * PASSBALL Cup - Admin: Resultados
 *
 * Datos y acciones por admin/assets/js/views.js contra backend/api.
 * No usar $pdo aquí.
 */
?>
<div class="admin-alert vista-aviso is-error" hidden></div>
<div class="admin-view-head">
<span class="admin-view-head-ico"><i class="fa-solid fa-ranking-star"></i></span>
<div>
<h2 class="admin-section-title">Resultados</h2>
<p class="admin-section-sub">Registra marcadores, goles por jugador y consulta el top goleador.</p>
</div>
</div>
<div class="admin-toolbar">
<div class="field">
<label>Torneo</label>
<select data-torneo-select class="admin-field-lg">
<option>Cargando…</option>
</select>
</div>
<div class="field">
<label>Ronda</label>
<select id="resultadosRonda" data-ronda-select class="admin-field-lg">
<option>Cargando…</option>
</select>
</div>
</div>
<div id="resultadosBody">
<p class="admin-note">Cargando…</p>
</div>
