# PASSBALLCUP — Requisitos del panel de administración

## Objetivo

Registrar qué partes de la simulación descrita en
[implementacion.md](../../sql/implementacion.md) se pueden realizar actualmente
desde el panel, cuáles solo tienen endpoints y cuáles están bloqueadas o no
pertenecen a una acción administrativa.

Esta validación es estática: contrasta la interfaz, el cliente JavaScript, las
rutas y los controladores de `backend`. No se ejecutaron operaciones contra una
base de datos.

### Estados

- 🟢 **Disponible:** la acción está expuesta en el panel y tiene endpoint.
- 🟡 **Parcial:** hay parte de la interfaz o del endpoint, pero requiere otra
  acción, datos previos o trabajo manual.
- 🔴 **No disponible:** no se puede completar desde el panel actual.
- 🔵 **Fuera del panel:** es una acción del usuario o una consulta para otro
  consumidor; no debería confundirse con una herramienta administrativa.

## Resultado general

La simulación **no se puede preparar ni validar de principio a fin usando solo
el panel actual**. El panel sí puede crear rondas y partidos, cargar resultados,
convocatorias y estadísticas de porteros, y configurar categorías de votación.
Pero no permite crear y organizar el torneo, los usuarios, los equipos ni sus
membresías. Además:

- El rol de jugador definido en la simulación no coincide con el que exigen los
  endpoints de membresías.
- Las altas y bajas de miembros tienen una comprobación de sesión que puede
  rechazar a un administrador autenticado.
- No se pueden registrar asistencias desde el formulario de eventos.
- El panel no comprueba la consistencia de los goles con los eventos ni los
  conteos de la simulación.

## 1. Datos generales del torneo

**Requisito de [implementacion.md](../../sql/implementacion.md):** crear
PASSBALLCUP como torneo de eliminación directa, con estado final `finalizado`.

**Estado: 🔴 No disponible desde el panel.**

- [partials/torneo.php](../../admin/partials/torneo.php) solo contiene un
  selector de torneo y el contenedor para rondas y partidos.
- [views.js](../../admin/assets/js/views.js) permite crear rondas y partidos,
  pero no muestra formularios para crear, editar o eliminar un torneo.
- [api.js](../../admin/assets/js/api.js) tampoco expone métodos de CRUD de
  torneos.
- Las rutas `POST /api/torneos`, `PATCH /api/torneos/{id}` y
  `DELETE /api/torneos/{id}` sí existen. [TorneosController.php](../controllers/TorneosController.php)
  exige sesión administrativa para crear, actualizar y eliminar. El tipo
  aceptado actualmente es `eliminacion_directa`; la actualización admite los
  estados `programado`, `en_curso`, `finalizado` y `cancelado`.
- El borrado puede responder `409` cuando hay rondas o inscripciones asociadas.

**Resultado:** los endpoints cubren el CRUD, pero no se puede cumplir desde el
dashboard hasta conectar esas operaciones al partial.

## 2. Administrador

**Requisito de [implementacion.md](../../sql/implementacion.md):** contar con
un administrador activo que apruebe los ocho equipos y quede registrado en
`torneo_equipos.aprobado_por`.

**Estado: 🟡 Parcial.**

- Con un administrador ya autenticado, la acción de aprobar una inscripción
  guarda el ID de ese administrador en `aprobado_por`.
- Hay CRUD de administradores en la API, pero no una sección para
  administradores en [dashboard.php](../../admin/dashboard.php).
- Importante: en [AdministradoresController.php](../controllers/AdministradoresController.php),
  el `requireAdminAPI()` de `crear()` está comentado. No debe considerarse un
  flujo de alta administrativa protegido hasta corregir y validar esa
  autorización.
- El panel depende de una sesión de administrador existente; no hay un flujo de
  bootstrap de la primera cuenta en el dashboard.

## 3. Usuarios / jugadores

**Requisito de [implementacion.md](../../sql/implementacion.md):** crear 48
jugadores con matrícula, estado activo, `jugador_activo = true` y el rol
especificado en el documento.

**Estado: 🔴 No disponible desde el panel.**

