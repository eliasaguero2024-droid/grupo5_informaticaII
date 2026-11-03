<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

try {
    $modos      = filas('SELECT id, nombre, descripcion, cantidad_preguntas FROM modo_juego ORDER BY cantidad_preguntas');
    $categorias = filas('SELECT id, nombre, ruta, nivel, preguntas_activas FROM v_categoria_arbol ORDER BY ruta');
    $top        = filas('SELECT puesto, alias, modo, categoria, puntaje, duracion_seg FROM v_ranking ORDER BY puesto LIMIT 5');
} catch (PDOException $ex) {
    encabezado('Error');
    echo '<div class="aviso aviso-error">' . e(mensaje_base($ex)) . '</div>';
    pie();
    exit;
}

$alias_guardado = $_SESSION['alias'] ?? '';

encabezado('Jugar', 'inicio');
?>
<section class="hero">
    <h1>¿Cuánto sabés?</h1>
    <p>Elegí una categoría y un modo, respondé las preguntas y entrá al ranking del grupo.</p>
</section>

<div class="grilla-inicio">
    <form class="tarjeta" method="post" action="iniciar.php">
        <h2>Nueva partida</h2>

        <label class="campo">
            <span>Tu alias</span>
            <input type="text" name="alias" maxlength="30" required
                   placeholder="Ej: Jugador_Sala5" value="<?= e($alias_guardado) ?>">
        </label>

        <fieldset class="campo">
            <legend>Modo de juego</legend>
            <div class="modos">
                <?php foreach ($modos as $i => $m): ?>
                    <label class="modo">
                        <input type="radio" name="id_modo" value="<?= (int) $m['id'] ?>" <?= $i === 1 ? 'checked' : '' ?>>
                        <span class="modo-caja">
                            <strong><?= e($m['nombre']) ?></strong>
                            <span class="modo-cant"><?= (int) $m['cantidad_preguntas'] ?> preguntas</span>
                            <small><?= e($m['descripcion']) ?></small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <label class="campo">
            <span>Categoría</span>
            <select name="id_categoria" required>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int) $c['id'] ?>">
                        <?= str_repeat('— ', (int) $c['nivel'] - 1) . e($c['nombre']) ?>
                        (<?= (int) $c['preguntas_activas'] ?> preguntas)
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="ayuda">Una categoría incluye las preguntas de todas sus subcategorías.</small>
        </label>

        <button class="boton boton-grande" type="submit">¡A jugar!</button>
    </form>

    <aside class="tarjeta">
        <h2>Top 5</h2>
        <?php if (!$top): ?>
            <p class="tenue">Todavía no hay partidas terminadas.</p>
        <?php else: ?>
            <ol class="podio">
                <?php foreach ($top as $t): ?>
                    <li>
                        <span class="puesto p<?= (int) $t['puesto'] ?>"><?= (int) $t['puesto'] ?></span>
                        <span class="podio-nombre">
                            <strong><?= e($t['alias']) ?></strong>
                            <small><?= e($t['modo']) ?> · <?= e($t['categoria']) ?></small>
                        </span>
                        <span class="podio-pts"><?= (int) $t['puntaje'] ?> pts</span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
        <a class="enlace" href="ranking.php">Ver ranking completo →</a>
    </aside>
</div>
<?php pie();
