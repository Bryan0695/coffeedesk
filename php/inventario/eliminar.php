<?php

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';

requiere_rol(ROL_ADMIN);

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !csrf_valido(post_texto('csrf'))
) {

    mensaje_flash(
        'error',
        'Solicitud no válida.'
    );

    redirigir('inventario.php');
}

$id = post_entero('id');

if (
    $id === null ||
    $id <= 0
) {

    mensaje_flash(
        'error',
        'ID inválido.'
    );

    redirigir('inventario.php');
}

$dao = new InventarioDAO();

$dao->eliminar($id);

mensaje_flash(
    'exito',
    'Insumo eliminado.'
);

redirigir('inventario.php');