<?php
/**
 * PLANTILLA de credenciales.
 *
 * 1. Copia este archivo como  config/credenciales.php
 * 2. Rellena los datos del bloque "hosting" con los que te da InfinityFree
 *    (Panel > MySQL Databases).
 *
 * config/credenciales.php está en .gitignore: NUNCA se sube al repositorio.
 */

return [
    // XAMPP en tu computadora
    'local' => [
        'host'     => 'localhost',
        'usuario'  => 'root',
        'clave'    => '',            // XAMPP trae root sin contraseña
        'base'     => 'coffeedesk',
        'puerto'   => 3306,
        'base_url' => '/coffeedesk', // carpeta dentro de htdocs
    ],

    // InfinityFree
    'hosting' => [
        'host'     => 'sqlXXX.infinityfree.com', // "MySQL Hostname"
        'usuario'  => 'if0_XXXXXXXX',            // "MySQL Username"
        'clave'    => 'CAMBIAR',                 // contraseña de la cuenta de hosting
        'base'     => 'if0_XXXXXXXX_coffeedesk', // "MySQL DB Name"
        'puerto'   => 3306,
        'base_url' => '',                        // la app va directo en htdocs
    ],
];
