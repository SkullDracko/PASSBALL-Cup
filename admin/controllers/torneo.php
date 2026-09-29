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

    /* =============================
       CREAR TORNEO
       ============================= */
    case 'crear_torneo':

        $nombre      = trim($_POST['nombre'] ?? '');
        $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
        $fechaFin    = trim($_POST['fecha_fin'] ?? '');
        $estado      = $_POST['estado'] ?? 'programado';

        $estadosValidos = ['programado', 'en_curso', 'finalizado', 'cancelado'];

        if ($nombre === '') {
            $_SESSION['flash_error'] = 'El nombre del torneo es obligatorio.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
            $_SESSION['flash_error'] = 'El nombre debe tener entre 3 y 100 caracteres.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        // Fechas opcionales (YYYY-MM-DD)
        $parsearFecha = function (string $valor) {

            if ($valor === '') {
                return ['ok' => true, 'valor' => null];
            }

            $fecha  = DateTime::createFromFormat('!Y-m-d', $valor);
            $errores = DateTime::getLastErrors();

            if (
                $fecha === false ||
                (is_array($errores) && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))
            ) {
                return ['ok' => false, 'valor' => null];
            }

            return ['ok' => true, 'valor' => $fecha->format('Y-m-d')];
        };

        $inicio = $parsearFecha($fechaInicio);
        $fin    = $parsearFecha($fechaFin);

        if (!$inicio['ok'] || !$fin['ok']) {
            $_SESSION['flash_error'] = 'Las fechas no son válidas.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        if ($inicio['valor'] && $fin['valor'] && $fin['valor'] < $inicio['valor']) {
            $_SESSION['flash_error'] = 'La fecha de fin no puede ser anterior a la de inicio.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        if (!in_array($estado, $estadosValidos, true)) {
            $estado = 'programado';
        }

        try {

            $stmt = $pdo->prepare("
                INSERT INTO torneos (nombre, tipo, fecha_inicio, fecha_fin, estado)
                VALUES (?, 'eliminacion_directa', ?, ?, ?)
            ");

            $stmt->execute([$nombre, $inicio['valor'], $fin['valor'], $estado]);

            $nuevoTorneoId = (int) $pdo->lastInsertId();

            $_SESSION['flash_success'] = "Torneo \"{$nombre}\" creado correctamente.";

            header("Location: ../dashboard.php?torneo_id={$nuevoTorneoId}#view-torneo");
            exit;

        } catch (PDOException $e) {

            error_log("crear_torneo: " . $e->getMessage());

            $_SESSION['flash_error'] = 'Error al crear el torneo.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

    /* =============================
       CAMBIAR ESTADO DEL TORNEO
       (iniciar / finalizar / cancelar / reabrir)
       ============================= */
    case 'cambiar_estado_torneo':

        $torneoId = (int) ($_POST['torneo_id'] ?? 0);
        $estado   = $_POST['estado'] ?? '';

        $estadosValidos = ['programado', 'en_curso', 'finalizado', 'cancelado'];

        if ($torneoId <= 0 || !in_array($estado, $estadosValidos, true)) {
            $_SESSION['flash_error'] = 'Acción no válida.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        try {

            $stmt = $pdo->prepare("SELECT nombre, estado FROM torneos WHERE id = ?");
            $stmt->execute([$torneoId]);
            $torneo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$torneo) {
                $_SESSION['flash_error'] = 'El torneo no existe.';
                header("Location: ../dashboard.php#view-torneo");
                exit;
            }

            if ($torneo['estado'] === $estado) {
                $_SESSION['flash_error'] = 'El torneo ya está en ese estado.';
                header("Location: ../dashboard.php?torneo_id={$torneoId}#view-torneo");
                exit;
            }

            $stmt = $pdo->prepare("UPDATE torneos SET estado = ? WHERE id = ?");
            $stmt->execute([$estado, $torneoId]);

            $etiquetas = [
                'programado'  => 'Programado',
                'en_curso'    => 'En curso',
                'finalizado'  => 'Finalizado',
                'cancelado'   => 'Cancelado',
            ];

            $_SESSION['flash_success'] = "Torneo \"{$torneo['nombre']}\" marcado como {$etiquetas[$estado]}.";

            header("Location: ../dashboard.php?torneo_id={$torneoId}#view-torneo");
            exit;

        } catch (PDOException $e) {

            error_log("cambiar_estado_torneo: " . $e->getMessage());

            $_SESSION['flash_error'] = 'Error al cambiar el estado del torneo.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

    /* =============================
       ELIMINAR TORNEO
       ============================= */
    case 'eliminar_torneo':

        $torneoId = (int) ($_POST['torneo_id'] ?? 0);

        if ($torneoId <= 0) {
            $_SESSION['flash_error'] = 'Acción no válida.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        try {

            $stmt = $pdo->prepare("SELECT nombre FROM torneos WHERE id = ?");
            $stmt->execute([$torneoId]);
            $nombreTorneo = $stmt->fetchColumn();

            if (!$nombreTorneo) {
                $_SESSION['flash_error'] = 'El torneo no existe.';
                header("Location: ../dashboard.php#view-torneo");
                exit;
            }

            // Se impide borrar si el torneo ya tiene información registrada
            $stmt = $pdo->prepare("
                SELECT
                    (SELECT COUNT(*) FROM torneo_rondas          WHERE torneo_id = ?) AS rondas,
                    (SELECT COUNT(*) FROM torneo_equipos         WHERE torneo_id = ?) AS equipos,
                    (SELECT COUNT(*) FROM torneo_categorias_voto  WHERE torneo_id = ?) AS categorias,
                    (SELECT COUNT(*) FROM torneo_votos            WHERE torneo_id = ?) AS votos,
                    (SELECT COUNT(*)
                       FROM partidos
                       JOIN torneo_rondas ON torneo_rondas.id = partidos.ronda_id
                      WHERE torneo_rondas.torneo_id = ?)            AS partidos
            ");
            $stmt->execute([$torneoId, $torneoId, $torneoId, $torneoId, $torneoId]);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC);

            $dependencias = array_filter([
                'rondas'      => (int) $datos['rondas'],
                'equipos'     => (int) $datos['equipos'],
                'partidos'    => (int) $datos['partidos'],
                'categorías'  => (int) $datos['categorias'],
                'votos'       => (int) $datos['votos'],
            ]);

            if ($dependencias) {

                $detalle = [];

                foreach ($dependencias as $tipo => $cantidad) {
                    $detalle[] = "{$cantidad} {$tipo}";
                }

                $_SESSION['flash_error'] =
                    "No se puede eliminar \"{$nombreTorneo}\": tiene " . implode(', ', $detalle) . '. ' .
                    'Marca el torneo como Finalizado o Cancelado para conservarlo en el registro.';

                header("Location: ../dashboard.php?torneo_id={$torneoId}#view-torneo");
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM torneos WHERE id = ?");
            $stmt->execute([$torneoId]);

            $_SESSION['flash_success'] = "Torneo \"{$nombreTorneo}\" eliminado.";

            header("Location: ../dashboard.php#view-torneo");
            exit;

        } catch (PDOException $e) {

            error_log("eliminar_torneo: " . $e->getMessage());

            $_SESSION['flash_error'] = 'Error al eliminar el torneo.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

    /* =============================
       EDITAR RONDA
       ============================= */
    case 'editar_ronda':

        $rondaId = (int) ($_POST['ronda_id'] ?? 0);
        $nombre  = trim($_POST['nombre'] ?? '');
        $orden   = trim($_POST['orden'] ?? '');

        if ($rondaId <= 0) {
            $_SESSION['flash_error'] = 'Acción no válida.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        if ($nombre === '') {
            $_SESSION['flash_error'] = 'El nombre de la ronda es obligatorio.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        if (mb_strlen($nombre) > 50) {
            $_SESSION['flash_error'] = 'El nombre no puede exceder 50 caracteres.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        if ($orden === '' || (int) $orden < 1) {
            $orden = null;   // se conserva el orden actual
        } else {
            $orden = (int) $orden;
        }

        try {

            $stmt = $pdo->prepare("SELECT torneo_id, nombre, orden FROM torneo_rondas WHERE id = ?");
            $stmt->execute([$rondaId]);
            $ronda = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ronda) {
                $_SESSION['flash_error'] = 'La ronda no existe.';
                header("Location: ../dashboard.php#view-torneo");
                exit;
            }

            $nuevoOrden = $orden ?? (int) $ronda['orden'];

            if ($orden !== null && $orden !== (int) $ronda['orden']) {
                $stmt = $pdo->prepare("
                    SELECT nombre FROM torneo_rondas
                    WHERE torneo_id = ? AND orden = ? AND id <> ?
                ");
                $stmt->execute([$ronda['torneo_id'], $orden, $rondaId]);

                if ($stmt->fetchColumn()) {
                    $_SESSION['flash_error'] = "Ya existe otra ronda con el orden {$orden} en este torneo.";
                    header("Location: ../dashboard.php?torneo_id={$ronda['torneo_id']}#view-torneo");
                    exit;
                }
            }

            $stmt = $pdo->prepare("UPDATE torneo_rondas SET nombre = ?, orden = ? WHERE id = ?");
            $stmt->execute([$nombre, $nuevoOrden, $rondaId]);

            $_SESSION['flash_success'] = "Ronda \"{$nombre}\" actualizada.";

            header("Location: ../dashboard.php?torneo_id={$ronda['torneo_id']}#view-torneo");
            exit;

        } catch (PDOException $e) {

            error_log("editar_ronda: " . $e->getMessage());

            $_SESSION['flash_error'] = 'Error al actualizar la ronda.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

    /* =============================
       CAMBIAR ESTADO DE LA RONDA
       (iniciar / finalizar / cancelar / reabrir)
       ============================= */
    case 'cambiar_estado_ronda':

        $rondaId = (int) ($_POST['ronda_id'] ?? 0);
        $estado  = $_POST['estado'] ?? '';

        $estadosValidos = ['programado', 'en_curso', 'finalizada', 'cancelada'];

        if ($rondaId <= 0 || !in_array($estado, $estadosValidos, true)) {
            $_SESSION['flash_error'] = 'Acción no válida.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        try {

            $stmt = $pdo->prepare("SELECT nombre, estado, torneo_id FROM torneo_rondas WHERE id = ?");
            $stmt->execute([$rondaId]);
            $ronda = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ronda) {
                $_SESSION['flash_error'] = 'La ronda no existe.';
                header("Location: ../dashboard.php#view-torneo");
                exit;
            }

            if ($ronda['estado'] === $estado) {
                $_SESSION['flash_error'] = 'La ronda ya está en ese estado.';
                header("Location: ../dashboard.php?torneo_id={$ronda['torneo_id']}#view-torneo");
                exit;
            }

            $stmt = $pdo->prepare("UPDATE torneo_rondas SET estado = ? WHERE id = ?");
            $stmt->execute([$estado, $rondaId]);

            $etiquetas = [
                'programado' => 'Programada',
                'en_curso'   => 'En curso',
                'finalizada' => 'Finalizada',
                'cancelada'  => 'Cancelada',
            ];

            $_SESSION['flash_success'] = "Ronda \"{$ronda['nombre']}\" marcada como {$etiquetas[$estado]}.";

            header("Location: ../dashboard.php?torneo_id={$ronda['torneo_id']}#view-torneo");
            exit;

        } catch (PDOException $e) {

            error_log("cambiar_estado_ronda: " . $e->getMessage());

            $_SESSION['flash_error'] = 'Error al cambiar el estado de la ronda.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

    /* =============================
       ELIMINAR RONDA
       ============================= */
    case 'eliminar_ronda':

        $rondaId = (int) ($_POST['ronda_id'] ?? 0);

        if ($rondaId <= 0) {
            $_SESSION['flash_error'] = 'Acción no válida.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        try {

            $stmt = $pdo->prepare("SELECT nombre, torneo_id FROM torneo_rondas WHERE id = ?");
            $stmt->execute([$rondaId]);
            $ronda = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ronda) {
                $_SESSION['flash_error'] = 'La ronda no existe.';
                header("Location: ../dashboard.php#view-torneo");
                exit;
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM partidos WHERE ronda_id = ?");
            $stmt->execute([$rondaId]);
            $partidos = (int) $stmt->fetchColumn();

            if ($partidos > 0) {
                $_SESSION['flash_error'] =
                    "No se puede eliminar \"{$ronda['nombre']}\": tiene {$partidos} partidos. " .
                    'Quita los partidos o cancela la ronda para conservarla en el registro.';
                header("Location: ../dashboard.php?torneo_id={$ronda['torneo_id']}#view-torneo");
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM torneo_rondas WHERE id = ?");
            $stmt->execute([$rondaId]);

            $_SESSION['flash_success'] = "Ronda \"{$ronda['nombre']}\" eliminada.";

            header("Location: ../dashboard.php?torneo_id={$ronda['torneo_id']}#view-torneo");
            exit;

        } catch (PDOException $e) {

            error_log("eliminar_ronda: " . $e->getMessage());

            $_SESSION['flash_error'] = 'Error al eliminar la ronda.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

    /* =============================
       CAMBIAR ESTADO DEL PARTIDO
       (programado / en_curso / finalizado / cancelado)
       ============================= */
    case 'cambiar_estado_partido':

        $partidoId = (int) ($_POST['partido_id'] ?? 0);
        $estado    = $_POST['estado'] ?? '';

        $estadosValidos = ['programado', 'en_curso', 'finalizado', 'cancelado'];

        if ($partidoId <= 0 || !in_array($estado, $estadosValidos, true)) {
            $_SESSION['flash_error'] = 'Acción no válida.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        try {

            $stmt = $pdo->prepare("
                SELECT p.estado, p.ronda_id, r.torneo_id
                FROM partidos p
                LEFT JOIN torneo_rondas r ON r.id = p.ronda_id
                WHERE p.id = ?
            ");
            $stmt->execute([$partidoId]);
            $partido = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$partido) {
                $_SESSION['flash_error'] = 'El partido no existe.';
                header("Location: ../dashboard.php#view-torneo");
                exit;
            }

            if ($partido['estado'] === $estado) {
                $_SESSION['flash_error'] = 'El partido ya está en ese estado.';
                header("Location: ../dashboard.php?torneo_id={$partido['torneo_id']}#view-torneo");
                exit;
            }

            $stmt = $pdo->prepare("UPDATE partidos SET estado = ? WHERE id = ?");
            $stmt->execute([$estado, $partidoId]);

            $etiquetas = [
                'programado' => 'Programado',
                'en_curso'   => 'En curso',
                'finalizado' => 'Finalizado',
                'cancelado'  => 'Cancelado',
            ];

            $_SESSION['flash_success'] = "Partido marcado como {$etiquetas[$estado]}.";

            header("Location: ../dashboard.php?torneo_id={$partido['torneo_id']}#view-torneo");
            exit;

        } catch (PDOException $e) {

            error_log("cambiar_estado_partido: " . $e->getMessage());

            $_SESSION['flash_error'] = 'Error al cambiar el estado del partido.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

    /* =============================
       ELIMINAR PARTIDO
       ============================= */
    case 'eliminar_partido':

        $partidoId = (int) ($_POST['partido_id'] ?? 0);

        if ($partidoId <= 0) {
            $_SESSION['flash_error'] = 'Acción no válida.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

        try {

            $stmt = $pdo->prepare("
                SELECT p.ronda_id, r.torneo_id
                FROM partidos p
                LEFT JOIN torneo_rondas r ON r.id = p.ronda_id
                WHERE p.id = ?
            ");
            $stmt->execute([$partidoId]);
            $partido = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$partido) {
                $_SESSION['flash_error'] = 'El partido no existe.';
                header("Location: ../dashboard.php#view-torneo");
                exit;
            }

            // El partido ya tiene historial o alimenta a otro partido del bracket
            $stmt = $pdo->prepare("
                SELECT
                    (SELECT COUNT(*) FROM partido_eventos    WHERE partido_id = ?) AS eventos,
                    (SELECT COUNT(*) FROM partido_convocados  WHERE partido_id = ?) AS convocados,
                    (SELECT COUNT(*) FROM partido_estadisticas_portero WHERE partido_id = ?) AS estadisticas,
                    (SELECT COUNT(*) FROM partidos
                      WHERE partido_origen_local_id = ?
                         OR partido_origen_visitante_id = ?) AS derivados
            ");
            $stmt->execute([$partidoId, $partidoId, $partidoId, $partidoId, $partidoId]);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC);

            if ((int) $datos['derivados'] > 0) {
                $_SESSION['flash_error'] =
                    'No se puede eliminar: este partido define al ganador de otro partido del bracket. ' .
                    'Cancela el partido para quitarlo sin romper el torneo.';
                header("Location: ../dashboard.php?torneo_id={$partido['torneo_id']}#view-torneo");
                exit;
            }

            if (
                (int) $datos['eventos'] > 0 ||
                (int) $datos['convocados'] > 0 ||
                (int) $datos['estadisticas'] > 0
            ) {
                $_SESSION['flash_error'] =
                    'No se puede eliminar: el partido ya tiene eventos, convocados o estadísticas registradas. ' .
                    'Cancela el partido para conservarlo en el registro.';
                header("Location: ../dashboard.php?torneo_id={$partido['torneo_id']}#view-torneo");
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM partidos WHERE id = ?");
            $stmt->execute([$partidoId]);

            $_SESSION['flash_success'] = 'Partido eliminado.';

            header("Location: ../dashboard.php?torneo_id={$partido['torneo_id']}#view-torneo");
            exit;

        } catch (PDOException $e) {

            error_log("eliminar_partido: " . $e->getMessage());

            $_SESSION['flash_error'] = 'Error al eliminar el partido.';
            header("Location: ../dashboard.php#view-torneo");
            exit;
        }

    default:
        $_SESSION['flash_error'] = 'Acción no válida.';
        header("Location: ../dashboard.php#view-torneo");
        exit;
}