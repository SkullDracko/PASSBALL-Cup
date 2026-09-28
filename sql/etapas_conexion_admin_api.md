# PASSBALL Cup — Estado de la API y plan de desconexión del stack legacy

> **Documento técnico para revisión.**
> Alcance: panel de administración (`admin/`) y API REST (`backend/`).
> El sitio público (`index.php`, `controllers/`, `equipos/`) **queda fuera de alcance**.
>
> Última actualización incorpora la verificación contra la base de datos real
> (`passballcup`, 14 tablas) y una auditoría de las 83 rutas de la API.

---

## 1. Diagnóstico corregido

### 1.1 La API no está pendiente de construir: ya está terminada

La premisa inicial de este documento era que había que "implementar la API". La auditoría
demuestra lo contrario:

| Métrica | Resultado |
|---|---|
| Rutas registradas en `backend/routes/api.php` | **83** |
| Rutas que resuelven a un método implementado | **83 / 83 (100%)** |
| Métodos stub / TODO / `throw new` vacío | **0** |
| Controladores referenciados en rutas que no existen | **0** |
| Hallazgos de inyección SQL | **0** — todas las queries usan prepared statements |
| Servicios muertos (`FinalizarPartido`, `ResolverPoolCandidatos`, `jugadores_service`) | **0** — los 3 vivos |
| Tablas de BD con controller | **14 / 16** |

La API tiene validación real (`core/validator.php` con 19 call sites, `filter_var` en cada
id de path, whitelists de enums, validación de fechas por round-trip), autorización por
ownership (`backend/security/authorization.php`) y transacciones
(`services/FinalizarPartido.php:16` usa `SELECT ... FOR UPDATE`, rollback en `:49-52`).

**El trabajo no es construir la API, es reconciliarla, corregirla y volverla explotable.**

### 1.2 `sql/schema.sql` está obsoleto — el archivo que miente

`sql/schema.sql:3` se etiqueta como DEFINITIVO, pero **contradice la base de datos real**:

| | `sql/schema.sql` (dice "DEFINITIVO") | `sql/bd_propuesta.sql` | **BD real** |
|---|---|---|---|
| `usuarios.rol` | `ENUM('usuario','administrador')` | `ENUM('usuario','jugador')` | **`ENUM('usuario','jugador')`** |
| `apellidop` / `apellidom` / `semestre` | ausentes | presentes | **presentes** |
| `nombre` | vía `ALTER` en `:536` | en el `CREATE` | **presente** |

El código de la API está escrito contra `bd_propuesta.sql`, no contra `schema.sql`. Por eso
`AuthController.php:117` (que inserta `apellidop, appellidom, semestre`) **funciona** contra
la BD real, aunque parezca estar escribiendo columnas inexistidas.

**Consecuencia práctica:** ninguna corrección de esquema es necesaria. Se documenta y ya.

### 1.3 `sql/migracion_admin.sql` nunca se aplicó

Faltan 3 objetos que los scripts declaran crear:

| Objeto | ¿Existe en BD? | Quién lo usa |
|---|---|---|
| Tabla `posts` | ❌ no | `admin/partials/comunidad.php:14`, `admin/controllers/comunidad.php:63,97,122` |
| Tabla `post_reacciones` | ❌ no | `controllers/reaccionar.php:37-67`, `partials/comunidad.php:19-21,65,106` |
| Columna `torneo_equipos.motivo_rechazo` | ❌ no | `admin/controllers/postulaciones.php:58` (escribe), `admin/partials/postulaciones.php:203` (lee) |

Dos funcionalidades están **rotas hoy, silenciosamente**:

1. **Vista Comunidad** — consulta una tabla que no existe.
2. **Rechazar postulación con motivo** — el panel escribe una columna que no existe.

> Esto resuelve la pregunta de qué hacer con Comunidad: **no hay nada que mover ni que
> preservar.** La funcionalidad no está "pendiente de migrar", está caída.

### 1.4 Ningún administrador puede iniciar sesión

```
id | usuario           | activo | hash        | len
 1 | admin_passballcup |     1 | $2y$10$...  |  40
 2 | admin1            |     1 | «plano»     |   7
```

Verificado con PHP:

