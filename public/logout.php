<?php
declare(strict_types=1);

/**
 * Cierra la sesión.
 * Solo funciona con POST y con token CSRF, para que otra web
 * no pueda cerrarme la sesión con un enlace.
 */

require_once __DIR__ . '/../includes/funciones.php';
iniciar_sesion();

// si entran escribiendo la dirección (GET), no hago nada
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('incidencias.php');
}
csrf_verificar();

// cierre completo: vacío los datos, borro la cookie de sesión y destruyo la sesión
$_SESSION = [];
setcookie(session_name(), '', time() - 3600, '/');
session_destroy();

redirigir('login.php');