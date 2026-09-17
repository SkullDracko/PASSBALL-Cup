# Endpoints de PASSBALLCUP y cobertura de información

Basado en la estructura de [`bd_propuesta.sql`](./bd_propuesta.sql). Formato
de referencia para implementar los controllers de la API; no incluye código,
solo el contrato de cada endpoint y qué tabla(s) toca.

---

## 1. `auth/` — sesión de jugador (usuarios)

| Endpoint | Descripción |
|---|---|
| `POST /api/auth/login` | Recibe matrícula, valida contra AFI Hub (o modo local), busca/crea fila en `usuarios`, abre sesión. |
| `POST /api/auth/logout` | Cierra la sesión del jugador actual. |
| `GET /api/auth/me` | Devuelve los datos del usuario en sesión (`id`, `matricula`, `rol`, `estado`, `jugador_activo`). |

## 2. `admin-auth/` — sesión de administrador (tabla independiente)

| Endpoint | Descripción |
|---|---|
| `POST /api/admin/login` | Valida `usuario`/`contrasena` contra `administradores` (hash con `password_verify`). |
| `POST /api/admin/logout` | Cierra la sesión del administrador. |
| `GET /api/admin/me` | Devuelve el administrador en sesión. |

## 3. `usuarios/`

| Endpoint | Descripción |
|---|---|
| `GET /api/usuarios` | Lista jugadores. Filtros: `estado`, `jugador_activo`, `rol`. |
| `GET /api/usuarios/{id}` | Detalle de un jugador. |
| `PATCH /api/usuarios/{id}` | Edita `avatar`. |
| `PATCH /api/usuarios/{id}/estado` | Activa/desactiva la cuenta (`usuarios.estado`). |
| `PATCH /api/usuarios/{id}/jugador-activo` | Habilita/inhabilita a la persona como jugador (`usuarios.jugador_activo`), sin afectar su acceso al sistema. |

## 4. `administradores/`

| Endpoint | Descripción |
|---|---|
| `GET /api/administradores` | Lista administradores. |
| `POST /api/administradores` | Crea un administrador (`nombre`, `usuario`, `contrasena` hasheada). |
| `GET /api/administradores/{id}` | Detalle. |
| `PATCH /api/administradores/{id}` | Edita `nombre`/`usuario`. |
| `PATCH /api/administradores/{id}/activo` | Activa/desactiva el acceso del administrador. |
| `DELETE /api/administradores/{id}` | Elimina (si no tiene `torneo_equipos.aprobado_por` asociados). |

## 5. `equipos/`

| Endpoint | Descripción |
|---|---|
| `GET /api/equipos` | Lista equipos. Filtro: `estado`. |
| `POST /api/equipos` | Crea equipo (`nombre`, `logo`, `capitan_id` obligatorio). |
| `GET /api/equipos/{id}` | Detalle de equipo (incluye datos del capitán vía `capitan_id`). |
| `PATCH /api/equipos/{id}` | Edita `nombre`, `logo`, `capitan_id`. |
| `PATCH /api/equipos/{id}/estado` | Activa/desactiva el equipo. |
| `DELETE /api/equipos/{id}` | Elimina (si no tiene membresías/inscripciones asociadas). |

## 6. `equipos/{equipoId}/miembros/` (tabla `equipo_miembros`)

| Endpoint | Descripción |
|---|---|
| `GET /api/equipos/{equipoId}/miembros` | Lista miembros del equipo. Filtro: `estado`. |
| `POST /api/equipos/{equipoId}/miembros` | Agrega jugador al equipo (`estado='activo'`); la restricción `jugador_membresia_activa_unica` impide una segunda membresía activa. |
| `PATCH /api/equipos/{equipoId}/miembros/{jugadorId}/salida` | Marca la membresía como `inactivo` y registra `fecha_salida`. |
| `DELETE /api/equipos/{equipoId}/miembros/{jugadorId}` | Borra el registro de membresía (uso administrativo/corrección, no salida normal). |

## 7. `jugadores/{jugadorId}/equipo/`

| Endpoint | Descripción |
|---|---|
| `GET /api/jugadores/{jugadorId}/equipo-actual` | Devuelve la membresía activa vigente del jugador (o ninguna). |
| `GET /api/jugadores/{jugadorId}/historial-equipos` | Devuelve todas las membresías (activas e inactivas) del jugador, ordenadas por `fecha_union`. |

## 8. `torneos/`

