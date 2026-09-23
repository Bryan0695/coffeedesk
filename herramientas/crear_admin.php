<?php
/**
 * Crea un usuario (normalmente el primer administrador del hosting) sin
 * contraseñas públicas (F-001). Pide los datos por consola e imprime el
 * INSERT para pegarlo en phpMyAdmin (pestaña SQL) de la base del hosting.
 *
 * Uso, desde la carpeta del proyecto:
 *
 *   C:\xampp\php\php.exe herramientas\crear_admin.php
 *
 * No se conecta a ninguna base de datos: solo genera el SQL.
 * En Windows la contraseña se ve mientras se escribe (no hay forma portable
 * de ocultarla); asegúrate de que nadie mira la pantalla.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/constantes.php';

const CLAVES_PROHIBIDAS = ['admin123*', 'mesero123*'];   // las de 90_seed_solo_local.sql
const CLAVE_MINIMO = 10;

function preguntar(string $texto): string
{
    fwrite(STDOUT, $texto);
    $linea = fgets(STDIN);
    if ($linea === false) {
        fwrite(STDERR, PHP_EOL . "Entrada cerrada. Cancelado." . PHP_EOL);
        exit(1);
    }
    return trim($linea);
}

/** Lee una contraseña sin mostrarla cuando es posible (Linux/macOS/Git Bash con stty). */
function preguntar_oculto(string $texto): string
{
    $puedeOcultar = DIRECTORY_SEPARATOR === '/' && function_exists('stream_isatty') && stream_isatty(STDIN);
    if ($puedeOcultar) {
        shell_exec('stty -echo');
    }
    fwrite(STDOUT, $texto);
    $linea = fgets(STDIN);
    if ($puedeOcultar) {
        shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);
    }
    if ($linea === false) {
        fwrite(STDERR, PHP_EOL . "Entrada cerrada. Cancelado." . PHP_EOL);
        exit(1);
    }
    return rtrim($linea, "\r\n");
}

/** Literal de texto SQL seguro (comillas y barras escapadas). */
function literal_sql(string $texto): string
{
    return "'" . str_replace(['\\', "'"], ['\\\\', "''"], $texto) . "'";
}

fwrite(STDOUT, "=== Crear usuario de CoffeeDesk ===" . PHP_EOL);
if (DIRECTORY_SEPARATOR !== '/') {
    fwrite(STDOUT, "(Windows: la contraseña será visible al escribirla.)" . PHP_EOL);
}

do {
    $nombre = preguntar('Nombre completo (2 a 80 caracteres): ');
    $ok = mb_strlen($nombre) >= 2 && mb_strlen($nombre) <= 80 && !preg_match('/[\x00-\x1F\x7F]/', $nombre);
    if (!$ok) {
        fwrite(STDOUT, "  Nombre no válido." . PHP_EOL);
    }
} while (!$ok);

do {
    $usuario = preguntar('Usuario (3 a 30: letras, números, punto, guion, guion bajo): ');
    $ok = (bool) preg_match('/^' . PATRON_USUARIO . '$/', $usuario);
    if (!$ok) {
        fwrite(STDOUT, "  Usuario no válido." . PHP_EOL);
    }
} while (!$ok);

do {
    $rol = preguntar('Rol [' . ROL_ADMIN . '/' . ROL_MESERO . '] (Enter = ' . ROL_ADMIN . '): ');
    $rol = $rol === '' ? ROL_ADMIN : strtolower($rol);
    $ok = in_array($rol, [ROL_ADMIN, ROL_MESERO], true);
    if (!$ok) {
        fwrite(STDOUT, "  Rol no válido." . PHP_EOL);
    }
} while (!$ok);

do {
    $clave = preguntar_oculto('Contraseña (mínimo ' . CLAVE_MINIMO . ' caracteres): ');
    $ok = true;
    if (strlen($clave) < CLAVE_MINIMO) {
        fwrite(STDOUT, "  Demasiado corta." . PHP_EOL);
        $ok = false;
    } elseif (in_array(strtolower($clave), CLAVES_PROHIBIDAS, true)) {
        fwrite(STDOUT, "  Esa es una contraseña de prueba pública. Elige otra." . PHP_EOL);
        $ok = false;
    } else {
        $repetida = preguntar_oculto('Repite la contraseña: ');
        if (!hash_equals($clave, $repetida)) {
            fwrite(STDOUT, "  No coinciden." . PHP_EOL);
            $ok = false;
        }
    }
} while (!$ok);

$hash = password_hash($clave, PASSWORD_DEFAULT, HASH_OPCIONES);

fwrite(STDOUT, PHP_EOL . "-- Pega esto en phpMyAdmin > SQL (base del hosting):" . PHP_EOL);
fwrite(STDOUT, 'INSERT INTO usuarios (nombre, usuario, clave_hash, rol_id)' . PHP_EOL
    . 'SELECT ' . literal_sql($nombre) . ', ' . literal_sql($usuario) . ', ' . literal_sql($hash) . ', id' . PHP_EOL
    . 'FROM roles WHERE nombre = ' . literal_sql($rol) . ';' . PHP_EOL);
