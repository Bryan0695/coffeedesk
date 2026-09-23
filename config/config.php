<?php
/**
 * Configuración general de CoffeeDesk.
 * Carga las credenciales del entorno (local o hosting), las constantes
 * y la configuración de errores.
 *
 * Responsable: Bryan Gallegos
 */

// Zona horaria de Ecuador para fechas de pedidos (MySQL se alinea en conectar(), F-012)
date_default_timezone_set('America/Guayaquil');

require_once __DIR__ . '/constantes.php';

// ---- 1. Cargar credenciales ---------------------------------------------
$archivoCredenciales = __DIR__ . '/credenciales.php';
if (!file_exists($archivoCredenciales)) {
    http_response_code(500);
    exit('Falta config/credenciales.php. Copia config/credenciales.example.php y complétalo.');
}
$credenciales = require $archivoCredenciales;

// ---- 2. Entorno (F-004) --------------------------------------------------
// Formato nuevo: el propio archivo declara su entorno. El servidor decide,
// no la cabecera Host que envía el navegador.
if (is_array($credenciales) && array_key_exists('entorno', $credenciales)) {
    if (!in_array($credenciales['entorno'], ['local', 'hosting'], true) || !isset($credenciales['bd'])) {
        http_response_code(500);
        exit('config/credenciales.php debe declarar entorno = local | hosting y el bloque bd.');
    }
    $entorno     = $credenciales['entorno'];
    $bd          = $credenciales['bd'];
    $forzarHttps = (bool) ($credenciales['forzar_https'] ?? false);
    $formatoViejo = false;
} else {
    // Formato anterior (bloques 'local' y 'hosting'): se sigue aceptando para no
    // romper las copias de los compañeros, pero el entorno depende del Host.
    // Migrar a config/credenciales.example.php cuanto antes.
    $hostActual = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0]);
    $entorno     = in_array($hostActual, ['localhost', '127.0.0.1', '::1'], true) ? 'local' : 'hosting';
    $bd          = $credenciales[$entorno] ?? null;
    $forzarHttps = false;
    $formatoViejo = true;
    if (!is_array($bd)) {
        http_response_code(500);
        exit('config/credenciales.php no tiene el bloque "' . $entorno . '".');
    }
}

define('ENTORNO', $entorno);
define('CREDENCIALES_FORMATO_VIEJO', $formatoViejo);
define('FORZAR_HTTPS', $forzarHttps);

define('DB_HOST',   $bd['host']);
define('DB_USER',   $bd['usuario']);
define('DB_PASS',   $bd['clave']);
define('DB_NAME',   $bd['base']);
define('DB_PORT',   (int) $bd['puerto']);
define('BASE_URL',  rtrim($bd['base_url'], '/')); // para armar enlaces y redirecciones

unset($credenciales, $bd, $entorno, $forzarHttps, $formatoViejo, $hostActual);

// ---- 3. Errores (F-005) ---------------------------------------------------
require_once __DIR__ . '/../php/comun/errores.php';
configurar_errores();

if (CREDENCIALES_FORMATO_VIEJO && ENTORNO === 'local') {
    error_log('[CoffeeDesk] config/credenciales.php usa el formato viejo; migrarlo a credenciales.example.php.');
}

/** Devuelve una URL absoluta dentro de la app: url('panel.php') */
function url(string $ruta = ''): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}
