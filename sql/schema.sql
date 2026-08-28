-- ============================================
-- PASSBALL Cup - Esquema de Base de Datos
-- ============================================

CREATE DATABASE IF NOT EXISTS passballcup
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE passballcup;

-- -------------------------------------------
-- 1. Usuarios (cache del login vía AFI Hub)
-- -------------------------------------------
CREATE TABLE usuarios_passball (
    id INT AUTO_INCREMENT PRIMARY KEY,
    afi_usuario_id INT NOT NULL,
    matricula VARCHAR(7) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellidop VARCHAR(100) NOT NULL,
    apellidom VARCHAR(100) NOT NULL,
    semestre INT NOT NULL,
    rol ENUM('admin','lider','miembro') DEFAULT 'miembro',
    avatar VARCHAR(255) DEFAULT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- -------------------------------------------
-- 2. Equipos
-- -------------------------------------------
CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    lider_id INT NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    color_equipo VARCHAR(7) DEFAULT '#7c4293',
    descripcion TEXT DEFAULT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (lider_id) REFERENCES usuarios_passball(id)
) ENGINE=InnoDB;

-- -------------------------------------------
-- 3. Miembros de equipo
-- -------------------------------------------
CREATE TABLE equipo_miembros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_union DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    FOREIGN KEY (equipo_id) REFERENCES equipos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios_passball(id),
    UNIQUE KEY uq_equipo_usuario (equipo_id, usuario_id)
) ENGINE=InnoDB;

-- -------------------------------------------
-- 4. Partidos
-- -------------------------------------------
CREATE TABLE partidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_local_id INT NOT NULL,
    equipo_visita_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME DEFAULT NULL,
    cancha VARCHAR(100) DEFAULT NULL,
    estado ENUM('programado','en_juego','finalizado') DEFAULT 'programado',
    goles_local INT DEFAULT 0,
    goles_visita INT DEFAULT 0,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_local_id) REFERENCES equipos(id),
    FOREIGN KEY (equipo_visita_id) REFERENCES equipos(id)
) ENGINE=InnoDB;

-- -------------------------------------------
-- 5. Alineaciones
-- -------------------------------------------
CREATE TABLE alineaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    equipo_id INT NOT NULL,
    tipo ENUM('previa','real_time') DEFAULT 'previa',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
) ENGINE=InnoDB;

-- -------------------------------------------
-- 6. Jugadores en alineación
-- -------------------------------------------
CREATE TABLE alineacion_jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alineacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    numero INT DEFAULT NULL,
    posicion VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (alineacion_id) REFERENCES alineaciones(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios_passball(id)
) ENGINE=InnoDB;

-- -------------------------------------------
-- 7. Apuestas (predicciones de marcador)
-- -------------------------------------------
CREATE TABLE apuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    partido_id INT NOT NULL,
    goles_local_pred INT NOT NULL,
    goles_visita_pred INT NOT NULL,
    puntos INT DEFAULT 0,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_passball(id),
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    UNIQUE KEY uq_apuesta_usuario_partido (usuario_id, partido_id)
) ENGINE=InnoDB;

-- -------------------------------------------
-- 8. Votaciones mejor jugador
-- -------------------------------------------
CREATE TABLE votaciones_jugador (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    partido_id INT NOT NULL,
    votado_id INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_passball(id),
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    FOREIGN KEY (votado_id) REFERENCES usuarios_passball(id),
    UNIQUE KEY uq_voto_usuario_partido (usuario_id, partido_id)
) ENGINE=InnoDB;

-- -------------------------------------------
-- 9. Goles (para top goleador)
-- -------------------------------------------
CREATE TABLE goles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    jugador_id INT NOT NULL,
    minuto INT DEFAULT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    FOREIGN KEY (jugador_id) REFERENCES usuarios_passball(id)
) ENGINE=InnoDB;

-- -------------------------------------------
-- 10. Posts
-- -------------------------------------------
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    contenido TEXT NOT NULL,
    imagen_url VARCHAR(255) DEFAULT NULL,
    likes INT DEFAULT 0,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_passball(id)
) ENGINE=InnoDB;

-- -------------------------------------------
-- 11. Comentarios
-- -------------------------------------------
CREATE TABLE comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    usuario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios_passball(id)
) ENGINE=InnoDB;

-- -------------------------------------------
-- 12. Fotos del evento (admin)
-- -------------------------------------------
CREATE TABLE fotos_evento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) DEFAULT NULL,
    descripcion TEXT DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    subido_por INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subido_por) REFERENCES usuarios_passball(id)
) ENGINE=InnoDB;

-- -------------------------------------------
-- 13. Fotos de equipo/jugador
-- -------------------------------------------
CREATE TABLE fotos_equipo (
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

-- -------------------------------------------
-- Admin inicial (matrícula 0000000 = admin)
-- -------------------------------------------
INSERT INTO usuarios_passball (afi_usuario_id, matricula, nombre, apellidop, apellidom, semestre, rol)
VALUES (0, '0000000', 'Admin', 'PASSBALL', 'Cup', 1, 'admin');
