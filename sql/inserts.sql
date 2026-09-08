-- =========================================================
-- PASSBALL Cup - Datos de prueba (seed) para passballcup
-- Basado en la estructura definida en schema_2.sql
-- Torneo de eliminacion directa con 8 equipos (16 jugadores,
-- 2 por equipo: 1 lider/capitan y 1 miembro normal)
-- =========================================================

USE passballcup;

-- ---------------------------------------------------------
-- Limpieza de datos insertados previamente (orden respeta FKs)
-- ---------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE partido_estadisticas_portero;
TRUNCATE TABLE partido_eventos;
TRUNCATE TABLE partido_convocados;
TRUNCATE TABLE partidos;
TRUNCATE TABLE torneo_rondas;
TRUNCATE TABLE torneo_equipos;
TRUNCATE TABLE torneos;
TRUNCATE TABLE equipo_miembros;
TRUNCATE TABLE equipos;
TRUNCATE TABLE administradores;
TRUNCATE TABLE usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- usuarios (2 administradores + 16 jugadores repartidos en 8 equipos)
-- ---------------------------------------------------------
INSERT INTO usuarios (id, matricula, afi_usuario_id, rol, avatar, jugador_activo, estado) VALUES
(1,  'ADM001', 'AFI-ADM-001', 'administrador', NULL, FALSE, 'activo'),
(2,  'ADM002', 'AFI-ADM-002', 'administrador', NULL, FALSE, 'activo'),
-- Equipo 1
(3,  'JUG003', 'AFI-003', 'usuario', NULL, TRUE, 'activo'),
(4,  'JUG004', 'AFI-004', 'usuario', NULL, TRUE, 'activo'),
-- Equipo 2
(5,  'JUG005', 'AFI-005', 'usuario', NULL, TRUE, 'activo'),
(6,  'JUG006', 'AFI-006', 'usuario', NULL, TRUE, 'activo'),
-- Equipo 3
(7,  'JUG007', 'AFI-007', 'usuario', NULL, TRUE, 'activo'),
(8,  'JUG008', 'AFI-008', 'usuario', NULL, TRUE, 'activo'),
-- Equipo 4
(9,  'JUG009', 'AFI-009', 'usuario', NULL, TRUE, 'activo'),
(10, 'JUG010', 'AFI-010', 'usuario', NULL, TRUE, 'activo'),
-- Equipo 5
(11, 'JUG011', 'AFI-011', 'usuario', NULL, TRUE, 'activo'),
(12, 'JUG012', 'AFI-012', 'usuario', NULL, TRUE, 'activo'),
-- Equipo 6
(13, 'JUG013', 'AFI-013', 'usuario', NULL, TRUE, 'activo'),
(14, 'JUG014', 'AFI-014', 'usuario', NULL, TRUE, 'activo'),
-- Equipo 7
(15, 'JUG015', 'AFI-015', 'usuario', NULL, TRUE, 'activo'),
(16, 'JUG016', 'AFI-016', 'usuario', NULL, TRUE, 'activo'),
-- Equipo 8
(17, 'JUG017', 'AFI-017', 'usuario', NULL, TRUE, 'activo'),
(18, 'JUG018', 'AFI-018', 'usuario', NULL, TRUE, 'activo');

-- ---------------------------------------------------------
-- administradores
-- ---------------------------------------------------------
INSERT INTO administradores (id, usuario_id, activo) VALUES
(1, 1, TRUE),
(2, 2, TRUE);

-- ---------------------------------------------------------
-- equipos (capitan_id = jugador "lider" de cada pareja)
-- ---------------------------------------------------------
INSERT INTO equipos (id, nombre, logo, capitan_id, estado) VALUES
(1, 'Aguilas FC',      NULL, 3,  'activo'),
(2, 'Tigres United',   NULL, 5,  'activo'),
(3, 'Halcones SC',     NULL, 7,  'activo'),
(4, 'Leones FC',       NULL, 9,  'activo'),
(5, 'Panteras CF',     NULL, 11, 'activo'),
(6, 'Cobras FC',       NULL, 13, 'activo'),
(7, 'Toros United',    NULL, 15, 'activo'),
(8, 'Lobos SC',        NULL, 17, 'activo');

-- ---------------------------------------------------------
-- equipo_miembros (por equipo: 1 lider/capitan + 1 miembro normal)
-- ---------------------------------------------------------
INSERT INTO equipo_miembros (equipo_id, jugador_id, estado) VALUES
(1, 3,  'activo'), (1, 4,  'activo'),
(2, 5,  'activo'), (2, 6,  'activo'),
(3, 7,  'activo'), (3, 8,  'activo'),
(4, 9,  'activo'), (4, 10, 'activo'),
(5, 11, 'activo'), (5, 12, 'activo'),
(6, 13, 'activo'), (6, 14, 'activo'),
(7, 15, 'activo'), (7, 16, 'activo'),
(8, 17, 'activo'), (8, 18, 'activo');

