<?php
/**
 * Límite de intentos de inicio de sesión guardado en la BD (F-002).
 *
 * Antes el contador vivía en la sesión, y bastaba con borrar la cookie para
 * seguir probando contraseñas. Ahora se cuenta en la tabla intentos_login
 * (sql/02_intentos_login.sql) con dos límites:
 *   - LOGIN_MAX_INTENTOS por usuario DESDE UNA MISMA IP. Contar solo por usuario
 *     permitía que cualquiera bloqueara la cuenta del administrador enviando
 *     5 claves falsas cada 5 minutos desde otro equipo (M1 de la revisión).
 *   - LOGIN_MAX_INTENTOS_IP por IP, para cualquier usuario.
 * No hay un tope global por usuario a propósito: cualquier tope así permite
 * dejar fuera al administrador desde otro equipo. Contra quien prueba claves
 * desde muchas IP protegen bcrypt (coste 12) y la clave del administrador del
 * hosting (crear_admin.php exige 10 caracteres o más).
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

/**
 * Pone en fila los intentos que llegan desde una misma IP hasta que termine la
 * petición (MySQL suelta el bloqueo al cerrar la conexión). Sin esto, N
 * peticiones en paralelo pasaban todas login_bloqueado() antes de que se
 * registrara el primer fallo (B1 de la revisión), tanto con un mismo usuario
 * como repartidas entre varios. Devuelve false si no se obtuvo en 10 segundos.
 */
function esperar_turno_de_login(string $ip): bool
{
    // Nombre ≤ 64 caracteres: 17 del prefijo + 45 como máximo de la IP
    $fila = consultar_uno('SELECT GET_LOCK(?, 10) AS obtenido', ['coffeedesk_login_' . $ip]);
    return (int) ($fila['obtenido'] ?? 0) === 1;
}

/** ¿Se superó el límite de intentos fallidos de este usuario desde esta IP, o de esta IP? */
function login_bloqueado(string $usuario, string $ip): bool
{
    $f = consultar_uno(
        'SELECT COALESCE(SUM(usuario = ?), 0) AS por_usuario,
                COUNT(*)                     AS por_ip
         FROM intentos_login
         WHERE ip = ?
           AND creado_en > NOW() - INTERVAL ? MINUTE',
        [$usuario, $ip, LOGIN_MINUTOS_BLOQUEO]
    );
    return (int) $f['por_usuario'] >= LOGIN_MAX_INTENTOS
        || (int) $f['por_ip'] >= LOGIN_MAX_INTENTOS_IP;
}

function registrar_intento_fallido(string $usuario, string $ip): void
{
    insertar('INSERT INTO intentos_login (usuario, ip) VALUES (?, ?)', [$usuario, $ip]);
}

/** Tras un inicio de sesión correcto: borra los intentos del usuario desde esa IP y los registros viejos. */
function limpiar_intentos(string $usuario, string $ip): void
{
    ejecutar(
        'DELETE FROM intentos_login
         WHERE (usuario = ? AND ip = ?)
            OR creado_en < NOW() - INTERVAL 1 DAY',
        [$usuario, $ip]
    );
}
