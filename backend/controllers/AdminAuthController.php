<?php
require_once __DIR__ . '/../core/db.php';

class AdminAuthController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function login(array $params): void {
        // POST /api/admin/login

        $body = jsonBody();

        $usuario = trim((string) ($body['usuario'] ?? ''));
        $contrasena = (string) ($body['contrasena'] ?? '');

        if ($usuario === '' || $contrasena === '') {
            jsonResponse(false, [], [
                'error' => 'Usuario y contraseña son obligatorios'
            ], 400);
        }

        $stmt = $this->pdo->prepare("
            SELECT id, nombre, usuario, contrasena, activo
            FROM administradores
            WHERE usuario = ?
            LIMIT 1
        ");
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($contrasena, $admin['contrasena'])) {
            jsonResponse(false, [], [
                'error' => 'Credenciales inválidas'
            ], 401);
        }

        if (!$admin['activo']) {
            jsonResponse(false, [], [
                'error' => 'El administrador está inactivo'
            ], 403);
        }

        session_start();
        $_SESSION['admin_id'] = (int) $admin['id'];

        unset($admin['contrasena']);

        jsonResponse(true, [
            'administrador' => $admin
        ]);
    }

    public function logout(array $params): void {
        // POST /api/admin/logout
        requireAdminAPI();
        session_destroy();
        jsonResponse(true);
    }

    public function me(array $params): void {
        // GET /api/admin/me

        $adminId = requireAdminAPI();

        $stmt = $this->pdo->prepare("
            SELECT id, nombre, usuario, activo
            FROM administradores
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();

        if (!$admin) {
            jsonResponse(false, [], [
                'error' => 'Administrador no encontrado'
            ], 404);
        }

        jsonResponse(true, [
            'administrador' => $admin
        ]);
    }
}
