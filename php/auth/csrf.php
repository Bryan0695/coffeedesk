<?php
/**
 * Token CSRF para formularios POST.
 *
 *   <form method="post"> <?= csrf_campo() ?> … </form>
 *   if (!csrf_valido(post_texto('csrf'))) { … }
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Imprime el campo oculto dentro de un <form method="post"> */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

/**
 * Compara el token recibido con el de la sesión en tiempo constante.
 * Acepta cualquier valor (p. ej. un array enviado como csrf[]=x) y en ese
 * caso devuelve false en lugar de lanzar TypeError (F-009).
 */
function csrf_valido($token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}
