-- =========================================================
-- CoffeeDesk — versión 3 del esquema: categorías y productos
-- Responsable: Gabo
--
-- Requiere 01_usuarios_roles.sql y 02_intentos_login.sql.
-- Importar en local y hosting. No contiene datos de prueba.
-- =========================================================

SET NAMES utf8mb4;

INSERT INTO esquema_version (version, descripcion)
VALUES (3, 'Categorías y productos del menú');

CREATE TABLE categorias (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(60)       NOT NULL,
    descripcion VARCHAR(180)      NULL,
    activo      TINYINT UNSIGNED  NOT NULL DEFAULT 1,
    creado_en   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_nombre (nombre),
    KEY idx_categorias_activo_nombre (activo, nombre),
    CONSTRAINT chk_categorias_activo CHECK (activo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE productos (
    id             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    categoria_id   SMALLINT UNSIGNED NOT NULL,
    nombre         VARCHAR(80)       NOT NULL,
    descripcion    VARCHAR(255)      NULL,
    precio         DECIMAL(10,2)     NOT NULL,
    disponible     TINYINT UNSIGNED  NOT NULL DEFAULT 1,
    creado_en      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_productos_nombre (nombre),
    KEY idx_productos_categoria (categoria_id),
    KEY idx_productos_lista (disponible, nombre),
    CONSTRAINT fk_productos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_productos_precio CHECK (precio > 0),
    CONSTRAINT chk_productos_disponible CHECK (disponible IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
