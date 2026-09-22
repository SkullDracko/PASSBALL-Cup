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

<h2 class="admin-section-title">Participantes</h2>
<p class="admin-section-sub"><?= count($participantes) ?> jugadores registrados en la plataforma.</p>

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
                    <td colspan="6" style="text-align:center; color:#998caf;">Sin participantes aún.</td>
                </tr>

            <?php else: ?>

                <?php foreach ($participantes as $p): ?>

                    <tr>

                        <td><?= (int) $p['id'] ?></td>

                        <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>

                        <td><?= htmlspecialchars($p['matricula']) ?></td>

                        <td><?= htmlspecialchars($p['equipo'] ?? '—') ?></td>

                        <td>
                            <span
                                style="
                                    display:inline-block;
                                    padding:3px 10px;
                                    border-radius:20px;
                                    font-size:11.5px;
                                    font-weight:700;
                                    background:<?= $p['estado'] === 'activo' ? '#e8f7ee' : '#fdeeee' ?>;
                                    color:<?= $p['estado'] === 'activo' ? '#1a7f3a' : '#b3261e' ?>;
                                "
                            ><?= htmlspecialchars($p['estado']) ?></span>
                        </td>

                        <td><?= $p['jugador_activo'] ? 'Sí' : 'No' ?></td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

        </tbody>

    </table>

</div>