- [partials/participantes.php](../../admin/partials/participantes.php) presenta
  una tabla de lectura; no tiene formularios para crear o editar usuarios.
- [API.participantes](../../admin/assets/js/api.js) solicita
  `/api/admin/usuarios?rol=usuario&orden=asc`. No carga una pestaña separada de
  jugadores.
- [AdminUsuariosController.php](../controllers/AdminUsuariosController.php)
  permite listar y filtrar usuarios, pero no crearlos ni modificar sus datos.
- No hay una ruta `POST /api/usuarios` en
  [routes/api.php](../routes/api.php). La ruta `PATCH /api/usuarios/{id}` solo
  actualiza el avatar; las rutas adicionales cambian `estado` y
  `jugador_activo`, no el nombre o la matrícula.

### Incompatibilidad de rol que bloquea las plantillas

[implementacion.md](../../sql/implementacion.md) especifica `rol = usuario`
para sus 48 jugadores. En cambio,
[EquipoMiembrosController.php](../controllers/EquipoMiembrosController.php)
valida que el miembro agregado tenga `rol = jugador`; las operaciones de
capitán también exigen un jugador activo. La especificación de datos y las
reglas actuales de la API no coinciden. Hay que resolver esa discrepancia antes
de cargar las plantillas; este documento no cambia silenciosamente el rol
pedido por la simulación.

## 4. Equipos

**Requisito de [implementacion.md](../../sql/implementacion.md):** crear ocho
equipos activos con capitán y permitir asociarles su logo y datos.

**Estado: 🔴 No disponible como flujo administrativo completo.**

- No hay módulo de equipos en el menú ni un partial administrativo para
  gestionarlos.
- `POST /api/equipos` existe, pero
  [EquiposController.php](../controllers/EquiposController.php) lo implementa
  como un flujo iniciado por un capitán; crea el equipo en estado `pendiente`.
  No es un alta de equipo por parte del administrador.
- `PATCH /api/equipos/{id}` permite cambiar nombre y logo con sesión de
  administrador o capitán. El logo se recibe como cadena/URL; la actualización
  no acepta una subida multipart.
- `PATCH /api/equipos/{id}/estado` permite al administrador cambiar estado.
- No encontré un endpoint de administración para asignar o cambiar
  `capitan_id`. El `capitan_id` se establece al crear el equipo mediante el
  flujo de capitán.

## 5. Plantillas de jugadores

**Requisito de [implementacion.md](../../sql/implementacion.md):** registrar
las seis plantillas con los jugadores indicados para cada uno de los ocho
equipos.

**Estado: 🔴 No disponible desde el panel.**

El panel no tiene una pantalla para crear jugadores ni para administrar
miembros permanentes de un equipo. Las opciones de participantes de las
pantallas de resultados y votaciones dependen de que esas cuentas y membresías
ya existan. Los nombres y la distribución exacta permanecen en la sección 5
del documento fuente.

## 6. Jugadores obligatorios

**Requisito de [implementacion.md](../../sql/implementacion.md):** que Kylian
Mbappé pertenezca a Real Madrid y André-Pierre Gignac pertenezca a Tigres, con
las estadísticas indicadas en las secciones posteriores.

**Estado: 🟡 Parcial, condicionado a datos existentes.**

El módulo de Resultados puede registrar goles y convocatorias, pero no puede
crear esas cuentas ni asignarlas inicialmente a esos equipos. Además, la
incompatibilidad de roles descrita en la sección 3 puede impedir la creación
de las membresías mediante la API actual.

## 7. Membresías

**Requisito de [implementacion.md](../../sql/implementacion.md):** 48
membresías activas, seis por equipo, sin que un jugador pertenezca activamente
a dos equipos.

**Estado: 🔴 No disponible desde el panel para administrar equipos.**

- Hay rutas para listar, agregar, dar de baja y eliminar membresías.
- [EquipoMiembrosController.php](../controllers/EquipoMiembrosController.php)
  valida que el jugador esté activo, tenga rol `jugador`, no pertenezca ya a
  otro equipo activo y que el equipo no supere el máximo de 12 integrantes.
