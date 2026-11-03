<?php
// =============================================================================
// Conexion a MariaDB y funciones de ayuda.
// La aplicacion NO valida reglas del juego: las impone la base (triggers,
// restricciones y procedimientos). Aca solo se muestran sus mensajes.
// =============================================================================

declare(strict_types=1);

session_start();

function config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $archivo = __DIR__ . '/../config.local.php';
        if (!is_file($archivo)) {
            http_response_code(500);
            exit('Falta app/config.local.php. Copiá config.example.php como config.local.php y poné la clave de la base.');
        }
        $cfg = require $archivo;
    }
    return $cfg;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config();
        $dsn = "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/** Ejecuta una consulta preparada y devuelve todas las filas. */
function filas(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Ejecuta una consulta preparada y devuelve la primera fila (o null). */
function fila(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $f = $st->fetch();
    $st->closeCursor();
    return $f === false ? null : $f;
}

/** Escapa texto para HTML (evita XSS). */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Traduce el error de MariaDB a un mensaje para el jugador. */
function mensaje_base(PDOException $ex): string
{
    $codigo = (int) ($ex->errorInfo[1] ?? 0);
    $texto  = (string) ($ex->errorInfo[2] ?? $ex->getMessage());
    switch ($codigo) {
        case 1644: // SIGNAL de un trigger o procedimiento: el mensaje ya es claro
            return $texto;
        case 1452:
            return 'La base rechazó la respuesta: esa opción no pertenece a la pregunta (regla R3).';
        case 1062:
            return 'La base rechazó un dato repetido: ' . $texto;
        case 4025:
            return 'La base rechazó el dato por una restricción CHECK: ' . $texto;
        default:
            return 'Error de la base de datos: ' . $texto;
    }
}

function avisar(string $tipo, string $msg): void
{
    $_SESSION['aviso'] = ['tipo' => $tipo, 'msg' => $msg];
}

function tomar_aviso(): ?array
{
    $a = $_SESSION['aviso'] ?? null;
    unset($_SESSION['aviso']);
    return $a;
}

/** Las partidas que se iniciaron desde este navegador (solo esas se pueden jugar). */
function es_mi_partida(int $id): bool
{
    return in_array($id, $_SESSION['mis_partidas'] ?? [], true);
}

function ir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function formato_tiempo(?int $seg): string
{
    if ($seg === null) {
        return '—';
    }
    return sprintf('%d:%02d', intdiv($seg, 60), $seg % 60);
}
