# PASSBALLCUP — Simulación integral de torneo

## Objetivo

Crear una simulación completa de un torneo llamado PASSBALLCUP utilizando la estructura actual de la base de datos de PASSBALL.

La finalidad de esta simulación no es únicamente tener datos de ejemplo, sino exprimir y validar al máximo la estructura actual de la base de datos antes de utilizarla con información real.

La simulación debe permitir comprobar:

- Usuarios y jugadores.
- Administradores.
- Equipos.
- Capitanes.
- Membresías de equipos.
- Restricción de un jugador activo en un solo equipo.
- Inscripción de equipos a un torneo.
- Aprobación de equipos.
- Estados de inscripción.
- Torneo.
- Rondas.
- Cuadro de eliminación directa.
- Partidos.
- Convocados.
- Titulares y banca.
- Posiciones.
- Goles.
- Autogoles.
- Penales anotados.
- Tarjetas amarillas.
- Tarjetas rojas.
- Asistencias.
- Estadísticas de porteros.
- Ganadores de partidos.
- Campeón del torneo.
- Tabla de goleadores.
- Estadísticas individuales.

## 1. Datos generales del torneo

Crear un torneo con los siguientes datos:

- Nombre: PASSBALLCUP
- Tipo: eliminacion_directa
- Estado inicial/final: finalizado

El torneo tendrá:

- 8 equipos participantes.
- 6 jugadores por equipo.
- 48 jugadores en total.
- 7 partidos.
- 3 rondas.

### Estructura

```text
PASSBALLCUP
│
├── Cuartos de final
│   ├── Real Madrid vs PSG
│   ├── Barcelona vs Borussia Dortmund
│   ├── Bayern Munich vs Everton
│   └── Monterrey vs Tigres
│
├── Semifinales
│   ├── Real Madrid vs Barcelona
│   └── Tigres vs Everton
│
└── Final
    └── Real Madrid vs Tigres
```

Campeón: Tigres

## 2. Administrador

Crear al menos un administrador para que pueda utilizarse en:

- torneo_equipos.aprobado_por

Datos sugeridos:

- Nombre: Administrador PASSBALL
- Activo: true

El administrador debe utilizarse para aprobar las inscripciones de los 8 equipos participantes.

## 3. Usuarios / jugadores

Crear exactamente 48 jugadores.

Cada jugador debe tener:

- matricula
- afi_usuario_id
- rol = usuario
- estado = activo
- jugador_activo = true

Las matrículas deben ser únicas.

Se recomienda utilizar matrículas ficticias claramente identificables, por ejemplo:

- PB00001
- PB00002
- ...
- PB00048

No utilizar matrículas reales.

## 4. Equipos

Crear exactamente los siguientes 8 equipos:

- Real Madrid
- PSG
- Barcelona
- Borussia Dortmund
- Bayern Munich
- Everton
- Monterrey
- Tigres

Todos deben iniciar como:

- estado = activo

Cada equipo debe tener un capitán.

El capitán debe ser uno de los 6 jugadores pertenecientes al equipo.

## 5. Plantillas de jugadores

Cada equipo debe tener exactamente 6 jugadores.

### Real Madrid

- Kylian Mbappé
- Vinícius Jr.
- Jude Bellingham
- Federico Valverde
- Luka Modrić
- Thibaut Courtois

### PSG

- Ousmane Dembélé
- Achraf Hakimi
- Marquinhos
- Vitinha
- Khvicha Kvaratskhelia
- Gianluigi Donnarumma

### Barcelona

- Robert Lewandowski
- Lamine Yamal
- Pedri
- Raphinha
- Frenkie de Jong
- Marc-André ter Stegen

### Borussia Dortmund

- Serhou Guirassy
- Karim Adeyemi
- Julian Brandt
- Marcel Sabitzer
- Nico Schlotterbeck
- Gregor Kobel

### Bayern Munich

- Harry Kane
- Jamal Musiala
- Joshua Kimmich
- Thomas Müller
- Alphonso Davies
- Manuel Neuer

