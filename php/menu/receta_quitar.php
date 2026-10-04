<?php
/**
 * Quita un insumo de la receta de un producto.
 *
 * POST: csrf, producto_id, insumo_id
 *
 * Responsable: Bryan Gallegos
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/RecetaDAO.php';

requiere_rol(ROL_ADMIN);
exigir_post_con_csrf('menu.php');

$productoId = post_id('producto_id');
$insumoId   = post_id('insumo_id');

if ($productoId === null || $insumoId === null) {
    fallar('Los datos enviados no son válidos.', 'menu.php');
}

$volver = 'recetas.php?producto=' . $productoId;

if ((new RecetaDAO())->quitar($productoId, $insumoId)) {
    terminar('exito', 'El insumo se quitó de la receta.', $volver);
}
terminar('aviso', 'Ese insumo ya no estaba en la receta.', $volver);
