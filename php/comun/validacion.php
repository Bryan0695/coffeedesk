<?php
/**
 * Lectura segura de $_POST y $_GET (F-009).
 *
 * Un atacante puede enviar cualquier campo como lista (usuario[]=x). Pasar
 * ese array a trim(), password_verify() o una función tipada lanza TypeError
 * (error 500). Estas funciones devuelven siempre el tipo esperado.
 *
 *   $nombre   = trim(post_texto('nombre'));   // '' si falta o no es texto
 *   $cantidad = post_entero('cantidad');      // null si falta o no es un entero
 */

/** Texto del formulario POST, o '' si falta o no es texto. */
function post_texto(string $campo): string
{
    $v = $_POST[$campo] ?? '';
    return is_string($v) ? $v : '';
}

/** Texto de la URL (GET), o '' si falta o no es texto. */
function get_texto(string $campo): string
{
    $v = $_GET[$campo] ?? '';
    return is_string($v) ? $v : '';
}

/** Entero del formulario POST, o null si falta o no es un entero válido ("12", "-3"; no "1.5" ni "12abc"). */
function post_entero(string $campo): ?int
{
    return a_entero($_POST[$campo] ?? null);
}

/** Entero de la URL (GET), o null si falta o no es un entero válido. */
function get_entero(string $campo): ?int
{
    return a_entero($_GET[$campo] ?? null);
}

function a_entero($valor): ?int
{
    if (!is_string($valor)) {
        return null;
    }
    $n = filter_var(trim($valor), FILTER_VALIDATE_INT);
    return $n === false ? null : $n;
}
