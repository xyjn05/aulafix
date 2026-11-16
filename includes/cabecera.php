<?php

declare(strict_types=1);

/**
 * Cabecera que usan todas las páginas: abre el HTML, enlaza el CSS
 * y pone el menú y el mensaje pendiente (si hay).
 * La página que la incluye tiene que definir antes la variable $titulo.
 */

$usuario_cab = $_SESSION['usuario'] ?? null;
$mensaje_cab = tomar_mensaje();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · AulaFix</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>
    <header class="barra">
        <a class="marca" href="incidencias.php">AulaFix</a>
        <nav>
            <?php if ($usuario_cab !== null) { ?>
                <a href="incidencias.php">Incidencias</a>
                <a href="nueva.php">Nueva incidencia</a>
                <span><?= e($usuario_cab['nombre']) ?> (<?= e($usuario_cab['rol']) ?>)</span>
                <form method="post" action="logout.php" class="en-linea">
                    <?= csrf_campo() ?>
                    <button type="submit">Cerrar sesión</button>
                </form>
            <?php } else { ?>
                <a href="login.php">Entrar</a>
                <a href="registro.php">Registrarse</a>
            <?php } ?>
        </nav>
    </header>
    <main>
        <?php if ($mensaje_cab !== null) { ?>
            <p class="mensaje mensaje-<?= e($mensaje_cab['tipo']) ?>"
                data-marca="<?= e($mensaje_cab['marca']) ?>"><?= e($mensaje_cab['texto']) ?></p>
        <?php } ?>
        <h1><?= e($titulo) ?></h1>