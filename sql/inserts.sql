-- =========================================================
-- PASSBALLCUP - Simulacion integral de torneo (seed)
-- Basado en la estructura definida en bd_propuesta.sql
-- Ver sql/observaciones_simulacion.md para conflictos y decisiones tomadas
-- =========================================================

USE passballcup;

-- ---------------------------------------------------------
-- Limpieza de datos insertados previamente (orden respeta FKs)
-- ---------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM partido_estadisticas_portero;
DELETE FROM partido_eventos;
DELETE FROM partido_convocados;
DELETE FROM partidos;

DELETE FROM torneo_rondas;
DELETE FROM torneo_equipos;
DELETE FROM torneos;

DELETE FROM equipo_miembros;
DELETE FROM equipos;
DELETE FROM administradores;
DELETE FROM usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- usuarios: 48 jugadores oficiales (6 por equipo x 8 equipos)
-- ---------------------------------------------------------
INSERT INTO usuarios (id, matricula, afi_usuario_id, rol, jugador_activo, estado) VALUES
-- Real Madrid (1)
(1,  'PB00001', 'AFI-00001', 'usuario', TRUE, 'activo'), -- Kylian Mbappe
(2,  'PB00002', 'AFI-00002', 'usuario', TRUE, 'activo'), -- Vinicius Jr.
(3,  'PB00003', 'AFI-00003', 'usuario', TRUE, 'activo'), -- Jude Bellingham
(4,  'PB00004', 'AFI-00004', 'usuario', TRUE, 'activo'), -- Federico Valverde
(5,  'PB00005', 'AFI-00005', 'usuario', TRUE, 'activo'), -- Luka Modric
(6,  'PB00006', 'AFI-00006', 'usuario', TRUE, 'activo'), -- Thibaut Courtois
-- PSG (2)
(7,  'PB00007', 'AFI-00007', 'usuario', TRUE, 'activo'), -- Ousmane Dembele
(8,  'PB00008', 'AFI-00008', 'usuario', TRUE, 'activo'), -- Achraf Hakimi
(9,  'PB00009', 'AFI-00009', 'usuario', TRUE, 'activo'), -- Marquinhos
(10, 'PB00010', 'AFI-00010', 'usuario', TRUE, 'activo'), -- Vitinha
(11, 'PB00011', 'AFI-00011', 'usuario', TRUE, 'activo'), -- Khvicha Kvaratskhelia
(12, 'PB00012', 'AFI-00012', 'usuario', TRUE, 'activo'), -- Gianluigi Donnarumma
-- Barcelona (3)
(13, 'PB00013', 'AFI-00013', 'usuario', TRUE, 'activo'), -- Robert Lewandowski
(14, 'PB00014', 'AFI-00014', 'usuario', TRUE, 'activo'), -- Lamine Yamal
(15, 'PB00015', 'AFI-00015', 'usuario', TRUE, 'activo'), -- Pedri
(16, 'PB00016', 'AFI-00016', 'usuario', TRUE, 'activo'), -- Raphinha
(17, 'PB00017', 'AFI-00017', 'usuario', TRUE, 'activo'), -- Frenkie de Jong
(18, 'PB00018', 'AFI-00018', 'usuario', TRUE, 'activo'), -- Marc-Andre ter Stegen
-- Borussia Dortmund (4)
(19, 'PB00019', 'AFI-00019', 'usuario', TRUE, 'activo'), -- Serhou Guirassy
(20, 'PB00020', 'AFI-00020', 'usuario', TRUE, 'activo'), -- Karim Adeyemi
(21, 'PB00021', 'AFI-00021', 'usuario', TRUE, 'activo'), -- Julian Brandt
(22, 'PB00022', 'AFI-00022', 'usuario', TRUE, 'activo'), -- Marcel Sabitzer
(23, 'PB00023', 'AFI-00023', 'usuario', TRUE, 'activo'), -- Nico Schlotterbeck
(24, 'PB00024', 'AFI-00024', 'usuario', TRUE, 'activo'), -- Gregor Kobel
-- Bayern Munich (5)
(25, 'PB00025', 'AFI-00025', 'usuario', TRUE, 'activo'), -- Harry Kane
(26, 'PB00026', 'AFI-00026', 'usuario', TRUE, 'activo'), -- Jamal Musiala
(27, 'PB00027', 'AFI-00027', 'usuario', TRUE, 'activo'), -- Joshua Kimmich
(28, 'PB00028', 'AFI-00028', 'usuario', TRUE, 'activo'), -- Thomas Muller
(29, 'PB00029', 'AFI-00029', 'usuario', TRUE, 'activo'), -- Alphonso Davies
(30, 'PB00030', 'AFI-00030', 'usuario', TRUE, 'activo'), -- Manuel Neuer
-- Everton (6)
(31, 'PB00031', 'AFI-00031', 'usuario', TRUE, 'activo'), -- Dominic Calvert-Lewin
(32, 'PB00032', 'AFI-00032', 'usuario', TRUE, 'activo'), -- Dwight McNeil
(33, 'PB00033', 'AFI-00033', 'usuario', TRUE, 'activo'), -- Idrissa Gueye
(34, 'PB00034', 'AFI-00034', 'usuario', TRUE, 'activo'), -- James Garner
(35, 'PB00035', 'AFI-00035', 'usuario', TRUE, 'activo'), -- James Tarkowski
(36, 'PB00036', 'AFI-00036', 'usuario', TRUE, 'activo'), -- Jordan Pickford
-- Monterrey (7)
(37, 'PB00037', 'AFI-00037', 'usuario', TRUE, 'activo'), -- Sergio Ramos
(38, 'PB00038', 'AFI-00038', 'usuario', TRUE, 'activo'), -- Sergio Canales
(39, 'PB00039', 'AFI-00039', 'usuario', TRUE, 'activo'), -- Lucas Ocampos
(40, 'PB00040', 'AFI-00040', 'usuario', TRUE, 'activo'), -- German Berterame
(41, 'PB00041', 'AFI-00041', 'usuario', TRUE, 'activo'), -- Jesus Corona
(42, 'PB00042', 'AFI-00042', 'usuario', TRUE, 'activo'), -- Esteban Andrada
-- Tigres (8)
(43, 'PB00043', 'AFI-00043', 'usuario', TRUE, 'activo'), -- Andre-Pierre Gignac
(44, 'PB00044', 'AFI-00044', 'usuario', TRUE, 'activo'), -- Nahuel Guzman
(45, 'PB00045', 'AFI-00045', 'usuario', TRUE, 'activo'), -- Juan Brunetta
(46, 'PB00046', 'AFI-00046', 'usuario', TRUE, 'activo'), -- Guido Pizarro
(47, 'PB00047', 'AFI-00047', 'usuario', TRUE, 'activo'), -- Fernando Gorriaran
(48, 'PB00048', 'AFI-00048', 'usuario', TRUE, 'activo'), -- Rafael Carioca
-- Capitanes placeholder para equipos de prueba (no forman parte de una plantilla oficial, ver observaciones)
(49, 'PB00049', 'AFI-00049', 'usuario', FALSE, 'activo'), -- Capitan nominal Club America
(50, 'PB00050', 'AFI-00050', 'usuario', FALSE, 'activo'); -- Capitan nominal Pumas

