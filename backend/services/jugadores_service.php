<?php
// core/jugadores_service.php
// Consulta el endpoint de AFIHub que verifica si una matrícula está
// inscrita en la actividad de fútbol (afi_id fijo).

const AFI_ID_FUTBOL = 30; // afi_id real de "PASSBALL Torneo Relámpago Fútbol Rápido" (330 era el fecha_id, no el afi_id)

/**
 * Devuelve los datos del estudiante si está inscrito en la AFI de fútbol,
 * o null si no lo está o si el servicio falla (fallback seguro: no
 * bloquea el login, simplemente no se le asigna rol de jugador).
 *
 * @return array{nombre:string, apellidop:string, apellidom:string, semestre:int}|null
 */
function datosJugadorSiEstaInscrito(string $matricula): ?array
{
    $baseUrl = $_ENV['AFI_BASE_URL'];

    $url = $baseUrl . '?' . http_build_query([
        'afi_id'    => AFI_ID_FUTBOL,
        'matricula' => $matricula,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_FAILONERROR    => false,
        // El endpoint valida el header Origin contra su whitelist y, además,
        // requiere X-Api-Key: un secreto compartido, ya que Origin es
        // trivialmente falsificable desde una llamada servidor-a-servidor.
        CURLOPT_HTTPHEADER     => [
            'Origin: ' . $_ENV['AFI_ORIGIN'],
            'X-Api-Key: ' . $_ENV['AFI_API_KEY'],
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '') {
        error_log("datosJugadorSiEstaInscrito: fallo de conexión para {$matricula}: {$curlError}");
        return null;
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("datosJugadorSiEstaInscrito: JSON inválido para {$matricula} (HTTP {$httpCode})");
        return null;
    }

    // HTTP 403 con success:false = no está inscrito (caso normal, no es error)
    // Cualquier otro código o success:false inesperado = lo tratamos como "no jugador"
    if ($httpCode !== 200 || empty($data['success'])) {
        return null;
    }

    $est = $data['estudiante'] ?? null;

    if (!$est) {
        return null;
    }

    return [
        'nombre'    => $est['nombre'] ?? '',
        'apellidop' => $est['apellidop'] ?? '',
        'apellidom' => $est['apellidom'] ?? '',
        'semestre'  => (int) ($est['semestre'] ?? 0),
    ];
}