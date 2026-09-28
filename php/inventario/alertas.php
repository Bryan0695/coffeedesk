<?php

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';

requiere_rol_api(ROL_ADMIN);

$dao = new InventarioDAO();

$datos = $dao->obtenerStockBajo();

responder_json(
    'exito',
    'Alertas obtenidas.',
    $datos
);