- No hay módulo administrativo para realizar esas operaciones.
- Además, `agregar()`, `marcarSalida()` y `eliminar()` llaman a
  `requireAuthAPI()` antes de permitir la rama de administrador. Esa función
  requiere `user_id`; la sesión normal del administrador usa `admin_id`. Con
  una sesión que solo contiene `admin_id`, la solicitud puede terminar en
  `401` antes de llegar a la autorización administrativa.

## 8. Inscripción al torneo

**Requisito de [implementacion.md](../../sql/implementacion.md):** ocho
inscripciones aprobadas y `aprobado_por` asociado al administrador.

**Estado: 🟡 Parcial.**

- La pantalla de Postulaciones permite aprobar solicitudes pendientes, y el
  controlador registra quién las aprobó.
- La ruta `POST /api/torneos/{torneoId}/equipos` no es una inscripción manual
  de administrador: la acción `solicitar()` exige `requireCapitan()` y crea
  una solicitud pendiente.
- No existe una ruta administrativa específica para añadir directamente un
  equipo al torneo. Sin solicitudes previas, el panel no puede completar el
  alta de los ocho equipos.

## 9. Casos adicionales para probar estados

**Requisito de [implementacion.md](../../sql/implementacion.md):** probar
inscripciones pendientes, aprobadas, rechazadas y retiradas sin alterar el
cuadro oficial.

**Estado: 🟡 Parcial.**

- Aprobar y rechazar solicitudes pendientes está conectado al dashboard.
- La ruta de retirar una inscripción aprobada permite operación administrativa,
  pero no hay botón de retirar en la vista.
- El panel solicita todas las inscripciones y además muestra una sección
  `Procesadas`; no muestra solamente pendientes. La API ya soporta
  `?estado=pendiente`, pero [API.postulaciones](../../admin/assets/js/api.js)
  no envía ese filtro.
- La interfaz de rechazo no envía un motivo. El controlador permite recibirlo,
  pero actualmente la vista lo deja vacío.

## 10. Rondas

**Requisito de [implementacion.md](../../sql/implementacion.md):** crear tres
rondas, con los nombres y órdenes especificados.

**Estado: 🟢 Disponible para crear; 🟡 para corregir o administrar.**

En `Torneo y Rondas` se pueden crear rondas indicando nombre y orden. La API
también tiene actualización y eliminación, pero el dashboard no ofrece esas
acciones. La eliminación del backend devuelve conflicto si la ronda ya tiene
partidos.

## 11. Partidos

**Requisito de [implementacion.md](../../sql/implementacion.md):** crear siete
partidos, distribuidos entre las tres rondas.

**Estado: 🟡 Parcial.**

- El panel permite crear partidos y asignar equipos locales y visitantes.
- El selector se llena con equipos activos en general, no solo con los equipos
  aprobados para ese torneo.
- La pantalla envía equipos concretos; no ofrece un flujo de cuadro que conecte
  los ganadores con los partidos de la siguiente ronda.
- Hay endpoints de edición y eliminación, pero no controles correspondientes
  en la interfaz.

## 12. Cuartos de final

**Datos requeridos por [implementacion.md](../../sql/implementacion.md):**

| Partido | Resultado | Ganador | Requisito individual |
|---|---:|---|---|
| Real Madrid vs PSG | 5–2 | Real Madrid | Mbappé anota 3 goles |
| Barcelona vs Borussia Dortmund | 3–1 | Barcelona | — |
| Bayern Munich vs Everton | 1–2 | Everton | — |
| Monterrey vs Tigres | 2–3 | Tigres | Gignac participa y registra una estadística ofensiva |

**Estado: 🟡 Parcial.**

Con equipos y miembros ya creados, Resultados permite ingresar marcador,
ganador, estado y eventos. El ganador debe ser uno de los dos equipos del
partido, validación que también hace [PartidosController.php](../controllers/PartidosController.php).
No existe una validación que compare automáticamente el total de eventos con
el marcador.

## 13. Semifinales

**Datos requeridos por [implementacion.md](../../sql/implementacion.md):**

