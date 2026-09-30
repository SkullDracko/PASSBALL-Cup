-- ============================================
-- 005 - Motivo de rechazo en postulaciones
-- Permite al administrador registrar por que rechaza
-- la postulacion de un equipo a un torneo.
--
-- La usa admin/controllers/postulaciones.php al rechazar y
-- admin/partials/postulaciones.php para mostrarla.
-- ============================================

-- MariaDB 10.4+ soporta IF NOT EXISTS, asi la migracion
-- se puede volver a correr sin error.
ALTER TABLE torneo_equipos
    ADD COLUMN IF NOT EXISTS motivo_rechazo TEXT NULL AFTER aprobado_por;
