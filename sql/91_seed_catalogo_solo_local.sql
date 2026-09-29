-- #########################################################################
-- ##  CoffeeDesk — DATOS DE PRUEBA SOLO PARA XAMPP / DESARROLLO LOCAL   ##
-- ##  NO IMPORTAR EN PRODUCCIÓN.                                         ##
-- #########################################################################
-- Requiere 03_menu.sql, 05_inventario.sql y 06_recetas.sql.

SET NAMES utf8mb4;

INSERT INTO categorias (id, nombre) VALUES
    (1, 'Bebidas calientes'),
    (2, 'Bebidas frías'),
    (3, 'Repostería'),
    (4, 'Aperitivos');

INSERT INTO productos (id, categoria_id, nombre, precio, disponible) VALUES
    (1, 1, 'Café americano',      1.50, 1),
    (2, 1, 'Capuchino',           2.50, 1),
    (3, 2, 'Frappé de caramelo',  3.75, 0),
    (4, 2, 'Limonada',            2.00, 1),
    (5, 3, 'Cheesecake de mora',  3.25, 1),
    (6, 4, 'Sánduche de jamón',   3.00, 1);

INSERT INTO insumos (id, nombre, unidad, stock, stock_minimo) VALUES
    (1, 'Café en grano',  'g',        8000.000, 3000.000),
    (2, 'Leche entera',   'ml',       4000.000, 10000.000),
    (3, 'Azúcar',         'g',       12000.000, 2000.000),
    (4, 'Vasos de 12 oz', 'unidades',   40.000,   50.000),
    (5, 'Limones',        'unidades',   60.000,   20.000),
    (6, 'Jarabe caramelo','ml',       1500.000,  300.000),
    (7, 'Cheesecake mora','unidades',   12.000,    4.000),
    (8, 'Pan',            'unidades',   20.000,    6.000),
    (9, 'Jamón',          'g',        1000.000,  250.000),
    (10,'Queso',          'g',        1000.000,  250.000);

-- Cantidad de cada insumo consumida por UNA unidad del producto.
INSERT INTO producto_insumo (producto_id, insumo_id, cantidad) VALUES
    (1, 1, 18.000),
    (1, 3,  5.000),
    (1, 4,  1.000),

    (2, 1, 18.000),
    (2, 2, 180.000),
    (2, 3,  5.000),
    (2, 4,  1.000),

    (3, 1, 18.000),
    (3, 2, 180.000),
    (3, 6, 25.000),
    (3, 4,  1.000),

    (4, 5,  2.000),
    (4, 3, 15.000),
    (4, 4,  1.000),

    (5, 7,  1.000),

    (6, 8,  2.000),
    (6, 9, 60.000),
    (6,10, 40.000);
