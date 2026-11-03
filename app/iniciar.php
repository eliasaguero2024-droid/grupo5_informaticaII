<?php
// Inicia una partida. Toda la logica (crear la partida y sortear preguntas de
// forma atomica) la hace el procedimiento sp_iniciar_partida en la base.
declare(strict_types=1);
require __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ir('index.php');
}

$alias       = trim((string) ($_POST['alias'] ?? ''));
$idModo      = (int) ($_POST['id_modo'] ?? 0);
$idCategoria = (int) ($_POST['id_categoria'] ?? 0);
$_SESSION['alias'] = $alias;

try {
    // 1) Jugador: si el alias ya existe se reutiliza; si no, se registra.
    //    (El CHECK de la tabla jugador valida el largo del alias.)
    $jugador = fila('SELECT id FROM jugador WHERE alias = ?', [$alias]);
    if ($jugador === null) {
        db()->prepare('INSERT INTO jugador (alias) VALUES (?)')->execute([$alias]);
        $idJugador = (int) db()->lastInsertId();
    } else {
        $idJugador = (int) $jugador['id'];
    }

    // 2) Partida: transaccion dentro de la base.
    $st = db()->prepare('CALL sp_iniciar_partida(?, ?, ?, @id_partida)');
    $st->execute([$idJugador, $idModo, $idCategoria]);
    $st->closeCursor();
    $idPartida = (int) fila('SELECT @id_partida AS id')['id'];

    $_SESSION['mis_partidas'][] = $idPartida;
    ir('jugar.php?id=' . $idPartida);
} catch (PDOException $ex) {
    avisar('error', mensaje_base($ex));
    ir('index.php');
}
