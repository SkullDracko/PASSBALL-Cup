/* ============================================================
   PASSBALL CUP
   VISTAS DEL PANEL SOBRE LA API
   ============================================================
   Cada vista es un cascarón en admin/partials/ y se pinta desde aquí.
   Nada de esto toca la base de datos: todo pasa por backend/api.

   Convención: los partials dejan los contenedores con id, y las
   acciones se delegan con atributos data-accion para no tener que
   enganchar un listener por nodo.

   El torneo en curso se guarda en ESTADO.torneoId y se comparte entre
   Postulaciones, Votaciones, Torneo y Resultados: es lo que hacia el
   selector ?torneo_id= del legacy.
   ============================================================ */

const ESTADO = {
    torneoId: 0,
    rondaId: 0,
    torneos: [],
    administradores: null,
    cargadas: {}
};


/* ------------------------------------------------------------
   Utilidades de pintado
   ------------------------------------------------------------ */

function esc(valor) {
    const div = document.createElement("div");
    div.textContent = valor == null ? "" : String(valor);
    return div.innerHTML;
}

function porId(id) {
    return document.getElementById(id);
}

function fecha(texto, conHora) {
    if (!texto) return null;
    const d = new Date(String(texto).replace(" ", "T"));
    if (isNaN(d)) return null;
    return conHora === false
        ? d.toLocaleDateString("es-MX")
        : d.toLocaleString("es-MX");
}

function inicial(nombre) {
    return esc(String(nombre || "?").trim().charAt(0).toUpperCase());
}

/*
   Etiqueta de un jugador o usuario.

   Los 50 usuarios de la semilla (sql/inserts.sql) se insertan solo con
   matricula: nombre, appellidop y appellidom vienen vacios, y el nombre real
   esta en un comentario del propio INSERT. Por eso los selectores de este
   panel caian en un "?" cuando la columna nombre no venia.

   Aqui se compone nombre + apellidos y, si no hay nada, se cae a la matricula,
   que al menos si existe. Devuelve texto plano: esc() lo aplica quien lo pinta.
*/
function etiquetaJugador(u) {
    if (!u) return "—";
    const nombre = String(u.nombre || "").trim();
    const ap = String(u.apellidop || "").trim();
    const am = String(u.apellidom || "").trim();
    const completo = [nombre, ap, am].filter(Boolean).join(" ");
    return completo || String(u.matricula || "—");
}

/*
   El aviso va dentro de la vista activa, no en un id único del documento.

   Cada partial trae su propia caja .vista-aviso. Antes todas se llamaban
   id="vistaAviso" y getElementById devolvia siempre la primera del DOM
   (la de Participantes), de modo que un error al aprobar un equipo o al
   guardar un resultado se escribia en la vista equivocada y se perdia
   sin llegar a verse. Los id duplicados tambien son HTML invalido.
*/
function vistaAviso() {
    const activa = document.querySelector(".admin-view.active");
    return activa ? activa.querySelector(".vista-aviso") : null;
}

function aviso(mensaje, tipo) {
    const caja = vistaAviso();
    if (!caja) return;
    caja.className = "admin-alert vista-aviso " + (tipo === "ok" ? "is-ok" : "is-error");
    caja.innerHTML = mensaje ? "<strong>" + esc(mensaje) + "</strong>" : "";
    caja.hidden = !mensaje;
}

function vacio(titulo, texto) {
    return '<div class="admin-stub"><h3>' + esc(titulo) + '</h3><p>' +
           esc(texto) + '</p></div>';
}

/* Convierte saltos de linea en <br>. El texto va ya escapado con esc(), asi
   que los <br> son los unicos que se inyectan. El legacy lo hacia con el
   nl2br() de PHP al renderizar el partial. */
function nl2br(texto) {
    return String(texto).replace(/\r\n|\r|\n/g, "<br>");
}


/* ------------------------------------------------------------
   Selector de torneo, compartido por las cuatro vistas
   ------------------------------------------------------------ */

async function cargarTorneos(forzar) {

    if (ESTADO.torneos.length && !forzar) return ESTADO.torneos;

    const d = await API.get("/torneos");
    ESTADO.torneos = d.torneos || [];

    // Mismo criterio que el legacy: en_curso primero, luego programado.
    const activo = ESTADO.torneos
        .filter(t => t.estado === "en_curso" || t.estado === "programado")
        .sort((a, b) => a.estado === b.estado
            ? b.id - a.id
            : (a.estado === "en_curso" ? -1 : 1))[0];

    if (!ESTADO.torneoId && activo) {
        ESTADO.torneoId = activo.id;
    } else if (!ESTADO.torneoId && ESTADO.torneos.length) {
        ESTADO.torneoId = ESTADO.torneos[0].id;
    }

    return ESTADO.torneos;
}

function pintarSelectorTorneo() {

    document.querySelectorAll("[data-torneo-select]").forEach(sel => {

        if (sel.dataset.lleno === "1") return;
        sel.dataset.lleno = "1";

        sel.innerHTML = ESTADO.torneos.map(t =>
            '<option value="' + t.id + '"' +
            (t.id === ESTADO.torneoId ? " selected" : "") + ">" +
            esc(t.nombre) + " (" + esc(t.estado) + ")</option>"
        ).join("");
    });

    // Los formularios que crean cosas necesitan el torneo actual.
    document.querySelectorAll(
        'form[data-accion="crear-categoria"], form[data-accion="crear-ronda"]'
    ).forEach(f => {
        f.dataset.torneo = String(ESTADO.torneoId);
    });
}


/* ------------------------------------------------------------
   Nombre del administrador que aprobó una inscripción
   ------------------------------------------------------------
   /api/torneos/{id}/equipos devuelve 'aprobado_por' como id, no el
   nombre (a diferencia del legacy, que hacia el JOIN). Se resuelve
   aquí contra /api/administradores en vez de tocar ese endpoint,
   que también lo usan los jugadores.
   ------------------------------------------------------------ */

async function mapaAdmins() {

    if (ESTADO.administradores) return ESTADO.administradores;

    const d = await API.get("/administradores");
    ESTADO.administradores = {};

    (d.administradores || []).forEach(a => {
        ESTADO.administradores[a.id] = a.nombre;
    });

    return ESTADO.administradores;
}


/* ============================================================
   PARTICIPANTES
   ============================================================ */

