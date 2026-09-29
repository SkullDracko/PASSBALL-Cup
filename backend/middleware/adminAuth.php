<?php
// Sesión de administrador (tabla administradores, independiente de usuarios).
//
// El panel legacy y la API usan dos claves de sesion distintas:
//   $_SESSION['admin']     -> array {id, nombre, usuario}   (admin/controllers/login.php)
//   $_SESSION['admin_id']  -> int                            (AdminAuthController)
//
// Ninguno reconocia al otro, lo que hacia imposible conectar el panel a la API.
// Ahora ambas se llenan juntas y cualquiera de las dos autentica en los dos lados.

require_once __DIR__ . '/../core/db.php';

/**
 * Deja las dos claves de sesion coherentes a partir de un id de administrador.
 * Idempotente: si el array ya existe con el mismo id, no vuelve a consultar.
 */
function setAdminSession(int $adminId): void
{
    $_SESSION['admin_id'] = $adminId;

    if (isset($_SESSION['admin']) && (int) ($_SESSION['admin']['id'] ?? 0) === $adminId) {
        return;
    }

    $stmt = conectarDB()->prepare(
        'SELECT id, nombre, usuario FROM administradores WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();

    // El admin fue borrado entre la autenticacion y ahora: no dejamos sesion valida.
    if (!$admin) {
        unset($_SESSION['admin'], $_SESSION['admin_id']);
        return;
    }

    $_SESSION['admin'] = [
        'id'      => (int) $admin['id'],
        'nombre'  => $admin['nombre'],
        'usuario' => $admin['usuario'],
    ];
}

function requireAdminAPI(): int {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    $adminId = (int) ($_SESSION['admin_id'] ?? 0);

    // El panel legacy solo dejaba $_SESSION['admin']; la API solo leia admin_id.
    // Aceptamos cualquiera de las dos y normalizamos a las dos.
    if ($adminId === 0) {
        $adminId = (int) ($_SESSION['admin']['id'] ?? 0);
    }

    if ($adminId === 0) {
        jsonResponse(false, [], ['error' => 'No autenticado como administrador'], 401);
    }

    setAdminSession($adminId);

    return $adminId;
}
