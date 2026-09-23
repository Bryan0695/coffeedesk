<?php
/**
 * Configuración general de CoffeeDesk.
 * Detecta si la app corre en XAMPP (local) o en InfinityFree (hosting)
 * y carga las credenciales correspondientes.
 *
 * Responsable: Bryan Gallegos
 */

// Zona horaria de Ecuador para fechas de pedidos
date_default_timezone_set('America/Guayaquil');

// ---- 1. Detectar entorno -------------------------------------------------
$hostActual = $_SERVER['HTTP_HOST'] ?? 'localhost';
$hostActual = strtolower(explode(':', $hostActual)[0]); // quita el puerto

$esLocal = in_array($hostActual, ['localhost', '127.0.0.1', '::1'], true);
define('ENTORNO', $esLocal ? 'local' : 'hosting');

// ---- 2. Cargar credenciales ---------------------------------------------
$archivoCredenciales = __DIR__ . '/credenciales.php';
if (!file_exists($archivoCredenciales)) {
    http_response_code(500);
    exit('Falta config/credenciales.php. Copia config/credenciales.example.php y complétalo.');
}
$credenciales = require $archivoCredenciales;
$bd = $credenciales[ENTORNO];

define('DB_HOST',   $bd['host']);
define('DB_USER',   $bd['usuario']);
define('DB_PASS',   $bd['clave']);
define('DB_NAME',   $bd['base']);
define('DB_PORT',   (int) $bd['puerto']);
define('BASE_URL',  rtrim($bd['base_url'], '/')); // para armar enlaces y redirecciones

// ---- 3. Errores ----------------------------------------------------------
// En local se muestran para depurar; en el hosting se ocultan al usuario.
if (ENTORNO === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ---- 4. Reglas de sesión -------------------------------------------------
define('SESION_NOMBRE', 'COFFEEDESK_SID');
define('SESION_MINUTOS_INACTIVIDAD', 30);
define('LOGIN_MAX_INTENTOS', 5);
define('LOGIN_MINUTOS_BLOQUEO', 5);

// Roles (deben coincidir con la tabla `roles`)
define('ROL_ADMIN',  'administrador');
define('ROL_MESERO', 'mesero');

/** Devuelve una URL absoluta dentro de la app: url('panel.php') */
function url(string $ruta = ''): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}
