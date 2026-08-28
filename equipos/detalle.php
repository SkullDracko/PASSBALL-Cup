<?php
/**
 * PASSBALL Cup - Detalle de Equipo
 */
require_once __DIR__ . '/../controllers/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

$equipoId = (int)($_GET['id'] ?? 0);

if ($equipoId <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT e.*, u.nombre AS lider_nombre, u.apellidop AS lider_apellidop, u.apellidom AS lider_apellidom,
           (SELECT COUNT(*) FROM equipo_miembros em WHERE em.equipo_id = e.id AND em.estado = 'activo') AS total_miembros
    FROM equipos e
    JOIN usuarios_passball u ON u.id = e.lider_id
    WHERE e.id = ? AND e.activo = 1
");
$stmt->execute([$equipoId]);
$equipo = $stmt->fetch();

if (!$equipo) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.matricula, u.nombre, u.apellidop, u.apellidom, u.semestre, u.avatar,
           em.fecha_union, em.estado
    FROM equipo_miembros em
    JOIN usuarios_passball u ON u.id = em.usuario_id
    WHERE em.equipo_id = ? AND em.estado = 'activo'
    ORDER BY em.fecha_union ASC
");
$stmt->execute([$equipoId]);
$miembros = $stmt->fetchAll();

$esLiderDeEste = ($usuario['id'] == $equipo['lider_id']);

$stmt = $pdo->prepare("SELECT id FROM equipo_miembros WHERE usuario_id = ? AND estado = 'activo'");
$stmt->execute([$usuario['id']]);
$yaTieneEquipo = $stmt->fetch() !== false;

$tituloPagina = $equipo['nombre'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-header">
    <div class="section-header-left">
        <a href="index.php" class="btn btn-secondary btn-sm">← Volver</a>
        <h1 class="section-title"><?= htmlspecialchars($equipo['nombre']) ?></h1>
    </div>
</div>

<div class="grid-2 detail-layout">
    <div>
        <div class="card team-info-card" style="border-top: 4px solid <?= htmlspecialchars($equipo['color_equipo']) ?>;">
            <div class="team-info-header">
                <div class="team-avatar-lg" style="background: <?= htmlspecialchars($equipo['color_equipo']) ?>;">
                    <?= strtoupper(substr($equipo['nombre'], 0, 2)) ?>
                </div>
                <div>
                    <h2 class="team-info-name"><?= htmlspecialchars($equipo['nombre']) ?></h2>
                    <p class="text-muted">
                        Líder: <?= htmlspecialchars($equipo['lider_apellidop'] . ' ' . $equipo['lider_apellidom'] . ' ' . $equipo['lider_nombre']) ?>
                    </p>
                </div>
            </div>

            <?php if ($equipo['descripcion']): ?>
                <p class="team-description"><?= nl2br(htmlspecialchars($equipo['descripcion'])) ?></p>
            <?php endif; ?>

            <div class="team-stats">
                <div>
                    <span class="stat-number stat-number-sm"><?= $equipo['total_miembros'] ?></span>
                    <span class="stat-label">/ 7 Miembros</span>
                </div>
                <div>
                    <span class="text-muted text-sm">
                        Creado: <?= date('d/m/Y', strtotime($equipo['fecha_creacion'])) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">👥 Miembros (<?= $equipo['total_miembros'] ?>/7)</h3>
            </div>

            <?php if (empty($miembros)): ?>
                <p class="text-muted text-center">No hay miembros aún</p>
            <?php else: ?>
                <div class="members-list">
                    <?php foreach ($miembros as $i => $m): ?>
                        <div class="member-item">
                            <div class="member-avatar" style="background: <?= htmlspecialchars($equipo['color_equipo']) ?>;">
                                <?= strtoupper(substr($m['nombre'], 0, 1) . substr($m['apellidop'], 0, 1)) ?>
                            </div>
                            <div class="member-info">
                                <strong><?= htmlspecialchars($m['apellidop'] . ' ' . $m['apellidom'] . ' ' . $m['nombre']) ?></strong>
                                <span class="text-muted text-sm">
                                    Mat: <?= $m['matricula'] ?> · Sem <?= $m['semestre'] ?>
                                </span>
                            </div>
                            <div class="member-actions">
                                <?php if ($m['id'] == $equipo['lider_id']): ?>
                                    <span class="badge badge-lider">⭐ Líder</span>
                                <?php endif; ?>
                                <?php if ($esLiderDeEste && $m['id'] != $usuario['id']): ?>
                                    <button class="btn-remove-member" data-id="<?= $m['id'] ?>" title="Eliminar">✕</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="card sidebar-card">
            <h3 class="card-title">Acciones</h3>

            <?php if ($yaTieneEquipo && !$esLiderDeEste): ?>
                <p class="text-muted text-sm">Ya perteneces a otro equipo</p>
            <?php elseif ($esLiderDeEste): ?>
                <p class="text-muted text-sm">Eres el líder de este equipo</p>
            <?php elseif ($equipo['total_miembros'] >= 7): ?>
                <p class="text-muted text-sm">Equipo lleno</p>
            <?php elseif ($yaTieneEquipo): ?>
                <p class="text-muted text-sm">Ya tienes un equipo</p>
            <?php else: ?>
                <button class="btn btn-primary" id="btnUnirse" data-equipo-id="<?= $equipo['id'] ?>">
                    Unirme a este equipo
                </button>
            <?php endif; ?>

            <?php if ($esLiderDeEste): ?>
                <button class="btn btn-secondary btn-full" onclick="eliminarEquipo(<?= $equipo['id'] ?>)">
                    🗑️ Eliminar Equipo
                </button>
            <?php endif; ?>

            <?php if ($yaTieneEquipo && !$esLiderDeEste): ?>
                <button class="btn btn-secondary btn-full" onclick="salirEquipo()">
                    Salir de mi equipo
                </button>
            <?php endif; ?>
        </div>

        <div class="card sidebar-card">
            <h3 class="card-title">🎨 Color</h3>
            <div class="team-color-preview" style="background: <?= htmlspecialchars($equipo['color_equipo']) ?>;"></div>
        </div>
    </div>
</div>

<script src="../assets/js/app.js"></script>
<script src="../assets/js/equipos.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
