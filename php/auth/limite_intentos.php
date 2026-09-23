<?php
/**
 * Límite de intentos de inicio de sesión guardado en la BD (F-002).
 *
 * Antes el contador vivía en la sesión, y bastaba con borrar la cookie para
 * seguir probando contraseñas. Ahora se cuenta por usuario y por IP en la
 * tabla intentos_login (sql/02_intentos_login.sql).
 *
 * Requiere php/conexion.php.
 */

/**
 * IP del cliente. En InfinityFree puede ser la IP del proxy: en ese caso el
 * límite por usuario sigue funcionando y el límite por IP pasa a ser global
 * (por eso LOGIN_MAX_INTENTOS_IP es más alto).
 */
function ip_cliente(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** ¿Se superó el límite de intentos fallidos para este usuario o esta IP? */
function login_bloqueado(string $usuario, string $ip): bool
{
    $f = consultar_uno(
        'SELECT COALESCE(SUM(usuario = ?), 0) AS por_usuario,
                COALESCE(SUM(ip = ?), 0)      AS por_ip
         FROM intentos_login
         WHERE creado_en > NOW() - INTERVAL ? MINUTE
           AND (usuario = ? OR ip = ?)',
        [$usuario, $ip, LOGIN_MINUTOS_BLOQUEO, $usuario, $ip]
    );
    return (int) $f['por_usuario'] >= LOGIN_MAX_INTENTOS
        || (int) $f['por_ip'] >= LOGIN_MAX_INTENTOS_IP;
}

function registrar_intento_fallido(string $usuario, string $ip): void
{
    insertar('INSERT INTO intentos_login (usuario, ip) VALUES (?, ?)', [$usuario, $ip]);
}

/** Tras un inicio de sesión correcto: borra los intentos del usuario y los registros viejos. */
function limpiar_intentos(string $usuario): void
{
    ejecutar(
        'DELETE FROM intentos_login WHERE usuario = ? OR creado_en < NOW() - INTERVAL 1 DAY',
        [$usuario]
    );
}
