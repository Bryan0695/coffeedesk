<?php
/**
 * Inventario, alertas de stock — PÁGINA BASE. Responsable del módulo: Jeremy.
 * El control de acceso ya está puesto; reemplaza el contenido de <main>.
 */
require_once __DIR__ . '/php/auth/sesion.php';
requiere_rol(ROL_ADMIN);

$tituloPagina = 'Inventario';
require __DIR__ . '/php/partials/cabecera.php';
?>
        <h1>Inventario</h1>
        <p>Módulo en construcción (Jeremy).</p>
<?php require __DIR__ . '/php/partials/pie.php'; ?>
