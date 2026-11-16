<?php
declare(strict_types=1);

/**
 * Conexión a la base de datos con PDO.
 * Toda la aplicación se conecta usando solo la función db().
 */

require_once __DIR__ . '/../config.php';

/**
 * Crea una conexión PDO a MySQL.
 * Llámala una sola vez por página y guarda el resultado en una variable ($pdo = db();).
 *
 * @return PDO La conexión lista para usar.
 */
function db(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PUERTO
         . ';dbname=' . DB_NOMBRE . ';charset=utf8mb4';

    $opciones = [
        // Si una consulta falla, PHP lanza una excepción (no falla en silencio).
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // Cada fila se devuelve como array con los nombres de las columnas.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Sentencias preparadas reales: los datos nunca se mezclan con el SQL.
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    return new PDO($dsn, DB_USUARIO, DB_CLAVE, $opciones);
}