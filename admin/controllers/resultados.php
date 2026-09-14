<?php
/**
 * PASSBALL Cup - Admin: Resultados (POST handler)
 * Acciones: actualizar_partido, agregar_gol
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php#view-resultados");
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {

    /* =============================
       ACTUALIZAR PARTIDO
       ============================= */
    case 'actualizar_partido':

        $partidoId       = (int) ($_POST['partido_id'] ?? 0);
        $golesLocal      = $_POST['goles_local'] !== '' ? (int) $_POST['goles_local'] : null;
        $golesVisitante  = $_POST['goles_visitante'] !== '' ? (int) $_POST['goles_visitante'] : null;
        $penalesLocal    = $_POST['penales_local'] !== '' ? (int) $_POST['penales_local'] : null;
        $penalesVisitante = $_POST['penales_visitante'] !== '' ? (int) $_POST['penales_visitante'] : null;
        $ganadorId       = (int) ($_POST['ganador_id'] ?? 0) ?: null;
        $estado          = $_POST['estado'] ?? 'programado';

        if ($partidoId <= 0) {
            $_SESSION['flash_error'] = 'ID de partido inválido.';
            header("Location: ../dashboard.php#view-resultados");
            exit;
        }

        $estadosValidos = ['programado', 'en_curso', 'finalizado', 'cancelado'];
        if (!in_array($estado, $estadosValidos, true)) {
            $estado = 'programado';
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE partidos
                SET goles_local = ?, goles_visitante = ?,
                    penales_local = ?, penales_visitante = ?,
                    ganador_id = ?, estado = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $golesLocal, $golesVisitante,
                $penalesLocal, $penalesVisitante,
                $ganadorId, $estado,
                $partidoId,
            ]);

            $_SESSION['flash_success'] = 'Resultado actualizado.';
        } catch (PDOException $e) {
            error_log("actualizar_partido: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al actualizar el partido.';
        }

        header("Location: ../dashboard.php#view-resultados");
        exit;

    /* =============================
       AGREGAR GOL / EVENTO
       ============================= */
    case 'agregar_gol':

        $partidoId = (int) ($_POST['partido_id'] ?? 0);
        $jugadorId = (int) ($_POST['jugador_id'] ?? 0);
        $equipoId  = (int) ($_POST['equipo_id'] ?? 0);
        $tipo      = $_POST['tipo'] ?? 'gol';
        $minuto    = $_POST['minuto'] !== '' ? (int) $_POST['minuto'] : null;
        $asistId   = (int) ($_POST['asistencia_jugador_id'] ?? 0) ?: null;

        if ($partidoId <= 0 || $jugadorId <= 0 || $equipoId <= 0) {
            $_SESSION['flash_error'] = 'Faltan datos del evento.';
            header("Location: ../dashboard.php#view-resultados");
            exit;
        }

        $tiposValidos = ['gol', 'autogol', 'penal_anotado', 'tarjeta_amarilla', 'tarjeta_roja'];
        if (!in_array($tipo, $tiposValidos, true)) {
            $tipo = 'gol';
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO partido_eventos
                    (partido_id, jugador_id, equipo_id, tipo, minuto, asistencia_jugador_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$partidoId, $jugadorId, $equipoId, $tipo, $minuto, $asistId]);

            $_SESSION['flash_success'] = 'Evento registrado.';
        } catch (PDOException $e) {
            error_log("agregar_gol: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al registrar el evento.';
        }

        header("Location: ../dashboard.php#view-resultados");
        exit;

    default:
        $_SESSION['flash_error'] = 'Acción no válida.';
        header("Location: ../dashboard.php#view-resultados");
        exit;
}