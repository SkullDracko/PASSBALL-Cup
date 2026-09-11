<?php
/**
 * PASSBALL Cup - Detalle de Equipo (BD definitiva)
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
    SELECT e.*, u.nombre AS capitan_nombre,
           (SELECT COUNT(*) FROM equipo_miembros em
            WHERE em.equipo_id = e.id AND em.estado = 'activo') AS total_miembros
    FROM equipos e
    LEFT JOIN usuarios u ON u.id = e.capitan_id
    WHERE e.id = ? AND e.estado = 'activo'
");
$stmt->execute([$equipoId]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.matricula, u.nombre, u.avatar, em.fecha_union
    FROM equipo_miembros em
    JOIN usuarios u ON u.id = em.jugador_id
    WHERE em.equipo_id = ? AND em.estado = 'activo'
    ORDER BY em.fecha_union ASC
");
$stmt->execute([$equipoId]);
$miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$esCapitan    = es_capitan($equipoId);
$estoyEnEste  = false;
$yaTengoOtro  = false;

foreach ($miembros as $m) {
    if ((int) $m['id'] === (int) $usuario['id']) {
        $estoyEnEste = true;
        break;
    }
}

if (!$estoyEnEste) {
    $stmt = $pdo->prepare("SELECT id FROM equipo_miembros WHERE jugador_id = ? AND estado = 'activo' LIMIT 1");
    $stmt->execute([$usuario['id']]);
    $yaTengoOtro = (bool) $stmt->fetch();
}

$equipoLleno = (int) $equipo['total_miembros'] >= 7;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($equipo['nombre']) ?> | <?= TORNEO_NOMBRE ?></title>
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

        .layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
        }

        @media (max-width: 780px) { .layout { grid-template-columns: 1fr; } }

        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        .team-head { display: flex; align-items: center; gap: 16px; }

        .logo {
            width: 70px;
            height: 70px;
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: #fff;
            font-weight: 800;
            font-size: 24px;
            flex-shrink: 0;
        }

        .logo img { width: 100%; height: 100%; object-fit: cover; }

        .team-head h2 { font-size: 20px; font-weight: 800; }

        .muted { color: #777; font-size: 13px; }

        .card h3 { font-size: 15px; font-weight: 800; margin-bottom: 14px; }

        .member {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f0ecf5;
        }

        .member:last-child { border-bottom: none; }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .avatar img { width: 100%; height: 100%; object-fit: cover; }

        .member .grow { flex: 1; }

        .member strong { font-size: 14px; }

        .tag {
            display: inline-block;
            background: #8a5fb8;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 20px;
            margin-left: 8px;
        }

        .remove-btn {
            background: #fdeeee;
            color: #b3261e;
            border: none;
            border-radius: 6px;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn {
            display: block;
            width: 100%;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn.secondary { background: #e8e0f2; color: var(--primary-mid); }

        .btn:disabled { opacity: 0.6; cursor: not-allowed; }

        .notice {
            background: #f5f0fa;
            border-radius: 8px;
            padding: 12px;
            font-size: 13px;
            color: var(--primary-mid);
            margin-top: 10px;
        }

        #msg {
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
        }

        #msg.ok { display:block; background:#e8f7ee; color:#1a7f3a; }
        #msg.err { display:block; background:#fdeeee; color:#b3261e; }
    </style>
</head>
<body>

    <div class="top">
        <h1>🏠 <?= htmlspecialchars($equipo['nombre']) ?></h1>
        <a href="index.php">← Volver a equipos</a>
    </div>

    <div class="wrap">

        <div id="msg"></div>

        <div class="layout">

            <div>

                <div class="card">

                    <div class="team-head">

                        <div class="logo">
                            <?php if (!empty($equipo['logo'])): ?>
                                <img src="<?= htmlspecialchars($equipo['logo']) ?>" alt="<?= htmlspecialchars($equipo['nombre']) ?>">
                            <?php else: ?>
                                <?= htmlspecialchars(mb_strtoupper(mb_substr($equipo['nombre'], 0, 2))) ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h2><?= htmlspecialchars($equipo['nombre']) ?></h2>
                            <div class="muted">
                                Líder: <?= htmlspecialchars($equipo['capitan_nombre'] ?? '—') ?>
                            </div>
                            <div class="muted">
                                👥 <?= (int) $equipo['total_miembros'] ?>/7 miembros ·
                                📅 Creado <?= date('d/m/Y', strtotime($equipo['fecha_creacion'])) ?>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="card">

                    <h3>Miembros (<?= (int) $equipo['total_miembros'] ?>/7)</h3>

                    <?php if (empty($miembros)): ?>
                        <div class="muted">No hay miembros aún.</div>
                    <?php else: ?>
                        <?php foreach ($miembros as $i => $m): ?>
                            <div class="member">

                                <div class="avatar">
                                    <?php if (!empty($m['avatar'])): ?>
                                        <img src="<?= htmlspecialchars($m['avatar']) ?>" alt="">
                                    <?php else: ?>
                                        <?= htmlspecialchars(mb_strtoupper(mb_substr($m['nombre'], 0, 1))) ?>
                                    <?php endif; ?>
                                </div>

                                <div class="grow">
                                    <strong><?= htmlspecialchars($m['nombre']) ?></strong>

                                    <?php if ((int) $m['id'] === (int) $equipo['capitan_id']): ?>
                                        <span class="tag">⭐ Líder</span>
                                    <?php endif; ?>

                                    <?php if ((int) $m['id'] === (int) $usuario['id']): ?>
                                        <span class="tag" style="background:#4caf50;">Tú</span>
                                    <?php endif; ?>

                                    <div class="muted">Mat: <?= htmlspecialchars($m['matricula']) ?></div>
                                </div>

                                <?php if ($esCapitan && (int) $m['id'] !== (int) $usuario['id']): ?>
                                    <button
                                        type="button"
                                        class="remove-btn"
                                        title="Eliminar del equipo"
                                        onclick="eliminarMiembro(<?= (int) $m['id'] ?>)"
                                    >✕</button>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>

            </div>

            <div>

                <div class="card">

                    <h3>Acciones</h3>

                    <?php if ($estoyEnEste && $esCapitan): ?>

                        <div class="notice">Eres el líder de este equipo.</div>

                    <?php elseif ($estoyEnEste): ?>

                        <button type="button" class="btn secondary" onclick="salirEquipo()">Salir de mi equipo</button>

                    <?php elseif ($yaTengoOtro): ?>

                        <div class="notice">Ya perteneces a otro equipo.</div>

                    <?php elseif ($equipoLleno): ?>

                        <div class="notice">El equipo ya está lleno (máximo 7 miembros).</div>

                    <?php else: ?>

                        <button type="button" class="btn" onclick="unirse(<?= (int) $equipo['id'] ?>)">Unirme a este equipo</button>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

    <script>
        function api(action, data, cb) {
            var body = new URLSearchParams(data);
            body.append('action', action);

            fetch('../controllers/equiposController.php', {
                method: 'POST',
                body: body
            })
            .then(function (r) { return r.json(); })
            .then(cb)
            .catch(function () {
                msg('Error de conexión. Intenta de nuevo.', false);
            });
        }

        function msg(text, ok) {
            var el = document.getElementById('msg');
            el.textContent = text;
            el.className = ok ? 'ok' : 'err';
        }

        function unirse(id) {
            if (!confirm('¿Quieres unirte a este equipo?')) return;
            api('unirse', { equipo_id: id }, function (d) {
                msg(d.message, d.success);
                if (d.success) setTimeout(function () { location.reload(); }, 900);
            });
        }

        function salirEquipo() {
            if (!confirm('¿Salir de tu equipo actual?')) return;
            api('salir', {}, function (d) {
                msg(d.message, d.success);
                if (d.success) setTimeout(function () { location.reload(); }, 900);
            });
        }

        function eliminarMiembro(miembroId) {
            if (!confirm('¿Eliminar a este miembro del equipo?')) return;
            api('eliminar_miembro', {
                equipo_id: <?= (int) $equipo['id'] ?>,
                miembro_id: miembroId
            }, function (d) {
                msg(d.message, d.success);
                if (d.success) setTimeout(function () { location.reload(); }, 900);
            });
        }
    </script>

</body>
</html>