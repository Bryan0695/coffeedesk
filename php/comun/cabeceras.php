<?php
/**
 * Cabeceras de seguridad y HTTPS (F-006).
 * Se envían desde PHP porque mod_headers no está garantizado en el hosting gratuito.
 *
 * La CSP no permite <script> ni style="" en línea: todo el JS va en js/ y
 * todo el CSS en css/. Mantener esa regla en los módulos nuevos.
 */

/** ¿La petición llegó por HTTPS? (también detrás de un proxy) */
function es_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        return true;
    }
    return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function enviar_cabeceras_seguridad(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    // Solo si credenciales.php lo pide: detrás del proxy de InfinityFree
    // $_SERVER['HTTPS'] puede no llegar y se formaría un bucle de redirecciones.
    if (FORZAR_HTTPS && !es_https()) {
        header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }

    $csp = "default-src 'self'; img-src 'self' data:; object-src 'none'; "
         . "base-uri 'self'; form-action 'self'; frame-ancestors 'none'";
    $nombreCsp = CSP_MODO === 'report' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';

    header($nombreCsp . ': ' . $csp);
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    if (FORZAR_HTTPS && es_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}
