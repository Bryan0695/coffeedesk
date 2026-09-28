<?php

require_once '../dao/InventarioDAO.php';
require_once '../shared/Response.php';

header('Content-Type: application/json');

$nombre = trim(post_texto('nombre'));
$unidad = trim(post_texto('unidad'));
$stock = (float) post_texto('stock');
$stockMinimo = (float) post_texto('stock_minimo');

if ($nombre === '') {
    responder_json(
        'error',
        'El nombre es obligatorio',
        null,
        422
    );
}

if ($stock < 0) {
    responder_json(
        'error',
        'El stock no puede ser negativo',
        null,
        422
    );
}

if ($stockMinimo < 0) {
    responder_json(
        'error',
        'El stock mínimo no puede ser negativo',
        null,
        422
    );
}

$unidadesPermitidas = [
    'unidades',
    'kg',
    'g',
    'litros',
    'ml'
];

if (!in_array($unidad, $unidadesPermitidas, true)) {
    responder_json(
        'error',
        'Unidad inválida',
        null,
        422
    );
}

$insumo = new Insumo();

$insumo->setNombre($nombre);
$insumo->setUnidad($unidad);
$insumo->setStock($stock);
$insumo->setStockMinimo($stockMinimo);
$insumo->setActivo(1);

$dao = new InventarioDAO();

$id = $dao->crear($insumo);

responder_json(
    'exito',
    'Insumo registrado',
    [
        'id' => $id
    ]
);