-- ---------------------------------------------------------
-- administradores (tabla independiente, ver bd_propuesta.sql)
-- ---------------------------------------------------------
INSERT INTO administradores (id, nombre, usuario, contrasena, activo) VALUES
(1, 'Administrador PASSBALL', 'admin_passballcup', '$2y$10$examplehashexamplehashexamplehash', TRUE);

-- ---------------------------------------------------------
-- equipos (8 oficiales del cuadro + 2 de prueba fuera del cuadro)
-- ---------------------------------------------------------
INSERT INTO equipos (id, nombre, logo, capitan_id, estado) VALUES
(1, 'Real Madrid',        NULL, 5,  'activo'), -- capitan: Luka Modric
(2, 'PSG',                NULL, 9,  'activo'), -- capitan: Marquinhos
(3, 'Barcelona',          NULL, 17, 'activo'), -- capitan: Frenkie de Jong
(4, 'Borussia Dortmund',  NULL, 22, 'activo'), -- capitan: Marcel Sabitzer
(5, 'Bayern Munich',      NULL, 27, 'activo'), -- capitan: Joshua Kimmich
(6, 'Everton',            NULL, 35, 'activo'), -- capitan: James Tarkowski
(7, 'Monterrey',          NULL, 37, 'activo'), -- capitan: Sergio Ramos
(8, 'Tigres',             NULL, 46, 'activo'), -- capitan: Guido Pizarro
(9, 'Club America',       NULL, 49, 'activo'), -- caso de prueba: inscripcion rechazada
(10,'Pumas',              NULL, 50, 'activo'); -- caso de prueba: inscripcion retirada

