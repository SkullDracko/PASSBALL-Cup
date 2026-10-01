-- 003_motivos_y_rechazo_equipos.sql
-- Ejecutar una vez sobre una BD existente para soportar motivos de equipo
-- y mostrar el estado/motivo de su inscripción más reciente al torneo.

ALTER TABLE equipos
  MODIFY COLUMN estado ENUM('activo','inactivo','pendiente','rechazado') NOT NULL DEFAULT 'activo';

SET @colMotivoSolicitud = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'equipos'
    AND COLUMN_NAME = 'motivo_solicitud'
);
SET @sqlMotivoSolicitud = IF(@colMotivoSolicitud = 0,
  'ALTER TABLE equipos ADD COLUMN motivo_solicitud TEXT NULL AFTER capitan_id',
  'SELECT 1');
PREPARE stmtMotivoSolicitud FROM @sqlMotivoSolicitud;
EXECUTE stmtMotivoSolicitud;
DEALLOCATE PREPARE stmtMotivoSolicitud;

SET @colMotivoEquipo = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'equipos'
    AND COLUMN_NAME = 'motivo_rechazo'
);
SET @sqlMotivoEquipo = IF(@colMotivoEquipo = 0,
  'ALTER TABLE equipos ADD COLUMN motivo_rechazo TEXT NULL AFTER motivo_solicitud',
  'SELECT 1');
PREPARE stmtMotivoEquipo FROM @sqlMotivoEquipo;
EXECUTE stmtMotivoEquipo;
DEALLOCATE PREPARE stmtMotivoEquipo;

SET @colMotivoPostulacion = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'torneo_equipos'
    AND COLUMN_NAME = 'motivo_rechazo'
);
SET @sqlMotivoPostulacion = IF(@colMotivoPostulacion = 0,
  'ALTER TABLE torneo_equipos ADD COLUMN motivo_rechazo TEXT NULL AFTER aprobado_por',
  'SELECT 1');
PREPARE stmtMotivoPostulacion FROM @sqlMotivoPostulacion;
EXECUTE stmtMotivoPostulacion;
DEALLOCATE PREPARE stmtMotivoPostulacion;
