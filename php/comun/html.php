<?php
/**
 * Utilidades para imprimir HTML.
 */

/** Escapa texto para imprimirlo en HTML (evita XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** Formatea un importe en centavos como dólares: 350 → "$3.50". */
function dinero(int $centavos): string
{
    return '$' . number_format($centavos / 100, 2, '.', ',');
}

/**
 * Icono SVG en línea (trazos de 24×24, estilo Lucide). Es decorativo: el texto
 * o el aria-label del elemento que lo contiene es el que da el nombre accesible.
 */
function icono(string $nombre): string
{
    $trazos = [
        'taza'       => '<path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><path d="M6 2v2M10 2v2M14 2v2"/>',
        'inicio'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
        'pedidos'    => '<path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2Z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'menu'       => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5Z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>',
        'inventario' => '<path d="M21 8 12 3 3 8v8l9 5 9-5Z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
        'salir'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'buscar'     => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'mas'        => '<path d="M12 5v14M5 12h14"/>',
        'editar'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'eliminar'   => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/>',
        'alerta'     => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
        'flecha'     => '<path d="M5 12h14M13 5l7 7-7 7"/>',
        'ok'         => '<path d="M20 6 9 17l-5-5"/>',
    ];
    return '<svg class="icono" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
         . ($trazos[$nombre] ?? '') . '</svg>';
}