-- ---------------------------------------------------------
-- equipo_miembros (48 membresias activas, 6 por equipo oficial)
-- ---------------------------------------------------------
INSERT INTO equipo_miembros (equipo_id, jugador_id, fecha_union, estado) VALUES
(1, 1,  '2026-07-15 09:00:00', 'activo'),
(1, 2,  '2026-07-15 09:00:00', 'activo'),
(1, 3,  '2026-07-15 09:00:00', 'activo'),
(1, 4,  '2026-07-15 09:00:00', 'activo'),
(1, 5,  '2026-07-15 09:00:00', 'activo'),
(1, 6,  '2026-07-15 09:00:00', 'activo'),
(2, 7,  '2026-07-15 09:00:00', 'activo'),
(2, 8,  '2026-07-15 09:00:00', 'activo'),
(2, 9,  '2026-07-15 09:00:00', 'activo'),
(2, 10, '2026-07-15 09:00:00', 'activo'),
(2, 11, '2026-07-15 09:00:00', 'activo'),
(2, 12, '2026-07-15 09:00:00', 'activo'),
(3, 13, '2026-07-15 09:00:00', 'activo'),
(3, 14, '2026-07-15 09:00:00', 'activo'),
(3, 15, '2026-07-15 09:00:00', 'activo'),
(3, 16, '2026-07-15 09:00:00', 'activo'),
(3, 17, '2026-07-15 09:00:00', 'activo'),
(3, 18, '2026-07-15 09:00:00', 'activo'),
(4, 19, '2026-07-15 09:00:00', 'activo'),
(4, 20, '2026-07-15 09:00:00', 'activo'),
(4, 21, '2026-07-15 09:00:00', 'activo'),
(4, 22, '2026-07-15 09:00:00', 'activo'),
(4, 23, '2026-07-15 09:00:00', 'activo'),
(4, 24, '2026-07-15 09:00:00', 'activo'),
(5, 25, '2026-07-15 09:00:00', 'activo'),
(5, 26, '2026-07-15 09:00:00', 'activo'),
(5, 27, '2026-07-15 09:00:00', 'activo'),
(5, 28, '2026-07-15 09:00:00', 'activo'),
(5, 29, '2026-07-15 09:00:00', 'activo'),
(5, 30, '2026-07-15 09:00:00', 'activo'),
(6, 31, '2026-07-15 09:00:00', 'activo'),
(6, 32, '2026-07-15 09:00:00', 'activo'),
(6, 33, '2026-07-15 09:00:00', 'activo'),
(6, 34, '2026-07-15 09:00:00', 'activo'),
(6, 35, '2026-07-15 09:00:00', 'activo'),
(6, 36, '2026-07-15 09:00:00', 'activo'),
(7, 37, '2026-07-15 09:00:00', 'activo'),
(7, 38, '2026-07-15 09:00:00', 'activo'),
(7, 39, '2026-07-15 09:00:00', 'activo'),
(7, 40, '2026-07-15 09:00:00', 'activo'),
(7, 41, '2026-07-15 09:00:00', 'activo'),
(7, 42, '2026-07-15 09:00:00', 'activo'),
(8, 43, '2026-07-15 09:00:00', 'activo'),
(8, 44, '2026-07-15 09:00:00', 'activo'),
(8, 45, '2026-07-15 09:00:00', 'activo'),
(8, 46, '2026-07-15 09:00:00', 'activo'),
(8, 47, '2026-07-15 09:00:00', 'activo'),
(8, 48, '2026-07-15 09:00:00', 'activo');

-- ---------------------------------------------------------
-- torneos
-- ---------------------------------------------------------
INSERT INTO torneos (id, nombre, tipo, fecha_inicio, fecha_fin, estado) VALUES
(1, 'PASSBALLCUP', 'eliminacion_directa', '2026-08-01', '2026-08-22', 'finalizado');

