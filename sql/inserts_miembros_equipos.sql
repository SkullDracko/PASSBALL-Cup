-- =========================================================
-- PASSBALLCUP - Inserts de: usuarios, equipos y equipo_miembros
-- Basado en sql/implementacion.md (secciones 3, 4, 5 y 7)
-- SOLO estas 3 tablas (sin torneos, partidos, etc.)
--
-- Contenido:
--  - 60 jugadores (rol='jugador', jugador_activo=1, estado='activo')
--  - 5 usuarios regulares (rol='usuario', jugador_activo=0, estado='activo') sin equipo
--  - 10 equipos x 6 jugadores = 60 membresias activas
--
-- Nota: el esquema de referencia es bd_propuesta.sql (rol ENUM 'usuario'/'jugador')
-- y la columna `nombre` agregada en schema.sql.
-- =========================================================

USE passballcup;

-- ---------------------------------------------------------
-- usuarios: 60 jugadores (6 por equipo x 10 equipos)
-- ---------------------------------------------------------
INSERT INTO usuarios (id, matricula, afi_usuario_id, nombre, rol, jugador_activo, estado) VALUES
-- Real Madrid (equipo 1)
(1,  'PB00001', 'AFI-00001', 'Kylian Mbappe',          'jugador', TRUE,  'activo'),
(2,  'PB00002', 'AFI-00002', 'Vinicius Jr.',           'jugador', TRUE,  'activo'),
(3,  'PB00003', 'AFI-00003', 'Jude Bellingham',        'jugador', TRUE,  'activo'),
(4,  'PB00004', 'AFI-00004', 'Federico Valverde',      'jugador', TRUE,  'activo'),
(5,  'PB00005', 'AFI-00005', 'Luka Modric',            'jugador', TRUE,  'activo'),
(6,  'PB00006', 'AFI-00006', 'Thibaut Courtois',       'jugador', TRUE,  'activo'),
-- PSG (equipo 2)
(7,  'PB00007', 'AFI-00007', 'Ousmane Dembele',        'jugador', TRUE,  'activo'),
(8,  'PB00008', 'AFI-00008', 'Achraf Hakimi',          'jugador', TRUE,  'activo'),
(9,  'PB00009', 'AFI-00009', 'Marquinhos',             'jugador', TRUE,  'activo'),
(10, 'PB00010', 'AFI-00010', 'Vitinha',                'jugador', TRUE,  'activo'),
(11, 'PB00011', 'AFI-00011', 'Khvicha Kvaratskhelia',  'jugador', TRUE,  'activo'),
(12, 'PB00012', 'AFI-00012', 'Gianluigi Donnarumma',   'jugador', TRUE,  'activo'),
-- Barcelona (equipo 3)
(13, 'PB00013', 'AFI-00013', 'Robert Lewandowski',     'jugador', TRUE,  'activo'),
(14, 'PB00014', 'AFI-00014', 'Lamine Yamal',           'jugador', TRUE,  'activo'),
(15, 'PB00015', 'AFI-00015', 'Pedri',                  'jugador', TRUE,  'activo'),
(16, 'PB00016', 'AFI-00016', 'Raphinha',               'jugador', TRUE,  'activo'),
(17, 'PB00017', 'AFI-00017', 'Frenkie de Jong',        'jugador', TRUE,  'activo'),
(18, 'PB00018', 'AFI-00018', 'Marc-Andre ter Stegen',  'jugador', TRUE,  'activo'),
-- Borussia Dortmund (equipo 4)
(19, 'PB00019', 'AFI-00019', 'Serhou Guirassy',        'jugador', TRUE,  'activo'),
(20, 'PB00020', 'AFI-00020', 'Karim Adeyemi',          'jugador', TRUE,  'activo'),
(21, 'PB00021', 'AFI-00021', 'Julian Brandt',          'jugador', TRUE,  'activo'),
(22, 'PB00022', 'AFI-00022', 'Marcel Sabitzer',        'jugador', TRUE,  'activo'),
(23, 'PB00023', 'AFI-00023', 'Nico Schlotterbeck',     'jugador', TRUE,  'activo'),
(24, 'PB00024', 'AFI-00024', 'Gregor Kobel',           'jugador', TRUE,  'activo'),
-- Bayern Munich (equipo 5)
(25, 'PB00025', 'AFI-00025', 'Harry Kane',             'jugador', TRUE,  'activo'),
(26, 'PB00026', 'AFI-00026', 'Jamal Musiala',          'jugador', TRUE,  'activo'),
(27, 'PB00027', 'AFI-00027', 'Joshua Kimmich',         'jugador', TRUE,  'activo'),
(28, 'PB00028', 'AFI-00028', 'Thomas Muller',          'jugador', TRUE,  'activo'),
(29, 'PB00029', 'AFI-00029', 'Alphonso Davies',        'jugador', TRUE,  'activo'),
(30, 'PB00030', 'AFI-00030', 'Manuel Neuer',           'jugador', TRUE,  'activo'),
-- Everton (equipo 6)
(31, 'PB00031', 'AFI-00031', 'Dominic Calvert-Lewin',  'jugador', TRUE,  'activo'),
(32, 'PB00032', 'AFI-00032', 'Dwight McNeil',          'jugador', TRUE,  'activo'),
(33, 'PB00033', 'AFI-00033', 'Idrissa Gueye',          'jugador', TRUE,  'activo'),
(34, 'PB00034', 'AFI-00034', 'James Garner',           'jugador', TRUE,  'activo'),
(35, 'PB00035', 'AFI-00035', 'James Tarkowski',        'jugador', TRUE,  'activo'),
(36, 'PB00036', 'AFI-00036', 'Jordan Pickford',        'jugador', TRUE,  'activo'),
-- Monterrey (equipo 7)
(37, 'PB00037', 'AFI-00037', 'Sergio Ramos',           'jugador', TRUE,  'activo'),
(38, 'PB00038', 'AFI-00038', 'Sergio Canales',         'jugador', TRUE,  'activo'),
(39, 'PB00039', 'AFI-00039', 'Lucas Ocampos',          'jugador', TRUE,  'activo'),
(40, 'PB00040', 'AFI-00040', 'German Berterame',       'jugador', TRUE,  'activo'),
(41, 'PB00041', 'AFI-00041', 'Jesus Corona',           'jugador', TRUE,  'activo'),
(42, 'PB00042', 'AFI-00042', 'Esteban Andrada',        'jugador', TRUE,  'activo'),
-- Tigres (equipo 8)
(43, 'PB00043', 'AFI-00043', 'Andre-Pierre Gignac',    'jugador', TRUE,  'activo'),
(44, 'PB00044', 'AFI-00044', 'Nahuel Guzman',          'jugador', TRUE,  'activo'),
(45, 'PB00045', 'AFI-00045', 'Juan Brunetta',          'jugador', TRUE,  'activo'),
(46, 'PB00046', 'AFI-00046', 'Guido Pizarro',          'jugador', TRUE,  'activo'),
(47, 'PB00047', 'AFI-00047', 'Fernando Gorriaran',     'jugador', TRUE,  'activo'),
(48, 'PB00048', 'AFI-00048', 'Rafael Carioca',         'jugador', TRUE,  'activo'),
-- Manchester City (equipo 9)
(49, 'PB00049', 'AFI-00049', 'Erling Haaland',         'jugador', TRUE,  'activo'),
(50, 'PB00050', 'AFI-00050', 'Kevin De Bruyne',        'jugador', TRUE,  'activo'),
(51, 'PB00051', 'AFI-00051', 'Phil Foden',             'jugador', TRUE,  'activo'),
(52, 'PB00052', 'AFI-00052', 'Rodri',                  'jugador', TRUE,  'activo'),
(53, 'PB00053', 'AFI-00053', 'Ruben Dias',             'jugador', TRUE,  'activo'),
(54, 'PB00054', 'AFI-00054', 'Ederson',                'jugador', TRUE,  'activo'),
-- Inter de Milan (equipo 10)
(55, 'PB00055', 'AFI-00055', 'Lautaro Martinez',       'jugador', TRUE,  'activo'),
(56, 'PB00056', 'AFI-00056', 'Nicolo Barella',         'jugador', TRUE,  'activo'),
(57, 'PB00057', 'AFI-00057', 'Hakan Calhanoglu',       'jugador', TRUE,  'activo'),
(58, 'PB00058', 'AFI-00058', 'Alessandro Bastoni',     'jugador', TRUE,  'activo'),
(59, 'PB00059', 'AFI-00059', 'Federico Dimarco',       'jugador', TRUE,  'activo'),
(60, 'PB00060', 'AFI-00060', 'Yann Sommer',            'jugador', TRUE,  'activo'),
-- Usuarios regulares (sin equipo, no jugadores)
(61, 'PB00061', 'AFI-00061', 'Usuario Demo 1',         'usuario', FALSE, 'activo'),
(62, 'PB00062', 'AFI-00062', 'Usuario Demo 2',         'usuario', FALSE, 'activo'),
(63, 'PB00063', 'AFI-00063', 'Usuario Demo 3',         'usuario', FALSE, 'activo'),
(64, 'PB00064', 'AFI-00064', 'Usuario Demo 4',         'usuario', FALSE, 'activo'),
(65, 'PB00065', 'AFI-00065', 'Usuario Demo 5',         'usuario', FALSE, 'activo');

