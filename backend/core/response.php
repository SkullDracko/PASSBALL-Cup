<?php
// Formato de respuesta uniforme para todos los controllers.

function jsonResponse(bool $exito, $data = [], array $errores = [], int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'exito'   => $exito,
        'data'    => $data,
        'errores' => $errores,
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

function jsonBody(): array {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        jsonResponse(false, [], ['error' => 'JSON mal formado'], 400);
    }

    return $input;
}