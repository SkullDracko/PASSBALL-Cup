<?php
require_once __DIR__ . '/../core/db.php';

/**
 * Comunidad: publicaciones del torneo que crea y administra el admin.
 *
 * Sustituye a admin/controllers/comunidad.php, que resolvia las tres
 * acciones con formularios POST y mensajes en $_SESSION['flash_*'].
 *
 * La tabla posts la crea sql/migracion_admin.sql. No forma parte de
 * bd_propuesta.sql, asi que puede no existir todavia: se comprueba antes
 * de tocar nada y se responde con un error claro en vez de un 500.
 */
class AdminComunidadController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    /**
     * Usuario de 'usuarios' que corresponde al administrador autenticado.
     *
     * posts.usuario_id referenciaba usuarios(id), asi que un admin que publica
     * necesita una fila ahi. El legacy fabricaba la matricula ADM<id>.
     *
     * La fila se marca con rol = 'administrador'. No es cosmetico: en Comunidad
     * solo publica el equipo organizador, porque los participantes se
     * identifican con matricula y no tienen cuenta verificable. Ademas, sin
     * ese valor el admin se colaba donde el resto de la API busca jugadores:
     * EquiposController ofrece a 'rol = usuario' como candidatos a equipo y
     * UsuariosController filtra por 'rol = jugador'.
     *
     * El enum de la columna no admitia 'administrador': con sql_mode laxo
     * MariaDB lo guardaba como '' sin avisar, y con STRICT_TRANS_TABLES, que
     * es lo habitual en hosting, la publicacion reventaba con error 1265.
     * sql/migracion_admin.sql punto 5 amplia el enum.
     */
    private function usuarioAutor(int $adminId): int {
        $stmt = $this->pdo->prepare("
            SELECT u.id, a.nombre
            FROM administradores a
            LEFT JOIN usuarios u ON u.matricula = CONCAT('ADM', LPAD(a.id, 3, '0'))
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();

        if (!$admin) {
            error_log("AdminComunidad: el admin {$adminId} no existe");
            jsonResponse(false, [], ['error' => 'El administrador no existe'], 500);
        }

        if ($admin['id'] !== null) {
            return (int) $admin['id'];
        }

        $insert = $this->pdo->prepare("
            INSERT INTO usuarios (matricula, nombre, rol, jugador_activo, estado)
            VALUES (CONCAT('ADM', LPAD(?, 3, '0')), ?, 'administrador', 0, 'activo')
        ");
        $insert->execute([$adminId, $admin['nombre'] ?? 'Administrador']);

        return (int) $this->pdo->lastInsertId();
    }

    /** Comprueba que posts existe antes de interpretar SQL contra ella. */
    private function exigirTablaPosts(): void {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts'
        ");
        $stmt->execute();

        if ((int) $stmt->fetchColumn() === 0) {
            jsonResponse(false, [], [
                'error' => 'La tabla de publicaciones no existe. Ejecuta sql/migracion_admin.sql.'
            ], 503);
        }
    }

    /**
     * Publicaciones del torneo, con su autor.
     * GET /api/admin/posts
     */
    public function listar(array $params): void {
        requireAdminAPI();
        $this->exigirTablaPosts();

        $stmt = $this->pdo->query("
            SELECT
                p.id,
                p.titulo,
                p.contenido,
                p.imagen_url,
                p.fijado,
                p.likes,
                p.fecha,
                COALESCE(u.nombre, '—') AS autor
            FROM posts p
            LEFT JOIN usuarios u ON u.id = p.usuario_id
            ORDER BY p.fijado DESC, p.fecha DESC, p.id DESC
        ");
        $posts = $stmt->fetchAll();

        jsonResponse(true, [
            'posts' => $posts,
            'total' => count($posts)
        ]);
    }

    /**
     * Crea una publicación.
     * POST /api/admin/posts
     */
    public function crear(array $params): void {
        $adminId = requireAdminAPI();
        $this->exigirTablaPosts();

        $body = jsonBody();

        $titulo   = trim((string) ($body['titulo'] ?? ''));
        $contenido = trim((string) ($body['contenido'] ?? ''));
        $imagenUrl = trim((string) ($body['imagen_url'] ?? ''));
        $fijado   = !empty($body['fijado']) ? 1 : 0;

        $errores = [];
        if ($titulo === '')    { $errores['titulo'] = 'El titulo es obligatorio'; }
        if ($contenido === '') { $errores['contenido'] = 'El contenido es obligatorio'; }

        if (mb_strlen($titulo) > 200) {
            $errores['titulo'] = 'El titulo no puede pasar de 200 caracteres';
        }

        if ($imagenUrl !== '' && !filter_var($imagenUrl, FILTER_VALIDATE_URL)) {
            $errores['imagen_url'] = 'La URL de la imagen no es valida';
        }

        if ($errores) {
            jsonResponse(false, [], $errores, 422);
        }

        $usuarioId = $this->usuarioAutor($adminId);

        $stmt = $this->pdo->prepare("
            INSERT INTO posts (usuario_id, titulo, contenido, imagen_url, fijado)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $usuarioId,
            $titulo,
            $contenido,
            $imagenUrl !== '' ? $imagenUrl : null,
            $fijado,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        $nuevo = $this->pdo->query("
            SELECT p.id, p.titulo, p.contenido, p.imagen_url, p.fijado, p.likes, p.fecha,
                   COALESCE(u.nombre, '—') AS autor
            FROM posts p
            LEFT JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.id = " . $id
        )->fetch();

        jsonResponse(true, ['post' => $nuevo], [], 201);
    }

    /**
     * Fija o desfija una publicación.
     * PATCH /api/admin/posts/{id}/fijado
     */
    public function toggleFijado(array $params): void {
        requireAdminAPI();
        $this->exigirTablaPosts();

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(false, [], ['error' => 'Publicacion no valida'], 400);
        }

        $stmt = $this->pdo->prepare("
            UPDATE posts SET fijado = NOT fijado WHERE id = ?
        ");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(false, [], ['error' => 'La publicacion no existe'], 404);
        }

        $post = $this->pdo->query("SELECT id, fijado FROM posts WHERE id = $id")->fetch();
        jsonResponse(true, ['post' => $post]);
    }

    /**
     * Elimina una publicación.
     * DELETE /api/admin/posts/{id}
     */
    public function eliminar(array $params): void {
        requireAdminAPI();
        $this->exigirTablaPosts();

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(false, [], ['error' => 'Publicacion no valida'], 400);
        }

        $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(false, [], ['error' => 'La publicacion no existe'], 404);
        }

        jsonResponse(true, ['id' => $id]);
    }
}
