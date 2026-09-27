-- =========================================================
-- CoffeeDesk — versión 4 del esquema: pedidos y detalle
-- Responsable: Gabo
--
-- Requiere 01_usuarios_roles.sql y 03_menu.sql.
-- El precio se copia al detalle para conservar el valor histórico del pedido.
-- =========================================================

SET NAMES utf8mb4;

INSERT INTO esquema_version (version, descripcion)
VALUES (4, 'Pedidos y detalle de pedidos');

CREATE TABLE pedidos (
    id             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    mesa           TINYINT UNSIGNED NOT NULL,
    cliente        VARCHAR(60)      NULL,
    registrado_por INT UNSIGNED     NOT NULL,
    estado         VARCHAR(15)      NOT NULL DEFAULT 'pendiente',
    total          DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    creado_en      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pedidos_fecha (creado_en),
    KEY idx_pedidos_usuario (registrado_por),
    KEY idx_pedidos_estado_fecha (estado, creado_en),
    CONSTRAINT fk_pedidos_usuario
        FOREIGN KEY (registrado_por) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_pedidos_mesa CHECK (mesa BETWEEN 1 AND 99),
    CONSTRAINT chk_pedidos_estado CHECK (estado IN ('pendiente', 'entregado', 'anulado')),
    CONSTRAINT chk_pedidos_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedido_detalle (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id       INT UNSIGNED    NOT NULL,
    producto_id     INT UNSIGNED    NOT NULL,
    cantidad        SMALLINT UNSIGNED NOT NULL,
    precio_unitario DECIMAL(10,2)   NOT NULL,
    subtotal        DECIMAL(10,2)   NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pedido_producto (pedido_id, producto_id),
    KEY idx_detalle_producto (producto_id),
    CONSTRAINT fk_detalle_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detalle_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_detalle_cantidad CHECK (cantidad BETWEEN 1 AND 999),
    CONSTRAINT chk_detalle_precio CHECK (precio_unitario > 0),
    CONSTRAINT chk_detalle_subtotal CHECK (subtotal > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
