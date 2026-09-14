<?php
/**
 * PASSBALL Cup - Admin: Torneo y Rondas
 * Bracket por rondas + creación de rondas y partidos
 */

/* ---------- Torneo seleccionado ---------- */

$torneoSeleccionado = null;

try {
    $torneos = $pdo->query(
        "SELECT id, nombre, estado FROM torneos ORDER BY id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $torneoIdSel = (int) ($_GET['torneo_id'] ?? 0);

    if ($torneoIdSel <= 0) {
        // Por defecto: el último torneo activo/siguiente
        $stmt = $pdo->prepare("
            SELECT id FROM torneos
            WHERE estado IN ('programado', 'en_curso')
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute();
        $torneoIdSel = (int) ($stmt->fetchColumn() ?: 0);
    }

    if ($torneoIdSel > 0) {
        $stmt = $pdo->prepare("SELECT * FROM torneos WHERE id = ?");
        $stmt->execute([$torneoIdSel]);
        $torneoSeleccionado = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Admin torneo - lista torneos: " . $e->getMessage());
    $torneos = [];
}

/* ---------- Equipos disponibles ---------- */

$equiposOpt = $pdo->query(
    "SELECT id, nombre FROM equipos WHERE estado = 'activo' ORDER BY nombre"
)->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Rondas con partidos ---------- */

$rondas = [];

if ($torneoSeleccionado) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM torneo_rondas
            WHERE torneo_id = ?
            ORDER BY orden ASC, id ASC
        ");
        $stmt->execute([$torneoSeleccionado['id']]);
        $rondas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rondas as &$r) {

            $stmt = $pdo->prepare("
                SELECT p.*,
                       l.nombre AS local_nombre, l.logo AS local_logo,
                       v.nombre AS visitante_nombre, v.logo AS visitante_logo
                FROM partidos p
                LEFT JOIN equipos l ON l.id = p.equipo_local_id
                LEFT JOIN equipos v ON v.id = p.equipo_visitante_id
                WHERE p.ronda_id = ?
                ORDER BY p.posicion ASC, p.id ASC
            ");
            $stmt->execute([$r['id']]);
            $r['partidos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($r);
    } catch (PDOException $e) {
        error_log("Admin torneo - rondas: " . $e->getMessage());
        $rondas = [];
    }
}

/* ---------- Flash ---------- */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$etiquetaEstadoTorneo = [
    'programado' => 'Programado',
    'en_curso'   => 'En curso',
    'finalizado' => 'Finalizado',
    'cancelado'  => 'Cancelado',
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


<h2 class="admin-section-title">Torneo y Rondas</h2>
<p class="admin-section-sub">Organiza las rondas y crea los partidos del torneo.</p>


<?php if (empty($torneos)): ?>

    <div class="admin-stub">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
        <h3>Aún no hay torneos</h3>
        <p>Crea un torneo desde la base de datos para comenzar.</p>
    </div>

<?php else: ?>

    <!-- SELECTOR DE TORNEO -->

    <form class="admin-form" method="GET" action="dashboard.php#view-torneo">

        <div class="field">
            <label for="selTorneo">Torneo</label>
            <select id="selTorneo" name="torneo_id" onchange="this.form.submit()" style="min-width:220px;">
                <?php foreach ($torneos as $t): ?>
                    <option
                        value="<?= (int) $t['id'] ?>"
                        <?= (int) $t['id'] === (int) ($torneoSeleccionado['id'] ?? 0) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($t['nombre']) ?> (<?= $etiquetaEstadoTorneo[$t['estado']] ?? $t['estado'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

    </form>


    <?php if ($torneoSeleccionado): ?>

        <!-- CREAR RONDA -->

        <form class="admin-form" method="POST" action="controllers/torneo.php">

            <input type="hidden" name="action" value="crear_ronda">
            <input type="hidden" name="torneo_id" value="<?= (int) $torneoSeleccionado['id'] ?>">

            <div class="field">
                <label for="rondaNombre">Nueva ronda</label>
                <input type="text" id="rondaNombre" name="nombre" placeholder="Ej. Octavos de final" required>
            </div>

            <div class="field" style="min-width:90px; max-width:110px;">
                <label for="rondaOrden">Orden</label>
                <input type="number" id="rondaOrden" name="orden" placeholder="Auto" min="1">
            </div>

            <button type="submit" class="admin-btn">+ Crear ronda</button>

        </form>


        <!-- BRACKET -->

        <?php if (empty($rondas)): ?>

            <div class="admin-stub">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
                <h3>Sin rondas todavía</h3>
                <p>Crea la primera ronda del torneo con el formulario de arriba.</p>
            </div>

        <?php else: ?>

            <div class="bracket">

                <?php foreach ($rondas as $r): ?>

                    <div class="round-col">

                        <h3><?= htmlspecialchars($r['nombre']) ?></h3>
                        <div class="round-order">Ronda <?= (int) $r['orden'] ?></div>

                        <?php if (empty($r['partidos'])): ?>
                            <p style="font-size:12.5px; color:#998caf; margin-bottom:12px;">
                                Sin partidos.
                            </p>
                        <?php else: ?>

                            <?php foreach ($r['partidos'] as $p): ?>

                                <div class="match-card">

                                    <div class="vs">

                                        <span class="teamx" title="<?= htmlspecialchars($p['local_nombre'] ?? '') ?>">
                                            <?= htmlspecialchars($p['local_nombre'] ?? '—') ?>
                                        </span>

                                        <?php if ($p['goles_local'] !== null): ?>
                                            <span class="score">
                                                <?= (int) $p['goles_local'] ?> - <?= (int) $p['goles_visitante'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#bfb0d6;">vs</span>
                                        <?php endif; ?>

                                        <span class="teamx" style="text-align:right;" title="<?= htmlspecialchars($p['visitante_nombre'] ?? '') ?>">
                                            <?= htmlspecialchars($p['visitante_nombre'] ?? '—') ?>
                                        </span>

                                    </div>

                                    <div class="meta">

                                        <span>
                                            <?php if (!empty($p['fecha_hora'])): ?>
                                                📅 <?= date('d/m/Y H:i', strtotime($p['fecha_hora'])) ?>
                                            <?php else: ?>
                                                📅 Sin fecha
                                            <?php endif; ?>
                                        </span>

                                        <span><?= htmlspecialchars($p['cancha'] ?? '') ?></span>

                                        <span class="chip <?= htmlspecialchars($p['estado']) ?>">
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $p['estado']))) ?>
                                        </span>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>


                        <!-- CREAR PARTIDO -->

                        <form class="admin-form round-add-form" method="POST" action="controllers/torneo.php" style="padding:12px; margin-bottom:0;">

                            <input type="hidden" name="action" value="crear_partido">
                            <input type="hidden" name="ronda_id" value="<?= (int) $r['id'] ?>">

                            <select name="equipo_local_id" required style="width:100%; margin-bottom:8px;">
                                <option value="">— Equipo local —</option>
                                <?php foreach ($equiposOpt as $eq): ?>
                                    <option value="<?= (int) $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <select name="equipo_visitante_id" required style="width:100%; margin-bottom:8px;">
                                <option value="">— Equipo visitante —</option>
                                <?php foreach ($equiposOpt as $eq): ?>
                                    <option value="<?= (int) $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <input
                                type="datetime-local"
                                name="fecha_hora"
                                style="width:100%; margin-bottom:8px;"
                            >

                            <input
                                type="text"
                                name="cancha"
                                placeholder="Cancha (opcional)"
                                style="width:100%; margin-bottom:8px;"
                            >

                            <input
                                type="number"
                                name="posicion"
                                placeholder="Posición (auto)"
                                min="1"
                                style="width:100%; margin-bottom:8px;"
                            >

                            <button type="submit" class="admin-btn" style="width:100%;">
                                + Agregar partido
                            </button>

                        </form>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    <?php endif; ?>

<?php endif; ?>