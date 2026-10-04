<?php
/**
 * Baja lógica de insumos (activo = 0).
 * Se bloquea si el insumo está en la receta de algún producto activo: descontarStock()
 * lo seguiría restando aunque ya no se viera en el inventario.
 *
 * Responsable: Jeremy
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';

requiere_rol(ROL_ADMIN);
exigir_post_con_csrf('inventario.php');

$id = post_id('id');
if ($id === null) {
    fallar('El identificador del insumo no es válido.', 'inventario.php');
}

$dao = new InventarioDAO();

$insumo = $dao->obtenerPorId($id);
if ($insumo === null || (int) $insumo['activo'] !== 1) {
    fallar('El insumo no existe o ya fue eliminado.', 'inventario.php');
}

$productos = $dao->productosQueLoUsan($id);
if ($productos !== []) {
    fallar('No se puede eliminar "' . $insumo['nombre'] . '": lo usan las recetas de ' . implode(', ', $productos) . '.', 'inventario.php');
}

$dao->eliminar($id);

terminar('exito', 'Insumo eliminado.', 'inventario.php');
