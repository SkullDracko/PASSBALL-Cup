<?php
/**
 * PASSBALL Cup - Admin: Resultados
 * Actualiza marcadores y registra goles por jugador
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

/* ---------- Torneo / Ronda ---------- */

$torneos = $pdo->query("SELECT id, nombre, estado FROM torneos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$torneoIdSel = (int) ($_GET['torneo_id'] ?? 0);
if ($torneoIdSel <= 0 && !empty($torneos)) $torneoIdSel = (int) $torneos[0]['id'];

$rondas = [];
$partidos = [];
$rondaSel = (int) ($_GET['ronda_id'] ?? 0);

if ($torneoIdSel > 0) {
    $stmt = $pdo->prepare("SELECT id, nombre, orden FROM torneo_rondas WHERE torneo_id = ? ORDER BY orden");
    $stmt->execute([$torneoIdSel]);
    $rondas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rondaSel <= 0 && !empty($rondas)) $rondaSel = (int) $rondas[0]['id'];

    if ($rondaSel > 0) {
        $stmt = $pdo->prepare("
            SELECT p.*,
                   l.nombre AS local_nombre, l.logo AS local_logo,
                   v.nombre AS visitante_nombre, v.logo AS visitante_logo
            FROM partidos p
            LEFT JOIN equipos l ON l.id = p.equipo_local_id
            LEFT JOIN equipos v ON v.id = p.equipo_visitante_id
            WHERE p.ronda_id = ?
            ORDER BY p.posicion, p.id
        ");
        $stmt->execute([$rondaSel]);
        $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* ---------- Eventos por partido + jugadores por equipo ---------- */

$eventosPorPartido = [];
$jugadoresPorEquipo = [];

foreach ($partidos as $p) {
    $stmt = $pdo->prepare("
        SELECT pe.*, u.nombre AS jugador_nombre
        FROM partido_eventos pe
        JOIN usuarios u ON u.id = pe.jugador_id
        WHERE pe.partido_id = ?
        ORDER BY pe.minuto IS NULL, pe.minuto ASC, pe.id ASC
    ");
    $stmt->execute([$p['id']]);
    $eventosPorPartido[$p['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach ($partidos as $p) {
    foreach ([$p['equipo_local_id'], $p['equipo_visitante_id']] as $eid) {
        if ($eid && !isset($jugadoresPorEquipo[$eid])) {
            $stmt = $pdo->prepare("
                SELECT u.id, u.nombre
                FROM equipo_miembros em
                JOIN usuarios u ON u.id = em.jugador_id
                WHERE em.equipo_id = ? AND em.estado = 'activo'
                ORDER BY u.nombre
            ");
            $stmt->execute([$eid]);
            $jugadoresPorEquipo[$eid] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

$chipEstado = [
    'programado' => 'chip programado',
    'en_curso'   => 'chip en_curso',
    'finalizado' => 'chip finalizado',
    'cancelado'  => 'chip cancelado',
];
$nombreTipoEvento = [
    'gol'             => '⚽ Gol',
    'autogol'         => '😬 Autogol',
    'penal_anotado'   => '🎯 Penal',
    'tarjeta_amarilla'=> '🟨 Amarilla',
    'tarjeta_roja'    => '🟥 Roja',
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

<h2 class="admin-section-title">Resultados</h2>
<p class="admin-section-sub">Registra marcadores, goles por jugador y consulta el top goleador.</p>

<form class="admin-form" method="GET" action="dashboard.php#view-resultados">

    <div class="field">
        <label>Torneo</label>
        <select name="torneo_id" onchange="this.form.submit()" style="min-width:210px;">
            <?php foreach ($torneos as $t): ?>
                <option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === $torneoIdSel ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Ronda</label>
        <select name="ronda_id" onchange="this.form.submit()" style="min-width:200px;">
            <?php foreach ($rondas as $r): ?>
                <option value="<?= (int) $r['id'] ?>" <?= (int) $r['id'] === $rondaSel ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

</form>

<?php if (empty($partidos)): ?>
    <div class="admin-stub">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h10v2h2a2 2 0 0 1 2 2c0 3.3-2.4 5.8-5.5 6.5V16a5 5 0 0 1-2 4.9V22H10v-1.1a5 5 0 0 1-2-4.9v-2.5C4.9 12.8 2.5 10.3 2.5 7a2 2 0 0 1 2-2h2V3z"/></svg>
        <h3>Sin partidos en esta ronda</h3>
        <p>Crea partidos en la sección «Torneo y Rondas».</p>
    </div>
<?php else: ?>

    <?php foreach ($partidos as $p): ?>

        <div style="background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(47,30,80,0.07); padding:18px; margin-bottom:20px;">

            <!-- Cabecera -->
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px;">

                <div style="display:flex; align-items:center; gap:12px; font-weight:800;">
                    <span><?= htmlspecialchars($p['local_nombre'] ?? '—') ?></span>
                    <span style="background:var(--admin-purple); color:#fff; padding:4px 14px; border-radius:8px; font-size:18px;">
                        <?= $p['goles_local'] !== null ? (int)$p['goles_local'] . ' - ' . (int)$p['goles_visitante'] : 'vs' ?>
                    </span>
                    <span style="text-align:right;"><?= htmlspecialchars($p['visitante_nombre'] ?? '—') ?></span>
                </div>

                <span class="<?= $chipEstado[$p['estado']] ?? '' ?>">
                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $p['estado']))) ?>
                </span>

            </div>

            <!-- Formulario de resultado -->
            <form class="admin-form" method="POST" action="controllers/resultados.php" style="margin-bottom:12px;">

                <input type="hidden" name="action" value="actualizar_partido">
                <input type="hidden" name="partido_id" value="<?= (int) $p['id'] ?>">

                <div class="field" style="max-width:90px;">
                    <label>Goles local</label>
                    <input type="number" name="goles_local" min="0" value="<?= $p['goles_local'] !== null ? (int)$p['goles_local'] : '' ?>">
                </div>

                <div class="field" style="max-width:90px;">
                    <label>Goles visit.</label>
                    <input type="number" name="goles_visitante" min="0" value="<?= $p['goles_visitante'] !== null ? (int)$p['goles_visitante'] : '' ?>">
                </div>

                <div class="field" style="max-width:90px;">
                    <label>Penales local</label>
                    <input type="number" name="penales_local" min="0" value="<?= $p['penales_local'] !== null ? (int)$p['penales_local'] : '' ?>">
                </div>

                <div class="field" style="max-width:90px;">
                    <label>Penales visit.</label>
                    <input type="number" name="penales_visitante" min="0" value="<?= $p['penales_visitante'] !== null ? (int)$p['penales_visitante'] : '' ?>">
                </div>

                <div class="field" style="min-width:160px;">
                    <label>Ganador</label>
                    <select name="ganador_id">
                        <option value="0">— Sin definir —</option>
                        <?php foreach ([$p['equipo_local_id'] => $p['local_nombre'], $p['equipo_visitante_id'] => $p['visitante_nombre']] as $gid => $gnom): ?>
                            <?php if ($gid): ?>
                                <option value="<?= (int) $gid ?>" <?= (int) $p['ganador_id'] === (int) $gid ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($gnom) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field" style="min-width:150px;">
                    <label>Estado</label>
                    <select name="estado">
                        <?php foreach (['programado','en_curso','finalizado','cancelado'] as $es): ?>
                            <option value="<?= $es ?>" <?= $p['estado'] === $es ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $es))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="admin-btn">Guardar resultado</button>

            </form>

            <?php if (!empty($eventosPorPartido[$p['id']])): ?>
                <div style="margin-bottom:12px; font-size:13px;">
                    <?php foreach ($eventosPorPartido[$p['id']] as $ev): ?>
                        <span style="display:inline-block; background:#f4f0fa; border-radius:20px; padding:3px 11px; margin:0 6px 6px 0; font-size:12px;">
                            <?= $nombreTipoEvento[$ev['tipo']] ?? $ev['tipo'] ?>
                            · <?= htmlspecialchars($ev['jugador_nombre']) ?>
                            <?php if ($ev['minuto'] !== null): ?> · <?= (int) $ev['minuto'] ?>'<?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Registrar eventos por equipo -->
            <div class="bracket" style="gap:10px;">

                <?php foreach ([$p['equipo_local_id'], $p['equipo_visitante_id']] as $eid): ?>

                    <?php if (!$eid) continue; ?>

                    <form class="admin-form" method="POST" action="controllers/resultados.php" style="padding:12px; margin-bottom:0; min-width:0; flex:1;">

                        <input type="hidden" name="action" value="agregar_gol">
                        <input type="hidden" name="partido_id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="equipo_id" value="<?= (int) $eid ?>">

                        <div class="field" style="min-width:100%;">
                            <label>Jugador · Equipo <?= $eid === (int)$p['equipo_local_id'] ? 'local' : 'visitante' ?></label>
                            <select name="jugador_id" required style="width:100%;">
                                <option value="">— Seleccionar —</option>
                                <?php foreach ($jugadoresPorEquipo[$eid] ?? [] as $jg): ?>
                                    <option value="<?= (int) $jg['id'] ?>"><?= htmlspecialchars($jg['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field" style="min-width:120px;">
                            <label>Tipo</label>
                            <select name="tipo" style="width:100%;">
                                <option value="gol">⚽ Gol</option>
                                <option value="penal_anotado">🎯 Penal</option>
                                <option value="autogol">😬 Autogol</option>
                                <option value="tarjeta_amarilla">🟨 Amarilla</option>
                                <option value="tarjeta_roja">🟥 Roja</option>
                            </select>
                        </div>

                        <div class="field" style="max-width:90px;">
                            <label>Minuto</label>
                            <input type="number" name="minuto" min="1" max="120" placeholder="—">
                        </div>

                        <button type="submit" class="admin-btn ghost">+ Registrar</button>

                    </form>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>