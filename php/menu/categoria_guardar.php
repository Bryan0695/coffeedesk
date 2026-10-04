<?php
/**
 * Crear y editar categorías (solo administrador).
 *
 * id vacío:      crea la categoría o, si hay una eliminada con el mismo nombre, la reactiva.
 * id con valor:  edita una categoría activa.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/CategoriaDAO.php';

requiere_rol(ROL_ADMIN);
exigir_post_con_csrf('categorias.php');

$volverAlForm = 'categorias.php#form-categoria';

$esEdicion = trim(post_texto('id')) !== '';
$id        = $esEdicion ? post_id('id') : null;
$nombre    = trim(post_texto('nombre'));

// ---- Validación -------------------------------------------------------------
if ($esEdicion && $id === null) {
    fallar('El identificador de la categoría no es válido.', $volverAlForm);
}
if ($nombre === '') {
    fallar('El nombre de la categoría es obligatorio.', $volverAlForm);
}
if (mb_strlen($nombre) > NOMBRE_MAX_CATEGORIA) {
    fallar('El nombre de la categoría no puede superar los ' . NOMBRE_MAX_CATEGORIA . ' caracteres.', $volverAlForm);
}

$categorias = new CategoriaDAO();

if ($esEdicion && $categorias->obtenerActiva($id) === null) {
    fallar('La categoría que intentas editar no existe o fue eliminada.', 'categorias.php');
}

// ---- Nombre repetido (el UNIQUE de la tabla incluye a las eliminadas) --------
$existente = $categorias->buscarPorNombre($nombre, $id ?? 0);

if ($existente !== null && (int) $existente['activo'] === 1) {
    fallar('Ya existe una categoría registrada con ese nombre.', $volverAlForm);
}
if ($existente !== null && $esEdicion) {
    fallar('Ese nombre pertenece a una categoría eliminada; créala nuevamente para reactivarla.', $volverAlForm);
}

// ---- Guardar ------------------------------------------------------------------
try {
    if ($esEdicion) {
        $categorias->renombrar($id, $nombre);
        terminar('exito', 'La categoría se actualizó correctamente.', 'categorias.php');
    }
    if ($existente !== null) {
        $categorias->reactivar((int) $existente['id'], $nombre);
        terminar('exito', 'La categoría "' . $nombre . '" fue reactivada correctamente.', 'categorias.php');
    }
    $categorias->crear($nombre);
    terminar('exito', 'La categoría se agregó correctamente.', 'categorias.php');
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        fallar('Ya existe una categoría registrada con ese nombre.', $volverAlForm);
    }
    throw $e;
}