async function cargarParticipantes() {

    const d = await API.participantes();
    const lista = d.usuarios || [];

    const sub = porId("participantesSub");
    if (sub) {
        sub.textContent = lista.length +
            " jugadores registrados en la plataforma.";
    }

    const cuerpo = porId("participantesCuerpo");
    if (!cuerpo) return;

    if (!lista.length) {
        cuerpo.innerHTML =
            '<tr><td colspan="6" class="admin-note">Sin participantes aún.</td></tr>';
        return;
    }

    cuerpo.innerHTML = lista.map(p => `
        <tr>
            <td>${p.id}</td>
            <td><strong>${esc(p.nombre || "—")}</strong></td>
            <td>${esc(p.matricula)}</td>
            <td>${p.equipo ? esc(p.equipo) : "—"}</td>
            <td>
                <span class="chip ${p.estado === "activo" ? "activo" : "inactivo"}">
                    ${p.estado === "activo" ? "Activo" : "Inactivo"}
                </span>
            </td>
            <td>${p.jugador_activo ? "Sí" : "No"}</td>
        </tr>
    `).join("");
}


/* ============================================================
   POSTULACIONES
   ============================================================ */

const CHIP_POSTULACION = {
    pendiente: "chip pendiente",
    aprobado: "chip aprobado",
    rechazado: "chip rechazado",
    retirado: "chip cancelado"
};

const LABEL_POSTULACION = {
    pendiente: "Pendiente",
    aprobado: "Aprobado",
    rechazado: "Rechazado",
    retirado: "Retirado"
};

async function cargarPostulaciones() {

    await cargarTorneos();
    pintarSelectorTorneo();

    const cont = porId("postulacionesBody");
    if (!cont) return;

    if (!ESTADO.torneoId) {
        cont.innerHTML = vacio("No hay torneos",
            "Crea al menos un torneo para gestionar postulaciones.");
        return;
    }

    const d = await API.postulaciones(ESTADO.torneoId);
    const lista = d.inscripciones || [];
    const admins = await mapaAdmins();

    if (!lista.length) {
        cont.innerHTML = vacio("Sin postulaciones",
            "Los equipos aún no se han postulado a este torneo.");
        return;
    }

    // El legacy agrupaba con FIELD(estado,...) y luego fecha_solicitud.
    const orden = { pendiente: 0, aprobado: 1, rechazado: 2, retirado: 3 };
    lista.sort((a, b) =>
        (orden[a.estado] - orden[b.estado]) ||
        String(a.fecha_solicitud).localeCompare(String(b.fecha_solicitud))
    );

    const pendientes = lista.filter(p => p.estado === "pendiente");
    const procesadas = lista.filter(p => p.estado !== "pendiente");

    let html = "";

    if (pendientes.length) {
        html += '<h2 class="admin-subsection">Pendientes (' +
                pendientes.length + ')</h2>';
        html += '<div class="admin-table-wrap mb-lg"><table class="admin-table">' +
                "<thead><tr><th>Equipo</th><th>Fecha solicitud</th>" +
                "<th>Acciones</th></tr></thead><tbody>";

        html += pendientes.map(p => `
            <tr>
                <td>
                    <div class="admin-row gap-sm">
                        ${p.equipo_logo
                            ? '<img src="' + esc(p.equipo_logo) + '" class="admin-avatar-thumb">'
                            : '<div class="admin-avatar-fallback">' +
                              inicial(p.equipo_nombre) + "</div>"}
                        <strong>${esc(p.equipo_nombre)}</strong>
                    </div>
                </td>
                <td>${esc(fecha(p.fecha_solicitud) || "—")}</td>
                <td>
                    <div class="admin-chips">
                        <button type="button" class="btn-inline ok"
                            data-accion="aprobar"
                            data-torneo="${ESTADO.torneoId}"
                            data-equipo="${p.equipo_id}">
                            ✓ Aprobar
                        </button>
                        <button type="button" class="admin-btn ghost btn-inline danger"
                            data-accion="rechazar"
                            data-torneo="${ESTADO.torneoId}"
                            data-equipo="${p.equipo_id}">
                            ✕ Rechazar
                        </button>
                    </div>
                </td>
            </tr>
        `).join("");

        html += "</tbody></table></div>";
    }

    if (procesadas.length) {
        html += '<h2 class="admin-subsection">Procesadas (' +
                procesadas.length + ')</h2>';
        html += '<div class="admin-table-wrap"><table class="admin-table">' +
                "<thead><tr><th>Equipo</th><th>Estado</th><th>Fecha</th>" +
                "<th>Aprobado por</th></tr></thead><tbody>";

        html += procesadas.map(p => `
            <tr>
                <td><strong>${esc(p.equipo_nombre)}</strong></td>
                <td>
                    <span class="${CHIP_POSTULACION[p.estado] || ""}">
                        ${esc(LABEL_POSTULACION[p.estado] || p.estado)}
                    </span>
                </td>
                <td>${esc(fecha(p.fecha_aprobacion) || "—")}</td>
                <td>${esc(admins[p.aprobado_por] || "—")}</td>
            </tr>
        `).join("");

        html += "</tbody></table></div>";
    }

    cont.innerHTML = html;
}


/* ============================================================
   VOTACIONES
   ============================================================ */

const NOMBRE_EVENTO = {
    gol: "⚽ Gol",
    autogol: "😬 Autogol",
    penal_anotado: "🎯 Penal",
    tarjeta_amarilla: "🟨 Amarilla",
    tarjeta_roja: "🟥 Roja"
};

function nombreAjuste(aj) {
    return aj.jugador_nombre || aj.equipo_nombre || "—";
}

