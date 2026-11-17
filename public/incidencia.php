<?php
declare(strict_types=1);

/**
 * Detalle de una incidencia con su historial.
 * El profesor puede cambiar el estado (con un comentario).
 * El profesor puede borrar cualquiera; el alumno solo las suyas si siguen abiertas.
 * Los permisos los compruebo en el servidor.
 */

require_once __DIR__ . '/../includes/funciones.php';
iniciar_sesion();
$usuario = requerir_login();

$pdo = db();

// cojo el id de la dirección (incidencia.php?id=3) y compruebo que es un número
$id = 0;
if (isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id'])) {
    $id = (int) $_GET['id'];
}

// busco la incidencia en la base de datos
$consulta = $pdo->prepare(
    'SELECT i.id, i.usuario_id, i.categoria, i.prioridad, i.descripcion, i.estado,
            i.creada_en, e.codigo AS equipo, e.descripcion AS equipo_desc, u.nombre AS autor
     FROM incidencias i
     JOIN equipos e ON e.id = i.equipo_id
     JOIN usuarios u ON u.id = i.usuario_id
     WHERE i.id = ?'
);
$consulta->execute([$id]);
$incidencia = $consulta->fetch();

// si no existe, respondo 404
if ($incidencia === false) {
    http_response_code(404);
    exit('La incidencia no existe.');
}

// ¿puede este usuario borrar esta incidencia?
// profesor: todas. Alumno: solo las suyas y si siguen abiertas.
$puede_borrar = false;
if (es_profesor($usuario)) {
    $puede_borrar = true;
} elseif ((int) $incidencia['usuario_id'] === $usuario['id'] && $incidencia['estado'] === 'abierta') {
    $puede_borrar = true;
}

$estado_nuevo = $incidencia['estado'];
$comentario = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $accion = campo_post('accion');

    if ($accion === 'estado') {
        // solo el profesor. Si un alumno manda el formulario a mano, 403.
        if (!es_profesor($usuario)) {
            prohibido('Solo el profesorado puede cambiar el estado.');
        }

        $estado_nuevo = campo_post('estado');
        $comentario = campo_post('comentario');

        if (!en_lista($estado_nuevo, ESTADOS)) {
            $errores['estado'] = 'Elige un estado válido.';
        } elseif ($estado_nuevo === $incidencia['estado']) {
            $errores['estado'] = 'La incidencia ya tiene ese estado.';
        }

        if (!longitud_valida($comentario, 3, 300)) {
            $errores['comentario'] = 'El comentario debe tener entre 3 y 300 caracteres.';
        }

        if (count($errores) === 0) {
            $ahora = ahora_sql();

            // Uso una transacción: se hacen las dos cosas (cambiar el estado y
            // apuntar en el historial) o no se hace ninguna. Sin transacción, si
            // fallara a la mitad, la incidencia cambiaría y no quedaría rastro.
            $pdo->beginTransaction();
            try {
                $c = $pdo->prepare('UPDATE incidencias SET estado = ?, actualizada_en = ? WHERE id = ?');
                $c->execute([$estado_nuevo, $ahora, $id]);

                $c = $pdo->prepare(
                    'INSERT INTO historial (incidencia_id, usuario_id, estado_anterior, estado_nuevo, comentario, creado_en)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $c->execute([$id, $usuario['id'], $incidencia['estado'], $estado_nuevo, $comentario, $ahora]);

                $pdo->commit(); // todo bien, se guardan los dos cambios
            } catch (Exception $ex) {
                $pdo->rollBack(); // algo falló, se deshace todo
                throw $ex;
            }

            poner_mensaje('Estado actualizado.');
            redirigir('incidencia.php?id=' . $id); // Post-Redirect-Get
        }
    } elseif ($accion === 'borrar') {
        if (!$puede_borrar) {
            prohibido('No puedes borrar esta incidencia.');
        }

        // el historial se borra solo gracias al ON DELETE CASCADE de la tabla
        $c = $pdo->prepare('DELETE FROM incidencias WHERE id = ?');
        $c->execute([$id]);

        poner_mensaje('Incidencia borrada.');
        redirigir('incidencias.php');
    } else {
        prohibido('Acción no válida.');
    }
}

