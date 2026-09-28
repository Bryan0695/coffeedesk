<?php

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';

requiere_rol_api(ROL_ADMIN);

$productoId = post_entero(
    'producto_id'
);

$cantidad = post_entero(
    'cantidad'
);

if (
    $productoId === null ||
    $productoId <= 0
) {

    responder_json(
        'error',
        'Producto inválido.',
        null,
        422
    );
}

if (
    $cantidad === null ||
    $cantidad <= 0
) {

    responder_json(
        'error',
        'Cantidad inválida.',
        null,
        422
    );
}

$dao = new InventarioDAO();

$dao->descontarStock(
    $productoId,
    $cantidad
);

responder_json(
    'exito',
    'Stock actualizado correctamente.'
);