# PASSBALL Cup — Estado de la API y plan de desconexión del stack legacy

> **Documento técnico para revisión.**
> Alcance: panel de administración (`admin/`) y API REST (`backend/`).
> El sitio público (`index.php`, `controllers/`, `equipos/`) **queda fuera de alcance**.
>
> Última actualización incorpora la verificación contra la base de datos real
> (`passballcup`, 16 tablas) y una auditoría de las 94 rutas de la API.
>
> **Si vienes a continuar el trabajo, empieza por la [sección 0](#0-estado-de-la-implementación).**

---

## 0. Estado de la implementación

Estado: ✅ verificado · ⚠️ a medias · ⛔ bloqueado · ❌ sin hacer

> **Si vienes a continuar el trabajo, empieza por la [sección 0](#0-estado-de-la-implementación).**
> Para el estado de un vistazo y sus commits, ve directo al
> [§10](#10-tabla-final-de-etapas), al final del documento.

### Las cinco etapas de un vistazo

| Etapa | Tema | Estado | Commits |
|---|---|---|---|
| **0** | Rescate de acceso: admin `id=3`, respaldo, auditoría AFIHub | ✅ | *sin commit, solo datos* |
| **1** | API segura y fiable | ✅ salvo 1.6 | `241d5da` |
| **2** | Conectar el panel a la API | ⚠️ falta navegador | `20462c4`, `c3885fc` |
| **3** | Desconectar el legacy | ⚠️ falta navegador | `a1bad5f`, `9b586ef`, `9ae32fb`, `be57ab1`, `439859d` |
| **4** | Poner en pantalla los datos que ya estaban | ⚠️ 5 de 9 | `99d308b`, `0b0cd7b`, `2ee5eba`, `91579e6`, `d151597` |

**Las etapas 0 a 3 están cerradas.** La 4 es la única con código pendiente: cuatro
funcionalidades de las que el legacy no mostraba. Tres ya tienen endpoint y solo falta el
control en el panel; la cuarta (4.9, reacciones) no tiene endpoint. Ver §0.1.

### Detalle por etapa

Un commit no puede citar su propio hash, así que esta tabla solo cubre las etapas ya
publicadas. El estado de la etapa siguiente se añade en su propio commit.

| Etapa | # | Alcance | Estado | Commit | Verificado por |
|---|---|---|---|---|---|
| 0 | 0.1–0.4 | Rescate de acceso: admin `id=3`, respaldo, auditoría AFIHub | ✅ | *sin commit, solo datos* | curl |
| 1 | 1.1–1.9 | API segura y fiable: S1, S2, S3, F1, F3, F7, §1.9 | ✅ salvo 1.6 | `241d5da` | HTTP |
| 2 | 2.1–2.5 | `api.js`: cliente base, `credentials`, envelope, login e **Inicio** | ✅ | `20462c4` | node + HTTP |
| 2 | 2.4 | Migrar las cinco vistas restantes | ⚠️ | `c3885fc` | HTTP, **no navegador** |
| 2 | 2.6–2.10 | Los cinco endpoints admin-only | ✅ | `c3885fc` | HTTP 200/401 |
| 3 | a1 | `sql/`: quitar `USE passballcup;` de los scripts seed | ✅ | `a1bad5f` | BD desechable |
| 3 | 3.1 | Sesión del panel sobre `admin_id` | ✅ | `9b586ef` | HTTP 200/401 |
| 3 | 3.2 | API de Comunidad admin-only + rol `administrador` | ✅ | `9ae32fb` | HTTP 200/201/404/422 |
| 3 | 3.3 | Vista de Comunidad sin SQL (shell + `views.js`) | ⚠️ | `be57ab1` | `php -l`, `node --check`, HTTP |
| 3 | 3.4–3.6 | Legacy a `_retired/`, shim de sesión, credenciales fuera del tracking | ✅ | `439859d` | HTTP 403/404, `git ls-files` |
| 4 | 4.1 | `partido_convocados`: 84 filas en pantalla, lecturas admin-only | ✅ | `99d308b` | HTTP 200/401/404/409/422 |
| 4 | 4.2 | `partido_estadisticas_portero`: 14 filas en pantalla, lecturas admin-only | ✅ | `2ee5eba` | HTTP 200/401/404/409/422 |
| 4 | 4.3 | Bug: todo rango numérico rechazaba el `0` | ✅ | `0b0cd7b` | HTTP 200/422, base igual que la siembra |
| 4 | 4.4 | Bug: tres formularios mandaban un cuerpo que el backend no esperaba | ✅ | `91579e6` | HTTP 200/404/422 |
| 4 | 4.5 | Editar categoría de votación desde el panel | ✅ | `d151597` | HTTP 200/409/422 |
| 4 | 4.6 | Editar/borrar evento desde la pantalla de resultados | ❌ | — | — |
| 4 | 4.7 | Retirar equipo de un torneo | ❌ | — | — |
| 4 | 4.8 | Toggles de `estado` y `jugador_activo` en Participantes | ❌ | — | — |
| 4 | 4.9 | Reacciones de Comunidad (`post_reacciones`) | ❌ | — | — |
| 4 | 4.10 | Todo lo anterior, en un navegador real | ⛔ | — | sin navegador en el entorno |

**Por qué 2.4, 3.3 y 4.10 están en ⚠️ y no en ✅.** Los contratos se validaron leyendo los
controladores y probando por HTTP, no viendo la página en un navegador: este entorno no
tiene ninguno. Para Comunidad eso significa que la API está probada de punta a punta
(crear, listar, fijar, eliminar) pero que **el render y los clics dentro de la vista no se
han ejecutado nunca en un navegador**. Es lo primero que debería hacer quien retome. Lo
mismo para 4.1, 4.2 y 4.5: los endpoints de cada uno están probados con sus casos de
error, pero nadie ha visto todavía el bloque de convocatoria, el de porteros ni el
formulario de editar categoría en pantalla.

**El hallazgo del `0` (4.3) salió de probar, no de leer.** Al verificar 4.2, un `POST` de
prueba con `goles_recibidos: 0` rebotó con un 422 que no cuadraba con lo que el código
prometía. La causa era `!filter_var(...)`, que trata el cero como «no es un entero»
porque `filter_var` devuelve `int(0)` y `!int(0)` es `true`. El botón «Guardar resultado»
llevaba tiempo roto por esto y por el `?? 0` de los penales. Merece la pena revisar
todos los demás rangos de `0..N` que se hayan añadido después.

**Los contratos del cliente se auditan contra el backend, no se suponen (4.4).** Al usar el
panel saltó un error: el botón de abrir/cerrar una categoría mandaba un `PATCH` sin cuerpo
a un endpoint que exige `{estado}`, y el backend respondía «JSON mal formado». Antes de
arreglarlo se revisaron las 19 acciones `data-accion` una a una contra su ruta: esa era la
única rota, y «Aprobar postulación» y «Fijar en el canal» sí son legítimos sin cuerpo. En la
misma revisión aparecieron los otros dos fallos de la tabla de abajo. La moraleja para
quien siga: **un `PATCH` sin cuerpo es un 400 garantizado salvo que el backend lo acepte
explícitamente.**

**Línea de tiempo.** El documento nació en `7e58a3e`. La Etapa 1 (`241d5da`) y la Etapa 2
(`20462c4`, `c3885fc`) son del 2026-09-29. Los cinco commits de la Etapa 3 (`a1bad5f`,
`9b586ef`, `9ae32fb`, `be57ab1`, `439859d`) y los cinco de la Etapa 4 (`99d308b`,
`0b0cd7b`, `2ee5eba`, `91579e6`, `d151597`) son del 2026-09-30.

### 0.1 Cómo continuar desde aquí

**Los seis bloqueos que impedían la Etapa 3 están resueltos.** Estado de cada uno:

| # | Bloqueo | Estado | Resuelto en |
|---|---|---|---|
| b1 | Comunidad con SQL directo y la tabla `posts` inexistente | ✅ | `9ae32fb` (API) + `be57ab1` (vista) |
| b2 | `dashboard.php` requería `database.php` solo por Comunidad | ✅ | `be57ab1` |
| b3 | Login legacy alcanzable por HTTP, con escritura de dos claves | ✅ | `439859d` (movido a `_retired/`, ahora 403) |
| b4 | Faltaban los `.htaccess` de bloqueo | ✅ | `439859d` (`_retired/.htaccess`) |
| b5 | `dashboard.php` leía `$_SESSION['admin']` | ✅ | `9b586ef` |
| b6 | Credenciales versionadas en la raíz | ✅ | `439859d` (fuera del tracking) |

**Dos de los tres hallazgos de seguridad de esta sección están cerrados; el del logout no.**
El login legacy da 403, y `crear_admin.php`, `auth_67676767.txt` y `auth_jug003.txt` ya no
están en el repo (siguen en disco local, ignorados por `.gitignore`). El tercero —que el
logout de la API destruya la sesión completa y con ella la del panel admin— **sigue
abierto**, y está en S5. Destruir la sesión completa es el defecto, no la solución: lo que
habría que hacer es borrar solo las claves de jugador.

**Lo que queda abierto, en orden de utilidad.** Lo primero son las cuatro funcionalidades
de la Etapa 4 (4.6–4.9), que son las únicas con código pendiente; lo segundo es la
verificación en navegador, que no es código sino mirar la página.

| # | Pendiente | Tipo | Bloquea a |
|---|---|---|---|
| p1 | **Probar el panel en un navegador** (2.4, 3.3, 4.10) | verificación | el ✅ de esas tres filas |
| p2 | Editar/borrar evento desde Resultados (4.6) | código | — |
| p3 | Retirar equipo de un torneo (4.7) | código | — |
| p4 | Toggles de `estado` y `jugador_activo` en Participantes (4.8) | código | — |
| p5 | Reacciones de Comunidad (4.9) | código | — |
| p6 | **Rotar la contraseña de `admin_local`** | seguridad | producción |
| p7 | Motivo de rechazo de postulaciones (F2, ver §1.5) | código | — |
| p8 | S5: el logout de jugador borra la sesión del panel admin | código | — |
| p9 | Quitar `admin_passballcup` (`id=1`) o darle credencial real | limpieza | — |
| p10 | Sincronizar la copia de `bd_propuesta.sql` de Descargas | limpieza | — |
| p11 | §1.6 (AFIHub, `403 Origen no permitido`) | **otro equipo** | 6 endpoints |

Detalles de los que no son de la Etapa 4:

- **p1, navegador.** Es lo único que impide marcar 2.4, 3.3 y 4.10 en verde: el render de
  las siete vistas y, en Comunidad, crear/fijar/eliminar en pantalla.
- **p6, contraseña.** Se compartió en un canal de chat y el hash llegó a estar en un
  archivo versionado. Aunque el archivo ya no está en el repo, la contraseña está en el
  historial de git y debe cambiarse antes de producción.
- **p7, `motivo_rechazo`.** La columna `torneo_equipos.motivo_rechazo` ya existe (`text`,
  nullable, la creó `migracion_admin.sql`), así que no hay `ALTER TABLE` pendiente. El
  endpoint `PATCH /api/torneos/{id}/equipos/{id}/rechazar` cambia el estado sin escribir el
  motivo, el cliente manda un `confirm()` a secas (`views.js:1244`) y el panel tampoco
  muestra la columna. Cerrarlo son tres cambios: leer el motivo del cuerpo, escribirlo en
  el `UPDATE`, y pedirlo en el diálogo.
- **p8, `admin_passballcup`.** Tiene un hash placeholder de 40 caracteres, así que no
  puede autenticarse, pero ensucia la tabla `administradores` y `sql/inserts.sql` lo sigue
  sembrando.
- **p9, `bd_propuesta.sql`.** La de `C:\Users\Black\Downloads\` es la que se ejecutó; no
  recibió el orden de borrado con FKs ni el rol `administrador` que sí están en el repo.
- **p10, AFIHub.** Bloqueo de red, no de código: sigue fuera de alcance.

### 0.2 Leyenda de la numeración

Los identificadores `2.x`, `3.x` y `4.x` se usan en tres sentidos distintos dentro de este
archivo, y conviene saber cuál es cuál antes de leer cualquier tabla:

| Prefijo | Significado |
|---|---|
| `Etapa 0`–`Etapa 4` | Plan **vigente** (§5) |
| `2.1`–`2.10` en el §5 | Sub-etapas **actuales** de la Etapa 2 |
| `3.1`–`3.7` en el §5 | Sub-etapas **actuales** de la Etapa 3 |
| `4.1`–`4.10` en el §5 | Sub-etapas **actuales** de la Etapa 4 |
| `Fase 0`–`Fase 4` | Plan **original, superado** por el reordenamiento (§5, y §10 para la equivalencia) |
| `S1`–`S10`, `F1`–`F10` | **Defectos** de la API (§3), no etapas |
| `p1`–`p11` en el §0.1 | Pendientes de trabajo, no etapas |

---

## 1. Diagnóstico corregido

### 1.1 La API no está pendiente de construir: ya está terminada

La premisa inicial de este documento era que había que "implementar la API". La auditoría
demuestra lo contrario:

| Métrica | Resultado |
|---|---|
| Rutas registradas en `backend/routes/api.php` | **94** |
| Rutas que resuelven a un método implementado | **94 / 94 (100%)** |
| Métodos stub / TODO / `throw new` vacío | **0** |
| Controladores referenciados en rutas que no existen | **0** |
| Hallazgos de inyección SQL | **0** — todas las queries usan prepared statements |
| Servicios muertos (`FinalizarPartido`, `ResolverPoolCandidatos`, `jugadores_service`) | **0** — los 3 vivos |
| Tablas de BD con controller | **14 / 16** |

> **Por qué 94 y no 83.** La cifra de 83 era correcta cuando se hizo la auditoría, en la
> Etapa 1. Las etapas siguientes añadieron rutas y el número se quedó viejo en el
> documento. El desglose: la Etapa 2 añadió **6** endpoints de administración, la 3.2
> añadió **4** de posts, y hay **1** ruta de prueba (`GET /api/test`, que es S12 y está
> sin autenticar). 83 + 6 + 4 + 1 = 94. Las 83 originales siguen siendo las mismas; nadie
> quitó ninguna. Nota para el que cuente: el patrón `^\$router->` marca 94, pero
> `GET /api/test` ocupa **cuatro** líneas (`api.php:7-10`) porque el path va en la línea
> siguiente, así que un regex que exija el path en la misma línea cuenta 93 y parece que
> falta una ruta.

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

### 1.3 `sql/migracion_admin.sql` no se había aplicado — **resuelto**

Este diagnóstico era correcto cuando se escribió. **La migración ya está aplicada** en
`passballcup`, junto con el `enum` de `usuarios.rol` ampliado a tres valores. Estado real:

| Objeto | ¿Existe en BD? | Quién lo usa |
|---|---|---|
| Tabla `posts` | ✅ sí | `backend/controllers/AdminComunidadController.php` |
| Tabla `post_reacciones` | ✅ sí | *nadie todavía* (§2.1) |
| Columna `torneo_equipos.motivo_rechazo` | ✅ sí | el panel la lee; la API aún no la escribe (F2) |
| `usuarios.rol` = `ENUM('usuario','jugador','administrador')` | ✅ sí | marca a los admins para que no acaben en un equipo |

Dos funcionalidades estaban **rotas y en silencio** mientras la migración no se aplicaba:

1. **Vista Comunidad** — consultaba una tabla que no existía.
2. **Rechazar postulación con motivo** — el panel escribía una columna que no existía.

Ambas quedaron resueltas por la Etapa 3, no por el esquema solo. La vista pasó a consumir
`GET /api/admin/posts` (`be57ab1`) y el motivo sigue pendiente de que la API lo escriba
(F2, §0.1).

> **El script sigue siendo obligatorio** en instalaciones nuevas o recreadas:
> `bd_propuesta.sql` deja 14 tablas y `migracion_admin.sql` es la que agrega las 2 de
> comunidad. Si se resetea la base, hay que correr los dos en orden.
### 1.4 Ningún administrador podía iniciar sesión — **resuelto**

> **Actualización:** se creó un tercer administrador (`id=3`, `admin_local`) con hash bcrypt
> válido. Login verificado por HTTP contra `admin/controllers/login.php` (200 con contraseña
> correcta, 401 con incorrecta). Verificado también que `password_verify` acepta la nueva
> contraseña y rechaza otras.

> ⚠️ **Sobre las rutas `admin/controllers/…` de este documento.** Se dejan tal cual
> porque describen lo que había en su momento, y reescribirlas falsearía la historia. Pero
> **ninguno de esos archivos está ya ahí**: los 6 controllers legacy se movieron a
> **`_retired/admin/`** en el commit `439859d` (Etapa 2), y la carpeta `admin/` se quedó
> solo con el panel nuevo. Si buscas uno de esos archivos, está en `_retired/admin/`. La
> lista completa de lo que hay ahora está en el §5, sub-etapa 2.6.

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

### 1.9 Conflicto de claves de sesión — **resuelto en `9b586ef` y `439859d`**

Este no estaba detectado y **condicionaba toda la conexión panel ↔ API**:

| | Clave | Forma | Lo escribe |
|---|---|---|---|
| Panel legacy | `$_SESSION['admin']` | array `{id, nombre, usuario}` | `admin/controllers/login.php:40` (retirado) |
| API | `$_SESSION['admin_id']` | int | `AdminAuthController.php:44` |

`admin/controllers/auth.php` exigía `$_SESSION['admin']` y
`backend/middleware/adminAuth.php` exigía `$_SESSION['admin_id']`. **Ninguno reconocía al
otro**: autenticar contra la API no abría el panel, y entrar por el panel no habilitaba
ningún endpoint. `setAdminSession()` mantenía las dos sincronizadas a mano, lo que además
hacía que el logout del panel dejara viva la clave que la API mira.

**Estado actual:** queda una sola clave, `$_SESSION['admin_id']`. `admin/controllers/auth.php`
la lee y busca el nombre en la base; `adminAuth.php` la lee y devuelve 401 si no está;
`AdminAuthController` la escribe. `setAdminSession()` ya no existe.

Además, `AdminAuthController` **no comprobaba `activo`** antes de `password_verify`, a
diferencia del login del panel, que sí lo hacía. **Corregido:** ahora devuelve 403 con
*"El administrador está inactivo"*.

Verificado por HTTP: login 200, `/api/admin/me` 200, dashboard 200; tras logout 401 y 302.


### 1.10 `git mv` a `_retired/` no desconecta nada — **resuelto en `439859d`**

El plan original era mover `admin/` a `_retired/`. **Eso no corta el acceso**: `_retired/`
sigue dentro del docroot de XAMPP, y el panel seguiría respondiendo en
`http://localhost/PASSBALL-Cup/_retired/admin/dashboard.php`.

El `git mv` ordena el código pero no desactiva nada. **Debe acompañarse de una regla de
denegar**, o el corte es cosmético.

**Estado actual:** `_retired/.htaccess` lleva `Require all denied` (con un `IfModule` de
respaldo para la sintaxis 2.2). Verificado: `/_retired/admin/login.php` y
`/_retired/.htaccess` devuelven **403**, no 404. Ver §5, Etapa 3.

---

## 2. Inventario verificado

### 2.1 Base de datos `passballcup` (16 tablas)

Las dos últimas filas estaban como *inexistente* cuando se escribió esto. `migracion_admin.sql`
ya está aplicada, así que existen y son las que usa la API de Comunidad.

Conteos verificados contra la BD el 2026-09-30, después de las pruebas de la Etapa 4.

| Tabla | Filas | ¿Cubierta por la API? |
|---|---|---|
| `usuarios` | 52 | ✅ 5 rutas |
| `administradores` | 2 | ✅ 6 rutas, todas con auth desde `9b586ef` |
| `equipos` | 10 | ✅ 6 rutas |
| `equipo_miembros` | 48 | ✅ 6 rutas |
| `torneos` | 1 | ✅ 5 rutas |
| `torneo_equipos` | 10 | ✅ 5 rutas |
| `torneo_rondas` | 3 | ✅ 4 rutas |
| `partidos` | 7 | ✅ 7 rutas |
| `partido_convocados` | **84** | ✅ 4 rutas, visibles desde `99d308b` |
| `partido_eventos` | 43 | ✅ 4 rutas |
| `partido_estadisticas_portero` | **14** | ✅ 4 rutas, visibles desde `2ee5eba` |
| `torneo_categorias_voto` | 0 | ✅ 6 rutas |
| `torneo_categoria_candidatos` | 0 | ✅ 3 rutas |
| `torneo_votos` | 0 | ✅ 4 rutas |
| `posts` | 1 | ✅ 4 rutas admin (`9ae32fb`) |
| `post_reacciones` | 1 | ❌ 0 rutas — la tabla existe y nadie la lee |

> **84 filas en `partido_convocados` y 14 en `partido_estadisticas_portero` ya estaban en
> la BD** y ninguna UI las mostraba. La Etapa 4 (`99d308b` y `2ee5eba`) las hizo visibles
> sin tocar los datos.

> **`post_reacciones` está huérfana, y hay dos contadores de «likes» que no coinciden.**
> La API de Comunidad expone listar, crear, fijar y eliminar, que es lo que hacía el legacy;
> **ningún endpoint lee ni escribe `post_reacciones`** (0 coincidencias en todo `backend/`).
> La fila que hay en esa tabla la puso alguien por fuera. Lo que el panel muestra es
> `posts.likes`, un contador desnormalizado que vive en otra tabla (`views.js:1145`). Si el
> portal público debe poder dar like, hay que decidir cuál de los dos manda antes de
> construir nada: migrar a `post_reacciones` y borrar `likes`, o al revés.

### 2.2 Cobertura de autenticación

| Categoría | Rutas | Estado |
|---|---|---|
| Protegidas con `requireAdminAPI()` | 37 + 4 de Comunidad | ✅ correcto |
| Sesión de jugador (`requireAuthAPI()`) | 11 | ⚠️ 2 de estas son superficies de admin |
| `requireJugador()` / `requireCapitan()` | 6 | ❌ 403 para todos (§1.5) |
| Lecturas públicas intencionadas | 21 | ✅ aceptable |
| Logins | 1 (API) | ✅ el legacy se retiró en `439859d` |
| **Sin auth, no deberían tenerlo** | **0** | ✅ los 6 eran controllers legacy |

### 2.3 Cobertura de pruebas

| Métrica | Valor |
|---|---|
| Controllers con pruebas | **3 de 19** (`CategoriasVoto`, `CandidatosVoto`, `Votos`) |
| Rutas cubiertas | **13 de 94 (~14%)** |
| `composer.json` / `phpunit.xml` / CI | **ninguno** |

`backend/tests/test_votaciones.php` es un buen harness de integración (50 asserts, cookie
jars, `finally` con cleanup, verifica fronteras de auth y aislamiento). **Pero cubre un
solo módulo.** Los 8 defectos de §3.2 viven en el 84% sin probar — por eso sobrevivieron.

---

## 3. Defectos confirmados de la API

### 3.1 Seguridad — bloquean la puesta en marcha

| # | Defecto | Ubicación | Impacto |
|---|---|---|---|
| ~~**S1**~~ ✅ | `requireAdminAPI()` **comentado** en 5 métodos de administradores — **corregido en 1.2** | `AdministradoresController.php:64,118,143,229,268` | 🚨 **Toma de control total de la cuenta.** Un anónimo puede crear un admin con la contraseña que elija, **resetear la contraseña de cualquier admin** (`PATCH` acepta `contrasena` en `:193-204`), desactivarlos y borrarlos. El propio código lo admite: `// TODO: Activar cuando exista un flujo autorizado` (`:63`) |
| ~~**S2**~~ ✅ | Tests alcanzables por HTTP sin autenticación — **corregido en 1.1** | `backend/tests/test_votaciones.php`, `prueba_bd.php` | 🚨 `backend/.htaccess:2` (`RewriteCond %{REQUEST_FILENAME} !-f`) deja los archivos servibles. `test_votaciones.php` **crea un admin `testadmin` en la BD** por HTTP anónimo. `prueba_bd.php` devuelve `DATABASE()` |
| ~~**S3**~~ ✅ | `APP_DEBUG=1` por defecto, sin `.env` ni `.env.example` — **corregido en 1.4** | `backend/config/app.php:22-26` | Cualquier 500 imprime traza PDO completa **con el DSN y el nombre de la base** a un llamador anónimo. Credenciales de BD: `root` con contraseña vacía |
| **S4** | Sin rate limiting ni lockout en login de admin | `AdminAuthController.php:8` | Permite fuerza bruta |
| **S5** | `session_destroy()` en el logout de jugador borra la sesión del panel | `AuthController.php:62`, `AdminAuthController.php:56` | **Sigue abierto.** Panel y API comparten una sola sesión PHP con dos claves (`admin_id` y `user_id`, ver `middleware/auth.php:7` y `adminAuth.php:24`). Un `session_destroy()` a secas se lleva las dos: si un jugador cierra sesión en el mismo navegador, el admin queda desconectado del panel. Lo correcto es borrar solo `$_SESSION['user_id']` y el `usuario`, y dejar `admin_id` intacto |
| **S6** | Sin CSRF, sin `SameSite`/`Secure`/`HttpOnly` explícitos | `middleware/auth.php:5` | Todo endpoint mutante se autentica solo con cookie de sesión |
| **S7** | `GET /api/usuarios` y `/api/usuarios/{id}` piden sesión de **jugador**, no de admin | `UsuariosController.php:19,81` | Cualquier jugador autenticado enumera todos los usuarios (matrícula, rol, estado) |
| **S8** | Auth de jugador: **sin contraseña**, con autoaprovisionamiento | `AuthController.php:20,23,31` | La matrícula de 7 dígitos es la credencial completa. Una matrícula desconocida **crea la cuenta** (`:31`). Combinado con S7 es enumeración total |
| ~~**S9**~~ ✅ | Credencial de admin en claro, en archivo trackeado y dentro del docroot — **corregido en 3.6** | `crear_admin.php:2-3` | El archivo ya no está en el repo (sigue en disco local, ignorado por `.gitignore`). La contraseña está en el historial: hay que rotarla, ver p6 |
| ~~**S10**~~ ✅ | Hash inválido y contraseña en claro sembrados en producción — **corregido en la Etapa 0** | `inserts.sql:98`, tabla `administradores` id=2 | §1.4 — el panel ya no era accesible; `admin_local` tiene bcrypt válido |
| **S11** | El login de admin no renueva el id de sesión | `AdminAuthController.php:43-44` | **Session fixation.** Va de `session_start()` a `$_SESSION['admin_id']` sin `session_regenerate_id(true)`, que el login de jugador sí hace (`AuthController.php:42`). Un atacante que fije el id antes del login conserva la sesión viva. Una línea lo arregla — ver Etapa 5, punto 5.6 |
| **S12** | `GET /api/test` es público | `TestController.php:6` | Sin `requireAdminAPI()`. Hace `SELECT 1` y responde `{"mensaje":"API funcionando correctamente"}`: no filtra datos, pero confirma por HTTP que la API y la base están vivas. Ver Etapa 5, punto 5.7 |

### 3.2 Funcionalidad

| # | Defecto | Ubicación | Impacto |
|---|---|---|---|
| ~~**F1**~~ ✅ | `GET /api/partidos` no se puede filtrar — **corregido en 1.5** | `PartidosController.php:25` | `$this->obtenerId(['id' => $filtros[$campo]], $campo)` pasa el array con clave `'id'` pero `obtenerId` (`:198`) busca `$campo` (`'ronda_id'`/`'equipo_id'`) → siempre `null` → **400 en todo request filtrado**. Sin filtro no hay forma de acotar a un torneo, porque **no existe filtro `torneo_id`**. El frontend no puede listar partidos por torneo |
| **F2** | `rechazar` nunca escribe `motivo_rechazo` | `TorneoEquiposController.php:122-127` | El `UPDATE` pone `estado="rechazado"` y limpia `aprobado_por`, pero **nunca el motivo**. La columna **sí existe** (`text`, nullable, la creó `migracion_admin.sql`), así que no hace falta `ALTER TABLE`; y el panel **tampoco la lee** — `postulaciones.php` no la menciona, y el cliente manda un `confirm()` a secas en `views.js:1244`. Cerrarlo son tres líneas: leer el motivo del cuerpo, escribirlo, y pedirlo en un diálogo |
| ~~**F3**~~ ✅ | Router no devuelve 405 — **corregido en 1.7** | `backend/core/router.php:21-61` | `if ($rutaMetodo !== strtoupper($metodo)) continue;` descarta el método y cae en el 404 genérico. Sin cabecera `Allow` |
| **F4** | Sin CORS, sin `OPTIONS` | `backend/` (0 coincidencias de `Access-Control`) | ⚠️ **No bloquea el panel admin**: admin y API están en el mismo origen (`localhost:80`). Sí bloquearía un frontend servido en otro origen |
| **F5** | Sin soporte de subida de archivos | `backend/` (0 coincidencias de `$_FILES`) | `logo` y `avatar` se manejan como **string**. Un frontend **no puede subir** logo ni avatar por la API |
| **F6** | Sin paginación en ningún listado | `EquiposController.php:34`, `UsuariosController.php:67`, `EstadisticasController` | Aceptable a escala de torneo; problemático cuando crezca |
| ~~**F7**~~ ✅ | Base path hardcodeado — **corregido en 1.8** | `backend/index.php:27-38` | `substr($uri, strlen('/PASSBALL-Cup/backend'))`. Desplegar en otra carpeta o en raíz → **las 94 rutas dan 404** |
| **F8** | `session_start()` sin guarda | `AuthController.php:38`, `AdminAuthController.php:43` | `E_NOTICE` "session already started" si se alcanza dos veces. El resto del código sí usa la guarda `session_status()` |
| **F9** | `requireRol()` es código muerto | `security/authorization.php:5` | 0 call sites en todo el repo |
| **F10** | Sin versionado de API | rutas `/api/...` | Sin `/v1` no hay espacio para cambios incompatibles |

---

## 4. Defectos de la colección Postman

`backend/passballcup.postman_collection.json` — **83 peticiones vs 94 rutas**.

> La cifra de 68 también era vieja: la Etapa 2 y la 3.2 añadieron peticiones a la par que
> las rutas. La cobertura real es 83 de 94, no 68 de 83. Los defectos P1 y P2 **siguen
> abiertos**: se comprobó que el objeto raíz de la colección no tiene bloque `auth` ni
> `variable`, así que la colección continúa sin ser ejecutable tal cual.

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

## 5. Etapas de ejecución

> Regla: **cada etapa deja el sistema en un estado coherente.** Nada de todo-o-nada.

**En este §5 hay dos numeraciones y solo una manda.** Las **Etapas 0 a 4** (abajo) son el
plan vigente. Las **Fases 0 a 4**, al final de esta misma sección dentro de un `<details>`,
son el plan original, ya superado: se conservan solo como registro de por qué se cambió el
orden. La tabla con la equivalencia está en el
[§10](#10-tabla-final-de-etapas), al final del documento.

> ⚠️ **El número no significa lo mismo en las dos.** La **Fase 1** era *desconectar el
> panel*; la **Etapa 1** es *asegurar la API*. Son objetivos opuestos. Si llegaste aquí
> por un número, lee esta tabla antes que el título de la sección.

### ⚠️ Reordenamiento: por qué "Etapa 1" ya no es la primera

La versión anterior de este documento empezaba por **desconectar** `admin/` y luego
reconstruirlo sobre la API. **Ese orden era un error operativo:** con el panel desconectado
y la API sin corregir, no queda nada funcionando en medio. Y la conexión tampoco era posible
por el conflicto de claves de sesión (§1.9).

**El orden correcto es al revés:** asegurar la API, conectar el panel, y **solo entonces**
desconectar el legacy. Ningún momento sin sistema utilizable.

El resumen con estado y commits está en el [§0](#0-estado-de-la-implementación) y en el
[§10](#10-tabla-final-de-etapas).

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
| 1.6 | `rechazar` escribe `motivo_rechazo` | F2 | `TorneoEquiposController.php:122` | ❌ sin hacer (p7) |
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
> archivo `sql/migracion_admin.sql` la declaraba pero no se había aplicado (§1.3, ya
resuelto: la columna existe desde que se corrió la migración). No es una
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
quedó en disco hasta la Etapa 3, junto con `admin/assets/js/login.js`, que ya estaba
huérfano. Los dos se movieron a `_retired/admin/` en `439859d` y ahora devuelven 403.

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

**Comunidad seguía sin tabla al cierre de la Etapa 2** *(resuelto en la Etapa 3: `9ae32fb`
+ `be57ab1`)*. `posts` no existía en el esquema y quedaba fuera de las
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
quedó en disco hasta la Etapa 3, junto con `admin/assets/js/login.js`, que ya estaba
huérfano. Los dos se movieron a `_retired/admin/` en `439859d` y ahora devuelven 403.

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

**b) La vista Comunidad tumbaba el panel entero (preexistente, no lo causó la Etapa 2)**

`admin/partials/comunidad.php` consultaba la tabla `posts`, que **no existe** en el esquema
(14 tablas, ninguna de comunidad). El `PDOException` era fatal y cortaba el render a
mitad: la página llegaba a 34 751 bytes sin `</html>` y, critically, **sin las etiquetas
`<script>` del final**, así que ningún JS del panel cargaba.

Auditoría de los siete partials contra `SHOW TABLES`: `posts` era la única referencia
colgante. La vista ahora degrada a un aviso y desactiva su formulario, en vez de tumbar
el panel. La reconstrucción real de Comunidad sobre la API es trabajo de la Etapa 3; hasta entonces
no hay dónde publicar.

#### Pérdida de datos aceptada en Inicio

La tarjeta **Votos** queda en `0`. El legacy la llenaba con un `SELECT COUNT(*)` sobre
`torneo_votos` y la API no expone ese conteo. Se marca en el código con un comentario en
lugar de inventar un endpoint; se resuelve en 2.4 junto con Votaciones.

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
silencio durante la 2.4.

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
entrar con la capitalización cambiada el prefijo no se recortaba y **las 94 rutas**
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
*Ejecutada. Ver la tabla de estado en §0.*

El plan original era mover `admin/` entero a `_retired/`. **Eso no se hizo, y es
intencionado:** `admin/` no es legacy, es el panel actual, y ya no corre SQL propio (§2). Lo
legacy eran seis controllers y un JS de login que quedaban huérfanos, más el login legacy
que seguía siendo un endpoint público. Moverlos basta y deja el panel donde debe estar.

| # | Acción | Commit | Estado |
|---|---|---|---|
| 3.1 | Sesión del panel sobre `admin_id`, login por API, logout completo | `9b586ef` | ✅ |
| 3.2 | API de Comunidad admin-only + rol `administrador` | `9ae32fb` | ✅ |
| 3.3 | Vista de Comunidad como shell + `views.js`, sin `database.php` | `be57ab1` | ⚠️ sin navegador |
| 3.4 | `git mv` de los 6 controllers + `login.js` a `_retired/admin/` | `439859d` | ✅ |
| 3.5 | **`_retired/.htaccess` con `Require all denied`** — sin esto no desconecta (§1.10) | `439859d` | ✅ |
| 3.6 | Fuera `crear_admin.php` y los `auth_*.txt`, con `.gitignore` que los bloquea | `439859d` | ✅ |
| 3.7 | Quitar `setAdminSession()` y el shim de doble clave | `439859d` | ✅ |

**No se hizo el paso 3.3 original** (`.htaccess` raíz con `RedirectMatch 410`): tras mover
los controllers, `admin/` solo contiene el panel que debe seguir sirviéndose, así que
aplicarle un 410 habría tumbado el panel en producción.

**Criterio de salida, verificado por HTTP**
- [x] `GET /admin/controllers/login.php` → **404** (ya no existe)
- [x] `GET /_retired/admin/login.php` → **403** (denegado, no 404: el archivo sigue ahí)
- [x] `GET /_retired/.htaccess` → **403**
- [x] `GET /admin/dashboard.php` sin sesión → **302** a `login.php`
- [x] `GET /admin/dashboard.php` con sesión → **200**, siete vistas
- [x] `POST /api/admin/logout` y `logout.php` → sesión destruida, `/api/admin/me` da **401**
- [x] `admin/` sigue versionado como referencia, junto a `partials/` y `assets/`

> Las rutas relativas de `_retired/admin/` (`../../config/database.php`) siguen resolviendo
> desde un nivel más arriba, así que el código se conserva legible como referencia.

### Etapa 4 — Poner en pantalla los datos que ya estaban

*Ejecutada a medias. Ver la tabla de estado en §0.*

La «Fase 4» original (reconstruir todo el panel sobre la API) quedó sin efecto cuando el
plan se reordenó: las Etapas 2 y 3 ya lo hicieron. Lo que sí quedó pendiente era otro
cosido con el mismo nombre, y es lo que esta etapa cubre: **la base tenía datos que
ninguna pantalla podía mostrar**. El criterio de esta etapa no es escribir código nuevo,
es que cada tabla de la siembra que el panel no leía, ahora se lea y se pueda editar.

| # | Acción | Commit | Estado |
|---|---|---|---|
| 4.1 | `partido_convocados`: 84 filas visibles y editables en Resultados | `99d308b` | ✅ |
| 4.2 | `partido_estadisticas_portero`: 14 filas visibles y editables | `2ee5eba` | ✅ |
| 4.3 | Bug: todo rango numérico rechazaba el `0` | `0b0cd7b` | ✅ |
| 4.4 | Bug: tres formularios mandaban un cuerpo que el backend no esperaba | `91579e6` | ✅ |
| 4.5 | Editar categoría de votación desde el panel | `d151597` | ✅ |
| 4.6 | Editar/borrar evento desde la pantalla de resultados | — | ❌ sin hacer |
| 4.7 | Retirar equipo de un torneo | — | ❌ sin hacer |
| 4.8 | Toggles de `estado` y `jugador_activo` en Participantes | — | ❌ sin hacer |
| 4.9 | Reacciones de Comunidad (`post_reacciones`) | — | ❌ sin hacer |
| 4.10 | Todo lo anterior, en un navegador real | — | ⛔ sin navegador |

**Lo pendiente son cuatro pantallas, pero no todas igual de fácil.** Tres tienen el endpoint
escrito y probado, y solo falta el control en `views.js`. La cuarta (4.9) no tiene endpoint
y encima tiene una decisión de diseño pendiente. Rutas verificadas en
`backend/routes/api.php`:

| Pendiente | Endpoint | Guard | Qué falta |
|---|---|---|---|
| 4.6 Editar/borrar evento | `PATCH`/`DELETE /api/partidos/{partidoId}/eventos/{eventoId}` (`api.php:107-108`) | `requireAdminAPI()` | botón y diálogo en Resultados |
| 4.7 Retirar equipo | `PATCH /api/torneos/{torneoId}/equipos/{equipoId}/retirar` (`api.php:82`) | `requireAdminAPI()` | botón y confirmación en Postulaciones |
| 4.8 Toggles de Participantes | `PATCH /api/usuarios/{id}/estado` y `PATCH /api/usuarios/{id}/jugador-activo` (`api.php:28-29`) | `requireAdminAPI()` | los dos interruptores de la tabla |
| 4.9 Reacciones | **no existe** | — | endpoint nuevo, y decidir qué contador manda |

**4.9 no es un rato de frontend.** No hay ninguna ruta de reacciones en `api.php`, así que
hay que escribir el endpoint. Y antes hay que resolver el conflicto de §2.1: existe
`post_reacciones` (una fila por reacción) y también `posts.likes` (un contador), y el panel
muestra el segundo. Hay que elegir uno; si se elige `post_reacciones`, `likes` sobra y
habría que decidir si se borra la columna.

**Criterio de salida, verificado por HTTP**
- [x] `GET /api/partidos/1/convocados` sin sesión → **401** (antes 200 con las matrículas)
- [x] `GET /api/partidos/1/porteros` sin sesión → **401** (antes 200 con las matrículas)
- [x] Convocatorias: 12 por partido, 10 titulares + 2 suplentes, las 84 filas intactas
- [x] Porteros: 2 por partido, las 14 filas intactas
- [x] `PATCH` de resultado, convocatoria, portero y evento, incluidos los casos de error
- [x] Abrir/cerrar categoría manda `{estado}` y responde 200; sin cuerpo responde 400
- [x] Editar categoría persiste nombre, modo y orden; 409 al cambiar tipo con candidatos
- [x] Base igual que la siembra tras las pruebas: 7 partidos, 43 eventos, 84, 14
- [ ] Todo lo anterior en un navegador — **pendiente** (4.10)

**El fallo del `0` (4.3) no era cosmético.** `filter_var` con `FILTER_VALIDATE_INT`
devuelve `int(0)` para el cero, y `!int(0)` es `true`, así que el operador `!` decía
«no es un entero» justo en el único entero que el rango solía admitir. Afectaba a
`obtenerNoNegativo` (goles y penales), `numero` (atajadas y goles recibidos) y
`validarMinuto`. Encima de eso, el formulario de resultados mandaba los
penales en `null` y el backend los pasaba por `?? 0`, con lo que el `0` recién
arreglado lo hacía fallar igual: **el botón «Guardar resultado» no guardaba nada**,
salvo que se tecleara un número de penales distinto de cero en los dos equipos.
Los penales ahora aceptan entero o `null`, que es como los tiene la siembra.

**Los tres formularios (4.4) fallaban por el contrato, no por el backend.** El botón de
abrir/cerrar una categoría, los dos formularios de creación de ronda y el de partido
mandaban algo que el backend no esperaba:

| Qué | Enviaba | Qué esperaba |
|---|---|---|
| Abrir/cerrar categoría | `PATCH` **sin cuerpo** | `{"estado":"abierta"}` o `{"estado":"cerrada"}` |
| Crear ronda (dos vistas) | omitía `orden` | `orden` obligatorio |
| Crear partido | `posicion: null` | `posicion` calculada por el backend |

`resultado()` y `fijarPostulacion()` sí aceptan el cuerpo vacío, y por eso
«Aprobar postulación» y «Fijar en el canal» no se tocaron. Un `PATCH` sin cuerpo solo es
legítimo si el backend lo acepta explícitamente. La posición de partido ahora se calcula
sola como `MAX(posicion)+1` dentro de la ronda, que es la regla que ya usaba la siembra.

**Editar categoría (4.5) es un endpoint que existía sin dueño.** `CategoriasVotoController::actualizar`
(`PATCH /api/torneos/{torneoId}/categorias-voto/{id}`) llevaba escrito y probado desde la
Etapa 1: acepta `nombre`, `tipo`, `modo_candidatos` y `orden`, con sus validaciones. Pero
`api.js` no tenía método que lo llamara y la tarjeta de la categoría solo ofrecía Abrir,
Cerrar y Eliminar. Se résumé el trabajo a un método y un formulario.

Dos decisiones que conviene no deshacer:

- **La clave no es editable.** `uq_torneo_categoria_clave` es por torneo y la clave se usa
  como identificador en otras partes. El backend no la acepta en el `PATCH` y el formulario
  lo avisa.
- **El `tipo` se manda siempre y puede dar 409.** El backend no deja cambiar el tipo si la
  categoría ya tiene candidatos o votos, porque los candidatos guardados quedarían con la
  columna equivocada. Es la respuesta correcta; si algún día se quiere permitir, hay que
  decidir antes qué pasa con esos candidatos.

> **Pendiente de decisión.** `resultado()` ya persiste `estado` cuando viene en el cuerpo
> (el selector de Estado lo mandaba y se ignoraba en silencio), pero poner `finalizado` a
> mano **sigue saltándose la propagación del ganador al cuadro** que hace
> `FinalizarPartido`. Ese hueco ya existía en `PATCH /partidos/{id}`, así que no es
> nuevo, pero sigue abierto: o se bloquea `finalizado` en `actualizar()` y se obliga a
> usar `PATCH /partidos/{id}/finalizar`, o se acepta y se documenta. Ver §0.

### Etapa 5 — Seguridad pendiente ❌ *sin empezar*

*No es una etapa que se pueda "terminar": mezcla código con decisiones de producto. Está
numerada al final solo para que los siete puntos que quedan abiertos tengan un sitio donde
leerse. El detalle de cada defecto está en el §3.*

| # | Ítem | Qué falta | Tipo | Decidido por |
|---|---|---|---|---|
| 5.1 | **S4** | Rate limiting o lockout en el login de admin | código | tú |
| 5.2 | **S5** | El logout de jugador no debe borrar la sesión del panel | código | tú |
| 5.3 | **S6** | CSRF, y `SameSite`/`Secure`/`HttpOnly` en la cookie | config + código | tú |
| 5.4 | **S7** | `/api/usuarios` pide sesión de jugador, no de admin | diseño | producto |
| 5.5 | **S8** | El login de jugador no tiene contraseña y **crea la cuenta** | **diseño** | producto |
| 5.6 | **S11** | El login de admin no renueva el id de sesión | código | tú |
| 5.7 | **S12** | `GET /api/test` es público | código | tú |

**5.6 (S11) y 5.7 (S12) no estaban en la lista S y aparecieron al escribir esta sección.**
Los dos se encontraron mirando el código, no leyendo:

- **S11, session fixation en el login de admin.** `AuthController` (jugador) llama a
  `session_regenerate_id(true)` en la línea 42, y **`AdminAuthController` no lo hace**: va
  directo de `session_start()` (`:43`) a escribir `$_SESSION['admin_id']` (`:44`). Si un
  atacante fija el id de sesión antes de que el admin entre, la sesión que queda viva es la
  suya. Es el más serio de los siete, y el más barato de arreglar: una línea.
- **S12, `GET /api/test`.** `TestController::index` hace `SELECT 1` y responde
  `{"mensaje":"API funcionando correctamente"}` **sin `requireAdminAPI()`**. No filtra
  datos, pero confirma por HTTP que la API está viva y que la BD responde. En el §2 no
  aparecía: es la ruta 94 y quedó fuera del conteo de 83.

**5.5 (S8) es la única que no debería tocarse sin una decisión de producto.** El código de
`AuthController` lo dice sin ambigüedad: una matrícula de 7 dígitos que no existe **crea la
cuenta** y autentica (`AuthController.php:30-33`, con el comentario *"login-o-registro
automático"*). Antes de cerrarlo hay que decidir si el portal público autentica así, porque
cambiarlo rompe el acceso de todos los jugadores que no tengan cuenta creada por el admin.

**Los puntos 5.1, 5.2, 5.3, 5.6 y 5.7 son código y no dependen de nadie.** Son los que se
pueden encadenar. Los 5.4 y 5.5 se quedan parados hasta que producto decida.

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
- [ ] Las 94 rutas tienen request en Postman y responden algo != 404

### Fase 3 — Documentar las incidencias aceptadas
*Sin tocar la BD, por decisión explícita.*

| # | Incidencia | Dónde documentar |
|---|---|---|
| 3.1 | `sql/schema.sql` contradice la BD real — es `bd_propuesta.sql` el vigente. **Marcar `schema.sql` como obsoleto o regenerarlo desde la BD** | encabezado del propio `schema.sql` |
| 3.2 | `migracion_admin.sql` nunca aplicada: faltan `posts`, `post_reacciones`, `motivo_rechazo`. **Comunidad y el motivo de rechazo están caídos** | nota en `migracion_admin.sql` |
| 3.3 | Los 51 usuarios son `rol='usuario'`; 6 endpoints dan 403. **Decisión pendiente:** poblar `'jugador'` o relajar `requireJugador()` | aquí + `authorization.php:40` |
| 3.4 | Sin cobertura de pruebas en 83 de 94 rutas, sin runner ni CI | aquí |
| 3.5 | Sin subida de archivos (F5) — logos y avatares son strings | aquí |

### Fase 4 — Reconstruir el panel sobre la API

| # | Acción |
|---|---|
| 4.1 | `admin/assets/js/api.js` — cliente base con `credentials: 'include'` y envelope `{exito, data, errores}` |
| 4.2 | Login del panel vía `API.post('/admin/login')`; eliminar la autenticación duplicada |
| 4.3 | Migrar vistas de menor a mayor riesgo: **Inicio** (read-only) → **Participantes** → **Postulaciones** → **Votaciones** → **Torneo** → **Resultados** |
| 4.4 | Migrar las 13 acciones de escritura con endpoint equivalente. **Comunidad queda fuera** — no hay API y no se va a construir |
| 4.5 | Habilitar lo hoy inalcanzable: finalizar partido (propaga ganador), CRUD de participantes, **convocatorias (84 filas ya en BD)**, **porteros (14 filas)**, editar/borrar rondas y partidos, estadísticas — *hecho en la [Etapa 4](#etapa-4--poner-en-pantalla-los-datos-que-ya-estaban) solo para convocatorias y porteros; el resto sigue pendiente* |

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

**Ya no hay conflicto de claves.** La sesión vive en una sola:

| | Clave | Forma | Quién la escribe |
|---|---|---|---|
| Panel y API | `$_SESSION['admin_id']` | int | `AdminAuthController` (login por API) |

`admin/controllers/login.php` se retiró en `439859d`, así que ya no hay un segundo
escritor. Ver §1.9.

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

## 7. Mapa de las 20 acciones del panel → endpoint

Mapa del **código actual**, no de la migración. Cada fila se verificó contra
`admin/assets/js/api.js` (el método que llama) y `admin/assets/js/views.js` (el
`data-accion` que la dispara). Las rutas son relativas a `/api`.

> La versión anterior de esta tabla listaba 15 acciones y apuntaba a
> `admin/controllers/*.php`. Esos controllers ya no existen: están en `_retired/admin/`
> y la migración se hizo en la Etapa 2. La tabla de abajo es el contrato vigente, y tiene
> cinco filas más porque la Etapa 2 y la 3.2 añadieron acciones que el mapa viejo no
> recogía.

**Postulaciones**

| Acción (`data-accion`) | Método API | Verbo + ruta |
|---|---|---|
| `aprobar` | `aprobarPostulacion` | `PATCH /torneos/{id}/equipos/{eqId}/aprobar` |
| `rechazar` | `rechazarPostulacion` | `PATCH /torneos/{id}/equipos/{eqId}/rechazar` ⚠️ **no manda `motivo_rechazo`** (F2) |

**Torneo y rondas**

| Acción | Método API | Verbo + ruta |
|---|---|---|
| `crear-ronda` | `crearRonda` | `POST /torneos/{id}/rondas` |
| `crear-partido` | `crearPartido` | `POST /partidos` |

**Resultados y eventos**

| Acción | Método API | Verbo + ruta |
|---|---|---|
| `guardar-resultado` | `guardarResultado` | `PATCH /partidos/{id}/resultado` ⚠️ ver abajo |
| `registrar-evento` | `registrarEvento` | `POST /partidos/{id}/eventos` |

**Convocatorias**

| Acción | Método API | Verbo + ruta |
|---|---|---|
| `convocar-jugador` | `convocarJugador` | `POST /partidos/{id}/convocados` |
| `alternar-titular` | `actualizarConvocado` | `PATCH /partidos/{id}/convocados/{jugadorId}` |
| `quitar-convocado` | `eliminarConvocado` | `DELETE /partidos/{id}/convocados/{jugadorId}` |

**Estadísticas de portero**

| Acción | Método API | Verbo + ruta |
|---|---|---|
| `registrar-portero` | `registrarEstadisticaPortero` | `POST /partidos/{id}/porteros` |
| `actualizar-portero` | `actualizarEstadisticaPortero` | `PATCH /partidos/{id}/porteros/{jugadorId}` |
| `quitar-portero` | `eliminarEstadisticaPortero` | `DELETE /partidos/{id}/porteros/{jugadorId}` |

**Votaciones** — las 6 de categorías y candidatos

| Acción | Método API | Verbo + ruta |
|---|---|---|
| `crear-categoria` | `crearCategoriaVoto` | `POST /torneos/{id}/categorias-voto` |
| `editar-categoria` | `actualizarCategoriaVoto` | `PATCH /torneos/{id}/categorias-voto/{catId}` |
| `toggle-categoria` | `cambiarEstadoCategoria` | `PATCH /torneos/{id}/categorias-voto/{catId}/estado` |
| `eliminar-categoria` | `eliminarCategoria` | `DELETE /torneos/{id}/categorias-voto/{catId}` |
| `agregar-candidato` | `agregarCandidato` | `POST /torneos/{id}/categorias-voto/{catId}/candidatos` |
| `quitar-candidato` | `excluirCandidato` | `DELETE /torneos/{id}/categorias-voto/{catId}/candidatos/{ajusteId}` |

**Comunidad**

| Acción | Método API | Verbo + ruta |
|---|---|---|
| *(form, sin `data-accion`)* | `crearPost` | `POST /admin/posts` |
| `eliminar-post` | `eliminarPost` | `DELETE /admin/posts/{id}` |
| `toggle-fijado` | `toggleFijadoPost` | `PATCH /admin/posts/{id}/fijado` |

> **`crearPost` no tiene `data-accion`** porque lo dispara el `submit` del formulario
> (`views.js:1425`), no un botón. Cuenta como acción de panel pero no aparece en el
> selector de `data-accion`, y por eso el conteo de 20 sale de 21 llamadas de escritura.

> **`excluirCandidato` usa `DELETE`, no `POST` con `ajuste:'excluir'`.** El mapa viejo
> decía lo segundo, copiado del panel legacy. El `api.js` actual (`:289-293`) borra la fila
> del ajuste. La API no tiene endpoint para "excluir vía POST" en esta versión.

> **`guardar-resultado` y el hueco de `finalizado`.** El panel manda
> `goles_local, goles_visitante, penales_local, penales_visitante, ganador_id y estado`.
> `resultado()` persiste los goles, los penales y `estado`, pero poner `finalizado` a mano
> **salta la propagación del ganador al cuadro** que hace `FinalizarPartido`. Es el
> pendiente de decisión de §5, Etapa 4, y no lo arregla esta tabla: hay que decidir si se
> bloquea `finalizado` en `actualizar()` o si se acepta el salto. Ver el aviso al final de
> la Etapa 4.

**Cobertura.** Estas 20 acciones cubren 20 endpoints de escritura de los 94. Los que
quedan fuera (lecturas, conteos, listados) los consume el panel sin acción de usuario, y
`API.estadisticas()`, `API.detalleTorneo()` y compañía no aparecen en ningún `data-accion`.


---

## 8. Cobertura por vista

Lecturas y escrituras ya migradas sobre la API, y lo que queda en cada pantalla. Los
números entre paréntesis remiten a la sub-etapa del §5.

| Vista | Lecturas | Escrituras | Pendiente |
|---|---|---|---|
| **Inicio** | ✅ torneos, partidos, postulaciones, estadísticas | — | — |
| **Torneo y Rondas** | ✅ torneos, rondas, bracket, equipos | ⚠️ crear ronda, crear partido | editar/borrar ronda y partido (`api.php:86-87`, ya existe), finalizar partido, bracket de `BracketController` |
| **Postulaciones** | ✅ `torneos/{id}/equipos` | ⚠️ aprobar, rechazar | retirar equipo (**4.7**), motivo de rechazo (p7) |
| **Participantes** | ✅ `admin/usuarios` | — | toggles de `estado` y `jugador_activo` (**4.8**, endpoints ya existen) |
| **Resultados** | ✅ partidos, eventos, estadísticas, **convocatorias** (4.1), **porteros** (4.2) | ⚠️ resultado, registrar evento, **convocar**, **stats de portero** | editar/borrar evento (**4.6**) |
| **Votaciones** | ✅ categorías, candidatos, jugadores | ⚠️ 6 acciones | — (editar categoría se hizo en **4.5**) |
| **Comunidad** | ✅ `admin/posts` | ⚠️ crear, fijar, eliminar | reacciones (**4.9**): sin endpoint, y con dos contadores de «likes» en conflicto |

✅ migrado sobre la API · ⚠️ migrado y **sin probar en navegador** · ❌ fuera de alcance

Lo marcado en **negrita** son las cuatro sub-etapas abiertas de la Etapa 4. Tres solo
necesitan el control en `views.js`; 4.9 necesita endpoint. Ninguna toca el esquema. El
detalle está en la [tabla de la Etapa 4](#etapa-4--poner-en-pantalla-los-datos-que-ya-estaban).

---

## 9. Resumen de etapas

| Etapa | Alcance | Estado | Commits | Entregado |
|---|---|---|---|---|
| **0** | Admin `id=3` operativo, respaldo, auditoría AFIHub | ✅ | — | Acceso restaurado |
| **1** | API segura y fiable: S1, S2, S3, F1, F3, F7, §1.9 | ✅ salvo 1.6 | `241d5da` | 94 rutas usables, sesión unificada |
| **2** | Conectar panel a la API | ⚠️ falta navegador | `20462c4`, `c3885fc` | 6 de 7 vistas sobre el backend |
| **3** | Desconectar el legacy | ⚠️ falta navegador | `a1bad5f`, `9b586ef`, `9ae32fb`, `be57ab1`, `439859d` | Panel legacy inaccesible |
| **4** | Poner en pantalla los datos que ya estaban | ⚠️ 5 de 9 | `99d308b`, `0b0cd7b`, `2ee5eba`, `91579e6`, `d151597` | 84 convocatorias, 14 de portero, editar categoría, y 3 bugs de contrato |
| **5** | Seguridad pendiente: S4, S5, S6, S7, S8, **S11**, **S12** | ⛔ sin empezar | — | — |

**Qué significa cada estado.** ✅ está hecho y verificado por HTTP. ⚠️ está hecho pero
falta probarlo en un navegador, o la etapa quedó a medias. ❌ no se ha empezado.
⛔ no se puede empezar sin una decisión de otro equipo.

**Etapas 0 a 3: cerradas.** No queda código pendiente en ellas, solo la prueba de navegador
(p1). **Etapa 4: lo único con código pendiente**, cuatro pantallas que ya tienen endpoint
(4.6–4.9). **Etapa 5: no existe todavía**, son los tres endurecimientos de seguridad y las
decisiones de producto listadas abajo; se numeró al final para que quede claro que es
posterior, no es trabajo que ya estuviera empezado.

**Corregidos en la Etapa 1:** S1 (toma de control de admin) · S2 (tests por HTTP) · S3
(`APP_DEBUG`) · F1 (filtro de partidos, + `torneo_id`) · F3 (405 con `Allow`) · F7 (basePath) ·
§1.9 (sesión compartida entre panel y API).

**Lo que la Etapa 1 desbloquea:** la Etapa 2 es posible por primera vez. Autenticar por la API
ahora abre el panel, y entrar por el panel habilita los 37 endpoints protegidos. Antes
ninguna de las dos vías reconocía a la otra.

**Lo que sigue bloqueado, y por qué:**

| Bloqueo | Causa | Quién decide |
|---|---|---|
| **F2** `motivo_rechazo` | No es un bloqueo de esquema: la columna ya existe. Es código sin escribir | tú (p7) |
| **§1.6** rol `jugador` | Whitelist de `Origin` en AFIHub | equipo de AFIhub |
| **§1.7** re-validación del rol | Diseño de producto | producto |
| **§1.8** matrícula como credencial | Diseño de producto | producto |

**Riesgo por etapa:** 0 nula · 1 media (toca auth) · 2 alta (reescribe el panel) ·
3 baja (reversible) · 4 media (solo habilita lo que ya existía, pero toca
validación compartida por varias vistas).

### Bloqueos que requieren decisión de otro equipo

| Bloqueo | Afecta | Quién decide |
|---|---|---|
| **§1.6** whitelist de `Origin` en AFIHub | 6 endpoints en 403, ningún jugador | Equipo de AFIHub |
| **§1.7** re-validación del rol | consistencia de permisos | Producto |
| **§1.8** matrícula como credencial única | modelo de identidad completo | Producto |
| **1.2** vía para crear admins sin sesión | primer admin tras limpiar la BD | Producto |

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

---

## 10. Tabla final de etapas

> Esta es la tabla de referencia. Si solo vas a leer una cosa del documento, lee esta.

### 10.1 Las etapas y su estado

Estado: ✅ hecho y verificado · ⚠️ a medias · ❌ sin empezar · ⛔ bloqueado por otro equipo

| Etapa | Alcance | Estado | Commit | Verificado por |
|---|---|---|---|---|
| **0** | Rescate de acceso: admin `id=3`, respaldo, auditoría AFIHub | ✅ | *sin commit* (solo datos) | curl |
| **1** | API segura y fiable; sesión unificada | ✅ salvo 1.6 | `241d5da` | HTTP |
| **2** | Conectar el panel a la API | ⚠️ falta navegador | `20462c4` · `c3885fc` | HTTP |
| **3** | Desconectar el legacy | ⚠️ falta navegador | `a1bad5f` · `9b586ef` · `9ae32fb` · `be57ab1` · `439859d` | HTTP · `php -l` |
| **4** | Poner en pantalla los datos que ya estaban | ⚠️ 5 de 9 | `99d308b` · `0b0cd7b` · `2ee5eba` · `91579e6` · `d151597` | HTTP |
| **5** | Seguridad pendiente: S4, S5, S6, S7, S8, **S11**, **S12** | ❌ sin empezar | — | — |

**Total: 4 etapas cerradas, 1 a medias (Etapa 4), 1 sin empezar (Etapa 5).**

Lo que impide el ✅ de las etapas 2 y 3 no es código: es que **nadie ha abierto el panel en
un navegador**. Los contratos se validaron por HTTP, leyendo los controladores. Es el
pendiente p1.

### 10.2 Commits en orden cronológico

Para reconstruir el trabajo en orden, o para saber qué commit revisar:

| # | Commit | Qué hizo |
|---|---|---|
| 1 | `241d5da` | Etapa 1: API segura y fiable, y sesión compartida con el panel |
| 2 | `20462c4` | Etapa 2.1–2.5: cliente base, login por API, Inicio sobre el backend |
| 3 | `c3885fc` | Etapa 2.6–2.10: las cinco vistas restantes sobre la API |
| 4 | `a1bad5f` | Los scripts seed ya no escriben en una base fija |
| 5 | `9b586ef` | Etapa 3.1: la sesión del panel se apoya solo en `admin_id` |
| 6 | `9ae32fb` | Etapa 3.2: la API de Comunidad, y que solo el admin pueda publicar |
| 7 | `be57ab1` | Etapa 3.3: la vista de Comunidad deja de hacer SQL |
| 8 | `439859d` | Etapa 3.4–3.7: legacy a `_retired/`, shim eliminado, credenciales fuera del repo |
| 9 | `99d308b` | Etapa 4.1: las 84 convocatorias que ya estaban en la base |
| 10 | `0b0cd7b` | Los rangos que admiten 0 rechazaban el 0 |
| 11 | `2ee5eba` | Etapa 4.2: las 14 líneas de estadísticas de portero |
| 12 | `91579e6` | Corrige tres formularios que mandaban un cuerpo que el backend no espera |
| 13 | `d151597` | Deja editar la categoría de votación desde el panel |

Los commits 1–3 son del 2026-09-29; del 4 al 13, del 2026-09-30. Los de documentación
(`d77db24`, `5bb3012`, `dce0eb4`, `3680a21`) no aparecen porque no cambian el estado del
sistema, solo este archivo.

> **El número de sub-etapa no es el orden de los commits.** El bug del `0` (`0b0cd7b`) es la
> sub-etapa 4.3, pero se subió **antes** que los porteros (4.2, `2ee5eba`), porque salió al
> probar 4.2 y se arreglarlo no podía esperar. Para reconstruir el trabajo usa esta tabla, que
> sí va en orden cronológico; para localizar una sub-etapa, usa el §0.

### 10.3 Equivalencia: las Fases originales y las Etapas actuales

Las **Fases** son el plan original, superado. Se leen al final del §5 dentro de un
`<details>`. Esta tabla evita la confusión más probable del documento, que es que
**el número no significa lo mismo en las dos numeraciones**:

| Fase original | Qué era | En qué Etapa acabó | Estado de eso |
|---|---|---|---|
| **Fase 0** | Cerrar vulnerabilidades (S1, S2, S3, S9) | **Etapa 1**, salvo S9 que fue a la 3 | ✅ hecho, menos rotar la contraseña (p6) |
| **Fase 1** | Desconectar el panel (`git mv admin _retired/`) | **Etapa 3**, pero más estrecha | ✅ los 6 controllers legacy sí se movieron; `admin/` no, a propósito |
| **Fase 2** | Hacer la API explotable (F1, F2, F7, S5, Postman, S8) | partida: F1 y F7 a la **Etapa 1**; F2, S5 y Postman siguen abiertos | ⚠️ partly |
| **Fase 3** | Documentar las incidencias aceptadas | No fue una etapa: es el §2, §3 y §4 de este mismo documento | ✅ documentado |
| **Fase 4** | Reconstruir el panel sobre la API | partida: la migración de vistas a la **Etapa 2**, lo inalcanzable a la **Etapa 4** | ⚠️ vistas hechas, 4 de 9 sin hacer |

> ⚠️ **Fase 1 ≠ Etapa 1.** La Fase 1 era *desconectar el panel*; la Etapa 1 es *asegurar la
> API*. Son objetivos opuestos, y esa fue justo la razón del reordenamiento (§9.1). Si
> buscabas algo por un número, la tabla de arriba es la que dice dónde está.