### Everton

- Dominic Calvert-Lewin
- Dwight McNeil
- Idrissa Gueye
- James Garner
- James Tarkowski
- Jordan Pickford

### Monterrey

- Sergio Ramos
- Sergio Canales
- Lucas Ocampos
- Germán Berterame
- Jesús Corona
- Esteban Andrada

### Tigres

- André-Pierre Gignac
- Nahuel Guzmán
- Juan Brunetta
- Guido Pizarro
- Fernando Gorriarán
- Rafael Carioca

## 6. Jugadores obligatorios

Hay dos jugadores que deben formar parte obligatoriamente de la simulación:

### Kylian Mbappé

- Equipo: Real Madrid
- Debe tener una participación destacada y terminar como campeón goleador del torneo.
- Total: 10 goles

### André-Pierre Gignac

- Equipo: Tigres
- Debe participar en el torneo y registrar estadísticas.
- No es necesario que sea campeón goleador.

## 7. Membresías

Crear las 48 membresías correspondientes en `equipo_miembros`.

Debe existir una relación:

- 8 equipos × 6 jugadores = 48 membresías

Todas las membresías deben iniciar como:

- estado = activo
- fecha_salida = NULL

La restricción de membresía activa debe quedar respetada.

Es decir: ningún jugador puede estar activo en dos equipos simultáneamente.

La columna `jugador_membresia_activa_unica` debe funcionar de acuerdo con la lógica definida en la estructura original.

## 8. Inscripción al torneo

Crear los 8 registros correspondientes en `torneo_equipos`.

Los 8 equipos participantes deben quedar con:

- estado = aprobado
- torneo_id
- equipo_id
- fecha_solicitud
- fecha_aprobacion
- aprobado_por

`aprobado_por` debe apuntar al administrador creado anteriormente.

Debe respetarse la restricción:

- UNIQUE(torneo_id, equipo_id)

## 9. Casos adicionales para probar estados

Para comprobar que la tabla `torneo_equipos` soporta todos sus estados, se recomienda crear adicionalmente algunos equipos de prueba que no participen en el cuadro oficial.

Por ejemplo:

- Club América: estado = rechazado
- Pumas: estado = retirado

Estos equipos no deben formar parte de los 8 participantes oficiales.

La finalidad es comprobar que el sistema distingue correctamente entre:

- pendiente
- aprobado
- rechazado
- retirado

No deben alterar el cuadro de eliminación.

## 10. Rondas

Crear exactamente 3 registros en `torneo_rondas`:

1. Ronda 1
   - nombre = Cuartos de final
   - orden = 1
2. Ronda 2
   - nombre = Semifinal
   - orden = 2
3. Ronda 3
   - nombre = Final
   - orden = 3

El campo `orden` debe permitir identificar correctamente la secuencia del torneo.

## 11. Partidos

Crear exactamente 7 partidos.

Distribuidos:

- 4 Cuartos
- 2 Semifinales
- 1 Final

Todos deben pertenecer a la ronda correspondiente.

## 12. Cuartos de final

### Partido 1

- Real Madrid vs PSG
- Resultado: Real Madrid 5 - 2 PSG
- Ganador: Real Madrid
- Mbappé debe marcar 3 goles

### Partido 2

- Barcelona vs Borussia Dortmund
- Resultado: Barcelona 3 - 1 Borussia Dortmund
- Ganador: Barcelona

### Partido 3

- Bayern Munich vs Everton
- Resultado: Bayern Munich 1 - 2 Everton
- Ganador: Everton

### Partido 4

- Monterrey vs Tigres
- Resultado: Monterrey 2 - 3 Tigres
- Ganador: Tigres
- Gignac debe participar y registrar al menos una estadística ofensiva.

## 13. Semifinales

### Partido 5

- Real Madrid vs Barcelona
- Resultado: Real Madrid 4 - 2 Barcelona
- Ganador: Real Madrid
- Mbappé debe marcar 4 goles

