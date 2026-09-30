<?php
/**
 * PASSBALL Cup - Admin: Votaciones
 *
 * Datos y acciones por admin/assets/js/views.js contra backend/api.
 * No usar $pdo aquí.
 */
?>
<div class="admin-alert vista-aviso is-error" hidden></div>
<div class="admin-view-head">
<span class="admin-view-head-ico"><i class="fa-solid fa-star"></i></span>
<div>
<h2 class="admin-section-title">Votaciones</h2>
<p class="admin-section-sub">Crea categorías, define candidatos y controla cuándo abren las votaciones.</p>
</div>
</div>
<div class="admin-toolbar">
<div class="field admin-field-md">
<label>Torneo</label>
<select data-torneo-select>
<option>Cargando…</option>
</select>
</div>
</div>
<div class="admin-card">
<h3 class="admin-card-title">Nueva categoría de votación</h3>
<form class="admin-form" data-accion="crear-categoria" data-torneo="0">
<div class="field admin-field-md">
<label>Clave</label>
<input type="text" name="clave" placeholder="ej. mejor_goleador" required>
</div>
<div class="field admin-field-lg">
<label>Nombre</label>
<input type="text" name="nombre" placeholder="ej. Goleador del torneo" required>
</div>
<div class="field">
<label>Tipo</label>
<select name="tipo">
<option value="jugador">Jugador</option>
<option value="equipo">Equipo</option>
</select>
</div>
<div class="field">
<label>Candidatos</label>
<select name="modo_candidatos">
<option value="automatico">Automático (todos)</option>
<option value="manual">Manual (solo incluidos)</option>
</select>
</div>
<div class="field admin-field-sm">
<label>Orden</label>
<input type="number" name="orden" min="0" value="0">
</div>
<div class="admin-row">
<label class="admin-check">
<input type="checkbox" name="abierta" value="1"> Abrir votación
</label>
<button type="submit" class="admin-btn">Crear categoría</button>
</div>
</form>
</div>
<div id="votacionesBody">
<p class="admin-note">Cargando…</p>
</div>