async function cargarVotaciones() {

    await cargarTorneos();
    pintarSelectorTorneo();

    const cont = porId("votacionesBody");
    if (!cont) return;

    if (!ESTADO.torneoId) {
        cont.innerHTML = vacio("Sin torneo", "Crea un torneo primero.");
        return;
    }

    const [cats, ajustes, jugadores] = await Promise.all([
        API.categoriasVoto(ESTADO.torneoId),
        API.candidatosVoto(ESTADO.torneoId),
        API.jugadoresDelTorneo(ESTADO.torneoId)
    ]);

    const categorias   = cats.categorias || [];
    const porCategoria = ajustes.ajustes || {};
    const listaJugadores = jugadores.jugadores || [];

    // Equipos aprobados del torneo: los selectores de tipo "equipo".
    const ins = await API.postulaciones(ESTADO.torneoId);
    const equipos = (ins.inscripciones || [])
        .filter(i => i.estado === "aprobado");

    if (!categorias.length) {
        cont.innerHTML = vacio("Sin categorías de votación",
            "Crea la primera categoría del torneo.");
        return;
    }

    cont.innerHTML = categorias.map(cat => {

        const listaAjustes = porCategoria[cat.id] || [];
        const esJugador = cat.tipo === "jugador";

        const opciones = esJugador
            ? listaJugadores.map(j =>
                '<option value="' + j.id + '">' +
                esc((j.nombre || "?") + " (" + (j.equipo || "sin equipo") + ")") +
                "</option>").join("")
            : equipos.map(e =>
                '<option value="' + e.equipo_id + '">' +
                esc(e.equipo_nombre) + "</option>").join("");

        const selects = `
            <select name="${esJugador ? "jugador_id" : "equipo_id"}">
                <option value="">— Seleccionar —</option>
                ${opciones}
            </select>
        `;

        return `
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <strong class="small-title">${esc(cat.nombre)}</strong>
                    <div class="admin-meta">
                        ${esc(cat.clave)} · ${esc(cat.tipo)} ·
                        modo ${esc(cat.modo_candidatos)} ·
                        ${cat.total_votos} votos
                    </div>
                </div>
                <div class="admin-row gap-sm">
                    <span class="chip ${cat.estado === "abierta" ? "finalizado" : "programado"}">
                        ${cat.estado === "abierta" ? "Abierta" : "Cerrada"}
                    </span>
                    <button type="button" class="admin-btn ghost mini"
                        data-accion="toggle-categoria"
                        data-torneo="${ESTADO.torneoId}"
                        data-categoria="${cat.id}"
                        data-estado="${cat.estado === "abierta" ? "cerrada" : "abierta"}">
                        ${cat.estado === "abierta" ? "Cerrar" : "Abrir"}
                    </button>
                    <button type="button" class="admin-btn ghost mini danger"
                        data-accion="eliminar-categoria"
                        data-torneo="${ESTADO.torneoId}"
                        data-categoria="${cat.id}">
                        Eliminar
                    </button>
                </div>
            </div>

            <details class="mt-sm">
                <summary class="admin-label-mini">Editar categoría</summary>
                <form class="admin-form tight" data-accion="editar-categoria"
                      data-torneo="${ESTADO.torneoId}" data-categoria="${cat.id}">
                    <div class="field admin-field-lg">
                        <label>Nombre</label>
                        <input type="text" name="nombre" maxlength="100"
                               value="${esc(cat.nombre)}" required>
                    </div>
                    <div class="field admin-field-md">
                        <label>Tipo</label>
                        <select name="tipo">
                            ${["jugador", "equipo"].map(t =>
                                '<option value="' + t + '"' +
                                (cat.tipo === t ? " selected" : "") + ">" +
                                esc(t) + "</option>").join("")}
                        </select>
                    </div>
                    <div class="field admin-field-md">
                        <label>Modo candidatos</label>
                        <select name="modo_candidatos">
                            ${["automatico", "manual"].map(m =>
                                '<option value="' + m + '"' +
                                (cat.modo_candidatos === m ? " selected" : "") + ">" +
                                esc(m) + "</option>").join("")}
                        </select>
                    </div>
                    <div class="field admin-field-xs">
                        <label>Orden</label>
                        <input type="number" name="orden" min="0"
                               value="${cat.orden}">
                    </div>
                    <button type="submit" class="admin-btn ghost">Guardar</button>
                    <p class="small-note">La clave no se edita. Cambiar el tipo
                        falla si ya hay candidatos o votos.</p>
                </form>
            </details>

            ${listaAjustes.length ? `
            <div class="mt-sm">
                <div class="admin-label-mini">Ajustes de candidatos</div>
                <div class="admin-chips">
                    ${listaAjustes.map(aj => `
                        <span class="admin-chip-inline">
                            ${aj.ajuste === "incluir" ? "+" : "−"}&nbsp;${esc(nombreAjuste(aj))}
                            <button type="button" class="admin-remove"
                                data-accion="quitar-candidato"
                                data-torneo="${ESTADO.torneoId}"
                                data-categoria="${cat.id}"
                                data-ajuste="${aj.ajuste_id}">×</button>
                        </span>
                    `).join("")}
                </div>
            </div>` : ""}

            <div class="bracket admin-row gap-sm mt-sm">
                <div class="admin-form tight">
                    <input type="hidden" name="tipo_candidato" value="${esc(cat.tipo)}">
                    <label class="admin-label-inline">Incluir candidato</label>
                    ${selects}
                    <button type="button" class="admin-btn ghost mini"
                        data-accion="agregar-candidato"
                        data-torneo="${ESTADO.torneoId}"
                        data-categoria="${cat.id}"
                        data-tipo="${esc(cat.tipo)}"
                        data-select="incluir">Incluir
                    </button>
                </div>
                <div class="admin-form tight">
                    <input type="hidden" name="tipo_candidato" value="${esc(cat.tipo)}">
                    <label class="admin-label-inline">Excluir candidato</label>
                    ${selects}
                    <button type="button" class="admin-btn ghost mini danger"
                        data-accion="agregar-candidato"
                        data-torneo="${ESTADO.torneoId}"
                        data-categoria="${cat.id}"
                        data-tipo="${esc(cat.tipo)}"
                        data-select="excluir">Excluir
                    </button>
                </div>
            </div>
        </div>`;
    }).join("");
}


/* ============================================================
   TORNEO Y RONDAS
   ============================================================ */

const LABEL_ESTADO_TORNEO = {
    programado: "Programado",
    en_curso: "En curso",
    finalizado: "Finalizado",
    cancelado: "Cancelado"
};