```php
password_get_info('$2y$10$examplehashexamplehashexamplehash')
// ['algo' => NULL, 'algoName' => 'unknown']

password_verify($plaintext, $plaintext)   // bool(false) — no es un hash
```

- **`admin_passballcup`** — placeholder de 40 caracteres. bcrypt exige 60. No es un hash.
- **`admin1`** — contraseña de 7 caracteres guardada **en texto plano** en la columna
  `contrasena` (valor enmascarado deliberadamente; está en la BD, no debe propagarse).

**El panel de administración está completamente inaccesible.** Nadie pudo entrar, y por eso
nunca se notó que los endpoints están desconectados.

### 1.5 Ningún usuario tiene el rol que la API exige

```
+------+------+
| rol  | n    |
+------+------+
| usuario | 51 |   ← los 51
+------+------+
```

`backend/security/authorization.php:40` exige `$usuario['rol'] !== 'jugador'`. El ENUM sí lo
permite, pero **ninguna fila lo usa**. Eso deja 6 endpoints en 403 permanente para todos:

| Endpoint | Controller |
|---|---|
| `POST /api/equipos` | `EquiposController.php:48` |
| `PATCH /api/equipos/{id}` | `EquiposController.php:149` |
| `POST /api/equipos/{equipoId}/miembros` | `EquipoMiembrosController.php:49` |
| `PATCH /api/equipos/{equipoId}/miembros/{jugadorId}/salida` | `EquipoMiembrosController.php:111` |
| `DELETE /api/equipos/{equipoId}/miembros/{jugadorId}` | `EquipoMiembrosController.php:137` |
| `POST /api/torneos/{torneoId}/equipos` | `TorneoEquiposController.php:58` |

Es el flujo completo de *crear equipo → gestionar plantilla → postular a torneo*.
**Es un problema de datos, no de esquema.** El ENUM ya admite `'jugador'`; simplemente nadie
lo tiene. Decisión pendiente: poblar ese rol, o relajar `requireJugador()` para que se
baste con `estado='activo' AND jugador_activo=1` (que es lo que ya significa la columna).

### 1.6 `git mv` a `_retired/` no desconecta nada

El plan original era mover `admin/` a `_retired/`. **Eso no corta el acceso**: `_retired/`
sigue dentro del docroot de XAMPP, y el panel seguiría respondiendo en
`http://localhost/PASSBALL-Cup/_retired/admin/dashboard.php`.

El `git mv` ordena el código pero no desactiva nada. **Debe acompañarse de una regla de
denegar**, o el corte es cosmético. Ver Fase 1.

---

## 2. Inventario verificado

### 2.1 Base de datos `passballcup` (14 tablas)

| Tabla | Filas | ¿Cubierta por la API? |
|---|---|---|
| `usuarios` | 51 | ✅ 5 rutas |
| `administradores` | 2 | ✅ 6 rutas (⚠️ 5 sin auth) |
| `equipos` | 10 | ✅ 6 rutas |
| `equipo_miembros` | 48 | ✅ 6 rutas |
| `torneos` | 1 | ✅ 5 rutas |
| `torneo_equipos` | 10 | ✅ 5 rutas |
| `torneo_rondas` | 3 | ✅ 4 rutas |
| `partidos` | 7 | ✅ 7 rutas |
| `partido_convocados` | **84** | ✅ 4 rutas |
| `partido_eventos` | 43 | ✅ 4 rutas |
| `partido_estadisticas_portero` | **14** | ✅ 4 rutas |
| `torneo_categorias_voto` | 0 | ✅ 6 rutas |
| `torneo_categoria_candidatos` | 0 | ✅ 3 rutas |
| `torneo_votos` | 0 | ✅ 4 rutas |
| `posts` | — inexistente | ❌ 0 rutas |
| `post_reacciones` | — inexistente | ❌ 0 rutas |

> **84 filas en `partido_convocados` y 14 en `partido_estadisticas_portero` ya existen en
> la BD** y no hay ninguna UI que las muestre. La Fase 4 las hace visibles sin trabajo de datos.

### 2.2 Cobertura de autenticación

| Categoría | Rutas | Estado |
|---|---|---|
| Protegidas con `requireAdminAPI()` | 37 | ✅ correcto |
| Sesión de jugador (`requireAuthAPI()`) | 11 | ⚠️ 2 de estas son superficies de admin |
| `requireJugador()` / `requireCapitan()` | 6 | ❌ 403 para todos (§1.5) |
| Lecturas públicas intencionadas | 21 | ✅ aceptable |
| Logins | 2 | ✅ |
| **Sin auth, no deberían tenerlo** | **6** | 🚨 **crítico** |

