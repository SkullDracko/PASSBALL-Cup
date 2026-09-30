<?php
/**
 * PASSBALL Cup - Admin: Participantes
 *
 * Los datos los pide admin/assets/js/views.js a backend/api.
 * No usar $pdo aquí.
 */
?>
<div class="admin-view-head">
<span class="admin-view-head-ico"><i class="fa-solid fa-people-group"></i></span>
<div>
<h2 class="admin-section-title">Participantes</h2>
<p class="admin-section-sub" id="participantesSub">Cargando…</p>
</div>
</div>
<div class="admin-alert vista-aviso is-error" hidden></div>
<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
<th>#</th>
<th>Nombre</th>
<th>Matrícula</th>
<th>Equipo</th>
<th>Estado</th>
<th>Activo</th>
</tr>
</thead>
<tbody id="participantesCuerpo">
<tr><td colspan="6" class="admin-note">Cargando…</td></tr>
</tbody>
</table>
</div>
