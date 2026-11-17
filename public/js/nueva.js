/**
 * nueva.js
 * Mejoras para el formulario de nueva incidencia: contador de caracteres
 * y borrador guardado en localStorage.
 * Si JavaScript no funciona, el formulario sigue funcionando igual.
 */
import { guardarBorrador, leerBorrador } from './preferencias.js';

const MAXIMO = 500;
const area = document.getElementById('descripcion');
const contador = document.getElementById('contador-caracteres');

/**
 * Pone en pantalla cuántos caracteres lleva escritos, por ejemplo "35 / 500".
 * @returns {void}
 */
function actualizarContador() {
  contador.textContent = area.value.length + ' / ' + MAXIMO;
}

if (area !== null && contador !== null) {
  // solo recupero el borrador si el campo está vacío.
  // Si el servidor devolvió el formulario con errores, lo que escribió el usuario manda.
  if (area.value === '') {
    area.value = leerBorrador().slice(0, MAXIMO);
  }
  actualizarContador();

  // cada vez que escribe, actualizo el contador y guardo el borrador
  area.addEventListener('input', function () {
    actualizarContador();
    guardarBorrador(area.value);
  });
}