### 2.3 Cobertura de pruebas

| Métrica | Valor |
|---|---|
| Controllers con pruebas | **3 de 19** (`CategoriasVoto`, `CandidatosVoto`, `Votos`) |
| Rutas cubiertas | **13 de 83 (~16%)** |
| `composer.json` / `phpunit.xml` / CI | **ninguno** |

`backend/tests/test_votaciones.php` es un buen harness de integración (50 asserts, cookie
jars, `finally` con cleanup, verifica fronteras de auth y aislamiento). **Pero cubre un
solo módulo.** Los 8 defectos de §3.2 viven en el 84% sin probar — por eso sobrevivieron.

---

## 3. Defectos confirmados de la API

### 3.1 Seguridad — bloquean la puesta en marcha

| # | Defecto | Ubicación | Impacto |
|---|---|---|---|
| **S1** | `requireAdminAPI()` **comentado** en 5 métodos de administrators | `AdministradoresController.php:64,118,143,229,268` | 🚨 **Toma de control total de la cuenta.** Un anónimo puede crear un admin con la contraseña que elija, **resetear la contraseña de cualquier admin** (`PATCH` acepta `contrasena` en `:193-204`), desactivarlos y borrarlos. El propio código lo admite: `// TODO: Activar cuando exista un flujo autorizado` (`:63`) |
| **S2** | Tests alcanzables por HTTP sin autenticación | `backend/tests/test_votaciones.php`, `prueba_bd.php` | 🚨 `backend/.htaccess:2` (`RewriteCond %{REQUEST_FILENAME} !-f`) deja los archivos servibles. `test_votaciones.php` **crea un admin `testadmin` en la BD** por HTTP anónimo. `prueba_bd.php` devuelve `DATABASE()` |
| **S3** | `APP_DEBUG=1` por defecto, sin `.env` ni `.env.example` | `backend/config/app.php:23` | Cualquier 500 imprime traza PDO completa **con el DSN y el nombre de la base** a un llamador anónimo. Credenciales de BD: `root` con contraseña vacía |
| **S4** | Sin rate limiting ni lockout en login de admin | `AdminAuthController.php:8` | Permite fuerza bruta |
| **S5** | `session_destroy()` en el logout de la API | `AuthController.php:50`, `AdminAuthController.php:56` | Destruye la sesión **completa**, incluida la del legacy. Un logout de jugador mata la sesión del panel admin |
| **S6** | Sin CSRF, sin `SameSite`/`Secure`/`HttpOnly` explícitos | `middleware/auth.php:5` | Todo endpoint mutante se autentica solo con cookie de sesión |
| **S7** | `GET /api/usuarios` y `/api/usuarios/{id}` piden sesión de **jugador**, no de admin | `UsuariosController.php:19,81` | Cualquier jugador autenticado enumera todos los usuarios (matrícula, rol, estado) |
| **S8** | Auth de jugador: **sin contraseña**, con autoaprovisionamiento | `AuthController.php:20,23,31` | La matrícula de 7 dígitos es la credencial completa. Una matrícula desconocida **crea la cuenta** (`:31`). Combinado con S7 es enumeración total |
| **S9** | Credencial de admin en claro, en archivo trackeado y dentro del docroot | `crear_admin.php:2-3` | Usuario y contraseña accesibles por HTTP en `crear_admin.php` y presentes en el historial de git |
| **S10** | Hash inválido y contraseña en claro sembrados en producción | `inserts.sql:98`, tabla `administradores` id=2 | §1.4 — panel inaccesible |

### 3.2 Funcionalidad

