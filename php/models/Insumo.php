<?php

class Insumo
{
    private $id;
    private $nombre;
    private $stock;
    private $stockMinimo;

    public function __construct(
        $id = null,
        $nombre = "",
        $stock = 0,
        $stockMinimo = 0
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->stock = $stock;
        $this->stockMinimo = $stockMinimo;
    }
}