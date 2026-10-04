<?php
/**
 * Acceso a datos de los pedidos y su detalle.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../conexion.php';

class PedidoDAO
{
    /**
     * Pedidos de hoy y pendientes de días anteriores. Los pendientes viejos van
     * primero: si no aparecieran, nadie podría entregarlos ni anularlos y su
     * stock quedaría descontado para siempre. CURDATE() usa la zona horaria
     * que fija conectar().
     */
    public function listarDeHoyYPendientes(): array
    {
        return consultar(
            'SELECT id, mesa, cliente, total, estado, creado_en
             FROM pedidos
             WHERE (creado_en >= CURDATE() AND creado_en < CURDATE() + INTERVAL 1 DAY)
                OR estado = ?
             ORDER BY creado_en < CURDATE() DESC, creado_en DESC, id DESC',
            [ESTADO_PENDIENTE]
        );
    }

    public function obtenerPorId(int $id): ?array
    {
        return consultar_uno('SELECT id, estado FROM pedidos WHERE id = ?', [$id]);
    }

    /** Cabecera del pedido (estado pendiente). Devuelve el id nuevo. */
    public function crear(int $mesa, ?string $cliente, int $usuarioId, string $total): int
    {
        return insertar(
            'INSERT INTO pedidos (mesa, cliente, registrado_por, estado, total)
             VALUES (?, ?, ?, ?, ?)',
            [$mesa, $cliente, $usuarioId, ESTADO_PENDIENTE, $total]
        );
    }

    public function agregarLinea(int $pedidoId, int $productoId, int $cantidad, string $precio, string $subtotal): void
    {
        insertar(
            'INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
             VALUES (?, ?, ?, ?, ?)',
            [$pedidoId, $productoId, $cantidad, $precio, $subtotal]
        );
    }

    /**
     * Pasa un pedido pendiente a $nuevoEstado. El AND estado = pendiente evita
     * procesarlo dos veces (y devolver el stock dos veces) si dos personas lo
     * cambian a la vez. Devuelve false si ya no estaba pendiente.
     */
    public function cambiarEstado(int $id, string $nuevoEstado): bool
    {
        return ejecutar(
            'UPDATE pedidos
             SET estado = ?
             WHERE id = ?
               AND estado = ?',
            [$nuevoEstado, $id, ESTADO_PENDIENTE]
        ) === 1;
    }
}