| Partido | Resultado | Ganador | Requisito individual |
|---|---:|---|---|
| Real Madrid vs Barcelona | 4–2 | Real Madrid | Mbappé anota 4 goles |
| Tigres vs Everton | 2–1 | Tigres | Gignac participa; Nahuel Guzmán registra estadísticas de portero |

**Estado: 🟡 Parcial.**

La captura de resultados, eventos y estadísticas de porteros existe, pero
depende de que equipos, membresías y convocatorias puedan cargarse primero.

## 14. Final

**Datos requeridos por [implementacion.md](../../sql/implementacion.md):**

- Real Madrid 2–3 Tigres; ganador y campeón: Tigres.
- Mbappé debe anotar tres goles en la final.
- Mbappé debe terminar con diez goles en el torneo.

**Estado: 🟡 Parcial.**

El marcador, ganador y eventos pueden capturarse manualmente en el panel si
los datos previos existen. No hay verificador que compruebe el total de diez
goles al finalizar el torneo.

## 15. Convocados

**Requisito de [implementacion.md](../../sql/implementacion.md):** convocar a
los jugadores de cada partido; la recomendación es 12 por partido y 84 en
total.

**Estado: 🟡 Parcial, condicionado a membresías previas.**

Resultados permite agregar y quitar convocados, y el backend valida que el
jugador pertenezca activamente a uno de los equipos del partido. Sin equipos y
membresías, no se pueden crear las 84 convocatorias desde el dashboard.

## 16. Titulares y banca

**Requisito de [implementacion.md](../../sql/implementacion.md):** cinco
titulares y una banca por equipo, con rotación durante el torneo.

**Estado: 🟢 Disponible para las convocatorias existentes.**

El panel permite convocar como titular o banca y alternar después la
titularidad. No valida que cada equipo mantenga exactamente cinco titulares y
una banca.

## 17. Posiciones

**Requisito de [implementacion.md](../../sql/implementacion.md):** asignar
portero, defensa, mediocampo o delantero a cada convocatoria.

**Estado: 🟢 Disponible al convocar; 🟡 para editar.**

El formulario de convocatoria permite elegir las cuatro posiciones. El
controlador soporta actualizar posición, pero la interfaz solo expone el cambio
de titular/banca, no la edición de posición de una convocatoria ya existente.

## 18. Eventos de partido

**Requisito de [implementacion.md](../../sql/implementacion.md):** registrar
gol, autogol, penal anotado, tarjeta amarilla y tarjeta roja.

**Estado: 🟢 Disponible para registrar los cinco tipos.**

El formulario de Resultados ofrece esos tipos. El controlador requiere que el
jugador esté convocado con el equipo indicado; la convocatoria debe existir
antes de registrar el evento.

## 19. Goles

**Requisito de [implementacion.md](../../sql/implementacion.md):** registrar
goles individuales y hacer coincidir los eventos con el marcador.

**Estado: 🟡 Parcial.**

El marcador y los eventos se guardan por operaciones distintas. El panel deja
ingresar ambos, pero no compara los conteos ni avisa cuando los eventos no
coinciden con `goles_local` y `goles_visitante`. La coherencia depende de
revisión manual.

## 20. Autogol

**Requisito de [implementacion.md](../../sql/implementacion.md):** registrar al
menos un autogol asociado al jugador y mantener un resultado coherente.

**Estado: 🟡 Parcial.**

La interfaz permite seleccionar el tipo `autogol`; no valida que el efecto del
evento sobre el marcador sea coherente. Aplican también las limitaciones de
consistencia descritas en la sección 19.

## 21. Penal anotado

**Requisito de [implementacion.md](../../sql/implementacion.md):** registrar
penales anotados asociados a un jugador.

**Estado: 🟡 Parcial.**

Se puede registrar el tipo de evento y capturar el marcador y los campos de
penales. La relación entre esos datos no se verifica automáticamente.

## 22. Asistencias

**Requisito de [implementacion.md](../../sql/implementacion.md):** registrar
varias asistencias y permitir consultar una tabla de asistencias.

**Estado: 🔴 No disponible desde el formulario administrativo.**

