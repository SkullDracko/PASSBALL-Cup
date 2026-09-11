<?php
/**
 * PASSBALL Cup - Listado de Equipos (BD definitiva)
 */
require_once __DIR__ . '/../controllers/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

$busqueda = trim($_GET['q'] ?? '');

$sql = "
    SELECT
        e.*,
        u.nombre AS capitan_nombre,
        (SELECT COUNT(*) FROM equipo_miembros em
         WHERE em.equipo_id = e.id AND em.estado = 'activo') AS total_miembros
    FROM equipos e
    LEFT JOIN usuarios u ON u.id = e.capitan_id
    WHERE e.estado = 'activo'
";
$params = [];

if ($busqueda !== '') {
    $sql .= " AND e.nombre LIKE ?";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY e.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mi equipo
$miEquipo = null;

$stmt = $pdo->prepare("
    SELECT e.id, e.nombre
    FROM equipo_miembros em
    JOIN equipos e ON e.id = em.equipo_id
    WHERE em.jugador_id = ? AND em.estado = 'activo' AND e.estado = 'activo'
");
$stmt->execute([$usuario['id']]);
$miEquipo = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipos | <?= TORNEO_NOMBRE ?></title>
    <link rel="icon" href="../assets/img/passball-cup.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-dark: #2f1e50;
            --primary-mid:  #543b67;
            --primary:      #7c4293;
            --primary-light:#b99ac8;
            --accent:       #e79eed;
            --bg:           #f4f0f7;
            --text:         #2e2e2e;
            --shadow:       0 2px 12px rgba(47,30,80,0.10);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding-bottom: 40px;
        }

        .top {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-mid));
            color: #fff;
            padding: 22px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .top h1 { font-size: 20px; font-weight: 800; }

        .top a {
            color: #fff;
            background: rgba(255,255,255,0.14);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .top a:hover { background: rgba(255,255,255,0.25); }

        .wrap { max-width: 1100px; margin: 0 auto; padding: 24px 20px; }

        .toolbar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 20px;
        }

        .toolbar form { display: flex; gap: 8px; flex: 1; max-width: 460px; }

        .toolbar input {
            flex: 1;
            padding: 10px 14px;
            border: 1.5px solid #d8cfe8;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        .toolbar input:focus { border-color: var(--primary); }

        .btn {
            display: inline-block;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .btn.secondary { background: #e8e0f2; color: var(--primary-mid); }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e8f7ee;
            color: #1a7f3a;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .badge a { color: #1a7f3a; font-weight: 800; }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
        }

        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: var(--shadow);
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .card-head { display: flex; align-items: center; gap: 12px; }

        .logo {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: #fff;
            font-weight: 800;
            font-size: 18px;
            flex-shrink: 0;
        }

        .logo img { width: 100%; height: 100%; object-fit: cover; }

        .card h3 { font-size: 16px; font-weight: 700; }

        .muted { color: #777; font-size: 13px; }

        .empty { text-align: center; padding: 60px 20px; color: #777; }

        .empty strong { color: var(--text); }
    </style>
</head>
<body>

    <div class="top">
        <h1>⚽ Equipos del torneo</h1>
        <a href="../dashboard.php#view-equipos">← Volver al panel</a>
    </div>

    <div class="wrap">

        <div class="toolbar">
            <form method="GET">
                <input
                    type="text"
                    name="q"
                    value="<?= htmlspecialchars($busqueda) ?>"
                    placeholder="Buscar equipo..."
                >
                <button type="submit" class="btn">🔍 Buscar</button>
            </form>
            <a href="../dashboard.php#view-equipos" class="btn secondary">+ Registrar equipo</a>
        </div>

        <?php if ($miEquipo): ?>
            <div class="badge">
                🏠 Ya perteneces a
                <a href="detalle.php?id=<?= (int) $miEquipo['id'] ?>">
                    <?= htmlspecialchars($miEquipo['nombre']) ?> →
                </a>
            </div>
        <?php endif; ?>

        <?php if (empty($equipos)): ?>

            <div class="empty">
                <p style="font-size: 34px; margin-bottom: 10px;">🏟️</p>
                <strong>No hay equipos registrados aún</strong>
            </div>

        <?php else: ?>

            <div class="grid">

                <?php foreach ($equipos as $eq): ?>

                    <a class="card" href="detalle.php?id=<?= (int) $eq['id'] ?>" style="text-decoration:none;color:inherit;">

                        <div class="card-head">

                            <div class="logo">
                                <?php if (!empty($eq['logo'])): ?>
                                    <img src="<?= htmlspecialchars($eq['logo']) ?>" alt="<?= htmlspecialchars($eq['nombre']) ?>">
                                <?php else: ?>
                                    <?= htmlspecialchars(mb_strtoupper(mb_substr($eq['nombre'], 0, 1))) ?>
                                <?php endif; ?>
                            </div>

                            <div>
                                <h3><?= htmlspecialchars($eq['nombre']) ?></h3>
                                <div class="muted">
                                    Líder: <?= htmlspecialchars($eq['capitan_nombre'] ?? '—') ?>
                                </div>
                            </div>

                        </div>

                        <div class="muted">
                            👥 <?= (int) $eq['total_miembros'] ?>/7 miembros ·
                            📅 <?= date('d/m/Y', strtotime($eq['fecha_creacion'])) ?>
                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</body>
</html>