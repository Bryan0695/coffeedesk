<?php
/**
 * Arranque de la sesión PHP con cookie segura y cierre por inactividad.
 * Requiere php/comun/cabeceras.php (es_https) y flash.php.
 */

/** Inicia la sesión con cookies seguras y controla la inactividad. */
function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1'); // rechaza identificadores inventados por el cliente
    session_name(SESION_NOMBRE);
    session_set_cookie_params([
        'lifetime' => 0,          // se borra al cerrar el navegador
        'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
        'secure'   => es_https(), // solo por HTTPS cuando exista
        'httponly' => true,       // JavaScript no puede leer la cookie
        'samesite' => 'Lax',
    ]);
    session_start();

    // Cierre automático por inactividad. Se lee la sesión directamente (no
    // usuario_actual()) para no consultar la base de datos en cada arranque.
    if (isset($_SESSION['ultimo_movimiento'], $_SESSION['usuario'])) {
        $inactivo = time() - $_SESSION['ultimo_movimiento'];
        if ($inactivo > SESION_MINUTOS_INACTIVIDAD * 60) {
            cerrar_sesion();
            mensaje_flash('aviso', 'Tu sesión expiró por inactividad. Vuelve a ingresar.');
        }
    }
    $_SESSION['ultimo_movimiento'] = time();
}
