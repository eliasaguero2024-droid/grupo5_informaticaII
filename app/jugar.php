<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

$idPartida   = (int) ($_GET['id'] ?? 0);
$recienResp  = isset($_GET['r']) ? (int) $_GET['r'] : null;   // id_pregunta recien respondida

try {
    $partida = fila('SELECT * FROM v_resultado_partida WHERE id_partida = ?', [$idPartida]);
    if ($partida === null) {
        avisar('error', 'Esa partida no existe.');
        ir('index.php');
    }

    $feedback = null;
    if ($recienResp !== null) {
        $feedback = fila(
            'SELECT * FROM v_pregunta_partida
              WHERE id_partida = ? AND id_pregunta = ? AND id_opcion_elegida IS NOT NULL',
            [$idPartida, $recienResp]
        );
    }

    if ($feedback === null) {
        if ($partida['estado'] === 'FINALIZADA') {
            ir('resultado.php?id=' . $idPartida);
        }
        if (!es_mi_partida($idPartida)) {
            avisar('info', 'Esa partida la inició otra persona: podés ver cómo va, pero no responderla.');
            ir('resultado.php?id=' . $idPartida);
        }
        // Proxima pregunta pendiente (la vista no muestra la correcta de las pendientes)
        $pregunta = fila(
            'SELECT * FROM v_pregunta_partida
              WHERE id_partida = ? AND id_opcion_elegida IS NULL
              ORDER BY orden LIMIT 1',
            [$idPartida]
        );
        $opciones = filas('SELECT id, texto FROM opcion WHERE id_pregunta = ? ORDER BY RAND()', [$pregunta['id_pregunta']]);
    }
} catch (PDOException $ex) {
    encabezado('Error');
    echo '<div class="aviso aviso-error">' . e(mensaje_base($ex)) . '</div>';
    pie();
    exit;
}

$total       = (int) $partida['total_preguntas'];
$respondidas = (int) $partida['respondidas'];
$porcentaje  = $total > 0 ? round(100 * $respondidas / $total) : 0;

encabezado('Partida #' . $idPartida);
?>
<section class="hud">
    <div class="hud-item"><small>Jugador</small><strong><?= e($partida['alias']) ?></strong></div>
    <div class="hud-item"><small>Modo</small><strong><?= e($partida['modo']) ?></strong></div>
    <div class="hud-item"><small>Categoría</small><strong><?= e($partida['categoria']) ?></strong></div>
    <div class="hud-item hud-pts"><small>Puntos</small><strong><?= (int) $partida['puntaje'] ?></strong></div>
</section>

<div class="progreso" role="progressbar" aria-valuenow="<?= $respondidas ?>" aria-valuemin="0" aria-valuemax="<?= $total ?>">
    <div class="progreso-barra" style="width: <?= $porcentaje ?>%"></div>
    <span class="progreso-texto"><?= $respondidas ?> / <?= $total ?></span>
</div>

<?php if ($feedback !== null): ?>
    <?php $bien = (int) $feedback['es_correcta'] === 1; ?>
    <section class="tarjeta pregunta resultado-respuesta <?= $bien ? 'es-bien' : 'es-mal' ?>">
        <div class="sello"><?= $bien ? '¡Correcto!' : 'Incorrecto' ?></div>
        <p class="meta">Pregunta <?= (int) $feedback['orden'] ?> · <?= e($feedback['dificultad']) ?></p>
        <h2><?= e($feedback['enunciado']) ?></h2>
        <p>Respondiste: <strong><?= e($feedback['opcion_elegida']) ?></strong></p>
        <?php if (!$bien): ?>
            <p>La correcta era: <strong class="ok"><?= e($feedback['opcion_correcta']) ?></strong></p>
        <?php else: ?>
            <p class="ok">+<?= (int) $feedback['puntos'] ?> puntos</p>
        <?php endif; ?>
        <?php if ($feedback['explicacion']): ?>
            <p class="explicacion"><?= e($feedback['explicacion']) ?></p>
        <?php endif; ?>
        <?php if ($partida['estado'] === 'FINALIZADA'): ?>
            <a class="boton boton-grande" href="resultado.php?id=<?= $idPartida ?>" data-atajo="Enter">Ver resultado final</a>
        <?php else: ?>
            <a class="boton boton-grande" href="jugar.php?id=<?= $idPartida ?>" data-atajo="Enter">Siguiente pregunta →</a>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="tarjeta pregunta">
        <p class="meta">
            Pregunta <?= (int) $pregunta['orden'] ?> de <?= $total ?>
            · <?= e($pregunta['categoria']) ?>
            · <span class="chip chip-<?= e(strtolower($pregunta['dificultad'])) ?>"><?= e($pregunta['dificultad']) ?> · <?= (int) $pregunta['puntos'] ?> pts</span>
        </p>
        <h2><?= e($pregunta['enunciado']) ?></h2>
        <form method="post" action="responder.php" class="opciones" data-una-vez>
            <input type="hidden" name="id_partida" value="<?= $idPartida ?>">
            <input type="hidden" name="id_pregunta" value="<?= (int) $pregunta['id_pregunta'] ?>">
            <?php foreach ($opciones as $i => $o): ?>
                <button type="submit" name="id_opcion" value="<?= (int) $o['id'] ?>" class="opcion" data-atajo="<?= $i + 1 ?>">
                    <span class="letra"><?= chr(65 + $i) ?></span>
                    <span><?= e($o['texto']) ?></span>
                </button>
            <?php endforeach; ?>
        </form>
        <p class="tenue atajos">Atajo: teclas 1 a <?= count($opciones) ?></p>
    </section>
<?php endif; ?>
<?php pie();
