-- ============================================================
-- PASSBALL Cup - Esquema de Base de Datos (DEFINITIVO)
-- Fuente: bd_propuesta.sql + inserts.sql + migracion_admin.sql
-- Esquema único: crea BD, tablas, columnas admin y comunidades.
-- ============================================================

CREATE DATABASE IF NOT EXISTS passballcup
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE passballcup;

SET FOREIGN_KEY_CHECKS = 0;

-- Tablas legacy (esquema anterior) por si quedan restos
DROP TABLE IF EXISTS alineacion_jugadores;
DROP TABLE IF EXISTS alineaciones;
DROP TABLE IF EXISTS apuestas;
DROP TABLE IF EXISTS comentarios;
DROP TABLE IF EXISTS fotos_equipo;
DROP TABLE IF EXISTS fotos_evento;
DROP TABLE IF EXISTS goles;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS usuarios_passball;
DROP TABLE IF EXISTS votaciones_jugador;

DROP TABLE IF EXISTS torneo_votos;
DROP TABLE IF EXISTS torneo_categoria_candidatos;
DROP TABLE IF EXISTS torneo_categorias_voto;
DROP TABLE IF EXISTS partido_estadisticas_portero;
DROP TABLE IF EXISTS partido_eventos;
DROP TABLE IF EXISTS partido_convocados;
DROP TABLE IF EXISTS partidos;
DROP TABLE IF EXISTS torneo_rondas;
DROP TABLE IF EXISTS torneo_equipos;
DROP TABLE IF EXISTS torneos;
DROP TABLE IF EXISTS equipo_miembros;
DROP TABLE IF EXISTS equipos;
DROP TABLE IF EXISTS administradores;
DROP TABLE IF EXISTS usuarios;


CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matricula VARCHAR(20) NOT NULL UNIQUE,
  afi_usuario_id VARCHAR(50) UNIQUE,
  rol ENUM('usuario','administrador') NOT NULL DEFAULT 'usuario',
  avatar VARCHAR(255),
  jugador_activo BOOLEAN NOT NULL DEFAULT TRUE, -- Indica si el jugador puede participar en los partidos (al terminar el afiliado, deberÃ­a de ponerse como 0)
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo', -- Sirve para habilitar o deshabilitar al usuario en el sistema
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tabla independiente de usuarios: el mÃ³dulo de administraciÃ³n maneja su propio acceso
CREATE TABLE administradores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,
  activo BOOLEAN NOT NULL DEFAULT TRUE,
  fecha_alta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE equipos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  logo VARCHAR(255),
  capitan_id INT NOT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_equipos_capitan
    FOREIGN KEY (capitan_id)
    REFERENCES usuarios(id),

  INDEX idx_equipos_capitan (capitan_id),
  INDEX idx_equipos_estado (estado)
);

-- 
CREATE TABLE equipo_miembros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equipo_id INT NOT NULL,
  jugador_id INT NOT NULL,
  fecha_union TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_salida TIMESTAMP NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',

  -- Permite que un jugador tenga solo una membresÃ­a activa a la vez. (Evita que un jugador estÃ© activo en mÃ¡s de un equipo simultÃ¡neamente)
  jugador_membresia_activa_unica INT GENERATED ALWAYS AS (
    CASE
      WHEN estado = 'activo' THEN jugador_id
      ELSE NULL
    END
  ) STORED,

  CONSTRAINT fk_miembros_equipo
    FOREIGN KEY (equipo_id)
    REFERENCES equipos(id),

  CONSTRAINT fk_miembros_jugador
    FOREIGN KEY (jugador_id)
    REFERENCES usuarios(id),

  UNIQUE KEY uq_jugador_membresia_activa (
    jugador_membresia_activa_unica
  ),

  INDEX idx_miembros_equipo_estado (
    equipo_id,
    estado
  ),

  INDEX idx_miembros_jugador_estado (
    jugador_id,
    estado
  )
);

