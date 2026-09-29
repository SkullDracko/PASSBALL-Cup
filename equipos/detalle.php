<?php
/**
 * PASSBALL Cup - Detalle de Equipo (diseño estilo dashboard)
 * Topbar + banner lateral + tabs (Resumen/Jugadores/Alineación/Gestionar).
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

// Miembros con posición real (equipo_miembros.posicion usa códigos).
// Se proyecta em.* porque la columna posicion llega con la migración 001
// (sql/migraciones/001_equipo_miembros_posicion.sql): si la BD todavía no la
// tiene, nombrarla en el SELECT rompe la página entera.
$stmt = $pdo->prepare("
    SELECT em.*, u.matricula, u.nombre, u.avatar
    FROM equipo_miembros em
    JOIN usuarios u ON u.id = em.jugador_id
    WHERE em.equipo_id = ? AND em.estado = 'activo'
    ORDER BY (u.id = ?) DESC, em.fecha_union ASC
");
$stmt->execute([$equipoId, (int) $equipo['capitan_id']]);
$miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$codigoPosiciones = [
    'POR' => 'Portero',
    'DEF' => 'Defensa',
    'MED' => 'Mediocampista',
    'DEL' => 'Delantero',
];

$numeros = [
    'POR' => 1,
    'DEF' => 4,
    'MED' => 8,
    'DEL' => 10,
];

$esCapitan   = es_capitan($equipoId);
$estoyEnEste = false;
$yaTengoOtro = false;
$jugadores   = [];

foreach ($miembros as $m) {
    if ((int) $m['jugador_id'] === (int) $usuario['id']) {
        $estoyEnEste = true;
    }

    $posCode = !empty($m['posicion']) ? $m['posicion'] : 'DEL';

    $jugadores[] = [
        'id'           => (int) $m['id'],
        'jugador_id'   => (int) $m['jugador_id'],
        'nombre'       => $m['nombre'],
        'matricula'    => $m['matricula'],
        'avatar'       => $m['avatar'],
        'posicion'     => $codigoPosiciones[$posCode] ?? 'Delantero',
        'posicion_es'  => $posCode,
        'numero'       => $numeros[$posCode] ?? 0,
        'lider'        => (int) $m['jugador_id'] === (int) $equipo['capitan_id'],
    ];
}

if (!$estoyEnEste) {
    $stmt = $pdo->prepare("SELECT id FROM equipo_miembros WHERE jugador_id = ? AND estado = 'activo' LIMIT 1");
    $stmt->execute([$usuario['id']]);
    $yaTengoOtro = (bool) $stmt->fetch();
}

$es_lider    = $esCapitan;
$equipoLleno = (int) $equipo['total_miembros'] >= 12;
$logoUrl     = !empty($equipo['logo']) ? '../' . $equipo['logo'] : '';

$porcentaje = ((int) $equipo['total_miembros'] / 12) * 100;
$rolBanner  = $es_lider ? 'Líder' : ($estoyEnEste ? 'Jugador' : 'Invitado');
$matriculaUsuario = $usuario['matricula'] ?? 'Usuario';

$aliasUsuario = trim((string) ($usuario['alias'] ?? ''));

$nombreVisible = $aliasUsuario !== ''
    ? $aliasUsuario
    : ($usuario['nombre'] ?? ('Jugador ' . $matriculaUsuario));

$nombreUsuario = htmlspecialchars($nombreVisible, ENT_QUOTES, 'UTF-8');

$avatarUsuario = $usuario['avatar'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($equipo['nombre']) ?> |
        PASSBALL Cup
    </title>

    <link rel="icon" href="../assets/img/passball-cup.png" type="image/png">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="../assets/css/fa/all.min.css"
    >

    <!-- CSS GENERAL -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/components.css">

    <!-- TOPBAR COMPARTIDO (igual que el dashboard) -->
    <link rel="stylesheet" href="../assets/css/topbar.css">

    <!-- CSS DE ESTA PÁGINA -->
    <link rel="stylesheet" href="../assets/css/pages/ver-equipo.css">

</head>
<body>

<div class="app-layout">

    <!-- =========================================================
         TOPBAR (misma estructura que el dashboard)
    ========================================================== -->

    <header class="topbar">

        <div class="nav-left">

            <!-- ÁREA DE LOGOS -->

            <div class="brand-area">

                <div class="institutional-logos">

                    <!-- FACMED -->

                    <img
                        src="../assets/img/facmed.png"
                        alt="Facultad de Medicina UANL"
                        class="logo-facmed"
                    >

                    <!-- MEDPREV -->

                    <img
                        src="../assets/img/medprev.png"
                        alt="Medicina Preventiva y Salud Pública"
                        class="logo-medprev"
                    >

                    <!-- PASSWORD -->

                    <img
                        src="../assets/img/password.png"
                        alt="PASSWORD"
                        class="logo-password"
                    >

                </div>

                <!-- SEPARADOR -->

                <div class="logo-divider"></div>

                <!-- PASSBALL CUP -->

                <a
                    href="../dashboard.php"
                    class="topbar-logo"
                >

                    <img
                        src="../assets/img/passball-cup.png"
                        alt="PASSBALL Cup"
                        class="logo-passball"
                    >

                </a>

            </div>

        </div>

        <!-- NAVEGACIÓN (enlaces al SPA del dashboard) -->

        <nav class="topbar-nav">

            <a
                class="nav-item nav-tab"
                href="../dashboard.php#view-inicio"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M3 10.5L12 3l9 7.5v9a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-9z"/>
                </svg>

                <span>Inicio</span>

            </a>

            <a
                class="nav-item nav-tab active"
                href="../dashboard.php#view-equipos"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm-8 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm8 2c-3.3 0-6 1.8-6 4v2h12v-2c0-2.2-2.7-4-6-4zM8 13c-2.8 0-5 1.5-5 3.5V18h5v-2c0-1.1.4-2.1 1.1-3A6 6 0 0 0 8 13z"/>
                </svg>

                <span>Equipos</span>

            </a>

            <a
                class="nav-item nav-tab"
                href="../dashboard.php#view-partidos"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M7 2v2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7zm12 17H5V9h14v10zM7 11h4v3H7v-3z"/>
                </svg>

                <span>Partidos</span>

            </a>

            <a
                class="nav-item nav-tab"
                href="../dashboard.php#view-votos"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M12 2a10 10 0 1 0 10 10A10.01 10.01 0 0 0 12 2zm0 17a7 7 0 1 1 7-7 7 7 0 0 1-7 7zm1-11h-2v4H8v2h3v3h2v-3h3v-2h-3V8z"/>
                </svg>

                <span>Votos</span>

            </a>

            <a
                class="nav-item nav-tab"
                href="../dashboard.php#view-resultados"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M7 3h10v2h2a2 2 0 0 1 2 2c0 3.3-2.4 5.8-5.5 6.5A4.99 4.99 0 0 1 13 16.9V19h4v2H7v-2h4v-2.1a4.99 4.99 0 0 1-2.5-3.4C5.4 12.8 3 10.3 3 7a2 2 0 0 1 2-2h2V3zm-2 4c0 1.8 1.1 3.2 2.8 3.7L8 7H5zm14 0h-3l-.8 3.7C16.9 10.2 18 8.8 18 7h1z"/>
                </svg>

                <span>Resultados</span>

            </a>

            <a
                class="nav-item nav-tab"
                href="../dashboard.php#view-comunidad"
            >

                <svg viewBox="0 0 24 24">
                    <path d="M20 4H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h3v3l4-3h9a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zM6 9h12v2H6V9zm0 4h8v2H6v-2z"/>
                </svg>

                <span>Comunidad</span>

            </a>

        </nav>

        <!-- USUARIO -->

        <div class="topbar-user">

            <a
                class="topbar-profile"
                href="../dashboard.php#view-perfil"
                title="Mi perfil"
            >

                <span class="topbar-user-name">
                    <?= $nombreUsuario ?>
                </span>

                <div class="topbar-avatar">

                    <?php if (!empty($avatarUsuario)): ?>
                        <img
                            src="<?= htmlspecialchars($avatarUsuario, ENT_QUOTES, 'UTF-8') ?>"
                            alt="Foto de perfil"
                        >
                    <?php else: ?>
                        <svg viewBox="0 0 24 24">

                            <circle
                                cx="12"
                                cy="8"
                                r="4"
                            />

                            <path
                                d="M4 21c.7-4 3.3-6 8-6s7.3 2 8 6"
                            />

                        </svg>
                    <?php endif; ?>

                </div>

                <span class="topbar-chevron">
                    ⌄
                </span>

            </a>

            <a href="../controllers/logout.php" class="topbar-logout">

                <svg viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>

                <span>Salir</span>

            </a>

        </div>

        <!-- BOTÓN MENÚ MÓVIL -->

        <button
            type="button"
            class="menu-button"
            id="menuButton"
            aria-label="Abrir menú"
        >
            <i class="fa-solid fa-bars"></i>
        </button>

    </header>

    <!-- =========================================================
         CONTENIDO
    ========================================================== -->

    <main class="dashboard-container">

        <!-- =====================================================
             BANNER LATERAL
        ====================================================== -->

        <aside class="player-banner">

            <div class="banner-overlay"></div>

            <div class="banner-content">

                <div class="banner-avatar">

                    <?php if (!empty($avatarUsuario)): ?>
                        <img
                            src="<?= htmlspecialchars($avatarUsuario, ENT_QUOTES, 'UTF-8') ?>"
                            alt="Foto de perfil"
                        >
                    <?php else: ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>

                </div>

                <h2>
                    <?= $nombreUsuario ?>
                </h2>

                <span class="banner-role">
                    <?= htmlspecialchars($rolBanner) ?>
                </span>

                <div class="banner-separator"></div>

                <div class="banner-quote">
                    <span>“</span>

                    <p>
                        Cada partido<br>
                        es una oportunidad<br>
                        para ser campeón.
                    </p>
                </div>

                <strong class="banner-hashtag">
                    #PassballCup
                </strong>

            </div>

        </aside>

        <!-- =====================================================
             CONTENIDO PRINCIPAL
        ====================================================== -->

        <section class="team-content">

            <!-- VOLVER -->

            <div class="page-top">

                <a href="../dashboard.php#view-equipos" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver a equipos
                </a>

            </div>

            <!-- MENSAJES -->

            <div class="team-msg" id="msg"></div>

            <!-- =================================================
                 HEADER DEL EQUIPO
            ================================================== -->

            <section class="team-header-card">

                <div class="team-main-info">

                    <div class="team-logo-container">

                        <?php if ($logoUrl !== ''): ?>

                            <img
                                src="<?= htmlspecialchars($logoUrl) ?>"
                                alt="Logo <?= htmlspecialchars($equipo['nombre']) ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            >

                        <?php endif; ?>

                        <div class="team-logo-fallback" <?= $logoUrl !== '' ? '' : 'style="display:flex;"' ?>>
                            <?= htmlspecialchars(mb_strtoupper(mb_substr($equipo['nombre'], 0, 1, 'UTF-8'), 'UTF-8')) ?>
                        </div>

                    </div>

                    <div class="team-title">

                        <span class="eyebrow">
                            PASSBALL CUP
                        </span>

                        <h1>
                            <?= htmlspecialchars($equipo['nombre']) ?>
                        </h1>

                        <p class="team-leader">
                            <i class="fa-solid fa-star"></i>
                            Líder:
                            <strong>
                                <?= htmlspecialchars($equipo['capitan_nombre'] ?? '—') ?>
                            </strong>
                        </p>

                        <div class="team-status">

                            <span class="status-pill members">
                                <i class="fa-solid fa-users"></i>
                                <?= (int) $equipo['total_miembros'] ?>/12
                                jugadores
                            </span>

                            <span class="status-pill registered">
                                <i class="fa-solid fa-circle-check"></i>
                                Equipo registrado
                            </span>

                        </div>

                    </div>

                </div>

                <!-- CONTADOR -->

                <div class="team-counter">

                    <span>
                        MIEMBROS
                    </span>

                    <strong>
                        <?= (int) $equipo['total_miembros'] ?>
                        <small>/12</small>
                    </strong>

                    <div class="progress">

                        <div
                            class="progress-bar"
                            style="width: <?= $porcentaje ?>%;"
                        ></div>

                    </div>

                </div>

            </section>

            <!-- =================================================
                 TABS
            ================================================== -->

            <nav class="team-tabs <?= $es_lider ? 'has-manage' : '' ?>">

                <button class="team-tab active" data-tab="resumen">
                    <i class="fa-solid fa-house"></i>
                    <span>Resumen</span>
                </button>

                <button class="team-tab" data-tab="jugadores">
                    <i class="fa-solid fa-users"></i>
                    <span>Jugadores</span>
                </button>

                <button class="team-tab" data-tab="alineacion">
                    <i class="fa-solid fa-futbol"></i>
                    <span>Alineación</span>
                </button>

                <?php if ($es_lider): ?>

                    <button class="team-tab" data-tab="gestionar">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Gestionar</span>
                    </button>

                <?php endif; ?>

            </nav>

            <!-- =================================================
                 TAB RESUMEN
            ================================================== -->

            <div class="tab-content active" id="resumen">

                <div class="content-grid">

                    <!-- INFORMACIÓN -->

                    <section class="panel-card">

                        <div class="panel-header">

                            <div>
                                <span class="eyebrow">
                                    EQUIPO
                                </span>

                                <h2>
                                    Información del equipo
                                </h2>
                            </div>

                            <i class="fa-solid fa-shield-halved panel-icon"></i>

                        </div>

                        <div class="info-grid">

                            <div class="info-item">
                                <span>Nombre</span>
                                <strong><?= htmlspecialchars($equipo['nombre']) ?></strong>
                            </div>

                            <div class="info-item">
                                <span>Líder</span>
                                <strong><?= htmlspecialchars($equipo['capitan_nombre'] ?? '—') ?></strong>
                            </div>

                            <div class="info-item">
                                <span>Integrantes</span>
                                <strong><?= (int) $equipo['total_miembros'] ?>/12</strong>
                            </div>

                            <div class="info-item">
                                <span>Registrado</span>
                                <strong><?= htmlspecialchars(date('d/m/Y', strtotime($equipo['fecha_creacion']))) ?></strong>
                            </div>

                        </div>

                    </section>

                    <!-- ACCIONES -->

                    <section class="panel-card actions-card">

                        <div class="panel-header">

                            <div>
                                <h2>Acciones rápidas</h2>
                            </div>

                        </div>

                        <button class="quick-action purple" data-tab-target="alineacion">

                            <span class="action-icon">
                                <i class="fa-solid fa-futbol"></i>
                            </span>

                            <span class="action-text">
                                <strong>Crear alineación</strong>
                                <small>Organiza tu once titular</small>
                            </span>

                            <i class="fa-solid fa-chevron-right"></i>

                        </button>

                        <button class="quick-action orange" data-tab-target="jugadores">

                            <span class="action-icon">
                                <i class="fa-solid fa-users"></i>
                            </span>

                            <span class="action-text">
                                <strong>Gestionar jugadores</strong>
                                <small>Posiciones y plantilla</small>
                            </span>

                            <i class="fa-solid fa-chevron-right"></i>

                        </button>

                        <?php if ($es_lider): ?>

                            <div class="role-note">
                                <i class="fa-solid fa-crown"></i>
                                Eres el líder de este equipo.
                            </div>

                        <?php elseif ($estoyEnEste): ?>

                            <button class="quick-action danger-cta" onclick="salirEquipo()">

                                <span class="action-icon">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                </span>

                                <span class="action-text">
                                    <strong>Salir del equipo</strong>
                                    <small>Abandonar mi membresía</small>
                                </span>

                                <i class="fa-solid fa-chevron-right"></i>

                            </button>

                        <?php elseif ($yaTengoOtro): ?>

                            <div class="role-note">
                                <i class="fa-solid fa-circle-info"></i>
                                Ya perteneces a otro equipo.
                            </div>

                        <?php elseif ($equipoLleno): ?>

                            <div class="role-note">
                                <i class="fa-solid fa-circle-info"></i>
                                El equipo ya está lleno (máximo 12 miembros).
                            </div>

                        <?php else: ?>

                            <button class="quick-action join-cta" onclick="unirse(<?= (int) $equipo['id'] ?>)">

                                <span class="action-icon">
                                    <i class="fa-solid fa-user-plus"></i>
                                </span>

                                <span class="action-text">
                                    <strong>Unirme a este equipo</strong>
                                    <small>¿Listo para jugar?</small>
                                </span>

                                <i class="fa-solid fa-chevron-right"></i>

                            </button>

                        <?php endif; ?>

                    </section>

                </div>

                <!-- PLANTILLA -->

                <section class="panel-card roster-card">

                    <div class="panel-header">

                        <div>

                            <span class="eyebrow">
                                PLANTILLA
                            </span>

                            <h2>
                                Jugadores del equipo
                            </h2>

                        </div>

                        <span class="roster-count">
                            <?= count($jugadores) ?>/12
                        </span>

                    </div>

                    <div class="players-list">

                        <?php if (empty($jugadores)): ?>

                            <p style="color:var(--text-light);font-size:13px;">No hay jugadores.</p>

                        <?php else: ?>

                            <?php foreach ($jugadores as $jugador): ?>

                                <div class="player-row">

                                    <div class="player-avatar">
                                        <?php if (!empty($jugador['avatar'])): ?>
                                            <img
                                                src="<?= htmlspecialchars((strpos($jugador['avatar'], 'http') === 0 ? '' : '../') . $jugador['avatar']) ?>"
                                                alt=""
                                                style="width:100%;height:100%;border-radius:50%;object-fit:cover;"
                                                onerror="this.style.display='none'; this.parentElement.textContent='<?= htmlspecialchars(mb_strtoupper(mb_substr($jugador['nombre'], 0, 1, 'UTF-8'), 'UTF-8')) ?>';"
                                            >
                                        <?php else: ?>
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr($jugador['nombre'], 0, 1, 'UTF-8'), 'UTF-8')) ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="player-info">

                                        <strong><?= htmlspecialchars($jugador['nombre']) ?></strong>

                                        <span>Mat: <?= htmlspecialchars($jugador['matricula']) ?></span>

                                    </div>

                                    <div class="player-position">
                                        <?= htmlspecialchars($jugador['posicion']) ?>
                                    </div>

                                    <div class="player-number">
                                        #<?= (int) $jugador['numero'] ?>
                                    </div>

                                    <?php if ($jugador['lider']): ?>

                                        <span class="leader-badge">
                                            <i class="fa-solid fa-star"></i>
                                            Líder
                                        </span>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </section>

            </div>

            <!-- =================================================
                 TAB JUGADORES
            ================================================== -->

            <div class="tab-content" id="jugadores">

                <section class="panel-card">

                    <div class="panel-header">

                        <div>

                            <span class="eyebrow">
                                PLANTILLA
                            </span>

                            <h2>
                                Gestionar jugadores
                            </h2>

                            <p>
                                Consulta los integrantes y administra
                                sus posiciones dentro del equipo.
                            </p>

                        </div>

                        <?php if ($es_lider): ?>

                            <button class="primary-btn" data-tab-target="gestionar">
                                <i class="fa-solid fa-user-plus"></i>
                                Agregar jugador
                            </button>

                        <?php endif; ?>

                    </div>

                    <div class="players-management">

                        <?php foreach ($jugadores as $jugador): ?>

                            <div class="management-player">

                                <div class="player-avatar">
                                    <?php if (!empty($jugador['avatar'])): ?>
                                        <img
                                            src="<?= htmlspecialchars((strpos($jugador['avatar'], 'http') === 0 ? '' : '../') . $jugador['avatar']) ?>"
                                            alt=""
                                            style="width:100%;height:100%;border-radius:50%;object-fit:cover;"
                                            onerror="this.style.display='none'; this.parentElement.textContent='<?= htmlspecialchars(mb_strtoupper(mb_substr($jugador['nombre'], 0, 1, 'UTF-8'), 'UTF-8')) ?>';"
                                        >
                                    <?php else: ?>
                                        <?= htmlspecialchars(mb_strtoupper(mb_substr($jugador['nombre'], 0, 1, 'UTF-8'), 'UTF-8')) ?>
                                    <?php endif; ?>
                                </div>

                                <div class="player-info">

                                    <strong><?= htmlspecialchars($jugador['nombre']) ?></strong>

                                    <span><?= htmlspecialchars($jugador['matricula']) ?></span>

                                </div>

                                <?php if ($es_lider): ?>

                                    <select
                                        onchange="changePosition(
                                            <?= $jugador['id'] ?>,
                                            this.value
                                        )"
                                    >

                                        <?php foreach ($codigoPosiciones as $codigo => $nombrePos): ?>
                                            <option
                                                value="<?= $codigo ?>"
                                                <?= $jugador['posicion_es'] === $codigo ? 'selected' : '' ?>
                                            >
                                                <?= htmlspecialchars($nombrePos) ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>

                                    <?php if (!$jugador['lider']): ?>

                                        <button class="icon-btn" title="Eliminar" onclick="removePlayer(<?= $jugador['id'] ?>)">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <span class="select-note">
                                        <?= htmlspecialchars($jugador['posicion']) ?>
                                        <?= $jugador['lider'] ? '· Líder' : '' ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </section>

            </div>

            <!-- =================================================
                 TAB ALINEACIÓN
            ================================================== -->

            <div class="tab-content" id="alineacion">

                <section class="panel-card lineup-panel">

                    <div class="panel-header">

                        <div>

                            <span class="eyebrow">
                                ALINEACIÓN
                            </span>

                            <h2>
                                Creador de alineación
                            </h2>

                            <p>
                                Organiza a tus jugadores para el próximo partido.
                            </p>

                        </div>

                        <button class="primary-btn" onclick="saveLineup()">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar alineación
                        </button>

                    </div>

                    <div class="lineup-wrapper">

                        <!-- CANCHA -->

                        <div class="football-field">

                            <div class="field-line center-line"></div>

                            <div class="center-circle"></div>

                            <div class="penalty-box top"></div>
                            <div class="penalty-box bottom"></div>

                            <?php if (empty($jugadores)): ?>

                                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;">
                                    Sin jugadores para alinear
                                </div>

                            <?php else: ?>

                                <?php
                                $contadorDefensas = 0;

                                foreach ($jugadores as $jugador):
                                    $claseCampo = 'player-mid';

                                    if ($jugador['posicion_es'] === 'POR') {
                                        $claseCampo = 'player-goalkeeper';
                                    } elseif ($jugador['posicion_es'] === 'DEF') {
                                        $claseCampo = $contadorDefensas === 0
                                            ? 'player-defense-left'
                                            : 'player-defense-right';
                                        $contadorDefensas++;
                                    } elseif ($jugador['posicion_es'] === 'DEL') {
                                        $claseCampo = 'player-forward';
                                    }
                                ?>

                                    <div
                                        class="field-player <?= $claseCampo ?>"
                                        draggable="true"
                                        data-player="<?= (int) $jugador['id'] ?>"
                                    >
                                        <span><?= (int) $jugador['numero'] ?></span>
                                        <small>Jugador</small>
                                    </div>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </div>

                        <!-- CONFIGURACIÓN -->

                        <div class="lineup-settings">

                            <label>
                                Nombre de alineación
                            </label>

                            <input
                                type="text"
                                value="Alineación titular"
                            >

                            <label>
                                Formación
                            </label>

                            <select>

                                <option>1 - 2 - 1</option>
                                <option>1 - 2 - 2</option>
                                <option>1 - 3 - 1</option>
                                <option>1 - 2 - 2 - 1</option>

                            </select>

                            <div class="lineup-help">

                                <i class="fa-solid fa-circle-info"></i>
                                Arrastra los jugadores sobre la cancha
                                para cambiar su posición.

                            </div>

                        </div>

                    </div>

                </section>

            </div>

            <!-- =================================================
                 TAB GESTIONAR (solo líder)
            ================================================== -->

            <?php if ($es_lider): ?>

                <div class="tab-content" id="gestionar">

                    <section class="panel-card" style="max-width:560px;">

                        <div class="panel-header">

                            <div>

                                <span class="eyebrow">
                                    PLANTILLA
                                </span>

                                <h2>
                                    Agregar jugador
                                </h2>

                                <p>
                                    Busca a un jugador por su nombre o matrícula
                                    y agrégalo al equipo.
                                </p>

                            </div>

                        </div>

                        <div class="lineup-settings">

                            <label for="agregarBusqueda">
                                Jugador
                            </label>

                            <input
                                type="text"
                                id="agregarBusqueda"
                                maxlength="60"
                                autocomplete="off"
                                placeholder="Ej. PB00027"
                            >

                            <div class="member-search" id="agregarResultados"></div>

                            <div class="lineup-help">

                                <i class="fa-solid fa-circle-info"></i>
                                Escribe al menos 2 letras. El equipo admite
                                máximo 12 integrantes.

                            </div>

                        </div>

                    </section>

                </div>

            <?php endif; ?>

        </section>

    </main>

    <!-- =========================================================
         FOOTER
    ========================================================== -->

    <footer class="dashboard-footer">
        © 2026 PASSBALL Cup — Todos los derechos reservados.
    </footer>

</div>

<!-- =============================================================
     (El buscador para agregar jugadores vive dentro del tab Gestionar)
============================================================= -->

<script>

document.addEventListener('DOMContentLoaded', () => {

    const tabs = document.querySelectorAll('.team-tab');
    const contents = document.querySelectorAll('.tab-content');

    function activateTab(tabName) {

        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === tabName);
        });

        contents.forEach(content => {
            content.classList.toggle('active', content.id === tabName);
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            activateTab(tab.dataset.tab);
        });
    });

    document.querySelectorAll('[data-tab-target]').forEach(button => {
        button.addEventListener('click', () => {
            activateTab(button.dataset.tabTarget);
        });
    });

    /* MENÚ MÓVIL (botón hamburguesa + topbar-nav, igual que dashboard) */

    const menuButton = document.getElementById('menuButton');
    const topbarNav = document.querySelector('.topbar-nav');

    if (menuButton && topbarNav) {

        function closeMobileNav() {
            topbarNav.classList.remove('mobile-open');
            menuButton.innerHTML = '<i class="fa-solid fa-bars"></i>';
        }

        menuButton.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = topbarNav.classList.toggle('mobile-open');
            menuButton.innerHTML = isOpen
                ? '<i class="fa-solid fa-xmark"></i>'
                : '<i class="fa-solid fa-bars"></i>';
        });

        topbarNav.querySelectorAll('.nav-tab')
            .forEach(function (link) {
                link.addEventListener('click', closeMobileNav);
            });

        document.addEventListener('click', function (e) {
            if (
                topbarNav.classList.contains('mobile-open') &&
                !topbarNav.contains(e.target) &&
                e.target !== menuButton &&
                !menuButton.contains(e.target)
            ) {
                closeMobileNav();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 1024) {
                closeMobileNav();
            }
        });

    }

});

