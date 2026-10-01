<?php

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';
require_once __DIR__ . '/../models/Insumo.php';

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

$id = trim(post_texto('id'));

$nombre = trim(post_texto('nombre'));

$unidad = trim(post_texto('unidad'));

$stock = trim(post_texto('stock'));

$stockMinimo = trim(
    post_texto('stock_minimo')
);

$dao = new InventarioDAO();

$errores = [];

if (
    mb_strlen($nombre) < 1 ||
    mb_strlen($nombre) > 80
) {
    $errores[] =
        'Nombre inválido.';
}

$unidadesPermitidas = [
    'unidades',
    'kg',
    'g',
    'litros',
    'ml'
];

if (
    !in_array(
        $unidad,
        $unidadesPermitidas,
        true
    )
) {

    $errores[] =
        'Unidad inválida.';
}

if (
    !is_numeric($stock) ||
    (float)$stock < 0
) {

    $errores[] =
        'Stock inválido.';
}

if (
    !is_numeric($stockMinimo) ||
    (float)$stockMinimo < 0
) {

    $errores[] =
        'Stock mínimo inválido.';
}

if (
    $id === '' &&
    $dao->existeInsumo($nombre)
) {

    $errores[] =
        'Ya existe un insumo con ese nombre.';
}

if ($errores) {

    mensaje_flash(
        'error',
        $errores[0]
    );

    redirigir('inventario.php');
}

$insumo = new Insumo();

$insumo->setNombre($nombre);
$insumo->setUnidad($unidad);
$insumo->setStock((float)$stock);
$insumo->setStockMinimo(
    (float)$stockMinimo
);
$insumo->setActivo(1);

if ($id === '') {

    $dao->crear($insumo);

    mensaje_flash(
        'exito',
        'Insumo registrado.'
    );
} else {

    $insumo->setId(
        (int)$id
    );

    $dao->actualizar(
        $insumo
    );

    mensaje_flash(
        'exito',
        'Insumo actualizado.'
    );
}

redirigir('inventario.php');