-- ---------------------------------------------------------
-- torneo_equipos (8 aprobados + 2 casos de prueba: rechazado y retirado)
-- ---------------------------------------------------------
INSERT INTO torneo_equipos (torneo_id, equipo_id, estado, fecha_solicitud, fecha_aprobacion, aprobado_por) VALUES
(1, 1, 'aprobado', '2026-07-20 10:00:00', '2026-07-20 12:00:00', 1),
(1, 2, 'aprobado', '2026-07-20 10:05:00', '2026-07-20 12:05:00', 1),
(1, 3, 'aprobado', '2026-07-20 10:10:00', '2026-07-20 12:10:00', 1),
(1, 4, 'aprobado', '2026-07-20 10:15:00', '2026-07-20 12:15:00', 1),
(1, 5, 'aprobado', '2026-07-20 10:20:00', '2026-07-20 12:20:00', 1),
(1, 6, 'aprobado', '2026-07-20 10:25:00', '2026-07-20 12:25:00', 1),
(1, 7, 'aprobado', '2026-07-20 10:30:00', '2026-07-20 12:30:00', 1),
(1, 8, 'aprobado', '2026-07-20 10:35:00', '2026-07-20 12:35:00', 1),
(1, 9, 'rechazado', '2026-07-20 10:40:00', NULL, NULL),
(1, 10, 'retirado', '2026-07-20 10:45:00', '2026-07-20 12:45:00', 1);

-- ---------------------------------------------------------
-- torneo_rondas
-- ---------------------------------------------------------
INSERT INTO torneo_rondas (id, torneo_id, nombre, orden) VALUES
(1, 1, 'Cuartos de Final', 1),
(2, 1, 'Semifinal', 2),
(3, 1, 'Final', 3);

-- ---------------------------------------------------------
-- partidos
-- Cuartos: RM 5-2 PSG, Barca 3-1 Borussia, Bayern 1-2 Everton, Monterrey 2-3 Tigres
-- Semis:   RM 4-2 Barca, Tigres 2-1 Everton
-- Final:   RM 2-3 Tigres (campeon: Tigres)
-- partido_origen_*: referencia al partido de la ronda anterior de donde viene cada lado (NULL en cuartos)
-- ---------------------------------------------------------
INSERT INTO partidos (id, ronda_id, equipo_local_id, equipo_visitante_id, partido_origen_local_id, partido_origen_visitante_id, posicion, fecha_hora, cancha,
  goles_local, goles_visitante, penales_local, penales_visitante, ganador_id, estado) VALUES
(1, 1, 1, 2, NULL, NULL, 1, '2026-08-08 15:00:00', 'Cancha 1',       5, 2, NULL, NULL, 1, 'finalizado'),
(2, 1, 3, 4, NULL, NULL, 2, '2026-08-08 16:00:00', 'Cancha 1',       3, 1, NULL, NULL, 3, 'finalizado'),
(3, 1, 5, 6, NULL, NULL, 3, '2026-08-08 17:00:00', 'Cancha 2',       1, 2, NULL, NULL, 6, 'finalizado'),
(4, 1, 7, 8, NULL, NULL, 4, '2026-08-08 18:00:00', 'Cancha 2',       2, 3, NULL, NULL, 8, 'finalizado'),
(5, 2, 1, 3, 1,    2,    1, '2026-08-15 16:00:00', 'Cancha Central', 4, 2, NULL, NULL, 1, 'finalizado'),
(6, 2, 8, 6, 4,    3,    2, '2026-08-15 18:00:00', 'Cancha Central', 2, 1, NULL, NULL, 8, 'finalizado'),
(7, 3, 1, 8, 5,    6,    1, '2026-08-22 17:00:00', 'Cancha Central', 2, 3, NULL, NULL, 8, 'finalizado');