/* === Provisional mientras llega assets/js + mensajes === */

function msg(text, ok) {
    const el = document.getElementById('msg');
    if (!el) return;
    el.textContent = text;
    el.className = 'team-msg ' + (ok ? 'ok' : 'err');
    clearTimeout(msg._t);
    msg._t = setTimeout(() => { el.className = 'team-msg'; }, 4000);
}

function api(action, data, cb) {
    const body = new URLSearchParams(data);
    body.append('action', action);

    fetch('../controllers/equiposController.php', {
        method: 'POST',
        body: body
    })
    .then(r => r.json())
    .then(cb)
    .catch(() => msg('Error de conexión. Intenta de nuevo.', false));
}

function unirse(id) {
    if (!confirm('¿Quieres unirte a este equipo?')) return;
    api('unirse', { equipo_id: id }, d => {
        msg(d.message, d.success);
        if (d.success) setTimeout(() => location.reload(), 900);
    });
}

function salirEquipo() {
    if (!confirm('¿Salir de tu equipo actual?')) return;
    api('salir', {}, d => {
        msg(d.message, d.success);
        if (d.success) setTimeout(() => location.reload(), 900);
    });
}

/* =============================================================
     AGREGAR JUGADOR
     POST /api/equipos/{equipoId}/miembros  →  { jugador_id }
     GET  /api/usuarios/buscar?q=…        →  candidatos
     El tab Gestionar sólo se renderiza para el capitán, y la API
     vuelve a validarlo con requireCapitanOAdmin().
   ============================================================= */

