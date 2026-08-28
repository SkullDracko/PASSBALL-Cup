<?php
/**
 * PASSBALL Cup - Conexión a Base de Datos
 * PDO MySQL para Hostinger / XAMPP local
 */

$host   = 'localhost';
$dbname = 'passballcup';
$dbuser = 'root';      // Ajustar según Hostinger
$dbpass = '';           // Ajustar según Hostinger

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Error de conexión DB: " . $e->getMessage());
    http_response_code(500);
    echo "Error de conexión a la base de datos.";
    exit;
}
