/* ============================================================
   PASSBALL CUP
   CLIENTE DE LA API REST
   ============================================================
   Sustituye el acceso directo a la base de datos por llamadas a
   backend/api. Todos los endpoints comparten origen con el panel
   (localhost:80), asi que no hace falta CORS.

   La API se autentica con la cookie de sesion PHP (PHPSESSID), no
   con un header Authorization: credentials: 'include' es obligatorio
   en cada peticion o el requireAdminAPI() devolvera 401.
   ============================================================ */

const API = {

    /* --------------------------------------------------
       Ruta base de la API.

       La inyecta el PHP (window.PASSBALL_API) como ruta absoluta desde
       la raiz del proyecto. Antes era "../backend/api", que dependia de
       la forma de la URL: entrar en /admin sin barra final hacia que
       "../" se saliera del proyecto y Apache respondia con su propio
       404, no con el "Ruta no encontrada" de la API. La relativa queda
       solo como respaldo por si el JS se carga suelto.
       -------------------------------------------------- */

    base: window.PASSBALL_API || "../backend/api",

    /* --------------------------------------------------
       Peticion base. Desenvuelve el envelope
       {exito, data, errores} y devuelve solo data.
       -------------------------------------------------- */

    async request(metodo, ruta, cuerpo) {

        const opciones = {
            method: metodo,
            credentials: "include",        // obligatorio: la API usa PHPSESSID
            headers: { "Content-Type": "application/json" }
        };

        if (cuerpo !== undefined) {
            opciones.body = JSON.stringify(cuerpo);
        }

        const res = await fetch(this.base + ruta, opciones);

        let json = null;

        try {
            json = await res.json();
        } catch {
            throw new Error("Respuesta no JSON (HTTP " + res.status + ")");
        }

        // errores es array en exito y objeto en error: hay que mirar los dos.
        if (!res.ok || !json.exito) {

            const detalle = Array.isArray(json.errores)
                ? null
                : (json.errores || {}).error;

            const err = new Error(detalle || "Error " + res.status);

            err.status = res.status;

            throw err;
        }

        return json.data;
    },

    get(ruta)     { return this.request("GET", ruta); },
    post(ruta, c) { return this.request("POST", ruta, c); },
    patch(ruta, c){ return this.request("PATCH", ruta, c); },
    del(ruta)     { return this.request("DELETE", ruta); },


    /* --------------------------------------------------
       Sesion de administrador
       -------------------------------------------------- */

    loginAdmin(usuario, contrasena) {
        return this.post("/admin/login", { usuario, contrasena });
    },

    me() {
        return this.get("/admin/me");
    },


    /* --------------------------------------------------
       Datos del panel. Cada metodo devuelve solo la parte
       que la vista necesita, no el envelope entero.
       -------------------------------------------------- */

    async estadisticas() {

        const [equipos, torneos, partidos, usuarios] = await Promise.all([

            this.get("/equipos?estado=activo"),

            this.get("/torneos"),

            this.get("/partidos?estado=programado"),

            this.get("/admin/usuarios?estado=activo&jugador_activo=1")

        ]);

        const listaEquipos   = equipos.equipos   || [];
        const listaTorneos   = torneos.torneos   || [];
        const listaPartidos  = partidos.partidos || [];
        const listaUsuarios  = usuarios.usuarios || [];

        // Mismo criterio que el legacy: primero en_curso, luego programado.
        const torneoActual = listaTorneos
            .filter(t => t.estado === "en_curso" || t.estado === "programado")
            .sort((a, b) => {
                if (a.estado !== b.estado) {
                    return a.estado === "en_curso" ? -1 : 1;
                }
                return b.id - a.id;
            })[0] || null;

        return {
            equipos:       listaEquipos.length,
            participantes: listaUsuarios.length,
            partidos:      listaPartidos.length,
            rondas:        0,          // se rellena con detalleTorneo()
            pendientes:    0,          // idem
            torneos:       listaTorneos.length,
            // La API no expone un conteo de votos. El legacy lo sacaba de un
            // SELECT directo sobre torneo_votos. Es el unico dato que la
            // tarjeta pierde al migrar, hasta que exista ese endpoint.
            votos:         0,
            torneoActual,
            proximosPartidos: [],
            postulacionesPendientes: []
        };
    },


    /* --------------------------------------------------
       Rondas y partidos del torneo activo
       -------------------------------------------------- */

    async detalleTorneo(torneoId) {

        const [rondas, partidos, inscripciones] = await Promise.all([

            this.get("/torneos/" + torneoId + "/rondas"),

            this.get("/partidos?torneo_id=" + torneoId),

            this.get("/torneos/" + torneoId + "/equipos?estado=pendiente")

        ]);

        const listaRondas        = rondas.rondas            || [];
        const listaPartidos      = partidos.partidos        || [];
        const listaInscripciones = inscripciones.inscripciones || [];

        return {
            rondas: listaRondas.length,
            pendientes: listaInscripciones.length,
            proximosPartidos: listaPartidos
                .filter(p => p.estado === "programado" || p.estado === "en_curso")
                .sort((a, b) => {
                    // los partidos sin fecha van al final
                    if (!a.fecha_hora && !b.fecha_hora) return a.id - b.id;
                    if (!a.fecha_hora) return 1;
                    if (!b.fecha_hora) return -1;
                    return a.fecha_hora.localeCompare(b.fecha_hora);
                })
                .slice(0, 3),

            postulacionesPendientes: listaInscripciones
                .sort((a, b) => String(a.fecha_solicitud || "")
                                  .localeCompare(String(b.fecha_solicitud || "")))
                .slice(0, 4)
        };
    },


    /* ==================================================
       ETAPA 2.4 - lecturas de las vistas restantes
       ==================================================
       Las de /api/admin/* son las que la API no dejaba
       usar a un administrador porque exigian sesion de
       jugador (401). Las escrituras, en cambio, ya
       existian y se usan directamente.
       ================================================== */

    /* ---.Participantes --- */

    participantes() {
        return this.get("/admin/usuarios?rol=usuario&orden=asc");
    },


    /* --- Postulaciones --- */

    postulaciones(torneoId) {
        return this.get("/torneos/" + torneoId + "/equipos");
    },

    aprobarPostulacion(torneoId, equipoId) {
        return this.patch(
            "/torneos/" + torneoId + "/equipos/" + equipoId + "/aprobar"
        );
    },

    // El motivo NO se puede guardar: torneo_equipos no tiene la columna
    // motivo_rechazo (defecto F2). La API solo cambia el estado.
    rechazarPostulacion(torneoId, equipoId) {
        return this.patch(
            "/torneos/" + torneoId + "/equipos/" + equipoId + "/rechazar"
        );
    },


    /* --- Torneo y rondas --- */

    rondas(torneoId) {
        return this.get("/torneos/" + torneoId + "/rondas");
    },

    partidosDeTorneo(torneoId) {
        return this.get("/partidos?torneo_id=" + torneoId);
    },

    crearRonda(torneoId, datos) {
        return this.post("/torneos/" + torneoId + "/rondas", datos);
    },

    crearPartido(datos) {
        return this.post("/partidos", datos);
    },


    /* --- Votaciones --- */

    categoriasVoto(torneoId) {
        return this.get("/admin/torneos/" + torneoId + "/categorias-voto");
    },

    candidatosVoto(torneoId) {
        return this.get("/admin/torneos/" + torneoId + "/candidatos-voto");
    },

    jugadoresDelTorneo(torneoId) {
        return this.get("/admin/torneos/" + torneoId + "/jugadores");
    },

    crearCategoriaVoto(torneoId, datos) {
        return this.post("/torneos/" + torneoId + "/categorias-voto", datos);
    },

    cambiarEstadoCategoria(torneoId, categoriaId) {
        return this.patch(
            "/torneos/" + torneoId + "/categorias-voto/" + categoriaId + "/estado"
        );
    },

    eliminarCategoria(torneoId, categoriaId) {
        return this.del(
            "/torneos/" + torneoId + "/categorias-voto/" + categoriaId
        );
    },

    agregarCandidato(torneoId, categoriaId, datos) {
        return this.post(
            "/torneos/" + torneoId + "/categorias-voto/" + categoriaId + "/candidatos",
            datos
        );
    },

    excluirCandidato(torneoId, categoriaId, ajusteId) {
        return this.del(
            "/torneos/" + torneoId + "/categorias-voto/" + categoriaId +
            "/candidatos/" + ajusteId
        );
    },


    /* --- Resultados --- */

    eventosDePartido(partidoId) {
        return this.get("/partidos/" + partidoId + "/eventos");
    },

    miembrosDeEquipo(equipoId) {
        return this.get("/admin/equipos/" + equipoId + "/miembros");
    },

    guardarResultado(partidoId, datos) {
        return this.patch("/partidos/" + partidoId + "/resultado", datos);
    },

    registrarEvento(partidoId, datos) {
        return this.post("/partidos/" + partidoId + "/eventos", datos);
    },


    /* --- Contadores --- */

    votosResumen(torneoId) {
        return this.get("/admin/votos-resumen" +
            (torneoId ? "?torneo_id=" + torneoId : ""));
    }
};


