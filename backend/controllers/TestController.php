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
}