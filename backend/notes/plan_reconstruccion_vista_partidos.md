# PASSBALL Cup — Plan de reconstrucción de la vista Partidos

> **Estado:** plan de trabajo; todavía no modifica la aplicación.  
> **Primera entrega propuesta:** bracket de eliminación directa, estático, construido con HTML y CSS.  
> **Fuera de la primera entrega:** peticiones a la API, interacción con partidos y cambios a la base de datos.

## 1. Objetivo

Reemplazar la lista actual de encuentros por una vista clara de un cuadro tradicional de eliminación directa. El bracket debe mostrar las rondas en columnas, cada partido como dos equipos enfrentados y el avance visual de sus ganadores hasta la final.

Para que el primer resultado sea comprobable, se propone usar la simulación PASSBALLCUP existente como fixture visual: ocho equipos, siete partidos, resultados ya definidos y Tigres como campeón. Esto **no** convierte la vista en una fuente de datos ni implica que el torneo real ya exista en la base.

La separación objetivo es:

- **Vista:** HTML semántico y CSS en `partials/` y `assets/css/`.
- **Dashboard:** conserva navegación, sesión y composición de la página; aloja el fragmento HTML sin incluir lógica propia de partidos.
- **API/backend:** conserva el contrato y las reglas de negocio en `backend/routes/`, `backend/controllers/` y servicios.
- **Integración futura:** un módulo JavaScript de `assets/js/` consume la API y actualiza la vista cuando se autorice hacerla funcional.

## 2. Estado actual y alcance confirmado

| Superficie | Estado observado | Decisión para esta reconstrucción |
|---|---|---|
| [Dashboard principal](../../dashboard.php) | Es un dashboard de una sola página. La pestaña `data-target="view-partidos"` cambia a la sección con ese id y el archivo incluye el partial actual. | Mantener el shell, su navegación y el id `view-partidos`. No reconstruir el dashboard completo. |
| [Partial actual](../../partials/partidos.php) | Define partidos, contadores, markup y filtros mediante PHP. Los datos son ficticios y la estructura es una lista, no un cuadro. | Sustituir el contenido de presentación por HTML estático, sin variables, consultas, ciclos ni condicionales PHP. |
| [CSS de Partidos](../../assets/css/pages/partidos.css) | Está acotado mayormente a `#view-partidos`, pero estiliza estadísticas, barra de filtros y filas de una lista. | Reemplazar las reglas de la lista por estilos propios del bracket, manteniendo el alcance bajo `#view-partidos`. |
| [JavaScript actual](../../assets/js/partidos.js) | Busca `.match-tab` y `.match-row`, filtra por estado/categoría y escucha el buscador. | No trasladar estos filtros al bracket. En la primera entrega se deja de cargar desde el dashboard principal; se conserva el archivo mientras se confirme el uso de `dashboard2.php`. |
| [Ruta de bracket](../routes/api.php) | Ya registra `GET /api/torneos/{torneoId}/bracket`. | No crear otra ruta para mostrar el bracket. No llamarla durante la primera entrega. |
| [Controlador del bracket](../controllers/BracketController.php) | Devuelve rondas ordenadas y partidos con equipos, resultados, ganador y partidos origen. | Reutilizarlo en una futura fase funcional, después de verificar el contrato y resolver el id de torneo. |
| [Esquema SQL](../../sql/bd_propuesta.sql) | Ya contempla rondas, partidos, partidos origen, equipos y ganador. | No añadir tablas ni columnas para construir la vista. |
| [Simulación](../../sql/implementacion.md) | Describe los ocho equipos, siete encuentros, resultados, progresión y campeón. | Usarla como referencia única de contenido para el mockup. |

### Nota sobre el límite HTML/PHP

`dashboard.php` es el contenedor PHP actual: también resuelve la sesión, el perfil y el resto de las secciones del SPA. Para conservar esa navegación sin introducir JavaScript que cargue fragmentos, la propuesta es que el contenedor incluya un archivo estático, por ejemplo `partials/partidos.html`, con `include __DIR__ . '/partials/partidos.html';`.

