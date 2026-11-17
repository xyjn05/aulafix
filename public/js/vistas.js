/**
 * vistas.js
 * Aquí construyo el panel con el DOM: resumen, pestañas, filtros, contador y tabla.
 * Los datos de los usuarios los pongo SIEMPRE con textContent, nunca con innerHTML,
 * porque innerHTML interpretaría etiquetas que haya escrito un usuario (XSS).
 */
import { haceTiempo, fechaCompleta } from './fechas.js';

// [valor, texto que se ve]
const PESTANAS = [
  ['todas', 'Todas'],
  ['abierta', 'Abiertas'],
  ['en_curso', 'En curso'],
  ['resuelta', 'Resueltas']
];
const TARJETAS = [
  ['abierta', 'Abiertas'],
  ['en_curso', 'En curso'],
  ['resuelta', 'Resueltas'],
  ['urgentes', 'Urgentes sin resolver']
];
// [campo, texto, ¿se puede ordenar?]
const COLUMNAS = [
  ['creada', 'Fecha', true],
  ['equipo', 'Equipo', true],
  ['categoria', 'Categoría', false],
  ['prioridad', 'Prioridad', true],
  ['estado', 'Estado', false],
  ['autor', 'Autor', false],
  ['descripcion', 'Descripción', false]
];

/**
 * Crea un elemento con una clase y un texto (el texto con textContent).
 * @param {string} etiqueta Por ejemplo 'div' o 'td'.
 * @param {string} clase Clase CSS ('' si no tiene).
 * @param {string} texto Texto ('' si no tiene).
 * @returns {HTMLElement} El elemento nuevo.
 */
function crear(etiqueta, clase, texto) {
  const nodo = document.createElement(etiqueta);
  if (clase) {
    nodo.className = clase;
  }
  if (texto) {
    nodo.textContent = texto;
  }
  return nodo;
}

/**
 * Pone un valor como texto para mostrar: 'en_curso' pasa a 'En curso'.
 * @param {string} valor El valor.
 * @returns {string} El texto.
 */