### Partido 6

- Tigres vs Everton
- Resultado: Tigres 2 - 1 Everton
- Ganador: Tigres
- Gignac debe participar.
- Nahuel Guzmán debe registrar estadísticas de portero.

## 14. Final

### Partido 7

- Real Madrid vs Tigres
- Resultado: Real Madrid 2 - 3 Tigres
- Ganador: Tigres
- Campeón: TIGRES
- Mbappé debe marcar 3 goles

Totales de Mbappé:

- Cuartos: 3 goles
- Semifinal: 4 goles
- Final: 3 goles
- Total: 10 goles

GOLEADOR DEL TORNEO: KYLIAN MBAPPÉ — 10 GOLES

## 15. Convocados

Cada partido debe tener registros en `partido_convocados`.

No limitarse a registrar únicamente al goleador.

La intención es probar:

- jugador
- equipo
- partido
- titularidad
- posición

Cada equipo tiene 6 jugadores disponibles.

Para cada partido se recomienda registrar los 6 jugadores de cada equipo participante:

- 6 jugadores × 2 equipos = 12 convocados por partido
- 12 convocados × 7 partidos = 84 registros

Esto permitirá probar la tabla de convocatorias de manera mucho más completa.

## 16. Titulares y banca

Como cada equipo tiene 6 jugadores y el partido utiliza una plantilla completa de 6:

- 5 titulares + 1 banca por equipo
- por partido: 5 titulares + 1 banca + 5 titulares + 1 banca = 12 convocados

No todos los jugadores tienen que ser titulares durante todo el torneo.

Rotar algunos jugadores entre titular y banca para comprobar que el campo `titular` funciona correctamente.

## 17. Posiciones

Utilizar las cuatro posiciones disponibles:

- portero
- defensa
- mediocampo
- delantero

Cada equipo debe tener al menos:

- 1 portero
- 1 defensa
- 2 mediocampistas
- 2 delanteros

La posición debe asignarse en `partido_convocados`.

Los porteros principales deben ser:

- Real Madrid → Thibaut Courtois
- PSG → Gianluigi Donnarumma
- Barcelona → Marc-André ter Stegen
- Borussia Dortmund → Gregor Kobel
- Bayern Munich → Manuel Neuer
- Everton → Jordan Pickford
- Monterrey → Esteban Andrada
- Tigres → Nahuel Guzmán

## 18. Eventos de partido

Utilizar la tabla `partido_eventos`.

La simulación debe utilizar todos los tipos de eventos disponibles:

- gol
- autogol
- penal_anotado
- tarjeta_amarilla
- tarjeta_roja

No crear únicamente goles.

La finalidad es comprobar cada valor del ENUM.

## 19. Goles

Registrar los goles como eventos individuales.

Importante:

El marcador almacenado en `partidos` debe coincidir con los eventos registrados.

Ejemplo:

Si `goles_local = 5`, deben existir exactamente 5 eventos de gol válidos correspondientes al equipo local, considerando correctamente los autogoles según la lógica que se defina.

El sistema debe evitar inconsistencias entre:

- `partidos.goles_local`
- `partidos.goles_visitante`
- `partido_eventos`

## 20. Autogol

Registrar al menos un evento de tipo `autogol` durante el torneo.

Debe comprobarse que el evento puede asociarse al jugador que cometió el autogol y al equipo correspondiente.

El resultado final del partido debe seguir siendo coherente con el marcador.

## 21. Penal anotado

Registrar al menos un evento de tipo `penal_anotado` durante el torneo.

Debe estar asociado al jugador que ejecutó el penal.

Este evento debe contar correctamente dentro del resultado del partido.

## 22. Asistencias

Utilizar `asistencia_jugador_id` en varios eventos de gol.

Ejemplo conceptual:

```text
Mbappé
   ↓
asistencia
   ↓
Vinícius Jr.
   ↓
gol
```

En `partido_eventos`:

- `jugador_id = Vinícius Jr.`
- `tipo = gol`
- `asistencia_jugador_id = Mbappé`