En ese arreglo, **el partial no contiene PHP ni JavaScript**: el `include` solo compone el HTML de la página existente. Si el requisito fuera que ni siquiera el contenedor PHP incluya la vista, ya no sería una modificación local del partial: habría que definir una página o ruta HTML independiente y decidir cómo conserva autenticación y navegación. Eso cambia el alcance y no se asume en este plan.

## 3. Fixture exacto del bracket

La primera entrega debe representar los siete partidos documentados, respetando marcador, ronda y camino de avance. No se inventan fechas, canchas, escudos ni estados en vivo: la simulación no los necesita para el cuadro final.

| Ronda | Partido | Marcador | Ganador |
|---|---|---:|---|
| Cuartos de final | Real Madrid vs PSG | 5–2 | Real Madrid |
| Cuartos de final | Barcelona vs Borussia Dortmund | 3–1 | Barcelona |
| Cuartos de final | Bayern Munich vs Everton | 1–2 | Everton |
| Cuartos de final | Monterrey vs Tigres | 2–3 | Tigres |
| Semifinal | Real Madrid vs Barcelona | 4–2 | Real Madrid |
| Semifinal | Tigres vs Everton | 2–1 | Tigres |
| Final | Real Madrid vs Tigres | 2–3 | Tigres, campeón |

### Conexiones entre partidos

Las conexiones importan tanto como los marcadores; no se debe ordenar a los equipos por nombre ni asumir que los encuentros de la siguiente ronda siguen el orden visual de la tabla de cuartos.

| Partido siguiente | Equipo que avanza al lado local | Equipo que avanza al lado visitante |
|---|---|---|
| Semifinal 1 | Ganador de Real Madrid–PSG | Ganador de Barcelona–Borussia Dortmund |
| Semifinal 2 | Ganador de Monterrey–Tigres | Ganador de Bayern Munich–Everton |
| Final | Ganador de Semifinal 1 | Ganador de Semifinal 2 |

Así se representa la semifinal Tigres–Everton: Tigres viene del cuarto Monterrey–Tigres, mientras Everton viene del cuarto Bayern Munich–Everton. En una futura lectura de base de datos, la fuente de verdad para esas conexiones será `partido_origen_local_id` / `partido_origen_visitante_id`, no el índice del elemento en el HTML.

## 4. Alcance por entregas

### Entrega A — bracket estático

Incluye el encabezado del torneo, las tres rondas, las siete tarjetas de partido, sus marcadores, los caminos de avance, el estado visual de ganador y la identificación de Tigres como campeón. El contenido vive en HTML; presentación y conectores viven en CSS.

No incluye filtros, tabs de estado, búsqueda, botones de detalle, modal, edición, resultados en vivo, carga desde la API ni interacción de clic. La maqueta debe comunicar un cuadro terminado, no controles que aparenten funcionar.

### Entrega B — integración de lectura, posterior

Cuando se apruebe que la vista sea funcional, un módulo JavaScript en `assets/js/` podrá solicitar el bracket del torneo y renderizar los datos recibidos. Se reutilizará el endpoint existente; no se mezclará `fetch()` con el HTML ni se agregarán consultas SQL a la vista.

Esta segunda entrega requiere decidir cómo se selecciona `torneoId` y cómo el dashboard principal configura la URL base de la API. Esas decisiones no se deben resolver con un id incrustado en el markup ni suponiendo que el dashboard público usa la configuración del panel admin.

## 5. Archivos y responsabilidades propuestas

### Primera entrega estática

| Archivo | Cambio previsto | Responsabilidad que conserva |
|---|---|---|
| [dashboard.php](../../dashboard.php) | Cambiar únicamente el include del partial a `partials/partidos.html` y dejar de cargar `assets/js/partidos.js` para esta vista. | Shell PHP, autenticación existente, navegación y contenedores SPA. |
| `partials/partidos.html` (nuevo) | Markup semántico estático para encabezado, rondas y partidos. | Contenido visible de la vista, sin reglas de negocio ni acceso a datos. |
| [assets/css/pages/partidos.css](../../assets/css/pages/partidos.css) | Sustituir estilos de lista por layout del bracket, tarjetas, ganadores, conectores y comportamiento responsive. | Apariencia exclusiva de `#view-partidos`. |
| [assets/js/partidos.js](../../assets/js/partidos.js) | No borrarlo en esta etapa. Dejar de incluirlo desde el dashboard principal si ya no existe el markup que espera. | Código legacy que puede seguir siendo usado por `dashboard2.php`; confirmar antes de retirarlo. |