| Endpoint | Descripción |
|---|---|
| `GET /api/torneos` | Lista torneos. |
| `POST /api/torneos` | Crea torneo (`nombre`, `tipo`, `fecha_inicio`, `fecha_fin`). |
| `GET /api/torneos/{id}` | Detalle de torneo. |
| `PATCH /api/torneos/{id}` | Edita datos generales o `estado` (`programado`/`en_curso`/`finalizado`/`cancelado`). |
| `DELETE /api/torneos/{id}` | Elimina (si no tiene rondas/inscripciones asociadas). |

## 9. `torneos/{torneoId}/equipos/` (tabla `torneo_equipos`)

| Endpoint | Descripción |
|---|---|
| `GET /api/torneos/{torneoId}/equipos` | Lista inscripciones. Filtro: `estado` (`pendiente`/`aprobado`/`rechazado`/`retirado`). |
| `POST /api/torneos/{torneoId}/equipos` | Solicita inscripción de un equipo (`estado='pendiente'`). |
| `PATCH /api/torneos/{torneoId}/equipos/{equipoId}/aprobar` | Marca `estado='aprobado'`, registra `fecha_aprobacion` y `aprobado_por` (administrador en sesión). |
| `PATCH /api/torneos/{torneoId}/equipos/{equipoId}/rechazar` | Marca `estado='rechazado'`. |
| `PATCH /api/torneos/{torneoId}/equipos/{equipoId}/retirar` | Marca `estado='retirado'` (equipo que ya estaba aprobado y se retira). |

## 10. `torneos/{torneoId}/rondas/` (tabla `torneo_rondas`)

| Endpoint | Descripción |
|---|---|
| `GET /api/torneos/{torneoId}/rondas` | Lista rondas ordenadas por `orden`. |
| `POST /api/torneos/{torneoId}/rondas` | Crea ronda (`nombre`, `orden`). |
| `PATCH /api/torneos/{torneoId}/rondas/{id}` | Edita `nombre`/`orden`. |
| `DELETE /api/torneos/{torneoId}/rondas/{id}` | Elimina (si no tiene partidos asociados). |

## 11. `torneos/{torneoId}/bracket/`

| Endpoint | Descripción |
|---|---|
| `GET /api/torneos/{torneoId}/bracket` | Devuelve el árbol completo: rondas → partidos, con `partido_origen_local_id`/`partido_origen_visitante_id` resueltos, para renderizar el cuadro de eliminación directa. |

## 12. `partidos/`

| Endpoint | Descripción |
|---|---|
| `GET /api/partidos` | Lista partidos. Filtros: `ronda_id`, `equipo_id`, `estado`. |
| `POST /api/partidos` | Crea partido (`ronda_id`, `posicion`, y `equipo_local_id`/`equipo_visitante_id` **o** `partido_origen_local_id`/`partido_origen_visitante_id`). |
| `GET /api/partidos/{id}` | Detalle del partido. |
| `PATCH /api/partidos/{id}` | Edita `fecha_hora`, `cancha`, `estado` (sin tocar el resultado). |
| `PATCH /api/partidos/{id}/resultado` | Registra `goles_local`, `goles_visitante`, `penales_local`, `penales_visitante`, `ganador_id`. |
| `PATCH /api/partidos/{id}/finalizar` | Marca `estado='finalizado'` y ejecuta el procedimiento que propaga el `ganador_id` hacia `equipo_local_id`/`equipo_visitante_id` del partido siguiente (ver sección de "Rondas" en `observaciones_simulacion.md`). |
| `DELETE /api/partidos/{id}` | Elimina (si no tiene convocados/eventos asociados). |

## 13. `partidos/{partidoId}/convocados/` (tabla `partido_convocados`)

| Endpoint | Descripción |
|---|---|
| `GET /api/partidos/{partidoId}/convocados` | Lista convocados del partido (ambos equipos). |
| `POST /api/partidos/{partidoId}/convocados` | Convoca a un jugador (`jugador_id`, `equipo_id`, `titular`, `posicion`). |
| `PATCH /api/partidos/{partidoId}/convocados/{jugadorId}` | Cambia `titular`/`posicion` de un convocado ya registrado. |
| `DELETE /api/partidos/{partidoId}/convocados/{jugadorId}` | Quita a un jugador de la convocatoria. |

## 14. `partidos/{partidoId}/eventos/` (tabla `partido_eventos`)

