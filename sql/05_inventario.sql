-- =========================================================
-- CoffeeDesk — versión 5 del esquema: inventario de insumos
-- Responsable funcional: Jeremy · Integración SQL: Gabo
--
-- Requiere 01_usuarios_roles.sql.
-- Las cantidades son DECIMAL para admitir kg, g, litros y ml.
-- =========================================================

SET NAMES utf8mb4;

INSERT INTO esquema_version (version, descripcion)
VALUES (5, 'Inventario de insumos');

CREATE TABLE insumos (
    id             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    nombre         VARCHAR(80)      NOT NULL,
    unidad         VARCHAR(20)      NOT NULL,
    stock          DECIMAL(12,3)    NOT NULL DEFAULT 0.000,
    stock_minimo   DECIMAL(12,3)    NOT NULL DEFAULT 0.000,
    activo         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    creado_en      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_insumos_nombre (nombre),
    KEY idx_insumos_activo_nombre (activo, nombre),
    KEY idx_insumos_stock (activo, stock, stock_minimo),
    CONSTRAINT chk_insumos_unidad CHECK (unidad IN ('unidades', 'kg', 'g', 'litros', 'ml')),
    CONSTRAINT chk_insumos_stock CHECK (stock >= 0),
    CONSTRAINT chk_insumos_stock_minimo CHECK (stock_minimo >= 0),
    CONSTRAINT chk_insumos_activo CHECK (activo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
