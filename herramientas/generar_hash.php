<?php
/**
 * Genera el hash de una contraseña para insertarlo en la tabla usuarios.
 * Uso (desde la consola, en la carpeta del proyecto):
 *
 *   C:\xampp\php\php.exe herramientas\generar_hash.php "MiClave123"
 *
 * Para crear el administrador del hosting usa mejor herramientas/crear_admin.php
 * (no deja la contraseña en el historial de la consola).
 *
 * Solo se ejecuta por consola; no funciona desde el navegador.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/constantes.php';

if ($argc < 2) {
    fwrite(STDERR, "Uso: php herramientas/generar_hash.php \"contraseña\"\n");
    exit(1);
}
echo password_hash($argv[1], PASSWORD_DEFAULT, HASH_OPCIONES), PHP_EOL;
