/**
 * incidencia.js
 * La clase Incidencia y las funciones para resumir, filtrar y ordenar la lista.
 * Ninguna función cambia el array original: así los datos de la página
 * quedan intactos y se pueden volver a filtrar u ordenar de otra manera.
 */

// valor de cada prioridad, para poder ordenar (alta es la más grande)
const PESO_PRIORIDAD = { baja: 1, media: 2, alta: 3 };

/**
 * Una incidencia del tablón.
 */
export class Incidencia {
  /**
   * @param {object} datos Un objeto del JSON que manda el servidor.
   */
  constructor(datos) {
    this.id = Number(datos.id);
    this.equipo = String(datos.equipo);
    this.categoria = String(datos.categoria);
    this.prioridad = String(datos.prioridad);
    this.descripcion = String(datos.descripcion);
    this.estado = String(datos.estado);
    this.autor = String(datos.autor);
    this.creadaEn = String(datos.creada_en); // el texto original (ISO)
    this.fecha = new Date(datos.creada_en);   // lo mismo pero como Date
  }

  /**
   * Urgente = prioridad alta y que no esté resuelta.
   * @returns {boolean}
   */
  get esUrgente() {
    return this.prioridad === 'alta' && this.estado !== 'resuelta';
  }

  /**
   * Días que han pasado desde que se creó (0 si es de hoy o la fecha no vale).
   * @returns {number}
   */
  get diasAbierta() {
    const milisegundos = new Date().getTime() - this.fecha.getTime();
    if (isNaN(milisegundos) || milisegundos < 0) {
      return 0;
    }
    return Math.floor(milisegundos / (1000 * 60 * 60 * 24));
  }
}

/**
 * Cuenta cuántas incidencias hay en cada estado y cuántas son urgentes.
 * Uso reduce para recorrer la lista una sola vez.
 * @param {Incidencia[]} lista Todas las incidencias.
 * @returns {{abierta:number, en_curso:number, resuelta:number, urgentes:number}}
 */
export function resumir(lista) {
  return lista.reduce(function (total, inc) {
    if (total[inc.estado] !== undefined) {
      total[inc.estado] = total[inc.estado] + 1;
    }
    if (inc.esUrgente) {
      total.urgentes = total.urgentes + 1;
    }
    return total;
  }, { abierta: 0, en_curso: 0, resuelta: 0, urgentes: 0 });
}

/**
 * Quita las tildes y pasa a minúsculas, para que "Lucía" y "lucia" coincidan.
 * @param {string} texto El texto.
 * @returns {string} El texto normalizado.
 */
function normalizar(texto) {
  return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}

/**
 * Filtra por pestaña (estado), por texto (equipo, descripción o autor) y por prioridad.
 * filter devuelve un array nuevo, el original no se toca.
 * @param {Incidencia[]} lista Todas las incidencias.
 * @param {{pestana:string, busqueda:string, prioridad:string}} criterios Lo que ha elegido el usuario.
 * @returns {Incidencia[]} Las que cumplen todo.
 */
export function filtrar(lista, criterios) {
  const texto = normalizar(criterios.busqueda.trim());

  return lista.filter(function (inc) {
    if (criterios.pestana !== 'todas' && inc.estado !== criterios.pestana) {
      return false;
    }
    if (criterios.prioridad !== '' && inc.prioridad !== criterios.prioridad) {
      return false;
    }
    if (texto === '') {
      return true;
    }
    return normalizar(inc.equipo).includes(texto)
        || normalizar(inc.descripcion).includes(texto)
        || normalizar(inc.autor).includes(texto);
  });
}

/**
 * Ordena por fecha, equipo o prioridad.
 * sort() cambia el array sobre el que se llama, por eso ordeno una copia
 * (slice) y así la lista original no se modifica.
 * @param {Incidencia[]} lista Las incidencias.
 * @param {{campo:string, direccion:string}} orden campo: 'creada', 'equipo' o 'prioridad'; direccion: 'asc' o 'desc'.
 * @returns {Incidencia[]} Una copia ordenada.
 */
export function ordenar(lista, orden) {
  const copia = lista.slice();

  copia.sort(function (a, b) {
    let resultado = 0;
    if (orden.campo === 'equipo') {
      // numeric: true para que PC-2 vaya antes que PC-10
      resultado = a.equipo.localeCompare(b.equipo, 'es', { numeric: true });
    } else if (orden.campo === 'prioridad') {
      resultado = (PESO_PRIORIDAD[a.prioridad] || 0) - (PESO_PRIORIDAD[b.prioridad] || 0);
    } else {
      resultado = a.fecha.getTime() - b.fecha.getTime();
    }
    if (orden.direccion === 'desc') {
      resultado = -resultado;
    }
    // si son iguales, desempato por id para que el orden no cambie al azar
    if (resultado === 0) {
      resultado = a.id - b.id;
    }
    return resultado;
  });

  return copia;
}