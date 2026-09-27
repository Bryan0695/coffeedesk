<?php

require_once __DIR__ . '/../models/Insumo.php';

class InventarioDAO
{
    public function crear($insumo) {}

    public function listar() {}

    public function obtenerPorId($id) {}

    public function actualizar($insumo) {}

    public function eliminar($id) {}

    public function buscarPorNombre($texto) {}

    public function obtenerStockBajo() {}

    public function descontarStock($productoId, $cantidad) {}
    
    public function filtrarPorCategoria($categoriaId) {}
}
