<?php
/**
 * PASSBALL Cup - Admin: Votaciones
 * Crea categorías de votación y administra candidatos
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$torneos = $pdo->query("SELECT id, nombre, estado FROM torneos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$torneoIdSel = (int) ($_GET['torneo_id'] ?? 0);
if ($torneoIdSel <= 0 && !empty($torneos)) $torneoIdSel = (int) $torneos[0]['id'];

$categorias = [];
$equiposTorneo = [];
$jugadoresTorneo = [];

if ($torneoIdSel> 0) {

    $stmt = $pdo->prepare("
        SELECT c.*,
               (SELECT COUNT(*) FROM torneo_votos v WHERE v.categoria_id = c.id) AS total_votos
        FROM torneo_categorias_voto c
        WHERE c.torneo_id = ?
        ORDER BY c.orden, c.id
    ");
    $stmt->execute([$torneoIdSel]);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT e.id, e.nombre, e.logo
        FROM equipos e
        JOIN torneo_equipos te ON te.equipo_id = e.id
        WHERE te.torneo_id = ? AND te.estado = 'aprobado'
        ORDER BY e.nombre
    ");
    $stmt->execute([$torneoIdSel]);
    $equiposTorneo = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.nombre, e.nombre AS equipo
        FROM equipo_miembros em
        JOIN usuarios u ON u.id = em.jugador_id
        JOIN equipos e ON e.id = em.equipo_id
        JOIN torneo_equipos te ON te.equipo_id = e.id
        WHERE te.torneo_id = ? AND te.estado = 'aprobado' AND em.estado = 'activo'
        ORDER BY u.nombre
    ");
    $stmt->execute([$torneoIdSel]);
    $jugadoresTorneo = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$ajustesPorCategoria = [];

if (!empty($categorias)) {
    $stmt = $pdo->query("
        SELECT cc.id AS ajuste_id, cc.categoria_id, cc.ajuste,
               cc.jugador_id, cc.equipo_id,
               u.nombre AS jugador_nombre, e.nombre AS equipo_nombre
        FROM torneo_categoria_candidatos cc
        LEFT JOIN usuarios u ON u.id = cc.jugador_id
        LEFT JOIN equipos e ON e.id = cc.equipo_id
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $aj) {
        $ajustesPorCategoria[(int) $aj['categoria_id']][] = $aj;
    }
}

function votoNombre(array $aj): string
{
    return $aj['jugador_nombre'] ?: ($aj['equipo_nombre'] ?? '—');
}
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
<span class="admin-view-head-ico"><i class="fa-solid fa-star"></i></span>
<div>
<h2 class="admin-section-title">Votaciones</h2>
<p class="admin-section-sub">Crea categorías, define candidatos y controla cuándo abren las votaciones.</p>
</div>
</div>
<!-- Selector torneo -->
<form class="admin-toolbar" method="GET" action="dashboard.php#view-votaciones">
<div class="field admin-field-md">
<label>Torneo</label>
<select name="torneo_id" onchange="this.form.submit()">
<?php foreach ($torneos as $t): ?>
<option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === $torneoIdSel ? 'selected' : '' ?>>
<?= htmlspecialchars($t['nombre']) ?>
</option>
<?php endforeach; ?>
</select>
</div>
</form>
<!-- Crear categoría -->
<div class="admin-card">
<h3 class="admin-card-title">Nueva categoría de votación</h3>
<form class="admin-form" method="POST" action="controllers/votaciones.php">
<input type="hidden" name="action" value="crear_categoria">
<input type="hidden" name="torneo_id" value="<?= $torneoIdSel ?>">
<div class="field admin-field-md">
<label>Clave</label>
<input type="text" name="clave" placeholder="ej. mejor_goleador" required>
</div>
<div class="field admin-field-lg">
<label>Nombre</label>
<input type="text" name="nombre" placeholder="ej. Goleador del torneo" required>
</div>
<div class="field">
<label>Tipo</label>
<select name="tipo">
<option value="jugador">Jugador</option>
<option value="equipo">Equipo</option>
</select>
</div>
<div class="field">
<label>Candidatos</label>
<select name="modo_candidatos">
<option value="automatico">Automático (todos)</option>
<option value="manual">Manual (solo incluidos)</option>
</select>
</div>
<div class="field admin-field-sm">
<label>Orden</label>
<input type="number" name="orden" min="0" value="0">
</div>
<div class="admin-row">
<label class="admin-check">
<input type="checkbox" name="abierta" value="1"> Abrir votación
            </label>
<button type="submit" class="admin-btn">Crear categoría</button>
</div>
</form>
</div>
<!-- Listado -->
<?php if (empty($categorias)): ?>
<div class="admin-stub">
<h3>Sin categorías de votación</h3>
<p>Crea la primera categoría del torneo.</p>
</div>
<?php else: ?>
<?php foreach ($categorias as $cat): ?>
<div class="admin-card">
<div class="admin-card-head">
<div>
<strong  class="small-title"><?= htmlspecialchars($cat['nombre']) ?></strong>
<div class="admin-meta">
<?= htmlspecialchars($cat['clave']) ?> · <?= $cat['tipo'] ?> · modo <?= $cat['modo_candidatos'] ?>
                        · <?= (int) $cat['total_votos'] ?> votos
                    </div>
</div>
<div class="admin-row gap-sm">
<span class="chip <?= $cat['estado'] === 'abierta' ? 'finalizado' : 'programado' ?>">
<?= $cat['estado'] === 'abierta' ? 'Abierta' : 'Cerrada' ?>
</span>
<form method="POST" action="controllers/votaciones.php" class="admin-form nostyle">
<input type="hidden" name="action" value="cambiar_estado">
<input type="hidden" name="categoria_id" value="<?= $cat['id'] ?>">
<button type="submit" class="admin-btn ghost mini">
<?= $cat['estado'] === 'abierta' ? 'Cerrar' : 'Abrir' ?>
</button>
</form>
<form method="POST" action="controllers/votaciones.php" class="admin-form nostyle" onsubmit="return confirm('Eliminar esta categoría y sus votos?');">
<input type="hidden" name="action" value="eliminar_categoria">
<input type="hidden" name="categoria_id" value="<?= $cat['id'] ?>">
<button type="submit" class="admin-btn ghost mini danger">Eliminar</button>
</form>
</div>
</div>
<!-- Ajustes actuales -->
<?php $ajustes = $ajustesPorCategoria[(int) $cat['id']] ?? []; ?>
<?php if ($ajustes): ?>
<div  class="mt-sm">
<div class="admin-label-mini">Ajustes de candidatos</div>
<div class="admin-chips">
<?php foreach ($ajustes as $aj): ?>
<span class="admin-chip-inline">
<?= $aj['ajuste'] === 'incluir' ? '+' : '-' ?>&nbsp;<?= htmlspecialchars(votoNombre($aj)) ?>
<form method="POST" action="controllers/votaciones.php" class="admin-form nostyle">
<input type="hidden" name="action" value="eliminar_candidato">
<input type="hidden" name="candidato_id" value="<?= (int) $aj['ajuste_id'] ?>">
<button type="submit" class="admin-remove">×</button>
</form>
</span>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>
<!-- Agregar / excluir candidato -->
<div class="bracket admin-row gap-sm mt-sm">
<form class="admin-form tight" method="POST" action="controllers/votaciones.php"
<input type="hidden" name="action" value="agregar_candidato">
<input type="hidden" name="categoria_id" value="<?= $cat['id'] ?>">
<input type="hidden" name="tipo_candidato" value="<?= $cat['tipo'] ?>">
<label class="admin-label-inline">Incluir candidato</label>
<select name="<?= $cat['tipo'] === 'jugador' ? 'jugador_id' : 'equipo_id' ?>">
<option value="">— Seleccionar —</option>
<?php if ($cat['tipo'] === 'jugador'): ?>
<?php foreach ($jugadoresTorneo as $jg): ?>
<option value="<?= (int) $jg['id'] ?>"><?= htmlspecialchars($jg['nombre'] . ' (' . $jg['equipo'] . ')') ?></option>
<?php endforeach; ?>
<?php else: ?>
<?php foreach ($equiposTorneo as $eq): ?>
<option value="<?= (int) $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
<?php endforeach; ?>
<?php endif; ?>
</select>
<button type="submit" class="admin-btn ghost mini">Incluir</button>
</form>
<form class="admin-form tight" method="POST" action="controllers/votaciones.php"
<input type="hidden" name="action" value="excluir_candidato">
<input type="hidden" name="categoria_id" value="<?= $cat['id'] ?>">
<input type="hidden" name="tipo_candidato" value="<?= $cat['tipo'] ?>">
<label class="admin-label-inline">Excluir candidato</label>
<select name="<?= $cat['tipo'] === 'jugador' ? 'jugador_id' : 'equipo_id' ?>">
<option value="">— Seleccionar —</option>
<?php if ($cat['tipo'] === 'jugador'): ?>
<?php foreach ($jugadoresTorneo as $jg): ?>
<option value="<?= (int) $jg['id'] ?>"><?= htmlspecialchars($jg['nombre'] . ' (' . $jg['equipo'] . ')') ?></option>
<?php endforeach; ?>
<?php else: ?>
<?php foreach ($equiposTorneo as $eq): ?>
<option value="<?= (int) $eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
<?php endforeach; ?>
<?php endif; ?>
</select>
<button type="submit" class="admin-btn ghost mini danger">Excluir</button>
</form>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>