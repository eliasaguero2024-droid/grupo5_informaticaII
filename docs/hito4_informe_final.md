# Hito 4 — Informe final (borrador)

**Proyecto:** Trivia Sala 5 — "Una base de datos que se juega"
**Integrantes:** Elías Agüero, Edgardo Molina, Nicolás Maure, Gabriel Martínez Canepa
**Repositorio:** https://github.com/nicomaure/grupo5_informaticaII

> Borrador para completar y reescribir con palabras del grupo (extensión pedida: 2 a 3 páginas).

## 1. El juego

Trivia de preguntas y respuestas. El jugador elige alias, modo (Relámpago 5, Clásico 10, Maratón 15 preguntas) y categoría. La base sortea las preguntas de esa categoría **y de todas sus subcategorías**, registra cada respuesta, calcula el puntaje según la dificultad y arma un ranking. La interfaz es PHP + HTML y corre en el servidor Azure del grupo (nginx + PHP-FPM + MariaDB).

## 2. Modelo de datos (resumen)

- 8 tablas: `jugador`, `categoria`, `dificultad`, `modo_juego`, `pregunta`, `opcion`, `partida`, `partida_pregunta`.
- **N:M con atributos:** `partida_pregunta` (orden, opción elegida, fecha de respuesta).
- **Estructura avanzada — relación reflexiva:** `categoria.id_categoria_padre` (ej.: Informática → Bases de datos → SQL). Se recorre con `WITH RECURSIVE`.
- 3FN justificada en `docs/hito2_modelo_relacional.md` (puntos en `dificultad`, cantidad en `modo_juego`, puntaje no almacenado).

## 3. Dónde se impone cada regla

| Regla | Mecanismo | Por qué ahí |
|---|---|---|
| R1 Pregunta no repetida en la partida | `UNIQUE (id_partida, id_pregunta)` | Es una restricción declarativa simple: la más barata y segura. |
| R2 Se responde una vez y con la partida EN_CURSO | trigger `trg_pp_bu` | Compara valor viejo y nuevo y consulta otra tabla: un CHECK no alcanza. |
| R3 Opción de la misma pregunta | FK compuesta → `opcion (id, id_pregunta)` | Se resuelve sin código, solo con integridad referencial. |
| R4 Pregunta ACTIVA con exactamente 1 correcta | triggers sobre `pregunta` y `opcion` | Involucra varias filas de otra tabla. |
| R5 Cantidad de preguntas del modo, solo ACTIVAS | trigger `trg_pp_bi` | Necesita contar filas y leer `modo_juego`. |
| R6 Finalizar solo completa; finalizada no cambia | trigger `trg_partida_bu` + `CHECK chk_partida_fin` | El CHECK controla la coherencia estado/fecha en la fila; el trigger cuenta respuestas. |
| R7 Puntaje derivado | vista `v_resultado_partida` | Evita la dependencia transitiva y los datos inconsistentes. |
| R8 Partida completa o nada | transacción en `sp_iniciar_partida` | Varias inserciones que deben confirmarse juntas. |

La aplicación PHP **no repite estas validaciones**: manda la acción y, si la base la rechaza (`SIGNAL 45000`, FK, UNIQUE, CHECK), muestra el mensaje. Así la regla se cumple aunque alguien cargue datos desde phpMyAdmin.

## 4. Transacciones

- `sp_iniciar_partida`: crea la partida y sortea N preguntas. Si la rama elegida no tiene suficientes preguntas ACTIVAS, hace `ROLLBACK` y no queda una partida a medias.
- `sp_responder`: bloquea la partida con `SELECT … FOR UPDATE`, registra la respuesta y, si era la última, finaliza la partida. Evita que dos clics simultáneos respondan dos veces.
- Sección 7 del script: transacción manual con un caso que termina en `ROLLBACK` (Clásico de Fútbol, sin preguntas suficientes) y otro que termina en `COMMIT`.

## 5. Consultas

Las 10 consultas están en la sección 8 de `sql/desafio_sala5.sql`, cada una con su enunciado. Incluyen JOIN de 3 o más tablas, anti-join (preguntas nunca jugadas), subconsultas escalares (puntaje sobre el promedio y distancia al récord), GROUP BY + HAVING y una consulta recursiva.

## 6. Cambios respecto de los hitos anteriores

_Completar: qué observó el docente y qué se modificó._

## 7. Problemas encontrados

- Los triggers en MariaDB necesitan `log_bin` desactivado o el privilegio correspondiente. Si phpMyAdmin da un error al crear triggers, revisar esto con el administrador del servidor.
- _Agregar otros problemas que aparezcan en la puesta en marcha._

## 8. Mejoras futuras

- Login con contraseña (hash) en lugar de solo alias.
- Tiempo límite por pregunta y bonus por velocidad.
- Panel para cargar preguntas en BORRADOR y activarlas.
- Estadísticas por categoría para cada jugador.
- Especialización de `pregunta` (opción múltiple / verdadero-falso).
