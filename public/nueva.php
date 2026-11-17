<?php
declare(strict_types=1);

/**
 * Formulario para avisar de una incidencia nueva.
 * Valido todo en el servidor. Si hay errores, los enseño junto a cada campo
 * y dejo escrito lo que puso el usuario. Si todo está bien, guardo en la
 * base de datos y redirijo al listado.
 */

require_once __DIR__ . '/../includes/funciones.php';
iniciar_sesion();
$usuario = requerir_login(); // solo entran usuarios con sesión

$pdo = db();

// saco los equipos de la base de datos para el desplegable
$consulta = $pdo->prepare('SELECT id, codigo, descripcion FROM equipos ORDER BY codigo');
$consulta->execute();
$equipos = $consulta->fetchAll();

// hago una lista solo con los ids de los equipos, para comprobar después
// que el equipo que llega del formulario existe de verdad
$ids_equipos = [];
foreach ($equipos as $equipo) {
    $ids_equipos[] = (string) $equipo['id'];
}

// valores que se ven en el formulario (la prioridad empieza en media)
$equipo_id = '';
$categoria = '';
$prioridad = 'media';
$descripcion = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $equipo_id = campo_post('equipo_id');
    $categoria = campo_post('categoria');
    $prioridad = campo_post('prioridad');
    $descripcion = campo_post('descripcion');

    // el equipo tiene que estar en la lista de ids que saqué de la base de datos
    if (!en_lista($equipo_id, $ids_equipos)) {
        $errores['equipo_id'] = 'Elige un equipo de la lista.';
    }

    // categoría y prioridad: solo valen los valores permitidos (los de config.php).
    // Compruebo en el servidor porque alguien podría cambiar el formulario a mano.
    if (!en_lista($categoria, CATEGORIAS)) {
        $errores['categoria'] = 'Elige una categoría válida.';
    }

    if (!en_lista($prioridad, PRIORIDADES)) {
        $errores['prioridad'] = 'Elige una prioridad válida.';
    }

    // descripción: de 10 a 500 caracteres
    if (!longitud_valida($descripcion, 10, 500)) {
        $errores['descripcion'] = 'La descripción debe tener entre 10 y 500 caracteres.';
    }

    // si no hay errores, guardo la incidencia. Siempre empieza como "abierta".
    if (count($errores) === 0) {
        $ahora = ahora_sql();
        $consulta = $pdo->prepare(
            'INSERT INTO incidencias (equipo_id, usuario_id, categoria, prioridad, descripcion, estado, creada_en, actualizada_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            (int) $equipo_id,
            $usuario['id'],
            $categoria,
            $prioridad,
            $descripcion,
            'abierta',
            $ahora,
            $ahora,
        ]);

        // la marca 'incidencia-creada' la leerá el JavaScript para borrar el borrador
        poner_mensaje('Incidencia registrada. ¡Gracias por avisar!', 'ok', 'incidencia-creada');
        // redirijo para que al pulsar F5 no se cree otra vez (Post-Redirect-Get)
        redirigir('incidencias.php');
    }
}

$titulo = 'Nueva incidencia';
require __DIR__ . '/../includes/cabecera.php';
?>
<form method="post" action="nueva.php" class="formulario">
    <?= csrf_campo() ?>

    <div class="campo">
        <label for="equipo_id">Equipo</label>
        <select id="equipo_id" name="equipo_id">
            <option value="">— Elige un equipo —</option>
            <?php foreach ($equipos as $equipo) { ?>
                <option value="<?= e($equipo['id']) ?>" <?= $equipo_id === (string) $equipo['id'] ? 'selected' : '' ?>>
                    <?= e($equipo['codigo']) ?> · <?= e($equipo['descripcion']) ?>
                </option>
            <?php } ?>
        </select>
        <?php if (isset($errores['equipo_id'])) { ?>
            <p class="error-campo"><?= e($errores['equipo_id']) ?></p>
        <?php } ?>
    </div>

    <div class="campo">
        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria">
            <option value="">— Elige una categoría —</option>
            <?php foreach (CATEGORIAS as $cat) { ?>
                <option value="<?= e($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>>
                    <?= e(etiqueta($cat)) ?>
                </option>
            <?php } ?>
        </select>
        <?php if (isset($errores['categoria'])) { ?>
            <p class="error-campo"><?= e($errores['categoria']) ?></p>
        <?php } ?>
    </div>

    <fieldset class="campo">
        <legend>Prioridad</legend>
        <?php foreach (PRIORIDADES as $pri) { ?>
            <label class="en-linea">
                <input type="radio" name="prioridad" value="<?= e($pri) ?>" <?= $prioridad === $pri ? 'checked' : '' ?>>
                <?= e(etiqueta($pri)) ?>
            </label>
        <?php } ?>
        <?php if (isset($errores['prioridad'])) { ?>
            <p class="error-campo"><?= e($errores['prioridad']) ?></p>
        <?php } ?>
    </fieldset>

    <div class="campo">
        <label for="descripcion">Descripción (de 10 a 500 caracteres)</label>
        <textarea id="descripcion" name="descripcion" rows="5" maxlength="500"><?= e($descripcion) ?></textarea>
        <p id="contador-caracteres">0 / 500</p>
        <?php if (isset($errores['descripcion'])) { ?>
            <p class="error-campo"><?= e($errores['descripcion']) ?></p>
        <?php } ?>
    </div>

    <button type="submit">Enviar incidencia</button>
</form>

<script type="module" src="js/nueva.js"></script>
<?php require __DIR__ . '/../includes/pie.php'; ?>