CREATE TABLE torneos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,

  tipo ENUM(
    'eliminacion_directa'
  ) NOT NULL DEFAULT 'eliminacion_directa',

  fecha_inicio DATE,
  fecha_fin DATE,

  estado ENUM(
    'programado',
    'en_curso',
    'finalizado',
    'cancelado'
  ) NOT NULL DEFAULT 'programado',

  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE torneo_equipos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  torneo_id INT NOT NULL,
  equipo_id INT NOT NULL,

  estado ENUM(
    'pendiente',
    'aprobado',
    'rechazado',
    'retirado'
  ) NOT NULL DEFAULT 'pendiente',

  fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_aprobacion TIMESTAMP NULL,

  aprobado_por INT NULL,

  CONSTRAINT fk_te_torneo
    FOREIGN KEY (torneo_id)
    REFERENCES torneos(id),

  CONSTRAINT fk_te_equipo
    FOREIGN KEY (equipo_id)
    REFERENCES equipos(id),

  CONSTRAINT fk_te_aprobado_por
    FOREIGN KEY (aprobado_por)
    REFERENCES administradores(id),

  UNIQUE KEY uq_torneo_equipo (
    torneo_id,
    equipo_id
  ),

  INDEX idx_te_torneo_estado (
    torneo_id,
    estado
  )
);

CREATE TABLE torneo_rondas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  torneo_id INT NOT NULL,
  nombre VARCHAR(50) NOT NULL,
  orden INT NOT NULL,

  CONSTRAINT fk_ronda_torneo
    FOREIGN KEY (torneo_id)
    REFERENCES torneos(id),

  UNIQUE KEY uq_torneo_orden (
    torneo_id,
    orden
  )
);

CREATE TABLE partidos (
  id INT AUTO_INCREMENT PRIMARY KEY,

  ronda_id INT NOT NULL,

  equipo_local_id INT NULL,
  equipo_visitante_id INT NULL,

  -- Partido de la ronda anterior cuyo ganador ocupa el lado local/visitante (NULL en la primera ronda)
  partido_origen_local_id INT NULL,
  partido_origen_visitante_id INT NULL,

  -- Dar un orden de despliegue fijo y determinista para los partidos de una ronda
  posicion INT NOT NULL,

  fecha_hora DATETIME NULL,
  cancha VARCHAR(100),

  goles_local INT NULL,
  goles_visitante INT NULL,

  penales_local INT NULL,
  penales_visitante INT NULL,

  ganador_id INT NULL,

  estado ENUM(
    'programado',
    'en_curso',
    'finalizado',
    'cancelado'
  ) NOT NULL DEFAULT 'programado',

  CONSTRAINT fk_partido_ronda
    FOREIGN KEY (ronda_id)
    REFERENCES torneo_rondas(id),

  CONSTRAINT fk_partido_local
    FOREIGN KEY (equipo_local_id)
    REFERENCES equipos(id),

  CONSTRAINT fk_partido_visitante
    FOREIGN KEY (equipo_visitante_id)
    REFERENCES equipos(id),

  CONSTRAINT fk_partido_ganador
    FOREIGN KEY (ganador_id)
    REFERENCES equipos(id),

  CONSTRAINT fk_partido_origen_local
    FOREIGN KEY (partido_origen_local_id)
    REFERENCES partidos(id),

  CONSTRAINT fk_partido_origen_visitante
    FOREIGN KEY (partido_origen_visitante_id)
    REFERENCES partidos(id),

  CHECK (
    equipo_local_id IS NULL
    OR equipo_visitante_id IS NULL
    OR equipo_local_id <> equipo_visitante_id
  ),

  CHECK (
    ganador_id IS NULL
    OR ganador_id = equipo_local_id
    OR ganador_id = equipo_visitante_id
  ),

  CHECK (
    partido_origen_local_id IS NULL
    OR partido_origen_visitante_id IS NULL
    OR partido_origen_local_id <> partido_origen_visitante_id
  ),

  INDEX idx_partido_ronda (
    ronda_id
  ),

  INDEX idx_partido_local (
    equipo_local_id
  ),

  INDEX idx_partido_visitante (
    equipo_visitante_id
  ),

  INDEX idx_partido_origen_local (
    partido_origen_local_id
  ),

  INDEX idx_partido_origen_visitante (
    partido_origen_visitante_id
  )
);

CREATE TABLE partido_convocados (
  id INT AUTO_INCREMENT PRIMARY KEY,

  partido_id INT NOT NULL,
  jugador_id INT NOT NULL,
  equipo_id INT NOT NULL,

  titular BOOLEAN NOT NULL DEFAULT TRUE,

  posicion ENUM(
    'portero',
    'defensa',
    'mediocampo',
    'delantero'
  ) NOT NULL,

  CONSTRAINT fk_conv_partido
    FOREIGN KEY (partido_id)
    REFERENCES partidos(id),

  CONSTRAINT fk_conv_jugador
    FOREIGN KEY (jugador_id)
    REFERENCES usuarios(id),

  CONSTRAINT fk_conv_equipo
    FOREIGN KEY (equipo_id)
    REFERENCES equipos(id),

  UNIQUE KEY uq_partido_jugador (
    partido_id,
    jugador_id
  )
);

