-- =============================================================================
-- PRUEBAS DE LAS REGLAS DEL JUEGO (para la defensa)
-- Ejecutar DESPUES de desafio_sala5.sql.
--
-- IMPORTANTE: casi todas estas sentencias TIENEN QUE DAR ERROR. Ese error es
-- la prueba de que la regla vive en la base. Ejecutalas de a UNA en la pestaña
-- SQL de phpMyAdmin (si se importa el archivo entero, se corta en el primer error).
-- =============================================================================

USE grupo5;

-- -----------------------------------------------------------------------------
-- R1. Una pregunta no se repite en una misma partida
-- Donde: UNIQUE uq_pp_partida_pregunta (id_partida, id_pregunta)
-- Se arma una partida a mano dentro de una transaccion y se intenta cargar
-- dos veces la pregunta 1. Ejecutar estas 5 lineas JUNTAS.
-- Esperado: ERROR 1062 Duplicate entry '...-1' for key 'uq_pp_partida_pregunta'
-- -----------------------------------------------------------------------------
START TRANSACTION;
INSERT INTO partida (id_jugador, id_modo_juego, id_categoria) VALUES (1, 1, 1);
INSERT INTO partida_pregunta (id_partida, id_pregunta, orden) VALUES (LAST_INSERT_ID(), 1, 1);
INSERT INTO partida_pregunta (id_partida, id_pregunta, orden)
SELECT id_partida, 1, 2 FROM partida_pregunta WHERE id = LAST_INSERT_ID();
ROLLBACK;

-- -----------------------------------------------------------------------------
-- R2. Una pregunta de la partida se responde una sola vez
-- Donde: trigger trg_pp_bu
-- Esperado: ERROR 1644 'R2: esta pregunta ya fue respondida en la partida'
-- (la partida 19 tiene respondidas las preguntas de orden 1 y 2)
-- -----------------------------------------------------------------------------
UPDATE partida_pregunta
   SET id_opcion_elegida = id_opcion_elegida, fecha_respuesta = NOW()
 WHERE id_partida = 19 AND orden = 1;

-- -----------------------------------------------------------------------------
-- R3. La opcion elegida tiene que ser una opcion de ESA pregunta
-- Donde: FK compuesta fk_pp_opcion_de_pregunta (id_opcion_elegida, id_pregunta)
--        -> opcion (id, id_pregunta)
-- Esperado: ERROR 1452 Cannot add or update a child row ... fk_pp_opcion_de_pregunta
-- (se intenta responder la pregunta de orden 3 con la opcion 1, que es de la pregunta 1)
-- -----------------------------------------------------------------------------
UPDATE partida_pregunta
   SET id_opcion_elegida = 1, fecha_respuesta = NOW()
 WHERE id_partida = 19 AND orden = 3 AND id_pregunta <> 1;

-- -----------------------------------------------------------------------------
-- R4. Una pregunta ACTIVA tiene exactamente una opcion correcta
-- Donde: triggers trg_pregunta_bi, trg_pregunta_bu, trg_opcion_bi/bu/bd
-- -----------------------------------------------------------------------------
-- 4a. Esperado: 'R4: una pregunta ACTIVA debe tener exactamente una opcion correcta'
--     (la pregunta 56 no tiene ninguna correcta)
UPDATE pregunta SET estado = 'ACTIVA' WHERE id = 56;

-- 4b. Esperado: el mismo error (la pregunta 57 tiene DOS correctas)
UPDATE pregunta SET estado = 'ACTIVA' WHERE id = 57;

-- 4c. Esperado: 'R4: una pregunta se crea en BORRADOR ...'
INSERT INTO pregunta (enunciado, estado, id_categoria, id_dificultad)
VALUES ('¿Pregunta creada directamente activa?', 'ACTIVA', 1, 1);

