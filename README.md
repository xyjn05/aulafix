# AulaFix

## Qué es

AulaFix sirve para avisar de las cosas que fallan en el aula de informática (un ordenador que no arranca, el proyector, la impresora...).

- El alumnado entra con su cuenta, ve las incidencias y avisa de una nueva.
- El profesorado además cambia el estado (abierta, en curso, resuelta) con un comentario y puede borrar incidencias.
- En la lista hay un panel que se maneja en el navegador: contadores, pestañas por estado, buscador y tabla que se ordena al hacer clic.

## Cómo arrancarlo

Lo he probado en Windows con XAMPP, PHP 8.4, VS Code y DBeaver.
Hace falta PHP 8.1 o más, con la extensión `pdo_mysql` y Argon2id.

1. Abrir XAMPP y pulsar **Start** en MySQL (Apache no hace falta).
2. Crear la base de datos y el usuario (por ejemplo desde DBeaver):

   ```sql
   CREATE DATABASE aulafix CHARACTER SET utf8mb4;
   CREATE USER 'aulafix'@'localhost' IDENTIFIED BY 'aulafix';
   GRANT ALL PRIVILEGES ON aulafix.* TO 'aulafix'@'localhost';
   ```

3. En la carpeta del proyecto, crear las tablas y los datos de ejemplo (esto **borra** lo que hubiera en las tablas):

   ```
   php database/instalar.php
   ```

   En Windows con XAMPP, si `php` no se reconoce, usar la ruta completa: `C:\xampp\php\php.exe database\instalar.php`.

4. Arrancar el servidor de PHP (solo la carpeta `public` es accesible):

   ```
   php -S localhost:8000 -t public
   ```

5. Abrir <http://localhost:8000> en el navegador.

Los datos de conexión están en `config.php`. Si existen las variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS`, se usan esas.

## Usuarios de prueba

| Rol | Correo | Contraseña |
|---|---|---|
| Profesor | profe@aulafix.test | Profesor2026! |
| Alumno | lucia@aulafix.test | Alumno2026! |
| Alumno | marcos@aulafix.test | Alumno2026! |

Los usuarios nuevos que se registran son siempre alumnos.

## Cómo funciona (cliente y servidor)

Recorrido de una petición, por ejemplo al abrir `incidencias.php`:

1. El navegador pide la página al servidor de PHP.
2. PHP comprueba la sesión. Si no hay sesión, manda al login.
3. PHP consulta MySQL con PDO y sentencias preparadas.
4. PHP genera el HTML con una tabla y, además, escribe las incidencias en JSON dentro de una etiqueta `<script type="application/json">`.
5. El navegador recibe la página y ejecuta `js/main.js`, que lee ese JSON con `JSON.parse` y cambia la tabla por el panel.

Qué se ejecuta en cada sitio:

- **Servidor (PHP y MySQL):** registro, login, permisos, validación de formularios, guardar y leer datos.
- **Navegador (JavaScript):** resumen, pestañas, buscador, ordenar la tabla, fechas, contador de caracteres y borrador.

## Estructura de carpetas

```
aulafix/
├─ config.php         datos de conexión y valores permitidos
├─ database/          esquema.sql (tablas) e instalar.php (datos de ejemplo)
├─ includes/          db.php, funciones.php, cabecera.php, pie.php
├─ public/            lo único accesible desde el navegador
│  ├─ *.php           páginas
│  ├─ css/estilos.css
│  └─ js/             main.js, incidencia.js, vistas.js, fechas.js, preferencias.js, nueva.js
└─ README.md
```

## Seguridad

- Contraseñas con `password_hash` (Argon2id) y `password_verify`.
- Se regenera el id de sesión al iniciar sesión (contra session fixation).
- Cookie de sesión con `HttpOnly` y `SameSite`.
- Mismo mensaje de error si el correo no existe o la contraseña es incorrecta.
- La cookie "Recordar mi correo" guarda solo el correo, 30 días.
- Token CSRF en todos los formularios POST y redirección después de guardar (Post-Redirect-Get).
- Todas las consultas usan `prepare()` y `execute()`.
- Listas blancas para categoría, prioridad y estado.
- `localStorage` solo guarda la pestaña, los filtros, el orden y el borrador. Nada sensible.

## Uso de IA

He usado una herramienta de IA (Claude) como ayuda en este proyecto:

- Para crear una serie de datos de ejemplo (usuarios, equipos e incidencias) y que la base de datos parezca más real. Están en `database/instalar.php`.
- Para ayudar a diseño visual de las páginas web (estructura, formularios, panel y estilos).

## Pruebas que he hecho

- Registro, login (con y sin "Recordar mi correo") y cierre de sesión.
- Crear incidencias con datos correctos y con errores. Se conserva lo escrito.
- Probar que `<b>hola</b>` en una descripción se ve como texto, no en negrita.
- Cambiar el valor de la prioridad con F12 y comprobar que el servidor lo rechaza.
- Probar como alumno el cambio de estado y el borrado de incidencias ajenas: el servidor responde 403.
- Cambiar el estado como profesor y comprobar el historial.
- Panel: pestañas, buscador, filtro de prioridad, ordenar por columnas y recordar la selección al recargar.
- Desactivar JavaScript y comprobar que se sigue viendo la tabla de PHP.