<?php
/**
 * PASSBALL Cup - Admin: Inicio rediseñado
 */

$stats = [
    'equipos'       => 0,
    'participantes' => 0,
    'pendientes'    => 0,
    'partidos'      => 0,
    'rondas'        => 0,
    'torneos'       => 0,
    'votos'         => 0,
];

$torneoActual = null;
$proximosPartidos = [];
$postulacionesPendientes = [];
$progresoRonda = 0;

try {
    $stats['equipos']       = (int) $pdo->query("SELECT COUNT(*) FROM equipos WHERE estado='activo'")->fetchColumn();
    $stats['participantes'] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado='activo' AND jugador_activo=1")->fetchColumn();
    $stats['pendientes']    = (int) $pdo->query("SELECT COUNT(*) FROM torneo_equipos WHERE estado='pendiente'")->fetchColumn();
    $stats['partidos']      = (int) $pdo->query("SELECT COUNT(*) FROM partidos WHERE estado IN ('programado','en_curso')")->fetchColumn();
    $stats['rondas']        = (int) $pdo->query("SELECT COUNT(*) FROM torneo_rondas")->fetchColumn();
    $stats['torneos']       = (int) $pdo->query("SELECT COUNT(*) FROM torneos")->fetchColumn();
    $stats['votos']         = (int) $pdo->query("SELECT COUNT(*) FROM torneo_votos")->fetchColumn();

    $torneoActual = $pdo->query("
        SELECT *
        FROM torneos
        WHERE estado IN ('en_curso', 'programado')
        ORDER BY FIELD(estado, 'en_curso', 'programado'), id DESC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if ($torneoActual) {
        $stmt = $pdo->prepare("
            SELECT p.*,
                el.nombre AS local_nombre,
                ev.nombre AS visitante_nombre
            FROM partidos p
            INNER JOIN torneo_rondas r ON r.id = p.ronda_id
            LEFT JOIN equipos el ON el.id = p.equipo_local_id
            LEFT JOIN equipos ev ON ev.id = p.equipo_visitante_id
            WHERE r.torneo_id = ?
             AND p.estado IN ('programado', 'en_curso')
            ORDER BY p.fecha_hora IS NULL, p.fecha_hora ASC, p.id ASC
            LIMIT 3
        ");
        $stmt->execute([$torneoActual['id']]);
        $proximosPartidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->query("
        SELECT te.*, e.nombre AS equipo_nombre
        FROM torneo_equipos te
        INNER JOIN equipos e ON e.id = te.equipo_id
        WHERE te.estado = 'pendiente'
        ORDER BY te.fecha_solicitud ASC
        LIMIT 4
    ");
    $postulacionesPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener estadísticas de inicio: " . $e->getMessage());
}

$progresoRonda = $stats['rondas']> 0 ? 100 : 0;
?>
<section class="torneo-card">
<div class="torneo-card-logo">
<i class="fa-solid fa-futbol"></i>
</div>
<div class="torneo-card-id">
<span>Torneo activo</span>
<h2><?= htmlspecialchars($torneoActual['nombre'] ?? 'PASSBALL Cup') ?></h2>
</div>
<span class="status-pill" data-estado="<?= htmlspecialchars($torneoActual['estado'] ?? '') ?>"></span>
<ul class="torneo-card-stats">
<li><strong><?= $stats['rondas'] ?></strong><span>Rondas</span></li>
<li><strong><?= $stats['equipos'] ?></strong><span>Equipos</span></li>
<li><strong><?= $stats['pendientes'] ?></strong><span>Pendientes</span></li>
</ul>

</section>
<div class="metric-grid">
<div class="metric-card">
<div class="metric-icon"><i class="fa-solid fa-shield-halved"></i></div>
<div>
<span>Equipos</span>
<strong><?= $stats['equipos'] ?></strong>
<small>registrados</small>
</div>
</div>
<div class="metric-card orange">
<div class="metric-icon"><i class="fa-solid fa-users"></i></div>
<div>
<span>Participantes</span>
<strong><?= $stats['participantes'] ?></strong>
<small>jugadores activos</small>
</div>
</div>
<div class="metric-card">
<div class="metric-icon"><i class="fa-solid fa-clipboard-list"></i></div>
<div>
<span>Postulaciones</span>
<strong><?= $stats['pendientes'] ?></strong>
<small>pendientes</small>
</div>
</div>
<div class="metric-card">
<div class="metric-icon"><i class="fa-solid fa-calendar-days"></i></div>
<div>
<span>Partidos</span>
<strong><?= $stats['partidos'] ?></strong>
<small>programados / en curso</small>
</div>
</div>
</div>
<div class="admin-home-layout">
<section class="admin-panel-card">
<div class="panel-title">
<i class="fa-solid fa-trophy"></i>
<h3>Estado del torneo</h3>
<span class="status-pill" data-estado="<?= htmlspecialchars($torneoActual['estado'] ?? '') ?>"></span>
</div>
<p class="panel-muted">Rondas creadas</p>
<div class="round-progress">
<strong><?= $stats['rondas'] ?></strong>
<span>rondas configuradas</span>
</div>
<div class="progress-bar">
<div style="width: <?= (int) $progresoRonda ?>%;"></div>
</div>
<div class="mini-summary">
<div>
<i class="fa-solid fa-shield"></i>
<strong><?= $stats['equipos'] ?></strong>
<span>Equipos</span>
</div>
<div>
<i class="fa-solid fa-users"></i>
<strong><?= $stats['participantes'] ?></strong>
<span>Jugadores</span>
</div>
<div>
<i class="fa-solid fa-star"></i>
<strong><?= $stats['votos'] ?></strong>
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
<?php if (empty($proximosPartidos)): ?>
<p class="empty-note">No hay partidos próximos programados.</p>
<?php else: ?>
<div class="match-list">
<?php foreach ($proximosPartidos as $p): ?>
<div class="match-row">
<strong>
<?= htmlspecialchars($p['local_nombre'] ?? 'Por definir') ?>
<span>vs</span>
<?= htmlspecialchars($p['visitante_nombre'] ?? 'Por definir') ?>
</strong>
<small>
<?= $p['fecha_hora'] ? date('d/m/Y H:i', strtotime($p['fecha_hora'])) : 'Fecha pendiente' ?>
                            · <?= htmlspecialchars($p['cancha'] ?? 'Cancha pendiente') ?>
</small>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>
<section class="admin-panel-card">
<div class="panel-title compact">
<i class="fa-solid fa-file-circle-check"></i>
<h3>Postulaciones pendientes</h3>
</div>
<?php if (empty($postulacionesPendientes)): ?>
<p class="empty-note">No hay postulaciones pendientes.</p>
<?php else: ?>
<div class="pending-list">
<?php foreach ($postulacionesPendientes as $p): ?>
<div class="pending-row">
<strong><?= htmlspecialchars($p['equipo_nombre']) ?></strong>
<span>Pendiente</span>
<small><?= date('d/m/Y', strtotime($p['fecha_solicitud'])) ?></small>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>
</div>