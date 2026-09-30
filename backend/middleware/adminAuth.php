<?php
// Sesión de administrador (tabla administradores, independiente de usuarios).
//
// La sesión vive en una sola clave: $_SESSION['admin_id'], que escribe
// AdminAuthController al hacer login por la API. admin/partials ya no
// consulta la base y dashboard.php se apoya en admin/controllers/auth.php,
// que lee ese mismo id.
//
// Antes coexistian dos claves, $_SESSION['admin'] (array {id,nombre,usuario})
// y $_SESSION['admin_id'] (int), y esta funcion las sincronizaba a mano
// porque el panel legacy solo escribia la primera y la API solo leia la
// segunda. Ese era el shim que hacia posible que el logout se quedara a
// medias: unset($_SESSION['admin']) dejaba viva la clave que la API mira.

require_once __DIR__ . '/../core/db.php';

/**
 * Id del administrador autenticado, o error 401 si no hay sesion.
 * Unica via de entrada a los endpoints admin de la API.
 */
function requireAdminAPI(): int {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    $adminId = (int) ($_SESSION['admin_id'] ?? 0);

    if ($adminId === 0) {
        jsonResponse(false, [], ['error' => 'No autenticado como administrador'], 401);
    }

    $_SESSION['admin_id'] = $adminId;

    return $adminId;
}
