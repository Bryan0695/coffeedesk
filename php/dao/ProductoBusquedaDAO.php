<?php

require_once __DIR__ . '/../conexion.php';

class ProductoBusquedaDAO
{
    public function buscarPorNombre($texto)
    {
        return consultar(
            "SELECT
                p.id,
                p.nombre,
                p.descripcion,
                p.precio,
                c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c
                ON c.id = p.categoria_id
            WHERE p.disponible = 1
              AND c.activo = 1
              AND p.nombre LIKE ?
            ORDER BY p.nombre",
            ["%{$texto}%"]
        );
    }

    public function filtrarPorCategoria($categoriaId)
    {
        return consultar(
            "SELECT
                p.id,
                p.nombre,
                p.descripcion,
                p.precio,
                c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c
                ON c.id = p.categoria_id
            WHERE c.id = ?
              AND p.disponible = 1
              AND c.activo = 1
            ORDER BY p.nombre",
            [$categoriaId]
        );
    }
}