| # | Defecto | Ubicación | Impacto |
|---|---|---|---|
| **F1** | `GET /api/partidos` no se puede filtrar | `PartidosController.php:25` | `$this->obtenerId(['id' => $filtros[$campo]], $campo)` pasa el array con clave `'id'` pero `obtenerId` (`:198`) busca `$campo` (`'ronda_id'`/`'equipo_id'`) → siempre `null` → **400 en todo request filtrado**. Sin filtro no hay forma de acotar a un torneo, porque **no existe filtro `torneo_id`**. El frontend no puede listar partidos por torneo |
| **F2** | `rechazar` nunca escribe `motivo_rechazo` | `TorneoEquiposController.php:124` | El `UPDATE` pone `estado="rechazado"` y limpia `aprobado_por`, pero **nunca el motivo** — aunque el panel lo lee (`admin/partials/postulaciones.php:203`). Rechazos vía API se ven en blanco |
| **F3** | Router no devuelve 405 | `backend/core/router.php:25` | `if ($rutaMetodo !== strtoupper($metodo)) continue;` descarta el método y cae en el 404 genérico. Sin cabecera `Allow` |
| **F4** | Sin CORS, sin `OPTIONS` | `backend/` (0 coincidencias de `Access-Control`) | ⚠️ **No bloquea el panel admin**: admin y API están en el mismo origen (`localhost:80`). Sí bloquearía un frontend servido en otro origen |
| **F5** | Sin soporte de subida de archivos | `backend/` (0 coincidencias de `$_FILES`) | `logo` y `avatar` se manejan como **string**. Un frontend **no puede subir** logo ni avatar por la API |
| **F6** | Sin paginación en ningún listado | `EquiposController.php:34`, `UsuariosController.php:67`, `EstadisticasController` | Aceptable a escala de torneo; problemático cuando crezca |
| **F7** | Base path hardcodeado | `backend/index.php:29` | `substr($uri, strlen('/PASSBALL-Cup/backend'))`. Desplegar en otra carpeta o en raíz → **las 83 rutas dan 404** |
| **F8** | `session_start()` sin guarda | `AuthController.php:38`, `AdminAuthController.php:43` | `E_NOTICE` "session already started" si se alcanza dos veces. El resto del código sí usa la guarda `session_status()` |
| **F9** | `requireRol()` es código muerto | `security/authorization.php:5` | 0 call sites en todo el repo |
| **F10** | Sin versionado de API | rutas `/api/...` | Sin `/v1` no hay espacio para cambios incompatibles |

---

## 4. Defectos de la colección Postman

`backend/passballcup.postman_collection.json` — 68 peticiones vs 83 rutas.

| # | Defecto | Impacto |
|---|---|---|
| **P1** | **Sin bloque `auth` ni variables de entorno** | Todas las rutas bajo `adminAuth` darían 401. La colección **no es ejecutable** |
| **P2** | `{{base_url}}` referenciado en 12 `description`, nunca declarado | Esas cURL no corren |
| **P3** | `partidos-convocados → listar` apunta a `/api/partidos/1/eventos` | Duplica `partidos-eventos → listar`; la ruta real nunca se prueba |
| **P4** | `estadisticas → tarjetas` tiene `"raw": ""` | Petición inválida |
| **P5** | Cuerpos con valores sin completar (`"jugador_id": ,`, `"posicion": `) | 400 "JSON mal formado" |
| **P6** | `auth → admin → "New Request"` = `POST /api/admin/logout` | Sin nombre |
| **P7** | Cero `example` de respuesta | Sin contrato documentado |
| **P8** | **Faltan 15 rutas** | 13 de votaciones (categorías, candidatos, votos) + `GET /api/test` + `DELETE /api/partidos/{id}` |

> P1 y P2 son bloqueantes. Hoy la colección sirve como catálogo, no como herramienta.

---

## 5. Fases de ejecución

> Regla: **cada fase deja el sistema en un estado coherente.** Nada de todo-o-nada.
> Las fases 0 y 1 no dependen de ninguna otra y pueden ir de inmediato.

### Fase 0 — Cerrar vulnerabilidades
*No toca la BD, no toca el legacy, no rompe nada. Sin dependencias.*

| # | Acción | Archivo |
|---|---|---|
| 0.1 | Descomentar `requireAdminAPI()` en 5 métodos — **S1** | `backend/controllers/AdministradoresController.php:64,118,143,229,268` |
| 0.2 | Mutar los tests fuera del docroot, o guardar con `php_sapi_name() === 'cli'` — **S2** | `backend/tests/` |
| 0.3 | `APP_DEBUG` por defecto a `0`; crear `.env.example` — **S3** | `backend/config/app.php:22-26` |
| 0.4 | Mover `crear_admin.php` fuera del docroot y del repo — **S9** | raíz |
| 0.5 | Rotar la contraseña expuesta en S9 y sanejar el historial de git | repo |

