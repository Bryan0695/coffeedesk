<?php

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/ProductoBusquedaDAO.php';

requiere_rol_api(ROL_ADMIN);

$categoriaId = get_entero('categoria_id');

if ($categoriaId === null || $categoriaId <= 0) {
    responder_json(
        'error',
        'Categoría inválida.',
        null,
        422
    );
}

$dao = new ProductoBusquedaDAO();

$datos = $dao->filtrarPorCategoria(
    $categoriaId
);

responder_json(
    'exito',
    'Productos filtrados.',
    $datos
);