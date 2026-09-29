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

/* ---------- Registro de torneos (historial completo) ---------- */

$registroTorneos = [];

try {
    $registroTorneos = $pdo->query("
        SELECT
            t.id, t.nombre, t.tipo, t.fecha_inicio, t.fecha_fin, t.estado, t.fecha_creacion,
            (SELECT COUNT(*) FROM torneo_rondas r WHERE r.torneo_id = t.id) AS rondas,
            (SELECT COUNT(*) FROM torneo_equipos te WHERE te.torneo_id = t.id) AS equipos,
            (SELECT COUNT(*)
               FROM partidos p
               JOIN torneo_rondas r ON r.id = p.ronda_id
              WHERE r.torneo_id = t.id) AS partidos
        FROM torneos t
        ORDER BY t.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin torneo - registro: " . $e->getMessage());
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

$etiquetaEstadoRonda = [
    'programado' => 'Programada',
    'en_curso'   => 'En curso',
    'finalizada' => 'Finalizada',
    'cancelada'  => 'Cancelada',
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
<p class="admin-section-sub">Crea torneos, organiza las rondas y los partidos.</p>
</div>
</div>
<?php if ($torneoSeleccionado): ?>
<!-- ADMINISTRAR TORNEO SELECCIONADO -->
<div class="admin-card admin-gesta-torneo">
<div class="admin-gesta-info">
<div>
<span class="admin-gesta-label">Administrar torneo</span>
<h3 class="admin-gesta-nombre"><?= htmlspecialchars($torneoSeleccionado['nombre']) ?></h3>
<p class="admin-gesta-meta">
<span class="chip <?= htmlspecialchars($torneoSeleccionado['estado']) ?>">
<?= $etiquetaEstadoTorneo[$torneoSeleccionado['estado']] ?? $torneoSeleccionado['estado'] ?>
</span>
<?php if (!empty($torneoSeleccionado['fecha_inicio'])): ?>
<span><i class="fa-regular fa-calendar"></i>
<?= htmlspecialchars($torneoSeleccionado['fecha_inicio']) ?>
<?= !empty($torneoSeleccionado['fecha_fin'])
? ' al ' . htmlspecialchars($torneoSeleccionado['fecha_fin'])
: '' ?>
</span>
<?php endif; ?>
</p>
</div>
</div>
<div class="admin-gesta-acts">
<?php if ($torneoSeleccionado['estado'] === 'programado'): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="cambiar_estado_torneo">
<input type="hidden" name="torneo_id" value="<?= (int) $torneoSeleccionado['id'] ?>">
<input type="hidden" name="estado" value="en_curso">
<button type="submit" class="admin-btn mini"><i class="fa-solid fa-play"></i> Iniciar torneo</button>
</form>
<?php endif; ?>

<?php if ($torneoSeleccionado['estado'] === 'en_curso'): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="cambiar_estado_torneo">
<input type="hidden" name="torneo_id" value="<?= (int) $torneoSeleccionado['id'] ?>">
<input type="hidden" name="estado" value="finalizado">
<button type="submit" class="admin-btn mini"><i class="fa-solid fa-flag-checkered"></i> Terminar torneo</button>
</form>
<?php endif; ?>

<?php if (in_array($torneoSeleccionado['estado'], ['en_curso', 'programado'], true)): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php"
onsubmit="return confirm('¿Cancelar este torneo? Se conserva en el registro.');">
<input type="hidden" name="action" value="cambiar_estado_torneo">
<input type="hidden" name="torneo_id" value="<?= (int) $torneoSeleccionado['id'] ?>">
<input type="hidden" name="estado" value="cancelado">
<button type="submit" class="admin-btn ghost mini"><i class="fa-solid fa-ban"></i> Cancelar</button>
</form>
<?php endif; ?>

<?php if (in_array($torneoSeleccionado['estado'], ['finalizado', 'cancelado'], true)): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="cambiar_estado_torneo">
<input type="hidden" name="torneo_id" value="<?= (int) $torneoSeleccionado['id'] ?>">
<input type="hidden" name="estado" value="en_curso">
<button type="submit" class="admin-btn ghost mini"><i class="fa-solid fa-rotate-left"></i> Reabrir</button>
</form>
<?php endif; ?>

<form class="admin-form nostyle" method="POST" action="controllers/torneo.php"
onsubmit="return confirm('¿Eliminar este torneo definitivamente? Esta acción no se puede deshacer.');">
<input type="hidden" name="action" value="eliminar_torneo">
<input type="hidden" name="torneo_id" value="<?= (int) $torneoSeleccionado['id'] ?>">
<button type="submit" class="admin-btn ghost mini danger"><i class="fa-solid fa-trash"></i> Eliminar</button>
</form>
</div>
</div>
<?php endif; ?>

<!-- CREAR TORNEO -->
<details class="admin-collapse">
<summary class="admin-collapse-head">
<i class="fa-solid fa-plus"></i>
Crear torneo
</summary>
<form class="admin-form admin-collapse-body" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="crear_torneo">
<div class="field admin-field-lg">
<label for="torneoNombre">Nombre del torneo</label>
<input type="text" id="torneoNombre" name="nombre" placeholder="Ej. Copa PASSBALL 2027" maxlength="100" required>
</div>
<div class="field">
<label for="torneoInicio">Fecha de inicio</label>
<input type="date" id="torneoInicio" name="fecha_inicio">
</div>
<div class="field">
<label for="torneoFin">Fecha de fin</label>
<input type="date" id="torneoFin" name="fecha_fin">
</div>
<div class="field admin-field-xs">
<label for="torneoEstado">Estado</label>
<select id="torneoEstado" name="estado">
<option value="programado">Programado</option>
<option value="en_curso">En curso</option>
<option value="finalizado">Finalizado</option>
<option value="cancelado">Cancelado</option>
</select>
</div>
<button type="submit" class="admin-btn">+ Crear torneo</button>
</form>
</details>
<?php if (empty($torneos)): ?>
<div class="admin-stub">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
<h3>Aún no hay torneos</h3>
<p>Crea un torneo con el botón de arriba para comenzar.</p>
</div>
<?php else: ?>
<!-- SELECTOR DE TORNEO -->
<form class="admin-toolbar" method="GET" action="dashboard.php#view-torneo">
<div class="field">
<label for="selTorneo">Torneo</label>
<select id="selTorneo" name="torneo_id" onchange="this.form.submit()" class="admin-field-lg">
<?php foreach ($torneos as $t): ?>
<option value="<?= (int) $t['id'] ?>"
                        <?= (int) $t['id'] === (int) ($torneoSeleccionado['id'] ?? 0) ? 'selected' : '' ?>>
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
<div class="field admin-field-xs">
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
<?php $rondaId = (int) $r['id']; ?>
<div class="round-col">
<h3><?= htmlspecialchars($r['nombre']) ?></h3>
<div class="round-order">
Ronda <?= (int) $r['orden'] ?>
<span class="chip <?= htmlspecialchars($r['estado']) ?>">
<?= $etiquetaEstadoRonda[$r['estado']] ?? $r['estado'] ?>
</span>
</div>
<div class="round-acts">
<?php if ($r['estado'] === 'programado'): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="cambiar_estado_ronda">
<input type="hidden" name="ronda_id" value="<?= $rondaId ?>">
<input type="hidden" name="estado" value="en_curso">
<button type="submit" class="admin-btn mini" title="Iniciar ronda"><i class="fa-solid fa-play"></i> Iniciar</button>
</form>
<?php endif; ?>
<?php if ($r['estado'] === 'en_curso'): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="cambiar_estado_ronda">
<input type="hidden" name="ronda_id" value="<?= $rondaId ?>">
<input type="hidden" name="estado" value="finalizada">
<button type="submit" class="admin-btn mini" title="Terminar ronda"><i class="fa-solid fa-flag-checkered"></i> Terminar</button>
</form>
<?php endif; ?>
<?php if (in_array($r['estado'], ['finalizada', 'cancelada'], true)): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="cambiar_estado_ronda">
<input type="hidden" name="ronda_id" value="<?= $rondaId ?>">
<input type="hidden" name="estado" value="en_curso">
<button type="submit" class="admin-btn ghost mini" title="Reabrir ronda"><i class="fa-solid fa-rotate-left"></i> Reabrir</button>
</form>
<?php endif; ?>
<?php if (in_array($r['estado'], ['programado', 'en_curso'], true)): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php"
onsubmit="return confirm('¿Cancelar la ronda <?= htmlspecialchars($r['nombre']) ?>? Se conservan sus partidos.');">
<input type="hidden" name="action" value="cambiar_estado_ronda">
<input type="hidden" name="ronda_id" value="<?= $rondaId ?>">
<input type="hidden" name="estado" value="cancelada">
<button type="submit" class="admin-btn ghost mini" title="Cancelar ronda"><i class="fa-solid fa-ban"></i> Cancelar</button>
</form>
<?php endif; ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php"
onsubmit="return confirm('¿Eliminar la ronda <?= htmlspecialchars($r['nombre']) ?>? Si tiene partidos no se podrá borrar.');">
<input type="hidden" name="action" value="eliminar_ronda">
<input type="hidden" name="ronda_id" value="<?= $rondaId ?>">
<button type="submit" class="admin-btn ghost mini danger" title="Eliminar ronda"><i class="fa-solid fa-trash"></i></button>
</form>
</div>
<?php if (empty($r['partidos'])): ?>
<p  class="muted-note">
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
<span  class="faint">vs</span>
<?php endif; ?>
<span class="teamx text-right" title="<?= htmlspecialchars($p['visitante_nombre'] ?? '') ?>">
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
<div class="match-acts">
<?php if ($p['estado'] !== 'cancelado'): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php"
onsubmit="return confirm('¿Quitar este partido de la ronda?');">
<input type="hidden" name="action" value="cambiar_estado_partido">
<input type="hidden" name="partido_id" value="<?= (int) $p['id'] ?>">
<input type="hidden" name="estado" value="cancelado">
<button type="submit" class="admin-btn ghost mini" title="Quitar partido de la ronda">
<i class="fa-solid fa-eye-slash"></i> Quitar
</button>
</form>
<?php endif; ?>
<?php if ($p['estado'] === 'cancelado'): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="cambiar_estado_partido">
<input type="hidden" name="partido_id" value="<?= (int) $p['id'] ?>">
<input type="hidden" name="estado" value="programado">
<button type="submit" class="admin-btn ghost mini" title="Restaurar partido">
<i class="fa-solid fa-rotate-left"></i> Restaurar
</button>
</form>
<?php endif; ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php"
onsubmit="return confirm('¿Eliminar este partido definitivamente? Si ya tiene eventos o define a otro partido no se podrá borrar.');">
<input type="hidden" name="action" value="eliminar_partido">
<input type="hidden" name="partido_id" value="<?= (int) $p['id'] ?>">
<button type="submit" class="admin-btn ghost mini danger" title="Eliminar partido">
<i class="fa-solid fa-trash"></i>
</button>
</form>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<!-- EDITAR RONDA -->
<details class="admin-collapse round-edit">
<summary class="admin-collapse-head">
<i class="fa-solid fa-pen"></i>
Editar ronda
</summary>
<form class="admin-form admin-collapse-body" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="editar_ronda">
<input type="hidden" name="ronda_id" value="<?= $rondaId ?>">
<div class="field">
<label for="rondaEditNombre<?= $rondaId ?>">Nombre</label>
<input type="text" id="rondaEditNombre<?= $rondaId ?>" name="nombre"
value="<?= htmlspecialchars($r['nombre']) ?>" maxlength="50" required>
</div>
<div class="field admin-field-xs">
<label for="rondaEditOrden<?= $rondaId ?>">Orden</label>
<input type="number" id="rondaEditOrden<?= $rondaId ?>" name="orden"
value="<?= (int) $r['orden'] ?>" min="1">
</div>
<button type="submit" class="admin-btn">Guardar cambios</button>
</form>
</details>
<!-- CREAR PARTIDO -->
<form class="admin-form round-add-form" method="POST" action="controllers/torneo.php"  class="admin-form tight">
<input type="hidden" name="action" value="crear_partido">
<input type="hidden" name="ronda_id" value="<?= (int) $r['id'] ?>">
<select name="equipo_local_id" required>
<option value="">— Equipo local —</option>
<?php foreach ($equiposOpt as $eq): ?>
<option value="<?= (int) $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
<?php endforeach; ?>
</select>
<select name="equipo_visitante_id" required>
<option value="">— Equipo visitante —</option>
<?php foreach ($equiposOpt as $eq): ?>
<option value="<?= (int) $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
<?php endforeach; ?>
</select>
<input type="datetime-local"
                                name="fecha_hora">
<input type="text"
                                name="cancha"
                                placeholder="Cancha (opcional)">
<input type="number"
                                name="posicion"
                                placeholder="Posición (auto)"
                                min="1">
<button type="submit" class="admin-btn">
                                + Agregar partido
                            </button>
</form>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- REGISTRO DE TORNEOS -->
<?php if (!empty($registroTorneos)): ?>
<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
<th>Torneo</th>
<th>Fechas</th>
<th>Equipos</th>
<th>Rondas</th>
<th>Partidos</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php foreach ($registroTorneos as $reg): ?>
<tr>
<td>
<strong><?= htmlspecialchars($reg['nombre']) ?></strong>
<?php if ((int) ($torneoSeleccionado['id'] ?? 0) === (int) $reg['id']): ?>
<span class="chip-info chip-inline">Viendo</span>
<?php endif; ?>
</td>
<td>
<?php if (!empty($reg['fecha_inicio'])): ?>
<?= htmlspecialchars($reg['fecha_inicio']) ?>
<?= !empty($reg['fecha_fin']) ? ' → ' . htmlspecialchars($reg['fecha_fin']) : '' ?>
<?php else: ?>
<span class="chip-muted chip-inline">Sin fechas</span>
<?php endif; ?>
</td>
<td><?= (int) $reg['equipos'] ?></td>
<td><?= (int) $reg['rondas'] ?></td>
<td><?= (int) $reg['partidos'] ?></td>
<td>
<span class="chip <?= htmlspecialchars($reg['estado']) ?>">
<?= $etiquetaEstadoTorneo[$reg['estado']] ?? $reg['estado'] ?>
</span>
</td>
<td class="registro-acts">
<a class="admin-btn ghost mini" href="dashboard.php?torneo_id=<?= (int) $reg['id'] ?>#view-torneo">
<i class="fa-solid fa-eye"></i> Ver
</a>
<?php if ($reg['estado'] === 'programado'): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="cambiar_estado_torneo">
<input type="hidden" name="torneo_id" value="<?= (int) $reg['id'] ?>">
<input type="hidden" name="estado" value="en_curso">
<button type="submit" class="admin-btn mini" title="Iniciar torneo"><i class="fa-solid fa-play"></i></button>
</form>
<?php endif; ?>
<?php if ($reg['estado'] === 'en_curso'): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="cambiar_estado_torneo">
<input type="hidden" name="torneo_id" value="<?= (int) $reg['id'] ?>">
<input type="hidden" name="estado" value="finalizado">
<button type="submit" class="admin-btn mini" title="Terminar torneo"><i class="fa-solid fa-flag-checkered"></i></button>
</form>
<?php endif; ?>
<?php if (in_array($reg['estado'], ['finalizado', 'cancelado'], true)): ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php">
<input type="hidden" name="action" value="cambiar_estado_torneo">
<input type="hidden" name="torneo_id" value="<?= (int) $reg['id'] ?>">
<input type="hidden" name="estado" value="en_curso">
<button type="submit" class="admin-btn ghost mini" title="Reabrir torneo"><i class="fa-solid fa-rotate-left"></i></button>
</form>
<?php endif; ?>
<form class="admin-form nostyle" method="POST" action="controllers/torneo.php"
onsubmit="return confirm('¿Eliminar &quot;<?= htmlspecialchars($reg['nombre']) ?>&quot;? Esta acción no se puede deshacer.');">
<input type="hidden" name="action" value="eliminar_torneo">
<input type="hidden" name="torneo_id" value="<?= (int) $reg['id'] ?>">
<button type="submit" class="admin-btn ghost mini danger" title="Eliminar torneo"><i class="fa-solid fa-trash"></i></button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<p class="registro-nota">
<i class="fa-solid fa-circle-info"></i>
Los torneos terminados o cancelados se conservan aquí como registro. Solo puedes eliminar los que aún no tienen rondas ni equipos inscritos.
</p>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
