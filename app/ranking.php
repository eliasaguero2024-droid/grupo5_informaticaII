<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

$filtroModo = trim((string) ($_GET['modo'] ?? ''));

try {
    $modos = filas('SELECT nombre FROM modo_juego ORDER BY cantidad_preguntas');
    // Toda la informacion sale de la vista v_ranking (el puntaje se calcula en la base).
    if ($filtroModo !== '') {
        $ranking = filas(
            'SELECT RANK() OVER (ORDER BY puntaje DESC, duracion_seg ASC) AS puesto,
                    id_partida, alias, modo, categoria, correctas, total_preguntas, puntaje, duracion_seg, fecha_fin
               FROM v_ranking
              WHERE modo = ?
              ORDER BY puesto
              LIMIT 25',
            [$filtroModo]
        );
    } else {
        $ranking = filas('SELECT * FROM v_ranking ORDER BY puesto LIMIT 25');
    }
} catch (PDOException $ex) {
    encabezado('Error');
    echo '<div class="aviso aviso-error">' . e(mensaje_base($ex)) . '</div>';
    pie();
    exit;
}

encabezado('Ranking', 'ranking');
?>
<section class="hero hero-chico">
    <h1>Ranking</h1>
    <p>A igual puntaje, gana quien terminó más rápido.</p>
</section>

<nav class="filtros">
    <a href="ranking.php" class="<?= $filtroModo === '' ? 'activa' : '' ?>">Todos</a>
    <?php foreach ($modos as $m): ?>
        <a href="ranking.php?modo=<?= urlencode($m['nombre']) ?>"
           class="<?= $filtroModo === $m['nombre'] ? 'activa' : '' ?>"><?= e($m['nombre']) ?></a>
    <?php endforeach; ?>
</nav>

<section class="tarjeta tabla-envoltura">
    <?php if (!$ranking): ?>
        <p class="tenue">No hay partidas terminadas en este modo.</p>
    <?php else: ?>
        <table class="tabla">
            <thead>
                <tr><th>#</th><th>Jugador</th><th>Modo</th><th>Categoría</th><th>Aciertos</th><th>Tiempo</th><th>Puntos</th></tr>
            </thead>
            <tbody>
                <?php foreach ($ranking as $f): ?>
                    <tr>
                        <td><span class="puesto p<?= (int) $f['puesto'] ?>"><?= (int) $f['puesto'] ?></span></td>
                        <td><a href="resultado.php?id=<?= (int) $f['id_partida'] ?>"><?= e($f['alias']) ?></a></td>
                        <td><?= e($f['modo']) ?></td>
                        <td><?= e($f['categoria']) ?></td>
                        <td><?= (int) $f['correctas'] ?>/<?= (int) $f['total_preguntas'] ?></td>
                        <td><?= formato_tiempo((int) $f['duracion_seg']) ?></td>
                        <td class="pts"><?= (int) $f['puntaje'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php pie();