// saco el historial de esta incidencia (lo más nuevo primero)
$consulta = $pdo->prepare(
    'SELECT h.estado_anterior, h.estado_nuevo, h.comentario, h.creado_en, u.nombre AS autor
     FROM historial h
     JOIN usuarios u ON u.id = h.usuario_id
     WHERE h.incidencia_id = ?
     ORDER BY h.creado_en DESC, h.id DESC'
);
$consulta->execute([$id]);
$historial = $consulta->fetchAll();

$titulo = 'Incidencia #' . $id;
require __DIR__ . '/../includes/cabecera.php';
?>
<dl class="detalle">
    <dt>Equipo</dt>
    <dd><?= e($incidencia['equipo']) ?> · <?= e($incidencia['equipo_desc']) ?></dd>
    <dt>Categoría</dt>
    <dd><?= e(etiqueta($incidencia['categoria'])) ?></dd>
    <dt>Prioridad</dt>
    <dd><?= e(etiqueta($incidencia['prioridad'])) ?></dd>
    <dt>Estado</dt>
    <dd><?= e(etiqueta($incidencia['estado'])) ?></dd>
    <dt>Autor</dt>
    <dd><?= e($incidencia['autor']) ?></dd>
    <dt>Creada</dt>
    <dd><?= e(date('d/m/Y H:i', strtotime($incidencia['creada_en']))) ?></dd>
    <dt>Descripción</dt>
    <dd><?= e($incidencia['descripcion']) ?></dd>
</dl>

<?php if (es_profesor($usuario)) { ?>
    <h2>Cambiar estado</h2>
    <form method="post" action="incidencia.php?id=<?= e($id) ?>" class="formulario">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="estado">

        <div class="campo">
            <label for="estado">Nuevo estado</label>
            <select id="estado" name="estado">
                <?php foreach (ESTADOS as $est) { ?>
                    <?php
                    $marcado = '';
                    if ($estado_nuevo === $est) {
                        $marcado = 'selected';
                    }
                    ?>
                    <option value="<?= e($est) ?>" <?= $marcado ?>><?= e(etiqueta($est)) ?></option>
                <?php } ?>
            </select>
            <?php if (isset($errores['estado'])) { ?>
                <p class="error-campo"><?= e($errores['estado']) ?></p>
            <?php } ?>
        </div>

        <div class="campo">
            <label for="comentario">Comentario</label>
            <textarea id="comentario" name="comentario" rows="3" maxlength="300"><?= e($comentario) ?></textarea>
            <?php if (isset($errores['comentario'])) { ?>
                <p class="error-campo"><?= e($errores['comentario']) ?></p>
            <?php } ?>
        </div>

        <button type="submit">Guardar cambio</button>
    </form>
<?php } ?>

<h2>Historial</h2>
<?php if (count($historial) === 0) { ?>
    <p>Todavía no hay cambios de estado.</p>
<?php } else { ?>
    <ul class="historial">
        <?php foreach ($historial as $h) { ?>
            <li>
                <strong><?= e(date('d/m/Y H:i', strtotime($h['creado_en']))) ?></strong>
                · <?= e($h['autor']) ?>:
                <?= e(etiqueta($h['estado_anterior'])) ?> → <?= e(etiqueta($h['estado_nuevo'])) ?>
                <br><?= e($h['comentario']) ?>
            </li>
        <?php } ?>
    </ul>
<?php } ?>

<?php if ($puede_borrar) { ?>
    <form method="post" action="incidencia.php?id=<?= e($id) ?>" class="en-linea">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="borrar">
        <button type="submit" class="boton-peligro">Borrar incidencia</button>
    </form>
<?php } ?>

<p><a href="incidencias.php">← Volver al listado</a></p>
<?php require __DIR__ . '/../includes/pie.php'; ?>