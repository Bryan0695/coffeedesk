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
                 stock_minimo = ?,
                 activo = ?
             WHERE id = ?",
            [
                $insumo->getNombre(),
                $insumo->getUnidad(),
                $insumo->getStock(),
                $insumo->getStockMinimo(),
                $insumo->getActivo(),
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

    public function existeInsumo(
        string $nombre
    ): bool {
        $fila = consultar_uno(
            "SELECT id
         FROM insumos
         WHERE nombre = ?
         LIMIT 1",
            [$nombre]
        );

        return $fila !== null;
    }
}
