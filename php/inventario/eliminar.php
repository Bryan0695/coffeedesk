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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido(post_texto('csrf'))) {
    mensaje_flash('error', 'Solicitud no válida.');
    redirigir('inventario.php');
}

$id = post_entero('id');

if ($id === null || $id <= 0) {
    mensaje_flash('error', 'ID inválido.');
    redirigir('inventario.php');
}

$dao = new InventarioDAO();

$insumo = $dao->obtenerPorId($id);
if ($insumo === null || (int) $insumo['activo'] !== 1) {
    mensaje_flash('error', 'El insumo no existe o ya fue eliminado.');
    redirigir('inventario.php');
}

$productos = $dao->productosQueLoUsan($id);
if ($productos !== []) {
    mensaje_flash(
        'error',
        'No se puede eliminar "' . $insumo['nombre'] . '": lo usan las recetas de ' . implode(', ', $productos) . '.'
    );
    redirigir('inventario.php');
}

$dao->eliminar($id);

mensaje_flash('exito', 'Insumo eliminado.');
redirigir('inventario.php');