El controlador de eventos admite `asistencia_jugador_id`, pero el formulario y
el payload de `registrar-evento` en [views.js](../../admin/assets/js/views.js)
no ofrecen ni envían ese campo. Por ello, la asistencia de Gignac y los datos
necesarios para la tabla no se pueden cargar desde la pantalla actual.

## 23. Tarjetas amarillas

**Requisito de [implementacion.md](../../sql/implementacion.md):** registrar
tarjetas amarillas de distintos jugadores.

**Estado: 🟢 Disponible para registrar; 🟡 para consultar desde el panel.**

El formulario admite el tipo `tarjeta_amarilla`. La vista muestra eventos del
partido, pero no ofrece un reporte de tarjetas por jugador y torneo.

## 24. Tarjeta roja

**Requisito de [implementacion.md](../../sql/implementacion.md):** registrar
una tarjeta roja de un jugador convocado.

**Estado: 🟢 Disponible para registrar.**

El formulario admite `tarjeta_roja`; la API valida que el jugador esté
convocado con el equipo indicado.

## 25. Estadísticas de porteros

**Requisito de [implementacion.md](../../sql/implementacion.md):** registrar
estadísticas de portero en los partidos.

**Estado: 🟢 Disponible, condicionado a una convocatoria en posición portero.**

Resultados permite registrar, editar y quitar atajadas y goles recibidos.
El controlador rechaza el alta si el jugador no está convocado en la posición
`portero`.

## 26. Mejor portero del torneo

**Requisito de [implementacion.md](../../sql/implementacion.md):** registrar
para Nahuel Guzmán los valores por partido y totalizar 20 atajadas y cinco goles
recibidos.

**Estado: 🟡 Parcial.**

Se pueden ingresar los valores individuales indicados en la fuente. No hay una
pantalla administrativa de agregado por torneo ni una validación que confirme
automáticamente los totales.

## 27. Mbappé — estadísticas

**Requisito de [implementacion.md](../../sql/implementacion.md):** convocarlo
en los tres partidos de Real Madrid y registrar 3, 4 y 3 goles.

**Estado: 🟡 Parcial, condicionado a jugador, membresía y convocatoria.**

El panel permite ingresar esos eventos, pero no puede preparar por sí solo la
cuenta y membresía; tampoco valida que el total del torneo sea diez.

## 28. Gignac — estadísticas

**Requisito de [implementacion.md](../../sql/implementacion.md):** convocarlo
en los tres partidos de Tigres y registrar goles, tarjeta y al menos una
asistencia.

**Estado: 🟡 Parcial.**

Goles, tarjetas y convocatorias se pueden capturar si los datos previos
existen. La asistencia está bloqueada desde la interfaz por la limitación de la
sección 22.

## 29. Ganador del partido

**Requisito de [implementacion.md](../../sql/implementacion.md):** establecer
el ganador de cada partido y limitarlo a los dos participantes.

**Estado: 🟢 Disponible.**

El formulario lista los dos equipos del partido y el backend rechaza un ganador
que no sea local ni visitante.

## 30. Estado de partidos

**Requisito de [implementacion.md](../../sql/implementacion.md):** dejar los
siete partidos históricos en estado `finalizado`.

**Estado: 🟢 Disponible para guardar el estado.**

El formulario de resultado incluye el estado y
[PartidosController::resultado](../controllers/PartidosController.php) lo
persiste. Hay además un endpoint separado `/finalizar`, que actualiza partidos
dependientes con el ganador, pero el panel no lo invoca. La simulación puede
cargar manualmente los siete partidos explícitos de la fuente, pero no usar esa
pantalla para propagar automáticamente los ganadores por el cuadro.

## 31. Cuadro final esperado

**Requisito de [implementacion.md](../../sql/implementacion.md):** que los
resultados permitan reconstruir cuartos, semifinales y final, con Tigres como
campeón.

**Estado: 🟡 Parcial.**

El panel puede crear rondas y partidos con equipos concretos y guardar sus
resultados. Existe una ruta de bracket en la API, pero la vista administrativa
no la consume ni muestra un cuadro visual. La reconstrucción y comprobación
final requiere consultar los datos por otra vía.

