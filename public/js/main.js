/**
 * main.js
 * Programa principal del panel de incidencias (se ejecuta en el navegador).
 * Lee el JSON que PHP dejó en la página, guarda lo que elige el usuario
 * (pestaña, filtros, orden) y vuelve a pintar el panel cuando algo cambia.
 * Si algo falla, no toco nada y se sigue viendo la tabla de PHP.
 */
import { Incidencia, resumir, filtrar, ordenar } from './incidencia.js';
import { cargarPreferencias, guardarPreferencias, borrarBorrador } from './preferencias.js';
import { crearPanel, pintarPanel, actualizarFechas } from './vistas.js';

/**
 * Lee el JSON de la página y lo convierte en objetos Incidencia.
 * @returns {Incidencia[]|null} La lista (puede estar vacía), o null si el JSON está mal.
 */
function leerIncidencias() {
  const nodo = document.getElementById('datos-incidencias');
  if (nodo === null) {
    return null;
  }
  try {
    const datos = JSON.parse(nodo.textContent);
    if (!Array.isArray(datos)) {
      throw new Error('el JSON no es una lista');
    }
    return datos.map(function (d) {
      return new Incidencia(d);
    });
  } catch (error) {
    console.warn('AulaFix: los datos no son correctos, se queda la tabla de PHP.', error.message);
    return null;
  }
}

// Si acaba de crear una incidencia (PHP deja esta marca), ya no hace falta el borrador
if (document.querySelector('[data-marca="incidencia-creada"]') !== null) {
  borrarBorrador();
}

const incidencias = leerIncidencias();
const contenedor = document.getElementById('panel');

if (incidencias !== null && contenedor !== null) {
  const prefs = cargarPreferencias();
  const panel = crearPanel(contenedor);

  // pongo en el buscador y en el desplegable lo que se guardó la última vez
  panel.buscador.value = prefs.busqueda;
  panel.selectorPrioridad.value = prefs.prioridad;

  /**
   * Filtra, ordena, pinta y guarda las preferencias.
   * Se llama cada vez que el usuario cambia algo.
   * @returns {void}
   */
  function refrescar() {
    const visibles = ordenar(filtrar(incidencias, prefs), prefs.orden);
    pintarPanel(panel, {
      resumen: resumir(incidencias),
      visibles: visibles,
      total: incidencias.length,
      prefs: prefs
    });
    guardarPreferencias(prefs);
  }

  // Pestañas: un solo manejador en el contenedor de las pestañas.
  // closest busca el botón sobre el que se hizo clic.
  panel.pestanas.addEventListener('click', function (evento) {
    const boton = evento.target.closest('[data-pestana]');
    if (boton === null) {
      return;
    }
    prefs.pestana = boton.dataset.pestana;
    refrescar();
  });

  // Buscador: filtra mientras se escribe
  panel.buscador.addEventListener('input', function () {
    prefs.busqueda = panel.buscador.value;
    refrescar();
  });

  // Desplegable de prioridad
  panel.selectorPrioridad.addEventListener('change', function () {
    prefs.prioridad = panel.selectorPrioridad.value;
    refrescar();
  });

  // Tabla: un solo manejador para las cabeceras (con closest)
  panel.tabla.addEventListener('click', function (evento) {
    const boton = evento.target.closest('[data-campo]');
    if (boton === null) {
      return;
    }
    const campo = boton.dataset.campo;
    if (prefs.orden.campo === campo) {
      // otro clic en la misma columna: invierto el orden
      prefs.orden.direccion = prefs.orden.direccion === 'asc' ? 'desc' : 'asc';
    } else {
      // columna nueva: equipo de la A a la Z; fecha y prioridad de más nuevo / más alto
      prefs.orden.campo = campo;
      prefs.orden.direccion = campo === 'equipo' ? 'asc' : 'desc';
    }
    refrescar();
  });

  refrescar();

  // cada minuto actualizo los "hace X" para que no se queden viejos
  setInterval(function () {
    actualizarFechas(panel);
  }, 60000);
}