**Criterio de salida**
- [ ] `curl -X PATCH .../api/administradores/1 -d '{"contrasena":"x"}'` sin sesión → 401
- [ ] `GET /backend/tests/test_votaciones.php` → 404
- [ ] Un 500 de la API no revela el DSN

> **Nota S1:** activar el guard rompe `POST /api/administradores` para quien no tenga sesión.
> Si se necesita una vía legítima para crear el primer admin, debe ser por CLI o con un
> token bootstrap — no dejándolo abierto.

### Fase 1 — Desconectar el panel de administración
*Alcance: solo `admin/`. El sitio público no se toca.*

| # | Acción |
|---|---|
| 1.1 | `git mv admin _retired/admin` |
| 1.2 | **Crear `_retired/.htaccess` con `Require all denied`** — sin esto no se desconecta (§1.6) |
| 1.3 | Crear `.htaccess` en la raíz: `RedirectMatch 410 ^/PASSBALL-Cup/admin/` para que las URLs viejas devuelvan *Gone* en vez de 404 crudo |
| 1.4 | `git mv crear_admin.php _retired/crear_admin.php` (sigue 0.4) |

**Criterio de salida**
- [ ] `GET /PASSBALL-Cup/admin/dashboard.php` → 410 o 404
- [ ] `GET /PASSBALL-Cup/_retired/admin/dashboard.php` → **denegado**
- [ ] `admin/` sigue versionado y legible como referencia

> Las rutas relativas de `_retired/admin/` (`../../config/database.php`) siguen resolviendo,
> así que el código se conserva funcional como referencia.

### Fase 2 — Hacer la API explotable

| # | Acción |
|---|---|
| 2.1 | Crear `.env` con credenciales reales (hoy no existe; la API usa los defaults `root`/vacío) |
| 2.2 | **Arreglar los 2 hashes de `administradores`** (§1.4) — sin esto no hay forma de validar nada de la Fase 4 |
| 2.3 | `config/database.php` deja de hardcodear credenciales y lee de `.env`, igual que la API |
| 2.4 | Corregir F1 — filtro de `GET /api/partidos`; agregar soporte de `torneo_id` |
| 2.5 | Corregir F2 — `rechazar` escribe `motivo_rechazo` |
| 2.6 | Corregir F7 — `basePath` derivado de configuración, no hardcodeado |
| 2.7 | Corregir S5 — `session_destroy()` en la API no debe matar la sesión del legacy |
| 2.8 | Sanear la colección Postman: **P1–P8** (base_url, auth, las 15 rutas faltantes, URL de convocados, tarjetas, cuerpos) |
| 2.9 | Decidir S8 — la matrícula sola como credencial es una decisión de producto, no un default |

**Criterio de salida**
- [ ] Un admin puede autenticarse en `/api/admin/login`
- [ ] `GET /api/partidos?torneo_id=1` devuelve solo ese torneo
- [ ] Las 83 rutas tienen request en Postman y responden algo != 404

### Fase 3 — Documentar las incidencias aceptadas
*Sin tocar la BD, por decisión explícita.*

| # | Incidencia | Dónde documentar |
|---|---|---|
| 3.1 | `sql/schema.sql` contradice la BD real — es `bd_propuesta.sql` el vigente. **Marcar `schema.sql` como obsoleto o regenerarlo desde la BD** | encabezado del propio `schema.sql` |
| 3.2 | `migracion_admin.sql` nunca aplicada: faltan `posts`, `post_reacciones`, `motivo_rechazo`. **Comunidad y el motivo de rechazo están caídos** | nota en `migracion_admin.sql` |
| 3.3 | Los 51 usuarios son `rol='usuario'`; 6 endpoints dan 403. **Decisión pendiente:** poblar `'jugador'` o relajar `requireJugador()` | aquí + `authorization.php:40` |
| 3.4 | Sin cobertura de pruebas en 70 de 83 rutas, sin runner ni CI | aquí |
| 3.5 | Sin subida de archivos (F5) — logos y avatares son strings | aquí |

### Fase 4 — Reconstruir el panel sobre la API

