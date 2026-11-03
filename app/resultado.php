<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

$idPartida = (int) ($_GET['id'] ?? 0);

try {
    $r = fila('SELECT * FROM v_resultado_partida WHERE id_partida = ?', [$idPartida]);
    if ($r === null) {
        avisar('error', 'Esa partida no existe.');
        ir('index.php');
    }
    $puesto  = fila('SELECT puesto FROM v_ranking WHERE id_partida = ?', [$idPartida]);
    $detalle = filas('SELECT * FROM v_pregunta_partida WHERE id_partida = ? ORDER BY orden', [$idPartida]);
} catch (PDOException $ex) {
    encabezado('Error');
    echo '<div class="aviso aviso-error">' . e(mensaje_base($ex)) . '</div>';
    pie();
    exit;
}

$total     = (int) $r['total_preguntas'];
$correctas = (int) $r['correctas'];
$pct       = $total > 0 ? $correctas / $total : 0;
if ($pct >= 0.8)      { $medalla = ['oro', 'Medalla de oro']; }
elseif ($pct >= 0.6)  { $medalla = ['plata', 'Medalla de plata']; }
elseif ($pct >= 0.4)  { $medalla = ['bronce', 'Medalla de bronce']; }
else                  { $medalla = ['ninguna', 'Seguí practicando']; }

encabezado('Resultado #' . $idPartida);
?>
<section class="tarjeta final">
    <?php if ($r['estado'] === 'FINALIZADA'): ?>
        <div class="medalla medalla-<?= $medalla[0] ?>" aria-hidden="true"><?= $correctas ?>/<?= $total ?></div>
        <h1><?= e($medalla[1]) ?></h1>
        <p class="final-pts"><?= (int) $r['puntaje'] ?> <small>puntos</small></p>
        <div class="final-datos">
            <div><small>Jugador</small><strong><?= e($r['alias']) ?></strong></div>
            <div><small>Modo</small><strong><?= e($r['modo']) ?></strong></div>
            <div><small>Categoría</small><strong><?= e($r['categoria']) ?></strong></div>
            <div><small>Tiempo</small><strong><?= formato_tiempo($r['duracion_seg'] === null ? null : (int) $r['duracion_seg']) ?></strong></div>
            <div><small>Puesto</small><strong><?= $puesto ? '#' . (int) $puesto['puesto'] : '—' ?></strong></div>
        </div>
    <?php else: ?>
        <h1>Partida en curso</h1>
        <p><?= e($r['alias']) ?> respondió <?= (int) $r['respondidas'] ?> de <?= $total ?> preguntas (<?= (int) $r['puntaje'] ?> puntos hasta ahora).</p>
        <?php if (es_mi_partida($idPartida)): ?>
            <a class="boton" href="jugar.php?id=<?= $idPartida ?>">Seguir jugando</a>
        <?php endif; ?>
    <?php endif; ?>
    <div class="acciones">
        <a class="boton" href="index.php">Jugar otra</a>
        <a class="boton boton-sec" href="ranking.php">Ver ranking</a>
    </div>
</section>

<section class="tarjeta">
    <h2>Repaso de la partida</h2>
    <ol class="repaso">
        <?php foreach ($detalle as $d): ?>
            <?php
            $estado = $d['id_opcion_elegida'] === null ? 'pendiente' : ((int) $d['es_correcta'] === 1 ? 'bien' : 'mal');
            ?>
            <li class="repaso-<?= $estado ?>">
                <span class="repaso-icono"><?= $estado === 'bien' ? '✓' : ($estado === 'mal' ? '✗' : '…') ?></span>
                <div>
                    <strong><?= e($d['enunciado']) ?></strong>
                    <?php if ($estado === 'pendiente'): ?>
                        <small>Sin responder</small>
                    <?php else: ?>
                        <small>
                            Respuesta: <?= e($d['opcion_elegida']) ?>
                            <?php if ($estado === 'mal'): ?> · Correcta: <?= e($d['opcion_correcta']) ?><?php endif; ?>
                            · <?= $estado === 'bien' ? '+' . (int) $d['puntos'] : '0' ?> pts
                        </small>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
</section>
<?php pie();