-- 4d. Esperado: 'R4: no se modifican opciones de una pregunta ACTIVA ...'
--     (marcar como correcta una opcion incorrecta de la pregunta 1)
UPDATE opcion SET es_correcta = 1
 WHERE id_pregunta = 1 AND es_correcta = 0
 LIMIT 1;

-- 4e. Caso que SI funciona: se arregla la pregunta 56 y despues se activa.
UPDATE opcion SET es_correcta = 1 WHERE id_pregunta = 56 AND texto = 'Pacífico';
INSERT INTO opcion (id_pregunta, texto, es_correcta) VALUES (56, 'Índico', 0);
UPDATE pregunta SET estado = 'ACTIVA' WHERE id = 56;   -- ahora no da error

-- -----------------------------------------------------------------------------
-- R5. Una partida tiene exactamente las preguntas de su modo, y solo ACTIVAS
-- Donde: trigger trg_pp_bi
-- -----------------------------------------------------------------------------
-- 5a. Esperado: 'R5: no se agregan preguntas a una partida FINALIZADA'
INSERT INTO partida_pregunta (id_partida, id_pregunta, orden) VALUES (1, 7, 16);

-- 5b. Esperado: 'R5: solo las preguntas ACTIVAS pueden salir en una partida'
--     (pregunta 57, sigue en BORRADOR)
INSERT INTO partida_pregunta (id_partida, id_pregunta, orden) VALUES (19, 57, 5);

-- -----------------------------------------------------------------------------
-- R6. Una partida solo se finaliza completa, y finalizada ya no cambia
-- Donde: trigger trg_partida_bu + CHECK chk_partida_fin
-- -----------------------------------------------------------------------------
-- 6a. Esperado: 'R6: no se puede finalizar, faltan preguntas por responder'
UPDATE partida SET estado = 'FINALIZADA', fecha_fin = NOW() WHERE id = 19;

-- 6b. Esperado: 'R6: una partida FINALIZADA no se modifica'
UPDATE partida SET fecha_inicio = NOW() WHERE id = 1;

-- 6c. Esperado: ERROR 4025 CONSTRAINT `chk_partida_fin` failed
--     (una partida EN_CURSO no puede tener fecha_fin)
INSERT INTO partida (id_jugador, id_modo_juego, id_categoria, fecha_fin)
VALUES (1, 1, 1, NOW());

-- -----------------------------------------------------------------------------
-- R7. El puntaje sale solo de las respuestas registradas
-- Donde: vista v_resultado_partida (no existe una columna "puntaje" para editar)
-- Esperado: ERROR 1054 Unknown column 'puntaje'
-- -----------------------------------------------------------------------------
UPDATE partida SET puntaje = 9999 WHERE id = 19;

-- -----------------------------------------------------------------------------
-- R8. Una partida se crea completa o no se crea (transaccion)
-- Donde: procedimiento sp_iniciar_partida (START TRANSACTION ... ROLLBACK)
-- Esperado: 'No hay suficientes preguntas activas en esa categoria para este modo'
-- (Clasico = 10 preguntas en Futbol, que tiene 5) y la cantidad de partidas
-- NO cambia.
-- -----------------------------------------------------------------------------
SELECT COUNT(*) AS partidas_antes FROM partida;
CALL sp_iniciar_partida(1, 2, 13, @id);
SELECT COUNT(*) AS partidas_despues, @id AS id_partida FROM partida;

-- Otras restricciones del modelo (CHECK / UNIQUE)
-- Esperado: CONSTRAINT `chk_jugador_alias` failed (alias de menos de 3 letras)
INSERT INTO jugador (alias) VALUES ('ab');
-- Esperado: Duplicate entry 'NicoMaure' for key 'alias'
INSERT INTO jugador (alias) VALUES ('NicoMaure');
-- Esperado: CONSTRAINT `chk_modo_cantidad` failed
INSERT INTO modo_juego (nombre, descripcion, cantidad_preguntas) VALUES ('Infinito', 'x', 500);
