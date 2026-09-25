<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../security/authorization.php';

class EquiposController {
    // Capitán + 11 integrantes, el mismo tope que aplicaba
    // controllers/registrarEquipo.php.
    private const MAX_MIEMBROS = 12;

    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void
    {
        // GET /api/equipos?estado=activo

      /*   requireAuthAPI(); */

        $filtros = array_merge($_GET, $params);
        $where = [];
        $values = [];

        if (!empty($filtros['estado'])) {
            requerirEnum($filtros['estado'], ['activo', 'inactivo'], 'estado');
            $where[] = 'estado = ?';
            $values[] = $filtros['estado'];
        }

        $sql = '
            SELECT id, nombre, logo, capitan_id, estado, fecha_creacion
            FROM equipos
        ';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        jsonResponse(true, [
            'equipos' => $stmt->fetchAll()
        ]);
    }

    public function crear(array $params): void
    {
        // POST /api/equipos
        // Acepta JSON (logo ya referenciado) o multipart/form-data (logo
        // subido + integrantes[]), que es lo que envía el modal de
        // dashboard.php ahora que este endpoint reemplaza a
        // controllers/registrarEquipo.php.

        $capitanId = $this->capitanActual();
        $body = requestBody();

        $nombre = trim((string) ($body['nombre'] ?? ''));

        if (mb_strlen($nombre, 'UTF-8') < 3) {
            jsonResponse(false, [], [
                'error' => 'El nombre debe tener al menos 3 caracteres'
            ], 400);
        }

        if (mb_strlen($nombre, 'UTF-8') > 100) {
            jsonResponse(false, [], [
                'error' => 'El nombre no puede exceder 100 caracteres'
            ], 400);
        }

        $integrantes = array_values(array_diff(
            $this->normalizarIntegrantes($body['integrantes'] ?? []),
            [$capitanId]
        ));

        if (count($integrantes) > self::MAX_MIEMBROS - 1) {
            jsonResponse(false, [], [
                'error' => 'El equipo admite máximo ' . self::MAX_MIEMBROS
                    . ' integrantes (tú + ' . (self::MAX_MIEMBROS - 1) . '). Reduce la lista.'
            ], 400);
        }

        $stmt = $this->pdo->prepare('SELECT id FROM equipos WHERE nombre = ? LIMIT 1');
        $stmt->execute([$nombre]);

        if ($stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'Ya existe un equipo con ese nombre. Elige otro.'
            ], 409);
        }

        $stmt = $this->pdo->prepare('SELECT id FROM equipo_miembros WHERE jugador_id = ? AND estado = "activo" LIMIT 1');
        $stmt->execute([$capitanId]);

        if ($stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'Ya perteneces a un equipo. Sal del equipo actual para crear uno nuevo.'
            ], 409);
        }

        $logo = null;
        $logoUrl = null;

        if (esMultipart()) {
            $logo = $this->guardarLogo($_FILES['logo'] ?? null);

            if ($logo['error'] !== null) {
                jsonResponse(false, [], ['error' => $logo['error']], 400);
            }

            $logoUrl = $logo['url'];
        } elseif (array_key_exists('logo', $body) && $body['logo'] !== null) {
            if (!is_string($body['logo']) || mb_strlen($body['logo'], 'UTF-8') > 255) {
                jsonResponse(false, [], [
                    'error' => 'El logo debe ser una cadena de hasta 255 caracteres'
                ], 400);
            }

            $logoUrl = $body['logo'];
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                INSERT INTO equipos (nombre, logo, capitan_id, estado)
                VALUES (?, ?, ?, 'activo')
            ");
            $stmt->execute([$nombre, $logoUrl, $capitanId]);
            $equipoId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("
                INSERT INTO equipo_miembros (equipo_id, jugador_id, estado)
                VALUES (?, ?, 'activo')
            ");
            $stmt->execute([$equipoId, $capitanId]);

            if ($integrantes !== []) {
                $this->insertarIntegrantes($equipoId, $integrantes);
            }

            $this->pdo->commit();
        } catch (RuntimeException $e) {
            $this->pdo->rollBack();
            $this->descartarLogo($logo);

            errorNegocio($e->getMessage());
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->descartarLogo($logo);

            error_log('PASSBALL - EquiposController::crear: ' . $e->getMessage());

            jsonResponse(false, [], [
                'error' => 'No se pudo crear el equipo'
            ], 409);
        }

        jsonResponse(true, [
            'mensaje' => 'Equipo "' . $nombre . '" registrado con '
                . (1 + count($integrantes)) . ' integrante(s). ¡Bienvenido, líder!',
            'id' => $equipoId,
            'integrantes' => 1 + count($integrantes)
        ], [], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Apoyo de crear()
    |--------------------------------------------------------------------------
    */

    // Antes se usaba requireJugador(), que exige usuarios.rol = 'jugador'.
    // El flujo legacy (controllers/auth.php → registrarEquipo.php) sólo pedía
    // sesión activa, y equipo_miembros.jugador_id referencia usuarios.id, así
    // que 11 de los 71 usuarios registrados (rol 'usuario') se quedarían sin
    // poder crear su equipo. Se replica el criterio legacy para que la
    // migración no rompa ese caso.
    private function capitanActual(): int
    {
        $usuarioId = requireAuthAPI();

        $stmt = $this->pdo->prepare('SELECT estado FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();

        if (!$usuario || $usuario['estado'] !== 'activo') {
            jsonResponse(false, [], ['error' => 'Usuario no autorizado'], 403);
        }

        return $usuarioId;
    }

    private function normalizarIntegrantes($raw): array
    {
        $ids = array_map('intval', (array) $raw);

        return array_values(array_unique(array_filter(
            $ids,
            fn($id) => $id > 0
        )));
    }

    // Mismo criterio que controllers/buscarUsuarios.php: sólo usuarios
    // 'usuario' activos. Si más adelante se quiere invitar a un rol 'jugador',
    // hay que quitar ese filtro en ambos lados a la vez.
    private function insertarIntegrantes(int $equipoId, array $ids): void
    {
        $ph = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->pdo->prepare("
            SELECT id FROM usuarios
            WHERE id IN ($ph)
              AND rol = 'usuario'
              AND estado = 'activo'
        ");
        $stmt->execute($ids);

        if (count($stmt->fetchAll(PDO::FETCH_COLUMN)) !== count($ids)) {
            throw new RuntimeException('Uno de los jugadores seleccionados ya no es válido.');
        }

        $stmt = $this->pdo->prepare("
            SELECT jugador_id FROM equipo_miembros
            WHERE jugador_id IN ($ph)
              AND estado = 'activo'
        ");
        $stmt->execute($ids);

        if ($stmt->fetch()) {
            throw new RuntimeException('Uno de los jugadores seleccionados ya pertenece a otro equipo.');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO equipo_miembros (equipo_id, jugador_id, estado)
            VALUES (?, ?, 'activo')
        ");

        foreach ($ids as $id) {
            $stmt->execute([$equipoId, $id]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    */

    // Devuelve ['error' => string|null, 'url' => ?string, 'ruta' => ?string].
    // 'ruta' es la del disco y se usa para borrarla si la transacción falla,
    // igual que hacía registrarEquipo.php.
    private function guardarLogo(?array $file): array
    {
        $fallo = fn(string $msg) => [
            'error' => $msg,
            'url' => null,
            'ruta' => null
        ];

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $fallo('El logo del equipo es obligatorio. Sube una imagen (JPG, PNG, WEBP o GIF).');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $fallo('Hubo un error al subir el logo del equipo.');
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            return $fallo('El logo no puede superar los 5 MB.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
            return $fallo('El logo debe ser una imagen válida (JPG, PNG, WEBP o GIF).');
        }

        $ext = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif'
        ][$mime];

        $dir = UPLOADS_PATH . 'equipos/';

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return $fallo('No se pudo guardar el logo. Intenta de nuevo.');
        }

        $archivo = uniqid('eq_', true) . '.' . $ext;
        $ruta = $dir . $archivo;

        if (!move_uploaded_file($file['tmp_name'], $ruta)) {
            return $fallo('No se pudo guardar el logo. Intenta de nuevo.');
        }

        return [
            'error' => null,
            'url' => UPLOADS_URL . 'equipos/' . $archivo,
            'ruta' => $ruta
        ];
    }

    private function descartarLogo(?array $logo): void
    {
        if ($logo && !empty($logo['ruta']) && is_file($logo['ruta'])) {
            unlink($logo['ruta']);
        }
    }

    public function detalle(array $params): void
    {
        // GET /api/equipos/{id}

      /*   requireAuthAPI(); */
        $id = $this->obtenerId($params);

        $stmt = $this->pdo->prepare('
            SELECT id, nombre, logo, capitan_id, estado, fecha_creacion
            FROM equipos
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$id]);
        $equipo = $stmt->fetch();

        if (!$equipo) {
            $this->noEncontrado();
        }

        jsonResponse(true, [
            'equipo' => $equipo
        ]);
    }

    public function actualizar(array $params): void
    {
        // PATCH /api/equipos/{id}

        $id = $this->obtenerId($params);
        $this->requireCapitanOAdmin($id);

        $body = jsonBody();

        if (!$body) {
            jsonResponse(false, [], [
                'error' => 'Debes enviar al menos un campo para actualizar'
            ], 400);
        }

        $campos = [];
        $values = [];

        if (array_key_exists('nombre', $body)) {
            $nombre = trim((string) $body['nombre']);

            if ($nombre === '' || mb_strlen($nombre) > 100) {
                jsonResponse(false, [], [
                    'error' => 'El nombre es obligatorio y no puede superar 100 caracteres'
                ], 400);
            }

            $stmt = $this->pdo->prepare(
                'SELECT id FROM equipos WHERE nombre = ? AND id <> ? LIMIT 1'
            );
            $stmt->execute([$nombre, $id]);

            if ($stmt->fetch()) {
                jsonResponse(false, [], [
                    'error' => 'Ya existe un equipo con ese nombre'
                ], 409);
            }

            $campos[] = 'nombre = ?';
            $values[] = $nombre;
        }

        if (array_key_exists('logo', $body)) {
            $logo = $body['logo'];

            if ($logo !== null && (!is_string($logo) || mb_strlen($logo) > 255)) {
                jsonResponse(false, [], [
                    'error' => 'El logo debe ser una cadena de hasta 255 caracteres'
                ], 400);
            }

            $campos[] = 'logo = ?';
            $values[] = $logo;
        }

        if (!$campos) {
            jsonResponse(false, [], [
                'error' => 'No hay campos válidos para actualizar'
            ], 400);
        }

        $values[] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE equipos SET ' . implode(', ', $campos) . ' WHERE id = ?'
        );
        $stmt->execute($values);

        $this->verificarExistencia($id);

        jsonResponse(true, [
            'mensaje' => 'Equipo actualizado correctamente'
        ]);
    }

    public function cambiarEstado(array $params): void
    {
        // PATCH /api/equipos/{id}/estado

        requireAdminAPI();
        $id = $this->obtenerId($params);
        $body = jsonBody();

        $estado = trim((string) ($body['estado'] ?? ''));
        requerirEnum($estado, ['activo', 'inactivo'], 'estado');

        $stmt = $this->pdo->prepare(
            'UPDATE equipos SET estado = ? WHERE id = ?'
        );
        $stmt->execute([$estado, $id]);

        $this->verificarExistencia($id);

        jsonResponse(true, [
            'mensaje' => 'Estado del equipo actualizado correctamente',
            'estado' => $estado
        ]);
    }

    public function eliminar(array $params): void
    {
        // DELETE /api/equipos/{id}

        requireAdminAPI();
        $id = $this->obtenerId($params);

        try {
            $stmt = $this->pdo->prepare('DELETE FROM equipos WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            jsonResponse(false, [], [
                'error' => 'No se puede eliminar el equipo porque tiene registros asociados'
            ], 409);
        }

        if ($stmt->rowCount() === 0) {
            $this->noEncontrado();
        }

        jsonResponse(true, [
            'mensaje' => 'Equipo eliminado correctamente'
        ]);
    }

    // Permite que el capitán del equipo o un administrador realicen la acción
    private function requireCapitanOAdmin(int $equipoId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!empty($_SESSION['admin_id'])) {
            requireAdminAPI();
            return;
        }

        requireCapitan($equipoId);
    }

    private function obtenerId(array $params): int
    {
        $id = $params['id'] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], [
                'error' => 'ID de equipo inválido'
            ], 400);
        }

        return (int) $id;
    }

    private function verificarExistencia(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM equipos WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            $this->noEncontrado();
        }
    }

    private function noEncontrado(): void
    {
        jsonResponse(false, [], [
            'error' => 'Equipo no encontrado'
        ], 404);
    }
}
