<?php
/**
 * PLANTILLA de credenciales para InfinityFree (hosting).
 *
 * 1. Copia este archivo como  config/credenciales.php  EN EL PAQUETE que subes
 *    al hosting (no reemplaces el de tu XAMPP).
 * 2. Rellena el bloque "bd" con los datos de Panel > MySQL Databases.
 * 3. Deja 'forzar_https' => false hasta comprobar que el certificado SSL funciona
 *    (ver docs/despliegue_infinityfree.md §6); luego cámbialo a true.
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

    'forzar_https' => false,
];
