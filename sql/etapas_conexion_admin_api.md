# PASSBALL Cup — Estado de la API y plan de desconexión del stack legacy

> **Documento técnico para revisión.**
> Alcance: panel de administración (`admin/`) y API REST (`backend/`).
> El sitio público (`index.php`, `controllers/`, `equipos/`) **queda fuera de alcance**.
>
> Última actualización incorpora la verificación contra la base de datos real
> (`passballcup`, 14 tablas) y una auditoría de las 83 rutas de la API.
>
> **Si vienes a continuar el trabajo, empieza por la [sección 0](#0-estado-de-la-implementación).**

---

## 0. Estado de la implementación

Estado: ✅ verificado · ⚠️ a medias · ⛔ bloqueado · 🟢 sin bloqueos

Un commit no puede citar su propio hash, así que esta tabla solo cubre las etapas ya
publicadas. La tabla de estado de la etapa siguiente se añade en su propio commit.

| # | Alcance | Estado | Commit | Verificado por |
|---|---|---|---|---|
| 0 | Rescate de acceso: admin `id=3`, respaldo, auditoría AFIHub | ✅ | *sin commit, solo datos* | curl |
| 1 | API segura y fiable: S1, S2, S3, F1, F3, F7, §1.9 | ✅ salvo §1.6 | `241d5da` | HTTP |
| 2.1 | `api.js`: cliente base, `credentials`, envelope `{exito,data,errores}` | ✅ | `20462c4` | node + HTTP |
| 2.2 | Login del panel por `POST /api/admin/login` | ✅ | `20462c4` | HTTP |
| 2.3 | Migrar **Inicio** como prueba de extremo a extremo | ✅ | `20462c4` | HTTP |
| 2.4 | Migrar las cinco vistas restantes | ⚠️ | `c3885fc` | HTTP, **no navegador** |
| 2.5 | `GET /api/admin/usuarios` | ✅ | `20462c4` | HTTP |
| 2.6–2.10 | Los cinco endpoints admin-only | ✅ | `c3885fc` | HTTP 200/401 |
| 3.1 | `git mv admin _retired/admin` | ⛔ b1, b2 | — | — |
| 3.2 | `_retired/.htaccess` con `Require all denied` | ⛔ depende de 3.1 | — | — |
| 3.3 | `.htaccess` raíz con `RedirectMatch 410` | ⛔ depende de 3.1 | — | — |
| 3.4 | `git mv crear_admin.php _retired/` | 🟢 | — | — |

**Por qué 2.4 está en ⚠️ y no en ✅.** Las lecturas se verificaron por HTTP, y dos
escrituras (crear ronda y crear categoría) se probaron creando y borrando el registro. Pero
**el render de las seis vistas y las escrituras completas nunca se probaron en un
navegador**, porque este entorno no tiene ninguno. Los contratos se validaron leyendo los
controladores, no viéndolos funcionar. Es lo primero que debería hacer quien retome.

**3.4 no depende de 3.1**, así que se puede sacar ya. No es obvious al leer los pasos en
orden, que es el orden natural en que seffee el documento.

**Línea de tiempo.** El documento nació en `7e58a3e`. Los tres commits de etapa son del
2026-09-29, y `c3885fc` es la `HEAD` de `David` en el momento de escribirse esto.

### 0.1 Cómo continuar desde aquí

**La Etapa 3 no se puede ejecutar tal como está escrita.** Estos seis bloqueos están
verificados contra el código, no supuestos:

| # | Bloqueo | Dónde |
|---|---|---|
| b1 | **Comunidad sigue en legacy**: consulta SQL directa y tres formularios con `action="controllers/comunidad.php"`, y la tabla `posts` no existe. Mover `admin/` la deja rota | `admin/partials/comunidad.php:19, 78, 148, 156` |
| b2 | `dashboard.php` mantiene el `require` de `database.php` **solo** para alimentar a Comunidad | `admin/dashboard.php:8` |
| b3 | `admin/controllers/login.php` sigue siendo un endpoint HTTP público aunque esté huérfano: escribe `$_SESSION['admin']` y `admin_id` | `admin/controllers/login.php:34, 40, 47` |
| b4 | No existe `.htaccess` en la raíz ni en `admin/`, solo en `backend/`: los pasos 3.2 y 3.3 hay que crearlos de cero | — |
| b5 | `dashboard.php` depende de `$_SESSION['admin']` para pintar nombre, inicial y usuario | `admin/dashboard.php:12, 139, 147, 155` |
| b6 | Credenciales quemadas en el repo | `crear_admin.php`, `auth_67676767.txt`, `auth_jug003.txt` |

**Hallazgos de seguridad abiertos**, a resolver antes de poner esto en producción:

1. **"Cerrar sesión" no destruye la sesión de la API.** `logout.php` solo hace
   `unset($_SESSION['admin'])`, pero `requireAdminAPI()` lee `admin_id` **primero**, y esa
   clave la deja puesta `setAdminSession()`. La sesión sobrevive contra los 37 endpoints
   protegidos. El arreglo es apuntar el enlace del panel a `POST /api/admin/logout`, que sí
   hace `session_destroy()`.
   (`admin/controllers/logout.php:7` · `backend/middleware/adminAuth.php:47, 19`)
2. El endpoint de login legacy del punto b3 sigue alcanzable por HTTP.
3. Los tres archivos del punto b6 llevan credenciales dentro y están versionados.

**Lo que queda sin verificar**, en orden de utilidad: render de las seis vistas, y las
seis escrituras (aprobar, rechazar, crear ronda, crear partido, guardar resultado,
registrar evento).

### 0.2 Leyenda de la numeración

Los identificadores `2.x` y `3.x` se usan en tres sentidos distintos dentro de este
archivo, y conviene saber cuál es cuál antes de leer cualquier tabla:

| Prefijo | Significado |
|---|---|
| `2.1`–`2.10` en el §5 | Sub-etapas **actuales** de la Etapa 2 |
| `3.1`–`3.4` en el §5 | Sub-etapas **actuales** de la Etapa 3 |
| `Fase 0`–`Fase 4` | Numeración **original, superada** por el reordenamiento (ver §9.1) |
| `§3.1`, `§3.2` | **Defectos** de la API, no etapas |

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

### 1.4 Ningún administrador podía iniciar sesión — **resuelto**

> **Actualización:** se creó un tercer administrador (`id=3`, `admin_local`) con hash bcrypt
> válido. Login verificado por HTTP contra `admin/controllers/login.php` (200 con contraseña
> correcta, 401 con incorrecta). Verificado también que `password_verify` acepta la nueva
> contraseña y rechaza otras.

```
id | usuario           | activo | hash               | len | login
 1 | admin_passballcup |     1 | $2y$10$…example…  |  40 | ✗ 40 chars, bcrypt exige 60
 2 | admin1            |     1 | «plano»            |   7 | ✗ texto plano, no es hash
 3 | admin_local       |     1 | $2y$10$…          |  60 | ✓ operativo
```

**Causa raíz de los dos rotos:**

- **`id=1`** viene de `sql/inserts.sql:98`, un placeholder literal de 40 caracteres cuyo
  cuerpo es `examplehashexamplehashexamplehash`. Parece un hash por el prefijo `$2y$10$`,
  pero no lo es.
- **`id=2`** se insertó **a mano** — `inserts.sql` solo crea el `id=1`. Alguien puso la
  contraseña en claro. Es además la matrícula de un jugador existente, así que se coló
  creyendo que era una credencial de admin.

`password_verify()` no detecta "esto es una contraseña sin hashear": contra texto plano
simplemente devuelve `false`. Por eso el panel siempre respondió `"Credenciales inválidas"`
(401) sin distinguir usuario inexistente, inactivo, hash inválido o contraseña correcta mal
hasheada. **El síntoma es idéntico al de escribir mal la contraseña.**

`crear_admin.php` no participa: define `elmoa8m` pero solo hace `echo`, nadie lo ejecuta.

> **Pendiente:** decidir el destino de `id=1` (asignarle contraseña, o `activo = 0`) y de
> `id=2` (contraseña nueva, o desactivar). `admin_local` ya cubre el acceso.

### 1.5 Ningún usuario tiene el rol que la API exige — por un bug, no por datos

```
+----------+------+
| rol      | n    |
+----------+------+
| usuario  | 51   |   ← los 51
| jugador  | 0    |
+----------+------+
```

> ⚠️ **Corrección de la versión anterior de este documento.** Se afirmaba que esto era
> "un problema de datos, no de esquema". **Es incorrecto.** La causa es el bug de AFIHub
> documentado en §1.7, y afecta a **toda instalación en local**, no a una BD concreta.
> Verificarlo:
>
> ```sql
> SELECT rol, COUNT(*) FROM usuarios GROUP BY rol;  -- jugador = 0, siempre
> ```

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

**No es un problema de datos.** El ENUM admite `'jugador'`; lo que impide obtenerlo es §1.7.
Arreglado ese bug, cada login nuevo de una matrícula inscrita recibe el rol automáticamente.
Decisión de producto pendiente (§8): si el rol debe re-validarse en cada login o quedarse
como registro histórico — ver §1.8.

### 1.6 El endpoint de AFIHub nunca responde bien en local — S0

**Este es el bloqueo raíz de §1.5 y el hallazgo más grave del documento.**

El diseño de autenticación es intencional y está documentado en el código: el login **no**
pide contraseña, y `jugadores_service.php` consulta un endpoint público de AFIHub que decide
si la matrícula está inscrita. Ese endpoint es **la fuente de verdad del rol**. Es un diseño
legítimo para un torneo escolar.

El problema es que en local **nunca devuelve datos**:

```php
// AFIhub/controllers/publico_verificar_inscripcion.php:4
$ALLOWED_ORIGINS = ['https://passballcup.encuestapassword2026.com'];   // ← solo producción

// backend/services/jugadores_service.php:38
'Origin: http://localhost',   // ← en local se envía localhost
```

Verificado en vivo:

```bash
curl -i "http://localhost/AFIhub/controllers/publico_verificar_inscripcion.php?afi_id=330&matricula=0000000" \
     -H "Origin: http://localhost"
# HTTP/1.1 403  {"success":false,"message":"Origen no permitido"}
```

Cadena completa del fallo:

| # | Archivo:línea | Resultado |
|---|---|---|
| 1 | `publico_verificar_inscripcion.php:6` | 403, `Origen no permitido` |
| 2 | `jugadores_service.php:61` | `$httpCode !== 200` → `return null` |
| 3 | `AuthController.php:124` | `$esJugador = (null !== null)` → `false` |
| 4 | `AuthController.php:125` | `$rol = 'usuario'` |
| 5 | `AuthController.php:136` | `jugador_activo = 0` → `requireJugador()` nunca pasa |

**Ningún usuario puede obtener `rol='jugador'` en local. Nunca.** Por eso la tabla tiene 51
`usuario` y 0 `jugador`.

> El comentario en `jugadores_service.php:35-36` documenta un contrato que AFIHub no cumple:
> "local → local, producción → producción". El whitelist es un arreglo fijo de solo producción.

**Por qué nadie lo notó:** `jugadores_service.php:61` devuelve `null` **sin ningún
`error_log`**. Las líneas 48 y 54 sí registran fallo de conexión y JSON inválido, pero el
caso 403 — el que realmente ocurre — se traga en silencio.

**El `Origin` no protege nada aquí.** La llamada es server-to-server con `curl`, no un
navegador; `Origin` es un mecanismo de CORS y un servidor puede enviar el que quiera, o
ninguno. El check no autentica al llamador: no aporta seguridad real y solo causa este bug.
Lo que sí protegería es un secreto compartido.

> **Requiere tocar `AFIhub/`, que es otro proyecto y probablemente de otro equipo.**
> Decisión pendiente: agregar `http://localhost` (mínimo, frágil), mover los orígenes a
> config, o reemplazar el check por secreto compartido.

### 1.7 El rol es una instantánea del primer login

`datosJugadorSiEstaInscrito()` se llama desde **un único sitio**: `AuthController.php:123`,
dentro de `registrarUsuario()`, que solo corre **si el usuario no existe**. Si ya existe,
`buscarUsuarioPorMatricula()` devuelve la fila y **AFIHub no se vuelve a consultar jamás**.

| Escenario | Resultado |
|---|---|
| Se reinscribe en la AFI después de su primer login | queda `rol='usuario'` para siempre |
| Abandona la AFI después de su primer login | conserva `rol='jugador'` para siempre |

`UsuariosController.php:269` permite corregir `jugador_activo` a mano vía `PATCH`, pero nada
re-valida automáticamente.

> **La premisa de seguridad —"AFIHub decide quién es jugador"— se cumple únicamente en el
> primer login.** Después, la BD local es la única autoridad. Decisión pendiente: re-validar
> en cada login, con TTL, o dejarlo como registro histórico. **No se asume aquí.**

### 1.8 La matrícula es la única credencial, y es enumerable

AFIHub valida **inscripción**, no **identidad**. El modelo de seguridad resultante es:
*"eres quien reclame esta matrícula, siempre que esté inscrita"*.

- 7 dígitos secuenciales → enumerables trivialmente.
- El endpoint de AFIHub devuelve nombre, semestre y horario → sirve de **oráculo** para
  confirmar qué matrículas existen.
- Cualquiera que conozca la matrícula de alguien inscrito entra como esa persona.

Para un torneo escolar puede ser aceptable, pero conviene que sea una decisión consciente.
Combinado con S7 (`GET /api/usuarios` pide sesión de jugador, no de admin) la enumeración
es total. No se modifica sin acuerdo explícito.

### 1.9 Conflicto de claves de sesión — bloquea la Etapa 1

Este no estaba detectado y **condiciona toda la conexión panel ↔ API**:

| | Clave | Forma | Lo escribe |
|---|---|---|---|
| Panel legacy | `$_SESSION['admin']` | array `{id, nombre, usuario}` | `admin/controllers/login.php:40` |
| API | `$_SESSION['admin_id']` | int | `AdminAuthController.php:44` |

`admin/controllers/auth.php:12` exige `$_SESSION['admin']`.
`backend/middleware/adminAuth.php:7` exige `$_SESSION['admin_id']`.

**Ninguno reconoce al otro.** Autenticar contra la API no abre el panel, y entrar por el
panel no habilita ningún endpoint. Cualquier migración de vistas empieza por resolver esto.
Ver E1.3.

Además, `AdminAuthController.php:31` **no comprueba `activo`** antes de `password_verify`, a
diferencia de `admin/controllers/login.php:34` que sí lo hace. La API permite entrar a un
admin desactivado; el panel no.

### 1.10 `git mv` a `_retired/` no desconecta nada

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
| ~~**S1**~~ ✅ | `requireAdminAPI()` **comentado** en 5 métodos de administradores — **corregido en E1.2** | `AdministradoresController.php:64,118,143,229,268` | 🚨 **Toma de control total de la cuenta.** Un anónimo puede crear un admin con la contraseña que elija, **resetear la contraseña de cualquier admin** (`PATCH` acepta `contrasena` en `:193-204`), desactivarlos y borrarlos. El propio código lo admite: `// TODO: Activar cuando exista un flujo autorizado` (`:63`) |
| ~~**S2**~~ ✅ | Tests alcanzables por HTTP sin autenticación — **corregido en E1.1** | `backend/tests/test_votaciones.php`, `prueba_bd.php` | 🚨 `backend/.htaccess:2` (`RewriteCond %{REQUEST_FILENAME} !-f`) deja los archivos servibles. `test_votaciones.php` **crea un admin `testadmin` en la BD** por HTTP anónimo. `prueba_bd.php` devuelve `DATABASE()` |
| ~~**S3**~~ ✅ | `APP_DEBUG=1` por defecto, sin `.env` ni `.env.example` — **corregido en E1.4** | `backend/config/app.php:22-26` | Cualquier 500 imprime traza PDO completa **con el DSN y el nombre de la base** a un llamador anónimo. Credenciales de BD: `root` con contraseña vacía |
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
| ~~**F1**~~ ✅ | `GET /api/partidos` no se puede filtrar — **corregido en E1.5** | `PartidosController.php:25` | `$this->obtenerId(['id' => $filtros[$campo]], $campo)` pasa el array con clave `'id'` pero `obtenerId` (`:198`) busca `$campo` (`'ronda_id'`/`'equipo_id'`) → siempre `null` → **400 en todo request filtrado**. Sin filtro no hay forma de acotar a un torneo, porque **no existe filtro `torneo_id`**. El frontend no puede listar partidos por torneo |
| **F2** | `rechazar` nunca escribe `motivo_rechazo` | `TorneoEquiposController.php:124` | El `UPDATE` pone `estado="rechazado"` y limpia `aprobado_por`, pero **nunca el motivo** — aunque el panel lo lee (`admin/partials/postulaciones.php:203`). Rechazos vía API se ven en blanco |
| ~~**F3**~~ ✅ | Router no devuelve 405 — **corregido en E1.7** | `backend/core/router.php:21-61` | `if ($rutaMetodo !== strtoupper($metodo)) continue;` descarta el método y cae en el 404 genérico. Sin cabecera `Allow` |
| **F4** | Sin CORS, sin `OPTIONS` | `backend/` (0 coincidencias de `Access-Control`) | ⚠️ **No bloquea el panel admin**: admin y API están en el mismo origen (`localhost:80`). Sí bloquearía un frontend servido en otro origen |
| **F5** | Sin soporte de subida de archivos | `backend/` (0 coincidencias de `$_FILES`) | `logo` y `avatar` se manejan como **string**. Un frontend **no puede subir** logo ni avatar por la API |
| **F6** | Sin paginación en ningún listado | `EquiposController.php:34`, `UsuariosController.php:67`, `EstadisticasController` | Aceptable a escala de torneo; problemático cuando crezca |
| ~~**F7**~~ ✅ | Base path hardcodeado — **corregido en E1.8** | `backend/index.php:27-38` | `substr($uri, strlen('/PASSBALL-Cup/backend'))`. Desplegar en otra carpeta o en raíz → **las 83 rutas dan 404** |
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

> Regla: **cada etapa deja el sistema en un estado coherente.** Nada de todo-o-nada.

### ⚠️ Reordenamiento: por qué "Etapa 1" ya no es la primera

La versión anterior de este documento empezaba por **desconectar** `admin/` y luego
reconstruirlo sobre la API. **Ese orden era un error operativo:** con el panel desconectado
y la API sin corregir, no queda nada funcionando en medio. Y la conexión tampoco era posible
por el conflicto de claves de sesión (§1.9).

**El orden correcto es al revés:** asegurar la API, conectar el panel, y **solo entonces**
desconectar el legacy. Ningún momento sin sistema utilizable.

| Etapa | Alcance | Toca el legacy | Toca la BD | Estado |
|---|---|---|---|---|
| **E0** | Tercer admin operativo, respaldo, auditoría de AFIHub | no | sí (1 fila) | ✅ `sin commit` |
| **E1** | API segura y fiable; sesión unificada | no | no | ✅ salvo §1.6 · `241d5da` |
| **E2** | **Conectar el panel a la API** — queda operativo sobre backend | sí (reescribe) | no | ✅ 6 de 7 vistas · `20462c4` + `c3885fc` |
| **E3** | Desconectar el legacy | sí | no | ⛔ bloqueada por b1–b6 |

> `E0`–`E3` son las mismas cuatro etapas que lasnumbered `0`–`3` del §5, con prefijo `E`.
> El estado y los commits por sub-etapa están en el [§0](#0-estado-de-la-implementación).
>
> La antigua `E4` ("migrar vistas y habilitar lo inalcanzable") **ya no es una etapa**: su
> parte de migración de vistas quedó absorbida por `E2`. Lo que sí quedó fuera —convocatorias,
> porteros, editar rondas— son funcionalidades pendientes, no un paso del plan; están
> listadas en la columna "Se habilita" del [§8](#8-cobertura-por-vista).

### Etapa 0 — Rescate de acceso ✅

| # | Acción | Estado |
|---|---|---|
| 0.1 | Respaldo de `passballcup` (67 KB) antes de tocar nada | ✅ |
| 0.2 | Admin `id=3` `admin_local` con bcrypt válido; login verificado por HTTP | ✅ |
| 0.3 | Auditoría del flujo AFIHub → hallazgo §1.6 | ✅ |
| 0.4 | Corrección de este documento (A506H6, rol, sesión) | ✅ |

### Etapa 1 — API segura y fiable ✅ *completa salvo 1.6*
*No toca la BD. Es la precondition de la conexión.*

| # | Acción | Defecto | Archivo | Estado |
|---|---|---|---|---|
| 1.1 | Guard `php_sapi_name() === 'cli'` + `RedirectMatch 403` | S2 | `backend/tests/`, `backend/.htaccess` | ✅ |
| 1.2 | `requireAdminAPI()` en 5 métodos | S1 | `AdministradoresController.php:63,117,142,228,267` | ✅ |
| 1.3 | **Unificar `$_SESSION['admin']` y `$_SESSION['admin_id']`** | §1.9 | `middleware/adminAuth.php`, `AdminAuthController.php`, `admin/controllers/login.php` | ✅ |
| 1.4 | `APP_DEBUG=0` + `.env.example` | S3 | `backend/config/app.php` | ✅ |
| 1.5 | Filtro de `GET /api/partidos` + filtro `torneo_id` nuevo | F1 | `PartidosController.php:25` | ✅ |
| 1.6 | `rechazar` escribe `motivo_rechazo` | F2 | `TorneoEquiposController.php:124` | ⛔ **bloqueada** |
| 1.7 | Router devuelve 405 con `Allow` | F3 | `backend/core/router.php:21-61` | ✅ |
| 1.8 | `basePath` derivado de `SCRIPT_NAME` | F7 | `backend/index.php:27-38` | ✅ |
| 1.9 | Comprobar `activo` antes de autenticar en la API | §1.9 | `AdminAuthController.php:37-41` | ✅ |

**Cómo se resolvió 1.3 (el bloqueador de la conexión)**

Se añadió `setAdminSession(int $adminId)` en `middleware/adminAuth.php`, que escribe **las
dos claves** desde un id. Los tres puntos de entrada la usan:

| Punto de entrada | Antes | Ahora |
|---|---|---|
| `POST /api/admin/login` | solo `admin_id` | `setAdminSession()` → ambas |
| `admin/controllers/login.php` | solo `admin` | ambas |
| `requireAdminAPI()` | leía solo `admin_id` | acepta cualquiera de las dos y normaliza |

`requireAdminAPI()` además **rechaza la sesión** si el admin fue borrado en medio, en vez de
dejar un id válido apuntando a nada.

**Criterio de salida — verificado**

- [x] `GET /backend/tests/test_votaciones.php` → **403**
- [x] `GET /backend/tests/prueba_bd.php` → **403**
- [x] `POST /api/administradores` sin sesión → **401**
- [x] `PATCH /api/administradores/1` (reset de contraseña) sin sesión → **401**
- [x] `DELETE /api/administradores/2` sin sesión → **401**
- [x] `GET /api/partidos?ronda_id=1` → **200** con 4 partidos (antes 400)
- [x] `GET /api/partidos?equipo_id=1` → **200** con 3 partidos
- [x] `GET /api/partidos?torneo_id=1&ronda_id=3` → **200** con 1 partido
- [x] `GET /api/partidos?ronda_id=abc` → **400** "ID inválido para ronda_id"
- [x] `GET /api/auth/login` (GET a ruta POST) → **405** `Allow: POST`
- [x] `GET /api/nope` → **404** con método y ruta en el mensaje
- [x] Login por API → `GET /api/admin/me` → **200**
- [x] Login por panel → `GET /api/admin/me` → **200** (sesión compartida)
- [x] `APP_DEBUG=0` por defecto

#### ⛔ 1.6 bloqueada — requiere decisión de esquema

F2 **no se puede arreglar solo con código.** `SHOW COLUMNS FROM torneo_equipos` confirma que
`motivo_rechazo` **no existe**:

```
id · torneo_id · equipo_id · estado · fecha_solicitud · fecha_aprobacion · aprobado_por
```

Escribirla daría un 500. Corregirlo implica `ALTER TABLE`, y la Etapa 1 se definió explícitamente
como **"no toca la BD"**. Opciones:

| Opción | Efecto |
|---|---|
| **(a)** `ALTER TABLE torneo_equipos ADD motivo_rechazo VARCHAR(255) NULL` | Habilita F2 y lo que el panel lee en `postulaciones.php:203`. Toca la BD |
| **(b)** Dejar F2 documentado | El motivo de rechazo sigue sin persistirse por API |

> Nota: el panel legacy **tampoco** puede escribir el motivo hoy, por el mismo motivo. El
> archivo `sql/migracion_admin.sql` la declaraba pero nunca se aplicó (§1.3). No es una
> regresión de la Etapa 1: es una columna que nunca existió.

### Etapa 2 — Conectar el panel a la API
*Objetivo: que el panel funcione sobre el backend. Es la "Etapa 1" del pedido original.*

| # | Acción |
|---|---|
| 2.1 | `admin/assets/js/api.js` — cliente base con `credentials: 'include'` y envelope `{exito, data, errores}` |
| 2.2 | Login del panel vía `POST /api/admin/login`; eliminar la autenticación duplicada |
| 2.3 | Migrar **Inicio** (solo lectura) como prueba de extremo a extremo |
| 2.4 | Migrar Participantes → Postulaciones → Votaciones → Torneo → Resultados |

**Criterio de salida**
- [x] El panel entra con la contraseña de `admin_local` vía la API
- [x] `GET /api/admin/me` responde 200 con la sesión del panel
- [x] Inicio sin `$pdo->`
- [x] 2.4 completo: los siete partials sin `$pdo->`

#### Estado tras 2.1–2.3

Verificado por HTTP contra Apache (`http://localhost/PASSBALL-Cup`), no solo por lectura:

| Comprobación | Resultado |
|---|---|
| `POST /api/admin/login` con `admin_local` | 200, devuelve `{id, nombre, usuario, activo}` sin el hash |
| `GET /api/admin/me` con esa sesión | 200 |
| Los 7 endpoints que consume `api.js` | 200 |
| `admin/login.php` ya no llama a `controllers/login.php` | correcto |
| `admin/dashboard.php` renderiza y cierra `</html>` | 200 |
| `inicio.php` con `$pdo->` | 0 apariciones |

`admin/controllers/login.php` queda sin referencias desde el HTML y el JS. El archivo
sigue en disco hasta E3.3, junto con `admin/assets/js/login.js`, que ya estaba huérfano.

#### 2.4 — las cinco vistas restantes

Cada partial pasó a ser un cascarón con sus contenedores `id`; el pintado y las
escrituras viven en `admin/assets/js/views.js`. Los formularios ya no hacen POST a
`admin/controllers/*.php`: disparan la API y recargan la vista.

| Vista | Lecturas | Escrituras |
|---|---|---|
| Participantes | `admin/usuarios` | — |
| Postulaciones | `torneos`, `torneos/{t}/equipos`, `administradores` | `.../aprobar`, `.../rechazar` |
| Votaciones | `admin/torneos/{t}/categorias-voto`, `.../candidatos-voto`, `.../jugadores`, `torneos/{t}/equipos` | crear / abrir / eliminar categoría, incluir / excluir candidato |
| Torneo | `torneos`, `rondas`, `partidos`, `equipos` | crear ronda, crear partido |
| Resultados | `rondas`, `partidos`, `partidos/{id}/eventos`, `admin/equipos/{e}/miembros` | `partidos/{id}/resultado`, `partidos/{id}/eventos` |

Las escrituras **ya existían** en la API y todas son `requireAdminAPI`; no hubo que
crear ninguna. Lo que faltaba eran las lecturas, y todas resultaban ser el mismo
problema que S7: endpoints de jugador cerrados a admin.

| # | Endpoint nuevo | Por qué hacía falta |
|---|---|---|
| 2.5 | `GET /api/admin/usuarios` | `/api/usuarios` pide sesión de jugador (401) |
| 2.6 | `GET /api/admin/torneos/{id}/categorias-voto` (+ `total_votos`) | `CategoriasVotoController::listar` pide sesión de jugador |
| 2.7 | `GET /api/admin/torneos/{id}/candidatos-voto` | Ajustes de las seis categorías en una llamada, como el legacy |
| 2.8 | `GET /api/admin/equipos/{id}/miembros` | `EquipoMiembrosController::listar` pide sesión de jugador |
| 2.9 | `GET /api/admin/torneos/{id}/jugadores` | Selectores de candidatos de Votaciones |
| 2.10 | `GET /api/admin/votos-resumen` | La tarjeta Votos de Inicio |

No se relajó ni un guard de jugador. Verificado: los seis dan 401 sin sesión de admin
y 200 con ella.

**El selector de torneo pasó a ser estado del cliente.** El legacy usaba
`?torneo_id=` en la URL, lo que obligaba a recargar la página entera. Ahora vive en
`ESTADO.torneoId`, se comparte entre las cuatro vistas que lo usan y se refleja en el
hash. La API acepta `orden=asc` en `admin/usuarios` para conservar el orden del legacy.

#### Dos degradaciones conscientes

**La columna «Motivo» de Postulaciones desaparece.** `torneo_equipos` no tiene
`motivo_rechazo` (F2), y el `PATCH .../rechazar` de la API solo cambia el estado. El
legacy pedía un motivo con `prompt()` y lo guardaba. Se quitó la columna y el `prompt`
antes que dejar un formulario que pide un motivo para descartarlo. Con F2 resuelto,
`TorneoEquiposController::rechazar` necesita un parámetro `motivo`.

**Comunidad sigue sin tabla.** `posts` no existe en el esquema y sigue fuera de las
vistas de 2.4; el aviso que se añadió en 2.3 es ahora su estado permanente, no
temporal.

#### Verificación de 2.4

| Comprobación | Resultado |
|---|---|
| `php -l` en los 7 partials y `dashboard.php` | sin errores |
| `node --check` en los 4 JS del panel | sin errores |
| `$pdo->` en los 7 partials | 0 apariciones |
| Los 6 endpoints nuevos con sesión de admin | 200 |
| Los 6 endpoints nuevos sin sesión | 401 |
| `dashboard.php` renderiza, cierra `</html>` y sin error PHP | 200, 14 776 bytes |
| Los 9 contenedores que espera el JS | presentes |
| Escrituras: crear ronda y crear categoría | 201, y borradas después |

`dashboard.php` baja de 46 850 a 14 776 bytes: casi todo el HTML que antes generaba PHP
ahora lo pinta el navegador.

Dos cosas que la verificación estática no cubre y hay que probar a mano en el panel:
el render de cada vista (no hay navegador en este entorno) y que los formularios
escriban lo que se espera en cada campo.


#### Estado tras 2.1–2.3

Verificado por HTTP contra Apache (`http://localhost/PASSBALL-Cup`), no solo por lectura:

| Comprobación | Resultado |
|---|---|
| `POST /api/admin/login` con `admin_local` | 200, devuelve `{id, nombre, usuario, activo}` sin el hash |
| `GET /api/admin/me` con esa sesión | 200 |
| Los 7 endpoints que consume `api.js` | 200 |
| `admin/login.php` ya no llama a `controllers/login.php` | correcto |
| `admin/dashboard.php` renderiza y cierra `</html>` | 200, 46 850 bytes |
| `inicio.php` con `$pdo->` | 0 apariciones |

`admin/controllers/login.php` queda sin referencias desde el HTML y el JS. El archivo
sigue en disco hasta E3.3, junto con `admin/assets/js/login.js`, que ya estaba huérfano.

#### Dos bloqueos que aparecieron al ejecutar 2.3

**a) `GET /api/usuarios` es inutilizable desde el panel (resuelve S7)**

`UsuariosController::listar()` llama a `requireAuthAPI()`, que solo reconoce
`$_SESSION['user_id']` / `$_SESSION['usuario']['id']` — la sesión de **jugador**. Un
administrador autenticado recibía `401 No autenticado` con su sesión perfectamente válida.

No se debilitó `requireAuthAPI()`: el panel necesita listar usuarios, no el jugador, y
cambiar el guard expondría a cualquier jugador al listado completo. Se añadió en su lugar

| # | Endpoint | Guard | Para qué |
|---|---|---|---|
| 2.5 | `GET /api/admin/usuarios?estado&jugador_activo&rol` (`AdminUsuariosController`) | `requireAdminAPI()` | Listados del panel; devuelve además `total` |

`api.js` consume este. Verificado: 200 con sesión de admin, 401 sin ella.

**b) La vista Comunidad tumbaba el panel entero (preexistente, no lo causó la E2)**

`admin/partials/comunidad.php` consultaba la tabla `posts`, que **no existe** en el esquema
(14 tablas, ninguna de comunidad). El `PDOException` era fatal y cortaba el render a
mitad: la página llegaba a 34 751 bytes sin `</html>` y, critically, **sin las etiquetas
`<script>` del final**, así que ningún JS del panel cargaba.

Auditoría de los siete partials contra `SHOW TABLES`: `posts` era la única referencia
colgante. La vista ahora degrada a un aviso y desactiva su formulario, en vez de tumbar
el panel. La reconstrucción real de Comunidad sobre la API es trabajo de E3; hasta entonces
no hay dónde publicar.

#### Pérdida de datos aceptada en Inicio

La tarjeta **Votos** queda en `0`. El legacy la llenaba con un `SELECT COUNT(*)` sobre
`torneo_votos` y la API no expone ese conteo. Se marca en el código con un comentario en
lugar de inventar un endpoint; se resuelve en E2.4 junto con Votaciones.

#### Cambio de credencial de `admin_local` (error propio)

Al verificar el login por HTTP, un script de comprobación sobrescribió el hash de
`admin_local` (id=3) **antes** de guardar el original: una ruta de archivo en PHP
interpretaba `\e2_hash_original.txt` como secuencia de escape, así que el
`file_put_contents` falló en silencio. El hash anterior quedó perdido y la contraseña que
el usuario tenía anotada dejó de servir.

Se fijó una nueva. `id=1` (hash de 40 caracteres, inválido) y `id=2` (7 caracteres, texto
plano) siguen rotos como antes; `admin_local` es el único administrador utilizable.

#### Dos fallos que sólo aparecen en un navegador

Ninguno lo detecta `php -l` ni `node --check`, y ambos habrían acumulado errores en
silencio durante la E2.4.

**La caja de aviso tenía el mismo `id` en los cinco partials.** `views.js` la buscaba con
`getElementById`, que devuelve sólo la primera del DOM, la de Participantes. Un error al
aprobar un equipo o al guardar un resultado se escribía en la vista equivocada, que está
oculta: **el mensaje se perdía sin llegar a verse**. En una escritura eso es lo peor que
puede pasar, porque aparenta que la operación funcionó. Ahora cada caja se localiza con
`.vista-aviso` dentro de `.admin-view.active`, el mismo criterio que ya usaba
`recargarVistaActiva()`, y se limpia al recargar la vista.

**El atributo `hidden` no ocultaba nada.** `admin.css` declara `.admin-alert { display: flex }`,
y eso gana en especificidad a la regla `[hidden] { display: none }` de la hoja por defecto
del navegador. Las cinco cajas se pintaban como una barra roja vacía en todas las vistas.
Se añadió `[hidden] { display: none !important; }` al principio de `admin.css`, que además
cubre `inicio.php`.

#### El router era sensible a mayúsculas — variante de F7

F7 eliminó la carpeta del proyecto hardcodeada del prefijo. El caso hermano se maintains
abierto: Apache normaliza `SCRIPT_NAME` al caso real de la carpeta en disco
(`/PASSBALL-Cup`) pero deja `REQUEST_URI` como lo escribió el usuario
(`/PASSBALL-cup`). `backend/index.php` comparaba ambos con `str_starts_with`, así que al
entrar con la capitalización cambiada el prefijo no se recortaba y **las 83 rutas**
respondían "Ruta no encontrada". Ahora la comparación es con `strncasecmp`.

Conviene distinguir dos 404 que se confunden: el de la API pesa 88 bytes
(`Ruta no encontrada`) y el de Apache 295. El primero significa ruta no registrada; el
segundo, que la petición se salió del proyecto.

#### Rutas absolutas y versión de assets

`api.js` usaba `base: "../backend/api"`, que depende de la forma de la URL: entrar en
`/admin` sin barra final hace que `../` se salga del proyecto. `config/app.php` ahora
expone `projectPath()` y `apiUrl()`, y el PHP inyecta `window.PASSBALL_API` antes de
cargar el cliente, de modo que la base no depende de cómo se escriba la URL. Lo mismo con
`assetUrl()`, que añade `?v=<filemtime>` a los cinco assets del panel: sin eso, editar un
`.js` no recarga nada y el panel ejecuta la versión anterior. `projectPath()` recorta
`/admin/...` del `SCRIPT_NAME`, así que **sólo vale para el panel**; el portal público
sigue con rutas relativas.

### Etapa 3 — Desconectar el legacy
*Solo después de que la Etapa 2 esté completa.*

| # | Acción |
|---|---|
| 3.1 | `git mv admin _retired/admin` |
| 3.2 | **`_retired/.htaccess` con `Require all denied`** — sin esto no desconecta (§1.10) |
| 3.3 | `.htaccess` raíz: `RedirectMatch 410 ^/PASSBALL-Cup/admin/` |
| 3.4 | `git mv crear_admin.php _retired/crear_admin.php` (S9) |

**Criterio de salida**
- [ ] `GET /admin/dashboard.php` → 410 o 404
- [ ] `GET /_retired/admin/dashboard.php` → **denegado**
- [ ] `admin/` sigue versionado como referencia

> Las rutas relativas de `_retired/admin/` (`../../config/database.php`) siguen resolviendo,
> así que el código se conserva funcional como referencia.

<details>
<summary>Fases originales 0–4 (superadas por el reordenamiento)</summary>

### Fase 0 — Cerrar vulnerabilidades (original)

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

</details>

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

| Vista | Lecturas | Escrituras | Se habilita |
|---|---|---|---|
| **Inicio** | ✅ torneos, partidos, postulaciones, estadísticas | — | — |
| **Torneo y Rondas** | ✅ torneos, rondas, bracket, equipos | ⚠️ crear ronda, crear partido | editar/borrar ronda y partido, finalizar partido, bracket de `BracketController` |
| **Postulaciones** | ✅ `torneos/{id}/equipos` | ⚠️ aprobar, rechazar | retirar equipo |
| **Participantes** | ✅ `admin/usuarios` | — | toggles de `estado` y `jugador_activo`, detalle de jugador, historial de equipos |
| **Resultados** | ✅ partidos, eventos, estadísticas | ⚠️ resultado, registrar evento | **convocatorias (84 filas)**, **porteros (14 filas)**, editar/borrar evento |
| **Votaciones** | ✅ categorías, candidatos, jugadores | ⚠️ 6 acciones | pool con `ResolverPoolCandidatos` en vez de SQL ad-hoc |
| **Comunidad** | ❌ | ❌ | — sin API y sin tabla |

✅ migrado sobre la API · ⚠️ migrado y **sin probar en navegador** · ❌ fuera de alcance
(la columna "Se habilita" lista lo que **no** quedó migrado: funciones que el legacy tenía
y la API no expone).

---

## 9. Resumen de etapas

| Etapa | Alcance | Bloqueada por | Entregable | Estado |
|---|---|---|---|---|
| **0** | Admin `id=3` operativo, respaldo, auditoría AFIHub | — | Acceso restaurado | ✅ |
| **1** | API segura y fiable: S1, S2, S3, F1, F3, F7, §1.9 | 1.6 (F2, requiere esquema) | 83 rutas usables, sesión unificada | ✅ salvo 1.6 |
| **2** | Conectar panel a la API | — | 6 de 7 vistas sobre el backend | ✅ ver [§0](#0-estado-de-la-implementación) |
| **3** | Desconectar el legacy | b1–b6 | Panel legacy inaccesible | ⛔ bloqueada |

**Corregidos en la Etapa 1:** S1 (toma de control de admin) · S2 (tests por HTTP) · S3
(`APP_DEBUG`) · F1 (filtro de partidos, + `torneo_id`) · F3 (405 con `Allow`) · F7 (basePath) ·
§1.9 (sesión compartida entre panel y API).

**Lo que la Etapa 1 desbloquea:** la Etapa 2 es posible por primera vez. Autenticar por la API
ahora abre el panel, y entrar por el panel habilita los 37 endpoints protegidos. Antes
ninguna de las dos vías reconocía a la otra.

**Lo que sigue bloqueado, y por qué:**

| Bloqueo | Causa | Quién decide |
|---|---|---|
| **F2** `motivo_rechazo` | La columna no existe; requiere `ALTER TABLE` | tú (§ Etapa 1, opción a/b) |
| **§1.6** rol `jugador` | Whitelist de `Origin` en AFIHub | equipo de AFIhub |
| **§1.7** re-validación del rol | Diseño de producto | producto |
| **§1.8** matrícula como credencial | Diseño de producto | producto |

**Riesgo por etapa:** 0 nula · 1 media (toca auth) · 2 alta (reescribe el panel) ·
3 baja (reversible) · 4 alta.

### Bloqueos que requieren decisión de otro equipo

| Bloqueo | Afecta | Quién decide |
|---|---|---|
| **§1.6** whitelist de `Origin` en AFIHub | 6 endpoints en 403, ningún jugador | Equipo de AFIHub |
| **§1.7** re-validación del rol | consistencia de permisos | Producto |
| **§1.8** matrícula como credencial única | modelo de identidad completo | Producto |
| **E1.2** vía para crear admins sin sesión | primer admin tras limpiar la BD | Producto |

### 9.1 Discrepancia resuelta sobre la Etapa 1

El pedido original fue "dejar operativo solo el panel de administrador con el backend",
identificado como **Etapa 1**. En la versión anterior de este documento la Fase 1 era
*desconectar* el panel — el objetivo contrario.

Se reordenó por dos razones concretas, ambas verificadas:

1. **Orden operativo:** desconectar antes de conectar deja la aplicación sin nada
   funcionando durante la migración.
2. **Bloqueo técnico:** el panel y la API no se reconocen (§1.9). Autenticar por la API no
   abría el panel. Sin resolver eso, la conexión no era posible en ninguna dirección.

La **Etapa 1** de este documento es por tanto la que prepara la API, y la conexión del panel
es la **Etapa 2**.
