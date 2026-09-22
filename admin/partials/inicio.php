<?php
/**
 * PASSBALL Cup - Admin: Inicio (resumen)
 */

$stats = [];

try {
    $stats['equipos']       = (int) $pdo->query("SELECT COUNT(*) FROM equipos WHERE estado='activo'")->fetchColumn();
    $stats['participantes'] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado='activo' AND jugador_activo=1")->fetchColumn();
    $stats['pendientes']    = (int) $pdo->query("SELECT COUNT(*) FROM torneo_equipos WHERE estado='pendiente'")->fetchColumn();
    $stats['partidos']      = (int) $pdo->query("SELECT COUNT(*) FROM partidos WHERE estado IN ('programado','en_curso')")->fetchColumn();
    $stats['rondas']        = (int) $pdo->query("SELECT COUNT(*) FROM torneo_rondas")->fetchColumn();
    $stats['torneos']       = (int) $pdo->query("SELECT COUNT(*) FROM torneos")->fetchColumn();
    $stats['posts']         = (int) $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $stats['votos']         = (int) $pdo->query("SELECT COUNT(*) FROM torneo_votos")->fetchColumn();
} catch (PDOException $e) {
    error_log("Admin Inicio stats: " . $e->getMessage());
}
?>

<h2 class="admin-section-title">Resumen del torneo</h2>
<p class="admin-section-sub">Vista general de la actividad actual.</p>

<div class="stat-grid">

    <div class="stat-card">
        <div class="stat-label">Equipos</div>
        <div class="stat-value"><?= $stats['equipos'] ?? 0 ?></div>
        <div class="stat-hint">registrados</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Participantes</div>
        <div class="stat-value"><?= $stats['participantes'] ?? 0 ?></div>
        <div class="stat-hint">jugadores activos</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Postulaciones</div>
        <div class="stat-value"><?= $stats['pendientes'] ?? 0 ?></div>
        <div class="stat-hint">pendientes de revisión</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Partidos</div>
        <div class="stat-value"><?= $stats['partidos'] ?? 0 ?></div>
        <div class="stat-hint">programados / en curso</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Rondas</div>
        <div class="stat-value"><?= $stats['rondas'] ?? 0 ?></div>
        <div class="stat-hint">del torneo</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Comunidad</div>
        <div class="stat-value"><?= $stats['posts'] ?? 0 ?></div>
        <div class="stat-hint">publicaciones</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Votos</div>
        <div class="stat-value"><?= $stats['votos'] ?? 0 ?></div>
        <div class="stat-hint">emitidos en categorías</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Torneos</div>
        <div class="stat-value"><?= $stats['torneos'] ?? 0 ?></div>
        <div class="stat-hint">creados</div>
    </div>

</div>