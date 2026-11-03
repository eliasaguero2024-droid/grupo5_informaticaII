<?php
// Registra una respuesta. La base decide todo: que la pregunta no se haya
// respondido antes (R2), que la opcion sea de esa pregunta (R3) y si la
// partida termina (R6), dentro de la transaccion de sp_responder.
declare(strict_types=1);
require __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ir('index.php');
}

$idPartida  = (int) ($_POST['id_partida'] ?? 0);
$idPregunta = (int) ($_POST['id_pregunta'] ?? 0);
$idOpcion   = (int) ($_POST['id_opcion'] ?? 0);

if (!es_mi_partida($idPartida)) {
    avisar('error', 'Solo quien inició la partida puede responderla.');
    ir('resultado.php?id=' . $idPartida);
}

try {
    $st = db()->prepare('CALL sp_responder(?, ?, ?, @es_correcta, @finalizada)');
    $st->execute([$idPartida, $idPregunta, $idOpcion]);
    $st->closeCursor();
    ir('jugar.php?id=' . $idPartida . '&r=' . $idPregunta);
} catch (PDOException $ex) {
    avisar('error', mensaje_base($ex));
    ir('jugar.php?id=' . $idPartida);
}
