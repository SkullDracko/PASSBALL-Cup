<?php
/**
 * PASSBALL Cup - Listado de Equipos
 */
$tituloPagina = 'Equipos';
require_once __DIR__ . '/../controllers/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

// Obtener todos los equipos con conteo de miembros
$busqueda = trim($_GET['q'] ?? '');

$sql = "
    SELECT e.*, 
           u.nombre AS lider_nombre, u.apellidop AS lider_apellidop,
           (SELECT COUNT(*) FROM equipo_miembros em WHERE em.equipo_id = e.id AND em.estado = 'activo') AS total_miembros
    FROM equipos e
    JOIN usuarios_passball u ON u.id = e.lider_id
    WHERE e.activo = 1
";
$params = [];

if ($busqueda !== '') {
    $sql .= " AND e.nombre LIKE ?";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY e.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipos = $stmt->fetchAll();

// Verificar si el usuario ya tiene equipo
$stmt = $pdo->prepare("
    SELECT e.id, e.nombre 
    FROM equipo_miembros em
    JOIN equipos e ON e.id = em.equipo_id
    WHERE em.usuario_id = ? AND em.estado = 'activo' AND e.activo = 1
");
$stmt->execute([$usuario['id']]);
$miEquipo = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-header">
    <h1 class="section-title">⚽ Equipos</h1>
    <div style="display: flex; gap: 0.75rem; align-items: center;">
        <form method="GET" style="display: flex; gap: 0.5rem;">
            <input type="text" name="q" value="<?= htmlspecialchars($busqueda) ?>" 
                   placeholder="Buscar equipo..." 
                   class="input-search">
            <button type="submit" class="btn btn-secondary btn-sm">🔍 Buscar</button>
        </form>
        <?php if (!$miEquipo): ?>
            <a href="crear.php" class="btn btn-primary btn-sm">+ Crear Equipo</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($miEquipo): ?>
<div class="alert alert-success">
    Ya perteneces al equipo <strong><?= htmlspecialchars($miEquipo['nombre']) ?></strong>
    — <a href="detalle.php?id=<?= $miEquipo['id'] ?>">Ver mi equipo →</a>
</div>
<?php endif; ?>

<?php if (empty($equipos)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">🏟️</div>
            <p>No hay equipos registrados aún</p>
            <?php if (!$miEquipo): ?>
                <a href="crear.php" class="btn btn-primary" style="margin-top: 1rem;">Sé el primero en crear un equipo</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="grid-3">
        <?php foreach ($equipos as $eq): ?>
            <a href="detalle.php?id=<?= $eq['id'] ?>" class="card team-card" style="border-top: 4px solid <?= htmlspecialchars($eq['color_equipo']) ?>;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div class="team-avatar" style="background: <?= htmlspecialchars($eq['color_equipo']) ?>;">
                        <?= strtoupper(substr($eq['nombre'], 0, 2)) ?>
                    </div>
                    <div>
                        <h3 class="card-title" style="margin-bottom: 0;"><?= htmlspecialchars($eq['nombre']) ?></h3>
                        <p style="font-size: 0.8rem; color: var(--primary-mid);">
                            Líder: <?= htmlspecialchars($eq['lider_apellidop'] . ' ' . $eq['lider_nombre']) ?>
                        </p>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="badge badge-miembro">
                        <?= $eq['total_miembros'] ?>/7 miembros
                    </span>
                    <span style="font-size: 0.75rem; color: var(--gray);">
                        <?= date('d/m/Y', strtotime($eq['fecha_creacion'])) ?>
                    </span>
                </div>
                <?php if ($eq['descripcion']): ?>
                    <p style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--primary-mid); line-height: 1.4;">
                        <?= htmlspecialchars(mb_strimwidth($eq['descripcion'], 0, 80, '...')) ?>
                    </p>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
