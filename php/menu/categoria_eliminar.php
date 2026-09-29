<?php
/**
 * Eliminación lógica de categorías.
 *
 * activo = 0
 *
 * No se eliminan físicamente los productos relacionados.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../conexion.php';

// Seguridad
requiere_rol(ROL_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    mensaje_flash(
        'error',
        'La operación solicitada no es válida.'
    );

    redirigir('categorias.php');
}

if (!csrf_valido(post_texto('csrf'))) {

    mensaje_flash(
        'error',
        'La solicitud no pudo verificarse.'
    );

    redirigir('categorias.php');
}

// ID

$id = post_entero('id');

if ($id === null || $id <= 0) {

    mensaje_flash(
        'error',
        'El identificador de la categoría no es válido.'
    );

    redirigir('categorias.php');
}

// Buscar categoría

$categoria = consultar_uno(
    'SELECT id, nombre, activo
     FROM categorias
     WHERE id = ?
     LIMIT 1',
    [$id]
);

if ($categoria === null) {

    mensaje_flash(
        'error',
        'La categoría no existe.'
    );

    redirigir('categorias.php');
}

// Ya eliminada

if ((int) $categoria['activo'] === 0) {

    mensaje_flash(
        'aviso',
        'La categoría ya se encuentra eliminada.'
    );

    redirigir('categorias.php');
}

// Eliminación lógica

ejecutar(
    'UPDATE categorias
     SET activo = 0
     WHERE id = ?
       AND activo = 1',
    [$id]
);

mensaje_flash(
    'exito',
    'La categoría "' . $categoria['nombre'] . '" fue eliminada.'
);

redirigir('categorias.php');