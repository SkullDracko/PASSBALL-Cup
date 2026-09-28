-- 002_alias_usuarios.sql
-- Agrega la columna alias al jugador para el perfil (apodo público).

ALTER TABLE `usuarios`
  ADD COLUMN `alias` VARCHAR(40) NULL DEFAULT NULL AFTER `nombre`;