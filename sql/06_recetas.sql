-- =========================================================
-- CoffeeDesk — versión 6 del esquema: receta producto-insumo
-- Responsable de integración: Gabo + Jeremy
--
-- Requiere 03_menu.sql y 05_inventario.sql.
-- Permite calcular qué insumos debe descontar cada producto vendido.
-- =========================================================

SET NAMES utf8mb4;

INSERT INTO esquema_version (version, descripcion)
VALUES (6, 'Relación de productos con insumos para descuento de stock');

CREATE TABLE producto_insumo (
    producto_id INT UNSIGNED NOT NULL,
    insumo_id   INT UNSIGNED NOT NULL,
    cantidad    DECIMAL(12,3) NOT NULL,
    PRIMARY KEY (producto_id, insumo_id),
    KEY idx_producto_insumo_insumo (insumo_id),
    CONSTRAINT fk_producto_insumo_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_producto_insumo_insumo
        FOREIGN KEY (insumo_id) REFERENCES insumos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_producto_insumo_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
