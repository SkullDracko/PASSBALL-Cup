<?php
/**
 * PASSBALL Cup - Admin: Votaciones (POST handler)
 * Acciones: crear_categoria, cambiar_estado, eliminar_categoria,
 *           agregar_candidato, excluir_candidato
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php#view-votaciones");
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {

    /* =============================
       CREAR CATEGORÍA
       ============================= */
    case 'crear_categoria':

        $torneoId       = (int) ($_POST['torneo_id'] ?? 0);
        $clave          = trim($_POST['clave'] ?? '');
        $nombre         = trim($_POST['nombre'] ?? '');
        $tipo           = $_POST['tipo'] ?? 'jugador';
        $modoCandidatos = $_POST['modo_candidatos'] ?? 'automatico';
        $estado         = isset($_POST['abierta']) ? 'abierta' : 'cerrada';
        $orden          = (int) ($_POST['orden'] ?? 0);

        $clave = strtolower(preg_replace('/[^A-Za-z0-9_-]+/', '_', $clave));

        if ($torneoId <= 0 || $clave === '' || $nombre === '') {
            $_SESSION['flash_error'] = 'Torreo, clave y nombre son obligatorios.';
            header("Location: ../dashboard.php#view-votaciones");
            exit;
        }

        if (!in_array($tipo, ['jugador', 'equipo'], true)) $tipo = 'jugador';
        if (!in_array($modoCandidatos, ['automatico', 'manual'], true)) $modoCandidatos = 'automatico';

        try {
            $stmt = $pdo->prepare("
                INSERT INTO torneo_categorias_voto
                    (torneo_id, clave, nombre, tipo, modo_candidatos, estado, orden)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$torneoId, $clave, $nombre, $tipo, $modoCandidatos, $estado, $orden]);
            $_SESSION['flash_success'] = 'Categoría creada.';
        } catch (PDOException $e) {
            error_log("crear_categoria: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al crear la categoría (¿clave duplicada?).';
        }

        header("Location: ../dashboard.php#view-votaciones");
        exit;

    /* =============================
       CAMBIAR ESTADO
       ============================= */
    case 'cambiar_estado':

        $catId = (int) ($_POST['categoria_id'] ?? 0);

        if ($catId <= 0) {
            $_SESSION['flash_error'] = 'ID de categoría inválido.';
            header("Location: ../dashboard.php#view-votaciones");
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE torneo_categorias_voto SET estado = IF(estado = 'abierta', 'cerrada', 'abierta') WHERE id = ?");
            $stmt->execute([$catId]);
            $_SESSION['flash_success'] = 'Estado de la categoría actualizado.';
        } catch (PDOException $e) {
            error_log("cambiar_estado: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al actualizar el estado.';
        }

        header("Location: ../dashboard.php#view-votaciones");
        exit;

    /* =============================
       ELIMINAR CATEGORÍA
       ============================= */
    case 'eliminar_categoria':

        $catId = (int) ($_POST['categoria_id'] ?? 0);

        if ($catId <= 0) {
            $_SESSION['flash_error'] = 'ID de categoría inválido.';
            header("Location: ../dashboard.php#view-votaciones");
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM torneo_votos WHERE categoria_id = ?");
            $stmt->execute([$catId]);
            $stmt = $pdo->prepare("DELETE FROM torneo_categoria_candidatos WHERE categoria_id = ?");
            $stmt->execute([$catId]);
            $stmt = $pdo->prepare("DELETE FROM torneo_categorias_voto WHERE id = ?");
            $stmt->execute([$catId]);
            $_SESSION['flash_success'] = 'Categoría eliminada.';
        } catch (PDOException $e) {
            error_log("eliminar_categoria: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al eliminar la categoría.';
        }

        header("Location: ../dashboard.php#view-votaciones");
        exit;

    /* =============================
       AGREGAR CANDIDATO (incluir)
       ============================= */
    case 'agregar_candidato':

        $catId        = (int) ($_POST['categoria_id'] ?? 0);
        $tipoCandidato = $_POST['tipo_candidato'] ?? 'jugador';
        $jugadorId    = (int) ($_POST['jugador_id'] ?? 0);
        $equipoId     = (int) ($_POST['equipo_id'] ?? 0);

        if ($catId <= 0) {
            $_SESSION['flash_error'] = 'ID de categoría inválido.';
            header("Location: ../dashboard.php#view-votaciones");
            exit;
        }

        if ($tipoCandidato === 'equipo' && $equipoId <= 0) {
            $_SESSION['flash_error'] = 'Selecciona un equipo.';
            header("Location: ../dashboard.php#view-votaciones");
            exit;
        }

        if ($tipoCandidato === 'jugador' && $jugadorId <= 0) {
            $_SESSION['flash_error'] = 'Selecciona un jugador.';
            header("Location: ../dashboard.php#view-votaciones");
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO torneo_categoria_candidatos (categoria_id, jugador_id, equipo_id, ajuste)
                VALUES (?, ?, ?, 'incluir')
            ");
            $stmt->execute([$catId, $tipoCandidato === 'jugador' ? $jugadorId : null, $tipoCandidato === 'equipo' ? $equipoId : null]);
            $_SESSION['flash_success'] = 'Candidato incluido.';
        } catch (PDOException $e) {
            error_log("agregar_candidato: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al incluir candidato.';
        }

        header("Location: ../dashboard.php#view-votaciones");
        exit;

    /* =============================
       EXCLUIR CANDIDATO (ajuste)
       ============================= */
    case 'excluir_candidato':

        $catId        = (int) ($_POST['categoria_id'] ?? 0);
        $tipoCandidato = $_POST['tipo_candidato'] ?? 'jugador';
        $jugadorId    = (int) ($_POST['jugador_id'] ?? 0);
        $equipoId     = (int) ($_POST['equipo_id'] ?? 0);

        if ($catId <= 0) {
            $_SESSION['flash_error'] = 'ID de categoría inválido.';
            header("Location: ../dashboard.php#view-votaciones");
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO torneo_categoria_candidatos (categoria_id, jugador_id, equipo_id, ajuste)
                VALUES (?, ?, ?, 'excluir')
            ");
            $stmt->execute([$catId, $tipoCandidato === 'jugador' ? $jugadorId : null, $tipoCandidato === 'equipo' ? $equipoId : null]);
            $_SESSION['flash_success'] = 'Candidato excluido.';
        } catch (PDOException $e) {
            error_log("excluir_candidato: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al excluir candidato.';
        }

        header("Location: ../dashboard.php#view-votaciones");
        exit;

    /* =============================
       ELIMINAR AJUSTE DE CANDIDATO
       ============================= */
    case 'eliminar_candidato':

        $candId = (int) ($_POST['candidato_id'] ?? 0);

        if ($candId <= 0) {
            $_SESSION['flash_error'] = 'ID de candidato inválido.';
            header("Location: ../dashboard.php#view-votaciones");
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM torneo_categoria_candidatos WHERE id = ?");
            $stmt->execute([$candId]);
            $_SESSION['flash_success'] = 'Ajuste de candidato eliminado.';
        } catch (PDOException $e) {
            error_log("eliminar_candidato: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al eliminar el ajuste.';
        }

        header("Location: ../dashboard.php#view-votaciones");
        exit;

    default:
        $_SESSION['flash_error'] = 'Acción no válida.';
        header("Location: ../dashboard.php#view-votaciones");
        exit;
}