<?php

declare(strict_types=1);

/**
 * Lista de incidencias.
 * Saco las incidencias de la base de datos y las muestro en una tabla de PHP.
 * También las dejo en JSON dentro de la página para que las lea el JavaScript.
 */

require_once __DIR__ . '/../includes/funciones.php';
iniciar_sesion();
requerir_login();

$pdo = db();

// el JOIN une las tablas para sacar el código del equipo y el nombre del autor
$consulta = $pdo->prepare(
    'SELECT i.id, e.codigo AS equipo, i.categoria, i.prioridad, i.descripcion,
            i.estado, u.nombre AS autor, i.creada_en
     FROM incidencias i
     JOIN equipos e ON e.id = i.equipo_id
     JOIN usuarios u ON u.id = i.usuario_id
     ORDER BY i.creada_en DESC'
);
$consulta->execute();
$filas = $consulta->fetchAll();

// preparo los datos con el formato del enunciado (apartado 3.4),
// el JavaScript espera justo estos nombres
$incidencias = [];
foreach ($filas as $fila) {
    $incidencias[] = [
        'id'          => (int) $fila['id'],
        'equipo'      => $fila['equipo'],
        'categoria'   => $fila['categoria'],
        'prioridad'   => $fila['prioridad'],
        'descripcion' => $fila['descripcion'],
        'estado'      => $fila['estado'],
        'autor'       => $fila['autor'],
        'creada_en'   => date('c', strtotime($fila['creada_en'])), // formato ISO
    ];
}
$json = json_cliente($incidencias);

$titulo = 'Incidencias';
require __DIR__ . '/../includes/cabecera.php';
?>
<p><a class="boton" href="nueva.php">Avisar de una incidencia</a></p>

<!-- el JavaScript va a cambiar esto por el panel.
     Si no funciona JavaScript, se sigue viendo esta tabla -->
<div id="panel">
    <table class="tabla">
        <caption class="sr-only">Listado de incidencias</caption>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Equipo</th>
                <th>Categoría</th>
                <th>Prioridad</th>
                <th>Estado</th>
                <th>Autor</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($incidencias as $inc) { ?>
                <?php
                // urgente = prioridad alta y que no esté resuelta
                $clase = '';
                if ($inc['prioridad'] === 'alta' && $inc['estado'] !== 'resuelta') {
                    $clase = 'urgente';
                }
                ?>
                <tr class="<?= e($clase) ?>">
                    <td><?= e(date('d/m/Y H:i', strtotime($inc['creada_en']))) ?></td>
                    <td><?= e($inc['equipo']) ?></td>
                    <td><?= e(etiqueta($inc['categoria'])) ?></td>
                    <td><?= e(etiqueta($inc['prioridad'])) ?></td>
                    <td><?= e(etiqueta($inc['estado'])) ?></td>
                    <td><?= e($inc['autor']) ?></td>
                    <td><?= e($inc['descripcion']) ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- application/json hace que el navegador no lo ejecute, solo lo guarda.
     Aquí no uso e() porque cambiaría las comillas y JSON.parse daría error.
     El JSON ya es seguro por las opciones JSON_HEX_* de json_cliente() -->
<script id="datos-incidencias" type="application/json">
    <?= $json ?>
</script>
<!-- cuando haga el JavaScript, quito los comentarios de esta línea:
<script type="module" src="js/main.js"></script> -->
<?php require __DIR__ . '/../includes/pie.php'; ?>