async function cargarTorneo() {

    await cargarTorneos();
    pintarSelectorTorneo();

    const cont = porId("torneoBody");
    if (!cont) return;

    if (!ESTADO.torneos.length) {
        cont.innerHTML = vacio("Aún no hay torneos",
            "Crea un torneo desde la base de datos para comenzar.");
        return;
    }

    const torneo = ESTADO.torneos.find(t => t.id === ESTADO.torneoId);
    if (!torneo) {
        cont.innerHTML = vacio("Torneo no encontrado", "Elige otro torneo.");
        return;
    }

    const [rondas, partidos, todosEquipos] = await Promise.all([
        API.rondas(ESTADO.torneoId),
        API.partidosDeTorneo(ESTADO.torneoId),
        API.get("/equipos?estado=activo")
    ]);

    const listaRondas   = rondas.rondas || [];
    const listaPartidos = partidos.partidos || [];
    const opcionesEquipo = (todosEquipos.equipos || []).map(e =>
        '<option value="' + e.id + '">' + esc(e.nombre) + "</option>").join("");

    if (!listaRondas.length) {
        cont.innerHTML = `
            <form class="admin-form" data-accion="crear-ronda"
                  data-torneo="${ESTADO.torneoId}">
                <div class="field">
                    <label for="rondaNombre">Nueva ronda</label>
                    <input type="text" id="rondaNombre" name="nombre"
                           placeholder="Ej. Octavos de final" required>
                </div>
              <div class="field admin-field-xs">
                      <label for="rondaOrden">Orden</label>
                      <input type="number" id="rondaOrden" name="orden" min="1" required>
                  </div>
                  <button type="submit" class="admin-btn">+ Crear ronda</button>
              </form>
              ${vacio("Sin rondas todavía",
                    "Crea la primera ronda del torneo con el formulario de arriba.")}`;
        return;
    }

    const cuerpoRondas = listaRondas.map(r => {

        const deRonda = listaPartidos
            .filter(p => p.ronda_id === r.id)
            .sort((a, b) => (a.posicion - b.posicion) || (a.id - b.id));

        const partidos = deRonda.length
            ? deRonda.map(p => `
                <div class="match-card">
                    <div class="vs">
                        <span class="teamx" title="${esc(p.equipo_local_nombre || "")}">
                            ${esc(p.equipo_local_nombre || "—")}
                        </span>
                        ${p.goles_local !== null && p.goles_local !== undefined
                            ? '<span class="score">' + p.goles_local + " - " +
                              p.goles_visitante + "</span>"
                            : '<span class="faint">vs</span>'}
                        <span class="teamx text-right"
                              title="${esc(p.equipo_visitante_nombre || "")}">
                            ${esc(p.equipo_visitante_nombre || "—")}
                        </span>
                    </div>
                    <div class="meta">
                        <span>📅 ${p.fecha_hora
                            ? esc(fecha(p.fecha_hora))
                            : "Sin fecha"}</span>
                        <span>${esc(p.cancha || "")}</span>
                        <span class="chip ${esc(p.estado)}">
                            ${esc(String(p.estado).replace(/_/g, " "))}
                        </span>
                    </div>
                </div>`).join("")
            : '<p class="muted-note">Sin partidos.</p>';

        return `
        <div class="round-col">
            <h3>${esc(r.nombre)}</h3>
            <div class="round-order">Ronda ${r.orden}</div>
            ${partidos}
            <form class="admin-form round-add-form" data-accion="crear-partido"
                  data-ronda="${r.id}" data-torneo="${ESTADO.torneoId}">
                <select name="equipo_local_id" required>
                    <option value="">— Equipo local —</option>${opcionesEquipo}
                </select>
                <select name="equipo_visitante_id" required>
                    <option value="">— Equipo visitante —</option>${opcionesEquipo}
                </select>
                <input type="datetime-local" name="fecha_hora">
                <input type="text" name="cancha" placeholder="Cancha (opcional)">
                <input type="number" name="posicion" placeholder="Posición (auto)" min="1">
                <button type="submit" class="admin-btn">+ Agregar partido</button>
            </form>
        </div>`;
    }).join("");

    cont.innerHTML = `
        <form class="admin-form" data-accion="crear-ronda"
              data-torneo="${ESTADO.torneoId}">
            <div class="field">
                <label for="rondaNombre">Nueva ronda</label>
                <input type="text" id="rondaNombre" name="nombre"
                       placeholder="Ej. Octavos de final" required>
            </div>
              <div class="field admin-field-xs">
                  <label for="rondaOrden">Orden</label>
                  <input type="number" id="rondaOrden" name="orden" min="1" required>
              </div>
            <button type="submit" class="admin-btn">+ Crear ronda</button>
        </form>
        <div class="bracket">${cuerpoRondas}</div>`;
}


/* ============================================================
   RESULTADOS
   ============================================================ */

const POSICIONES = ["portero", "defensa", "mediocampo", "delantero"];

/*
   convocatoria de un partido, agrupada por equipo y separada en titulares y
   suplentes. Las 84 filas de partido_convocados ya estaban en la base desde
   la siembra y ninguna pantalla las mostraba.

   Los bloques se generan desde los dos equipos del partido, no desde los
   equipos que aparecen en la lista: si se vacia una convocatoria entera
   quedan dos equipos sin bloque, y sin bloque no hay formulario de alta, y
   el partido ya no se puede reconstruir desde el panel.
*/
function pintarConvocatoria(partido, lista, miembros) {

    const porEquipo = new Map();
    lista.forEach(c => {
        if (!porEquipo.has(c.equipo_id)) porEquipo.set(c.equipo_id, []);
        porEquipo.get(c.equipo_id).push(c);
    });

    const equipos = [
        { id: partido.equipo_local_id, nombre: partido.equipo_local_nombre },
        { id: partido.equipo_visitante_id, nombre: partido.equipo_visitante_nombre }
    ].filter(e => e.id);

    const fila = (c, tipo) => `
        <div class="admin-row gap-sm">
            <span class="post-avatar">${inicial(etiquetaJugador(c))}</span>
            <span class="side">${esc(etiquetaJugador(c))}</span>
            <span class="chip-soft">${esc(c.posicion)}</span>
            <span class="small-note">${esc(c.matricula)}</span>
            <button type="button" class="admin-btn ghost mini"
                    data-accion="alternar-titular"
                    data-partido="${partido.id}" data-jugador="${c.jugador_id}"
                    data-titular="${tipo === "titular" ? 0 : 1}">
                ${tipo === "titular" ? "A banco" : "A titular"}
            </button>
            <button type="button" class="admin-btn ghost mini danger"
                    data-accion="quitar-convocado"
                    data-partido="${partido.id}" data-jugador="${c.jugador_id}">
                Quitar
            </button>
        </div>`;

    const alta = (equipoId) => {
        const yaConvocados = new Set(
            lista.filter(c => c.equipo_id === equipoId).map(c => c.jugador_id)
        );
        const disponibles = (miembros[equipoId] || []).filter(m => !yaConvocados.has(m.id));
        if (!disponibles.length) {
            return '<p class="admin-note">Sin miembros activos sin convocar.</p>';
        }
        return `
            <form class="admin-form tight" data-accion="convocar-jugador"
                  data-partido="${partido.id}" data-equipo="${equipoId}">
                <div class="field admin-field-wide">
                    <label>Jugador</label>
                    <select name="jugador_id" required>
                        <option value="">— Seleccionar —</option>
                        ${disponibles.map(m =>
                            '<option value="' + m.id + '">' +
                            esc(etiquetaJugador(m)) + " (" + esc(m.matricula || "") +
                            ")</option>").join("")}
                    </select>
                </div>
                <div class="field admin-field-md">
                    <label>Posición</label>
                    <select name="posicion">
                        ${POSICIONES.map(p => '<option value="' + p + '">' + esc(p) + "</option>").join("")}
                    </select>
                </div>
                <label class="admin-check">
                    <input type="checkbox" name="titular" value="1" checked> Titular
                </label>
                <button type="submit" class="admin-btn ghost">+ Convocar</button>
            </form>`;
    };

    const bloques = equipos.map(eq => {
        const filas = porEquipo.get(eq.id) || [];
        const titulares = filas.filter(c => c.titular);
        const banco = filas.filter(c => !c.titular);
        return `
            <div class="mb-sm">
                <div class="feed-head">
                    <h4>${esc(eq.nombre || ("Equipo " + eq.id))}</h4>
                    <span class="feed-count">${titulares.length} + ${banco.length}</span>
                </div>
                <div class="admin-stack-sm">
                    ${titulares.length
                        ? '<p class="small-note">Titulares</p>' + titulares.map(c => fila(c, "titular")).join("")
                        : '<p class="admin-note">Sin titulares.</p>'}
                    ${banco.length
                        ? '<p class="small-note">Suplentes</p>' + banco.map(c => fila(c, "banco")).join("")
                        : ""}
                </div>
                ${alta(eq.id)}
            </div>`;
    }).join("");

    return `
        <div class="mb-sm">
            <div class="feed-head">
                <h4>Convocatoria</h4>
                <span class="feed-count">${lista.length}</span>
            </div>
            ${lista.length ? "" : '<p class="admin-note">Sin convocatoria para este partido.</p>'}
            ${bloques}
        </div>`;
}

