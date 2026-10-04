<?php
/**
 * Reactiva una categoría eliminada (activo = 0 → 1) desde el botón de la tabla.
 * (Crear una categoría con el nombre de una eliminada también la reactiva:
 * eso lo hace categoria_guardar.php.)
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/CategoriaDAO.php';

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
if ((int) $categoria['activo'] === 1) {
    terminar('aviso', 'La categoría ya se encuentra activa.', 'categorias.php');
}

$categorias->reactivar($id);

terminar('exito', 'La categoría "' . $categoria['nombre'] . '" fue reactivada correctamente.', 'categorias.php');
