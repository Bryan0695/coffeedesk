<?php
/**
 * Reactivación de categorías eliminadas lógicamente.
 * activo = 0 -> activo = 1
 * Creado para no afectar la logica de guardado.
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
        'La solicitud no pudo verificarse.'
    );

    redirigir('categorias.php');
}

// Obtener ID

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
    'SELECT
        id,
        nombre,
        activo
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

// Ya está activa

if ((int) $categoria['activo'] === 1) {

    mensaje_flash(
        'aviso',
        'La categoría ya se encuentra activa.'
    );

    redirigir('categorias.php');
}

// Reactivar

ejecutar(
    'UPDATE categorias
     SET activo = 1
     WHERE id = ?
       AND activo = 0',
    [$id]
);

mensaje_flash(
    'exito',
    'La categoría "' . $categoria['nombre'] . '" fue reactivada correctamente.'
);

redirigir('categorias.php');