No se planean cambios en controladores, rutas, servicios, SQL, autenticación ni panel admin para la Entrega A.

### Integración futura

| Archivo / capa | Responsabilidad futura |
|---|---|
| `assets/js/partidos-bracket.js` (propuesta) | Obtener el torneo seleccionado, solicitar el endpoint, normalizar la respuesta y pintar estados de carga, error, vacío y éxito. |
| Cliente de API del portal (por confirmar) | Construir la URL base y normalizar errores HTTP/JSON. No duplicar la configuración privada de `admin/assets/js/api.js`. |
| [backend/routes/api.php](../routes/api.php) | Ya publica el GET del bracket. Solo se modifica si una carencia real del contrato se confirma. |
| [backend/controllers/BracketController.php](../controllers/BracketController.php) | Seguir siendo lectura de datos de bracket. La presentación y el CSS no deben filtrarse al controlador. |

## 6. Estructura de markup recomendada

El partial estático no necesita ids por partido para funcionar. Conviene que el markup exprese las rondas y partidos con elementos semánticos repetibles y clases de componente predecibles. Este fragmento ilustra una tarjeta de la final; el mismo patrón se replica para los siete partidos.

```html
<section class="partidos-view" aria-labelledby="partidos-title">
    <header class="partidos-header">
        <p class="partidos-kicker">Torneo finalizado</p>
        <h1 id="partidos-title">PASSBALLCUP</h1>
        <p>Cuadro de eliminación directa</p>
    </header>

    <div
        class="bracket-scroll"
        role="region"
        aria-label="Bracket PASSBALLCUP: cuartos de final, semifinales y final"
        tabindex="0"
    >
        <div class="bracket-board">
            <section class="bracket-round" aria-labelledby="round-final">
                <h2 class="bracket-round__title" id="round-final">Final</h2>
                <div class="bracket-round__matches">
                    <article class="bracket-match bracket-match--final">
                        <h3 class="bracket-match__title">Partido 7</h3>
                        <ol class="bracket-match__teams">
                            <li class="bracket-team">
                                <span class="bracket-team__name">Real Madrid</span>
                                <span class="bracket-team__score">2</span>
                            </li>
                            <li class="bracket-team is-winner is-champion">
                                <span class="bracket-team__name">Tigres</span>
                                <span class="bracket-team__score">3</span>
                                <span class="bracket-team__badge">Campeón</span>
                            </li>
                        </ol>
                    </article>
                </div>
            </section>
        </div>
    </div>
</section>
```

Pautas para completar el HTML:

- El wrapper externo se coloca **dentro** del `div#view-partidos` que ya existe. No se repite el id `view-partidos`.
- La lista ordenada `<ol>` hace explícito que el partido contiene dos lados; cada lado mantiene el equipo y su marcador en el mismo elemento.
- Los títulos de ronda son encabezados `<h2>` y cada tarjeta tiene un título propio. Si el número de partido no aporta lectura visible, puede usarse como texto auxiliar accesible, pero no se deben duplicar encabezados visualmente.
- `is-winner` distingue al ganador de ese partido; `is-champion` se reserva para Tigres en la final. No marcar como campeón a Tigres en rondas anteriores.
- El nombre del ganador no se comunica solo con color. Añadir texto visible como “Campeón” para la final y texto accesible para los otros ganadores, o una señal equivalente con nombre accesible.
- Las tarjetas son informativas, no botones. No añadir `tabindex`, `role="button"` ni enlaces vacíos.
- No crear un `<form>` ni controles de filtros para categorías/estados: esos controles pertenecen a la lista anterior y no tienen función en esta entrega.

## 7. Estructura visual y CSS

### Composición de escritorio