Registrar varias asistencias para permitir posteriormente construir una tabla de asistencias.

También probar al menos un gol sin asistencia.

## 23. Tarjetas amarillas

Registrar múltiples eventos de tipo `tarjeta_amarilla` repartidas entre diferentes equipos y jugadores.

Esto permitirá posteriormente consultar:

- Jugador
- Tarjetas amarillas

## 24. Tarjeta roja

Registrar al menos una tarjeta roja de tipo `tarjeta_roja`.

Debe corresponder a un jugador convocado en ese partido.

No utilizar una tarjeta roja para alterar artificialmente el marcador.

La finalidad es probar el registro disciplinario.

## 25. Estadísticas de porteros

Utilizar `partido_estadisticas_portero`.

Debe existir al menos un registro de portero para cada partido.

Los porteros deben ser los registrados como porteros en las convocatorias.

## 26. Mejor portero del torneo

El mejor portero será: Nahuel Guzmán — Tigres

Registrar sus estadísticas:

### Cuartos

- vs Monterrey
- Atajadas: 7
- Goles recibidos: 2

### Semifinal

- vs Everton
- Atajadas: 5
- Goles recibidos: 1

### Final

- vs Real Madrid
- Atajadas: 8
- Goles recibidos: 2

Total:

- 20 atajadas
- 5 goles recibidos

Esto permitirá comprobar posteriormente la generación de estadísticas de porteros.

## 27. Mbappé — estadísticas

Mbappé debe participar en los tres partidos de Real Madrid.

- Cuartos vs PSG: 3 goles
- Semifinal vs Barcelona: 4 goles
- Final vs Tigres: 3 goles

Total: 3 + 4 + 3 = 10 goles

Debe quedar como:

- Máximo goleador
- 10 goles
- 3 partidos

También puede registrar asistencias y tarjetas para comprobar estadísticas adicionales.

## 28. Gignac — estadísticas

Gignac debe participar en los tres partidos de Tigres:

- vs Monterrey
- vs Everton
- vs Real Madrid

Registrar para él:

- goles
- al menos una asistencia
- al menos una tarjeta
- participación en convocatorias

No debe superar los 10 goles de Mbappé.

## 29. Ganador del partido

Cada partido debe tener `ganador_id` correctamente establecido.

Los ganadores deben ser:

### Cuartos

- Real Madrid
- Barcelona
- Everton
- Tigres

### Semifinales

- Real Madrid
- Tigres

### Final

- Tigres

El ganador siempre debe ser uno de los dos equipos participantes.

No debe existir un `ganador_id` perteneciente a otro equipo.

## 30. Estado de partidos

Todos los partidos históricos deben quedar con:

- estado = finalizado

La simulación debe representar un torneo que ya terminó.

## 31. Cuadro final esperado

El resultado completo debe poder reconstruirse desde la BD:

### CUARTOS DE FINAL

```text
Real Madrid 5 ───────┐
PSG          2       │
                     ├── Real Madrid 4 ──────┐
Barcelona    3 ──────┤                       │
Borussia     1       │                       │
                                             ├── TIGRES 3 🏆
Bayern       1 ──────┐                       │
Everton      2       │                       │
                     ├── Tigres 2 ───────────┘
Monterrey    2 ──────┤
Tigres       3       │
```

### SEMIFINALES

```text
Real Madrid 4
Barcelona   2

Tigres      2
Everton     1
```

### FINAL

```text
Real Madrid 2
Tigres      3
```

## 32. Validaciones que debe realizar el agente

Después de insertar los datos, comprobar:

- Usuarios: 48 jugadores
- Equipos: 8 equipos participantes
- 6 jugadores por equipo
- Membresías: 48 membresías activas
- Ningún jugador debe tener dos membresías activas.
- Torneo: 1 PASSBALLCUP
- Inscripciones: 8 equipos aprobados
- Además de los registros opcionales de prueba: rechazado y retirado
- Rondas: 3
- Partidos: 7
- Convocados: 84 (si se utilizan los 12 jugadores disponibles por partido)

