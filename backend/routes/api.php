<?php
// Mapa de rutas: aquí es donde de un vistazo se ve TODO el contrato de la API
// (coincide 1:1 con endpoints_implementacion.md). $router ya existe cuando
// este archivo se incluye desde index.php.

//TEST 
$router->get(
    '/api/test',
    [TestController::class, 'index']
);

// --- auth / admin-auth ---
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/auth/me', [AuthController::class, 'me']);

$router->post('/api/admin/login', [AdminAuthController::class, 'login']);
$router->post('/api/admin/logout', [AdminAuthController::class, 'logout']);
$router->get('/api/admin/me', [AdminAuthController::class, 'me']);

// --- usuarios / administradores ---
$router->get('/api/usuarios', [UsuariosController::class, 'listar']);
$router->get('/api/usuarios/{id}', [UsuariosController::class, 'detalle']);
$router->patch('/api/usuarios/{id}', [UsuariosController::class, 'actualizarAvatar']);
$router->patch('/api/usuarios/{id}/estado', [UsuariosController::class, 'cambiarEstado']);
$router->patch('/api/usuarios/{id}/jugador-activo', [UsuariosController::class, 'cambiarJugadorActivo']);

$router->get('/api/administradores', [AdministradoresController::class, 'listar']);
$router->post('/api/administradores', [AdministradoresController::class, 'crear']);
$router->get('/api/administradores/{id}', [AdministradoresController::class, 'detalle']);
$router->patch('/api/administradores/{id}', [AdministradoresController::class, 'actualizar']);
$router->patch('/api/administradores/{id}/activo', [AdministradoresController::class, 'cambiarActivo']);
$router->delete('/api/administradores/{id}', [AdministradoresController::class, 'eliminar']);

// --- equipos / membresías ---
$router->get('/api/equipos', [EquiposController::class, 'listar']);
$router->post('/api/equipos', [EquiposController::class, 'crear']);
$router->get('/api/equipos/{id}', [EquiposController::class, 'detalle']);
$router->patch('/api/equipos/{id}', [EquiposController::class, 'actualizar']);
$router->patch('/api/equipos/{id}/estado', [EquiposController::class, 'cambiarEstado']);
$router->delete('/api/equipos/{id}', [EquiposController::class, 'eliminar']);

$router->get('/api/equipos/{equipoId}/miembros', [EquipoMiembrosController::class, 'listar']);
$router->post('/api/equipos/{equipoId}/miembros', [EquipoMiembrosController::class, 'agregar']);
$router->patch('/api/equipos/{equipoId}/miembros/{jugadorId}/salida', [EquipoMiembrosController::class, 'marcarSalida']);
$router->delete('/api/equipos/{equipoId}/miembros/{jugadorId}', [EquipoMiembrosController::class, 'eliminar']);

$router->get('/api/jugadores/{jugadorId}/equipo-actual', [EquipoMiembrosController::class, 'equipoActual']);
$router->get('/api/jugadores/{jugadorId}/historial-equipos', [EquipoMiembrosController::class, 'historialEquipos']);

// --- torneos / inscripciones / rondas / bracket ---
$router->get('/api/torneos', [TorneosController::class, 'listar']);
$router->post('/api/torneos', [TorneosController::class, 'crear']);
$router->get('/api/torneos/{id}', [TorneosController::class, 'detalle']);
$router->patch('/api/torneos/{id}', [TorneosController::class, 'actualizar']);
$router->delete('/api/torneos/{id}', [TorneosController::class, 'eliminar']);

$router->get('/api/torneos/{torneoId}/equipos', [TorneoEquiposController::class, 'listar']);
$router->post('/api/torneos/{torneoId}/equipos', [TorneoEquiposController::class, 'solicitar']);
$router->patch('/api/torneos/{torneoId}/equipos/{equipoId}/aprobar', [TorneoEquiposController::class, 'aprobar']);
$router->patch('/api/torneos/{torneoId}/equipos/{equipoId}/rechazar', [TorneoEquiposController::class, 'rechazar']);
$router->patch('/api/torneos/{torneoId}/equipos/{equipoId}/retirar', [TorneoEquiposController::class, 'retirar']);

$router->get('/api/torneos/{torneoId}/rondas', [TorneoRondasController::class, 'listar']);
$router->post('/api/torneos/{torneoId}/rondas', [TorneoRondasController::class, 'crear']);
$router->patch('/api/torneos/{torneoId}/rondas/{id}', [TorneoRondasController::class, 'actualizar']);
$router->delete('/api/torneos/{torneoId}/rondas/{id}', [TorneoRondasController::class, 'eliminar']);

