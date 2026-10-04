<?php
/**
 * Acceso a datos de los productos del menú.
 * Los precios entran y salen como texto DECIMAL ("2.50"): la conversión a
 * centavos la hacen las páginas con php/comun/dinero.php.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/excepciones.php';

class ProductoDAO
{
    /** Productos activos de categorías activas, con su categoría y cuántos insumos tiene su receta. */
    public function listarActivos(): array
    {
        return consultar(
            'SELECT p.id, p.nombre, p.categoria_id, c.nombre AS categoria, p.precio, p.disponible,
                    (SELECT COUNT(*) FROM producto_insumo AS pi WHERE pi.producto_id = p.id) AS insumos_receta
             FROM productos AS p
             INNER JOIN categorias AS c ON c.id = p.categoria_id
             WHERE p.activo = 1
               AND c.activo = 1
             ORDER BY c.nombre ASC, p.nombre ASC'
        );
    }

    /** Productos que se pueden pedir: activos, disponibles y de una categoría activa. */
    public function listarParaVenta(): array
    {
        return consultar(
            'SELECT p.id, p.nombre, p.precio, p.categoria_id, c.nombre AS categoria
             FROM productos AS p
             INNER JOIN categorias AS c ON c.id = p.categoria_id
             WHERE p.disponible = 1
               AND p.activo = 1
               AND c.activo = 1
             ORDER BY c.nombre ASC, p.nombre ASC'
        );
    }

    /**
     * Nombre y precio de los productos del pedido. Se llama dentro de la
     * transacción del pedido: FOR UPDATE impide que se marquen como agotados o
     * se eliminen hasta que el pedido se guarde (B6). Si alguno ya no se puede
     * vender lanza ProductoNoDisponibleException.
     *
     * @param int[] $ids
     */
    public function obtenerParaVenta(array $ids): array
    {
        $marcadores = implode(', ', array_fill(0, count($ids), '?'));
        $productos = consultar(
            'SELECT p.id, p.nombre, p.precio
             FROM productos AS p
             INNER JOIN categorias AS c ON c.id = p.categoria_id
             WHERE p.id IN (' . $marcadores . ')
               AND p.disponible = 1
               AND p.activo = 1
               AND c.activo = 1
             FOR UPDATE',
            array_values($ids)
        );
        if (count($productos) !== count($ids)) {
            throw new ProductoNoDisponibleException('Producto no disponible.');
        }
        return $productos;
    }

    /** Producto (activo o eliminado) o null. */
    public function obtenerPorId(int $id): ?array
    {
        return consultar_uno(
            'SELECT p.id, p.nombre, p.activo, c.nombre AS categoria
             FROM productos AS p
             INNER JOIN categorias AS c ON c.id = p.categoria_id
             WHERE p.id = ?',
            [$id]
        );
    }

    /** Producto activo o null. */
    public function obtenerActivo(int $id): ?array
    {
        $producto = $this->obtenerPorId($id);
        return $producto !== null && (int) $producto['activo'] === 1 ? $producto : null;
    }

    /** Otro producto (activo o eliminado) con ese nombre, o null. */
    public function buscarPorNombre(string $nombre, int $excluirId = 0): ?array
    {
        return consultar_uno(
            'SELECT id, nombre, activo
             FROM productos
             WHERE nombre = ?
               AND id <> ?
             LIMIT 1',
            [$nombre, $excluirId]
        );
    }

    /** Nombres de los productos activos de una categoría. */
    public function nombresActivosDeCategoria(int $categoriaId): array
    {
        return array_column(consultar(
            'SELECT nombre
             FROM productos
             WHERE categoria_id = ?
               AND activo = 1
             ORDER BY nombre',
            [$categoriaId]
        ), 'nombre');
    }

    public function crear(int $categoriaId, string $nombre, string $precio, bool $disponible): int
    {
        return insertar(
            'INSERT INTO productos (categoria_id, nombre, precio, disponible, activo)
             VALUES (?, ?, ?, ?, 1)',
            [$categoriaId, $nombre, $precio, $disponible]
        );
    }

    public function actualizar(int $id, int $categoriaId, string $nombre, string $precio, bool $disponible): void
    {
        ejecutar(
            'UPDATE productos
             SET nombre = ?, categoria_id = ?, precio = ?, disponible = ?
             WHERE id = ?
               AND activo = 1',
            [$nombre, $categoriaId, $precio, $disponible, $id]
        );
    }

    /** Vuelve a activar un producto eliminado, con los datos nuevos. */
    public function reactivar(int $id, int $categoriaId, string $nombre, string $precio, bool $disponible): void
    {
        ejecutar(
            'UPDATE productos
             SET nombre = ?, categoria_id = ?, precio = ?, disponible = ?, activo = 1
             WHERE id = ?',
            [$nombre, $categoriaId, $precio, $disponible, $id]
        );
    }

    /**
     * Baja lógica (activo = 0): el registro se conserva porque lo referencian
     * los pedidos anteriores (pedido_detalle).
     */
    public function eliminar(int $id): int
    {
        return ejecutar(
            'UPDATE productos
             SET activo = 0, disponible = 0
             WHERE id = ?
               AND activo = 1',
            [$id]
        );
    }
}
