<?php
/**
 * Acceso a datos del inventario: insumos y movimientos de stock.
 *
 * Responsable: Jeremy
 */

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../models/Insumo.php';
require_once __DIR__ . '/excepciones.php';

class InventarioDAO
{
    /** Insumos activos por nombre. La columna "bajo" (0/1) marca stock ≤ mínimo. */
    public function listar(): array
    {
        return consultar(
            'SELECT id, nombre, unidad, stock, stock_minimo,
                    stock <= stock_minimo AS bajo
             FROM insumos
             WHERE activo = 1
             ORDER BY nombre'
        );
    }

    public function obtenerPorId(int $id): ?array
    {
        return consultar_uno(
            'SELECT id, nombre, unidad, stock, stock_minimo, activo
             FROM insumos
             WHERE id = ?',
            [$id]
        );
    }

    /** Insumo con ese nombre (activo o eliminado) o null; sirve para detectar repetidos y reactivar. */
    public function obtenerPorNombre(string $nombre, int $excluirId = 0): ?array
    {
        return consultar_uno(
            'SELECT id, activo
             FROM insumos
             WHERE nombre = ?
               AND id <> ?
             LIMIT 1',
            [$nombre, $excluirId]
        );
    }

    public function crear(Insumo $insumo): int
    {
        return insertar(
            'INSERT INTO insumos (nombre, unidad, stock, stock_minimo, activo)
             VALUES (?, ?, ?, ?, 1)',
            [$insumo->nombre, $insumo->unidad, $insumo->stock, $insumo->stockMinimo]
        );
    }

    /**
     * Edita el insumo. El stock no se sobrescribe: se suma la diferencia entre el
     * stock que escribió el administrador y el que vio al abrir el formulario
     * ($stockOriginal). Así no se pierden las ventas o anulaciones que ocurran
     * mientras edita. Devuelve false si con esa diferencia el stock quedaría negativo.
     */
    public function actualizar(Insumo $insumo, string $stockOriginal): bool
    {
        // Fragmento fijo: solo contiene marcadores "?", nunca datos del usuario
        $diferencia = '(CAST(? AS DECIMAL(12,3)) - CAST(? AS DECIMAL(12,3)))';

        $afectadas = ejecutar(
            "UPDATE insumos
             SET nombre = ?,
                 unidad = ?,
                 stock = stock + $diferencia,
                 stock_minimo = ?
             WHERE id = ?
               AND activo = 1
               AND stock + $diferencia >= 0",
            [
                $insumo->nombre,
                $insumo->unidad,
                $insumo->stock, $stockOriginal,
                $insumo->stockMinimo,
                $insumo->id,
                $insumo->stock, $stockOriginal,
            ]
        );

        if ($afectadas === 1) {
            return true;
        }

        // 0 filas también significa "nada cambió" (MySQL no cuenta las filas
        // que quedan igual): solo es error si el stock quedaría negativo.
        return consultar_uno(
            "SELECT 1
             FROM insumos
             WHERE id = ?
               AND stock + $diferencia < 0",
            [$insumo->id, $insumo->stock, $stockOriginal]
        ) === null;
    }

    /** Reactiva un insumo eliminado con los datos nuevos. */
    public function reactivar(int $id, Insumo $insumo): void
    {
        ejecutar(
            'UPDATE insumos
             SET nombre = ?, unidad = ?, stock = ?, stock_minimo = ?, activo = 1
             WHERE id = ?',
            [$insumo->nombre, $insumo->unidad, $insumo->stock, $insumo->stockMinimo, $id]
        );
    }

    /** Baja lógica. Devuelve las filas afectadas (0 si ya estaba eliminado). */
    public function eliminar(int $id): int
    {
        return ejecutar(
            'UPDATE insumos
             SET activo = 0
             WHERE id = ?
               AND activo = 1',
            [$id]
        );
    }

    /** Nombres de los productos activos cuya receta usa el insumo. */
    public function productosQueLoUsan(int $insumoId): array
    {
        return array_column(consultar(
            'SELECT p.nombre
             FROM producto_insumo AS pi
             INNER JOIN productos AS p ON p.id = pi.producto_id
             WHERE pi.insumo_id = ?
               AND p.activo = 1
             ORDER BY p.nombre',
            [$insumoId]
        ), 'nombre');
    }

    /**
     * Descuenta los insumos de la receta del producto y anota en pedido_insumo
     * cuánto se descontó, para devolver exactamente eso si el pedido se anula.
     * Si algún insumo no alcanza lanza StockInsuficienteException (la
     * transacción del pedido hace rollback). Debe llamarse dentro de transaccion().
     *
     * Las cantidades (receta × unidades) las calcula MySQL en DECIMAL, sin float.
     * FOR UPDATE bloquea la receta hasta el final de la transacción: si el
     * administrador la cambia a la vez, se descuenta y se anota la misma cantidad.
     */
    public function descontarStock(int $pedidoId, int $productoId, int $cantidadVendida): void
    {
        $insumos = consultar(
            'SELECT pi.insumo_id, i.nombre, pi.cantidad * ? AS total
             FROM producto_insumo AS pi
             INNER JOIN insumos AS i ON i.id = pi.insumo_id
             WHERE pi.producto_id = ?
             ORDER BY pi.insumo_id
             FOR UPDATE',
            [$cantidadVendida, $productoId]
        );

        foreach ($insumos as $insumo) {
            $insumoId = (int) $insumo['insumo_id'];
            $total    = (string) $insumo['total'];

            $afectadas = ejecutar(
                'UPDATE insumos
                 SET stock = stock - ?
                 WHERE id = ?
                   AND stock >= ?',
                [$total, $insumoId, $total]
            );
            if ($afectadas !== 1) {
                throw new StockInsuficienteException($insumo['nombre']);
            }

            // Dos productos del mismo pedido pueden usar el mismo insumo: se suman
            ejecutar(
                'INSERT INTO pedido_insumo (pedido_id, insumo_id, cantidad)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE cantidad = cantidad + ?',
                [$pedidoId, $insumoId, $total, $total]
            );
        }
    }

    /** Inverso de descontarStock(): devuelve lo que se descontó al registrar el pedido. */
    public function reponerStock(int $pedidoId): void
    {
        ejecutar(
            'UPDATE insumos AS i
             INNER JOIN pedido_insumo AS pi ON pi.insumo_id = i.id
             SET i.stock = i.stock + pi.cantidad
             WHERE pi.pedido_id = ?',
            [$pedidoId]
        );
    }
}
