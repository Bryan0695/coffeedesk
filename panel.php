<?php
/**
 * Panel principal después de iniciar sesión.
 * Muestra accesos distintos según el rol.
 */
require_once __DIR__ . '/php/auth/sesion.php';
requiere_login();

// [archivo, icono, título, descripción]
$modulos = [
    ['pedidos.php', 'pedidos', 'Pedidos', 'Registra órdenes por mesa y calcula el total automáticamente.'],
    ['menu.php', 'menu', 'Menú', es_admin()
        ? 'Agrega, edita y marca productos como disponibles o agotados.'
        : 'Consulta productos, precios y disponibilidad.'],
];
if (es_admin()) {
    $modulos[] = ['inventario.php', 'inventario', 'Inventario', 'Controla el stock de insumos y revisa las alertas.'];
}

$tituloPagina = 'Inicio';
require __DIR__ . '/php/partials/cabecera.php';
?>
        <div class="encabezado-pagina">
            <div>
                <p class="sobretitulo">Hola, <?= e(usuario_actual()['nombre']) ?></p>
                <h1>Panel de <?= es_admin() ? 'administración' : 'atención' ?></h1>
                <p class="descripcion">Selecciona un módulo para comenzar.</p>
            </div>
        </div>

        <section aria-labelledby="titulo-accesos">
            <h2 id="titulo-accesos" class="titulo-seccion">Módulos disponibles</h2>
            <ul class="lista-accesos">
                <?php foreach ($modulos as [$archivo, $icono, $titulo, $descripcion]): ?>
                    <li>
                        <a class="acceso" href="<?= e(url($archivo)) ?>">
                            <span class="acceso-icono"><?= icono($icono) ?></span>
                            <span class="acceso-titulo"><?= e($titulo) ?></span>
                            <span class="acceso-descripcion"><?= e($descripcion) ?></span>
                            <span class="acceso-ir">Abrir <?= icono('flecha') ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
<?php require __DIR__ . '/php/partials/pie.php'; ?>
