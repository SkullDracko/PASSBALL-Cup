<?php
/**
 * =========================================================
 * PASSBALL Cup - Equipos (redirect)
 * =========================================================
 * Esta pagina quedo huérfana tras la refactorización del
 * dashboard como SPA. La sección Equipos ahora vive en la
 * pestaña "Equipos" del dashboard (partials/equipos.php).
 * Redirigimos aquí para mantener compatibilidad con links
 * externos (registrarEquipo, detalle, etc.).
 * =========================================================
 */

require_once __DIR__ . '/controllers/auth.php';

$destino = 'dashboard.php';
$tab = isset($_GET['tab']) ? $_GET['tab'] : '';

if ($tab !== '') {
    $safe = preg_replace('/[^a-z0-9-]/i', '', $tab);
    if ($safe !== '') {
        $destino .= '#' . $safe;
    }
}

header('Location: ' . $destino);
exit;
