<?php
/**
 * Tests de integración del módulo de votaciones (PASSBALL-Cup).
 *
 * Levanta un fixture (admin, torneo, equipos, membresías, inscripciones), ejecuta
 * cada endpoint como admin y como usuario por HTTP contra el router real, y limpia
 * todo el fixture al final. No toca datos preexistentes.
 *
 * Uso:  php backend/tests/test_votaciones.php
 * Requiere: Apache/XAMPP corriendo en http://localhost y BD `passballcup` cargada.
 */

require __DIR__ . '/../config/app.php';
require __DIR__ . '/../core/db.php';

$BASE = 'http://localhost/PASSBALL-Cup/backend';
$PASS = [];

// ---------------------------------------------------------------------------
// Cliente HTTP mínimo con sesiones por cookie jar
// ---------------------------------------------------------------------------
function reqAuth(string $method, string $path, ?array $body, string $cookieJar, string $file): array
{
    $ch = curl_init($path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $cookieJar,
        CURLOPT_COOKIEFILE     => $cookieJar,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw      = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return [$httpCode, ['curl_error' => $error], $file];
    }

    return [$httpCode, json_decode($raw, true) ?? ['raw' => $raw], $file];
}

function api(string $method, string $path, ?array $body = null, string $session = 'admin'): array
{
    global $COOKIES, $BASE;
    return reqAuth($method, $BASE . $path, $body, $COOKIES[$session]['jar'], '');
}

function assertOk(string $nombre, int $esperado, int $obtenido, $condicion = true): void
{
    global $PASS;
    $ok = ($obtenido === $esperado) && $condicion;
    $PASS[] = [$nombre, $ok ? 'PASS' : 'FAIL', explode(' ', $esperado)[0] . "->" . $obtenido];
}

function login(string $session, string $path, array $body): int
{
    global $COOKIES;
    [$code, $json] = reqAuth('POST', $GLOBALS['BASE'] . $path, $body, $COOKIES[$session]['jar'], '');
    return $code;
}

// ---------------------------------------------------------------------------
// Fixture: se crea con PDO directo y se borra al final (try/finally)
// ---------------------------------------------------------------------------
$pdo = conectarDB();
$fixture = [];

