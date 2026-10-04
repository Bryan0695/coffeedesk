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
// El propio archivo declara su entorno: lo decide el servidor, no la cabecera
// Host que envía el navegador. (El formato viejo, con bloques 'local' y
// 'hosting' elegidos según el Host, ya no se acepta: B4 de la revisión.)
if (!is_array($credenciales)
    || !in_array($credenciales['entorno'] ?? null, ['local', 'hosting'], true)
    || !isset($credenciales['bd'])) {
    http_response_code(500);
    exit('config/credenciales.php debe tener el formato de config/credenciales.example.php '
        . "('entorno' => 'local' | 'hosting' y el bloque 'bd').");
}
$bd          = $credenciales['bd'];
$forzarHttps = (bool) ($credenciales['forzar_https'] ?? false);
$dominio     = strtolower(trim((string) ($credenciales['dominio'] ?? '')));

// Para redirigir a HTTPS hace falta el dominio fijo (B3): sin él, se tomaría de la petición
if ($forzarHttps && preg_match('/^[a-z0-9.-]+(:\d+)?$/', $dominio) !== 1) {
    http_response_code(500);
    exit("config/credenciales.php: con 'forzar_https' => true hay que indicar 'dominio' (p. ej. 'coffeedesk.infinityfreeapp.com').");
}

define('ENTORNO', $credenciales['entorno']);
define('FORZAR_HTTPS', $forzarHttps);
define('DOMINIO', $dominio);

define('DB_HOST',   $bd['host']);
define('DB_USER',   $bd['usuario']);
define('DB_PASS',   $bd['clave']);
define('DB_NAME',   $bd['base']);
define('DB_PORT',   (int) $bd['puerto']);
define('BASE_URL',  rtrim($bd['base_url'], '/')); // para armar enlaces y redirecciones

unset($credenciales, $bd, $forzarHttps, $dominio);

// ---- 3. Errores (F-005) ---------------------------------------------------
require_once __DIR__ . '/../php/comun/errores.php';
configurar_errores();

/** Devuelve una URL absoluta dentro de la app: url('panel.php') */
function url(string $ruta = ''): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}
