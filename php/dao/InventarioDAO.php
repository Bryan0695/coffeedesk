<?php

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../models/Insumo.php';

class InventarioDAO
{
    public function listar()
    {
        return consultar(
            "SELECT
                id,
                nombre,
                unidad,
                stock,
                stock_minimo,
                activo
            FROM insumos
            WHERE activo = 1
            ORDER BY nombre"
        );
    }

    public function obtenerPorId($id)
    {
        return consultar_uno(
            "SELECT
                id,
                nombre,
                unidad,
                stock,
                stock_minimo,
                activo
            FROM insumos
            WHERE id = ?",
            [$id]
        );
    }

    public function crear(Insumo $insumo)
    {
        return insertar(
            "INSERT INTO insumos
            (
                nombre,
                unidad,
                stock,
                stock_minimo,
                activo
            )
            VALUES (?, ?, ?, ?, ?)",
            [
                $insumo->getNombre(),
                $insumo->getUnidad(),
                $insumo->getStock(),
                $insumo->getStockMinimo(),
                $insumo->getActivo()
            ]
        );
    }

    public function actualizar(Insumo $insumo)
    {
        return ejecutar(
            "UPDATE insumos
             SET nombre = ?,
                 unidad = ?,
                 stock = ?,
                 stock_minimo = ?
             WHERE id = ?
               AND activo = 1",
            [
                $insumo->getNombre(),
                $insumo->getUnidad(),
                $insumo->getStock(),
                $insumo->getStockMinimo(),
                $insumo->getId()
            ]
        );
    }

    public function eliminar($id)
    {
        return ejecutar(
            "UPDATE insumos
             SET activo = 0
             WHERE id = ?",
            [$id]
        );
    }

    public function obtenerStockBajo()
    {
        return consultar(
            "SELECT *
             FROM insumos
             WHERE activo = 1
               AND stock <= stock_minimo
             ORDER BY stock ASC"
        );
    }

    public function descontarStock(
        int $productoId,
        int $cantidadVendida
    ): void {
        $insumos = consultar(
            "SELECT
            insumo_id,
            cantidad
         FROM producto_insumo
         WHERE producto_id = ?",
            [$productoId]
        );

        foreach ($insumos as $insumo) {

            $cantidadADescontar =
                (float)$insumo['cantidad']
                * $cantidadVendida;

            $afectadas = ejecutar(
                "UPDATE insumos
             SET stock = stock - ?
             WHERE id = ?
             AND stock >= ?",
                [
                    $cantidadADescontar,
                    $insumo['insumo_id'],
                    $cantidadADescontar
                ]
            );

            if ($afectadas !== 1) {

                throw new RuntimeException(
                    'Stock insuficiente para completar el pedido.'
                );
            }
        }
    }
    /** Inverso de descontarStock(): devuelve los insumos de un producto cuando se anula su pedido. */
    public function reponerStock(
        int $productoId,
        int $cantidad
    ): void {
        $insumos = consultar(
            "SELECT insumo_id, cantidad
             FROM producto_insumo
             WHERE producto_id = ?",
            [$productoId]
        );

        foreach ($insumos as $insumo) {
            ejecutar(
                "UPDATE insumos
                 SET stock = stock + ?
                 WHERE id = ?",
                [
                    (float) $insumo['cantidad'] * $cantidad,
                    $insumo['insumo_id']
                ]
            );
        }
    }

    public function contarAlertas(): int
    {
        $fila = consultar_uno(
            "SELECT COUNT(*) AS total
         FROM insumos
         WHERE activo = 1
         AND stock <= stock_minimo"
        );

        return (int)$fila['total'];
    }

    /** Insumo con ese nombre (activo o eliminado) o null; sirve para detectar repetidos y reactivar. */
    public function obtenerPorNombre(string $nombre, int $excluirId = 0): ?array
    {
        return consultar_uno(
            "SELECT id, activo
             FROM insumos
             WHERE nombre = ?
               AND id <> ?
             LIMIT 1",
            [$nombre, $excluirId]
        );
    }

    /** Reactiva un insumo eliminado con los datos nuevos. */
    public function reactivar(int $id, Insumo $insumo): void
    {
        ejecutar(
            "UPDATE insumos
             SET nombre = ?, unidad = ?, stock = ?, stock_minimo = ?, activo = 1
             WHERE id = ?",
            [
                $insumo->getNombre(),
                $insumo->getUnidad(),
                $insumo->getStock(),
                $insumo->getStockMinimo(),
                $id
            ]
        );
    }

    /** Nombres de los productos activos cuya receta usa el insumo. */
    public function productosQueLoUsan(int $insumoId): array
    {
        return array_column(consultar(
            "SELECT p.nombre
             FROM producto_insumo AS pi
             INNER JOIN productos AS p ON p.id = pi.producto_id
             WHERE pi.insumo_id = ?
               AND p.activo = 1
             ORDER BY p.nombre",
            [$insumoId]
        ), 'nombre');
    }
}
