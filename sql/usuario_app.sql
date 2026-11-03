-- =============================================================================
-- OPCIONAL: usuario de la aplicacion con permisos minimos
-- Se ejecuta UNA vez en el servidor, como administrador:
--     sudo mariadb < sql/usuario_app.sql
-- (el usuario grupo5 no puede crear usuarios, por eso no va en phpMyAdmin)
--
-- La app solo necesita: leer (SELECT), registrar jugadores (INSERT en jugador)
-- y ejecutar los procedimientos (EXECUTE). No puede borrar tablas ni cambiar
-- reglas. Los procedimientos corren con los permisos de quien los creo
-- (SQL SECURITY DEFINER), asi que igual pueden crear partidas y respuestas.
--
-- Antes de ejecutar: cambiar CAMBIAR_ESTA_CLAVE por una clave propia y ponerla
-- tambien en app/config.local.php. No subir esa clave a GitHub.
-- =============================================================================

CREATE USER IF NOT EXISTS 'juego5'@'localhost' IDENTIFIED BY 'CAMBIAR_ESTA_CLAVE';

GRANT SELECT  ON grupo5.*       TO 'juego5'@'localhost';
GRANT INSERT  ON grupo5.jugador TO 'juego5'@'localhost';
GRANT EXECUTE ON grupo5.*       TO 'juego5'@'localhost';

SHOW GRANTS FOR 'juego5'@'localhost';
