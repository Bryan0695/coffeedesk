<?php
/**
 * Baja lógica de categorías (activo = 0), solo si no tienen productos activos.
 * Con productos activos desaparecerían del menú y ya no se podrían editar:
 * primero hay que moverlos a otra categoría o eliminarlos.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/CategoriaDAO.php';
require_once __DIR__ . '/../dao/ProductoDAO.php';

requiere_rol(ROL_ADMIN);
exigir_post_con_csrf('categorias.php');

$id = post_id('id');
if ($id === null) {
    fallar('El identificador de la categoría no es válido.', 'categorias.php');
}

$categorias = new CategoriaDAO();

$categoria = $categorias->obtenerPorId($id);
if ($categoria === null) {
    fallar('La categoría no existe.', 'categorias.php');
}
if ((int) $categoria['activo'] === 0) {
    terminar('aviso', 'La categoría ya se encuentra eliminada.', 'categorias.php');
}

$productosActivos = (new ProductoDAO())->nombresActivosDeCategoria($id);
if ($productosActivos) {
    fallar(
        'No se puede eliminar "' . $categoria['nombre'] . '" porque tiene ' . count($productosActivos)
        . ' producto(s) activo(s): ' . implode(', ', $productosActivos)
        . '. Muévelos a otra categoría o elimínalos primero.',
        'categorias.php'
    );
}

if (!$categorias->eliminarSiNoTieneProductos($id)) {
    fallar('La categoría no pudo eliminarse porque cambió mientras tanto. Recarga e inténtalo de nuevo.', 'categorias.php');
}

terminar('exito', 'La categoría "' . $categoria['nombre'] . '" fue eliminada.', 'categorias.php');
