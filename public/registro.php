<?php
declare(strict_types=1);

/**
 * Registro de usuarios nuevos (siempre con rol alumno).
 * Valido los datos en el servidor y guardo la contraseña con hash Argon2id.
 */

require_once __DIR__ . '/../includes/funciones.php';
iniciar_sesion();

// si ya hay sesión, no hace falta registrarse
if (isset($_SESSION['usuario'])) {
    redirigir('incidencias.php');
}

$nombre = '';
$email = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $nombre = campo_post('nombre');
    $email = strtolower(campo_post('email'));
    // las contraseñas no se recortan, los espacios también cuentan
    $clave = campo_post('clave', false);
    $clave2 = campo_post('clave2', false);

    if (!longitud_valida($nombre, 2, 80)) {
        $errores['nombre'] = 'El nombre debe tener entre 2 y 80 caracteres.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Escribe un correo válido.';
    }

    // al menos 10 caracteres, con letras y números
    if (mb_strlen($clave) < 10 || !preg_match('/[a-zA-Z]/', $clave) || !preg_match('/[0-9]/', $clave)) {
        $errores['clave'] = 'La contraseña necesita 10 caracteres o más, con letras y números.';
    }

    if ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    $pdo = db();

    // compruebo que el correo no esté repetido
    if (!isset($errores['email'])) {
        $consulta = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $consulta->execute([$email]);
        if ($consulta->fetch() !== false) {
            $errores['email'] = 'Ya existe una cuenta con ese correo.';
        }
    }

    // si todo está bien, guardo el usuario
    if (count($errores) === 0) {
        $hash = password_hash($clave, PASSWORD_ARGON2ID);
        $consulta = $pdo->prepare(
            'INSERT INTO usuarios (nombre, email, clave_hash, rol, creado_en) VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->execute([$nombre, $email, $hash, 'alumno', ahora_sql()]);

        poner_mensaje('Cuenta creada. Ya puedes iniciar sesión.');
        redirigir('login.php'); // Post-Redirect-Get
    }
}

$titulo = 'Crear cuenta';
require __DIR__ . '/../includes/cabecera.php';
?>
<form method="post" action="registro.php" class="formulario">
    <?= csrf_campo() ?>

    <div class="campo">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($nombre) ?>">
        <?php if (isset($errores['nombre'])) { ?>
            <p class="error-campo"><?= e($errores['nombre']) ?></p>
        <?php } ?>
    </div>

    <div class="campo">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>">
        <?php if (isset($errores['email'])) { ?>
            <p class="error-campo"><?= e($errores['email']) ?></p>
        <?php } ?>
    </div>

    <div class="campo">
        <label for="clave">Contraseña</label>
        <input type="password" id="clave" name="clave">
        <?php if (isset($errores['clave'])) { ?>
            <p class="error-campo"><?= e($errores['clave']) ?></p>
        <?php } ?>
    </div>

    <div class="campo">
        <label for="clave2">Repite la contraseña</label>
        <input type="password" id="clave2" name="clave2">
        <?php if (isset($errores['clave2'])) { ?>
            <p class="error-campo"><?= e($errores['clave2']) ?></p>
        <?php } ?>
    </div>

    <button type="submit">Crear cuenta</button>
</form>
<?php require __DIR__ . '/../includes/pie.php'; ?>