
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matricula VARCHAR(20) NOT NULL UNIQUE,
  afi_usuario_id VARCHAR(50) UNIQUE,
  rol ENUM('usuario','administrador') NOT NULL DEFAULT 'usuario',
  avatar VARCHAR(255),
  jugador_activo BOOLEAN NOT NULL DEFAULT TRUE,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE administradores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  activo BOOLEAN NOT NULL DEFAULT TRUE,
  fecha_alta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_admin_usuario
    FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
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

CREATE TABLE equipo_miembros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equipo_id INT NOT NULL,
  jugador_id INT NOT NULL,
  fecha_union TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_salida TIMESTAMP NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',

  jugador_activo_unico INT GENERATED ALWAYS AS (
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
    jugador_activo_unico
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

  INDEX idx_partido_ronda (
    ronda_id
  ),

  INDEX idx_partido_local (
    equipo_local_id
  ),

  INDEX idx_partido_visitante (
    equipo_visitante_id
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