## 32. Validaciones de la simulación

**Requisito de [implementacion.md](../../sql/implementacion.md):** comprobar
conteos, membresías activas únicas, inscripciones, partidos, convocados,
resultados, estadísticas y votaciones.

**Estado: 🔴 No hay una validación integral en el panel.**

- Inicio muestra algunos conteos, pero no comprueba que haya exactamente 48
  jugadores, ocho equipos con seis miembros, 48 membresías o 84 convocatorias.
- El conteo de participantes se obtiene de usuarios activos con
  `jugador_activo = 1` sin limitarse al rol de jugador; no equivale
  necesariamente al total requerido por la simulación.
- No hay verificación de unicidad de membresías activas, consistencia de
  marcador/eventos, campeón, goleador, mejor portero ni cantidades exactas de
  candidatos y votos.
- Esas comprobaciones requieren validaciones explícitas o reportes; actualmente
  no basta con que los formularios permitan guardar datos.

## 33. Consultas de resultados y estadísticas

**Requisito de [implementacion.md](../../sql/implementacion.md):** consultar
tabla de posiciones, goleadores, asistencias, tarjetas, porteros y participación.

**Estado: 🟡 API disponible; 🔴 sin módulo de reportes administrativos.**

[EstadisticasController.php](../controllers/EstadisticasController.php) expone
consultas de goleadores, asistencias, tarjetas, porteros, tabla de posiciones y
estadísticas individuales. Sin embargo, `api.js` no conecta esas consultas y
el panel no ofrece un reporte integral para revisar los resultados de la
simulación.

## 34. Simulación de votaciones

**Requisito de [implementacion.md](../../sql/implementacion.md):** crear cuatro
categorías, definir modo y tipo, ajustar candidatos y probar votos.

**Estado: 🟡 Configuración administrativa disponible; votos condicionados a
usuarios.**

### 34.1 Categorías a crear

La vista permite crear, editar, abrir, cerrar y eliminar categorías. Se pueden
configurar tipo `jugador`/`equipo`, modo `automatico`/`manual` y orden.

Hay un detalle de comportamiento: el formulario tiene una casilla para crear
una categoría abierta, pero
[CategoriasVotoController.php](../controllers/CategoriasVotoController.php)
crea todas las categorías cerradas. Después de crearla, el administrador puede
abrirla desde el botón de la categoría; la casilla no cambia el estado inicial
real.

### 34.2 `goleador-torneo`

Se puede crear como categoría automática de jugadores y no agregar ajustes.
El panel no comprueba que el pool automático contenga exactamente los 48
jugadores.

### 34.3 `equipo-campeon`

Se puede crear como categoría automática de equipos. El selector administrativo
de candidatos se basa en equipos aprobados. El panel no presenta una validación
del pool que confirme que solo estén los ocho equipos oficiales.

### 34.4 `mejor-portero`

Se pueden incluir manualmente los ocho porteros si ya existen jugadores,
membresías y equipos aprobados. No hay una validación de que el total sea
exactamente ocho.

### 34.5 `mvp-final`

Se pueden incluir manualmente los doce candidatos si existen y están dentro
del torneo. La categoría puede quedar cerrada. No hay una comprobación de que
coincida exactamente con los convocados de la final.

### 34.6 Votos de ejemplo

El panel administrativo no emite votos en nombre de usuarios. La ruta de emitir
voto exige sesión de usuario y el UPSERT se realiza desde el flujo del votante.
Los votos de ejemplo deben generarse mediante sesiones de usuario o pruebas de
API; no son una acción administrativa del dashboard. El panel puede ver conteos
por categoría, pero Inicio no actualiza su contador de votos: la función de
estadísticas del cliente sigue asignando `votos = 0`, aunque existe un endpoint
de resumen administrativo.

## 35. Criterio importante de implementación

Se conserva el criterio de
[implementacion.md](../../sql/implementacion.md): no agregar tablas ni cambiar
el esquema solo para completar la simulación. Cuando un requisito no se pueda
completar, primero debe documentarse la limitación y decidirse si se corrige en
la interfaz, en las reglas existentes o en el contrato de la API.

