<?php
/**
 * Errores de negocio que lanzan los DAO dentro de una transacción. Son clases
 * propias para no confundirlas con los errores de MySQL (mysqli_sql_exception
 * también es RuntimeException): el endpoint las captura y muestra un mensaje.
 */

/** Un insumo de la receta no alcanza para el pedido. */
class StockInsuficienteException extends RuntimeException
{
    private string $insumo;

    public function __construct(string $insumo)
    {
        parent::__construct('Stock insuficiente de ' . $insumo . '.');
        $this->insumo = $insumo;
    }

    public function getInsumo(): string
    {
        return $this->insumo;
    }
}

/** Un producto del pedido se agotó, se eliminó o su categoría se desactivó. */
class ProductoNoDisponibleException extends RuntimeException
{
}
