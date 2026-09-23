<?php
/**
 * Registro y presentación de errores (F-005).
 *
 * - Siempre se detectan y registran todos los errores (logs/php_error.log).
 * - Solo en local se muestran en pantalla.
 * - Cualquier excepción no capturada responde 500 con un código de referencia
 *   que también queda en el log, para poder encontrarla después.
 *
 * Responsable: Bryan Gallegos
 */

function configurar_errores(): void
{
    error_reporting(E_ALL);
    ini_set('display_errors', ENTORNO === 'local' ? '1' : '0');
    ini_set('log_errors', '1');
    // Las trazas no incluyen argumentos: evita que la contraseña de la BD
    // (argumento de new mysqli) aparezca en pantalla o en el log.
    ini_set('zend.exception_ignore_args', '1');

    $carpetaLogs = dirname(__DIR__, 2) . '/logs';
    if (is_dir($carpetaLogs) && is_writable($carpetaLogs)) {
        ini_set('error_log', $carpetaLogs . '/php_error.log');
    }

    set_exception_handler('manejar_excepcion');
}

/** Manejador global: registra la excepción y responde 500 (HTML o JSON). */
function manejar_excepcion(Throwable $e): void
{
    $referencia = strtoupper(bin2hex(random_bytes(4)));
    error_log('[CoffeeDesk][ref ' . $referencia . '] ' . $e);

    $mensaje = 'Ocurrió un error inesperado. Código de referencia: ' . $referencia;
    $quiereJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: ' . ($quiereJson ? 'application/json' : 'text/html') . '; charset=utf-8');
    }

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $mensaje . PHP_EOL . (ENTORNO === 'local' ? $e . PHP_EOL : ''));
        return;
    }

    if ($quiereJson) {
        $datos = ['referencia' => $referencia];
        if (ENTORNO === 'local') {
            $datos['detalle'] = $e->getMessage();
        }
        echo json_encode(['estado' => 'error', 'mensaje' => $mensaje, 'datos' => $datos], JSON_UNESCAPED_UNICODE);
        return;
    }

    $detalle = ENTORNO === 'local'
        ? '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>'
        : '<p>Intenta de nuevo en unos minutos. Si el problema continúa, comunica este código al administrador.</p>';

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
        . '<title>Error | CoffeeDesk</title></head><body>'
        . '<h1>Algo salió mal</h1>'
        . '<p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>'
        . $detalle
        . '</body></html>';
}
