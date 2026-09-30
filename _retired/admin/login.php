<?php
/**
 * PASSBALL Cup - Login del administrador
 * Verifica credenciales contra la tabla administradores
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$usuario    = trim($_POST['usuario'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

if ($usuario === '' || $contrasena === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ingresa usuario y contraseña']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM administradores WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $admin = $stmt->fetch();

    // No revelar si el usuario existe o no
    if (!$admin || !$admin['activo'] || !password_verify($contrasena, $admin['contrasena'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
        exit;
    }

    $_SESSION['admin'] = [
        'id'      => (int) $admin['id'],
        'nombre'  => $admin['nombre'],
        'usuario' => $admin['usuario'],
    ];
    // La API exige $_SESSION['admin_id']; sin esto entrar por el panel no
    // habilita ningun endpoint de backend (ver etapes_conexion_admin_api.md 1.9).
    $_SESSION['admin_id'] = (int) $admin['id'];

    echo json_encode([
        'success' => true,
        'message' => 'Bienvenido ' . $admin['nombre'],
        'admin'   => $_SESSION['admin'],
        'redirect' => 'dashboard.php',
    ]);

} catch (PDOException $e) {
    error_log("Login admin PASSBALL: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}