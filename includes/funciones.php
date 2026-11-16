<?php
declare(strict_types=1);

/**
 * Funciones comunes de AulaFix: escapar texto, sesión, token CSRF,
 * mensajes, redirecciones y validación de formularios.
 */

require_once __DIR__ . '/db.php';

date_default_timezone_set(ZONA_HORARIA);

/**
 * Escapa un texto para mostrarlo en HTML sin riesgo (evita XSS).
 * Se escapa al mostrar, no al guardar, porque el mismo dato puede ir
 * a HTML, a JSON o a otros sitios y cada uno necesita su escapado.
 *
 * @param string|int|null $valor El texto que se va a mostrar.
 * @return string El texto seguro para HTML.
 */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Inicia la sesión con una cookie más segura.
 * httponly: JavaScript no puede leer la cookie de sesión.
 * samesite: el navegador no la envía desde formularios de otras webs.
 *
 * @return void
 */
function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false, // en una web real con HTTPS sería true
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Devuelve el token CSRF de la sesión (lo crea si todavía no existe).
 *
 * @return string El token, una cadena aleatoria.
 */
function csrf_token(): string
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Devuelve el campo oculto con el token para ponerlo en cada formulario POST.
 *
 * @return string El HTML del campo oculto.
 */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Comprueba que el token enviado por el formulario es el de la sesión.
 * Si no coincide, responde 403 y detiene la página.
 * hash_equals compara los dos textos de forma segura.
 *
 * @return void
 */
function csrf_verificar(): void
{
    $enviado = '';
    if (isset($_POST['csrf']) && is_string($_POST['csrf'])) {
        $enviado = $_POST['csrf'];
    }
    if (!hash_equals(csrf_token(), $enviado)) {
        http_response_code(403);
        exit('Formulario no válido. Vuelve atrás y inténtalo de nuevo.');
    }
}

/**
 * Redirige a otra página y detiene el script.
 * Se usa después de guardar un formulario (patrón Post-Redirect-Get)
 * para que al pulsar F5 no se reenvíen los datos.
 *
 * @param string $url La página de destino, por ejemplo 'incidencias.php'.
 * @return void
 */
function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Guarda un mensaje para enseñarlo en la página siguiente.
 *
 * @param string $texto El mensaje.
 * @param string $tipo 'ok' o 'error'.
 * @param string $marca Una marca opcional que puede leer el JavaScript.
 * @return void
 */
function poner_mensaje(string $texto, string $tipo = 'ok', string $marca = ''): void
{
    $_SESSION['mensaje'] = ['texto' => $texto, 'tipo' => $tipo, 'marca' => $marca];
}

/**
 * Devuelve el mensaje guardado y lo borra para que se vea una sola vez.
 *
 * @return array|null El mensaje, o null si no hay ninguno.
 */
function tomar_mensaje(): ?array
{
    if (!isset($_SESSION['mensaje'])) {
        return null;
    }
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
    return $mensaje;
}

/**
 * Lee un campo de $_POST como texto.
 * Si el campo no existe o no es texto, devuelve una cadena vacía.
 *
 * @param string $nombre El nombre del campo.
 * @param bool $recortar Si es true se quitan los espacios de los extremos
 *                       (con las contraseñas se pone false).
 * @return string El valor del campo.
 */
function campo_post(string $nombre, bool $recortar = true): string
{
    if (!isset($_POST[$nombre]) || !is_string($_POST[$nombre])) {
        return '';
    }
    if ($recortar) {
        return trim($_POST[$nombre]);
    }
    return $_POST[$nombre];
}

/**
 * Comprueba que un valor está dentro de una lista de valores permitidos.
 *
 * @param string $valor El valor recibido.
 * @param array $lista Los valores permitidos.
 * @return bool true si está en la lista.
 */
function en_lista(string $valor, array $lista): bool
{
    return in_array($valor, $lista, true);
}

/**
 * Comprueba que un texto tiene entre $min y $max caracteres.
 * Se usa mb_strlen para contar bien las tildes y la ñ.
 *
 * @param string $texto El texto.
 * @param int $min Mínimo de caracteres.
 * @param int $max Máximo de caracteres.
 * @return bool true si la longitud es correcta.
 */
function longitud_valida(string $texto, int $min, int $max): bool
{
    $largo = mb_strlen($texto);
    return $largo >= $min && $largo <= $max;
}

/**
 * Convierte datos a JSON para ponerlos dentro de un <script> de la página.
 * Las opciones JSON_HEX_* cambian < > & ' " por códigos \uXXXX, así un texto
 * como "</script>" no puede romper la página ni meter código (XSS).
 *
 * @param array $datos Los datos que se van a convertir.
 * @return string El JSON.
 */
function json_cliente(array $datos): string
{
    $json = json_encode(
        $datos,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    );
    if ($json === false) {
        return '[]';
    }
    return $json;
}

/**
 * Convierte un valor interno en un texto para mostrar ('en_curso' pasa a 'En curso').
 *
 * @param string $valor El valor interno.
 * @return string El texto legible.
 */
function etiqueta(string $valor): string
{
    return ucfirst(str_replace('_', ' ', $valor));
}

/**
 * Devuelve la fecha y hora actuales en formato de MySQL.
 *
 * @return string Por ejemplo '2026-11-16 10:30:00'.
 */
function ahora_sql(): string
{
    return date('Y-m-d H:i:s');
}