$router->get('/api/torneos/{torneoId}/bracket', [BracketController::class, 'obtener']);

// --- partidos ---
$router->get('/api/partidos', [PartidosController::class, 'listar']);
$router->post('/api/partidos', [PartidosController::class, 'crear']);
$router->get('/api/partidos/{id}', [PartidosController::class, 'detalle']);
$router->patch('/api/partidos/{id}', [PartidosController::class, 'actualizar']);
$router->patch('/api/partidos/{id}/resultado', [PartidosController::class, 'resultado']);
$router->patch('/api/partidos/{id}/finalizar', [PartidosController::class, 'finalizar']);
$router->delete('/api/partidos/{id}', [PartidosController::class, 'eliminar']);

$router->get('/api/partidos/{partidoId}/convocados', [PartidoConvocadosController::class, 'listar']);
$router->post('/api/partidos/{partidoId}/convocados', [PartidoConvocadosController::class, 'convocar']);
$router->patch('/api/partidos/{partidoId}/convocados/{jugadorId}', [PartidoConvocadosController::class, 'actualizar']);
$router->delete('/api/partidos/{partidoId}/convocados/{jugadorId}', [PartidoConvocadosController::class, 'eliminar']);

$router->get('/api/partidos/{partidoId}/eventos', [PartidoEventosController::class, 'listar']);
$router->post('/api/partidos/{partidoId}/eventos', [PartidoEventosController::class, 'registrar']);
$router->patch('/api/partidos/{partidoId}/eventos/{eventoId}', [PartidoEventosController::class, 'actualizar']);
$router->delete('/api/partidos/{partidoId}/eventos/{eventoId}', [PartidoEventosController::class, 'eliminar']);

$router->get('/api/partidos/{partidoId}/porteros', [PartidoPorterosController::class, 'listar']);
$router->post('/api/partidos/{partidoId}/porteros', [PartidoPorterosController::class, 'registrar']);
$router->patch('/api/partidos/{partidoId}/porteros/{jugadorId}', [PartidoPorterosController::class, 'actualizar']);
$router->delete('/api/partidos/{partidoId}/porteros/{jugadorId}', [PartidoPorterosController::class, 'eliminar']);

// --- estadísticas (solo lectura) ---
$router->get('/api/estadisticas/goleadores', [EstadisticasController::class, 'goleadores']);
$router->get('/api/estadisticas/asistencias', [EstadisticasController::class, 'asistencias']);
$router->get('/api/estadisticas/tarjetas', [EstadisticasController::class, 'tarjetas']);
$router->get('/api/estadisticas/porteros', [EstadisticasController::class, 'porteros']);
$router->get('/api/estadisticas/tabla-posiciones', [EstadisticasController::class, 'tablaPosiciones']);
$router->get('/api/estadisticas/jugador/{jugadorId}', [EstadisticasController::class, 'jugador']);

// --- votaciones ---
$router->get('/api/torneos/{torneoId}/categorias-voto', [CategoriasVotoController::class, 'listar']);
$router->post('/api/torneos/{torneoId}/categorias-voto', [CategoriasVotoController::class, 'crear']);
$router->patch('/api/torneos/{torneoId}/categorias-voto/{id}', [CategoriasVotoController::class, 'actualizar']);
$router->patch('/api/torneos/{torneoId}/categorias-voto/{id}/estado', [CategoriasVotoController::class, 'cambiarEstado']);
$router->delete('/api/torneos/{torneoId}/categorias-voto/{id}', [CategoriasVotoController::class, 'eliminar']);
$router->get('/api/torneos/{torneoId}/categorias-voto/{id}/pool', [CategoriasVotoController::class, 'pool']);

$router->get('/api/torneos/{torneoId}/categorias-voto/{categoriaId}/candidatos', [CandidatosVotoController::class, 'listar']);
$router->post('/api/torneos/{torneoId}/categorias-voto/{categoriaId}/candidatos', [CandidatosVotoController::class, 'agregar']);
$router->delete('/api/torneos/{torneoId}/categorias-voto/{categoriaId}/candidatos/{id}', [CandidatosVotoController::class, 'eliminar']);

$router->post('/api/torneos/{torneoId}/votos', [VotosController::class, 'emitir']);
$router->get('/api/torneos/{torneoId}/votos/mios', [VotosController::class, 'mios']);
$router->get('/api/torneos/{torneoId}/votos/resultados', [VotosController::class, 'resultados']);
$router->delete('/api/torneos/{torneoId}/votos/{categoriaId}', [VotosController::class, 'retirar']);
