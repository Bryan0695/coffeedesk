<?php
/**
 * Baja lógica de productos del menú (solo administrador).
 * No borra el registro (activo = 0): lo referencian los pedidos anteriores.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/ProductoDAO.php';

requiere_rol(ROL_ADMIN);
exigir_post_con_csrf('menu.php');

$id = post_id('id');
if ($id === null) {
    fallar('El identificador del producto no es válido.', 'menu.php');
}

$productos = new ProductoDAO();

$producto = $productos->obtenerPorId($id);
if ($producto === null) {
    fallar('El producto no existe o ya fue eliminado.', 'menu.php');
}
if ((int) $producto['activo'] === 0) {
    terminar('aviso', 'El producto ya se encuentra eliminado.', 'menu.php');
}

$productos->eliminar($id);

terminar('exito', 'El producto "' . $producto['nombre'] . '" se eliminó correctamente.', 'menu.php');