<?php if ($es_lider): ?>

const API_BASE = '../backend/api';
const EQUIPO_ID = <?= (int) $equipo['id'] ?>;
const EQUIPO_LLENO = <?= $equipoLleno ? 'true' : 'false' ?>;

const inputAgregar = document.getElementById('agregarBusqueda');
const boxAgregar = document.getElementById('agregarResultados');

function apiRequest(url, options) {
    return fetch(url, Object.assign({ credentials: 'same-origin' }, options))
        .then(r => r.json().catch(() => ({})).then(payload => {
            if (!r.ok || !payload.exito) {
                throw new Error(
                    (payload.errores && payload.errores.error)
                        ? payload.errores.error
                        : 'No se pudo completar la operación.'
                );
            }

            return payload.data || {};
        }));
}

if (inputAgregar && boxAgregar) {

    let temporizador = null;

    function cerrarResultados() {
        boxAgregar.innerHTML = '';
        boxAgregar.classList.remove('show');
    }

    function pintarVacio(texto) {
        const aviso = document.createElement('p');
        aviso.className = 'member-vacio';
        aviso.textContent = texto;
        boxAgregar.appendChild(aviso);
        boxAgregar.classList.add('show');
    }

    function crearAvatarResultado(u) {

        const avatar = document.createElement('span');
        avatar.className = 'member-result-avatar';

        if (u.avatar) {

            const img = document.createElement('img');
            img.src = u.avatar;
            img.alt = '';
            avatar.appendChild(img);

        } else {

            avatar.textContent = (u.nombre || '?').trim().charAt(0).toUpperCase();

        }

        return avatar;

    }

    function pintarResultado(u) {

        const enOtroEquipo = parseInt(u.en_equipo, 10) > 0;

        const item = document.createElement('div');
        item.className = 'member-result' + (enOtroEquipo ? ' disabled' : '');

        const info = document.createElement('div');
        info.className = 'member-result-info';

        const nombre = document.createElement('strong');
        nombre.textContent = u.nombre || 'Jugador';

        const matricula = document.createElement('span');
        matricula.textContent = 'Mat: ' + u.matricula;

        info.appendChild(nombre);
        info.appendChild(matricula);

        const estado = document.createElement('span');
        estado.className = 'member-result-estado';
        estado.textContent = enOtroEquipo ? 'En otro equipo' : 'Agregar';

        item.appendChild(crearAvatarResultado(u));
        item.appendChild(info);
        item.appendChild(estado);

        if (!enOtroEquipo) {
            item.addEventListener('click', () => agregarJugador(u, item));
        }

        boxAgregar.appendChild(item);

    }

    function buscarJugadores(q) {

        apiRequest(API_BASE + '/usuarios/buscar?q=' + encodeURIComponent(q))
            .then(data => {

                boxAgregar.innerHTML = '';

                const usuarios = Array.isArray(data.usuarios) ? data.usuarios : [];

                if (!usuarios.length) {

                    pintarVacio('Sin resultados.');

                    return;

                }

                usuarios.forEach(pintarResultado);
                boxAgregar.classList.add('show');

            })
            .catch(error => pintarVacio(error.message));

    }

    function agregarJugador(u, item) {

        if (EQUIPO_LLENO) {
            msg('El equipo ya está lleno (máximo 12 miembros).', false);
            return;
        }

        if (!confirm('¿Agregar a ' + (u.nombre || 'este jugador') + ' al equipo?')) return;

        const estado = item.querySelector('.member-result-estado');
        estado.textContent = 'Agregando…';
        item.classList.add('loading');

        apiRequest(API_BASE + '/equipos/' + EQUIPO_ID + '/miembros', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ jugador_id: u.id })
        })
            .then(data => {

                msg(data.mensaje || 'Jugador agregado al equipo', true);

                /* El contador, la plantilla y las tabs se calculan en PHP */
                setTimeout(() => location.reload(), 900);

            })
            .catch(error => {

                msg(error.message, false);
                estado.textContent = 'Agregar';
                item.classList.remove('loading');

            });

    }

    inputAgregar.addEventListener('input', function () {

        clearTimeout(temporizador);

        const q = this.value.trim();

        if (q.length < 2) {
            cerrarResultados();
            return;
        }

        temporizador = setTimeout(() => buscarJugadores(q), 250);

    });

    inputAgregar.addEventListener('keydown', function (e) {

        if (e.key === 'Escape') cerrarResultados();

    });

    document.addEventListener('click', function (e) {

        if (
            boxAgregar.classList.contains('show') &&
            !boxAgregar.contains(e.target) &&
            !inputAgregar.contains(e.target)
        ) {
            cerrarResultados();
        }

    });

}

<?php endif; ?>

</script>

</body>
</html>