-- ---------------------------------------------------------
-- equipos (10 oficiales, todos con capitan activo)
-- ---------------------------------------------------------
INSERT INTO equipos (id, nombre, logo, capitan_id, estado) VALUES
(1,  'Real Madrid',       NULL, 5,  'activo'), -- capitan: Luka Modric
(2,  'PSG',               NULL, 9,  'activo'), -- capitan: Marquinhos
(3,  'Barcelona',         NULL, 17, 'activo'), -- capitan: Frenkie de Jong
(4,  'Borussia Dortmund', NULL, 22, 'activo'), -- capitan: Marcel Sabitzer
(5,  'Bayern Munich',     NULL, 27, 'activo'), -- capitan: Joshua Kimmich
(6,  'Everton',           NULL, 35, 'activo'), -- capitan: James Tarkowski
(7,  'Monterrey',         NULL, 37, 'activo'), -- capitan: Sergio Ramos
(8,  'Tigres',            NULL, 46, 'activo'), -- capitan: Guido Pizarro
(9,  'Manchester City',   NULL, 52, 'activo'), -- capitan: Rodri
(10, 'Inter de Milan',    NULL, 55, 'activo'); -- capitan: Lautaro Martinez

-- ---------------------------------------------------------
-- equipo_miembros (60 membresias activas, 6 por equipo)
-- ---------------------------------------------------------
INSERT INTO equipo_miembros (equipo_id, jugador_id, fecha_union, estado) VALUES
-- Real Madrid
(1, 1,  '2026-07-15 09:00:00', 'activo'),
(1, 2,  '2026-07-15 09:00:00', 'activo'),
(1, 3,  '2026-07-15 09:00:00', 'activo'),
(1, 4,  '2026-07-15 09:00:00', 'activo'),
(1, 5,  '2026-07-15 09:00:00', 'activo'),
(1, 6,  '2026-07-15 09:00:00', 'activo'),
-- PSG
(2, 7,  '2026-07-15 09:00:00', 'activo'),
(2, 8,  '2026-07-15 09:00:00', 'activo'),
(2, 9,  '2026-07-15 09:00:00', 'activo'),
(2, 10, '2026-07-15 09:00:00', 'activo'),
(2, 11, '2026-07-15 09:00:00', 'activo'),
(2, 12, '2026-07-15 09:00:00', 'activo'),
-- Barcelona
(3, 13, '2026-07-15 09:00:00', 'activo'),
(3, 14, '2026-07-15 09:00:00', 'activo'),
(3, 15, '2026-07-15 09:00:00', 'activo'),
(3, 16, '2026-07-15 09:00:00', 'activo'),
(3, 17, '2026-07-15 09:00:00', 'activo'),
(3, 18, '2026-07-15 09:00:00', 'activo'),
-- Borussia Dortmund
(4, 19, '2026-07-15 09:00:00', 'activo'),
(4, 20, '2026-07-15 09:00:00', 'activo'),
(4, 21, '2026-07-15 09:00:00', 'activo'),
(4, 22, '2026-07-15 09:00:00', 'activo'),
(4, 23, '2026-07-15 09:00:00', 'activo'),
(4, 24, '2026-07-15 09:00:00', 'activo'),
-- Bayern Munich
(5, 25, '2026-07-15 09:00:00', 'activo'),
(5, 26, '2026-07-15 09:00:00', 'activo'),
(5, 27, '2026-07-15 09:00:00', 'activo'),
(5, 28, '2026-07-15 09:00:00', 'activo'),
(5, 29, '2026-07-15 09:00:00', 'activo'),
(5, 30, '2026-07-15 09:00:00', 'activo'),
-- Everton
(6, 31, '2026-07-15 09:00:00', 'activo'),
(6, 32, '2026-07-15 09:00:00', 'activo'),
(6, 33, '2026-07-15 09:00:00', 'activo'),
(6, 34, '2026-07-15 09:00:00', 'activo'),
(6, 35, '2026-07-15 09:00:00', 'activo'),
(6, 36, '2026-07-15 09:00:00', 'activo'),
-- Monterrey
(7, 37, '2026-07-15 09:00:00', 'activo'),
(7, 38, '2026-07-15 09:00:00', 'activo'),
(7, 39, '2026-07-15 09:00:00', 'activo'),
(7, 40, '2026-07-15 09:00:00', 'activo'),
(7, 41, '2026-07-15 09:00:00', 'activo'),
(7, 42, '2026-07-15 09:00:00', 'activo'),
-- Tigres
(8, 43, '2026-07-15 09:00:00', 'activo'),
(8, 44, '2026-07-15 09:00:00', 'activo'),
(8, 45, '2026-07-15 09:00:00', 'activo'),
(8, 46, '2026-07-15 09:00:00', 'activo'),
(8, 47, '2026-07-15 09:00:00', 'activo'),
(8, 48, '2026-07-15 09:00:00', 'activo'),
-- Manchester City
(9, 49, '2026-07-15 09:00:00', 'activo'),
(9, 50, '2026-07-15 09:00:00', 'activo'),
(9, 51, '2026-07-15 09:00:00', 'activo'),
(9, 52, '2026-07-15 09:00:00', 'activo'),
(9, 53, '2026-07-15 09:00:00', 'activo'),
(9, 54, '2026-07-15 09:00:00', 'activo'),
-- Inter de Milan
(10, 55, '2026-07-15 09:00:00', 'activo'),
(10, 56, '2026-07-15 09:00:00', 'activo'),
(10, 57, '2026-07-15 09:00:00', 'activo'),
(10, 58, '2026-07-15 09:00:00', 'activo'),
(10, 59, '2026-07-15 09:00:00', 'activo'),
(10, 60, '2026-07-15 09:00:00', 'activo');