-- ---------------------------------------------------------
-- partido_convocados (12 por partido x 7 partidos = 84)
-- Cada equipo aporta sus 6 jugadores: 5 titulares + 1 en banca (rotado entre partidos)
-- ---------------------------------------------------------
INSERT INTO partido_convocados (partido_id, jugador_id, equipo_id, titular, posicion) VALUES
-- Partido 1: Real Madrid vs PSG
(1, 1,  1, TRUE,  'delantero'),
(1, 2,  1, TRUE,  'delantero'),
(1, 3,  1, TRUE,  'mediocampo'),
(1, 4,  1, TRUE,  'defensa'),
(1, 6,  1, TRUE,  'portero'),
(1, 5,  1, FALSE, 'mediocampo'),
(1, 7,  2, TRUE,  'delantero'),
(1, 8,  2, TRUE,  'defensa'),
(1, 10, 2, TRUE,  'mediocampo'),
(1, 11, 2, TRUE,  'delantero'),
(1, 12, 2, TRUE,  'portero'),
(1, 9,  2, FALSE, 'mediocampo'),
-- Partido 2: Barcelona vs Borussia Dortmund
(2, 13, 3, TRUE,  'delantero'),
(2, 14, 3, TRUE,  'delantero'),
(2, 15, 3, TRUE,  'mediocampo'),
(2, 17, 3, TRUE,  'mediocampo'),
(2, 18, 3, TRUE,  'portero'),
(2, 16, 3, FALSE, 'defensa'),
(2, 19, 4, TRUE,  'delantero'),
(2, 20, 4, TRUE,  'delantero'),
(2, 21, 4, TRUE,  'mediocampo'),
(2, 23, 4, TRUE,  'defensa'),
(2, 24, 4, TRUE,  'portero'),
(2, 22, 4, FALSE, 'mediocampo'),
-- Partido 3: Bayern Munich vs Everton
(3, 25, 5, TRUE,  'delantero'),
(3, 26, 5, TRUE,  'mediocampo'),
(3, 27, 5, TRUE,  'mediocampo'),
(3, 29, 5, TRUE,  'defensa'),
(3, 30, 5, TRUE,  'portero'),
(3, 28, 5, FALSE, 'delantero'),
(3, 31, 6, TRUE,  'delantero'),
(3, 32, 6, TRUE,  'delantero'),
(3, 33, 6, TRUE,  'mediocampo'),
(3, 35, 6, TRUE,  'defensa'),
(3, 36, 6, TRUE,  'portero'),
(3, 34, 6, FALSE, 'mediocampo'),
-- Partido 4: Monterrey vs Tigres
(4, 37, 7, TRUE,  'defensa'),
(4, 39, 7, TRUE,  'delantero'),
(4, 40, 7, TRUE,  'delantero'),
(4, 41, 7, TRUE,  'mediocampo'),
(4, 42, 7, TRUE,  'portero'),
(4, 38, 7, FALSE, 'mediocampo'),
(4, 43, 8, TRUE,  'delantero'),
(4, 44, 8, TRUE,  'portero'),
(4, 45, 8, TRUE,  'mediocampo'),
(4, 46, 8, TRUE,  'defensa'),
(4, 47, 8, TRUE,  'mediocampo'),
(4, 48, 8, FALSE, 'delantero'),
-- Partido 5: Real Madrid vs Barcelona
(5, 1,  1, TRUE,  'delantero'),
(5, 2,  1, TRUE,  'delantero'),
(5, 3,  1, TRUE,  'mediocampo'),
(5, 5,  1, TRUE,  'mediocampo'),
(5, 6,  1, TRUE,  'portero'),
(5, 4,  1, FALSE, 'defensa'),
(5, 13, 3, TRUE,  'delantero'),
(5, 14, 3, TRUE,  'delantero'),
(5, 16, 3, TRUE,  'defensa'),
(5, 17, 3, TRUE,  'mediocampo'),
(5, 18, 3, TRUE,  'portero'),
(5, 15, 3, FALSE, 'mediocampo'),
-- Partido 6: Tigres vs Everton
(6, 43, 8, TRUE,  'delantero'),
(6, 44, 8, TRUE,  'portero'),
(6, 45, 8, TRUE,  'mediocampo'),
(6, 46, 8, TRUE,  'defensa'),
(6, 48, 8, TRUE,  'delantero'),
(6, 47, 8, FALSE, 'mediocampo'),
(6, 31, 6, TRUE,  'delantero'),
(6, 33, 6, TRUE,  'mediocampo'),
(6, 34, 6, TRUE,  'mediocampo'),
(6, 35, 6, TRUE,  'defensa'),
(6, 36, 6, TRUE,  'portero'),
(6, 32, 6, FALSE, 'delantero'),
-- Partido 7: Real Madrid vs Tigres (Final)
(7, 1,  1, TRUE,  'delantero'),
(7, 2,  1, TRUE,  'delantero'),
(7, 4,  1, TRUE,  'defensa'),
(7, 5,  1, TRUE,  'mediocampo'),
(7, 6,  1, TRUE,  'portero'),
(7, 3,  1, FALSE, 'mediocampo'),
(7, 43, 8, TRUE,  'delantero'),
(7, 44, 8, TRUE,  'portero'),
(7, 45, 8, TRUE,  'mediocampo'),
(7, 47, 8, TRUE,  'mediocampo'),
(7, 48, 8, TRUE,  'delantero'),
(7, 46, 8, FALSE, 'defensa');

