<?php

class TestController
{
    public function index(array $params = []): void
    {
        $pdo = conectarDB();

        $stmt = $pdo->query("SELECT 1 AS conexion");

        $resultado = $stmt->fetch();

        jsonResponse(true, [
            'mensaje' => 'API funcionando correctamente',
            'base_datos' => $resultado
        ]);
    }

    // GET /api/test/jugador?matricula=1234567
    // Solo disponible en local: llama directo a datosJugadorSiEstaInscrito()
    // para probar la integración con AFIHub sin pasar por login/sesión.
    public function jugador(array $params = []): void
    {
        if ($_ENV['APP_ENV'] === 'production') {
            jsonResponse(false, [], ['error' => 'No disponible'], 404);
        }

        require_once __DIR__ . '/../services/jugadores_service.php';

        $matricula = trim($_GET['matricula'] ?? '');

        if (!preg_match('/^\d{7}$/', $matricula)) {
            jsonResponse(false, [], ['error' => 'Matrícula inválida'], 400);
        }

        $datos = datosJugadorSiEstaInscrito($matricula);

        jsonResponse(true, [
            'inscrito' => $datos !== null,
            'datos'    => $datos,
        ]);
    }
}