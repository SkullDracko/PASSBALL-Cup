<?php
/**
 * PASSBALL Cup - Admin: Inicio
 *
 * Los datos ya no salen de la base: los pide admin/assets/js/api.js a
 * backend/api y se pintan en el cliente. Este archivo es solo el cascarón.
 * No usar $pdo aquí — la conexión vive únicamente en la API.
 */
?>
<section class="torneo-card">
<p class="empty-note" id="inicioError" hidden></p>
<div class="torneo-card-logo">
<i class="fa-solid fa-futbol"></i>
</div>
<div class="torneo-card-id">
<span>Torneo activo</span>
<h2 id="nombreTorneoActual">PASSBALL Cup</h2>
</div>
<span class="status-pill" data-estado=""></span>
<ul class="torneo-card-stats">
<li><strong id="statRondas">—</strong><span>Rondas</span></li>
<li><strong id="statEquipos">—</strong><span>Equipos</span></li>
<li><strong id="statPendientes">—</strong><span>Pendientes</span></li>
</ul>
<a class="torneo-card-action" href="#view-torneo">
        Gestionar <i class="fa-solid fa-arrow-right"></i>
</a>
</section>
<div class="metric-grid">
<div class="metric-card">
<div class="metric-icon"><i class="fa-solid fa-shield-halved"></i></div>
<div>
<span>Equipos</span>
<strong id="statEquiposCard">—</strong>
<small>registrados</small>
</div>
</div>
<div class="metric-card orange">
<div class="metric-icon"><i class="fa-solid fa-users"></i></div>
<div>
<span>Participantes</span>
<strong id="statParticipantes">—</strong>
<small>jugadores activos</small>
</div>
</div>
<div class="metric-card">
<div class="metric-icon"><i class="fa-solid fa-clipboard-list"></i></div>
<div>
<span>Postulaciones</span>
<strong id="statPendientesCard">—</strong>
<small>pendientes</small>
</div>
</div>
<div class="metric-card">
<div class="metric-icon"><i class="fa-solid fa-calendar-days"></i></div>
<div>
<span>Partidos</span>
<strong id="statPartidos">—</strong>
<small>programados / en curso</small>
</div>
</div>
</div>
<div class="admin-home-layout">
<section class="admin-panel-card">
<div class="panel-title">
<i class="fa-solid fa-trophy"></i>
<h3>Estado del torneo</h3>
<span class="status-pill" data-estado=""></span>
</div>
<p class="panel-muted">Rondas creadas</p>
<div class="round-progress">
<strong id="statRondasCard">—</strong>
<span>rondas configuradas</span>
</div>
<div class="progress-bar">
<div id="barraProgreso" style="width: 0%;"></div>
</div>
<div class="mini-summary">
<div>
<i class="fa-solid fa-shield"></i>
<strong id="statEquiposMini">—</strong>
<span>Equipos</span>
</div>
<div>
<i class="fa-solid fa-users"></i>
<strong id="statParticipantesMini">—</strong>
<span>Jugadores</span>
</div>
<div>
<i class="fa-solid fa-star"></i>
<strong id="statVotos">—</strong>
<span>Votos</span>
</div>
</div>
</section>
<section class="admin-brand-card">
<div>
<h3>PASSBALL Cup</h3>
<p>Más que un torneo, una comunidad.</p>
</div>
</section>
</div>
<div class="admin-home-layout two">
<section class="admin-panel-card">
<div class="panel-title compact">
<i class="fa-solid fa-calendar-check"></i>
<h3>Próximos partidos</h3>
</div>
<div class="match-list" id="listaProximosPartidos">
<p class="empty-note">Cargando…</p>
</div>
</section>
<section class="admin-panel-card">
<div class="panel-title compact">
<i class="fa-solid fa-file-circle-check"></i>
<h3>Postulaciones pendientes</h3>
</div>
<div class="pending-list" id="listaPostulacionesPendientes">
<p class="empty-note">Cargando…</p>
</div>
</section>
</div>