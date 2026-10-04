<?php
/**
 * Lectura segura de $_POST y $_GET (F-009) y validaciones comunes.
 *
 * Un atacante puede enviar cualquier campo como lista (usuario[]=x). Pasar
 * ese array a trim(), password_verify() o una función tipada lanza TypeError
 * (error 500). Estas funciones devuelven siempre el tipo esperado.
 *
 *   $nombre   = trim(post_texto('nombre'));   // '' si falta o no es texto
 *   $cantidad = post_entero('cantidad');      // null si falta o no es un entero
 *   $id       = post_id('id');                // null si falta, no es entero o es ≤ 0
 */

/** Texto del formulario POST, o '' si falta o no es texto. */
function post_texto(string $campo): string
{
    $v = $_POST[$campo] ?? '';
    return is_string($v) ? $v : '';
}

/** Entero del formulario POST, o null si falta o no es un entero válido ("12", "-3"; no "1.5" ni "12abc"). */
function post_entero(string $campo): ?int
{
    return a_entero($_POST[$campo] ?? null);
}

/** Identificador (entero > 0) del formulario POST, o null. */
function post_id(string $campo): ?int
{
    return id_valido(post_entero($campo));
}

/** Identificador (entero > 0) de la URL (GET), o null. */
function get_id(string $campo): ?int
{
    return id_valido(a_entero($_GET[$campo] ?? null));
}

/** Convierte un texto en entero; null si no es texto o no es un entero válido. */
function a_entero($valor): ?int
{
    if (!is_string($valor)) {
        return null;
    }
    $n = filter_var(trim($valor), FILTER_VALIDATE_INT);
    return $n === false ? null : $n;
}

function id_valido(?int $n): ?int
{
    return $n !== null && $n > 0 ? $n : null;
}

/**
 * Cantidad de 0 a 99999 con hasta 3 decimales (lo que admite DECIMAL(12,3)):
 * stock de insumos y cantidades de las recetas. Se valida como texto y se
 * guarda como texto: MySQL lo convierte a DECIMAL sin pasar por float.
 */
function cantidad_valida(string $valor): bool
{
    return preg_match('/^\d{1,5}(\.\d{1,3})?$/', $valor) === 1 && (float) $valor <= 99999;
}

/** Como cantidad_valida(), pero además mayor que cero (cantidades de una receta). */
function cantidad_positiva(string $valor): bool
{
    return cantidad_valida($valor) && preg_match('/[1-9]/', $valor) === 1;
}

/** ¿El texto tiene entre 1 y $maximo caracteres (contando tildes y ñ como uno)? */
function largo_valido(string $texto, int $maximo): bool
{
    $largo = mb_strlen($texto);
    return $largo >= 1 && $largo <= $maximo;
}
