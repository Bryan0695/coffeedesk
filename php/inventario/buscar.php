<?php

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/ProductoBusquedaDAO.php';

requiere_rol_api(ROL_ADMIN);

$texto = trim(get_texto('q'));

$dao = new ProductoBusquedaDAO();

$datos = $dao->buscarPorNombre($texto);

responder_json(
    'exito',
    'Búsqueda realizada correctamente.',
    $datos
);