/*
   estadisticas de portero del partido. Las 14 filas de
   partido_estadisticas_portero tampoco las mostraba nadie.

   El backend solo acepta a alguien que este en partido_convocados con
   posicion "portero" (verificarPorteroConvocado), asi que el selector de
   alta se arma con esa misma lista en vez de con los miembros del equipo: si
   se ofreciera a un defensa, el POST rebotaria con 422.
*/
function pintarPorteros(partido, lista, convocados) {

    const nombreDe = (fila) =>
        etiquetaJugador(convocados.find(c => c.jugador_id === fila.jugador_id) || fila);

    const filas = lista.map(pk => `
        <div class="admin-row gap-sm">
            <span class="post-avatar">${inicial(nombreDe(pk))}</span>
            <span class="side">${esc(nombreDe(pk))}</span>
            <span class="small-note">${esc(pk.matricula)}</span>
            <form class="admin-row gap-sm" data-accion="actualizar-portero"
                  data-partido="${partido.id}" data-jugador="${pk.jugador_id}">
                <label class="sr-only" for="atajadas-${partido.id}-${pk.jugador_id}">Atajadas</label>
                <input class="admin-input admin-stat" type="number" min="0" max="32767"
                       id="atajadas-${partido.id}-${pk.jugador_id}"
                       name="atajadas" value="${pk.atajadas}" title="Atajadas">
                <label class="sr-only" for="goles-${partido.id}-${pk.jugador_id}">Goles recibidos</label>
                <input class="admin-input admin-stat" type="number" min="0" max="32767"
                       id="goles-${partido.id}-${pk.jugador_id}"
                       name="goles_recibidos" value="${pk.goles_recibidos}" title="Goles recibidos">
                <button type="submit" class="admin-btn ghost mini">Guardar</button>
            </form>
            <button type="button" class="admin-btn ghost mini danger"
                    data-accion="quitar-portero"
                    data-partido="${partido.id}" data-jugador="${pk.jugador_id}">
                Quitar
            </button>
        </div>`).join("");

    const conStats = new Set(lista.map(pk => pk.jugador_id));
    const sinStats = convocados
        .filter(c => c.posicion === "portero" && !conStats.has(c.jugador_id));

    const altaReal = sinStats.length === 0
        ? '<p class="admin-note">Todos los porteros convocados tienen estadísticas.</p>'
        : `<p class="admin-note">Porteros convocados sin estadísticas: ${sinStats.map(c =>
            esc(etiquetaJugador(c))).join(", ")}</p>`;

    return `
        <div class="mb-sm">
            <div class="feed-head">
                <h4>Porteros</h4>
                <span class="feed-count">${lista.length}</span>
            </div>
            ${lista.length ? '<div class="admin-stack-sm">' + filas + "</div>" : ""}
            ${altaReal}
            ${sinStats.length ? `
            <form class="admin-form tight" data-accion="registrar-portero"
                  data-partido="${partido.id}">
                <div class="field admin-field-wide">
                    <label>Portero</label>
                    <select name="jugador_id" required>
                        <option value="">— Seleccionar —</option>
                        ${sinStats.map(c =>
                            '<option value="' + c.jugador_id + '">' +
                            esc(etiquetaJugador(c)) + " (" + esc(c.matricula) +
                            ")</option>").join("")}
                    </select>
                </div>
                <div class="field admin-field-sm">
                    <label>Atajadas</label>
                    <input type="number" name="atajadas" min="0" max="32767" value="0" required>
                </div>
                <div class="field admin-field-sm">
                    <label>Goles recibidos</label>
                    <input type="number" name="goles_recibidos" min="0" max="32767" value="0" required>
                </div>
                <button type="submit" class="admin-btn ghost">+ Registrar stats</button>
            </form>` : ""}
        </div>`;
}