### Resultados

- Los marcadores deben coincidir con los eventos.
- Campeón: Tigres
- Goleador: Mbappé — 10 goles
- Mejor portero: Nahuel Guzmán

### Votaciones

- Categorías: 4 (`goleador-torneo`, `equipo-campeon`, `mejor-portero`,
  `mvp-final`)
- `mejor-portero`: exactamente 8 candidatos `incluir`
- `mvp-final`: exactamente 12 candidatos `incluir`, `estado='cerrada'`
- `goleador-torneo` / `equipo-campeon`: 0 filas en
  `torneo_categoria_candidatos` (pool 100% automático)
- Ningún usuario debe tener dos filas en `torneo_votos` para la misma
  `(torneo_id, categoria_id)` — el caso de cambio de voto debe reflejarse
  como una sola fila actualizada

## 33. Consultas que la simulación debería permitir

Una vez implementada, la información debería permitir obtener fácilmente:

### Tabla de posiciones / resultados

- Equipo
- Partidos
- Victorias
- Derrotas
- Goles a favor
- Goles en contra
- Diferencia de goles

### Goleadores

- Jugador
- Equipo
- Goles

Mbappé debe aparecer primero con 10 goles.

### Asistencias

- Jugador
- Equipo
- Asistencias

### Tarjetas

- Jugador
- Equipo
- Amarillas
- Rojas

### Porteros

- Portero
- Equipo
- Partidos
- Atajadas
- Goles recibidos

Nahuel Guzmán debe poder identificarse como el portero con 20 atajadas y 5 goles recibidos.

### Participación

Consultar:

- Jugador
- Partidos convocado
- Titularidades
- Banca
- Goles
- Asistencias
- Tarjetas

## 34. Simulación de votaciones (`torneo_categorias_voto` / `torneo_categoria_candidatos` / `torneo_votos`)

Igual que el resto de la simulación, esta parte debe ejercitar cada rama del
modelo: ambos `tipo` (`jugador`/`equipo`), ambos `modo_candidatos`
(`automatico`/`manual`), el ajuste `excluir` en modo automático, el ajuste
`incluir` en modo manual, ambos `estado` (`abierta`/`cerrada`), y un cambio
de voto (upsert) para comprobar la unique key.

### 34.1 Categorías a crear

Sobre el torneo PASSBALLCUP (`torneo_id` del torneo ya creado):

| clave | nombre | tipo | modo_candidatos | estado |
|---|---|---|---|---|
| `goleador-torneo` | Goleador del torneo | jugador | automatico | abierta |
| `equipo-campeon` | Equipo campeón | equipo | automatico | abierta |
| `mejor-portero` | Mejor portero | jugador | manual | abierta |
| `mvp-final` | MVP de la final | jugador | manual | cerrada |

### 34.2 `goleador-torneo` — automático, sin exclusiones

No se agrega ningún registro en `torneo_categoria_candidatos`. El pool debe
resolver a los 48 jugadores (derivados de `equipo_miembros` vigente durante
las fechas del torneo), sin necesidad de curar nada — cualquiera pudo
marcar.

### 34.3 `equipo-campeon` — automático, tipo equipo, sin exclusiones

Tampoco necesita ajustes: el pool automático de equipos ya se limita
correctamente a los 8 equipos con `torneo_equipos.estado='aprobado'` — Club
América (`rechazado`) y Pumas (`retirado`) quedan fuera solos, sin
intervención manual. Esto sirve para comprobar que el cálculo automático
excluye correctamente inscripciones no aprobadas.

### 34.4 `mejor-portero` — manual, tipo jugador

Insertar 8 registros en `torneo_categoria_candidatos` con `ajuste='incluir'`,
uno por el portero principal de cada equipo (los mismos 8 de la sección 17):
Courtois, Donnarumma, ter Stegen, Kobel, Neuer, Pickford, Andrada, Guzmán.