1. Encabezado compacto: nombre PASSBALLCUP, estado de torneo terminado y descriptor “Cuadro de eliminación directa”. No repetir las cuatro tarjetas estadísticas del diseño anterior.
2. Área de bracket con tres columnas: Cuartos de final, Semifinales y Final.
3. Cada tarjeta muestra los dos equipos y sus goles. El lado ganador lleva más énfasis, pero ambos equipos y marcadores permanecen legibles.
4. Conectores visibles entre las tarjetas que alimentan la siguiente ronda. El recorrido del bracket sigue la tabla de conexiones de la sección 3.
5. La final destaca a Tigres como campeón con texto, además de tratamiento visual. No basar el reconocimiento únicamente en verde, morado u otro color.

### Esqueleto CSS

Este es un punto de partida de layout, no una hoja terminada. La altura de las pistas se debe calibrar con el tamaño final de las tarjetas y los encabezados de ronda.

```css
#view-partidos .partidos-view {
    min-width: 0;
}

#view-partidos .bracket-scroll {
    max-width: 100%;
    overflow-x: auto;
    overscroll-behavior-inline: contain;
    padding: 0.25rem 0 1rem;
}

#view-partidos .bracket-board {
    --bracket-card-min: 15rem;
    --bracket-column-gap: 2.5rem;

    display: grid;
    grid-template-columns: repeat(3, minmax(var(--bracket-card-min), 1fr));
    column-gap: var(--bracket-column-gap);
    min-width: calc(3 * var(--bracket-card-min) + 2 * var(--bracket-column-gap));
    align-items: stretch;
}

#view-partidos .bracket-round__matches {
    display: grid;
    grid-template-rows: repeat(4, minmax(5.25rem, auto));
    gap: 1rem;
}

#view-partidos .bracket-round--semifinal .bracket-match:first-child {
    grid-row: 1 / span 2;
}

#view-partidos .bracket-round--semifinal .bracket-match:last-child {
    grid-row: 3 / span 2;
}

#view-partidos .bracket-round--final .bracket-match {
    grid-row: 2 / span 2;
}

#view-partidos .bracket-match {
    position: relative;
    min-width: 0;
    align-self: center;
}

#view-partidos .bracket-match__teams {
    margin: 0;
    padding: 0;
    list-style: none;
}

#view-partidos .bracket-team {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 0.5rem;
    min-height: 2.75rem;
}

#view-partidos .bracket-team__name {
    min-width: 0;
    overflow-wrap: anywhere;
}

#view-partidos .bracket-team__score {
    min-width: 1.5rem;
    text-align: end;
    font-variant-numeric: tabular-nums;
}

@media (max-width: 760px) {
    #view-partidos .bracket-board {
        /* Se conserva la geometría del bracket; el contenedor permite desplazarlo. */
        min-width: 53rem;
    }
}
```

### Conectores

Las líneas de avance son decoración del layout, no controles. Implementarlas como pseudo-elementos o como elementos vacíos `aria-hidden="true"` asociados a pares de partidos. No usar caracteres de texto (`────`, flechas repetidas) para dibujar conexiones, porque se deforman al cambiar el ancho o la fuente.

La implementación concreta debe respetar esta geometría:

- Dos cuartos conectan con el centro de su semifinal.
- Las semifinales conectan con el partido final.
- El camino de Tigres parte del cuarto 4, llega como lado local a la Semifinal 2 y llega como lado visitante a la final.
- Los conectores quedan por detrás de las tarjetas y nunca ocultan nombres ni marcadores.

Se recomienda agregar las clases de ronda en HTML (`bracket-round--quarterfinal`, `bracket-round--semifinal`, `bracket-round--final`) y clases de ubicación por partido solo si hacen falta para posicionar conectores. Evitar selectores dependientes de nombres de equipos o del texto del marcador.

### Diseño responsive

Para este cuadro de tres rondas se recomienda mantener las tres columnas en móvil y permitir desplazamiento horizontal dentro de `.bracket-scroll`. Comprimir las columnas a un ancho que corte “Borussia Dortmund” haría el bracket menos útil que el scroll deliberado.

Criterios:

