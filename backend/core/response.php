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

function esMultipart(): bool {
    $tipo = $_SERVER['CONTENT_TYPE'] ?? '';
    return stripos($tipo, 'multipart/form-data') === 0;
}

// Lee el cuerpo de la petición sea JSON o formulário. Con multipart/form-data
// y x-www-form-urlencoded PHP ya llenó $_POST y php://input viene vacío, así
// que hay que mirar el Content-Type en vez de hacer json_decode() siempre.
function requestBody(): array {
    $tipo = $_SERVER['CONTENT_TYPE'] ?? '';

    if (esMultipart() || stripos($tipo, 'application/x-www-form-urlencoded') === 0) {
        return $_POST;
    }

    return jsonBody();
}

// Traduce un RuntimeException de negocio al 422 con el motivo ya redactado,
// para que el frontend pueda mostrarlo sin adivinar.
function errorNegocio(string $mensaje): void {
    jsonResponse(false, [], ['error' => $mensaje], 422);
}