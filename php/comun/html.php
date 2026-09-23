<?php
/**
 * Utilidades para imprimir HTML.
 */

/** Escapa texto para imprimirlo en HTML (evita XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}
