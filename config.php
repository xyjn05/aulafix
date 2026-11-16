<?php
declare(strict_types=1);

/**
 * Configuración de AulaFix.
 * Aquí están los datos de conexión a la base de datos y los valores permitidos.
 * Si existe una variable de entorno se usa esa; si no, se usa el valor por defecto
 * (así la contraseña real no hace falta subirla al repositorio).
 */

// Datos de conexión a MySQL
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PUERTO', getenv('DB_PORT') ?: '3306');
define('DB_NOMBRE', getenv('DB_NAME') ?: 'aulafix');
define('DB_USUARIO', getenv('DB_USER') ?: 'aulafix');
define('DB_CLAVE', getenv('DB_PASS') ?: 'aulafix');

define('ZONA_HORARIA', 'Europe/Madrid');

// Listas blancas: solo se aceptan estos valores en los formularios.
define('CATEGORIAS', ['hardware', 'software', 'red', 'otro']);
define('PRIORIDADES', ['baja', 'media', 'alta']);
define('ESTADOS', ['abierta', 'en_curso', 'resuelta']);