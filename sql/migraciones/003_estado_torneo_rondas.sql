-- ============================================
-- 003 - Estado de las rondas
-- Permite finalizar, cancelar o reabrir rondas
-- ============================================

ALTER TABLE torneo_rondas
    ADD COLUMN estado ENUM('programado', 'en_curso', 'finalizada', 'cancelada')
    NOT NULL DEFAULT 'programado'
    AFTER orden;
