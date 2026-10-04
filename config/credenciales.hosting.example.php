<?php
/**
 * PLANTILLA de credenciales para InfinityFree (hosting).
 *
 * 1. Copia este archivo como  config/credenciales.php  EN EL PAQUETE que subes
 *    al hosting (no reemplaces el de tu XAMPP).
 * 2. Rellena el bloque "bd" con los datos de Panel > MySQL Databases.
 * 3. Escribe en 'dominio' el dominio del sitio (sin https:// ni barras).
 * 4. Deja 'forzar_https' => false solo hasta comprobar que el certificado SSL
 *    funciona (ver docs/despliegue_infinityfree.md §6) y cámbialo a true en
 *    cuanto funcione: mientras sea false, la cookie de sesión viaja sin cifrar
 *    y otro equipo de la misma wifi podría robar la sesión.
 *
 * config/credenciales.php está en .gitignore: NUNCA se sube al repositorio.
 */

return [
    'entorno' => 'hosting',

    'bd' => [
        'host'     => 'sqlXXX.infinityfree.com', // "MySQL Hostname"
        'usuario'  => 'if0_XXXXXXXX',            // "MySQL Username"
        'clave'    => 'CAMBIAR',                 // contraseña de la cuenta de hosting
        'base'     => 'if0_XXXXXXXX_coffeedesk', // "MySQL DB Name"
        'puerto'   => 3306,
        'base_url' => '',                        // la app va directo en htdocs
    ],

    // Dominio público del sitio: la redirección a HTTPS se arma con él, nunca
    // con la cabecera Host que envía el cliente.
    'dominio' => 'coffeedesk.infinityfreeapp.com',

    // Redirige http:// → https:// y marca la cookie de sesión como Secure.
    // Cambiar a true en cuanto el SSL funcione (ver el paso 4).
    'forzar_https' => false,
];
