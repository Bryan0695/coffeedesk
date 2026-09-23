<?php
/**
 * Panel principal después de iniciar sesión.
 * Muestra accesos distintos según el rol.
 */
require_once __DIR__ . '/php/auth/sesion.php';
requiere_login();

$tituloPagina = 'Inicio';
require __DIR__ . '/php/partials/cabecera.php';
?>
        <h1>Panel de <?= es_admin() ? 'administración' : 'atención' ?></h1>
        <p>Selecciona un módulo para comenzar.</p>

        <section class="accesos" aria-labelledby="titulo-accesos">
            <h2 id="titulo-accesos">Módulos disponibles</h2>
            <ul class="lista-accesos">
                <li><a class="acceso" href="<?= e(url('pedidos.php')) ?>">Registrar y ver pedidos</a></li>
                <li><a class="acceso" href="<?= e(url('menu.php')) ?>"><?= es_admin() ? 'Gestionar el menú' : 'Consultar el menú' ?></a></li>
                <?php if (es_admin()): ?>
                    <li><a class="acceso" href="<?= e(url('inventario.php')) ?>">Controlar inventario</a></li>
                <?php endif; ?>
            </ul>
        </section>
<?php require __DIR__ . '/php/partials/pie.php'; ?>