async function cargarResultados() {

    await cargarTorneos();
    pintarSelectorTorneo();

    const cont = porId("resultadosBody");
    if (!cont) return;

    if (!ESTADO.torneoId) {
        cont.innerHTML = vacio("Sin torneo", "Crea un torneo primero.");
        return;
    }

    const rondas = await API.rondas(ESTADO.torneoId);
    const listaRondas = rondas.rondas || [];

    const selRonda = porId("resultadosRonda");
    if (selRonda) {
        selRonda.innerHTML = listaRondas.map(r =>
            '<option value="' + r.id + '"' +
            (r.id === ESTADO.rondaId ? " selected" : "") + ">" +
            esc(r.nombre) + "</option>").join("");
    }

    if (!ESTADO.rondaId && listaRondas.length) {
        ESTADO.rondaId = listaRondas[0].id;
        if (selRonda) selRonda.value = String(ESTADO.rondaId);
    }

    if (!ESTADO.rondaId) {
        cont.innerHTML = vacio("Sin rondas",
            "Crea una ronda en «Torneo y Rondas».");
        return;
    }

    const partidos = await API.partidosDeTorneo(ESTADO.torneoId);
    const deRonda = (partidos.partidos || [])
        .filter(p => p.ronda_id === ESTADO.rondaId)
        .sort((a, b) => (a.posicion - b.posicion) || (a.id - b.id));

    if (!deRonda.length) {
        cont.innerHTML = vacio("Sin partidos en esta ronda",
            "Crea partidos en la sección «Torneo y Rondas».");
        return;
    }

    // Eventos y miembros: se piden una sola vez por equipo/partido y se cachean.
    const eventos = {};
    await Promise.all(deRonda.map(async p => {
        const d = await API.eventosDePartido(p.id);
        eventos[p.id] = d.eventos || [];
    }));

    const miembros = {};
    const idsEquipo = new Set();
    deRonda.forEach(p => {
        if (p.equipo_local_id) idsEquipo.add(p.equipo_local_id);
        if (p.equipo_visitante_id) idsEquipo.add(p.equipo_visitante_id);
    });
    await Promise.all([...idsEquipo].map(async id => {
        const d = await API.miembrosDeEquipo(id);
        miembros[id] = d.miembros || [];
    }));

    // Convocatorias: 84 filas en partido_convocados que hasta ahora no las
    // mostraba ninguna pantalla. Se piden por partido, en paralelo.
    const convocados = {};
    await Promise.all(deRonda.map(async p => {
        const d = await API.convocadosDePartido(p.id);
        convocados[p.id] = d.convocados || [];
    }));

    // 14 filas en partido_estadisticas_portero, tampoco mostradas hasta ahora.
    const porteros = {};
    await Promise.all(deRonda.map(async p => {
        const d = await API.porterosDePartido(p.id);
        porteros[p.id] = d.porteros || [];
    }));

    cont.innerHTML = deRonda.map(p => {

        const tieneMarcador =
            p.goles_local !== null && p.goles_local !== undefined;

        const opcionesGanador = [
            { id: p.equipo_local_id, nombre: p.equipo_local_nombre, lado: "local" },
            { id: p.equipo_visitante_id, nombre: p.equipo_visitante_nombre, lado: "visitante" }
        ].filter(g => g.id).map(g =>
            '<option value="' + g.id + '"' +
            (p.ganador_id === g.id ? " selected" : "") + ">" +
            esc(g.nombre || "—") + "</option>").join("");

        const num = (v) => (v === null || v === undefined ? "" : v);

        const eventosHtml = (eventos[p.id] || []).map(ev => `
            <span class="chip-soft">
                ${esc(NOMBRE_EVENTO[ev.tipo] || ev.tipo)} ·
                ${esc(ev.jugador_nombre)}
                ${ev.minuto !== null && ev.minuto !== undefined
                    ? " · " + ev.minuto + "'" : ""}
            </span>`).join("");

        const formsEquipo = [
            { id: p.equipo_local_id, lado: "local" },
            { id: p.equipo_visitante_id, lado: "visitante" }
        ].filter(e => e.id).map(e => `
            <form class="admin-form tight" data-accion="registrar-evento"
                  data-partido="${p.id}" data-equipo="${e.id}">
                <div class="field admin-field-full">
                    <label>Jugador · Equipo ${esc(e.lado)}</label>
                    <select name="jugador_id" required>
                        <option value="">— Seleccionar —</option>
                        ${(miembros[e.id] || []).map(j =>
                            '<option value="' + j.id + '">' +
                            esc(etiquetaJugador(j)) + "</option>").join("")}
                    </select>
                </div>
                <div class="field admin-field-md">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="gol">⚽ Gol</option>
                        <option value="penal_anotado">🎯 Penal</option>
                        <option value="autogol">😬 Autogol</option>
                        <option value="tarjeta_amarilla">🟨 Amarilla</option>
                        <option value="tarjeta_roja">🟥 Roja</option>
                    </select>
                </div>
                <div class="field admin-field-sm">
                    <label>Minuto</label>
                    <input type="number" name="minuto" min="1" max="120" placeholder="—">
                </div>
                <button type="submit" class="admin-btn ghost">+ Registrar</button>
            </form>`).join("");

        return `
        <div class="admin-card">
            <div class="admin-card-head">
                <div class="admin-scoreline">
                    <span class="side">${esc(p.equipo_local_nombre || "—")}</span>
                    <span class="admin-score">${
                        tieneMarcador ? p.goles_local + " - " + p.goles_visitante : "vs"
                    }</span>
                    <span class="side away">${esc(p.equipo_visitante_nombre || "—")}</span>
                </div>
                <span class="chip ${esc(p.estado)}">
                    ${esc(String(p.estado).replace(/_/g, " "))}
                </span>
            </div>

            <form class="admin-form" data-accion="guardar-resultado"
                  data-partido="${p.id}">
                <div class="field admin-field-sm">
                    <label>Goles local</label>
                    <input type="number" name="goles_local" min="0"
                           value="${num(p.goles_local)}">
                </div>
                <div class="field admin-field-sm">
                    <label>Goles visit.</label>
                    <input type="number" name="goles_visitante" min="0"
                           value="${num(p.goles_visitante)}">
                </div>
                <div class="field admin-field-sm">
                    <label>Penales local</label>
                    <input type="number" name="penales_local" min="0"
                           value="${num(p.penales_local)}">
                </div>
                <div class="field admin-field-sm">
                    <label>Penales visit.</label>
                    <input type="number" name="penales_visitante" min="0"
                           value="${num(p.penales_visitante)}">
                </div>
                <div class="field admin-field-wide">
                    <label>Ganador</label>
                    <select name="ganador_id">
                        <option value="0">— Sin definir —</option>
                        ${opcionesGanador}
                    </select>
                </div>
                <div class="field admin-field-md">
                    <label>Estado</label>
                    <select name="estado">
                        ${["programado", "en_curso", "finalizado", "cancelado"].map(es =>
                            '<option value="' + es + '"' +
                            (p.estado === es ? " selected" : "") + ">" +
                            esc(es.replace(/_/g, " ")) + "</option>").join("")}
                    </select>
                </div>
                <button type="submit" class="admin-btn">Guardar resultado</button>
            </form>

            ${eventosHtml ? '<div class="mb-sm small-note">' + eventosHtml + "</div>" : ""}

            <div class="bracket admin-row gap-sm">${formsEquipo}</div>

            ${pintarConvocatoria(p, convocados[p.id] || [], miembros)}

            ${pintarPorteros(p, porteros[p.id] || [], convocados[p.id] || [])}
        </div>`;
    }).join("");
}