| # | Acción |
|---|---|
| 4.1 | `admin/assets/js/api.js` — cliente base con `credentials: 'include'` y envelope `{exito, data, errores}` |
| 4.2 | Login del panel vía `API.post('/admin/login')`; eliminar la autenticación duplicada |
| 4.3 | Migrar vistas de menor a mayor riesgo: **Inicio** (read-only) → **Participantes** → **Postulaciones** → **Votaciones** → **Torneo** → **Resultados** |
| 4.4 | Migrar las 13 acciones de escritura con endpoint equivalente. **Comunidad queda fuera** — no hay API y no se va a construir |
| 4.5 | Habilitar lo hoy inalcanzable: finalizar partido (propaga ganador), CRUD de participantes, **convocatorias (84 filas ya en BD)**, **porteros (14 filas)**, editar/borrar rondas y partidos, estadísticas |

**Criterio de salida**
- [ ] Cero `$pdo->` en `admin/partials/`
- [ ] Sin `$_SESSION['flash_*']` — el estado de la vista vive en el cliente
- [ ] Finalizar un partido propaga el ganador al bracket

---

## 6. Contrato técnico para el frontend

### 6.1 Origen y CORS

Panel: `http://localhost/PASSBALL-Cup/admin/dashboard.php` · API: `http://localhost/PASSBALL-Cup/backend/api/…`

**Mismo origen** (`localhost:80`). **No se requiere CORS** (F4 no bloquea el panel admin).
Ruta relativa válida desde cualquier vista: `../backend/api/…`

### 6.2 Autenticación — sessions PHP, no tokens

`middleware/adminAuth.php:4` lee `$_SESSION['admin_id']`; `AdminAuthController.php:44` lo
escribe. El transporte es la cookie `PHPSESSID`, **no** un header `Authorization`.

→ **`credentials: 'include'` es obligatorio** en cada `fetch`.

**Conflicto de claves de sesión** (a resolver en la Fase 4, no antes):

| | Clave | Forma |
|---|---|---|
| Panel legacy | `$_SESSION['admin']` | array `{id, nombre, usuario}` |
| API | `$_SESSION['admin_id']` | int |

Hoy no se reconocen entre sí. En la Fase 4 el panel deja de usar su propia clave.

### 6.3 Envelope de respuesta

`backend/core/response.php:4`

```json
{ "exito": true,  "data": { }, "errores": [] }
{ "exito": false, "data": [],  "errores": { "error": "…" } }
```

> **Inconsistencia a blindar:** `errores` es **array** en éxito y **objeto** en error. El
> extractor debe manejar ambos casos.

Códigos: `200` · `400` validación · `401` no autenticado · `403` inactivo/sin permisos ·
`404` no encontrado · `500` error interno. **No hay 405** (F3).

### 6.4 Cliente base propuesto

```js
const API = {
  base: '../backend/api',

  async request(metodo, ruta, cuerpo) {
    const res = await fetch(this.base + ruta, {
      method: metodo,
      credentials: 'include',            // obligatorio: la API usa PHPSESSID
      headers: { 'Content-Type': 'application/json' },
      body: cuerpo === undefined ? undefined : JSON.stringify(cuerpo)
    });

    let json = null;
    try { json = await res.json(); }
    catch { throw new Error('Respuesta no JSON (HTTP ' + res.status + ')'); }

    if (!res.ok || !json.exito) {
      throw new Error(
        Array.isArray(json.errores) ? 'Error ' + res.status : json.errores.error
      );
    }
    return json.data;
  },

  get(ruta)       { return this.request('GET', ruta); },
  post(ruta, c)   { return this.request('POST', ruta, c); },
  patch(ruta, c)  { return this.request('PATCH', ruta, c); },
  del(ruta)       { return this.request('DELETE', ruta); }
};
```

---

## 7. Mapa de las 15 acciones del panel → endpoint

