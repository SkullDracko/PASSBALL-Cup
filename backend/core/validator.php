<?php
// Helpers genéricos de validación, reutilizables entre controllers.
// Reglas de negocio específicas de cada recurso siguen viviendo en su controller.

function requerirCampos(array $data, array $campos): void {
    $faltantes = array_filter($campos, fn($c) => !array_key_exists($c, $data) || $data[$c] === '');

    if (!empty($faltantes)) {
        jsonResponse(false, [], ['error' => 'Campos requeridos: ' . implode(', ', $faltantes)], 422);
    }
}

function requerirEnum(string $valor, array $permitidos, string $campo): void {
    if (!in_array($valor, $permitidos, true)) {
        jsonResponse(false, [], ["error" => "Valor inválido para {$campo}: {$valor}"], 422);
    }
}
