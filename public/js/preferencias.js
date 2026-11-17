/**
 * preferencias.js
 * Guarda en localStorage la pestaña, los filtros, el orden y el borrador del formulario.
 * Solo guardo cosas de la interfaz, nunca contraseñas ni datos sensibles.
 * Todo va dentro de try/catch porque localStorage puede fallar
 * (modo privado, bloqueado o lleno).
 */

const CLAVE_PREFERENCIAS = 'aulafix:preferencias';
const CLAVE_BORRADOR = 'aulafix:borrador';

/**
 * Valores por defecto de la pantalla.
 * @returns {{pestana:string, busqueda:string, prioridad:string, orden:{campo:string, direccion:string}}}
 */
export function preferenciasPorDefecto() {
  return {
    pestana: 'todas',
    busqueda: '',
    prioridad: '',
    orden: { campo: 'creada', direccion: 'desc' }
  };
}

/**
 * Lee las preferencias guardadas.
 * Lo que hay en localStorage lo puede cambiar cualquiera, así que
 * compruebo cada valor antes de usarlo.
 * @returns {{pestana:string, busqueda:string, prioridad:string, orden:{campo:string, direccion:string}}}
 */
export function cargarPreferencias() {
  const prefs = preferenciasPorDefecto();

  try {
    const texto = localStorage.getItem(CLAVE_PREFERENCIAS);
    if (texto === null) {
      return prefs;
    }
    const guardado = JSON.parse(texto);

    if (['todas', 'abierta', 'en_curso', 'resuelta'].includes(guardado.pestana)) {
      prefs.pestana = guardado.pestana;
    }
    if (typeof guardado.busqueda === 'string') {
      prefs.busqueda = guardado.busqueda.slice(0, 100);
    }
    if (['', 'baja', 'media', 'alta'].includes(guardado.prioridad)) {
      prefs.prioridad = guardado.prioridad;
    }
    if (guardado.orden && ['creada', 'equipo', 'prioridad'].includes(guardado.orden.campo)) {
      prefs.orden.campo = guardado.orden.campo;
      prefs.orden.direccion = guardado.orden.direccion === 'asc' ? 'asc' : 'desc';
    }
  } catch (error) {
    console.warn('AulaFix: no se pudieron leer las preferencias.', error.message);
  }

  return prefs;
}

/**
 * Guarda las preferencias. Si falla, la página sigue funcionando igual.
 * @param {object} prefs Las preferencias.
 * @returns {void}
 */
export function guardarPreferencias(prefs) {
  try {
    localStorage.setItem(CLAVE_PREFERENCIAS, JSON.stringify(prefs));
  } catch (error) {
    console.warn('AulaFix: no se pudieron guardar las preferencias.', error.message);
  }
}

/**
 * Guarda el borrador de la descripción. Si está vacío, lo borra.
 * @param {string} texto Lo que lleva escrito.
 * @returns {void}
 */
export function guardarBorrador(texto) {
  try {
    if (texto === '') {
      localStorage.removeItem(CLAVE_BORRADOR);
    } else {
      localStorage.setItem(CLAVE_BORRADOR, texto);
    }
  } catch (error) {
    console.warn('AulaFix: no se pudo guardar el borrador.', error.message);
  }
}

/**
 * Lee el borrador guardado.
 * @returns {string} El borrador, o '' si no hay.
 */
export function leerBorrador() {
  try {
    const texto = localStorage.getItem(CLAVE_BORRADOR);
    if (texto === null) {
      return '';
    }
    return texto;
  } catch (error) {
    return '';
  }
}

/**
 * Borra el borrador (cuando la incidencia ya se ha enviado).
 * @returns {void}
 */
export function borrarBorrador() {
  guardarBorrador('');
}