| Vista | Acción actual (`admin/controllers/`) | Endpoint destino |
|---|---|---|
| Torneo | `torneo.php:23 crear_ronda` | `POST /api/torneos/{id}/rondas` |
| Torneo | `torneo.php:57 crear_partido` | `POST /api/partidos` |
| Postulaciones | `postulaciones.php:44 aprobar` | `PATCH /api/torneos/{id}/equipos/{eqId}/aprobar` |
| Postulaciones | `postulaciones.php:54 rechazar` | `PATCH /api/torneos/{id}/equipos/{eqId}/rechazar` (⚠️ F2) |
| Resultados | `resultados.php:22 actualizar_partido` | `PATCH /api/partidos/{id}/resultado` — ⚠️ **ver abajo** |
| Resultados | `resultados.php:70 agregar_gol` | `POST /api/partidos/{id}/eventos` |
| Votaciones | `votaciones.php:23 crear_categoria` | `POST /api/torneos/{id}/categorias-voto` |
| Votaciones | `votaciones.php:63 cambiar_estado` | `PATCH /api/torneos/{id}/categorias-voto/{catId}/estado` |
| Votaciones | `votaciones.php:88 eliminar_categoria` | `DELETE …/categorias-voto/{catId}` — usar el cascade de la API, no el de 3 pasos |
| Votaciones | `votaciones.php:117 agregar_candidato` | `POST …/categorias-voto/{catId}/candidatos` |
| Votaciones | `votaciones.php:160 excluir_candidato` | `POST …/categorias-voto/{catId}/candidatos` con `ajuste:'excluir'` |
| Votaciones | `votaciones.php:191 eliminar_candidato` | `DELETE …/categorias-voto/{catId}/candidatos/{id}` |
| Comunidad | `comunidad.php:46 crear_post` | ❌ sin endpoint **y sin tabla** (§1.3) — fuera de alcance |
| Comunidad | `comunidad.php:86 eliminar_post` | ❌ ídem |
| Comunidad | `comunidad.php:111 toggle_fijado` | ❌ ídem |

> **Riesgo a resolver antes de migrar `actualizar_partido`:** el panel legacy escribe
> `goles_local, goles_visitante, penales_local, penales_visitante, ganador_id y estado`
> en un solo `UPDATE`. `PATCH /api/partidos/{id}/resultado` expone un subconjunto y **no
> acepta `ganador_id`**. Hay que confirmar si el controller lo calcula o si espera que el
> cliente lo envíe. Si no lo calcula, migrar esa acción **regresa funcionalidad**; la
> alternativa es `PATCH /api/partidos/{id}/finalizar`, que sí propaga el ganador vía
> `FinalizarPartido`.

---

## 8. Cobertura por vista

🔄 pendiente · ✅ migrado · ❌ fuera de alcance

| Vista | Lecturas | Escrituras | Se habilita |
|---|---|---|---|
| **Inicio** | 🔄 torneos, partidos, postulaciones, estadísticas | — | — |
| **Torneo y Rondas** | 🔄 torneos, rondas, bracket, equipos | 🔄 crear ronda, crear partido | editar/borrar ronda y partido, finalizar partido, bracket de `BracketController` |
| **Postulaciones** | 🔄 `torneos/{id}/equipos` | 🔄 aprobar, rechazar | retirar equipo |
| **Participantes** | 🔄 `usuarios` | — | toggles de `estado` y `jugador_activo`, detalle de jugador, historial de equipos |
| **Resultados** | 🔄 partidos, eventos, estadísticas | 🔄 resultado, registrar evento | **convocatorias (84 filas)**, **porteros (14 filas)**, editar/borrar evento |
| **Votaciones** | 🔄 categorías, candidatos, pool, resultados de votos | 🔄 6 acciones | pool con `ResolverPoolCandidatos` en vez de SQL ad-hoc |
| **Comunidad** | ❌ | ❌ | — sin API y sin tabla |

---

## 9. Resumen de fases

| Fase | Alcance | Bloqueada por | Entregable |
|---|---|---|---|
| **0** | Seguridad: S1–S5, S9 | — | Sin toma de control de admin |
| **1** | Desconectar `admin/` | — | Panel inaccesible por HTTP |
| **2** | API explotable: F1, F2, F7, P1–P8, S5 | 2.2 (hashes) | 83 rutas usables |
| **3** | Documentar incidencias | — | Sin cambios de BD |
| **4** | Reconstruir panel sobre API | Fases 0–2 | 6 vistas migradas, 3 funcionalidades nuevas |

**Riesgo por fase:** 0 baja · 1 baja (reversible) · 2 media (toca auth) · 3 nula · 4 alta (reescritura).

**Fase 0 y Fase 1 no dependen de nada y se pueden ejecutar de inmediato.**