| Endpoint | Descripción |
|---|---|
| `GET /api/partidos/{partidoId}/eventos` | Lista eventos del partido. Filtros: `tipo`, `jugador_id`, `equipo_id`. |
| `POST /api/partidos/{partidoId}/eventos` | Registra un evento (`gol`, `autogol`, `penal_anotado`, `tarjeta_amarilla`, `tarjeta_roja`), con `jugador_id`, `equipo_id`, `minuto`, `asistencia_jugador_id` opcional. |
| `PATCH /api/partidos/{partidoId}/eventos/{eventoId}` | Corrige un evento ya registrado. |
| `DELETE /api/partidos/{partidoId}/eventos/{eventoId}` | Elimina un evento (por ejemplo, registrado por error). |

## 15. `partidos/{partidoId}/porteros/` (tabla `partido_estadisticas_portero`)

| Endpoint | Descripción |
|---|---|
| `GET /api/partidos/{partidoId}/porteros` | Lista estadísticas de porteros del partido (local y visitante). |
| `POST /api/partidos/{partidoId}/porteros` | Registra estadística de un portero (`jugador_id`, `atajadas`, `goles_recibidos`). |
| `PATCH /api/partidos/{partidoId}/porteros/{jugadorId}` | Corrige `atajadas`/`goles_recibidos`. |
| `DELETE /api/partidos/{partidoId}/porteros/{jugadorId}` | Elimina el registro. |

## 16. `estadisticas/` — agregados de solo lectura (derivados, no tablas propias)

| Endpoint | Descripción |
|---|---|
| `GET /api/estadisticas/goleadores?torneo_id=` | `COUNT(*)` de `partido_eventos` con `tipo IN ('gol','penal_anotado')`, agrupado por `jugador_id`. |
| `GET /api/estadisticas/asistencias?torneo_id=` | `COUNT(*)` de `partido_eventos.asistencia_jugador_id`, agrupado por jugador. |
| `GET /api/estadisticas/tarjetas?torneo_id=` | `COUNT(*)` de `partido_eventos` con `tipo IN ('tarjeta_amarilla','tarjeta_roja')`, agrupado por jugador y tipo. |
| `GET /api/estadisticas/porteros?torneo_id=` | `SUM(atajadas)`, `SUM(goles_recibidos)` de `partido_estadisticas_portero`, agrupado por jugador. |
| `GET /api/estadisticas/tabla-posiciones?torneo_id=` | Partidos jugados, victorias, derrotas, goles a favor/en contra, diferencia — calculado desde `partidos` + `partido_eventos`. |
| `GET /api/estadisticas/jugador/{jugadorId}` | Ficha individual: convocatorias, titularidades, goles, asistencias, tarjetas. |

---

## 17. `torneos/{torneoId}/categorias-voto/` (tabla `torneo_categorias_voto`)

| Endpoint | Descripción |
|---|---|
| `GET /api/torneos/{torneoId}/categorias-voto` | Lista categorías del torneo, ordenadas por `orden`. |
| `POST /api/torneos/{torneoId}/categorias-voto` | Crea categoría (`clave`, `nombre`, `tipo`, `modo_candidatos`). Nace con `estado='cerrada'`. |
| `PATCH /api/torneos/{torneoId}/categorias-voto/{id}` | Edita `nombre`, `tipo`, `modo_candidatos`, `orden`. |
| `PATCH /api/torneos/{torneoId}/categorias-voto/{id}/estado` | Alterna `estado` entre `abierta`/`cerrada`. Actualiza `fecha_ultimo_cambio` automáticamente (`ON UPDATE CURRENT_TIMESTAMP`). |
| `DELETE /api/torneos/{torneoId}/categorias-voto/{id}` | Elimina (si no tiene candidatos ni votos asociados). |
| `GET /api/torneos/{torneoId}/categorias-voto/{id}/pool` | Devuelve el pool final de candidatos ya resuelto (ver lógica abajo), listo para pintar la boleta. |

**Lógica de `pool` (no es una tabla, se calcula en el controller):**
- Si `modo_candidatos = 'automatico'`: equipos = `torneo_equipos` con `estado='aprobado'` en ese torneo; jugadores = `equipo_miembros` cuya vigencia (`fecha_union`/`fecha_salida`) se solapa con `torneos.fecha_inicio`/`fecha_fin` (si el torneo no tiene fechas capturadas, cae a membresía activa actual). A ese conjunto se le restan los `jugador_id`/`equipo_id` con `ajuste='excluir'` en `torneo_categoria_candidatos`.
- Si `modo_candidatos = 'manual'`: el pool es exactamente los `jugador_id`/`equipo_id` con `ajuste='incluir'` en `torneo_categoria_candidatos`.

## 18. `torneos/{torneoId}/categorias-voto/{categoriaId}/candidatos/` (tabla `torneo_categoria_candidatos`)

