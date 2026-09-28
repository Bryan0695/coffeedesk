<?php

function post_decimal(string $campo): ?float
{
    $valor = post_texto($campo);

    if ($valor === '') {
        return null;
    }

    if (!is_numeric($valor)) {
        return null;
    }

    return (float)$valor;
}