function legible(valor) {
  const texto = valor.replace(/_/g, ' ');
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

/**
 * Construye el panel vacío dentro del contenedor (cambia la tabla de PHP por el panel).
 * Lo construyo una sola vez; después solo cambio los textos y las filas.
 * @param {HTMLElement} contenedor El div #panel.
 * @returns {object} Los elementos importantes, para usarlos después.
 */
export function crearPanel(contenedor) {
  const panel = { valores: {}, cabeceras: {} };

  // 1) resumen: las 4 tarjetas
  const resumen = crear('section', 'resumen');
  resumen.setAttribute('aria-label', 'Resumen');
  TARJETAS.forEach(function (t) {
    const tarjeta = crear('div', 'tarjeta');
    panel.valores[t[0]] = crear('span', 'tarjeta-valor', '0');
    tarjeta.append(panel.valores[t[0]], crear('span', '', t[1]));
    resumen.append(tarjeta);
  });

  // 2) pestañas
  panel.pestanas = crear('div', 'pestanas');
  panel.pestanas.setAttribute('role', 'tablist');
  PESTANAS.forEach(function (p) {
    const boton = crear('button', 'pestana', p[1]);
    boton.type = 'button';
    boton.setAttribute('role', 'tab');
    boton.dataset.pestana = p[0]; // así sé qué pestaña es al hacer clic
    panel.pestanas.append(boton);
  });

  // 3) filtros: buscador y desplegable de prioridad
  const filtros = crear('div', 'filtros');
  const etiquetaBuscar = crear('label', '', 'Buscar ');
  etiquetaBuscar.htmlFor = 'buscador';
  panel.buscador = crear('input');
  panel.buscador.type = 'search';
  panel.buscador.id = 'buscador';
  panel.buscador.placeholder = 'Equipo, descripción o autor';

  const etiquetaPrioridad = crear('label', '', ' Prioridad ');
  etiquetaPrioridad.htmlFor = 'filtro-prioridad';
  panel.selectorPrioridad = crear('select');
  panel.selectorPrioridad.id = 'filtro-prioridad';
  [['', 'Todas'], ['alta', 'Alta'], ['media', 'Media'], ['baja', 'Baja']].forEach(function (o) {
    const opcion = crear('option', '', o[1]);
    opcion.value = o[0];
    panel.selectorPrioridad.append(opcion);
  });
  filtros.append(etiquetaBuscar, panel.buscador, etiquetaPrioridad, panel.selectorPrioridad);

  // 4) contador. aria-live hace que el lector de pantalla lo lea cuando cambia
  panel.contador = crear('p', 'contador');
  panel.contador.setAttribute('aria-live', 'polite');

  // 5) tabla con su cabecera
  panel.tabla = crear('table', 'tabla');
  panel.tabla.append(crear('caption', 'sr-only', 'Listado de incidencias'));
  const fila = crear('tr');
  COLUMNAS.forEach(function (c) {
    const th = crear('th');
    th.scope = 'col';
    if (c[2]) {
      // columna que se puede ordenar: un botón dentro del th
      const boton = crear('button', 'orden', c[1]);
      boton.type = 'button';
      boton.dataset.campo = c[0];
      th.append(boton);
      th.setAttribute('aria-sort', 'none');
      panel.cabeceras[c[0]] = th;
    } else {
      th.textContent = c[1];
    }
    fila.append(th);
  });
  const cabecera = crear('thead');
  cabecera.append(fila);
  panel.cuerpo = crear('tbody');
  panel.tabla.append(cabecera, panel.cuerpo);

  const zona = crear('div', 'zona-tabla');
  zona.append(panel.tabla);

  contenedor.textContent = ''; // vacío el contenedor (quito la tabla de PHP)
  contenedor.append(resumen, panel.pestanas, filtros, panel.contador, zona);

  return panel;
}

/**
 * Crea una fila de la tabla para una incidencia.
 * @param {Incidencia} inc La incidencia.
 * @returns {HTMLTableRowElement} La fila <tr>.
 */
function crearFila(inc) {
  // la clase "urgente" pinta la fila de rojo (está en el CSS)
  const fila = crear('tr', inc.esUrgente ? 'urgente' : '');

  // fecha: "hace 2 días", y al pasar el ratón sale la fecha completa (title)
  const celdaFecha = crear('td');
  const hora = crear('time', '', haceTiempo(inc.fecha));
  hora.dateTime = inc.creadaEn;
  hora.title = fechaCompleta(inc.fecha);
  celdaFecha.append(hora);

  // el equipo es un enlace al detalle
  const celdaEquipo = crear('td');
  const enlace = crear('a', '', inc.equipo);
  enlace.href = 'incidencia.php?id=' + inc.id;
  celdaEquipo.append(enlace);

  fila.append(
    celdaFecha,
    celdaEquipo,
    crear('td', '', legible(inc.categoria)),
    crear('td', '', legible(inc.prioridad)),
    crear('td', '', legible(inc.estado)),
    crear('td', '', inc.autor),
    crear('td', '', inc.descripcion)
  );
  return fila;
}

/**
 * Pinta en el panel los datos actuales (tarjetas, pestaña activa, orden, contador y filas).
 * @param {object} panel El panel que devolvió crearPanel.
 * @param {{resumen:object, visibles:Incidencia[], total:number, prefs:object}} datos Lo que hay que pintar.
 * @returns {void}
 */
export function pintarPanel(panel, datos) {
  // tarjetas
  TARJETAS.forEach(function (t) {
    panel.valores[t[0]].textContent = String(datos.resumen[t[0]]);
  });

  // la pestaña activa se marca con aria-selected
  for (const boton of panel.pestanas.children) {
    boton.setAttribute('aria-selected', String(boton.dataset.pestana === datos.prefs.pestana));
  }

  // aria-sort dice qué columna está ordenada y en qué dirección
  for (const campo in panel.cabeceras) {
    let valor = 'none';
    if (campo === datos.prefs.orden.campo) {
      valor = datos.prefs.orden.direccion === 'asc' ? 'ascending' : 'descending';
    }
    panel.cabeceras[campo].setAttribute('aria-sort', valor);
  }

  panel.contador.textContent = 'Mostrando ' + datos.visibles.length + ' de ' + datos.total + ' incidencias';

  // meto todas las filas en un fragmento y lo añado de una vez (más rápido)
  const fragmento = document.createDocumentFragment();
  if (datos.visibles.length === 0) {
    const fila = crear('tr');
    const celda = crear('td', 'vacio', 'No hay incidencias que coincidan.');
    celda.colSpan = COLUMNAS.length;
    fila.append(celda);
    fragmento.append(fila);
  }
  datos.visibles.forEach(function (inc) {
    fragmento.append(crearFila(inc));
  });
  panel.cuerpo.textContent = '';
  panel.cuerpo.append(fragmento);
}

/**
 * Recalcula los "hace X días" sin volver a pintar toda la tabla.
 * @param {object} panel El panel.
 * @returns {void}
 */
export function actualizarFechas(panel) {
  panel.cuerpo.querySelectorAll('time[datetime]').forEach(function (hora) {
    hora.textContent = haceTiempo(new Date(hora.dateTime));
  });
}