La incompatibilidad `rol = usuario` / `rol = jugador` de la sección 3 debe
resolverse explícitamente antes de crear las membresías.

## 36. Resultado esperado

**Estado: 🔴 No alcanzable íntegramente desde el panel actual.**

El dashboard puede capturar una parte importante del cuadro si las cuentas,
equipos y membresías ya están preparados. No puede completar por sí solo la
preparación administrativa de los datos ni validar todos los resultados
esperados. Las brechas principales son alta/edición de usuarios, administración
de equipos y capitanes, membresías, inscripción manual, asistencias y reportes
de integridad.

## 37. Requisitos adicionales solicitados para el dashboard

Estos puntos complementan la simulación y deben quedar cubiertos por el panel:

| Requisito | Situación actual |
|---|---|
| Crear, editar y eliminar torneos | Los endpoints existen; falta conectar el CRUD en `partials/torneo.php`. |
| Añadir manualmente un equipo a un torneo como admin | No hay alta administrativa directa. La ruta existente es una solicitud del capitán. |
| Separar Participantes en Usuarios y Jugadores | Hay una sola tabla; no hay pestañas ni vistas separadas. |
| En Usuarios, editar datos | La tabla es de lectura; la API solo actualiza avatar y estados, no datos generales. |
| En Jugadores, añadir/quitar de equipo | Hay endpoints, pero no una pantalla de administración y la comprobación de sesión puede rechazar al admin. |
| Nombrar o quitar capitán | No hay endpoint ni acción en el panel. |
| Mostrar solamente postulaciones pendientes | Actualmente se muestran pendientes y procesadas; se debe enviar `estado=pendiente` y excluir las procesadas. |
| Editar logo, nombre y estado del equipo | Hay endpoints para nombre/logo como cadena y estado, pero no módulo de equipos; subir un archivo como logo en `PATCH` no está soportado. |

## 38. Revisión de `passballcup.postman_collection.json`

La colección contiene requests de torneo, equipo, membresías, inscripciones,
usuarios y autenticación. En el cruce estático de método y ruta no se detectaron
requests relevantes que apunten a una ruta inexistente en
[routes/api.php](../routes/api.php).

Faltan en [passballcup.postman_collection.json](../passballcup.postman_collection.json)
requests para estas rutas administrativas ya declaradas y usadas por el panel:

- `GET /api/admin/usuarios`
- `GET /api/admin/equipos/{equipoId}/miembros`
- `GET /api/admin/torneos/{torneoId}/categorias-voto`
- `GET /api/admin/torneos/{torneoId}/candidatos-voto`
- `GET /api/admin/torneos/{torneoId}/jugadores`
- `GET /api/admin/votos-resumen`
- `GET /api/admin/posts`
- `POST /api/admin/posts`
- `PATCH /api/admin/posts/{id}/fijado`
- `DELETE /api/admin/posts/{id}`

También faltan `GET /api/test/jugador` y `GET /api/usuarios/buscar`, aunque no
son las brechas principales del panel. No se debe agregar a Postman un endpoint
de alta manual de equipo en torneo o de cambio de capitán hasta que esas
operaciones existan en la API.

## 39. Orden recomendado para completar el panel

1. Resolver y documentar el rol correcto de los 48 jugadores para que puedan
   formar parte de equipos.
2. Corregir el flujo de autorización administrativa para agregar y quitar
   miembros.
3. Conectar el CRUD de torneos en `partials/torneo.php`.
4. Crear módulos separados de Usuarios, Jugadores y Equipos, incluyendo edición
   de datos, membresías, capitán y estado.
5. Agregar inscripción manual de equipos por administrador y una acción de
   retirar equipos aprobados.
6. Filtrar Postulaciones por pendientes únicamente.
7. Añadir captura de asistencias y validaciones para comparar eventos con
   marcadores y comprobar los conteos de la simulación.
8. Incorporar reportes administrativos de estadísticas y validación integral.
9. Completar Postman con las rutas administrativas ya existentes y con los
   nuevos endpoints después de implementarlos.
