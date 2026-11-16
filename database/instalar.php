<?php

declare(strict_types=1);

/**
 * Instalador: crea las tablas y mete datos de ejemplo.
 * Se ejecuta en la terminal con: php database/instalar.php
 * Borra todo lo que hubiera en la base de datos.
 */

// si lo abren desde el navegador, no hace nada (así nadie puede borrar la base de datos)
if (PHP_SAPI !== 'cli') {
    exit('Solo se ejecuta desde la terminal.');
}

require_once __DIR__ . '/../includes/funciones.php';

$pdo = db();

// 1) leo esquema.sql y quito las líneas de comentario (las que empiezan por --)
$lineas = file(__DIR__ . '/esquema.sql');
$sql = '';
foreach ($lineas as $linea) {
    if (strpos(trim($linea), '--') !== 0) {
        $sql = $sql . $linea;
    }
}
// separo por ; y ejecuto cada sentencia una por una
foreach (explode(';', $sql) as $sentencia) {
    $sentencia = trim($sentencia);
    if ($sentencia !== '') {
        $pdo->exec($sentencia);
    }
}

// 2) usuarios (la contraseña se guarda con hash, nunca en claro)
$usuarios = [
    ['Marta Profesora', 'profe@aulafix.test', 'Profesor2026!', 'profesor'],
    ['Lucía Alumna', 'lucia@aulafix.test', 'Alumno2026!', 'alumno'],
    ['Marcos Alumno', 'marcos@aulafix.test', 'Alumno2026!', 'alumno'],
];
$consulta = $pdo->prepare(
    'INSERT INTO usuarios (nombre, email, clave_hash, rol, creado_en) VALUES (?, ?, ?, ?, ?)'
);
foreach ($usuarios as $u) {
    $hash = password_hash($u[2], PASSWORD_ARGON2ID);
    $consulta->execute([$u[0], $u[1], $hash, $u[3], ahora_sql()]);
}

// 3) equipos
$equipos = [
    ['PC-01', 'Ordenador del puesto 1'],
    ['PC-02', 'Ordenador del puesto 2'],
    ['PC-03', 'Ordenador del puesto 3'],
    ['PROY-01', 'Proyector del aula'],
    ['IMP-01', 'Impresora del pasillo'],
];
$consulta = $pdo->prepare('INSERT INTO equipos (codigo, descripcion) VALUES (?, ?)');
foreach ($equipos as $eq) {
    $consulta->execute([$eq[0], $eq[1]]);
}

// 4) incidencias de ejemplo
// el último número son los días que han pasado desde que se creó,
// para que en el panel salga "hace 2 días", "ayer", etc.
$incidencias = [
    // equipo_id, usuario_id, categoria, prioridad, descripcion, estado, días
    [4, 2, 'hardware', 'alta', 'El proyector se apaga solo a los 10 minutos.', 'abierta', 2],
    [1, 3, 'software', 'media', 'El navegador no abre y pide actualizar.', 'en_curso', 5],
    [5, 2, 'hardware', 'baja', 'La impresora se ha quedado sin tóner.', 'resuelta', 9],
    [2, 3, 'red', 'alta', 'No hay conexión a internet en el puesto 2.', 'abierta', 1],
    [3, 2, 'otro', 'media', 'Falta el ratón en el puesto 3.', 'abierta', 0],
    [1, 3, 'software', 'alta', 'No arranca el entorno de desarrollo.', 'en_curso', 3],
];
$consulta = $pdo->prepare(
    'INSERT INTO incidencias (equipo_id, usuario_id, categoria, prioridad, descripcion, estado, creada_en, actualizada_en)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
foreach ($incidencias as $i) {
    $fecha = date('Y-m-d H:i:s', strtotime('-' . $i[6] . ' days -1 hour'));
    $consulta->execute([$i[0], $i[1], $i[2], $i[3], $i[4], $i[5], $fecha, $fecha]);
}

echo "Base de datos instalada.\n";
echo "Profesor: profe@aulafix.test / Profesor2026!\n";
echo "Alumno:   lucia@aulafix.test / Alumno2026!\n";
