/**
 * fechas.js
 * Funciones para enseñar las fechas en español con Intl.
 */

// "hace 2 días". Con numeric: 'always' sale siempre con número
// (si no, el navegador pone "anteayer" en vez de "hace 2 días")
const formatoRelativo = new Intl.RelativeTimeFormat('es', { numeric: 'always' });

// fecha completa, por ejemplo "lunes, 2 de noviembre de 2026, 18:30"
const formatoCompleto = new Intl.DateTimeFormat('es-ES', { dateStyle: 'full', timeStyle: 'short' });

/**
 * Mira si una fecha vale. new Date('xx') no da error, pero la fecha es inválida.
 * @param {Date} fecha La fecha a comprobar.
 * @returns {boolean} true si es una fecha correcta.
 */
export function esFechaValida(fecha) {
  return fecha instanceof Date && !isNaN(fecha.getTime());
}

/**
 * Devuelve la fecha completa en español.
 * @param {Date} fecha La fecha.
 * @returns {string} El texto, o 'Fecha desconocida' si la fecha no vale.
 */
export function fechaCompleta(fecha) {
  if (!esFechaValida(fecha)) {
    return 'Fecha desconocida';
  }
  return formatoCompleto.format(fecha);
}

/**
 * Dice cuánto tiempo ha pasado: "hace 5 minutos", "hace 2 días"...
 * Recibe "ahora" como parámetro para poder probarla con fechas fijas.
 * @param {Date} fecha La fecha pasada.
 * @param {Date} [ahora] El momento actual (si no se pone, se usa new Date()).
 * @returns {string} El texto.
 */
export function haceTiempo(fecha, ahora) {
  if (!esFechaValida(fecha)) {
    return 'Fecha desconocida';
  }
  if (ahora === undefined) {
    ahora = new Date();
  }

  const segundos = Math.round((ahora.getTime() - fecha.getTime()) / 1000);
  if (segundos < 60) {
    return 'ahora mismo';
  }
  const minutos = Math.floor(segundos / 60);
  if (minutos < 60) {
    return formatoRelativo.format(-minutos, 'minute');
  }
  const horas = Math.floor(minutos / 60);
  if (horas < 24) {
    return formatoRelativo.format(-horas, 'hour');
  }
  const dias = Math.floor(horas / 24);
  return formatoRelativo.format(-dias, 'day');
}