-- =========================================================
-- CoffeeDesk — versión 8
-- Insumos descontados en cada pedido
-- Al anular un pedido se devuelve exactamente lo que se descontó al venderlo,
-- aunque la receta del producto haya cambiado después (B5 de la revisión).
-- Responsable: Bryan Gallegos
-- =========================================================

SET NAMES utf8mb4;

INSERT INTO esquema_version (version, descripcion)
VALUES (8, 'Insumos descontados por pedido');

CREATE TABLE pedido_insumo (
    pedido_id INT UNSIGNED  NOT NULL,
    insumo_id INT UNSIGNED  NOT NULL,
    cantidad  DECIMAL(12,3) NOT NULL,
    PRIMARY KEY (pedido_id, insumo_id),
    KEY idx_pedido_insumo_insumo (insumo_id),
    CONSTRAINT fk_pedido_insumo_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pedido_insumo_insumo
        FOREIGN KEY (insumo_id) REFERENCES insumos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_pedido_insumo_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pedidos pendientes registrados antes de esta versión: se completa con la
-- receta actual (es lo mismo que se habría devuelto al anularlos).
INSERT INTO pedido_insumo (pedido_id, insumo_id, cantidad)
SELECT d.pedido_id, pi.insumo_id, SUM(pi.cantidad * d.cantidad)
FROM pedidos AS p
INNER JOIN pedido_detalle AS d ON d.pedido_id = p.id
INNER JOIN producto_insumo AS pi ON pi.producto_id = d.producto_id
WHERE p.estado = 'pendiente'
GROUP BY d.pedido_id, pi.insumo_id;
