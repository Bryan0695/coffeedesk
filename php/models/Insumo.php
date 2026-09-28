<?php

class Insumo
{
    private $id;
    private $nombre;
    private $unidad;
    private $stock;
    private $stockMinimo;
    private $activo;

    public function getId()
    {
        return $this->id;
    }

    public function getNombre()
    {
        return $this->nombre;
    }

    public function getUnidad()
    {
        return $this->unidad;
    }

    public function getStock()
    {
        return $this->stock;
    }

    public function getStockMinimo()
    {
        return $this->stockMinimo;
    }

    public function getActivo()
    {
        return $this->activo;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    public function setUnidad($unidad)
    {
        $this->unidad = $unidad;
    }

    public function setStock($stock)
    {
        $this->stock = $stock;
    }

    public function setStockMinimo($stockMinimo)
    {
        $this->stockMinimo = $stockMinimo;
    }

    public function setActivo($activo)
    {
        $this->activo = $activo;
    }
}