-- ---------------------------------------------------------
-- PASSBALL Cup · Migración 001
-- Posición de jugador en el equipo (alineaciones)
-- Columna: equipo_miembros.posicion
-- Valores: POR (portero), DEF (defensa), MED (medio), DEL (delantero)
-- ---------------------------------------------------------

ALTER TABLE equipo_miembros
    ADD COLUMN posicion ENUM('POR', 'DEF', 'MED', 'DEL') NOT NULL DEFAULT 'DEL' AFTER estado;

-- ---------------------------------------------------------
-- Seed de posiciones para el equipo de prueba (Los Mucucurru)
-- ---------------------------------------------------------
UPDATE equipo_miembros SET posicion = 'POR' WHERE equipo_id = 14 AND jugador_id = 28;
UPDATE equipo_miembros SET posicion = 'DEF' WHERE equipo_id = 14 AND jugador_id = 26;
UPDATE equipo_miembros SET posicion = 'MED' WHERE equipo_id = 14 AND jugador_id = 27;
UPDATE equipo_miembros SET posicion = 'DEL' WHERE equipo_id = 14 AND jugador_id = 29;