try {
    // IDs para el fixture (no asumimos IDs secuenciales en tablas con datos).
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    $pdo->prepare("INSERT INTO administradores (nombre, usuario, contrasena, activo) VALUES ('Test Votaciones', 'testadmin', ?, 1)")
        ->execute([password_hash('test-password', PASSWORD_DEFAULT)]);
    $fixture['admin_id'] = (int) $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO torneos (nombre, tipo, estado) VALUES ('TORNEO TEST VOTACIONES', 'eliminacion_directa', 'programado')")
        ->execute();
    $fixture['torneo_id'] = (int) $pdo->lastInsertId();

    // Torneo "ajeno" para probar que las categorías no se escapan de su torneo.
    $pdo->prepare("INSERT INTO torneos (nombre, tipo, estado) VALUES ('TORNEO AJENO TEST', 'eliminacion_directa', 'programado')")
        ->execute();
    $fixture['torneo_ajeno_id'] = (int) $pdo->lastInsertId();

    // Equipos: capitán usuario 2 (sin membresía previa). Jugadores de membresía
    // usan usuarios 3..8, que no tienen membresía activa preexistente (el usuario
    // 1 sí la tiene: queda fuera del fixture para no chocar con uq_jugador_membresia_activa).
    $fixture['equipos'] = [];
    foreach ([['Equipo A TEST', [3, 4, 5]], ['Equipo B TEST', [6, 7, 8]]] as [$nombre, $jugadores]) {
        $pdo->prepare('INSERT INTO equipos (nombre, capitan_id, estado) VALUES (?, 2, "activo")')
            ->execute([$nombre]);
        $equipoId = (int) $pdo->lastInsertId();
        $fixture['equipos'][] = $equipoId;

        foreach ($jugadores as $jugadorId) {
            $pdo->prepare('INSERT INTO equipo_miembros (equipo_id, jugador_id, estado) VALUES (?, ?, "activo")')
                ->execute([$equipoId, $jugadorId]);
        }
        $pdo->prepare('INSERT INTO torneo_equipos (torneo_id, equipo_id, estado, fecha_aprobacion, aprobado_por) VALUES (?, ?, "aprobado", CURRENT_TIMESTAMP, ?)')
            ->execute([$fixture['torneo_id'], $equipoId, $fixture['admin_id']]);
    }

    // Sesiones de cliente (archivos temporales de cookie separados).
    $COOKIES = [];
    foreach (['admin', 'userA', 'userB'] as $ses) {
        $COOKIES[$ses] = ['jar' => tempnam(sys_get_temp_dir(), 'pbjar')];
    }

    // -----------------------------------------------------------------------
    // 0. Sesiones: login de admin y de dos usuarios.
    // -----------------------------------------------------------------------
    $BASE = 'http://localhost/PASSBALL-Cup/backend';

    assertOk('login admin', 200, login('admin', '/api/admin/login', [
        'usuario'    => 'testadmin',
        'contrasena' => 'test-password',
    ]));
    assertOk('login user A', 200, login('userA', '/api/auth/login', ['matricula' => '1908082']));
    assertOk('login user B', 200, login('userB', '/api/auth/login', ['matricula' => '1908083']));

    // -----------------------------------------------------------------------
    // 1. Categorías de votación
    // -----------------------------------------------------------------------
    $path = "/api/torneos/{$fixture['torneo_id']}/categorias-voto";

    [$code, $json] = api('GET', $path, null, 'userA');
    assertOk('GET categorias lista vacía (usuario autenticado)', 200, $code, !empty($json['data']['categorias'] ?? null) === false);

    [$code] = reqAuth('GET', $BASE . $path, null, tempnam(sys_get_temp_dir(), 'pbjar'), '');
    assertOk('GET categorias requiere sesión', 401, $code);

    // Creación (admin). Nace'cerrada'.
    [$code, $json] = api('POST', $path, [
        'clave' => 'goleador-torneo', 'nombre' => 'Goleador del torneo', 'tipo' => 'jugador', 'modo_candidatos' => 'automatico'
    ]);
    assertOk('POST crear categoria jugador auto', 201, $code);
    $catAutoJugador = (int) ($json['data']['id'] ?? 0);

    [$code, $json] = api('POST', $path, [
        'clave' => 'equipo-campeon', 'nombre' => 'Equipo campeón', 'tipo' => 'equipo', 'modo_candidatos' => 'automatico'
    ]);
    assertOk('POST crear categoria equipo auto', 201, $code);
    $catAutoEquipo = (int) ($json['data']['id'] ?? 0);

    // Clave duplicada -> 409.
    [$code] = api('POST', $path, [
        'clave' => 'goleador-torneo', 'nombre' => 'Duplicada', 'tipo' => 'jugador'
    ]);
    assertOk('POST clave duplicada -> 409', 409, $code);

    // Tipo inválido -> 422.
    [$code] = api('POST', $path, ['clave' => 'x', 'nombre' => 'X', 'tipo' => 'portero']);
    assertOk('POST tipo inválido -> 422', 422, $code);

    // Crear ejercicio por un usuario (no admin) -> 401.
    [$code] = api('POST', $path, ['clave' => 'y', 'nombre' => 'Y', 'tipo' => 'jugador'], 'userA');
    assertOk('POST crear (usuario) -> 401', 401, $code);

    // Cambiar estado: abierta. Y dejar una segunda categoría cerrada (no obrta).
    [$code] = api("PATCH", "{$path}/{$catAutoJugador}/estado", ['estado' => 'abierta']);
    assertOk('PATCH estado abierta', 200, $code);

    // Actualizar nombre.
    [$code] = api("PATCH", "{$path}/{$catAutoJugador}", ['nombre' => 'Goleador del torneo (editado)']);
    assertOk('PATCH actualizar nombre', 200, $code);

    // Cambiar estado con JSON válido pero sin campo 'estado' -> 422 (requerirCampos).
    [$code] = api("PATCH", "{$path}/{$catAutoJugador}/estado", []);
    assertOk('PATCH estado sin campo estado -> 422', 422, $code);

    // -----------------------------------------------------------------------
    // 2. Pool (cálculo automático)
    // -----------------------------------------------------------------------
    [$code, $json] = api("GET", "{$path}/{$catAutoJugador}/pool", null, 'userA');
    assertOk('GET pool jugadores auto (6 miembros)', 200, $code, count($json['data']['candidatos'] ?? []) === 6);

    [$code, $json] = api("GET", "{$path}/{$catAutoEquipo}/pool", null, 'userA');
    assertOk('GET pool equipos auto (2 aprobados)', 200, $code, count($json['data']['candidatos'] ?? []) === 2);

    // Pool de una categoría de otro torneo -> 404.
    [$code] = api("GET", "/api/torneos/{$fixture['torneo_ajeno_id']}/categorias-voto/{$catAutoJugador}/pool", null, 'userA');
    assertOk('GET pool categoria de otro torneo -> 404', 404, $code);

    // -----------------------------------------------------------------------
    // 3. Candidatos (ajustes) — pantalla admin
    // -----------------------------------------------------------------------
    $candPath = "{$path}/{$catAutoJugador}/candidatos";

    [$code, $json] = api('GET', $candPath);
    assertOk('GET candidatos (admin) lista vacía', 200, $code);

    [$code] = api('GET', $candPath, null, 'userA');
    assertOk('GET candidatos (usuario) -> 401', 401, $code);

    // Excluir al jugador 3 del pool automático (ajuste válido en modo auto).
    [$code] = api('POST', $candPath, ['jugador_id' => 3, 'ajuste' => 'excluir']);
    assertOk('POST candidato excluir (admin)', 201, $code);

    [$code, $json] = api("GET", "{$path}/{$catAutoJugador}/pool", null, 'userA');
    assertOk('pool sin el excluido (5 jugadores)', 200, $code, count($json['data']['candidatos'] ?? []) === 5);

    // Upsert: el mismo jugador pasa a 'incluir' (se re-graba el ajuste).
    [$code] = api('POST', $candPath, ['jugador_id' => 3, 'ajuste' => 'incluir']);
    assertOk('POST candidato upsert mismo jugador', 201, $code);

    [$code, $json] = api('GET', $candPath);
    $ajustes = $json['data']['ajustes'] ?? [];
    $bien = is_array($ajustes) && count($ajustes) === 1 && ($ajustes[0]['ajuste'] ?? '') === 'incluir';
    assertOk('GET candidatos refleja upsert (ajuste=incluir)', 200, $code, $bien);

    // Tipo mal dirigido: enviar equipo a categoría de jugador -> 422.
    [$code] = api('POST', $candPath, ['equipo_id' => $fixture['equipos'][0], 'ajuste' => 'excluir']);
    assertOk('POST candidato equipo en categoria jugador -> 422', 422, $code);

    // Jugador inexistente -> 404.
    [$code] = api('POST', $candPath, ['jugador_id' => 99999, 'ajuste' => 'excluir']);
    assertOk('POST candidato jugador inexistente -> 404', 404, $code);

    // Pool vuelve a 6 tras cambiar a 'incluir'.
    [$code, $json] = api("GET", "{$path}/{$catAutoJugador}/pool", null, 'userA');
    assertOk('pool restaurado a 6 tras upsert', 200, $code, count($json['data']['candidatos'] ?? []) === 6);

    // Eliminar ajuste -> 200, y repetir -> 404.
    $ajusteId = (int) ($ajustes[0]['id'] ?? 0);
    [$code] = api('DELETE', "{$candPath}/{$ajusteId}");
    assertOk('DELETE candidato -> 200', 200, $code);
    [$code] = api('DELETE', "{$candPath}/{$ajusteId}");
    assertOk('DELETE candidato inexistente -> 404', 404, $code);

    // Eliminar la categoría ahora que no tiene datos -> 200 (borrar con datos luego lo probamos).
    // Para poder seguir votando, NO borramos aquí.

    // -----------------------------------------------------------------------
    // 4. Votos
    // -----------------------------------------------------------------------
    $votosPath = "/api/torneos/{$fixture['torneo_id']}/votos";

    [$code] = reqAuth('POST', $BASE . $votosPath, ['categoria_id' => $catAutoJugador, 'jugador_id' => 1], tempnam(sys_get_temp_dir(), 'pbjar'), '');
    assertOk('POST voto sin sesión -> 401', 401, $code);

    // Candidato fuera del pool automático (el usuario 9 no es miembro de los
    // equipos aprobados de este torneo).
    [$code] = api('POST', $votosPath, ['categoria_id' => $catAutoJugador, 'jugador_id' => 9], 'userA');
    assertOk('POST voto candidato fuera de pool -> 422', 422, $code);

    // Categoría cerrada -> 422.
    [$code] = api('POST', $votosPath, ['categoria_id' => $catAutoEquipo, 'equipo_id' => $fixture['equipos'][0]], 'userA');
    assertOk('POST voto a categoria cerrada -> 422', 422, $code);

    // Ambos ids -> 422 (categoría de jugador).
    [$code] = api('POST', $votosPath, ['categoria_id' => $catAutoJugador, 'jugador_id' => 3, 'equipo_id' => $fixture['equipos'][0]], 'userA');
    assertOk('POST voto con ambos ids -> 422', 422, $code);

    // Categoría pertenece a otro torneo -> 404.
    [$code] = api('POST', "/api/torneos/{$fixture['torneo_ajeno_id']}/votos", ['categoria_id' => $catAutoJugador, 'jugador_id' => 3], 'userA');
    assertOk('POST voto categoria de otro torneo -> 404', 404, $code);

    // Abrir la categoría de equipos y votar correctamente.
    api("PATCH", "{$path}/{$catAutoEquipo}/estado", ['estado' => 'abierta']);
    [$code] = api('POST', $votosPath, ['categoria_id' => $catAutoEquipo, 'equipo_id' => $fixture['equipos'][0]], 'userA');
    assertOk('POST voto equipo ok', 200, $code);

    // Voto válido (userA -> jugador 3) y (userB -> jugador 3 también).
    [$code] = api('POST', $votosPath, ['categoria_id' => $catAutoJugador, 'jugador_id' => 3], 'userA');
    assertOk('POST voto usuario A ok', 200, $code);
    [$code] = api('POST', $votosPath, ['categoria_id' => $catAutoJugador, 'jugador_id' => 3], 'userB');
    assertOk('POST voto usuario B ok', 200, $code);

    // Mis votos (userA): uno por categoría en este torneo (no contamos categoría cerrada).
    [$code, $json] = api('GET', $votosPath . '/mios', null, 'userA');
    assertOk('GET mis votos userA (2 votos)', 200, $code, count($json['data']['mis_votos'] ?? []) === 2);

    // Cambio de voto (upsert): userA pasa a jugador 4 en la misma categoría.
    [$code] = api('POST', $votosPath, ['categoria_id' => $catAutoJugador, 'jugador_id' => 4], 'userA');
    assertOk('POST cambio de voto (upsert) ok', 200, $code);

    [$code, $json] = api('GET', $votosPath . '/mios', null, 'userA');
    $misVotos = $json['data']['mis_votos'] ?? [];
    $soloUno = 0;
    foreach ($misVotos as $v) {
        if ((int) $v['categoria_id'] === $catAutoJugador) $soloUno++;
    }
    assertOk('upsert: un solo voto por categoria', 200, $code, $soloUno === 1);

    // Resultados: jugador 3 = 1 voto (userB), jugador 4 = 1 voto (userA).
    [$code, $json] = api('GET', "$votosPath/resultados?categoria_id={$catAutoJugador}", null, 'userA');
    $res = array_column($json['data']['resultados'] ?? [], 'votos', 'jugador_id');
    $bien = isset($res['3']) && isset($res['4']) && (int) $res['3'] === 1 && (int) $res['4'] === 1;
    assertOk('GET resultados conteo correcto (1-1)', 200, $code, $bien);

    // Resultados sin categoria_id -> 400.
    [$code] = api('GET', $votosPath . '/resultados', null, 'userA');
    assertOk('GET resultados sin categoria_id -> 400', 400, $code);

    // Retirar el voto de userA en goleador.
    [$code] = api('DELETE', "{$votosPath}/{$catAutoJugador}", null, 'userA');
    assertOk('DELETE retirar voto userA', 200, $code);
    [$code] = api('DELETE', "{$votosPath}/{$catAutoJugador}", null, 'userA');
    assertOk('DELETE retirar voto inexistente -> 404', 404, $code);

    // -----------------------------------------------------------------------
    // 5. Categoría manual + pool manual + candidatos incluir, y borrados.
    // -----------------------------------------------------------------------
    [$code, $json] = api('POST', $path, [
        'clave' => 'mvp-final-test', 'nombre' => 'MVP de la final', 'tipo' => 'jugador', 'modo_candidatos' => 'manual'
    ]);
    assertOk('POST categoria manual (cerrada)', 201, $code);
    $catManual = (int) ($json['data']['id'] ?? 0);

    $candManual = "{$path}/{$catManual}/candidatos";
    api('POST', $candManual, ['jugador_id' => 1, 'ajuste' => 'incluir']);
    api('POST', $candManual, ['jugador_id' => 2, 'ajuste' => 'incluir']);

    [$code, $json] = api("GET", "{$path}/{$catManual}/pool", null, 'userA');
    assertOk('GET pool manual = 2 candidatos incluir', 200, $code, count($json['data']['candidatos'] ?? []) === 2);

    // La categoría manual nace cerrada: votar -> 422.
    [$code] = api('POST', $votosPath, ['categoria_id' => $catManual, 'jugador_id' => 1], 'userA');
    assertOk('POST voto categoria manual cerrada -> 422', 422, $code);

    // Eliminación de categoría con datos asociados -> 409.
    [$code] = api('DELETE', "{$path}/{$catAutoJugador}");
    assertOk('DELETE categoria con votos -> 409', 409, $code);

    // Limpieza de la categoría manual y borrado -> 200.
    api('POST', $votosPath, ['categoria_id' => $catManual, 'jugador_id' => 1], 'userA'); // cerrada -> se rechaza; no crea voto
    [$code] = api('DELETE', "{$candManual}/" . (int) api('GET', $candManual)[1]['data']['ajustes'][0]['id']);
    assertOk('DELETE candidato manual -> 200', 200, $code);
    [$code] = api('DELETE', "{$candManual}/" . (int) api('GET', $candManual)[1]['data']['ajustes'][0]['id']);
    assertOk('DELETE segundo candidato manual -> 200', 200, $code);
    [$code] = api('DELETE', "{$path}/{$catManual}");
    assertOk('DELETE categoria manual sin datos -> 200', 200, $code);
} finally {
    // -----------------------------------------------------------------------
    // Limpieza del fixture (siempre, aunque un test haya fallado con exit)
    // -----------------------------------------------------------------------
    try {
        $ids = [];
        if (!empty($fixture['torneo_id'])) {
            $t = $fixture['torneo_id'];
            $ids[] = $t;
            $pdo->prepare('DELETE FROM torneo_votos WHERE torneo_id = ?')->execute([$t]);
            $pdo->prepare('DELETE FROM torneo_categoria_candidatos WHERE categoria_id IN (SELECT id FROM torneo_categorias_voto WHERE torneo_id = ?)')->execute([$t]);
            $pdo->prepare('DELETE FROM torneo_categorias_voto WHERE torneo_id = ?')->execute([$t]);
            $pdo->prepare('DELETE FROM torneo_equipos WHERE torneo_id = ?')->execute([$t]);
        }
        if (!empty($fixture['torneo_ajeno_id'])) {
            $ids[] = $fixture['torneo_ajeno_id'];
            $pdo->prepare('DELETE FROM torneo_equipos WHERE torneo_id = ?')->execute([$fixture['torneo_ajeno_id']]);
        }
        if (!empty($fixture['equipos'])) {
            $in = implode(',', array_map('intval', $fixture['equipos']));
            $pdo->exec("DELETE FROM equipo_miembros WHERE equipo_id IN ($in)");
            $pdo->exec("DELETE FROM equipos WHERE id IN ($in)");
        }
        foreach ($ids as $id) {
            $pdo->prepare('DELETE FROM torneos WHERE id = ?')->execute([$id]);
        }
        if (!empty($fixture['admin_id'])) {
            $pdo->prepare('DELETE FROM administradores WHERE id = ?')->execute([$fixture['admin_id']]);
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        foreach ($COOKIES ?? [] as $jar) {
            @unlink($jar['jar']);
        }
    } catch (Throwable $e) {
        echo "Limpieza falló: {$e->getMessage()}\n";
    }
}

// ---------------------------------------------------------------------------
// Resumen
// ---------------------------------------------------------------------------
$fails = 0;
foreach ($GLOBALS['PASS'] as [$nombre, $estado, $detalle]) {
    printf("%-52s %-4s %s\n", $nombre, $estado, $detalle);
    if ($estado === 'FAIL') $fails++;
}
echo "\nTotal: " . count($GLOBALS['PASS']) . " | FAIL: {$fails}\n";
exit($fails > 0 ? 1 : 0);