- El área desplazable debe tener nombre accesible y ser enfocables con teclado (`tabindex="0"`).
- El usuario puede recorrer el bracket horizontalmente con teclado o gesto táctil sin que se desplace accidentalmente toda la página de lado.
- Las tarjetas tienen ancho mínimo estable. Nombres largos pueden partirse, no deben superponerse al marcador.
- A 320–390 px de viewport no debe aparecer una segunda barra horizontal en el `body`; el scroll horizontal pertenece solamente al bracket.
- A partir del ancho suficiente, el cuadro se ve completo sin scroll interno.

## 8. Responsabilidades por capa

### HTML

Define la estructura: título del torneo, nombres de ronda, tarjetas, equipos, goles, estados de avance y campeón. En la entrega estática, el fixture está escrito directamente en el markup como contenido de demostración. No hace consultas, no ejecuta reglas ni transforma datos.

### CSS

Define dimensiones, columnas, alineación, conectores, jerarquía visual, estados de ganador y adaptación a pantalla. Las reglas se prefijan con `#view-partidos` para no alterar listas o tarjetas de otras vistas. Se reutilizan las variables de color y borde del dashboard cuando sean compatibles, en lugar de duplicar una paleta global.

### `dashboard.php`

Sigue siendo responsable del shell autenticado y de seleccionar las vistas SPA. Su cambio para esta tarea debe limitarse a incluir el partial estático y dejar de cargar el JavaScript de los filtros antiguos desde el dashboard principal.

### JavaScript y API, solo en la fase futura

El JavaScript futuro obtiene datos, maneja carga/error y actualiza el DOM. No contiene HTML de presentación concatenado de forma dispersa ni reglas SQL. Los controladores existentes conservan acceso a datos y autorización; no deben generar markup.

No se asume que `window.PASSBALL_API` esté definido en el dashboard público: el bootstrap observado en el panel admin vive en sus archivos y no se debe acoplar por accidente a esta vista.

## 9. Integración futura con el contrato actual

El endpoint existente es `GET /api/torneos/{torneoId}/bracket`. El controlador organiza la respuesta por rondas y partidos. Entre los datos que ya incluye están:

- Por ronda: `id`, `nombre`, `orden` y `partidos`.
- Por partido: `id`, `posicion`, `estado`, `goles_local`, `goles_visitante`, `ganador_id` y objetos `equipo_local` / `equipo_visitante` con id y nombre.
- Para progresión: `origen_local` y `origen_visitante`, con el id del partido previo y su ganador.

Antes de conectar la vista, comprobar el envelope JSON real de `jsonResponse()` y manejar respuestas donde equipos, marcador o ganador todavía sean `null`. Aunque esta simulación ya terminó, la API debe seguir pudiendo representar torneos en curso.

### Pendientes reales para integrar

1. **Id de torneo:** el dashboard principal no define en esta vista cuál torneo se selecciona. Decidir entre un torneo elegido desde UI, torneo actual obtenido desde API o id recibido por la navegación. No hardcodear el id de la simulación en el markup final.
2. **Base de API:** definir un cliente de API del portal con URL base estable. `assets/js/app.js` incluye un helper genérico de `fetch`, pero no una base específica del backend; el cliente del panel admin está en otra capa y no debe importarse como si fuera el del portal.
3. **Nombre y estado del torneo:** el endpoint de bracket incluye `torneo_id`, pero el controlador mostrado no devuelve el nombre ni estado del torneo. Si el encabezado debe usar datos reales, consultar también `GET /api/torneos/{id}` o acordar otro dato de entrada; no replicar manualmente el nombre como si viniera del bracket.
4. **Logos:** el bracket actual devuelve id y nombre de equipo, no el campo `logo`. La primera versión funcional puede seguir siendo tipográfica. Solo ampliar la respuesta del controlador si mostrar logos es un requisito aprobado y la columna ya es suficiente para resolverlo; no crear una tabla/campo nuevo.
5. **Estados de interfaz:** preparar loading, error recuperable, torneo sin rondas/partidos y bracket cargado. Los errores no deben ocultarse como un bracket vacío.
6. **Ganador y progresión:** usar `ganador_id` y `origen_*` para identificar el avance. No inferir al ganador comparando goles si existe empate y definición por penales.
7. **Acciones:** este endpoint es de lectura. La vista pública no debe emitir las rutas POST/PATCH administrativas para crear partidos, cambiar resultados o finalizar encuentros.

