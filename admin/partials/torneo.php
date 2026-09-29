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

    if ($torneoIdSel> 0) {
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

    /* ---------- Resumen ---------- */

    $totalPartidos = 0;
    $enCurso = 0;
    foreach ($rondas as $r) {
        $totalPartidos += count($r['partidos']);
        foreach ($r['partidos'] as $p) {
            if (($p['estado'] ?? '') === 'en_curso') {
                $enCurso++;
            }
        }
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
<div class="admin-alert is-ok">
<strong><?= htmlspecialchars($flashSuccess) ?></strong>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="admin-alert is-error">
<strong><?= htmlspecialchars($flashError) ?></strong>
</div>
<?php endif; ?>
<div class="admin-view-head">
<span class="admin-view-head-ico"><i class="fa-solid fa-trophy"></i></span>
<div>
<h2 class="admin-section-title">Torneo y Rondas</h2>
<p class="admin-section-sub">Organiza las rondas y crea los partidos del torneo.</p>
</div>
</div>
<?php if (empty($torneos)): ?>
<div class="admin-stub">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
<h3>Aún no hay torneos</h3>
<p>Crea un torneo desde la base de datos para comenzar.</p>
</div>
<?php else: ?>
<div class="tny-top">
<!-- SELECTOR DE TORNEO -->
<form class="tny-sel" method="GET" action="dashboard.php#view-torneo">
<label for="selTorneo">Torneo</label>
<select id="selTorneo" name="torneo_id" onchange="this.form.submit()">
<?php foreach ($torneos as $t): ?>
<option value="<?= (int) $t['id'] ?>"
                        <?= (int) $t['id'] === (int) ($torneoSeleccionado['id'] ?? 0) ? 'selected' : '' ?>>
<?= htmlspecialchars($t['nombre']) ?> (<?= $etiquetaEstadoTorneo[$t['estado']] ?? $t['estado'] ?>)
                    </option>
<?php endforeach; ?>
</select>
</form>
<?php if ($torneoSeleccionado): ?>
<!-- CREAR RONDA -->
<form class="tny-add" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="crear_ronda">
<input type="hidden" name="torneo_id" value="<?= (int) $torneoSeleccionado['id'] ?>">
<div class="f">
<label for="rondaNombre">Nueva ronda</label>
<input type="text" id="rondaNombre" name="nombre" placeholder="Ej. Octavos de final" required>
</div>
<div class="f ord">
<label for="rondaOrden">Orden</label>
<input type="number" id="rondaOrden" name="orden" placeholder="Auto" min="1">
</div>
<button type="submit" class="admin-btn">+ Crear ronda</button>
</form>
<?php endif; ?>
</div>
<?php if ($torneoSeleccionado): ?>
<!-- RESUMEN -->
<div class="tny-summary">
<div class="tny-tn">
<span class="ico"><i class="fa-solid fa-trophy"></i></span>
<div>
<b><?= htmlspecialchars($torneoSeleccionado['nombre']) ?></b>
<small><?= $etiquetaEstadoTorneo[$torneoSeleccionado['estado']] ?? $torneoSeleccionado['estado'] ?></small>
</div>
</div>
<div class="tny-kpis">
<div class="tny-kpi"><b><?= count($rondas) ?></b><span>Rondas</span></div>
<div class="tny-kpi"><b><?= $totalPartidos ?></b><span>Partidos</span></div>
<div class="tny-kpi"><b><?= $enCurso ?></b><span>En curso</span></div>
</div>
</div>
<?php if (empty($rondas)): ?>
<div class="admin-stub">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
<h3>Sin rondas todavía</h3>
<p>Crea la primera ronda del torneo con el formulario de arriba.</p>
</div>
<?php else: ?>
<!-- BRACKET -->
<div class="tny-bracket">
<?php foreach ($rondas as $r): ?>
<div class="tny-round" data-ronda-id="<?= (int) $r['id'] ?>">
<div class="tny-head">
<b><?= htmlspecialchars($r['nombre']) ?></b>
<span class="num">Ronda <?= (int) $r['orden'] ?></span>
<span class="cnt"><?= count($r['partidos']) ?> partidos</span>
</div>
<div class="tny-body">
<?php if (empty($r['partidos'])): ?>
<div class="ronda-empty">
<i class="fa-solid fa-plus"></i>
Aún no hay partidos en esta ronda.<br>Agrega el primero.
</div>
<?php else: ?>
<?php foreach ($r['partidos'] as $p): ?>
<div class="mtc">
<div class="teams">
<div class="tm">
<?php if (!empty($p['local_logo'])): ?>
<img src="<?= htmlspecialchars($p['local_logo']) ?>" alt="">
<?php else: ?>
<span class="fl"><?= htmlspecialchars(substr($p['local_nombre'] ?? '?', 0, 1)) ?></span>
<?php endif; ?>
<span class="nm"><?= htmlspecialchars($p['local_nombre'] ?? '—') ?></span>
</div>
<?php if ($p['goles_local'] !== null): ?>
<span class="score"><?= (int) $p['goles_local'] ?> - <?= (int) $p['goles_visitante'] ?></span>
<?php else: ?>
<span class="score faint">vs</span>
<?php endif; ?>
<div class="tm r">
<span class="nm"><?= htmlspecialchars($p['visitante_nombre'] ?? '—') ?></span>
<?php if (!empty($p['visitante_logo'])): ?>
<img src="<?= htmlspecialchars($p['visitante_logo']) ?>" alt="">
<?php else: ?>
<span class="fl"><?= htmlspecialchars(substr($p['visitante_nombre'] ?? '?', 0, 1)) ?></span>
<?php endif; ?>
</div>
</div>
<div class="meta">
<span>
<i class="fa-solid fa-calendar-days"></i>
<?php if (!empty($p['fecha_hora'])): ?>
<?= date('d/m/Y H:i', strtotime($p['fecha_hora'])) ?>
<?php else: ?>
Sin fecha
<?php endif; ?>
</span>
<?php if (!empty($p['cancha'])): ?>
<span><i class="fa-solid fa-location-dot"></i><?= htmlspecialchars($p['cancha']) ?></span>
<?php endif; ?>
<span class="chip <?= htmlspecialchars($p['estado']) ?>">
<?= htmlspecialchars(ucfirst(str_replace('_', ' ', $p['estado']))) ?>
</span>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<!-- CREAR PARTIDO -->
<button type="button" class="add-match-btn" onclick="openPartidoModal(this)">
<i class="fa-solid fa-plus"></i>Agregar partido
</button>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<!-- MODAL AGREGAR PARTIDO -->
<div class="prod" id="prodModal" hidden>
<div class="prod-bg" onclick="closePartidoModal()"></div>
<div class="prod-dialog" role="dialog" aria-modal="true">
<div class="prod-head">
<span class="prod-ico"><i class="fa-solid fa-trophy"></i></span>
<h3>Agregar partido</h3>
<span class="prod-round" id="prodRound">Ronda</span>
<button type="button" class="prod-x" onclick="closePartidoModal()"><i class="fa-solid fa-xmark"></i></button>
</div>
<form class="prod-body" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="crear_partido">
<input type="hidden" name="ronda_id" id="prodRondaId">
<div class="prod-grid2">
<label>Equipo local
<select name="equipo_local_id" required>
<option value="">— Equipo local —</option>
<?php foreach ($equiposOpt as $eq): ?>
<option value="<?= (int) $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
<?php endforeach; ?>
</select>
</label>
<label>Equipo visitante
<select name="equipo_visitante_id" required>
<option value="">— Equipo visitante —</option>
<?php foreach ($equiposOpt as $eq): ?>
<option value="<?= (int) $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
<?php endforeach; ?>
</select>
</label>
</div>
<div class="prod-grid2">
<label>Fecha y hora <input type="datetime-local" name="fecha_hora"></label>
<label>Cancha <input type="text" name="cancha" placeholder="Cancha (opcional)"></label>
</div>
<label>Posición <input type="number" name="posicion" placeholder="Auto" min="1"></label>
<div class="prod-foot">
<button type="button" class="prod-btn ghost" onclick="closePartidoModal()">Cancelar</button>
<button type="submit" class="prod-btn">Guardar partido</button>
</div>
</form>
</div>
</div>
<script>
function openPartidoModal(btn) {
var round = btn.closest('.tny-round');
var num   = round.querySelector('.num').textContent;
var name  = round.querySelector('.tny-head b').textContent;
document.getElementById('prodRondaId').value = round.getAttribute('data-ronda-id');
document.getElementById('prodRound').textContent = num + ' · ' + name;
document.getElementById('prodModal').hidden = false;
}
function closePartidoModal() { document.getElementById('prodModal').hidden = true; }
</script>
<?php endif; ?>
<?php endif; ?>