/* ============================================================
   COMUNIDAD
   ============================================================
   La ultima vista que quedaba fuera de la API. El partial hacia SQL
   directo y el POST lo recogia admin/controllers/comunidad.php, que
   avisaba con $_SESSION['flash_*'].

   La tabla posts la crea sql/migracion_admin.sql, no bd_propuesta.sql.
   Si no esta, AdminComunidadController responde 503 con un mensaje que
   lo dice, y se muestra tal cual en vez de un error generico.
   ============================================================ */

function tiempoRelativo(fecha) {

    if (!fecha) return "";

    const d = new Date(String(fecha).replace(" ", "T"));
    if (isNaN(d.getTime())) return "";

    const seg = Math.floor((Date.now() - d.getTime()) / 1000);

    if (seg < 60)    return "Hace un momento";
    if (seg < 3600)  return "Hace " + Math.floor(seg / 60) + " min";
    if (seg < 86400) return "Hace " + Math.floor(seg / 3600) + " h";
    if (seg < 604800) return "Hace " + Math.floor(seg / 86400) + " d";

    return d.toLocaleDateString("es-MX", { day: "2-digit", month: "short", year: "numeric" });
}

async function cargarComunidad() {

    const cont = porId("view-comunidad");
    if (!cont) return;

    const lista = cont.querySelector("[data-comunidad-lista]");
    const total = cont.querySelector("[data-comunidad-total]");

    const d = await API.listarPosts();
    const posts = d.posts || [];

    if (total) total.textContent = String(posts.length);

    if (!lista) return;

    if (!posts.length) {
        lista.innerHTML = vacio(
            "No hay publicaciones",
            "Usa el formulario para publicar la primera novedad."
        );
        return;
    }

    lista.innerHTML = posts.map(p => {

        const autor = esc(p.autor || "—");
        const fijado = p.fijado ? " pinned" : "";

        const imagen = p.imagen_url
            ? '<div class="post-media"><img src="' + esc(p.imagen_url) +
              '" alt="Imagen de la publicación"></div>'
            : "";

        const pin = p.fijado
            ? '<span class="post-pin"><i class="fa-solid fa-thumbtack"></i> FIJADO</span>'
            : "";

        return '<article class="post' + fijado + '">' +
            '<span class="post-avatar">' + inicial(p.autor) + '</span>' +
            '<div class="post-body">' +
                '<div class="post-meta">' +
                    '<strong>' + autor + '</strong>' +
                    '<span class="dot">&bull;</span>' +
                    '<span class="post-time">' + esc(tiempoRelativo(p.fecha)) + '</span>' +
                    pin +
                '</div>' +
                '<h4 class="post-title">' + esc(p.titulo) + '</h4>' +
                '<p class="post-text">' + nl2br(esc(p.contenido)) + '</p>' +
                imagen +
                '<div class="post-foot">' +
                    '<span class="post-likes"><i class="fa-solid fa-thumbs-up"></i> ' +
                        (Number(p.likes) || 0) + '</span>' +
                    '<div class="post-acts">' +
                        '<button type="button" class="admin-btn ghost mini" ' +
                            'data-accion="toggle-fijado" data-post="' + p.id + '">' +
                            '<i class="fa-solid fa-thumbtack"></i> ' +
                            (p.fijado ? "Desfijar" : "Fijar") +
                        '</button>' +
                        '<button type="button" class="admin-btn ghost mini danger" ' +
                            'data-accion="eliminar-post" data-post="' + p.id + '">' +
                            'Eliminar</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</article>';

    }).join("");
}


/* ============================================================
   CARGA DE VISTAS
   ============================================================ */

const VISTAS = {
    "view-participantes": cargarParticipantes,
    "view-postulaciones": cargarPostulaciones,
    "view-votaciones":    cargarVotaciones,
    "view-torneo":        cargarTorneo,
    "view-resultados":    cargarResultados,
    "view-comunidad":     cargarComunidad
};

/* Limpia el aviso de una vista concreta. Se usa al (re)cargarla para que
   no quede pegado el mensaje de la visita anterior. Se busca por viewId y
   no por la vista activa, que al recargar podria no ser la misma. */
function limpiarAviso(viewId) {
    const cont = porId(viewId);
    const caja = cont ? cont.querySelector(".vista-aviso") : null;
    if (!caja) return;
    caja.innerHTML = "";
    caja.hidden = true;
}

async function cargarVista(viewId) {

    const fn = VISTAS[viewId];
    if (!fn) return;

    limpiarAviso(viewId);

    try {
        await fn();
        ESTADO.cargadas[viewId] = true;
    } catch (err) {
        console.error("Error cargando " + viewId + ":", err);
        const cont = porId(viewId);
        const destino = cont && cont.querySelector(".cargando");
        if (destino) {
            destino.innerHTML = vacio("No se pudo cargar",
                err.message || "Error desconocido");
        }
    }
}


/* ============================================================
   ESCRITURAS
   ============================================================
   Delegación: cualquier elemento con data-accion dispara su
   operación contra la API y recarga la vista.
   ============================================================ */