No se necesita otro endpoint para el mockup estático. Si más adelante aparece una limitación del contrato, documentarla primero y comprobar si el controlador puede resolverla con las relaciones actuales antes de proponer cambios estructurales.

## 10. Fases y etapas de construcción

### Fase 0 — Cerrar alcance y fixture

**Etapa 0.1: confirmar superficie activa.** Usar `dashboard.php` como entrada de la entrega porque contiene el `view-partidos` enlazado desde la navegación. Revisar `dashboard2.php` solo para confirmar si es una entrada activa; no reconstruirlo por defecto.

**Etapa 0.2: fijar contenido.** Usar exactamente los siete resultados de la sección 3. Acordar que es la simulación finalizada y que no se mostrarán fechas o canchas inventadas.

**Etapa 0.3: fijar móvil.** Aprobar el desplazamiento horizontal del bracket en pantallas estrechas. La alternativa de apilar rondas verticalmente cambia la lectura del cuadro y se trataría como una decisión de diseño distinta.

**Salida de fase:** alcance estático confirmado; sin cambios en código.

### Fase 1 — Separar markup y contenido

**Etapa 1.1:** crear `partials/partidos.html` con markup semántico para encabezado, wrapper desplazable, tres rondas y siete partidos.

**Etapa 1.2:** retirar del partial anterior el array PHP, contadores, loops, condicionales de estado y markup de lista. No migrar los partidos ficticios antiguos.

**Etapa 1.3:** actualizar en `dashboard.php` solo la referencia del partial. Mantener `id="view-partidos"`, `data-target` de navegación y clases requeridas por el sistema SPA.

**Etapa 1.4:** representar ganador y campeón en el HTML sin botones ni atributos que aparenten interacción.

**Salida de fase:** el dashboard presenta contenido local y no ejecuta lógica de partido.

### Fase 2 — Construir el bracket con CSS

**Etapa 2.1:** reemplazar estilos de tabs, toolbar, contadores y filas por estilos encapsulados del encabezado y tarjetas.

**Etapa 2.2:** crear columnas de rondas y alinear semifinales con los pares correctos de cuartos; centrar la final entre las dos semifinales.

**Etapa 2.3:** dibujar conectores detrás de las tarjetas. Revisar en particular la rama Monterrey–Tigres → Tigres vs Everton y la rama Bayern–Everton → Everton.

**Etapa 2.4:** aplicar el sistema visual ya presente en el dashboard con estados legibles. El ganador no se distingue exclusivamente por color.

**Salida de fase:** bracket completo en escritorio, con progresión entendible sin explicación externa.

### Fase 3 — Adaptabilidad y accesibilidad

**Etapa 3.1:** agregar scroll horizontal local al bracket para viewport estrecho, manteniendo ancho mínimo para nombres y marcadores.

**Etapa 3.2:** verificar foco visible del contenedor, navegación por teclado y lector de pantalla: nombre de torneo, ronda, partido, equipos, resultado y campeón.

**Etapa 3.3:** verificar contraste de texto, marcador y estado de ganador, zoom del navegador y nombres largos.

**Salida de fase:** la vista se puede recorrer en desktop y móvil sin tarjetas cortadas ni información dependiente del color.

### Fase 4 — Retirar cableado obsoleto del dashboard principal

**Etapa 4.1:** quitar del HTML de `dashboard.php` la carga de `assets/js/partidos.js`, porque sus listeners corresponden a controles eliminados.

**Etapa 4.2:** no borrar ni reescribir `assets/js/partidos.js` en esta fase. Confirmar primero si `dashboard2.php` lo sigue utilizando; si se desea retirar ese dashboard, hacerlo como tarea separada y aprobada.

**Etapa 4.3:** buscar referencias a `match-tab`, `match-row`, `matchSearch`, `categoriaFilter` y `emptyResults`. No deben quedar dependencias rotas dentro de `view-partidos`.

