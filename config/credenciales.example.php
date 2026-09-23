<?php
/**
 * PLANTILLA de credenciales para XAMPP (local).
 *
 * 1. Copia este archivo como  config/credenciales.php
 * 2. Ajusta los datos si tu MySQL no es el de XAMPP por defecto.
 *
 * Para el hosting usa config/credenciales.hosting.example.php.
 * config/credenciales.php está en .gitignore: NUNCA se sube al repositorio.
 */

return [
    // 'local' muestra los errores en pantalla; 'hosting' los oculta y los registra en logs/
    'entorno' => 'local',

    'bd' => [
        'host'     => 'localhost',
        'usuario'  => 'root',
        'clave'    => '',            // XAMPP trae root sin contraseña
        'base'     => 'coffeedesk',
        'puerto'   => 3306,
        'base_url' => '/coffeedesk', // carpeta dentro de htdocs
    ],

    // Redirige http:// → https://. En local siempre false.
    'forzar_https' => false,
];
