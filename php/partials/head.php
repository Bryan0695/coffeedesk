<?php
/**
 * Inicio común del documento: DOCTYPE, <html> y <head>.
 * La página puede definir antes  $tituloPagina = 'Menú';
 * Después de incluirlo, la página abre su propio <body>.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina ?? 'CoffeeDesk') ?> | CoffeeDesk</title>
    <link rel="stylesheet" href="<?= e(url('css/estilos.css')) ?>">
</head>