| Endpoint | Descripción |
|---|---|
| `GET /api/torneos/{torneoId}/categorias-voto/{categoriaId}/candidatos` | Lista los ajustes (`incluir`/`excluir`) crudos de la categoría, sin resolver contra el pool automático — para la vista de edición del admin. |
| `POST /api/torneos/{torneoId}/categorias-voto/{categoriaId}/candidatos` | Agrega un ajuste (`jugador_id` **o** `equipo_id`, `ajuste`). El controller valida que `jugador_id`/`equipo_id` corresponda al `tipo` de la categoría antes de insertar (regla que el `CHECK` de la tabla no cubre). |
| `DELETE /api/torneos/{torneoId}/categorias-voto/{categoriaId}/candidatos/{id}` | Quita el ajuste (vuelve a depender del cálculo automático, o en modo manual saca al candidato de la boleta). |

## 19. `torneos/{torneoId}/votos/` (tabla `torneo_votos`)

| Endpoint | Descripción |
|---|---|
| `POST /api/torneos/{torneoId}/votos` | Emite o actualiza mi voto (`categoria_id` + `jugador_id` o `equipo_id`, según el `tipo` de la categoría). Es un upsert sobre `uq_voto_usuario_categoria`. El controller rechaza el voto si `categoria.estado != 'abierta'` o si el candidato no está en el `pool` resuelto de esa categoría (ninguna de las dos reglas vive en la BD). |
| `GET /api/torneos/{torneoId}/votos/mios` | Devuelve mis votos actuales en ese torneo (uno por categoría). |
| `GET /api/torneos/{torneoId}/votos/resultados?categoria_id=` | Conteo de votos agrupado por `jugador_id`/`equipo_id` para esa categoría (para pintar el ranking en la UI). |
| `DELETE /api/torneos/{torneoId}/votos/{categoriaId}` | Retira mi voto de esa categoría. |

---

## 20. ¿La BD + estos endpoints cubren toda la información pedida?

| Información requerida | ¿Se puede obtener? | Cómo |
|---|---|---|
| Quién participó | Sí | `equipo_miembros` (jugadores con membresía en algún equipo) |
| En qué equipo jugó | Sí | `equipo_miembros.equipo_id` / `GET /api/jugadores/{id}/equipo-actual` |
| Quién era capitán | Sí | `equipos.capitan_id` |
| Qué equipos fueron aprobados | Sí | `torneo_equipos` con `estado='aprobado'` / `GET /api/torneos/{id}/equipos?estado=aprobado` |
| Qué equipos quedaron fuera | Sí | `torneo_equipos` con `estado IN ('rechazado','retirado')` |
| Qué partidos se jugaron | Sí | `partidos` con `estado='finalizado'` / `GET /api/partidos?estado=finalizado` |
| Quiénes fueron convocados | Sí | `partido_convocados` / `GET /api/partidos/{id}/convocados` |
| Quiénes fueron titulares | Sí | `partido_convocados.titular = TRUE` |
| Qué posición jugaron | Sí | `partido_convocados.posicion` |
| Quién marcó | Sí | `partido_eventos` con `tipo IN ('gol','penal_anotado')` |
| Quién asistió | Sí | `partido_eventos.asistencia_jugador_id` |
| Quién cometió autogol | Sí | `partido_eventos` con `tipo='autogol'` |
| Quién anotó penal | Sí | `partido_eventos` con `tipo='penal_anotado'` |
| Quién recibió tarjetas | Sí | `partido_eventos` con `tipo IN ('tarjeta_amarilla','tarjeta_roja')` |
| Atajadas por portero | Sí | `partido_estadisticas_portero.atajadas` |
| Quién ganó cada partido | Sí | `partidos.ganador_id` |
| Cómo avanzaron los equipos por rondas | Sí | `partidos.partido_origen_local_id` / `partido_origen_visitante_id` + `ganador_id` del partido de origen |
| Quién llegó a la final | Sí | `equipo_local_id`/`equipo_visitante_id` del partido con la `ronda` de mayor `orden` |
| Quién fue campeón | Sí | `ganador_id` del partido de la ronda con mayor `orden` |
| Quién fue el goleador | Sí | Agregado sobre `partido_eventos` (endpoint `goleadores`) |
| Quién fue el mejor portero | Sí | Agregado sobre `partido_estadisticas_portero` (endpoint `porteros`) |
| Qué categorías de votación existen por torneo | Sí | `torneo_categorias_voto` / `GET /api/torneos/{id}/categorias-voto` |
| Quién puede ser votado en una categoría | Sí | Pool resuelto vía `GET /api/torneos/{id}/categorias-voto/{id}/pool` (automático + ajustes de `torneo_categoria_candidatos`) |
| Si una votación está abierta o cerrada | Sí | `torneo_categorias_voto.estado` |
| Por quién votó cada usuario | Sí | `torneo_votos` / `GET /api/torneos/{id}/votos/mios` |
| Resultados de una votación | Sí | Agregado sobre `torneo_votos` (endpoint `resultados`) |

