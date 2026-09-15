<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/response.php';

try {

    $pdo = conectarDB();

    $stmt = $pdo->query("
        SELECT 
            1 AS conexion,
            DATABASE() AS base_datos
    ");

    $resultado = $stmt->fetch();


jsonResponse(true, ['conexion' => $resultado['conexion'], 'base_datos' => $resultado['base_datos']]);

} catch (PDOException $e) {

    jsonResponse(false, [], ['ERROR DE CONEXIÓN', $e->getMessage()], 500);

}