Esta categoría es el ejemplo de por qué existe el modo manual: en modo
automático el pool serían los 48 jugadores y habría que excluir a los 40
que no son porteros — con manual basta con 8 filas `incluir`.

### 34.5 `mvp-final` — manual, tipo jugador, categoría cerrada

Insertar 12 registros `incluir`: exactamente los convocados del Partido 7
(Real Madrid vs Tigres) — Mbappé, Vinícius Jr., Bellingham, Valverde,
Modrić, Courtois, Gignac, Guzmán, Brunetta, Pizarro, Gorriarán, Carioca.

Dejar esta categoría en `estado='cerrada'` a propósito: sirve para probar
que `POST /api/torneos/{id}/votos` la rechaza mientras no se abra, y que
`GET /api/torneos/{id}/categorias-voto/{id}/pool` sigue devolviendo los 12
candidatos aunque la categoría esté cerrada (el pool no depende del
`estado`).

### 34.6 Votos de ejemplo

Usar una muestra de los 48 usuarios (no hace falta que los 48 voten). Por
ejemplo, 10 usuarios votan en `goleador-torneo` (repartidos entre varios
jugadores, no solo Mbappé, para poder probar el conteo agrupado), 8 usuarios
votan en `equipo-campeon`, y 6 usuarios votan en `mejor-portero`.

Incluir al menos un caso de **cambio de voto**: un mismo `usuario_id` vota
primero por un jugador en `goleador-torneo` y luego vuelve a votar por otro
jugador en la misma categoría — debe quedar una sola fila en `torneo_votos`
para ese `(torneo_id, usuario_id, categoria_id)`, con el valor actualizado
(comprueba la unique key `uq_voto_usuario_categoria` funcionando como
upsert, no como error de duplicado).

No registrar votos en `mvp-final` (está cerrada).

## 35. Criterio importante de implementación

El agente no debe introducir nuevas tablas ni modificar la estructura de la BD solamente para hacer funcionar esta simulación.

La finalidad de esta prueba es precisamente comprobar cuánto puede hacer la estructura actual.

Si durante la implementación se detecta una limitación real del modelo, primero:

- Documentar la limitación.
- Explicar por qué ocurre.
- Determinar si puede resolverse con la estructura existente.
- Solo después proponer una modificación estructural.

No crear campos o tablas innecesarias únicamente para almacenar datos de la simulación.

## 36. Resultado esperado

Al finalizar la implementación debe existir un torneo completamente navegable desde la base de datos:

```text
PASSBALLCUP
│
├── 8 equipos
│   └── 48 jugadores
│
├── 3 rondas
│   └── 7 partidos
│
├── 84 convocatorias
│
├── Eventos
│   ├── goles
│   ├── autogoles
│   ├── penales
│   ├── asistencias
│   ├── amarillas
│   └── rojas
│
├── Estadísticas de porteros
│
├── Campeón
│   └── Tigres
│
├── Campeón goleador
│   └── Mbappé — 10 goles
│
└── Mejor portero
    └── Nahuel Guzmán
```

### Objetivo final de la prueba

La simulación debe ser suficientemente completa para que, utilizando únicamente la información almacenada en la BD, el sistema pueda reconstruir:

- quién participó;
- en qué equipo jugó;
- quién era capitán;
- qué equipos fueron aprobados;
- qué equipos quedaron fuera;
- qué partidos se jugaron;
- quiénes fueron convocados;
- quiénes fueron titulares;
- qué posición jugaron;
- quién marcó;
- quién asistió;
- quién cometió autogol;
- quién anotó penal;
- quién recibió tarjetas;
- cuántas atajadas realizó cada portero;
- quién ganó cada partido;
- cómo avanzaron los equipos por las rondas;
- quién llegó a la final;
- quién fue campeón;
- quién fue el goleador;
- y quién fue el mejor portero.

La información debe ser internamente consistente y respetar todas las relaciones, restricciones, claves únicas y reglas de negocio actualmente definidas en la base de datos.