/* ============================================================
   PINTADO DE LA VISTA INICIO
   ============================================================
   Los contadores se pintan primero con lo que ya devuelve
   /api/test, y luego se sustituyen al cargar los datos reales.
   Si la API falla, la vista se queda en su estado de carga y
   muestra el error, en vez de mostrar ceros como si fuera real.
   ============================================================ */

function pintarInicio(datos) {

    const set = (sel, valor) => {
        const el = document.querySelector(sel);
        if (el) el.textContent = valor;
    };

    set("#statEquipos", datos.equipos);
    set("#statEquiposCard", datos.equipos);
    set("#statEquiposMini", datos.equipos);
    set("#statParticipantes", datos.participantes);
    set("#statParticipantesMini", datos.participantes);
    set("#statPartidos", datos.partidos);
    set("#statRondas", datos.rondas);
    set("#statRondasCard", datos.rondas);
    set("#statVotos", datos.votos);
    set("#statPendientes", datos.pendientes);
    set("#statPendientesCard", datos.pendientes);

    const barra = document.getElementById("barraProgreso");
    if (barra) barra.style.width = (datos.rondas > 0 ? 100 : 0) + "%";

    if (datos.torneoActual) {
        set("#nombreTorneoActual", datos.torneoActual.nombre);
    }

    // --- Partidos próximos ---

    const listaPartidos = document.getElementById("listaProximosPartidos");

    if (listaPartidos) {

        if (!datos.proximosPartidos.length) {
            listaPartidos.innerHTML =
                '<p class="empty-note">No hay partidos próximos programados.</p>';
        } else {
            listaPartidos.innerHTML = datos.proximosPartidos.map(p => `
                <div class="match-row">
                    <strong>
                        ${esc(p.equipo_local_nombre || "Por definir")}
                        <span>vs</span>
                        ${esc(p.equipo_visitante_nombre || "Por definir")}
                    </strong>
                    <small>
                        ${p.fecha_hora
                            ? new Date(p.fecha_hora.replace(" ", "T"))
                                  .toLocaleString("es-MX")
                            : "Fecha pendiente"}
                        · ${esc(p.cancha || "Cancha pendiente")}
                    </small>
                </div>
            `).join("");
        }
    }

    // --- Postulaciones pendientes ---

    const listaPost = document.getElementById("listaPostulacionesPendientes");

    if (listaPost) {

        if (!datos.postulacionesPendientes.length) {
            listaPost.innerHTML =
                '<p class="empty-note">No hay postulaciones pendientes.</p>';
        } else {
            listaPost.innerHTML = datos.postulacionesPendientes.map(e => `
                <div class="pending-row">
                    <strong>${esc(e.equipo_nombre || "Equipo " + e.equipo_id)}</strong>
                    <span>Pendiente</span>
                    <small>${e.fecha_solicitud
                        ? new Date(e.fecha_solicitud).toLocaleDateString("es-MX")
                        : "—"}</small>
                </div>
            `).join("");
        }
    }
}


function esc(valor) {
    const div = document.createElement("div");
    div.textContent = valor == null ? "" : String(valor);
    return div.innerHTML;
}


async function cargarInicio() {

    try {

        const stats = await API.estadisticas();

        if (stats.torneoActual) {
            const detalle = await API.detalleTorneo(stats.torneoActual.id);
            Object.assign(stats, detalle);
        }

        pintarInicio(stats);

    } catch (err) {

        console.error("Error cargando Inicio:", err);

        const aviso = document.getElementById("inicioError");

        if (aviso) {
            aviso.textContent = "No se pudo cargar la información: " + err.message;
            aviso.hidden = false;
        }
    }
}
