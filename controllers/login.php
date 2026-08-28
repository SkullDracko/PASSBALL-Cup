<?php
/**
 * PASSBALL Cup - Login vía AFI Hub
 * Recibe matrícula, verifica con AFI Hub, crea sesión local
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$matricula = trim($_POST['matricula'] ?? '');

// Validar matrícula: 7 dígitos
if (!preg_match('/^\d{7}$/', $matricula)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La matrícula debe tener exactamente 7 dígitos']);
    exit;
}

// Detectar si estamos en localhost (modo desarrollo)
$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', 'localhost:8080']);

if ($isLocalhost) {
    // === MODO DESARROLLO: login directo sin AFI Hub ===
    $estudiante = [
        'id'              => 1,
        'matricula'       => $matricula,
        'nombre'          => 'Usuario',
        'apellidop'       => 'Test',
        'apellidom'       => 'Local',
        'nombre_completo' => 'Test Local Usuario',
        'semestre'        => 5,
    ];
} else {
    // === PRODUCCIÓN: verificar con AFI Hub ===
    $url = AFI_URL_VERIFICAR . "?afi_id=" . AFI_ID . "&matricula=" . urlencode($matricula);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'No se pudo conectar con AFI Hub']);
        exit;
    }

    $data = json_decode($response, true);

    if (!$data || !isset($data['success']) || !$data['success']) {
        $msg = $data['message'] ?? 'No estás inscrito en esta actividad';
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    $estudiante = $data['estudiante'];
}

// Buscar o crear usuario en nuestra BD
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios_passball WHERE matricula = ?");
    $stmt->execute([$matricula]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        $rol = ($matricula === '0000000') ? 'admin' : 'miembro';

        $insert = $pdo->prepare("
            INSERT INTO usuarios_passball (afi_usuario_id, matricula, nombre, apellidop, apellidom, semestre, rol)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->execute([
            $estudiante['id'],
            $estudiante['matricula'],
            $estudiante['nombre'],
            $estudiante['apellidop'],
            $estudiante['apellidom'],
            $estudiante['semestre'],
            $rol,
        ]);

        $stmt = $pdo->prepare("SELECT * FROM usuarios_passball WHERE matricula = ?");
        $stmt->execute([$matricula]);
        $usuario = $stmt->fetch();
    }

    // Guardar en sesión
    $_SESSION['usuario'] = [
        'id'        => $usuario['id'],
        'matricula' => $usuario['matricula'],
        'nombre'    => $usuario['nombre'],
        'apellidop' => $usuario['apellidop'],
        'apellidom' => $usuario['apellidom'],
        'semestre'  => $usuario['semestre'],
        'rol'       => $usuario['rol'],
        'avatar'    => $usuario['avatar'],
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Bienvenido ' . $estudiante['nombre_completo'],
        'usuario' => $_SESSION['usuario'],
    ]);

} catch (PDOException $e) {
    error_log("Login PASSBALL: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
