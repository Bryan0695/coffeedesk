<?php
/**
 * Conexión PHP–MySQL (mysqli).
 * Todos los módulos obtienen la conexión con:  $db = conectar();
 *
 * Responsable: Bryan Gallegos
 */

require_once __DIR__ . '/../config/config.php';

// Hace que mysqli lance excepciones en lugar de fallar en silencio
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function conectar(): mysqli
{
    static $conexion = null; // se reutiliza la misma conexión en cada petición

    if ($conexion instanceof mysqli) {
        return $conexion;
    }

    try {
        $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conexion->set_charset('utf8mb4'); // tildes y ñ correctas
        return $conexion;
    } catch (mysqli_sql_exception $e) {
        error_log('[CoffeeDesk] Error de conexión: ' . $e->getMessage());
        http_response_code(500);
        exit(ENTORNO === 'local'
            ? 'No se pudo conectar a MySQL: ' . htmlspecialchars($e->getMessage())
            : 'Servicio no disponible. Intenta más tarde.');
    }
}
