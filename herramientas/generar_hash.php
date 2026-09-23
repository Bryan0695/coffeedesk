<?php
/**
 * Genera el hash de una contraseña para insertarlo en la tabla usuarios.
 * Uso (desde la consola, en la carpeta del proyecto):
 *
 *   C:\xampp\php\php.exe herramientas\generar_hash.php "MiClave123"
 *
 * Solo se ejecuta por consola; no funciona desde el navegador.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if ($argc < 2) {
    fwrite(STDERR, "Uso: php herramientas/generar_hash.php \"contraseña\"\n");
    exit(1);
}
echo password_hash($argv[1], PASSWORD_DEFAULT), PHP_EOL;
