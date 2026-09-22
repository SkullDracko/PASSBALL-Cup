-- ============================================================
-- PASSBALL Cup - Migración Admin (añadidos sobre bd_propuesta.sql)
-- IDEMPOTENTE: se puede ejecutar varias veces sin romper nada.
-- Ejecutar DESPUÉS de bd_propuesta.sql + inserts.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Nombre en usuarios (para topbar y ver capitán en admin)
-- ------------------------------------------------------------
SET @colNombre = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'nombre');
SET @sqlNombre = IF(@colNombre = 0,
  'ALTER TABLE usuarios ADD COLUMN nombre VARCHAR(120) NULL AFTER afi_usuario_id',
  'SELECT 1');
PREPARE stmtNombre FROM @sqlNombre; EXECUTE stmtNombre; DEALLOCATE PREPARE stmtNombre;

-- ------------------------------------------------------------
-- 2. Motivo de rechazo en postulación a torneo
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