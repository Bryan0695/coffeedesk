<?php
/**
 * Crear, editar y reactivar categorías.
 *
 * id vacío:
 *   - crea una categoría nueva;
 *   - o reactiva una categoría eliminada con el mismo nombre.
 *
 * id con valor:
 *   - edita una categoría activa existente.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../conexion.php';

// Seguridad
requiere_rol(ROL_ADMIN);

// Solo POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    mensaje_flash(
        'error',
        'La operación solicitada no es válida.'
    );

     redirigir('categorias.php');
}

// CSRF.
if (!csrf_valido(post_texto('csrf'))) {

    mensaje_flash(
        'error',
        'La solicitud no pudo verificarse. Recarga la página e inténtalo nuevamente.'
    );

     redirigir('categorias.php');
}

// Obtener datos
$idTexto        = trim(post_texto('id'));
$nombre         = trim(post_texto('nombre'));
$esEdicion      = $idTexto !== '';

$id = null;

if ($esEdicion) {
    $id = post_entero('id');
}

// Validaciones de Edición

if ($esEdicion && ($id === null || $id <= 0)) {

    mensaje_flash(
        'error',
        'El identificador de la categoría no es válido.'
    );

     redirigir('categorias.php');
}

if ($nombre === '') {

    mensaje_flash(
        'error',
        'El nombre de la categoría es obligatorio.'
    );

     redirigir('categorias.php');
}

if (mb_strlen($nombre) > 60) {

    mensaje_flash(
        'error',
        'El nombre de la categoría no puede superar los 60 caracteres.'
    );

     redirigir('categorias.php');
}

// Si estamos editando, comprobar que la categoría exista y esté activa

if ($esEdicion) {

    $categoriaActual = consultar_uno(
        'SELECT id, nombre, activo
         FROM categorias
         WHERE id = ?
           AND activo = 1
         LIMIT 1',
        [$id]
    );

    if ($categoriaActual === null) {

        mensaje_flash(
            'error',
            'La categoría que intentas editar no existe o fue eliminada.'
        );

         redirigir('categorias.php');
    }
}

// -----------------------------------------------------------------------------
// Buscar otra categoría con el mismo nombre
// Casos:
// 1. No existe:
//      continuar.
// 2. Existe y activo = 1:
//      error.
// 3. Existe y activo = 0, y estamos creando:
//      reactivar.
// 4. Existe y activo = 0, y estamos editando otra:
//      error.
// -----------------------------------------------------------------------------

$existente = consultar_uno(
    'SELECT id, nombre, activo
     FROM categorias
     WHERE nombre = ?
       AND id <> ?
     LIMIT 1',
    [
        $nombre,
        $id ?? 0
    ]
);

if ($existente !== null) {

    // Categoría activa con el mismo nombre.
    if ((int) $existente['activo'] === 1) {

        mensaje_flash(
            'error',
            'Ya existe una categoría registrada con ese nombre.'
        );

         redirigir('categorias.php');
    }

    // Categoría eliminada encontrada durante una creación.
    if (!$esEdicion) {

        ejecutar(
            'UPDATE categorias
             SET
                nombre = ?,
                activo = 1
             WHERE id = ?',
            [
                $nombre,
                (int) $existente['id']
            ]
        );

        mensaje_flash(
            'exito',
            'La categoría "' . $nombre . '" fue reactivada correctamente.'
        );

         redirigir('categorias.php');
    }

    // Estamos editando otra categoría.
    mensaje_flash(
        'error',
        'Ese nombre pertenece a una categoría eliminada; créala nuevamente para reactivarla.'
    );

     redirigir('categorias.php');
}

// Guardar
try {

    // EDITAR
    if ($esEdicion) {

        ejecutar(
            'UPDATE categorias
             SET nombre = ?
             WHERE id = ?
               AND activo = 1',
            [
                $nombre,
                $id
            ]
        );

        mensaje_flash(
            'exito',
            'La categoría se actualizó correctamente.'
        );

        redirigir('categorias.php');
    }

    // CREAR

    insertar(
        'INSERT INTO categorias (
            nombre,
            activo
         )
         VALUES (?, 1)',
        [$nombre]
    );

    mensaje_flash(
        'exito',
        'La categoría se agregó correctamente.'
    );

    redirigir('categorias.php');

} catch (mysqli_sql_exception $e) {

    if ((int) $e->getCode() === 1062) {

        mensaje_flash(
            'error',
            'Ya existe una categoría registrada con ese nombre.'
        );

        redirigir('categorias.php');
    }

    throw $e;
}