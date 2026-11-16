<?php
declare(strict_types=1);

/**
 * Inicio de sesión.
 * Compruebo la contraseña con password_verify y, si es correcta,
 * guardo los datos del usuario en la sesión.
 * La casilla "Recordar mi correo" guarda solo el correo en una cookie 30 días.
 */

require_once __DIR__ . '/../includes/funciones.php';
iniciar_sesion();

if (isset($_SESSION['usuario'])) {
    redirigir('incidencias.php');
}

$email = '';
$recordar = false;
$error = '';

// si hay cookie con el correo, lo pongo ya escrito en el formulario
if (isset($_COOKIE['aulafix_correo']) && is_string($_COOKIE['aulafix_correo'])) {
    $email = $_COOKIE['aulafix_correo'];
    $recordar = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $email = strtolower(campo_post('email'));
    $clave = campo_post('clave', false);
    $recordar = isset($_POST['recordar']);

    $pdo = db();
    $consulta = $pdo->prepare('SELECT id, nombre, clave_hash, rol FROM usuarios WHERE email = ?');
    $consulta->execute([$email]);
    $usuario = $consulta->fetch();

    // el mensaje es el mismo si el correo no existe o si la contraseña está mal,
    // así nadie puede averiguar qué correos están registrados
    if ($usuario === false || !password_verify($clave, $usuario['clave_hash'])) {
        $error = 'Correo o contraseña incorrectos.';
    } else {
        // cambio el id de sesión al entrar para evitar el robo de sesión
        // (session fixation)
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id'     => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'rol'    => $usuario['rol'],
        ];

        // la cookie solo guarda el correo, nunca la contraseña
        if ($recordar) {
            setcookie('aulafix_correo', $email, [
                'expires'  => time() + 60 * 60 * 24 * 30, // 30 días
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            // si no marca la casilla, borro la cookie
            setcookie('aulafix_correo', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        redirigir('incidencias.php');
    }
}

$titulo = 'Iniciar sesión';
require __DIR__ . '/../includes/cabecera.php';
?>
<form method="post" action="login.php" class="formulario">
    <?= csrf_campo() ?>

    <?php if ($error !== '') { ?>
        <p class="mensaje mensaje-error"><?= e($error) ?></p>
    <?php } ?>

    <div class="campo">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>">
    </div>

    <div class="campo">
        <label for="clave">Contraseña</label>
        <input type="password" id="clave" name="clave">
    </div>

    <div class="campo">
        <input type="checkbox" id="recordar" name="recordar" <?= $recordar ? 'checked' : '' ?>>
        <label for="recordar" class="en-linea">Recordar mi correo</label>
    </div>

    <button type="submit">Entrar</button>
    <p>¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
</form>
<?php require __DIR__ . '/../includes/pie.php'; ?>