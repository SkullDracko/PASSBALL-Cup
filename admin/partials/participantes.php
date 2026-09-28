<?php
/**
 * PASSBALL Cup - Admin: Participantes
 */

$participantes = [];

try {
    $stmt = $pdo->query("
        SELECT
            u.id,
            u.matricula,
            u.nombre,
            u.rol,
            u.jugador_activo,
            u.estado,
            (
                SELECT e.nombre
                FROM equipo_miembros em
                JOIN equipos e ON e.id = em.equipo_id
                WHERE em.jugador_id = u.id AND em.estado = 'activo'
                LIMIT 1
            ) AS equipo
        FROM usuarios u
        WHERE u.rol = 'usuario'
        ORDER BY u.id
    ");
    $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin Participantes: " . $e->getMessage());
}
?>
<div class="admin-view-head">
<span class="admin-view-head-ico"><i class="fa-solid fa-people-group"></i></span>
<div>
<h2 class="admin-section-title">Participantes</h2>
<p class="admin-section-sub"><?= count($participantes) ?> jugadores registrados en la plataforma.</p>
</div>
</div>
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
<tbody>
<?php if (empty($participantes)): ?>
<tr>
<td colspan="6" class="admin-note">Sin participantes aún.</td>
</tr>
<?php else: ?>
<?php foreach ($participantes as $p): ?>
<tr>
<td><?= (int) $p['id'] ?></td>
<td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
<td><?= htmlspecialchars($p['matricula']) ?></td>
<td><?= htmlspecialchars($p['equipo'] ?? '—') ?></td>
<td>
<span class="chip <?= $p['estado'] === 'activo' ? 'activo' : 'inactivo' ?>">
<?= $p['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?>
</span>
</td>
<td><?= $p['jugador_activo'] ? 'Sí' : 'No' ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>