-- ---------------------------------------------------------
-- partido_eventos
-- Convencion: equipo_id = equipo al que se le acredita el evento
-- (en autogol, jugador_id pertenece al equipo rival del que anota equipo_id)
-- ---------------------------------------------------------
INSERT INTO partido_eventos (partido_id, jugador_id, equipo_id, tipo, minuto, asistencia_jugador_id) VALUES
-- Partido 1: Real Madrid 5 - 2 PSG
(1, 1,  1, 'gol', 10, 2),
(1, 1,  1, 'gol', 33, 3),
(1, 1,  1, 'gol', 70, NULL),
(1, 2,  1, 'gol', 45, 1),
(1, 2,  1, 'gol', 80, NULL),
(1, 7,  2, 'gol', 55, NULL),
(1, 3,  2, 'autogol', 62, NULL),
(1, 4,  1, 'tarjeta_amarilla', 40, NULL),
(1, 8,  2, 'tarjeta_amarilla', 58, NULL),
-- Partido 2: Barcelona 3 - 1 Borussia Dortmund
(2, 13, 3, 'gol', 12, 14),
(2, 13, 3, 'gol', 50, 17),
(2, 14, 3, 'gol', 65, 15),
(2, 19, 4, 'gol', 38, NULL),
(2, 23, 4, 'tarjeta_amarilla', 44, NULL),
-- Partido 3: Bayern Munich 1 - 2 Everton
(3, 25, 5, 'gol', 20, NULL),
(3, 31, 6, 'gol', 30, 32),
(3, 31, 6, 'gol', 75, NULL),
(3, 26, 5, 'tarjeta_amarilla', 50, NULL),
(3, 33, 6, 'tarjeta_amarilla', 68, NULL),
-- Partido 4: Monterrey 2 - 3 Tigres
(4, 40, 7, 'gol', 15, 39),
(4, 40, 7, 'gol', 60, NULL),
(4, 43, 8, 'penal_anotado', 25, NULL),
(4, 45, 8, 'gol', 48, 43),
(4, 46, 8, 'gol', 82, NULL),
(4, 37, 7, 'tarjeta_amarilla', 55, NULL),
-- Partido 5: Real Madrid 4 - 2 Barcelona
(5, 1,  1, 'gol', 10, 2),
(5, 1,  1, 'gol', 25, 2),
(5, 1,  1, 'gol', 50, 3),
(5, 1,  1, 'gol', 77, NULL),
(5, 13, 3, 'gol', 35, 17),
(5, 16, 3, 'gol', 60, NULL),
(5, 3,  1, 'tarjeta_amarilla', 40, NULL),
(5, 16, 3, 'tarjeta_roja', 78, NULL),
-- Partido 6: Tigres 2 - 1 Everton
(6, 43, 8, 'gol', 20, 46),
(6, 45, 8, 'gol', 55, NULL),
(6, 31, 6, 'gol', 40, 33),
(6, 35, 6, 'tarjeta_amarilla', 65, NULL),
(6, 46, 8, 'tarjeta_amarilla', 70, NULL),
-- Partido 7: Real Madrid 2 - 3 Tigres (Final)
(7, 1,  1, 'gol', 15, 2),
(7, 1,  1, 'gol', 66, NULL),
(7, 43, 8, 'gol', 30, 48),
(7, 45, 8, 'gol', 52, 43),
(7, 47, 8, 'gol', 85, NULL);

-- ---------------------------------------------------------
-- partido_estadisticas_portero (2 por partido: portero local y visitante)
-- ---------------------------------------------------------
INSERT INTO partido_estadisticas_portero (partido_id, jugador_id, atajadas, goles_recibidos) VALUES
(1, 6,  4, 2),  -- Courtois vs PSG
(1, 12, 3, 5),  -- Donnarumma vs Real Madrid
(2, 18, 5, 1),  -- Ter Stegen vs Borussia
(2, 24, 2, 3),  -- Kobel vs Barcelona
(3, 30, 6, 2),  -- Neuer vs Everton
(3, 36, 4, 1),  -- Pickford vs Bayern
(4, 42, 3, 3),  -- Andrada vs Tigres
(4, 44, 7, 2),  -- Nahuel Guzman vs Monterrey
(5, 6,  5, 2),  -- Courtois vs Barcelona
(5, 18, 2, 4),  -- Ter Stegen vs Real Madrid
(6, 44, 5, 1),  -- Nahuel Guzman vs Everton
(6, 36, 4, 2),  -- Pickford vs Tigres
(7, 6,  6, 3),  -- Courtois vs Tigres
(7, 44, 8, 2);  -- Nahuel Guzman vs Real Madrid