document.addEventListener("click", async function (e) {

    const btn = e.target.closest("[data-accion]");
    if (!btn || btn.tagName === "FORM") return;

    const accion = btn.dataset.accion;
    if (!accion || accion.startsWith("crear-")) return;   // esas van por submit

    e.preventDefault();

    const torneoId = btn.dataset.torneo;
    const catId = btn.dataset.categoria;

    try {
        btn.disabled = true;

        switch (accion) {

            case "aprobar":
                await API.aprobarPostulacion(torneoId, btn.dataset.equipo);
                aviso("Equipo aprobado.", "ok");
                break;

            case "rechazar":
                // Sin motivo: la columna ya existe pero la API no la escribe
                // todavia. Pendiente, ver sql/etapas_conexion_admin_api.md.
                if (!confirm("¿Rechazar esta postulación?")) return;
                await API.rechazarPostulacion(torneoId, btn.dataset.equipo);
                aviso("Equipo rechazado.", "ok");
                break;

            case "toggle-categoria":
                // El endpoint es un set, no un toggle: exige {estado} en el
                // cuerpo. El boton lleva el estado destino en data-estado.
                await API.cambiarEstadoCategoria(torneoId, catId, btn.dataset.estado);
                break;

            case "eliminar-categoria":
                if (!confirm("¿Eliminar esta categoría y sus votos?")) return;
                await API.eliminarCategoria(torneoId, catId);
                break;

            case "agregar-candidato": {

                // El <select> está justo antes del botón.
                const sel = btn.parentElement.querySelector("select");
                const valor = sel ? sel.value : "";
                if (!valor) { aviso("Elige un candidato.", "error"); return; }

                const esJugador = btn.dataset.tipo === "jugador";
                const cuerpo = esJugador
                    ? { jugador_id: Number(valor), ajuste: btn.dataset.select }
                    : { equipo_id: Number(valor), ajuste: btn.dataset.select };

                await API.agregarCandidato(torneoId, catId, cuerpo);
                break;
            }

            case "quitar-candidato":
                await API.excluirCandidato(torneoId, catId, btn.dataset.ajuste);
                break;

            case "toggle-fijado":
                await API.toggleFijadoPost(btn.dataset.post);
                break;

            case "eliminar-post":
                if (!confirm("¿Eliminar esta publicación?")) return;
                await API.eliminarPost(btn.dataset.post);
                break;

            case "alternar-titular":
                await API.actualizarConvocado(btn.dataset.partido, btn.dataset.jugador, {
                    titular: btn.dataset.titular === "1"
                });
                break;

            case "quitar-convocado":
                if (!confirm("¿Quitar a este jugador de la convocatoria?")) return;
                await API.eliminarConvocado(btn.dataset.partido, btn.dataset.jugador);
                break;

            case "quitar-portero":
                if (!confirm("¿Quitar las estadísticas de este portero?")) return;
                await API.eliminarEstadisticaPortero(btn.dataset.partido, btn.dataset.jugador);
                break;
        }

        await recargarVistaActiva();

    } catch (err) {
        console.error("Error en " + accion + ":", err);
        aviso(err.message || "No se pudo completar la operación.", "error");
    } finally {
        btn.disabled = false;
    }
});


/* Formularios: crear ronda, crear partido, guardar resultado, evento */

document.addEventListener("submit", async function (e) {

    const form = e.target.closest("[data-accion]");
    if (!form) return;

    e.preventDefault();

    const accion = form.dataset.accion;
    const datos = Object.fromEntries(new FormData(form).entries());

    try {
        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        switch (accion) {

            case "crear-ronda":
                await API.crearRonda(form.dataset.torneo, {
                    nombre: datos.nombre,
                    orden: datos.orden ? Number(datos.orden) : null
                });
                break;

            case "crear-partido":
                await API.crearPartido({
                    ronda_id: Number(form.dataset.ronda),
                    equipo_local_id: Number(datos.equipo_local_id),
                    equipo_visitante_id: Number(datos.equipo_visitante_id),
                    fecha_hora: datos.fecha_hora || null,
                    cancha: datos.cancha || null,
                    posicion: datos.posicion ? Number(datos.posicion) : null
                });
                break;

            case "guardar-resultado": {

                const cuerpo = {
                    goles_local: datos.goles_local === "" ? null : Number(datos.goles_local),
                    goles_visitante: datos.goles_visitante === "" ? null : Number(datos.goles_visitante),
                    penales_local: datos.penales_local === "" ? null : Number(datos.penales_local),
                    penales_visitante: datos.penales_visitante === "" ? null : Number(datos.penales_visitante),
                    ganador_id: Number(datos.ganador_id) || null,
                    estado: datos.estado
                };

                await API.guardarResultado(form.dataset.partido, cuerpo);
                break;
            }

            case "registrar-evento":
                await API.registrarEvento(form.dataset.partido, {
                    jugador_id: Number(datos.jugador_id),
                    equipo_id: Number(form.dataset.equipo),
                    tipo: datos.tipo,
                    minuto: datos.minuto ? Number(datos.minuto) : null
                });
                break;

            case "convocar-jugador":
                await API.convocarJugador(form.dataset.partido, {
                    jugador_id: Number(datos.jugador_id),
                    equipo_id: Number(form.dataset.equipo),
                    titular: datos.titular === "1",
                    posicion: datos.posicion
                });
                aviso("Jugador convocado.", "ok");
                break;

            case "registrar-portero":
                await API.registrarEstadisticaPortero(form.dataset.partido, {
                    jugador_id: Number(datos.jugador_id),
                    atajadas: Number(datos.atajadas),
                    goles_recibidos: Number(datos.goles_recibidos)
                });
                aviso("Estadística registrada.", "ok");
                break;

            case "actualizar-portero":
                await API.actualizarEstadisticaPortero(form.dataset.partido, Number(form.dataset.jugador), {
                    atajadas: Number(datos.atajadas),
                    goles_recibidos: Number(datos.goles_recibidos)
                });
                break;

            case "editar-categoria":
                await API.actualizarCategoriaVoto(torneoId, form.dataset.categoria, {
                    nombre: datos.nombre,
                    tipo: datos.tipo,
                    modo_candidatos: datos.modo_candidatos,
                    orden: datos.orden === "" ? 0 : Number(datos.orden)
                });
                aviso("Categoría actualizada.", "ok");
                break;

            case "crear-categoria":
                await API.crearCategoriaVoto(form.dataset.torneo, {
                    clave: datos.clave,
                    nombre: datos.nombre,
                    tipo: datos.tipo,
                    modo_candidatos: datos.modo_candidatos,
                    orden: datos.orden ? Number(datos.orden) : 0,
                    abierta: datos.abierta === "1"
                });
                break;

            case "crear-post":
                await API.crearPost({
                    titulo: datos.titulo,
                    contenido: datos.contenido,
                    imagen_url: datos.imagen_url || "",
                    fijado: datos.fijado === "1"
                });
                break;
        }

        form.reset();
        aviso("Listo.", "ok");
        await recargarVistaActiva();

    } catch (err) {
        console.error("Error en " + accion + ":", err);
        aviso(err.message || "No se pudo completar la operación.", "error");
    }
});


/* Cambio de torneo o de ronda */

document.addEventListener("change", async function (e) {

    const sel = e.target.closest("[data-torneo-select], [data-ronda-select]");
    if (!sel) return;

    if (sel.hasAttribute("data-ronda-select")) {
        ESTADO.rondaId = Number(sel.value);
    } else {
        ESTADO.torneoId = Number(sel.value);
        ESTADO.rondaId = 0;      // la ronda pertenece al torneo anterior
    }

    await recargarVistaActiva();
});


function recargarVistaActiva() {
    const activa = document.querySelector(".admin-view.active");
    return activa ? cargarVista(activa.id) : Promise.resolve();
}
