<?php
/**
 * Cierra la sesión. Se llama con POST desde el botón "Cerrar sesión"
 * (POST + CSRF evita que un enlace externo cierre la sesión del usuario).
 *
 * Responsable: Bryan Gallegos
 */

require_once __DIR__ . '/sesion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido(post_texto('csrf'))) {
    redirigir('panel.php');
}

cerrar_sesion();
mensaje_flash('exito', 'Cerraste sesión correctamente.');
redirigir('index.php');