-- ---------------------------------------------------------
-- torneos
-- ---------------------------------------------------------
INSERT INTO torneos (id, nombre, tipo, fecha_inicio, fecha_fin, estado) VALUES
(1, 'Copa PASSBALL 2026', 'eliminacion_directa', '2026-08-01', '2026-08-22', 'finalizado');

-- ---------------------------------------------------------
-- torneo_equipos (los 8 equipos aprobados)
-- ---------------------------------------------------------
INSERT INTO torneo_equipos (torneo_id, equipo_id, estado, fecha_aprobacion, aprobado_por) VALUES
(1, 1, 'aprobado', '2026-07-20 10:00:00', 1),
(1, 2, 'aprobado', '2026-07-20 10:05:00', 1),
(1, 3, 'aprobado', '2026-07-20 10:10:00', 1),
(1, 4, 'aprobado', '2026-07-20 10:15:00', 1),
(1, 5, 'aprobado', '2026-07-20 10:20:00', 2),
(1, 6, 'aprobado', '2026-07-20 10:25:00', 2),
(1, 7, 'aprobado', '2026-07-20 10:30:00', 2),
(1, 8, 'aprobado', '2026-07-20 10:35:00', 2);

-- ---------------------------------------------------------
-- torneo_rondas
-- ---------------------------------------------------------
INSERT INTO torneo_rondas (id, torneo_id, nombre, orden) VALUES
(1, 1, 'Cuartos de Final', 1),
(2, 1, 'Semifinal', 2),
(3, 1, 'Final', 3);

-- ---------------------------------------------------------
-- partidos
-- Cuartos: 1vs2->1, 3vs4->4, 5vs6->5, 7vs8->8
-- Semis:   1vs4->1, 5vs8->8
-- Final:   1vs8->1  (campeon: Aguilas FC)
-- ---------------------------------------------------------
INSERT INTO partidos (id, ronda_id, equipo_local_id, equipo_visitante_id, posicion, fecha_hora, cancha,
  goles_local, goles_visitante, penales_local, penales_visitante, ganador_id, estado) VALUES
(1, 1, 1, 2, 1, '2026-08-08 15:00:00', 'Cancha 1', 3, 1, NULL, NULL, 1, 'finalizado'),
(2, 1, 3, 4, 2, '2026-08-08 16:00:00', 'Cancha 1', 1, 2, NULL, NULL, 4, 'finalizado'),
(3, 1, 5, 6, 3, '2026-08-08 17:00:00', 'Cancha 2', 2, 0, NULL, NULL, 5, 'finalizado'),
(4, 1, 7, 8, 4, '2026-08-08 18:00:00', 'Cancha 2', 1, 3, NULL, NULL, 8, 'finalizado'),
(5, 2, 1, 4, 1, '2026-08-15 16:00:00', 'Cancha Central', 3, 2, NULL, NULL, 1, 'finalizado'),
(6, 2, 5, 8, 2, '2026-08-15 18:00:00', 'Cancha Central', 1, 2, NULL, NULL, 8, 'finalizado'),
(7, 3, 1, 8, 1, '2026-08-22 17:00:00', 'Cancha Central', 2, 1, NULL, NULL, 1, 'finalizado');

-- ---------------------------------------------------------
-- partido_convocados (convocatoria del partido final, id 7)
-- ---------------------------------------------------------
INSERT INTO partido_convocados (partido_id, jugador_id, equipo_id, titular, posicion) VALUES
(7, 3,  1, TRUE, 'delantero'),
(7, 4,  1, TRUE, 'portero'),
(7, 17, 8, TRUE, 'delantero'),
(7, 18, 8, TRUE, 'portero');

-- ---------------------------------------------------------
-- partido_eventos (goles del partido final, id 7)
-- ---------------------------------------------------------
INSERT INTO partido_eventos (partido_id, jugador_id, equipo_id, tipo, minuto, asistencia_jugador_id) VALUES
(7, 3,  1, 'gol', 15, 4),
(7, 3,  1, 'gol', 60, NULL),
(7, 17, 8, 'gol', 30, 18);

-- ---------------------------------------------------------
-- partido_estadisticas_portero (partido final, id 7)
-- ---------------------------------------------------------
INSERT INTO partido_estadisticas_portero (partido_id, jugador_id, atajadas, goles_recibidos) VALUES
(7, 4,  3, 1),
(7, 18, 4, 2);