CREATE TABLE partido_eventos (
  id INT AUTO_INCREMENT PRIMARY KEY,

  partido_id INT NOT NULL,
  jugador_id INT NOT NULL,
  equipo_id INT NOT NULL,

  tipo ENUM(
    'gol',
    'autogol',
    'penal_anotado',
    'tarjeta_amarilla',
    'tarjeta_roja'
  ) NOT NULL,

  minuto TINYINT NULL,

  asistencia_jugador_id INT NULL,

  CONSTRAINT fk_ev_partido
    FOREIGN KEY (partido_id)
    REFERENCES partidos(id),

  CONSTRAINT fk_ev_jugador
    FOREIGN KEY (jugador_id)
    REFERENCES usuarios(id),

  CONSTRAINT fk_ev_equipo
    FOREIGN KEY (equipo_id)
    REFERENCES equipos(id),

  CONSTRAINT fk_ev_asistencia
    FOREIGN KEY (asistencia_jugador_id)
    REFERENCES usuarios(id),

  CHECK (
    asistencia_jugador_id IS NULL
    OR tipo IN ('gol', 'penal_anotado')
  ),

  INDEX idx_eventos_partido (
    partido_id
  ),

  INDEX idx_eventos_jugador (
    jugador_id
  ),

  INDEX idx_eventos_tipo (
    tipo
  )
);

CREATE TABLE partido_estadisticas_portero (
  id INT AUTO_INCREMENT PRIMARY KEY,

  partido_id INT NOT NULL,
  jugador_id INT NOT NULL,

  atajadas SMALLINT NOT NULL DEFAULT 0,
  goles_recibidos SMALLINT NOT NULL DEFAULT 0,

  CONSTRAINT fk_pep_partido
    FOREIGN KEY (partido_id)
    REFERENCES partidos(id),

  CONSTRAINT fk_pep_jugador
    FOREIGN KEY (jugador_id)
    REFERENCES usuarios(id),

  UNIQUE KEY uq_partido_portero (
    partido_id,
    jugador_id
  )
);

-- ============================================================
-- Votaciones / encuestas por torneo
-- ============================================================

CREATE TABLE torneo_categorias_voto (
  id INT AUTO_INCREMENT PRIMARY KEY,

  torneo_id INT NOT NULL,
  clave VARCHAR(30) NOT NULL,        -- 'goleador' | 'atajadas' | 'mvp-final' | ...
  nombre VARCHAR(100) NOT NULL,      -- texto que se muestra en la UI
  tipo ENUM('jugador','equipo') NOT NULL,

  -- 'automatico': el pool de candidatos se deriva de quiÃ©n participÃ³ en el torneo,
  --   con la posibilidad de excluir individuos puntuales.
  -- 'manual': el admin arma la lista de candidatos a mano (ej. MVP de la final).
  modo_candidatos ENUM('automatico','manual') NOT NULL DEFAULT 'automatico',

  -- Fuente de verdad de si la categorÃ­a acepta votos en este momento.
  -- El admin la abre/cierra a conveniencia (no hay ventana de fechas automÃ¡tica).
  estado ENUM('abierta','cerrada') NOT NULL DEFAULT 'cerrada',
  fecha_ultimo_cambio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP, -- informativo: cuÃ¡ndo fue el Ãºltimo cambio de estado

  orden INT NOT NULL DEFAULT 0,

  CONSTRAINT fk_cat_torneo
    FOREIGN KEY (torneo_id)
    REFERENCES torneos(id),

  UNIQUE KEY uq_torneo_categoria_clave (
    torneo_id,
    clave
  )
);

