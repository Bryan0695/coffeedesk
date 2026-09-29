-- =========================================================
-- CoffeeDesk — versión 7
-- Estado lógico de productos
-- Responsable: Gabo
-- =========================================================

SET NAMES utf8mb4;

INSERT INTO esquema_version (version, descripcion)
VALUES (7, 'Estado activo para eliminación lógica de productos');

ALTER TABLE productos
    ADD COLUMN activo TINYINT UNSIGNED NOT NULL DEFAULT 1
    AFTER disponible;

ALTER TABLE productos
    ADD KEY idx_productos_activo (activo);

ALTER TABLE productos
    ADD CONSTRAINT chk_productos_activo
    CHECK (activo IN (0, 1));