<?php
/**
 * Insumo del inventario tal como llega del formulario.
 * Las cantidades van como texto ("2.5") para que MySQL las convierta a
 * DECIMAL(12,3) sin pasar por float.
 *
 * Responsable: Jeremy
 */
class Insumo
{
    public function __construct(
        public string $nombre,
        public string $unidad,
        public string $stock,
        public string $stockMinimo,
        public ?int $id = null
    ) {
    }
}