CREATE TABLE torneo_categoria_candidatos (
  id INT AUTO_INCREMENT PRIMARY KEY,

  categoria_id INT NOT NULL,
  jugador_id INT NULL,
  equipo_id INT NULL,

  -- En categorÃ­as modo 'automatico': solo se usan filas 'excluir'
  -- (sacan a alguien del pool derivado del torneo).
  -- En categorÃ­as modo 'manual': solo se usan filas 'incluir'
  -- (arman el pool completo desde cero).
  ajuste ENUM('incluir','excluir') NOT NULL,

  CONSTRAINT fk_cand_categoria
    FOREIGN KEY (categoria_id)
    REFERENCES torneo_categorias_voto(id),

  CONSTRAINT fk_cand_jugador
    FOREIGN KEY (jugador_id)
    REFERENCES usuarios(id),

  CONSTRAINT fk_cand_equipo
    FOREIGN KEY (equipo_id)
    REFERENCES equipos(id),

  CHECK (
    (jugador_id IS NOT NULL AND equipo_id IS NULL)
    OR (jugador_id IS NULL AND equipo_id IS NOT NULL)
  ),

  -- Un jugador o equipo solo puede tener un ajuste por categorÃ­a
  -- (no se puede incluir y excluir a la vez).
  UNIQUE KEY uq_cand_categoria_jugador (
    categoria_id,
    jugador_id
  ),

  UNIQUE KEY uq_cand_categoria_equipo (
    categoria_id,
    equipo_id
  )
);

CREATE TABLE torneo_votos (
  id INT AUTO_INCREMENT PRIMARY KEY,

  torneo_id INT NOT NULL,
  usuario_id INT NOT NULL, -- quien emite el voto
  categoria_id INT NOT NULL,

  jugador_id INT NULL, -- usado cuando la categorÃ­a es de tipo 'jugador'
  equipo_id INT NULL,  -- usado cuando la categorÃ­a es de tipo 'equipo'

  fecha_voto TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_voto_torneo
    FOREIGN KEY (torneo_id)
    REFERENCES torneos(id),

  CONSTRAINT fk_voto_usuario
    FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id),

  CONSTRAINT fk_voto_categoria
    FOREIGN KEY (categoria_id)
    REFERENCES torneo_categorias_voto(id),

  CONSTRAINT fk_voto_jugador
    FOREIGN KEY (jugador_id)
    REFERENCES usuarios(id),

  CONSTRAINT fk_voto_equipo
    FOREIGN KEY (equipo_id)
    REFERENCES equipos(id),

  CHECK (
    (jugador_id IS NOT NULL AND equipo_id IS NULL)
    OR (jugador_id IS NULL AND equipo_id IS NOT NULL)
  ),

  -- Un usuario solo puede tener un voto vigente por categorÃ­a (permite upsert/cambio de voto)
  UNIQUE KEY uq_voto_usuario_categoria (
    torneo_id,
    usuario_id,
    categoria_id
  ),

  INDEX idx_voto_categoria (
    torneo_id,
    categoria_id
  )
);

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Nombre en usuarios (para topbar y ver capitÃ¡n en admin)
-- ------------------------------------------------------------
SET @colNombre = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'nombre');
SET @sqlNombre = IF(@colNombre = 0,
  'ALTER TABLE usuarios ADD COLUMN nombre VARCHAR(120) NULL AFTER afi_usuario_id',
  'SELECT 1');
PREPARE stmtNombre FROM @sqlNombre; EXECUTE stmtNombre; DEALLOCATE PREPARE stmtNombre;

-- ------------------------------------------------------------
-- 2. Motivo de rechazo en postulaciÃ³n a torneo
-- ------------------------------------------------------------
SET @colMotivo = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'torneo_equipos' AND COLUMN_NAME = 'motivo_rechazo');
SET @sqlMotivo = IF(@colMotivo = 0,
  'ALTER TABLE torneo_equipos ADD COLUMN motivo_rechazo TEXT NULL AFTER aprobado_por',
  'SELECT 1');
PREPARE stmtMotivo FROM @sqlMotivo; EXECUTE stmtMotivo; DEALLOCATE PREPARE stmtMotivo;

-- ------------------------------------------------------------
-- 3. Posts (comunidad, solo admin publica)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  contenido TEXT NOT NULL,
  imagen_url VARCHAR(255) DEFAULT NULL,
  likes INT DEFAULT 0,
  fijado TINYINT(1) DEFAULT 0,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_posts_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  INDEX idx_posts_fecha (fecha),
  INDEX idx_posts_fijado (fijado)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. Reacciones a posts (una por usuario por post)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS post_reacciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  usuario_id INT NOT NULL,
  tipo ENUM('like','me_encanta','me_asombra') NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reaccion_post
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_reaccion_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  UNIQUE KEY uq_reaccion_post_usuario (post_id, usuario_id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Datos de prueba (seed): ver sql/inserts.sql
-- ============================================================
