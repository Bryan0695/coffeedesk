<?php
/**
 * Crear y editar productos del menú (solo administrador).
 *
 * id vacío:      crea el producto o, si hay uno eliminado con el mismo nombre, lo reactiva.
 * id con valor:  edita un producto activo.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/ProductoDAO.php';
require_once __DIR__ . '/../dao/CategoriaDAO.php';

requiere_rol(ROL_ADMIN);
exigir_post_con_csrf('menu.php');

$volverAlForm = 'menu.php#form-producto';

$esEdicion      = trim(post_texto('id')) !== '';
$id             = $esEdicion ? post_id('id') : null;
$nombre         = trim(post_texto('nombre'));
$categoriaId    = post_id('categoria_id');
$precioCentavos = texto_a_centavos(post_texto('precio'));
$disponible     = isset($_POST['disponible']); // un checkbox sin marcar no se envía

// ---- Validación -------------------------------------------------------------
if ($esEdicion && $id === null) {
    fallar('El identificador del producto no es válido.', $volverAlForm);
}
if ($nombre === '') {
    fallar('El nombre del producto es obligatorio.', $volverAlForm);
}
if (mb_strlen($nombre) > NOMBRE_MAX_PRODUCTO) {
    fallar('El nombre del producto no puede superar los ' . NOMBRE_MAX_PRODUCTO . ' caracteres.', $volverAlForm);
}
if ($categoriaId === null) {
    fallar('Debes seleccionar una categoría válida.', $volverAlForm);
}
if ($precioCentavos === null || $precioCentavos < 1) {
    fallar('El precio debe ser un número válido entre 0.01 y 999.99.', $volverAlForm);
}

$productos  = new ProductoDAO();
$categorias = new CategoriaDAO();

if ($categorias->obtenerActiva($categoriaId) === null) {
    fallar('La categoría seleccionada no existe o ya no está activa.', $volverAlForm);
}
if ($esEdicion && $productos->obtenerActivo($id) === null) {
    fallar('El producto que intentas editar no existe o fue eliminado.', 'menu.php');
}

$precio = centavos_a_decimal($precioCentavos);

// ---- Nombre repetido (el UNIQUE de la tabla incluye a los eliminados) --------
$existente = $productos->buscarPorNombre($nombre, $id ?? 0);

if ($existente !== null && (int) $existente['activo'] === 1) {
    fallar('Ya existe un producto registrado con ese nombre.', $volverAlForm);
}
if ($existente !== null && $esEdicion) {
    fallar('Ese nombre pertenece a un producto eliminado; créalo de nuevo para reactivarlo.', $volverAlForm);
}

// ---- Guardar ------------------------------------------------------------------
try {
    if ($esEdicion) {
        $productos->actualizar($id, $categoriaId, $nombre, $precio, $disponible);
        terminar('exito', 'El producto se actualizó correctamente.', 'menu.php');
    }
    if ($existente !== null) {
        // Estaba eliminado: se reactiva el mismo registro en lugar de crear otro
        $productos->reactivar((int) $existente['id'], $categoriaId, $nombre, $precio, $disponible);
        terminar('exito', 'El producto "' . $nombre . '" volvió al menú.', 'menu.php');
    }
    $productos->crear($categoriaId, $nombre, $precio, $disponible);
    terminar('exito', 'El producto se agregó correctamente.', 'menu.php');
} catch (mysqli_sql_exception $e) {
    // 1062 = nombre duplicado: la restricción UNIQUE es la protección definitiva
    // si dos administradores guardan el mismo nombre a la vez.
    if ($e->getCode() === 1062) {
        fallar('Ya existe un producto registrado con ese nombre.', $volverAlForm);
    }
    throw $e;
}
