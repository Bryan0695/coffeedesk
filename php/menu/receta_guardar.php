<?php
/**
 * Agrega un insumo a la receta de un producto o, si ya estaba, reemplaza su cantidad.
 *
 * POST: csrf, producto_id, insumo_id, cantidad (por unidad vendida, hasta 3 decimales)
 *
 * Responsable: Bryan Gallegos
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/ProductoDAO.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';
require_once __DIR__ . '/../dao/RecetaDAO.php';

requiere_rol(ROL_ADMIN);
exigir_post_con_csrf('menu.php');

$productoId = post_id('producto_id');
$insumoId   = post_id('insumo_id');
$cantidad   = trim(post_texto('cantidad'));

if ($productoId === null || (new ProductoDAO())->obtenerActivo($productoId) === null) {
    fallar('El producto no existe o fue eliminado.', 'menu.php');
}

$volver = 'recetas.php?producto=' . $productoId;

$insumo = $insumoId !== null ? (new InventarioDAO())->obtenerPorId($insumoId) : null;
if ($insumo === null || (int) $insumo['activo'] !== 1) {
    fallar('Elige un insumo del inventario.', $volver);
}
if (!cantidad_positiva($cantidad)) {
    fallar('La cantidad debe ser mayor que 0, hasta 99999 y con máximo 3 decimales.', $volver);
}

(new RecetaDAO())->guardar($productoId, $insumoId, $cantidad);

terminar('exito', '"' . $insumo['nombre'] . '" se guardó en la receta.', $volver);
