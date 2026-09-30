<?php
/**
 * PASSBALL Cup - Admin: Torneo y Rondas (POST handler)
 * Acciones: crear_ronda, crear_partido
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php#view-torneo");
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {

    /* =============================
       CREAR RONDA
       ============================= */
    case 'crear_ronda':

        $torneoId = (int) ($_POST['torneo_id'] ?? 0);
        $nombre   = trim($_POST['nombre'] ?? '');
        $orden    = (int) ($_POST['orden'] ?? 0);

        if ($torneoId <= 0 || $nombre === '') {
            $_SESSION['flash_error'] = 'Torneo y nombre de ronda son obligatorios.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        if ($orden <= 0) {
            // Auto: siguiente orden disponible
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(orden), 0) + 1 FROM torneo_rondas WHERE torneo_id = ?");
            $stmt->execute([$torneoId]);
            $orden = (int) $stmt->fetchColumn();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO torneo_rondas (torneo_id, nombre, orden) VALUES (?, ?, ?)");
            $stmt->execute([$torneoId, $nombre, $orden]);
            $_SESSION['flash_success'] = "Ronda \"{$nombre}\" creada.";
        } catch (PDOException $e) {
            error_log("crear_ronda: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al crear la ronda.';
        }

        header("Location: ../dashboard.php#view-torneo");
        exit;

    /* =============================
       CREAR PARTIDO
       ============================= */
    case 'crear_partido':

        $rondaId        = (int) ($_POST['ronda_id'] ?? 0);
        $localId        = (int) ($_POST['equipo_local_id'] ?? 0);
        $visitanteId    = (int) ($_POST['equipo_visitante_id'] ?? 0);
        $fechaHora      = trim($_POST['fecha_hora'] ?? '');
        $cancha         = trim($_POST['cancha'] ?? '');
        $posicion       = (int) ($_POST['posicion'] ?? 0);

        if ($rondaId <= 0 || $localId <= 0 || $visitanteId <= 0) {
            $_SESSION['flash_error'] = 'Ronda y ambos equipos son obligatorios.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        if ($localId === $visitanteId) {
            $_SESSION['flash_error'] = 'Un equipo no puede jugar contra sí mismo.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        // La ronda debe existir
        $stmt = $pdo->prepare("SELECT id FROM torneo_rondas WHERE id = ?");
        $stmt->execute([$rondaId]);
        if (!$stmt->fetch()) {
            $_SESSION['flash_error'] = 'La ronda no existe.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        // Los equipos deben existir y estar activos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM equipos WHERE id IN (?, ?) AND estado = 'activo'");
        $stmt->execute([$localId, $visitanteId]);
        if ($stmt->fetchColumn() < 2) {
            $_SESSION['flash_error'] = 'Uno de los equipos no existe o está inactivo.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        if ($posicion <= 0) {
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(posicion), 0) + 1 FROM partidos WHERE ronda_id = ?");
            $stmt->execute([$rondaId]);
            $posicion = (int) $stmt->fetchColumn();
        }

        $fechaHora = $fechaHora !== '' ? $fechaHora : null;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO partidos
                    (ronda_id, equipo_local_id, equipo_visitante_id, posicion, fecha_hora, cancha, estado)
                VALUES (?, ?, ?, ?, ?, ?, 'programado')
            ");
            $stmt->execute([$rondaId, $localId, $visitanteId, $posicion, $fechaHora, $cancha ?: null]);

            $_SESSION['flash_success'] = 'Partido creado correctamente.';
        } catch (PDOException $e) {
            error_log("crear_partido: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al crear el partido.';
        }

        header("Location: ../dashboard.php#view-torneo");
        exit;

    default:
        $_SESSION['flash_error'] = 'Acción no válida.';
        header("Location: ../dashboard.php#view-torneo");
        exit;
}