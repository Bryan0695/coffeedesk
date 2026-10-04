<?php
/**
 * Acceso a datos de las recetas: cuánto de cada insumo se descuenta del
 * inventario por cada unidad vendida de un producto (tabla producto_insumo).
 *
 * Responsable: Bryan Gallegos
 */

require_once __DIR__ . '/../conexion.php';

class RecetaDAO
{
    /** Insumos de la receta del producto (también los eliminados del inventario, para avisar). */
    public function insumosDeProducto(int $productoId): array
    {
        return consultar(
            'SELECT i.id, i.nombre, i.unidad, i.activo, pi.cantidad
             FROM producto_insumo AS pi
             INNER JOIN insumos AS i ON i.id = pi.insumo_id
             WHERE pi.producto_id = ?
             ORDER BY i.nombre',
            [$productoId]
        );
    }

    /** Agrega el insumo a la receta o, si ya estaba, reemplaza su cantidad. */
    public function guardar(int $productoId, int $insumoId, string $cantidad): void
    {
        ejecutar(
            'INSERT INTO producto_insumo (producto_id, insumo_id, cantidad)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE cantidad = ?',
            [$productoId, $insumoId, $cantidad, $cantidad]
        );
    }

    /** Quita el insumo de la receta. Devuelve false si no estaba. */
    public function quitar(int $productoId, int $insumoId): bool
    {
        return ejecutar(
            'DELETE FROM producto_insumo
             WHERE producto_id = ?
               AND insumo_id = ?',
            [$productoId, $insumoId]
        ) === 1;
    }
}