**Conclusión:** con el esquema actual (incluyendo `partido_origen_*` ya
agregado) y los endpoints anteriores, el sistema puede reconstruir el 100% de
la información pedida directamente desde la base de datos, sin necesitar
tablas ni columnas adicionales. Todo lo listado arriba ya se validó de forma
manual contra la simulación PASSBALLCUP (ver `observaciones_simulacion.md`).

Las únicas dos cosas que **no** garantiza la base de datos por sí sola (deben
vivir en la capa de aplicación, ya señaladas en `observaciones_simulacion.md`):

1. Que `partido_origen_local_id` apunte siempre a una ronda anterior (orden
   menor), no a cualquier partido arbitrario.
2. Que al finalizar un partido, el partido siguiente se actualice
   automáticamente (`equipo_local_id`/`equipo_visitante_id`) — requiere el
   endpoint `PATCH /api/partidos/{id}/finalizar` explícito, no ocurre solo.

---

## 21. Arquitectura recomendada para este proyecto

El proyecto ya usa **controllers de un solo archivo por recurso** en PHP
plano (ver `controllers/equiposController.php`, `controllers/login.php`),
sin capa de modelos separada. Recomiendo mantener ese mismo patrón en vez de
introducir un ORM o una separación Modelo/Controlador estricta, para no
romper la convención existente del repo:

```
controllers/
├── auth.php                       (middleware ya existente)
├── authController.php             -> auth/*
├── adminAuthController.php        -> admin-auth/*
├── usuariosController.php         -> usuarios/*
├── administradoresController.php  -> administradores/*
├── equiposController.php          -> equipos/* (ya existe, se adapta)
├── equipoMiembrosController.php   -> equipos/{id}/miembros/*, jugadores/{id}/equipo*
├── torneosController.php          -> torneos/*
├── torneoEquiposController.php    -> torneos/{id}/equipos/*
├── torneoRondasController.php     -> torneos/{id}/rondas/*
├── bracketController.php          -> torneos/{id}/bracket
├── partidosController.php         -> partidos/* (incluye /finalizar)
├── partidoConvocadosController.php-> partidos/{id}/convocados/*
├── partidoEventosController.php   -> partidos/{id}/eventos/*
├── partidoPorterosController.php  -> partidos/{id}/porteros/*
├── estadisticasController.php     -> estadisticas/* (solo lectura, JOINs/GROUP BY)
├── categoriasVotoController.php   -> torneos/{id}/categorias-voto/* (incluye /pool)
├── candidatosVotoController.php   -> torneos/{id}/categorias-voto/{id}/candidatos/*
└── votosController.php            -> torneos/{id}/votos/*
```

Cada controller sigue el patrón que ya usa `equiposController.php`: un
`switch ($action)` con los casos CRUD de ese recurso, usando `$pdo` directo
(sin capa de modelo intermedia). Esto es apropiado para el tamaño actual del
proyecto; evita introducir Modelos/Repositorios que agregarían complejidad
sin beneficio real dado que no hay lógica de negocio compleja reutilizada
entre controllers (cada consulta es específica de su endpoint).

**Única pieza que sí conviene aislar de la lógica del controller:** el
procedimiento de "finalizar partido y propagar ganador" (sección 12,
`PATCH /api/partidos/{id}/finalizar`), por ser una transacción con varios
pasos (`UPDATE partidos` del propio partido + `UPDATE partidos` de hasta dos
partidos dependientes). Ese sí conviene envolverlo en su propia función PHP
reutilizable (o stored procedure en MySQL, como se discutió antes) en lugar
de repetir la lógica en línea dentro del controller.

**Segunda pieza a aislar:** la resolución del pool de candidatos (sección
17, `GET /api/torneos/{id}/categorias-voto/{id}/pool`). Es la misma consulta
la necesitan tres lugares distintos — el propio endpoint `/pool`, la
validación de `POST /api/torneos/{id}/votos` (¿el candidato elegido
pertenece al pool?), y la pantalla de edición de candidatos del admin — así
que conviene una sola función PHP (`resolverPoolCandidatos($categoriaId)`)
que devuelva el arreglo final, en vez de repetir el `automatico` vs
`manual` + exclusiones en cada controller.
