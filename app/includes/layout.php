<?php
declare(strict_types=1);

function encabezado(string $titulo, string $activa = ''): void
{
    $aviso = tomar_aviso();
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Desafío Sala 5</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='14' fill='%238b5cf6'/><text x='32' y='44' font-size='34' text-anchor='middle' fill='white' font-family='Arial' font-weight='bold'>?</text></svg>">
</head>
<body>
<div class="fondo" aria-hidden="true"></div>
<header class="barra">
    <a class="logo" href="index.php">
        <span class="logo-icono">?</span>
        <span>DESAFÍO <strong>SALA 5</strong></span>
    </a>
    <nav>
        <a href="index.php" class="<?= $activa === 'inicio' ? 'activa' : '' ?>">Jugar</a>
        <a href="ranking.php" class="<?= $activa === 'ranking' ? 'activa' : '' ?>">Ranking</a>
    </nav>
</header>
<main class="contenedor">
<?php if ($aviso): ?>
    <div class="aviso aviso-<?= e($aviso['tipo']) ?>" role="alert"><?= e($aviso['msg']) ?></div>
<?php endif; ?>
<?php
}

function pie(): void
{
    ?>
</main>
<footer class="pie">
    <p>Grupo Sala 5 · Informática II · UNNE</p>
    <p class="integrantes">Elías Agüero · Edgardo Molina · Nicolás Maure · Gabriel Martínez Canepa</p>
</footer>
<script src="assets/app.js"></script>
</body>
</html>
<?php
}
