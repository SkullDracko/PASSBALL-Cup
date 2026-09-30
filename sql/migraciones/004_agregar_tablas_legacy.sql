-- ---------------------------------------------------------
-- PASSBALL Cup · Migración 004
-- Agrega las tablas del esquema anterior (legacy)
--
-- Estas tablas NO son parte del esquema actual de bd_propuesta.sql, pero
-- se dejan disponibles para los módulos que todavía las necesiten
-- (apuestas, fotos, alineaciones, votos, etc.).
--
-- Requisito: las tablas `partidos`, `equipos` y `posts` del esquema actual
-- deben existir antes de correr este archivo.
--
-- NO se crea `posts`: ya existe en el esquema actual y su clave foránea
-- apunta a `usuarios` (no a `usuarios_passball` como en el esquema viejo).
-- Este archivo es idempotente: se puede volver a correr sin error.
-- ---------------------------------------------------------
-- Alineaciones (previa o en tiempo real)
CREATE TABLE IF NOT EXISTS alineaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    equipo_id INT NOT NULL,
    tipo ENUM('previa','real_time') DEFAULT 'previa',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
) ENGINE=InnoDB;

-- Jugadores dentro de una alineación
CREATE TABLE IF NOT EXISTS alineacion_jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alineacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    numero INT DEFAULT NULL,
    posicion VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (alineacion_id) REFERENCES alineaciones(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios_passball(id)
) ENGINE=InnoDB;

-- Apuestas (predicciones de marcador)
CREATE TABLE IF NOT EXISTS apuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    partido_id INT NOT NULL,
    goles_local_pred INT NOT NULL,
    goles_visitante_pred INT NOT NULL,
    puntos INT DEFAULT 0,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_passball(id),
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    UNIQUE KEY uq_apuesta_usuario_partido (usuario_id, partido_id)
) ENGINE=InnoDB;

-- Votación al mejor jugador del partido
CREATE TABLE IF NOT EXISTS votaciones_jugador (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    partido_id INT NOT NULL,
    votado_id INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_passball(id),
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    FOREIGN KEY (votado_id) REFERENCES usuarios_passball(id),
    UNIQUE KEY uq_voto_usuario_partido (usuario_id, partido_id)
) ENGINE=InnoDB;

-- Goles (tabla histórica del goleo)
CREATE TABLE IF NOT EXISTS goles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    jugador_id INT NOT NULL,
    minuto INT DEFAULT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    FOREIGN KEY (jugador_id) REFERENCES usuarios_passball(id)
) ENGINE=InnoDB;

-- Fotos del evento (admin)
CREATE TABLE IF NOT EXISTS fotos_evento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) DEFAULT NULL,
    descripcion TEXT DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    subido_por INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subido_por) REFERENCES usuarios_passball(id)
) ENGINE=InnoDB;

-- Fotos de equipo/jugador
CREATE TABLE IF NOT EXISTS fotos_equipo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT DEFAULT NULL,
    usuario_id INT DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    subido_por INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios_passball(id),
    FOREIGN KEY (subido_por) REFERENCES usuarios_passball(id)
) ENGINE=InnoDB;

-- Comentarios de las publicaciones
CREATE TABLE IF NOT EXISTS comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    usuario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios_passball(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Verificación
-- ---------------------------------------------------------
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
    'usuarios_passball', 'alineaciones', 'alineacion_jugadores',
    'apuestas', 'votaciones_jugador', 'goles',
    'fotos_evento', 'fotos_equipo', 'comentarios'
  )
ORDER BY TABLE_NAME;
