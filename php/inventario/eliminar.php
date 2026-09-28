<?php

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';

requiere_rol_api(ROL_ADMIN);

$id = post_entero('id');

if ($id === null || $id <= 0) {

    responder_json(
        'error',
        'ID inválido.',
        null,
        422
    );
}

$dao = new InventarioDAO();

$dao->eliminar($id);

responder_json(
    'exito',
    'Insumo eliminado correctamente.'
);