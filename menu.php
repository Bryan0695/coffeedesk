<?php
/**
 * Gestión del menú (el mesero solo consulta; crear/editar/eliminar requiere ROL_ADMIN) — PÁGINA BASE. Responsable del módulo: Gabo.
 * El control de acceso ya está puesto; reemplaza el contenido de <main>.
 */
require_once __DIR__ . '/php/auth/sesion.php';
requiere_rol(ROL_ADMIN, ROL_MESERO);

$tituloPagina = 'Menú';
require __DIR__ . '/php/partials/cabecera.php';
?>
        <h1>Menú</h1>
        <p>Módulo en construcción (Gabo).</p>
<?php require __DIR__ . '/php/partials/pie.php'; ?>
