<?php

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';

requiere_rol_api(ROL_ADMIN);

$dao = new InventarioDAO();

$respuesta = $dao->listar();

responder_json(
    'exito',
    'Inventario obtenido',
    $respuesta
);
