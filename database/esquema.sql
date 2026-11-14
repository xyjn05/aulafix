-- esquema.sql · Tablas de AulaFix
-- 

DROP TABLE IF EXISTS historial;
DROP TABLE IF EXISTS incidencias;
DROP TABLE IF EXISTS equipos;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(80)  NOT NULL,
    email      VARCHAR(190) NOT NULL,
    clave_hash VARCHAR(255) NOT NULL,
    rol        ENUM('alumno','profesor') NOT NULL DEFAULT 'alumno',
    creado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE equipos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    codigo      VARCHAR(20)  NOT NULL,
    descripcion VARCHAR(120) NOT NULL,
    UNIQUE KEY uq_equipos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE incidencias (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    equipo_id      INT UNSIGNED NOT NULL,
    usuario_id     INT UNSIGNED NOT NULL,
    categoria      ENUM('hardware','software','red','otro') NOT NULL,
    prioridad      ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
    descripcion    VARCHAR(500) NOT NULL,
    estado         ENUM('abierta','en_curso','resuelta') NOT NULL DEFAULT 'abierta',
    creada_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inc_equipo  FOREIGN KEY (equipo_id)  REFERENCES equipos(id),
    CONSTRAINT fk_inc_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE historial (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    incidencia_id   INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NOT NULL,
    estado_anterior ENUM('abierta','en_curso','resuelta') NOT NULL,
    estado_nuevo    ENUM('abierta','en_curso','resuelta') NOT NULL,
    comentario      VARCHAR(300) NOT NULL,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Al borrar una incidencia se borra también su historial.
    CONSTRAINT fk_hist_inc     FOREIGN KEY (incidencia_id) REFERENCES incidencias(id) ON DELETE CASCADE,
    CONSTRAINT fk_hist_usuario FOREIGN KEY (usuario_id)    REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;