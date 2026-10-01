<?php
/**
 * PASSBALL Cup - Middleware de autenticación (admin)
 * Incluir en cada página del panel admin que requiera login
 * Esquema: tabla administradores
 *
 * La sesión vive en una sola clave: $_SESSION['admin_id'], que es la que
 * escribe AdminAuthController al hacer login por la API. Antes se usaba
 * $_SESSION['admin'] (un array con id, nombre y usuario) y setAdminSession()
 * mantenía las dos claves sincronizadas a mano.
 *
 * El nombre y el usuario se leen de la base, no de la sesión, para que un
 * cambio de nombre en la tabla se vea al instante en el panel en vez de
 * quedar congelado hasta el siguiente login.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$stmt = $pdo->prepare('
    SELECT id, nombre, usuario
    FROM administradores
    WHERE id = ? AND activo = 1
    LIMIT 1
');
$stmt->execute([(int) $_SESSION['admin_id']]);
$admin = $stmt->fetch();

// El admin fue borrado o desactivado despues de autenticarse: la sesion ya
// no vale y no debe seguir dando acceso al panel.
if (!$admin) {
    unset($_SESSION['admin_id']);
    session_destroy();
    header('Location: login.php?error=' . rawurlencode('La cuenta ya no esta activa.'));
    exit;
}

$admin['id'] = (int) $admin['id'];
