<?php
/**
 * Respuestas HTTP: redirección y JSON. Ambas terminan la ejecución.
 * Requiere config/config.php (url()).
 */

function redirigir(string $ruta): void
{
    header('Location: ' . url($ruta));
    exit;
}

/**
 * Respuesta JSON con el formato común del proyecto: { estado, mensaje, datos }.
 * Termina la ejecución.
 */
function responder_json(string $estado, string $mensaje, $datos = null, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['estado' => $estado, 'mensaje' => $mensaje, 'datos' => $datos],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
