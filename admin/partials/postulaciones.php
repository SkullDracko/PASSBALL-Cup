<?php
/**
 * PASSBALL Cup - Admin: Postulaciones
 * Lista y proceso de solicitudes de equipos al torneo
 */

/* ---------- Torneo ---------- */

$torneos = [];

try {
    $torneos = $pdo->query(
        "SELECT id, nombre, estado FROM torneos ORDER BY id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin postulaciones - torneos: " . $e->getMessage());
}

$torneoIdSel = (int) ($_GET['torneo_id'] ?? 0);

if ($torneoIdSel <= 0 && !empty($torneos)) {
    $torneoIdSel = (int) $torneos[0]['id'];
}

$torneoSel = null;
$postulaciones = [];

if ($torneoIdSel > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM torneos WHERE id = ?");
        $stmt->execute([$torneoIdSel]);
        $torneoSel = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT te.*,
                   e.nombre AS equipo_nombre, e.logo AS equipo_logo,
                   a.nombre AS aprobador_nombre
            FROM torneo_equipos te
            JOIN equipos e ON e.id = te.equipo_id
            LEFT JOIN administradores a ON a.id = te.aprobado_por
            WHERE te.torneo_id = ?
            ORDER BY
                FIELD(te.estado, 'pendiente','aprobado','rechazado','retirado'),
                te.fecha_solicitud ASC
        ");
        $stmt->execute([$torneoIdSel]);
        $postulaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Admin postulaciones - listar: " . $e->getMessage());
    }
}

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$chipEstado = [
    'pendiente' => 'chip pendiente',
    'aprobado'  => 'chip aprobado',
    'rechazado' => 'chip rechazado',
    'retirado'  => 'chip cancelado',
];
$labelEstado = [
    'pendiente' => 'Pendiente',
    'aprobado'  => 'Aprobado',
    'rechazado' => 'Rechazado',
    'retirado'  => 'Retirado',
];
?>

<?php if ($flashSuccess): ?>
    <div class="admin-stub" style="padding:14px 18px; background:#e8f7ee; color:#1a7f3a; margin-bottom:18px;">
        <strong><?= htmlspecialchars($flashSuccess) ?></strong>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="admin-stub" style="padding:14px 18px; background:#fdeeee; color:#b3261e; margin-bottom:18px;">
        <strong><?= htmlspecialchars($flashError) ?></strong>
    </div>
<?php endif; ?>

<h2 class="admin-section-title">Postulaciones</h2>
<p class="admin-section-sub">Revisa y aprueba los equipos que solicitan entrar al torneo.</p>

<?php if (empty($torneos)): ?>

    <div class="admin-stub">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12l2 2 4-4"/></svg>
        <h3>No hay torneos</h3>
        <p>Crea al menos un torneo para gestionar postulaciones.</p>
    </div>

<?php else: ?>

    <!-- Selector de torneo -->
    <form class="admin-form" method="GET" action="dashboard.php#view-postulaciones">
        <div class="field">
            <label>Torneo</label>
            <select name="torneo_id" onchange="this.form.submit()" style="min-width:240px;">
                <?php foreach ($torneos as $t): ?>
                    <option
                        value="<?= (int) $t['id'] ?>"
                        <?= (int) $t['id'] === $torneoIdSel ? 'selected' : '' ?>
                    ><?= htmlspecialchars($t['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if (empty($postulaciones)): ?>

        <div class="admin-stub">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12l2 2 4-4"/></svg>
            <h3>Sin postulaciones</h3>
            <p>Los equipos aún no se han postulado a este torneo.</p>
        </div>

    <?php else: ?>

        <?php
        $pendientes  = array_filter($postulaciones, fn($p) => $p['estado'] === 'pendiente');
        $procesadas  = array_filter($postulaciones, fn($p) => $p['estado'] !== 'pendiente');
        ?>

        <?php if (!empty($pendientes)): ?>

            <h2 class="admin-section-title" style="font-size:14px; margin-top:6px; color:#5b3d91;">
                Pendientes (<?= count($pendientes) ?>)
            </h2>

            <div class="admin-table-wrap" style="margin-bottom:26px;">

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Equipo</th>
                            <th>Fecha solicitud</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendientes as $p): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <?php if (!empty($p['equipo_logo'])): ?>
                                            <img src="<?= htmlspecialchars($p['equipo_logo']) ?>" style="width:36px; height:36px; border-radius:8px; object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:36px; height:36px; border-radius:8px; background:var(--admin-purple); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">
                                                <?= htmlspecialchars(mb_strtoupper(mb_substr($p['equipo_nombre'], 0, 1))) ?>
                                            </div>
                                        <?php endif; ?>
                                        <strong><?= htmlspecialchars($p['equipo_nombre']) ?></strong>
                                    </div>
                                </td>

                                <td>
                                    <?= date('d/m/Y H:i', strtotime($p['fecha_solicitud'])) ?>
                                </td>

                                <td>
                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">

                                        <!-- Aprobar -->
                                        <form method="POST" action="controllers/postulaciones.php" style="display:inline;">
                                            <input type="hidden" name="action" value="aprobar">
                                            <input type="hidden" name="postulacion_id" value="<?= (int) $p['id'] ?>">
                                            <input type="hidden" name="torneo_id" value="<?= (int) $torneoIdSel ?>">
                                            <button type="submit" class="admin-btn" style="font-size:12px; padding:7px 14px; background:#1a7f3a;">
                                                ✓ Aprobar
                                            </button>
                                        </form>

                                        <!-- Rechazar (prompt para motivo) -->
                                        <button
                                            type="button"
                                            class="admin-btn ghost"
                                            style="font-size:12px; padding:7px 14px; color:#b3261e;"
                                            onclick="rechazarPost(<?= (int) $p['id'] ?>)"
                                        >
                                            ✕ Rechazar
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>

        <?php endif; ?>


        <?php if (!empty($procesadas)): ?>

            <h2 class="admin-section-title" style="font-size:14px; margin-top:6px; color:#5b3d91;">
                Procesadas (<?= count($procesadas) ?>)
            </h2>

            <div class="admin-table-wrap">

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Equipo</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Aprobado por</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($procesadas as $p): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['equipo_nombre']) ?></strong></td>
                                <td>
                                    <span class="<?= $chipEstado[$p['estado']] ?? '' ?>">
                                        <?= $labelEstado[$p['estado']] ?? $p['estado'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $p['fecha_aprobacion']
                                        ? date('d/m/Y H:i', strtotime($p['fecha_aprobacion']))
                                        : '—' ?>
                                </td>
                                <td><?= htmlspecialchars($p['aprobador_nombre'] ?? '—') ?></td>
                                <td>
                                    <?= $p['motivo_rechazo']
                                        ? htmlspecialchars($p['motivo_rechazo'])
                                        : '<span style="color:#ccc;">—</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>

        <?php endif; ?>

    <?php endif; ?>

<?php endif; ?>


<!-- Formulario oculto de rechazo + JS -->
<form id="formRechazar" method="POST" action="controllers/postulaciones.php" style="display:none;">
    <input type="hidden" name="action" value="rechazar">
    <input type="hidden" name="postulacion_id" id="rejectId">
    <input type="hidden" name="torneo_id" value="<?= (int) $torneoIdSel ?>">
    <input type="hidden" name="motivo" id="rejectMotivo">
</form>

<script>
function rechazarPost(id) {
    var motivo = prompt('Motivo del rechazo (opcional):');
    if (motivo === null) return;
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectMotivo').value = motivo;
    document.getElementById('formRechazar').submit();
}
</script>