**Salida de fase:** la vista estática no depende de scripts de filtros antiguos y las otras páginas no se afectan.

### Fase 5 — QA visual y aceptación

**Etapa 5.1:** abrir el dashboard autenticado y comprobar entrada/salida de la pestaña Partidos. Debe seguir funcionando la navegación a Inicio, Equipos, Votos, Resultados y Comunidad.

**Etapa 5.2:** probar al menos 1440 px, 1024 px, 768 px, 390 px y 320 px de ancho. Inspeccionar capturas del bracket completo y del scroll en móvil.

**Etapa 5.3:** comprobar fixture partido por partido, rama por rama, texto de campeón y ausencia de solicitudes a la API.

**Etapa 5.4:** revisar consola del navegador, errores PHP del dashboard y reglas CSS que afecten otras vistas.

**Salida de fase:** primera entrega visual aprobada; no requiere cambios de backend.

### Fase 6 — Conectar datos reales, después de aprobación

**Etapa 6.1:** cerrar las decisiones de `torneoId`, URL base, envelope JSON, selección de torneo y nombre/estado del encabezado.

**Etapa 6.2:** definir el cliente de lectura y un adaptador que convierta el payload de `BracketController` a la estructura de presentación, sin cambiar el contrato de la API solo para hacer coincidir clases CSS.

**Etapa 6.3:** reemplazar progresivamente el fixture estático por datos del endpoint, preservando loading/error/empty states y el camino de ganadores.

**Etapa 6.4:** verificar el bracket de la simulación contra la respuesta real de base de datos; probar también rondas incompletas y partidos sin equipos/resultado.

**Salida de fase:** vista funcional de solo lectura. La edición administrativa de partidos permanece fuera de esta tarea.

## 11. Criterios de aceptación de la primera entrega

- [ ] La vista activa es la sección `#view-partidos` de `dashboard.php`.
- [ ] El partial nuevo contiene HTML puro: no abre bloques PHP ni incorpora scripts.
- [ ] Las rondas visibles son Cuartos de final, Semifinales y Final.
- [ ] Se muestran exactamente cuatro cuartos, dos semifinales y una final.
- [ ] Los siete marcadores coinciden con la tabla de la sección 3.
- [ ] Las conexiones conservan Real Madrid/Barcelona y Tigres/Everton en las semifinales, y Real Madrid/Tigres en la final.
- [ ] Tigres se identifica explícitamente como campeón de la final.
- [ ] La victoria no se indica solo con color y los nombres largos caben o parten línea.
- [ ] El bracket no contiene botones decorativos, filtros sin función ni datos de partidos antiguos.
- [ ] La navegación y las demás pestañas del dashboard siguen operativas.
- [ ] El bracket puede desplazarse localmente con ratón, teclado y gesto táctil en móvil.
- [ ] No se cambian rutas, controladores, servicios, tablas ni datos de la base para esta entrega.
- [ ] La primera entrega no hace peticiones a la API.

## 12. Decisiones asumidas y puntos a confirmar antes de construir

1. **Contenido:** se muestran los marcadores de la simulación finalizada; no se dejan tarjetas vacías.
2. **Dispositivo móvil:** se mantiene el bracket horizontal con scroll local, no se transforma en una lista vertical.
3. **Identidad visual:** se conserva la identidad ya presente en PASSBALL Cup; no se introducen escudos inventados. El endpoint actual tampoco incluye logos.
4. **Entrada:** se modifica el dashboard raíz. `dashboard2.php` queda fuera hasta confirmar que se usa como entrada pública vigente.
5. **Límite de PHP:** el HTML del partial queda puro; `dashboard.php` continúa incluyéndolo como parte del shell SPA actual.
6. **Funcionalidad:** la primera entrega es solo visual. La conexión de lectura a la API queda como Fase 6 y no se mezcla con esta reconstrucción.

La única decisión bloqueante de diseño antes de ejecutar la primera fase es confirmar que el bracket con scroll horizontal en móvil es aceptable. Las demás son defaults conservadores alineados con la simulación y la arquitectura existente.
